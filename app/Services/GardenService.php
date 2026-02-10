<?php

namespace App\Services;

use App\Config\ConstructionConfig;
use App\Models\CropType;
use App\Models\GardenPlot;
use App\Models\HouseFurniture;
use App\Models\HouseRoom;
use App\Models\Item;
use App\Models\PlayerHouse;
use App\Models\User;

class GardenService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected EnergyService $energyService
    ) {}

    /**
     * Plant a herb in a garden plot.
     *
     * @return array{success: bool, message: string}
     */
    public function plantHerb(User $user, string $plotSlot, int $cropTypeId): array
    {
        $house = PlayerHouse::where('player_id', $user->id)->first();
        if (! $house) {
            return ['success' => false, 'message' => 'You do not own a house.'];
        }

        $gardenRoom = HouseRoom::where('player_house_id', $house->id)
            ->where('room_type', 'garden')
            ->first();
        if (! $gardenRoom) {
            return ['success' => false, 'message' => 'You do not have a garden room.'];
        }

        if (! in_array($plotSlot, ['planter_1', 'planter_2', 'planter_3', 'planter_4'])) {
            return ['success' => false, 'message' => 'Invalid plot slot.'];
        }

        $hasFurniture = HouseFurniture::where('house_room_id', $gardenRoom->id)
            ->where('hotspot_slug', $plotSlot)
            ->exists();
        if (! $hasFurniture) {
            return ['success' => false, 'message' => 'Build a planter at this slot first.'];
        }

        $cropType = CropType::find($cropTypeId);
        if (! $cropType) {
            return ['success' => false, 'message' => 'Invalid crop type.'];
        }

        if ($cropType->slug !== 'herbs') {
            return ['success' => false, 'message' => 'Only herbs can be grown in the garden.'];
        }

        $farmingLevel = $user->skills->where('skill_name', 'farming')->first()?->level ?? 1;
        if ($farmingLevel < $cropType->farming_level_required) {
            return ['success' => false, 'message' => "You need Farming level {$cropType->farming_level_required} to plant this."];
        }

        $seedItem = $cropType->seedItem;
        if (! $seedItem || ! $this->inventoryService->hasItem($user, $seedItem)) {
            return ['success' => false, 'message' => 'You do not have the required seeds.'];
        }

        $plot = GardenPlot::firstOrCreate(
            ['player_house_id' => $house->id, 'plot_slot' => $plotSlot],
            ['status' => 'empty', 'quality' => 60]
        );

        if ($plot->status !== 'empty') {
            return ['success' => false, 'message' => 'This plot is not empty.'];
        }

        $this->inventoryService->removeItem($user, $seedItem);

        $autoWater = $this->hasAutoWater($gardenRoom);
        $plot->plant($cropType, $autoWater);

        return ['success' => true, 'message' => "Planted {$cropType->name} in your garden.".($autoWater ? ' Auto-watered!' : '')];
    }

    /**
     * Water a garden plot.
     *
     * @return array{success: bool, message: string}
     */
    public function waterPlot(User $user, string $plotSlot): array
    {
        $plot = $this->getPlot($user, $plotSlot);
        if (! $plot) {
            return ['success' => false, 'message' => 'Garden plot not found.'];
        }

        if (! $plot->water()) {
            return ['success' => false, 'message' => 'Cannot water this plot right now.'];
        }

        return ['success' => true, 'message' => 'Plot watered! Quality improved.'];
    }

    /**
     * Tend a garden plot (costs 2 energy).
     *
     * @return array{success: bool, message: string}
     */
    public function tendPlot(User $user, string $plotSlot): array
    {
        $plot = $this->getPlot($user, $plotSlot);
        if (! $plot) {
            return ['success' => false, 'message' => 'Garden plot not found.'];
        }

        if (! in_array($plot->status, ['planted', 'growing'])) {
            return ['success' => false, 'message' => 'Nothing to tend in this plot.'];
        }

        if (! $this->energyService->consumeEnergy($user, 2)) {
            return ['success' => false, 'message' => 'Not enough energy (need 2).'];
        }

        $plot->tend();

        return ['success' => true, 'message' => 'Tended your garden plot. Quality improved!'];
    }

    /**
     * Harvest a garden plot. Awards farming + herblore XP.
     *
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function harvestPlot(User $user, string $plotSlot): array
    {
        $plot = $this->getPlot($user, $plotSlot);
        if (! $plot) {
            return ['success' => false, 'message' => 'Garden plot not found.'];
        }

        $result = $plot->harvest();
        if (! $result) {
            return ['success' => false, 'message' => 'This crop is not ready to harvest.'];
        }

        if ($result['withered']) {
            return ['success' => false, 'message' => 'The crop has withered. Plot cleared.'];
        }

        // Add harvested items to inventory
        if ($result['item_id'] && $result['yield'] > 0) {
            $this->inventoryService->addItem($user, $result['item_id'], $result['yield']);
        }

        // Award farming XP
        $farmingSkill = $user->skills->where('skill_name', 'farming')->first();
        $farmingLevels = 0;
        if ($farmingSkill) {
            $farmingLevels = $farmingSkill->addXp($result['xp']);
        }

        // Award herblore XP (50% of farming XP)
        $herbloreXp = (int) ($result['xp'] * 0.5);
        $herbloreSkill = $user->skills->where('skill_name', 'herblore')->first();
        $herbloreLevels = 0;
        if ($herbloreSkill && $herbloreXp > 0) {
            $herbloreLevels = $herbloreSkill->addXp($herbloreXp);
        }

        $message = "Harvested {$result['yield']}x {$result['item_name']}! +{$result['xp']} Farming XP, +{$herbloreXp} Herblore XP.";
        if ($farmingLevels > 0) {
            $message .= ' Farming level up!';
        }
        if ($herbloreLevels > 0) {
            $message .= ' Herblore level up!';
        }

        return [
            'success' => true,
            'message' => $message,
            'data' => [
                'yield' => $result['yield'],
                'item_name' => $result['item_name'],
                'farming_xp' => $result['xp'],
                'herblore_xp' => $herbloreXp,
                'farming_level_up' => $farmingLevels > 0,
                'herblore_level_up' => $herbloreLevels > 0,
            ],
        ];
    }

    /**
     * Clear a garden plot.
     *
     * @return array{success: bool, message: string}
     */
    public function clearPlot(User $user, string $plotSlot): array
    {
        $plot = $this->getPlot($user, $plotSlot);
        if (! $plot) {
            return ['success' => false, 'message' => 'Garden plot not found.'];
        }

        $plot->clear();

        return ['success' => true, 'message' => 'Plot cleared.'];
    }

    /**
     * Add compost charges (requires 5x Bones).
     *
     * @return array{success: bool, message: string}
     */
    public function addCompost(User $user): array
    {
        $house = PlayerHouse::where('player_id', $user->id)->first();
        if (! $house) {
            return ['success' => false, 'message' => 'You do not own a house.'];
        }

        $gardenRoom = HouseRoom::where('player_house_id', $house->id)
            ->where('room_type', 'garden')
            ->first();
        if (! $gardenRoom) {
            return ['success' => false, 'message' => 'You do not have a garden room.'];
        }

        $hasCompostBin = HouseFurniture::where('house_room_id', $gardenRoom->id)
            ->where('hotspot_slug', 'compost_bin')
            ->exists();
        if (! $hasCompostBin) {
            return ['success' => false, 'message' => 'Build a compost bin first.'];
        }

        if ($house->compost_charges >= 10) {
            return ['success' => false, 'message' => 'Compost bin is full (max 10 charges).'];
        }

        $bonesItem = Item::where('name', 'Bones')->first();
        if (! $bonesItem || ! $this->inventoryService->hasItem($user, $bonesItem, 5)) {
            return ['success' => false, 'message' => 'You need 5 Bones to make compost.'];
        }

        $this->inventoryService->removeItem($user, $bonesItem, 5);

        $newCharges = min(10, $house->compost_charges + 3);
        $house->update(['compost_charges' => $newCharges]);

        return ['success' => true, 'message' => "Added compost! Now have {$newCharges}/10 charges."];
    }

    /**
     * Use compost on a garden plot.
     *
     * @return array{success: bool, message: string}
     */
    public function useCompost(User $user, string $plotSlot): array
    {
        $house = PlayerHouse::where('player_id', $user->id)->first();
        if (! $house || $house->compost_charges <= 0) {
            return ['success' => false, 'message' => 'No compost charges available.'];
        }

        $plot = $this->getPlot($user, $plotSlot);
        if (! $plot) {
            return ['success' => false, 'message' => 'Garden plot not found.'];
        }

        if ($plot->is_composted) {
            return ['success' => false, 'message' => 'This plot is already composted.'];
        }

        if (! in_array($plot->status, ['empty', 'planted', 'growing'])) {
            return ['success' => false, 'message' => 'Cannot compost this plot right now.'];
        }

        $house->decrement('compost_charges');

        $plot->update([
            'is_composted' => true,
            'quality' => min(100, $plot->quality + 15),
        ]);

        return ['success' => true, 'message' => 'Compost applied! Quality boosted by 15.'];
    }

    /**
     * Get garden data for the frontend.
     *
     * @return array<string, mixed>|null
     */
    public function getGardenData(User $user): ?array
    {
        $house = PlayerHouse::where('player_id', $user->id)->first();
        if (! $house) {
            return null;
        }

        $gardenRoom = HouseRoom::where('player_house_id', $house->id)
            ->where('room_type', 'garden')
            ->first();
        if (! $gardenRoom) {
            return null;
        }

        $plots = GardenPlot::where('player_house_id', $house->id)
            ->with('cropType.harvestItem')
            ->get()
            ->keyBy('plot_slot');

        $plotData = [];
        foreach (['planter_1', 'planter_2', 'planter_3', 'planter_4'] as $slot) {
            $hasFurniture = HouseFurniture::where('house_room_id', $gardenRoom->id)
                ->where('hotspot_slug', $slot)
                ->exists();

            $plot = $plots->get($slot);

            // Update status if growing and ready
            if ($plot && $plot->status === 'growing' && $plot->ready_at && now()->gte($plot->ready_at)) {
                $plot->update(['status' => 'ready']);
                $plot->status = 'ready';
            }

            // Check withered
            if ($plot && $plot->status === 'ready' && $plot->withers_at && now()->gte($plot->withers_at)) {
                $plot->update(['status' => 'withered']);
                $plot->status = 'withered';
            }

            $plotData[$slot] = [
                'plot_slot' => $slot,
                'has_furniture' => $hasFurniture,
                'status' => $plot?->status ?? 'empty',
                'crop_name' => $plot?->cropType?->name,
                'growth_progress' => $plot?->getGrowthProgress() ?? 0,
                'time_remaining' => $plot?->getTimeRemaining(),
                'quality' => $plot?->quality ?? 60,
                'is_watered' => $plot?->is_watered ?? false,
                'is_composted' => $plot?->is_composted ?? false,
                'times_tended' => $plot?->times_tended ?? 0,
            ];
        }

        // Get herb seeds from inventory
        $herbCrops = CropType::where('slug', 'herbs')->with('seedItem')->get();
        $availableSeeds = [];
        foreach ($herbCrops as $crop) {
            if ($crop->seedItem && $this->inventoryService->hasItem($user, $crop->seedItem)) {
                $availableSeeds[] = [
                    'item_id' => $crop->seedItem->id,
                    'name' => $crop->seedItem->name,
                    'crop_type_id' => $crop->id,
                    'crop_name' => $crop->name,
                    'farming_level' => $crop->farming_level_required,
                ];
            }
        }

        // Check irrigation effects
        $irrigationFurniture = HouseFurniture::where('house_room_id', $gardenRoom->id)
            ->where('hotspot_slug', 'irrigation')
            ->first();
        $autoWater = false;
        if ($irrigationFurniture) {
            $config = ConstructionConfig::ROOMS['garden']['hotspots']['irrigation']['options'][$irrigationFurniture->furniture_key] ?? null;
            if ($config && isset($config['effect']['auto_water'])) {
                $autoWater = true;
            }
        }

        // Get total bonuses from garden furniture
        $totalBonuses = [];
        foreach (['irrigation', 'lighting'] as $hotspot) {
            $furniture = HouseFurniture::where('house_room_id', $gardenRoom->id)
                ->where('hotspot_slug', $hotspot)
                ->first();
            if ($furniture) {
                $config = ConstructionConfig::ROOMS['garden']['hotspots'][$hotspot]['options'][$furniture->furniture_key] ?? null;
                if ($config && isset($config['effect'])) {
                    foreach ($config['effect'] as $key => $value) {
                        $totalBonuses[$key] = ($totalBonuses[$key] ?? 0) + $value;
                    }
                }
            }
        }

        return [
            'plots' => $plotData,
            'available_seeds' => $availableSeeds,
            'compost_charges' => $house->compost_charges,
            'max_compost' => 10,
            'auto_water' => $autoWater,
            'total_bonuses' => $totalBonuses,
        ];
    }

    /**
     * Get a garden plot for the user.
     */
    protected function getPlot(User $user, string $plotSlot): ?GardenPlot
    {
        $house = PlayerHouse::where('player_id', $user->id)->first();
        if (! $house) {
            return null;
        }

        return GardenPlot::where('player_house_id', $house->id)
            ->where('plot_slot', $plotSlot)
            ->first();
    }

    /**
     * Check if the garden has auto-water from irrigation furniture.
     */
    protected function hasAutoWater(HouseRoom $gardenRoom): bool
    {
        $furniture = HouseFurniture::where('house_room_id', $gardenRoom->id)
            ->where('hotspot_slug', 'irrigation')
            ->first();

        if (! $furniture) {
            return false;
        }

        $config = ConstructionConfig::ROOMS['garden']['hotspots']['irrigation']['options'][$furniture->furniture_key] ?? null;

        return $config && isset($config['effect']['auto_water']);
    }
}

<?php

namespace App\Services;

use App\Models\HouseFurniture;
use App\Models\HouseRoom;
use App\Models\HouseTrophy;
use App\Models\Item;
use App\Models\PlayerHouse;
use App\Models\User;

class TrophyService
{
    /**
     * Maps trophy item name → monster data for populating HouseTrophy records.
     *
     * @var array<string, array{monster_name: string, monster_type: string, combat_level: int, is_boss: bool}>
     */
    public const TROPHY_CONFIG = [
        'Bandit Trophy' => ['monster_name' => 'Bandit', 'monster_type' => 'humanoid', 'combat_level' => 10, 'is_boss' => false],
        'Hobgoblin Trophy' => ['monster_name' => 'Hobgoblin', 'monster_type' => 'goblinoid', 'combat_level' => 12, 'is_boss' => false],
        'Bear Trophy' => ['monster_name' => 'Bear', 'monster_type' => 'beast', 'combat_level' => 15, 'is_boss' => false],
        'Dark Mage Trophy' => ['monster_name' => 'Dark Mage', 'monster_type' => 'humanoid', 'combat_level' => 18, 'is_boss' => false],
        'Troll Trophy' => ['monster_name' => 'Troll', 'monster_type' => 'giant', 'combat_level' => 22, 'is_boss' => false],
        'Ice Elemental Trophy' => ['monster_name' => 'Ice Elemental', 'monster_type' => 'elemental', 'combat_level' => 25, 'is_boss' => false],
        'Fire Elemental Trophy' => ['monster_name' => 'Fire Elemental', 'monster_type' => 'elemental', 'combat_level' => 25, 'is_boss' => false],
        'Ogre Trophy' => ['monster_name' => 'Ogre', 'monster_type' => 'giant', 'combat_level' => 30, 'is_boss' => false],
        'Demon Trophy' => ['monster_name' => 'Demon', 'monster_type' => 'demon', 'combat_level' => 38, 'is_boss' => false],
        'Wyvern Trophy' => ['monster_name' => 'Wyvern', 'monster_type' => 'dragon', 'combat_level' => 25, 'is_boss' => false],
        'Goblin King Trophy' => ['monster_name' => 'Goblin King', 'monster_type' => 'goblinoid', 'combat_level' => 32, 'is_boss' => true],
        'Lich Trophy' => ['monster_name' => 'Lich', 'monster_type' => 'undead', 'combat_level' => 50, 'is_boss' => true],
        'Elder Dragon Trophy' => ['monster_name' => 'Elder Dragon', 'monster_type' => 'dragon', 'combat_level' => 70, 'is_boss' => true],
    ];

    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Mount a trophy in a display slot or pedestal.
     *
     * @return array{success: bool, message: string}
     */
    public function mountTrophy(User $user, string $slot, int $itemId): array
    {
        $house = PlayerHouse::where('player_id', $user->id)->first();
        if (! $house) {
            return ['success' => false, 'message' => 'You do not have a house.'];
        }

        // Validate trophy_hall room exists
        $trophyRoom = HouseRoom::where('player_house_id', $house->id)
            ->where('room_type', 'trophy_hall')
            ->first();

        if (! $trophyRoom) {
            return ['success' => false, 'message' => 'You need a Trophy Hall room first.'];
        }

        // Validate slot
        $validSlots = ['display_1', 'display_2', 'display_3', 'pedestal'];
        if (! in_array($slot, $validSlots)) {
            return ['success' => false, 'message' => 'Invalid display slot.'];
        }

        // Validate furniture is built at the hotspot
        $hotspotSlug = $slot;
        $hasFurniture = HouseFurniture::where('house_room_id', $trophyRoom->id)
            ->where('hotspot_slug', $hotspotSlug)
            ->exists();

        if (! $hasFurniture) {
            return ['success' => false, 'message' => 'Build a display case or pedestal first.'];
        }

        // Validate item is a trophy in player inventory
        $item = Item::find($itemId);
        if (! $item || $item->subtype !== 'trophy') {
            return ['success' => false, 'message' => 'That is not a trophy.'];
        }

        $trophyConfig = self::TROPHY_CONFIG[$item->name] ?? null;
        if (! $trophyConfig) {
            return ['success' => false, 'message' => 'Unknown trophy type.'];
        }

        // Check player has it in inventory
        $hasItem = $user->inventory()
            ->where('item_id', $item->id)
            ->where('quantity', '>=', 1)
            ->exists();

        if (! $hasItem) {
            return ['success' => false, 'message' => 'You don\'t have that trophy in your inventory.'];
        }

        // Pedestal: only boss trophies
        if ($slot === 'pedestal' && ! $trophyConfig['is_boss']) {
            return ['success' => false, 'message' => 'Only boss trophies can be mounted on the pedestal.'];
        }

        // If slot already has a trophy, return old one to inventory
        $existingTrophy = HouseTrophy::where('player_house_id', $house->id)
            ->where('slot', $slot)
            ->first();

        if ($existingTrophy) {
            $this->inventoryService->addItem($user, $existingTrophy->item_id, 1);
            $existingTrophy->delete();
        }

        // Remove trophy from inventory
        $this->inventoryService->removeItem($user, $item, 1);

        // Create mount record
        HouseTrophy::create([
            'player_house_id' => $house->id,
            'slot' => $slot,
            'item_id' => $item->id,
            'monster_name' => $trophyConfig['monster_name'],
            'monster_type' => $trophyConfig['monster_type'],
            'monster_combat_level' => $trophyConfig['combat_level'],
            'is_boss' => $trophyConfig['is_boss'],
            'mounted_at' => now(),
        ]);

        return ['success' => true, 'message' => "Mounted {$item->name} in the trophy hall!"];
    }

    /**
     * Remove a trophy from a display slot.
     *
     * @return array{success: bool, message: string}
     */
    public function removeTrophy(User $user, string $slot): array
    {
        $house = PlayerHouse::where('player_id', $user->id)->first();
        if (! $house) {
            return ['success' => false, 'message' => 'You do not have a house.'];
        }

        $trophy = HouseTrophy::where('player_house_id', $house->id)
            ->where('slot', $slot)
            ->first();

        if (! $trophy) {
            return ['success' => false, 'message' => 'No trophy mounted in that slot.'];
        }

        // Return trophy to inventory
        $added = $this->inventoryService->addItem($user, $trophy->item_id, 1);
        if (! $added) {
            return ['success' => false, 'message' => 'Inventory is full. Cannot remove trophy.'];
        }

        $name = $trophy->monster_name;
        $trophy->delete();

        return ['success' => true, 'message' => "Removed {$name} Trophy and returned it to your inventory."];
    }

    /**
     * Get trophy hall data for the frontend.
     *
     * @return array{slots: array, available_trophies: array, total_bonuses: array}|null
     */
    public function getTrophyData(User $user): ?array
    {
        $house = PlayerHouse::where('player_id', $user->id)
            ->first();

        if (! $house) {
            return null;
        }

        $trophyRoom = HouseRoom::where('player_house_id', $house->id)
            ->where('room_type', 'trophy_hall')
            ->first();

        if (! $trophyRoom) {
            return null;
        }

        $trophyRoom->load('furniture');
        $trophies = HouseTrophy::where('player_house_id', $house->id)->get();

        $slots = [];
        $totalBonuses = [];

        foreach (['display_1', 'display_2', 'display_3', 'pedestal'] as $slot) {
            $hasFurniture = $trophyRoom->furniture->where('hotspot_slug', $slot)->isNotEmpty();
            $trophy = $trophies->where('slot', $slot)->first();

            $trophyData = null;
            if ($trophy) {
                $bonuses = $trophy->getStatBonuses();
                $trophyData = [
                    'id' => $trophy->id,
                    'monster_name' => $trophy->monster_name,
                    'monster_type' => $trophy->monster_type,
                    'is_boss' => $trophy->is_boss,
                    'bonuses' => $bonuses,
                ];

                foreach ($bonuses as $key => $value) {
                    $totalBonuses[$key] = ($totalBonuses[$key] ?? 0) + $value;
                }
            }

            $slots[$slot] = [
                'has_furniture' => $hasFurniture,
                'trophy' => $trophyData,
            ];
        }

        // Get available trophies from player inventory
        $availableTrophies = $user->inventory()
            ->whereHas('item', fn ($q) => $q->where('subtype', 'trophy'))
            ->with('item')
            ->get()
            ->map(fn ($inv) => [
                'item_id' => $inv->item_id,
                'name' => $inv->item->name,
                'is_boss' => self::TROPHY_CONFIG[$inv->item->name]['is_boss'] ?? false,
            ])
            ->values()
            ->toArray();

        return [
            'slots' => $slots,
            'available_trophies' => $availableTrophies,
            'total_bonuses' => $totalBonuses,
        ];
    }
}

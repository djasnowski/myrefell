<?php

namespace App\Http\Controllers;

use App\Models\CropType;
use App\Models\FarmPlot;
use App\Models\PlayerRole;
use App\Models\PlayerSkill;
use App\Models\Role;
use App\Models\Town;
use App\Models\Village;
use App\Services\FoodConsumptionService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FarmingController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected FoodConsumptionService $foodConsumptionService
    ) {}

    /**
     * Show the player's farm plots at their current location.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if (! $user->current_location_type || ! $user->current_location_id) {
            return Inertia::render('Farming/Index', [
                'error' => 'You must be at a location to farm.',
                'plots' => [],
                'crop_types' => [],
                'farming_skill' => null,
            ]);
        }

        // Get player's plots at current location
        $plots = FarmPlot::where('user_id', $user->id)
            ->where('location_type', $user->current_location_type)
            ->where('location_id', $user->current_location_id)
            ->with('cropType')
            ->get()
            ->map(fn ($plot) => $this->formatPlot($plot));

        // Get available crop types
        $farmingSkill = PlayerSkill::where('player_id', $user->id)
            ->where('skill_name', 'farming')
            ->first();

        $farmingLevel = $farmingSkill?->level ?? 1;

        $cropTypes = CropType::where('farming_level_required', '<=', $farmingLevel)
            ->orderBy('farming_level_required')
            ->get()
            ->map(fn ($crop) => [
                'id' => $crop->id,
                'name' => $crop->name,
                'slug' => $crop->slug,
                'icon' => $crop->icon,
                'description' => $crop->description,
                'grow_time_minutes' => $crop->grow_time_minutes,
                'farming_level_required' => $crop->farming_level_required,
                'farming_xp' => $crop->farming_xp,
                'yield_min' => $crop->yield_min,
                'yield_max' => $crop->yield_max,
                'plant_cost' => $crop->plant_cost,
                'can_plant' => $crop->canPlantInSeason(),
            ]);

        // Check for Master Farmer role bonuses
        $masterFarmerBonuses = $this->getMasterFarmerBonuses(
            $user,
            $user->current_location_type,
            $user->current_location_id
        );

        // Get village/town food stats
        $villageFoodStats = null;
        $locationName = null;
        if ($user->current_location_type === 'village') {
            $village = Village::find($user->current_location_id);
            if ($village) {
                $villageFoodStats = $this->foodConsumptionService->getVillageFoodStats($village);
                $locationName = $village->name;
            }
        } elseif ($user->current_location_type === 'town') {
            $town = Town::find($user->current_location_id);
            if ($town) {
                // Towns also have food consumption - get stats from their associated villages or use similar logic
                $locationName = $town->name;
                // For towns, we could extend this later
            }
        }

        return Inertia::render('Farming/Index', [
            'plots' => $plots,
            'crop_types' => $cropTypes,
            'farming_skill' => $farmingSkill ? [
                'level' => $farmingSkill->level,
                'xp' => $farmingSkill->xp,
                'xp_to_next' => $farmingSkill->xpToNextLevel(),
                'xp_progress' => $farmingSkill->getXpProgress(),
            ] : ['level' => 1, 'xp' => 0, 'xp_to_next' => 83, 'xp_progress' => 0],
            'location' => [
                'type' => $user->current_location_type,
                'id' => $user->current_location_id,
            ],
            'max_plots' => $this->getMaxPlots($farmingLevel),
            'gold' => $user->gold,
            'master_farmer_bonuses' => ! empty($masterFarmerBonuses) ? [
                'yield_bonus' => $masterFarmerBonuses['crop_yield_bonus'] ?? 0,
                'xp_bonus' => $masterFarmerBonuses['farming_xp_bonus'] ?? 0,
            ] : null,
            'village_food' => $villageFoodStats,
            'location_name' => $locationName,
        ]);
    }

    /**
     * Purchase a new farm plot.
     */
    public function buyPlot(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->current_location_type || ! $user->current_location_id) {
            return back()->with('error', 'You must be at a location to buy a plot.');
        }

        $farmingSkill = PlayerSkill::where('player_id', $user->id)
            ->where('skill_name', 'farming')
            ->first();
        $farmingLevel = $farmingSkill?->level ?? 1;
        $maxPlots = $this->getMaxPlots($farmingLevel);

        $currentPlots = FarmPlot::where('user_id', $user->id)
            ->where('location_type', $user->current_location_type)
            ->where('location_id', $user->current_location_id)
            ->count();

        if ($currentPlots >= $maxPlots) {
            return back()->with('error', 'You have reached the maximum number of plots at this location.');
        }

        $plotCost = $this->getPlotCost($currentPlots + 1);

        if ($user->gold < $plotCost) {
            return back()->with('error', "You need {$plotCost} gold to buy a plot.");
        }

        $user->decrement('gold', $plotCost);

        FarmPlot::create([
            'user_id' => $user->id,
            'location_type' => $user->current_location_type,
            'location_id' => $user->current_location_id,
            'status' => 'empty',
        ]);

        return back()->with('success', 'You purchased a new farm plot!');
    }

    /**
     * Plant a crop in a plot.
     */
    public function plant(Request $request, FarmPlot $plot): RedirectResponse
    {
        $user = $request->user();

        if ($plot->user_id !== $user->id) {
            return back()->with('error', 'This is not your plot.');
        }

        if ($plot->status !== 'empty') {
            return back()->with('error', 'This plot is not empty.');
        }

        $validated = $request->validate([
            'crop_type_id' => 'required|exists:crop_types,id',
        ]);

        $cropType = CropType::find($validated['crop_type_id']);

        // Check farming level
        $farmingSkill = PlayerSkill::where('player_id', $user->id)
            ->where('skill_name', 'farming')
            ->first();
        $farmingLevel = $farmingSkill?->level ?? 1;

        if ($cropType->farming_level_required > $farmingLevel) {
            return back()->with('error', "You need farming level {$cropType->farming_level_required} to plant {$cropType->name}.");
        }

        // Check season
        if (! $cropType->canPlantInSeason()) {
            return back()->with('error', "{$cropType->name} cannot be planted in this season.");
        }

        // Check gold cost
        if ($user->gold < $cropType->plant_cost) {
            return back()->with('error', "You need {$cropType->plant_cost} gold for seeds.");
        }

        $user->decrement('gold', $cropType->plant_cost);
        $plot->plant($cropType);

        return back()->with('success', "You planted {$cropType->name}!");
    }

    /**
     * Water a plot.
     */
    public function water(Request $request, FarmPlot $plot): RedirectResponse
    {
        $user = $request->user();

        if ($plot->user_id !== $user->id) {
            return back()->with('error', 'This is not your plot.');
        }

        if (! $plot->water()) {
            $message = $plot->is_watered ? 'This plot has already been watered today.' : 'This plot cannot be watered.';

            return back()->with('error', $message);
        }

        return back()->with('success', 'You watered the plot! Quality improved.');
    }

    /**
     * Tend a plot (costs energy).
     */
    public function tend(Request $request, FarmPlot $plot): RedirectResponse
    {
        $user = $request->user();

        if ($plot->user_id !== $user->id) {
            return back()->with('error', 'This is not your plot.');
        }

        $energyCost = 5;
        if ($user->energy < $energyCost) {
            return back()->with('error', "You need {$energyCost} energy to tend crops.");
        }

        if (! $plot->tend()) {
            return back()->with('error', 'This plot cannot be tended.');
        }

        $user->decrement('energy', $energyCost);

        return back()->with('success', 'You tended the crops! Quality improved significantly.');
    }

    /**
     * Harvest a crop.
     */
    public function harvest(Request $request, FarmPlot $plot): RedirectResponse
    {
        $user = $request->user();

        if ($plot->user_id !== $user->id) {
            return back()->with('error', 'This is not your plot.');
        }

        $result = $plot->harvest();

        if ($result === null) {
            return back()->with('error', 'This crop is not ready for harvest.');
        }

        if ($result['withered']) {
            return back()->with('success', 'The crop has withered. The plot has been cleared.');
        }

        // Check for Master Farmer role bonuses at this location
        $bonuses = $this->getMasterFarmerBonuses($user, $plot->location_type, $plot->location_id);
        $yieldBonus = $bonuses['crop_yield_bonus'] ?? 0;
        $xpBonus = $bonuses['farming_xp_bonus'] ?? 0;

        // Apply yield bonus
        $bonusYield = 0;
        if ($yieldBonus > 0) {
            $bonusYield = (int) ceil($result['yield'] * ($yieldBonus / 100));
        }
        $totalYield = $result['yield'] + $bonusYield;

        // Apply XP bonus
        $bonusXp = 0;
        if ($xpBonus > 0) {
            $bonusXp = (int) ceil($result['xp'] * ($xpBonus / 100));
        }
        $totalXp = $result['xp'] + $bonusXp;

        // Award XP
        $farmingSkill = PlayerSkill::where('player_id', $user->id)
            ->where('skill_name', 'farming')
            ->first();

        if ($farmingSkill) {
            $farmingSkill->addXp($totalXp);
        }

        // Add harvested items to inventory
        if ($result['item_id']) {
            $this->inventoryService->addItem($user, $result['item_id'], $totalYield);
        }

        // Build message with bonus info
        $message = "You harvested {$totalYield} {$result['item_name']}! (+{$totalXp} Farming XP)";
        if ($bonusYield > 0 || $bonusXp > 0) {
            $message .= ' [Master Farmer bonus!]';
        }

        return back()->with('success', $message);
    }

    /**
     * Clear a withered or unwanted plot.
     */
    public function clear(Request $request, FarmPlot $plot): RedirectResponse
    {
        $user = $request->user();

        if ($plot->user_id !== $user->id) {
            return back()->with('error', 'This is not your plot.');
        }

        if ($plot->status === 'empty') {
            return back()->with('error', 'This plot is already empty.');
        }

        $plot->clear();

        return back()->with('success', 'Plot cleared and ready for planting.');
    }

    /**
     * Format a plot for the frontend.
     */
    protected function formatPlot(FarmPlot $plot): array
    {
        return [
            'id' => $plot->id,
            'status' => $plot->status,
            'crop' => $plot->cropType ? [
                'id' => $plot->cropType->id,
                'name' => $plot->cropType->name,
                'icon' => $plot->cropType->icon,
            ] : null,
            'quality' => $plot->quality,
            'times_tended' => $plot->times_tended,
            'is_watered' => $plot->is_watered,
            'growth_progress' => $plot->getGrowthProgress(),
            'time_remaining' => $plot->getTimeRemaining(),
            'is_ready' => $plot->isReadyToHarvest(),
            'has_withered' => $plot->hasWithered(),
            'planted_at' => $plot->planted_at?->diffForHumans(),
        ];
    }

    /**
     * Get maximum plots based on farming level.
     * Starts at 4 plots, gains 1 every 5 levels, max 25.
     */
    protected function getMaxPlots(int $farmingLevel): int
    {
        return min(25, 4 + floor($farmingLevel / 5));
    }

    /**
     * Get cost for a plot (increases with each plot).
     */
    protected function getPlotCost(int $plotNumber): int
    {
        return 100 * $plotNumber;
    }

    /**
     * Get Master Farmer role bonuses if player holds the role at this location.
     */
    protected function getMasterFarmerBonuses($user, string $locationType, int $locationId): array
    {
        try {
            $masterFarmerRole = Role::where('slug', 'master_farmer')->first();
            if (! $masterFarmerRole) {
                return [];
            }

            $playerRole = PlayerRole::where('user_id', $user->id)
                ->where('role_id', $masterFarmerRole->id)
                ->where('location_type', $locationType)
                ->where('location_id', $locationId)
                ->active()
                ->first();

            if ($playerRole) {
                return $masterFarmerRole->bonuses ?? [];
            }
        } catch (\Throwable $e) {
            // Silently fail if tables don't exist
        }

        return [];
    }
}

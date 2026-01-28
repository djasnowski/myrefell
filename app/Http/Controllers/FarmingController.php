<?php

namespace App\Http\Controllers;

use App\Models\CropType;
use App\Models\FarmPlot;
use App\Models\PlayerRole;
use App\Models\PlayerSkill;
use App\Models\Role;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FarmingController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Show the player's farm plots at their current location.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if (!$user->current_location_type || !$user->current_location_id) {
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
            'master_farmer_bonuses' => !empty($masterFarmerBonuses) ? [
                'yield_bonus' => $masterFarmerBonuses['crop_yield_bonus'] ?? 0,
                'xp_bonus' => $masterFarmerBonuses['farming_xp_bonus'] ?? 0,
            ] : null,
        ]);
    }

    /**
     * Purchase a new farm plot.
     */
    public function buyPlot(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->current_location_type || !$user->current_location_id) {
            return response()->json(['error' => 'You must be at a location to buy a plot.'], 422);
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
            return response()->json(['error' => 'You have reached the maximum number of plots at this location.'], 422);
        }

        $plotCost = $this->getPlotCost($currentPlots + 1);

        if ($user->gold < $plotCost) {
            return response()->json(['error' => "You need {$plotCost} gold to buy a plot."], 422);
        }

        $user->decrement('gold', $plotCost);

        $plot = FarmPlot::create([
            'user_id' => $user->id,
            'location_type' => $user->current_location_type,
            'location_id' => $user->current_location_id,
            'status' => 'empty',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'You purchased a new farm plot!',
            'plot' => $this->formatPlot($plot),
            'gold' => $user->gold,
        ]);
    }

    /**
     * Plant a crop in a plot.
     */
    public function plant(Request $request, FarmPlot $plot): JsonResponse
    {
        $user = $request->user();

        if ($plot->user_id !== $user->id) {
            return response()->json(['error' => 'This is not your plot.'], 403);
        }

        if ($plot->status !== 'empty') {
            return response()->json(['error' => 'This plot is not empty.'], 422);
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
            return response()->json(['error' => "You need farming level {$cropType->farming_level_required} to plant {$cropType->name}."], 422);
        }

        // Check season
        if (!$cropType->canPlantInSeason()) {
            return response()->json(['error' => "{$cropType->name} cannot be planted in this season."], 422);
        }

        // Check gold cost
        if ($user->gold < $cropType->plant_cost) {
            return response()->json(['error' => "You need {$cropType->plant_cost} gold for seeds."], 422);
        }

        $user->decrement('gold', $cropType->plant_cost);
        $plot->plant($cropType);

        return response()->json([
            'success' => true,
            'message' => "You planted {$cropType->name}!",
            'plot' => $this->formatPlot($plot->fresh('cropType')),
            'gold' => $user->gold,
        ]);
    }

    /**
     * Water a plot.
     */
    public function water(Request $request, FarmPlot $plot): JsonResponse
    {
        $user = $request->user();

        if ($plot->user_id !== $user->id) {
            return response()->json(['error' => 'This is not your plot.'], 403);
        }

        if (!$plot->water()) {
            $message = $plot->is_watered ? 'This plot has already been watered today.' : 'This plot cannot be watered.';
            return response()->json(['error' => $message], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'You watered the plot! Quality improved.',
            'plot' => $this->formatPlot($plot->fresh('cropType')),
        ]);
    }

    /**
     * Tend a plot (costs energy).
     */
    public function tend(Request $request, FarmPlot $plot): JsonResponse
    {
        $user = $request->user();

        if ($plot->user_id !== $user->id) {
            return response()->json(['error' => 'This is not your plot.'], 403);
        }

        $energyCost = 5;
        if ($user->energy < $energyCost) {
            return response()->json(['error' => "You need {$energyCost} energy to tend crops."], 422);
        }

        if (!$plot->tend()) {
            return response()->json(['error' => 'This plot cannot be tended.'], 422);
        }

        $user->decrement('energy', $energyCost);

        return response()->json([
            'success' => true,
            'message' => 'You tended the crops! Quality improved significantly.',
            'plot' => $this->formatPlot($plot->fresh('cropType')),
            'energy' => $user->energy,
        ]);
    }

    /**
     * Harvest a crop.
     */
    public function harvest(Request $request, FarmPlot $plot): JsonResponse
    {
        $user = $request->user();

        if ($plot->user_id !== $user->id) {
            return response()->json(['error' => 'This is not your plot.'], 403);
        }

        $cropType = $plot->cropType;
        $result = $plot->harvest();

        if ($result === null) {
            return response()->json(['error' => 'This crop is not ready for harvest.'], 422);
        }

        if ($result['withered']) {
            return response()->json([
                'success' => true,
                'message' => 'The crop has withered. The plot has been cleared.',
                'plot' => $this->formatPlot($plot->fresh('cropType')),
                'withered' => true,
            ]);
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
            $message .= " [Master Farmer bonus!]";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'plot' => $this->formatPlot($plot->fresh('cropType')),
            'yield' => $totalYield,
            'base_yield' => $result['yield'],
            'bonus_yield' => $bonusYield,
            'xp_gained' => $totalXp,
            'bonus_xp' => $bonusXp,
            'farming_skill' => $farmingSkill ? [
                'level' => $farmingSkill->level,
                'xp' => $farmingSkill->xp,
                'xp_to_next' => $farmingSkill->xpToNextLevel(),
                'xp_progress' => $farmingSkill->getXpProgress(),
            ] : null,
        ]);
    }

    /**
     * Clear a withered or unwanted plot.
     */
    public function clear(Request $request, FarmPlot $plot): JsonResponse
    {
        $user = $request->user();

        if ($plot->user_id !== $user->id) {
            return response()->json(['error' => 'This is not your plot.'], 403);
        }

        if ($plot->status === 'empty') {
            return response()->json(['error' => 'This plot is already empty.'], 422);
        }

        $plot->clear();

        return response()->json([
            'success' => true,
            'message' => 'Plot cleared and ready for planting.',
            'plot' => $this->formatPlot($plot),
        ]);
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
     */
    protected function getMaxPlots(int $farmingLevel): int
    {
        return min(12, 2 + floor($farmingLevel / 10));
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
            if (!$masterFarmerRole) {
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

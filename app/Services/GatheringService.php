<?php

namespace App\Services;

use App\Models\Item;
use App\Models\LocationActivityLog;
use App\Models\User;
use App\Models\WorldState;
use Illuminate\Support\Facades\DB;

class GatheringService
{
    /**
     * Map gathering activities to bonus activity types.
     */
    public const ACTIVITY_BONUS_MAP = [
        'mining' => 'mining',
        'fishing' => 'fishing',
        'woodcutting' => 'woodcutting',
        'herblore' => 'herblore',
    ];

    /**
     * Gathering activities configuration.
     */
    public const ACTIVITIES = [
        'mining' => [
            'name' => 'Mining',
            'skill' => 'mining',
            'energy_cost' => 5,
            'base_xp' => 17,
            'task_type' => 'mine',
            'location_types' => ['village', 'town', 'barony', 'wilderness'],
            'resources' => [
                // Ores
                ['name' => 'Copper Ore', 'weight' => 60, 'min_level' => 1, 'xp_bonus' => 0],
                ['name' => 'Tin Ore', 'weight' => 40, 'min_level' => 1, 'xp_bonus' => 8],
                ['name' => 'Iron Ore', 'weight' => 30, 'min_level' => 10, 'xp_bonus' => 23],
                ['name' => 'Coal', 'weight' => 25, 'min_level' => 15, 'xp_bonus' => 33],
                ['name' => 'Silver Ore', 'weight' => 15, 'min_level' => 25, 'xp_bonus' => 58],
                ['name' => 'Gold Ore', 'weight' => 10, 'min_level' => 40, 'xp_bonus' => 108],
                ['name' => 'Mithril Ore', 'weight' => 6, 'min_level' => 55, 'xp_bonus' => 150],
                ['name' => 'Celestial Ore', 'weight' => 4, 'min_level' => 70, 'xp_bonus' => 200],
                ['name' => 'Oria Ore', 'weight' => 2, 'min_level' => 85, 'xp_bonus' => 300],
                // Uncut gems (rare drops)
                ['name' => 'Uncut Opal', 'weight' => 5, 'min_level' => 1, 'xp_bonus' => 20],
                ['name' => 'Uncut Jade', 'weight' => 4, 'min_level' => 15, 'xp_bonus' => 35],
                ['name' => 'Uncut Red Topaz', 'weight' => 3, 'min_level' => 25, 'xp_bonus' => 50],
                ['name' => 'Uncut Sapphire', 'weight' => 3, 'min_level' => 35, 'xp_bonus' => 75],
                ['name' => 'Uncut Emerald', 'weight' => 2, 'min_level' => 45, 'xp_bonus' => 100],
                ['name' => 'Uncut Ruby', 'weight' => 2, 'min_level' => 55, 'xp_bonus' => 130],
                ['name' => 'Uncut Diamond', 'weight' => 1, 'min_level' => 65, 'xp_bonus' => 175],
                ['name' => 'Uncut Oria Stone', 'weight' => 1, 'min_level' => 80, 'xp_bonus' => 250],
            ],
        ],
        'fishing' => [
            'name' => 'Fishing',
            'skill' => 'fishing',
            'energy_cost' => 4,
            'base_xp' => 10,
            'task_type' => 'fish',
            'location_types' => ['village', 'town', 'wilderness'],
            'resources' => [
                ['name' => 'Raw Shrimp', 'weight' => 50, 'min_level' => 1, 'xp_bonus' => 0],
                ['name' => 'Raw Sardine', 'weight' => 40, 'min_level' => 1, 'xp_bonus' => 10],
                ['name' => 'Raw Trout', 'weight' => 35, 'min_level' => 10, 'xp_bonus' => 30],
                ['name' => 'Raw Salmon', 'weight' => 25, 'min_level' => 20, 'xp_bonus' => 50],
                ['name' => 'Raw Lobster', 'weight' => 15, 'min_level' => 35, 'xp_bonus' => 70],
                ['name' => 'Raw Swordfish', 'weight' => 10, 'min_level' => 50, 'xp_bonus' => 100],
            ],
        ],
        'woodcutting' => [
            'name' => 'Woodcutting',
            'skill' => 'woodcutting',
            'energy_cost' => 4,
            'base_xp' => 25,
            'task_type' => 'chop',
            'location_types' => ['village', 'town', 'wilderness'],
            'resources' => [
                ['name' => 'Wood', 'weight' => 60, 'min_level' => 1, 'xp_bonus' => 0],
                ['name' => 'Oak Wood', 'weight' => 35, 'min_level' => 10, 'xp_bonus' => 35],
                ['name' => 'Willow Wood', 'weight' => 25, 'min_level' => 20, 'xp_bonus' => 75],
                ['name' => 'Maple Wood', 'weight' => 15, 'min_level' => 35, 'xp_bonus' => 125],
                ['name' => 'Yew Wood', 'weight' => 10, 'min_level' => 50, 'xp_bonus' => 225],
            ],
        ],
        'herblore' => [
            'name' => 'Herblore',
            'skill' => 'herblore',
            'energy_cost' => 3,
            'base_xp' => 15,
            'task_type' => 'forage',
            'location_types' => ['village', 'town', 'wilderness'],
            'resources' => [
                // Basic herbs (Level 1-10)
                ['name' => 'Herb', 'weight' => 50, 'min_level' => 1, 'xp_bonus' => 0],
                ['name' => 'Healing Herb', 'weight' => 45, 'min_level' => 5, 'xp_bonus' => 5],
                ['name' => 'Sunblossom', 'weight' => 40, 'min_level' => 8, 'xp_bonus' => 8],
                // Intermediate herbs (Level 10-25)
                ['name' => 'Stoneroot', 'weight' => 35, 'min_level' => 12, 'xp_bonus' => 12],
                ['name' => 'Moonpetal', 'weight' => 30, 'min_level' => 15, 'xp_bonus' => 15],
                ['name' => 'Nightshade', 'weight' => 28, 'min_level' => 18, 'xp_bonus' => 18],
                ['name' => 'Ironbark', 'weight' => 25, 'min_level' => 22, 'xp_bonus' => 22],
                // Advanced herbs (Level 25-40)
                ['name' => 'Bloodroot', 'weight' => 22, 'min_level' => 25, 'xp_bonus' => 25],
                ['name' => 'Hawkeye Leaf', 'weight' => 20, 'min_level' => 28, 'xp_bonus' => 28],
                ['name' => 'Swiftfoot Moss', 'weight' => 18, 'min_level' => 32, 'xp_bonus' => 32],
                ['name' => 'Ghostcap Mushroom', 'weight' => 15, 'min_level' => 35, 'xp_bonus' => 35],
                ['name' => 'Windweed', 'weight' => 14, 'min_level' => 38, 'xp_bonus' => 38],
                // Rare herbs (Level 45+)
                ['name' => 'Dragonvine', 'weight' => 10, 'min_level' => 50, 'xp_bonus' => 50],
                ['name' => 'Starlight Essence', 'weight' => 5, 'min_level' => 60, 'xp_bonus' => 65],
                // Supplies found while foraging
                ['name' => 'Vial', 'weight' => 12, 'min_level' => 1, 'xp_bonus' => 2],
                ['name' => 'Crystal Vial', 'weight' => 6, 'min_level' => 20, 'xp_bonus' => 10],
                ['name' => 'Holy Water', 'weight' => 4, 'min_level' => 30, 'xp_bonus' => 20],
            ],
        ],
    ];

    public function __construct(
        protected InventoryService $inventoryService,
        protected DailyTaskService $dailyTaskService,
        protected TownBonusService $townBonusService,
        protected BlessingEffectService $blessingEffectService
    ) {}

    /**
     * Check if user can access gathering at their current location.
     */
    public function canGather(User $user, string $activity): bool
    {
        if ($user->isTraveling()) {
            return false;
        }

        $config = self::ACTIVITIES[$activity] ?? null;
        if (! $config) {
            return false;
        }

        return in_array($user->current_location_type, $config['location_types']);
    }

    /**
     * Get available activities at the current location.
     */
    public function getAvailableActivities(User $user): array
    {
        $activities = [];

        foreach (self::ACTIVITIES as $key => $config) {
            if ($this->canGather($user, $key)) {
                $skillLevel = $user->getSkillLevel($config['skill']);
                $availableResources = $this->getAvailableResources($key, $skillLevel);

                $activities[] = [
                    'id' => $key,
                    'name' => $config['name'],
                    'skill' => $config['skill'],
                    'skill_level' => $skillLevel,
                    'energy_cost' => $config['energy_cost'],
                    'base_xp' => $config['base_xp'],
                    'available_resources' => count($availableResources),
                    'resources' => $availableResources,
                ];
            }
        }

        return $activities;
    }

    /**
     * Get resources available for a skill level.
     */
    protected function getAvailableResources(string $activity, int $skillLevel): array
    {
        $config = self::ACTIVITIES[$activity] ?? null;
        if (! $config) {
            return [];
        }

        return array_values(array_filter($config['resources'], function ($resource) use ($skillLevel) {
            return $skillLevel >= $resource['min_level'];
        }));
    }

    /**
     * Perform a gathering action.
     */
    public function gather(User $user, string $activity, ?string $locationType = null, ?int $locationId = null, ?string $resourceName = null): array
    {
        $config = self::ACTIVITIES[$activity] ?? null;

        if (! $config) {
            return [
                'success' => false,
                'message' => 'Invalid activity.',
            ];
        }

        if (! $this->canGather($user, $activity)) {
            return [
                'success' => false,
                'message' => 'You cannot do this activity here.',
            ];
        }

        if (! $user->hasEnergy($config['energy_cost'])) {
            return [
                'success' => false,
                'message' => "Not enough energy. Need {$config['energy_cost']} energy.",
            ];
        }

        if (! $this->inventoryService->hasEmptySlot($user)) {
            return [
                'success' => false,
                'message' => 'Your inventory is full.',
            ];
        }

        $skillLevel = $user->getSkillLevel($config['skill']);
        $availableResources = $this->getAvailableResources($activity, $skillLevel);

        if (empty($availableResources)) {
            return [
                'success' => false,
                'message' => 'No resources available at your skill level.',
            ];
        }

        // If a specific resource is requested, find it; otherwise use weighted random
        if ($resourceName) {
            $resource = $this->findResourceByName($activity, $resourceName, $skillLevel);
            if (! $resource) {
                return [
                    'success' => false,
                    'message' => 'Invalid resource or level too low.',
                ];
            }
        } else {
            $resource = $this->selectWeightedResource($availableResources);
        }

        $item = Item::where('name', $resource['name'])->first();

        if (! $item) {
            return [
                'success' => false,
                'message' => 'Resource not found in database.',
            ];
        }

        // Use provided location or fall back to user's current location
        $locationType = $locationType ?? $user->current_location_type;
        $locationId = $locationId ?? $user->current_location_id;

        return DB::transaction(function () use ($user, $config, $resource, $item, $activity, $locationType, $locationId) {
            // Consume energy
            $user->consumeEnergy($config['energy_cost']);

            // Calculate quantity based on seasonal modifier
            $seasonalModifier = $this->getSeasonalModifier();
            $quantity = $this->calculateYield($seasonalModifier);

            // Apply town bonus for yield
            $bonusActivity = self::ACTIVITY_BONUS_MAP[$activity] ?? $activity;
            $yieldBonus = $this->townBonusService->getYieldBonus($user, $bonusActivity);
            $bonusQuantity = $this->townBonusService->calculateBonusQuantity($yieldBonus, $quantity);
            $totalQuantity = $quantity + $bonusQuantity;

            // Apply blessing yield bonus (e.g., fishing_yield_bonus, mining_yield_bonus)
            $blessingYieldBonus = $this->blessingEffectService->getEffect($user, "{$activity}_yield_bonus");
            if ($blessingYieldBonus > 0) {
                $blessingBonusQty = (int) floor($totalQuantity * $blessingYieldBonus / 100);
                $totalQuantity += $blessingBonusQty;
            }

            // Add item to inventory
            $this->inventoryService->addItem($user, $item, $totalQuantity);

            // Calculate and contribute to town stockpile
            $contributionRate = $this->townBonusService->getContributionRate($user, $bonusActivity);
            $contribution = $this->townBonusService->calculateContribution($contributionRate, $totalQuantity);
            $contributedToStockpile = false;
            if ($contribution > 0) {
                $contributedToStockpile = $this->townBonusService->contributeToStockpile($user, $item->id, $contribution);
            }

            // Award XP (scaled by quantity for bonus yields)
            $baseXp = $config['base_xp'] + $resource['xp_bonus'];
            $xpAwarded = (int) ceil($baseXp * $totalQuantity);

            // Get or create the skill
            $skill = $user->skills()->where('skill_name', $config['skill'])->first();

            if (! $skill) {
                $skill = $user->skills()->create([
                    'skill_name' => $config['skill'],
                    'level' => 1,
                    'xp' => 0,
                ]);
            }

            $oldLevel = $skill->level;
            $skill->addXp($xpAwarded);
            $newLevel = $skill->fresh()->level;
            $leveledUp = $newLevel > $oldLevel;

            // Record daily task progress
            $this->dailyTaskService->recordProgress($user, $config['task_type'], $item->name, $totalQuantity);

            // Log activity at location
            if ($locationType && $locationId) {
                try {
                    LocationActivityLog::log(
                        userId: $user->id,
                        locationType: $locationType,
                        locationId: $locationId,
                        activityType: LocationActivityLog::TYPE_GATHERING,
                        description: "{$user->username} gathered {$totalQuantity}x {$item->name}",
                        activitySubtype: $activity,
                        metadata: [
                            'item' => $item->name,
                            'quantity' => $totalQuantity,
                            'xp_gained' => $xpAwarded,
                            'skill' => $config['skill'],
                            'leveled_up' => $leveledUp,
                        ]
                    );
                } catch (\Illuminate\Database\QueryException $e) {
                    // Table may not exist yet
                }
            }

            $message = $totalQuantity > 1
                ? "You gathered {$totalQuantity}x {$item->name}!"
                : "You gathered {$item->name}!";

            if ($contributedToStockpile && $contribution > 0) {
                $message .= " ({$contribution} contributed to town stockpile)";
            }

            return [
                'success' => true,
                'message' => $message,
                'resource' => [
                    'name' => $item->name,
                    'description' => $item->description,
                ],
                'quantity' => $totalQuantity,
                'base_quantity' => $quantity,
                'bonus_quantity' => $bonusQuantity,
                'xp_awarded' => $xpAwarded,
                'skill' => $config['skill'],
                'leveled_up' => $leveledUp,
                'new_level' => $leveledUp ? $newLevel : null,
                'energy_remaining' => $user->fresh()->energy,
                'seasonal_bonus' => $quantity > 1,
                'role_bonus' => $bonusQuantity > 0,
                'stockpile_contribution' => $contribution,
            ];
        });
    }

    /**
     * Get the current seasonal gathering modifier.
     */
    public function getSeasonalModifier(): float
    {
        return WorldState::current()->getGatheringModifier();
    }

    /**
     * Calculate yield based on seasonal modifier.
     *
     * Modifier < 1.0: Chance of getting nothing (returns 0 on failure, 1 on success)
     * Modifier = 1.0: Always returns 1
     * Modifier > 1.0: Chance of bonus yield (e.g., 1.3 = 30% chance of 2x)
     */
    public function calculateYield(float $modifier): int
    {
        if ($modifier >= 1.0) {
            // Bonus chance: modifier of 1.3 = 30% chance of getting 2 instead of 1
            $bonusChance = $modifier - 1.0;
            if ($bonusChance > 0 && mt_rand(1, 100) <= ($bonusChance * 100)) {
                return 2;
            }

            return 1;
        }

        // Penalty chance: modifier of 0.5 = 50% chance of getting 0 instead of 1
        // We always give at least 1 to avoid frustrating players, but reduce effective yield
        // by increasing chance of lower-tier resources via weighted selection
        // For simplicity, we'll give 1 but note that future iterations could
        // add chance of failure. For now, seasonal penalties affect only bonus chances.
        return 1;
    }

    /**
     * Select a resource using weighted random.
     */
    protected function selectWeightedResource(array $resources): array
    {
        $totalWeight = array_sum(array_column($resources, 'weight'));
        $random = mt_rand(1, $totalWeight);
        $cumulative = 0;

        foreach ($resources as $resource) {
            $cumulative += $resource['weight'];
            if ($random <= $cumulative) {
                return $resource;
            }
        }

        return $resources[0];
    }

    /**
     * Find a specific resource by name if player meets level requirement.
     */
    protected function findResourceByName(string $activity, string $resourceName, int $skillLevel): ?array
    {
        $config = self::ACTIVITIES[$activity] ?? null;
        if (! $config) {
            return null;
        }

        foreach ($config['resources'] as $resource) {
            if ($resource['name'] === $resourceName && $skillLevel >= $resource['min_level']) {
                return $resource;
            }
        }

        return null;
    }

    /**
     * Get gathering info for a specific activity.
     */
    public function getActivityInfo(User $user, string $activity): ?array
    {
        $config = self::ACTIVITIES[$activity] ?? null;
        if (! $config) {
            return null;
        }

        $skill = $user->skills()->where('skill_name', $config['skill'])->first();
        $skillLevel = $skill?->level ?? 1;
        $skillXp = $skill?->xp ?? 0;
        $skillXpProgress = $skill?->getXpProgress() ?? 0;
        $skillXpToNext = $skill?->xpToNextLevel() ?? 60;
        $availableResources = $this->getAvailableResources($activity, $skillLevel);

        // Find next unlock
        $nextUnlock = null;
        foreach ($config['resources'] as $resource) {
            if ($resource['min_level'] > $skillLevel) {
                $nextUnlock = $resource;
                break;
            }
        }

        // Get seasonal data
        $worldState = WorldState::current();
        $seasonalModifier = $worldState->getGatheringModifier();

        // Get role bonuses
        $bonusActivity = self::ACTIVITY_BONUS_MAP[$activity] ?? $activity;
        $yieldBonus = $this->townBonusService->getYieldBonus($user, $bonusActivity);
        $contributionRate = $this->townBonusService->getContributionRate($user, $bonusActivity);

        return [
            'id' => $activity,
            'name' => $config['name'],
            'skill' => $config['skill'],
            'skill_level' => $skillLevel,
            'skill_xp' => $skillXp,
            'skill_xp_progress' => $skillXpProgress,
            'skill_xp_to_next' => $skillXpToNext,
            'energy_cost' => $config['energy_cost'],
            'base_xp' => $config['base_xp'],
            'player_energy' => $user->energy,
            'can_gather' => $this->canGather($user, $activity) && $user->hasEnergy($config['energy_cost']),
            'resources' => $availableResources,
            'next_unlock' => $nextUnlock,
            'inventory_full' => ! $this->inventoryService->hasEmptySlot($user),
            'free_slots' => $this->inventoryService->freeSlots($user),
            'seasonal_modifier' => $seasonalModifier,
            'current_season' => $worldState->current_season,
            'yield_bonus' => $yieldBonus,
            'yield_bonus_percent' => round($yieldBonus * 100),
            'contribution_rate' => $contributionRate,
            'contribution_rate_percent' => round($contributionRate * 100),
        ];
    }

    /**
     * Get seasonal data for display purposes.
     */
    public function getSeasonalData(): array
    {
        $worldState = WorldState::current();

        return [
            'season' => $worldState->current_season,
            'modifier' => $worldState->getGatheringModifier(),
            'description' => $worldState->getSeasonDescription(),
        ];
    }
}

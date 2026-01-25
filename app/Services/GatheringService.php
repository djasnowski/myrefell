<?php

namespace App\Services;

use App\Models\Item;
use App\Models\User;
use App\Models\WorldState;
use Illuminate\Support\Facades\DB;

class GatheringService
{
    /**
     * Gathering activities configuration.
     */
    public const ACTIVITIES = [
        'mining' => [
            'name' => 'Mining',
            'skill' => 'mining',
            'energy_cost' => 5,
            'base_xp' => 15,
            'task_type' => 'mine',
            'location_types' => ['village', 'barony', 'wilderness'],
            'resources' => [
                ['name' => 'Copper Ore', 'weight' => 60, 'min_level' => 1, 'xp_bonus' => 0],
                ['name' => 'Tin Ore', 'weight' => 40, 'min_level' => 1, 'xp_bonus' => 5],
                ['name' => 'Iron Ore', 'weight' => 30, 'min_level' => 10, 'xp_bonus' => 10],
                ['name' => 'Coal', 'weight' => 25, 'min_level' => 15, 'xp_bonus' => 12],
                ['name' => 'Silver Ore', 'weight' => 15, 'min_level' => 25, 'xp_bonus' => 20],
                ['name' => 'Gold Ore', 'weight' => 10, 'min_level' => 40, 'xp_bonus' => 35],
            ],
        ],
        'fishing' => [
            'name' => 'Fishing',
            'skill' => 'fishing',
            'energy_cost' => 4,
            'base_xp' => 12,
            'task_type' => 'fish',
            'location_types' => ['village', 'wilderness'],
            'resources' => [
                ['name' => 'Raw Shrimp', 'weight' => 50, 'min_level' => 1, 'xp_bonus' => 0],
                ['name' => 'Raw Sardine', 'weight' => 40, 'min_level' => 1, 'xp_bonus' => 3],
                ['name' => 'Raw Trout', 'weight' => 35, 'min_level' => 10, 'xp_bonus' => 8],
                ['name' => 'Raw Salmon', 'weight' => 25, 'min_level' => 20, 'xp_bonus' => 15],
                ['name' => 'Raw Lobster', 'weight' => 15, 'min_level' => 35, 'xp_bonus' => 25],
                ['name' => 'Raw Swordfish', 'weight' => 10, 'min_level' => 50, 'xp_bonus' => 40],
            ],
        ],
        'woodcutting' => [
            'name' => 'Woodcutting',
            'skill' => 'crafting',
            'energy_cost' => 4,
            'base_xp' => 10,
            'task_type' => 'chop',
            'location_types' => ['village', 'wilderness'],
            'resources' => [
                ['name' => 'Wood', 'weight' => 60, 'min_level' => 1, 'xp_bonus' => 0],
                ['name' => 'Oak Wood', 'weight' => 35, 'min_level' => 10, 'xp_bonus' => 8],
                ['name' => 'Willow Wood', 'weight' => 25, 'min_level' => 20, 'xp_bonus' => 15],
                ['name' => 'Maple Wood', 'weight' => 15, 'min_level' => 35, 'xp_bonus' => 25],
                ['name' => 'Yew Wood', 'weight' => 10, 'min_level' => 50, 'xp_bonus' => 40],
            ],
        ],
    ];

    public function __construct(
        protected InventoryService $inventoryService,
        protected DailyTaskService $dailyTaskService
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
    public function gather(User $user, string $activity): array
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

        // Select a resource using weighted random
        $resource = $this->selectWeightedResource($availableResources);
        $item = Item::where('name', $resource['name'])->first();

        if (! $item) {
            return [
                'success' => false,
                'message' => 'Resource not found in database.',
            ];
        }

        return DB::transaction(function () use ($user, $config, $resource, $item, $activity) {
            // Consume energy
            $user->consumeEnergy($config['energy_cost']);

            // Calculate quantity based on seasonal modifier
            $seasonalModifier = $this->getSeasonalModifier();
            $quantity = $this->calculateYield($seasonalModifier);

            // Add item to inventory
            $this->inventoryService->addItem($user, $item, $quantity);

            // Award XP (scaled by quantity for bonus yields)
            $baseXp = $config['base_xp'] + $resource['xp_bonus'];
            $xpAwarded = (int) ceil($baseXp * $quantity);
            $skill = $user->skills()->where('skill_name', $config['skill'])->first();
            $leveledUp = false;

            if ($skill) {
                $oldLevel = $skill->level;
                $skill->addXp($xpAwarded);
                $leveledUp = $skill->fresh()->level > $oldLevel;
            }

            // Record daily task progress
            $this->dailyTaskService->recordProgress($user, $config['task_type'], $item->name, $quantity);

            $message = $quantity > 1
                ? "You gathered {$quantity}x {$item->name}!"
                : "You gathered {$item->name}!";

            return [
                'success' => true,
                'message' => $message,
                'resource' => [
                    'name' => $item->name,
                    'description' => $item->description,
                ],
                'quantity' => $quantity,
                'xp_awarded' => $xpAwarded,
                'skill' => $config['skill'],
                'leveled_up' => $leveledUp,
                'energy_remaining' => $user->fresh()->energy,
                'seasonal_bonus' => $quantity > 1,
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
     * Get gathering info for a specific activity.
     */
    public function getActivityInfo(User $user, string $activity): ?array
    {
        $config = self::ACTIVITIES[$activity] ?? null;
        if (! $config) {
            return null;
        }

        $skillLevel = $user->getSkillLevel($config['skill']);
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

        return [
            'id' => $activity,
            'name' => $config['name'],
            'skill' => $config['skill'],
            'skill_level' => $skillLevel,
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

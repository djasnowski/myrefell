<?php

namespace App\Services;

use App\Models\Item;
use App\Models\LocationActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ThievingService
{
    /**
     * Thieving targets configuration.
     * success_rate is base rate, modified by level difference
     */
    public const TARGETS = [
        'farmer' => [
            'name' => 'Farmer',
            'description' => 'A humble farmer tending their crops',
            'min_level' => 1,
            'energy_cost' => 3,
            'base_xp' => 8,
            'base_success_rate' => 90,
            'gold_range' => [5, 15],
            'catch_gold_loss' => 10,
            'catch_energy_loss' => 6,
            'location_types' => ['village', 'town'],
            'loot_table' => [
                ['item' => 'Wheat', 'weight' => 40, 'quantity' => [1, 3]],
                ['item' => 'Potato', 'weight' => 30, 'quantity' => [1, 2]],
                ['item' => 'Carrot', 'weight' => 20, 'quantity' => [1, 2]],
                ['item' => 'Seeds', 'weight' => 10, 'quantity' => [2, 5]],
            ],
        ],
        'market_stall' => [
            'name' => 'Market Stall',
            'description' => 'A busy market stall with various goods',
            'min_level' => 10,
            'energy_cost' => 4,
            'base_xp' => 15,
            'base_success_rate' => 85,
            'gold_range' => [10, 30],
            'catch_gold_loss' => 25,
            'catch_energy_loss' => 8,
            'location_types' => ['village', 'town', 'barony'],
            'loot_table' => [
                ['item' => 'Bread', 'weight' => 30, 'quantity' => [1, 2]],
                ['item' => 'Apple', 'weight' => 25, 'quantity' => [2, 4]],
                ['item' => 'Raw Trout', 'weight' => 20, 'quantity' => [1, 2]],
                ['item' => 'Ale', 'weight' => 15, 'quantity' => [1, 2]],
                ['item' => 'Lockpick', 'weight' => 10, 'quantity' => [1, 1]],
            ],
        ],
        'merchant' => [
            'name' => 'Wealthy Merchant',
            'description' => 'A prosperous trader with heavy coin purse',
            'min_level' => 25,
            'energy_cost' => 5,
            'base_xp' => 28,
            'base_success_rate' => 80,
            'gold_range' => [25, 60],
            'catch_gold_loss' => 50,
            'catch_energy_loss' => 10,
            'location_types' => ['town', 'barony', 'duchy'],
            'loot_table' => [
                ['item' => 'Gold Ring', 'weight' => 25, 'quantity' => [1, 1]],
                ['item' => 'Silver Ore', 'weight' => 20, 'quantity' => [1, 2]],
                ['item' => 'Silk', 'weight' => 20, 'quantity' => [1, 1]],
                ['item' => 'Sapphire', 'weight' => 15, 'quantity' => [1, 1]],
                ['item' => 'Trade Contract', 'weight' => 10, 'quantity' => [1, 1]],
                ['item' => 'Lockpick', 'weight' => 10, 'quantity' => [1, 2]],
            ],
        ],
        'noble' => [
            'name' => 'Noble',
            'description' => 'A wealthy aristocrat adorned with jewelry',
            'min_level' => 40,
            'energy_cost' => 6,
            'base_xp' => 45,
            'base_success_rate' => 75,
            'gold_range' => [50, 120],
            'catch_gold_loss' => 100,
            'catch_energy_loss' => 12,
            'location_types' => ['town', 'barony', 'duchy', 'kingdom'],
            'loot_table' => [
                ['item' => 'Ruby', 'weight' => 20, 'quantity' => [1, 1]],
                ['item' => 'Gold Ore', 'weight' => 20, 'quantity' => [1, 2]],
                ['item' => 'Diamond', 'weight' => 15, 'quantity' => [1, 1]],
                ['item' => 'Noble Signet', 'weight' => 15, 'quantity' => [1, 1]],
                ['item' => 'Silk', 'weight' => 15, 'quantity' => [1, 2]],
                ['item' => 'Fine Wine', 'weight' => 15, 'quantity' => [1, 1]],
            ],
        ],
        'castle_treasury' => [
            'name' => 'Castle Treasury',
            'description' => 'The baron\'s well-guarded treasury room',
            'min_level' => 60,
            'energy_cost' => 8,
            'base_xp' => 75,
            'base_success_rate' => 65,
            'gold_range' => [100, 300],
            'catch_gold_loss' => 200,
            'catch_energy_loss' => 16,
            'location_types' => ['barony', 'duchy', 'kingdom'],
            'loot_table' => [
                ['item' => 'Gold Bar', 'weight' => 25, 'quantity' => [1, 2]],
                ['item' => 'Diamond', 'weight' => 20, 'quantity' => [1, 2]],
                ['item' => 'Ruby', 'weight' => 20, 'quantity' => [1, 2]],
                ['item' => 'Royal Seal', 'weight' => 15, 'quantity' => [1, 1]],
                ['item' => 'Ancient Coin', 'weight' => 10, 'quantity' => [2, 5]],
                ['item' => 'Treasure Map', 'weight' => 10, 'quantity' => [1, 1]],
            ],
        ],
        'royal_vault' => [
            'name' => 'Royal Vault',
            'description' => 'The king\'s legendary vault of treasures',
            'min_level' => 80,
            'energy_cost' => 10,
            'base_xp' => 120,
            'base_success_rate' => 55,
            'gold_range' => [200, 500],
            'catch_gold_loss' => 400,
            'catch_energy_loss' => 20,
            'location_types' => ['kingdom'],
            'loot_table' => [
                ['item' => 'Gold Bar', 'weight' => 20, 'quantity' => [2, 4]],
                ['item' => 'Diamond', 'weight' => 20, 'quantity' => [1, 3]],
                ['item' => 'Crown Jewel', 'weight' => 15, 'quantity' => [1, 1]],
                ['item' => 'Royal Scepter', 'weight' => 10, 'quantity' => [1, 1]],
                ['item' => 'Ancient Artifact', 'weight' => 10, 'quantity' => [1, 1]],
                ['item' => 'Treasure Map', 'weight' => 15, 'quantity' => [1, 2]],
                ['item' => 'Rare Gem', 'weight' => 10, 'quantity' => [1, 1]],
            ],
        ],
        'dragons_hoard' => [
            'name' => 'Dragon\'s Hoard',
            'description' => 'A legendary dragon\'s treasure hoard - extremely dangerous',
            'min_level' => 90,
            'energy_cost' => 15,
            'base_xp' => 500,
            'base_success_rate' => 5, // 0.5% effective with level scaling
            'gold_range' => [1000, 5000],
            'catch_gold_loss' => 1000,
            'catch_energy_loss' => 50,
            'location_types' => ['kingdom'],
            'is_legendary' => true,
            'loot_table' => [
                ['item' => 'Dragon Scale', 'weight' => 25, 'quantity' => [1, 3]],
                ['item' => 'Legendary Gem', 'weight' => 20, 'quantity' => [1, 2]],
                ['item' => 'Ancient Dragon Coin', 'weight' => 20, 'quantity' => [5, 20]],
                ['item' => 'Dragon\'s Eye Ruby', 'weight' => 15, 'quantity' => [1, 1]],
                ['item' => 'Dragonbone', 'weight' => 10, 'quantity' => [1, 2]],
                ['item' => 'Enchanted Artifact', 'weight' => 10, 'quantity' => [1, 1]],
            ],
        ],
    ];

    public function __construct(
        protected InventoryService $inventoryService,
        protected DailyTaskService $dailyTaskService
    ) {}

    /**
     * Check if user can thieve at their current location.
     */
    public function canThieve(User $user): bool
    {
        if ($user->isTraveling()) {
            return false;
        }

        // Check if any targets are available at this location
        foreach (self::TARGETS as $target) {
            if (in_array($user->current_location_type, $target['location_types'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get available targets at the current location.
     */
    public function getAvailableTargets(User $user): array
    {
        $targets = [];
        $thievingLevel = $user->getSkillLevel('thieving');

        foreach (self::TARGETS as $key => $config) {
            if (! in_array($user->current_location_type, $config['location_types'])) {
                continue;
            }

            $isUnlocked = $thievingLevel >= $config['min_level'];
            $successRate = $this->calculateSuccessRate($thievingLevel, $config);

            $targets[] = [
                'id' => $key,
                'name' => $config['name'],
                'description' => $config['description'],
                'min_level' => $config['min_level'],
                'energy_cost' => $config['energy_cost'],
                'base_xp' => $config['base_xp'],
                'gold_range' => $config['gold_range'],
                'success_rate' => $successRate,
                'catch_gold_loss' => $config['catch_gold_loss'],
                'catch_energy_loss' => $config['catch_energy_loss'],
                'is_unlocked' => $isUnlocked,
                'is_legendary' => $config['is_legendary'] ?? false,
                'can_attempt' => $isUnlocked && $user->hasEnergy($config['energy_cost']),
            ];
        }

        return $targets;
    }

    /**
     * Calculate success rate based on player level vs target requirement.
     */
    protected function calculateSuccessRate(int $playerLevel, array $config): int
    {
        $baseRate = $config['base_success_rate'];
        $levelDiff = $playerLevel - $config['min_level'];

        // Each level above requirement adds 0.5% success rate (max +15%)
        $levelBonus = min(15, $levelDiff * 0.5);

        // For legendary targets, the rate is much lower
        if ($config['is_legendary'] ?? false) {
            return max(1, min(10, $baseRate + $levelBonus)); // Cap at 10% for legendary
        }

        return max(5, min(95, $baseRate + $levelBonus));
    }

    /**
     * Attempt to thieve from a target.
     */
    public function thieve(User $user, string $targetId, ?string $locationType = null, ?int $locationId = null): array
    {
        $config = self::TARGETS[$targetId] ?? null;

        if (! $config) {
            return [
                'success' => false,
                'message' => 'Invalid target.',
            ];
        }

        if (! in_array($user->current_location_type, $config['location_types'])) {
            return [
                'success' => false,
                'message' => 'This target is not available at your location.',
            ];
        }

        $thievingLevel = $user->getSkillLevel('thieving');

        if ($thievingLevel < $config['min_level']) {
            return [
                'success' => false,
                'message' => "You need level {$config['min_level']} Thieving to attempt this.",
            ];
        }

        if (! $user->hasEnergy($config['energy_cost'])) {
            return [
                'success' => false,
                'message' => "Not enough energy. Need {$config['energy_cost']} energy.",
            ];
        }

        $locationType = $locationType ?? $user->current_location_type;
        $locationId = $locationId ?? $user->current_location_id;

        $successRate = $this->calculateSuccessRate($thievingLevel, $config);
        $roll = mt_rand(1, 100);
        $isSuccess = $roll <= $successRate;

        return DB::transaction(function () use ($user, $config, $targetId, $isSuccess, $thievingLevel, $locationType, $locationId) {
            // Always consume base energy
            $user->consumeEnergy($config['energy_cost']);

            if ($isSuccess) {
                return $this->handleSuccess($user, $config, $targetId, $thievingLevel, $locationType, $locationId);
            } else {
                return $this->handleFailure($user, $config, $targetId, $locationType, $locationId);
            }
        });
    }

    /**
     * Handle successful thieving attempt.
     */
    protected function handleSuccess(User $user, array $config, string $targetId, int $thievingLevel, ?string $locationType, ?int $locationId): array
    {
        // Award gold
        $goldStolen = mt_rand($config['gold_range'][0], $config['gold_range'][1]);
        $user->increment('gold', $goldStolen);

        // Award XP
        $xpAwarded = $config['base_xp'];
        $skill = $user->skills()->where('skill_name', 'thieving')->first();
        $oldLevel = $skill->level;
        $skill->addXp($xpAwarded);
        $leveledUp = $skill->fresh()->level > $oldLevel;

        // Chance to get loot item (40% base chance)
        $lootItem = null;
        $lootQuantity = 0;
        if (mt_rand(1, 100) <= 40 && ! empty($config['loot_table'])) {
            $loot = $this->selectWeightedLoot($config['loot_table']);
            if ($loot) {
                $item = Item::where('name', $loot['item'])->first();
                if ($item && $this->inventoryService->hasEmptySlot($user)) {
                    $lootQuantity = mt_rand($loot['quantity'][0], $loot['quantity'][1]);
                    $this->inventoryService->addItem($user, $item, $lootQuantity);
                    $lootItem = [
                        'name' => $item->name,
                        'quantity' => $lootQuantity,
                    ];
                }
            }
        }

        // Record daily task progress
        $this->dailyTaskService->recordProgress($user, 'thieve', $config['name'], 1);

        // Log activity
        if ($locationType && $locationId) {
            try {
                LocationActivityLog::log(
                    userId: $user->id,
                    locationType: $locationType,
                    locationId: $locationId,
                    activityType: LocationActivityLog::TYPE_THIEVING ?? 'thieving',
                    description: "{$user->username} successfully pickpocketed a {$config['name']}",
                    activitySubtype: $targetId,
                    metadata: [
                        'target' => $config['name'],
                        'gold_stolen' => $goldStolen,
                        'xp_gained' => $xpAwarded,
                        'loot' => $lootItem,
                    ]
                );
            } catch (\Illuminate\Database\QueryException $e) {
                // Table may not exist
            }
        }

        $message = "You successfully pickpocketed the {$config['name']} and stole {$goldStolen}g!";
        if ($lootItem) {
            $message .= " You also found {$lootQuantity}x {$lootItem['name']}!";
        }

        return [
            'success' => true,
            'caught' => false,
            'message' => $message,
            'gold_stolen' => $goldStolen,
            'xp_awarded' => $xpAwarded,
            'leveled_up' => $leveledUp,
            'loot' => $lootItem,
            'energy_remaining' => $user->fresh()->energy,
            'gold_remaining' => $user->fresh()->gold,
        ];
    }

    /**
     * Handle failed thieving attempt (caught).
     */
    protected function handleFailure(User $user, array $config, string $targetId, ?string $locationType, ?int $locationId): array
    {
        // Extra energy penalty
        $extraEnergyLoss = $config['catch_energy_loss'] - $config['energy_cost'];
        if ($extraEnergyLoss > 0 && $user->energy >= $extraEnergyLoss) {
            $user->consumeEnergy($extraEnergyLoss);
        }

        // Gold fine (only if player has enough)
        $goldLost = 0;
        if ($user->gold >= $config['catch_gold_loss']) {
            $goldLost = $config['catch_gold_loss'];
            $user->decrement('gold', $goldLost);
        } elseif ($user->gold > 0) {
            $goldLost = $user->gold;
            $user->decrement('gold', $goldLost);
        }

        // Still award some XP for the attempt (25% of base)
        $xpAwarded = (int) ceil($config['base_xp'] * 0.25);
        $skill = $user->skills()->where('skill_name', 'thieving')->first();
        $skill->addXp($xpAwarded);

        // Log activity
        if ($locationType && $locationId) {
            try {
                LocationActivityLog::log(
                    userId: $user->id,
                    locationType: $locationType,
                    locationId: $locationId,
                    activityType: LocationActivityLog::TYPE_THIEVING ?? 'thieving',
                    description: "{$user->username} was caught trying to pickpocket a {$config['name']}",
                    activitySubtype: $targetId,
                    metadata: [
                        'target' => $config['name'],
                        'gold_lost' => $goldLost,
                        'caught' => true,
                    ]
                );
            } catch (\Illuminate\Database\QueryException $e) {
                // Table may not exist
            }
        }

        $message = "You were caught trying to pickpocket the {$config['name']}!";
        if ($goldLost > 0) {
            $message .= " You paid a fine of {$goldLost}g.";
        }

        return [
            'success' => false,
            'caught' => true,
            'message' => $message,
            'gold_lost' => $goldLost,
            'xp_awarded' => $xpAwarded,
            'energy_remaining' => $user->fresh()->energy,
            'gold_remaining' => $user->fresh()->gold,
        ];
    }

    /**
     * Select loot from weighted table.
     */
    protected function selectWeightedLoot(array $lootTable): ?array
    {
        $totalWeight = array_sum(array_column($lootTable, 'weight'));
        $random = mt_rand(1, $totalWeight);
        $cumulative = 0;

        foreach ($lootTable as $loot) {
            $cumulative += $loot['weight'];
            if ($random <= $cumulative) {
                return $loot;
            }
        }

        return $lootTable[0] ?? null;
    }

    /**
     * Get thieving info for the page.
     */
    public function getThievingInfo(User $user): array
    {
        $skill = $user->skills()->where('skill_name', 'thieving')->first();
        $thievingLevel = $skill?->level ?? 1;

        return [
            'can_thieve' => $this->canThieve($user),
            'targets' => $this->getAvailableTargets($user),
            'player_energy' => $user->energy,
            'max_energy' => $user->max_energy,
            'player_gold' => $user->gold,
            'thieving_level' => $thievingLevel,
            'thieving_xp' => $skill?->xp ?? 0,
            'thieving_xp_progress' => $skill?->getXpProgress() ?? 0,
            'thieving_xp_to_next' => $skill?->xpToNextLevel() ?? 60,
            'free_slots' => $this->inventoryService->freeSlots($user),
        ];
    }
}

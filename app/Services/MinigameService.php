<?php

namespace App\Services;

use App\Models\Item;
use App\Models\MinigamePlay;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MinigameService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Check if a user can play today.
     */
    public function canPlay(User $user): bool
    {
        return MinigamePlay::canPlayToday($user->id);
    }

    /**
     * Get player info for the minigame.
     *
     * @return array{can_play: bool, streak: int, last_play: string|null, next_streak: int, reward_chances: array}
     */
    public function getPlayerInfo(User $user): array
    {
        $lastPlay = MinigamePlay::where('user_id', $user->id)
            ->orderBy('played_at', 'desc')
            ->first();

        $currentStreak = MinigamePlay::getCurrentStreak($user->id);
        $nextStreak = MinigamePlay::getNextStreakCount($user->id);

        return [
            'can_play' => $this->canPlay($user),
            'streak' => $currentStreak,
            'last_play' => $lastPlay?->played_at?->toDateString(),
            'next_streak' => $nextStreak,
            'reward_chances' => MinigamePlay::getRewardChances($nextStreak),
        ];
    }

    /**
     * Get recent rewards history for the user.
     *
     * @return array<int, array{played_at: string, reward_type: string, reward_value: int, reward_item: string|null, streak: int}>
     */
    public function getRewardsHistory(User $user, int $limit = 10): array
    {
        return MinigamePlay::where('user_id', $user->id)
            ->with('rewardItem')
            ->orderBy('played_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn (MinigamePlay $play) => [
                'played_at' => $play->played_at->toDateString(),
                'reward_type' => $play->reward_type,
                'reward_value' => $play->reward_value,
                'reward_item' => $play->rewardItem?->name,
                'streak' => $play->streak_count,
            ])
            ->toArray();
    }

    /**
     * Play the minigame and receive rewards.
     *
     * @return array{success: bool, reward_type: string|null, reward_value: int|null, reward_item: array|null, streak: int, error?: string}
     */
    public function play(User $user): array
    {
        if (! $this->canPlay($user)) {
            return [
                'success' => false,
                'reward_type' => null,
                'reward_value' => null,
                'reward_item' => null,
                'streak' => MinigamePlay::getCurrentStreak($user->id),
                'error' => 'You have already played today. Come back tomorrow!',
            ];
        }

        return DB::transaction(function () use ($user) {
            $streakCount = MinigamePlay::getNextStreakCount($user->id);
            $rewardType = MinigamePlay::rollRewardType($streakCount);

            $rewardValue = 0;
            $rewardItem = null;
            $rewardItemData = null;

            switch ($rewardType) {
                case MinigamePlay::REWARD_COMMON:
                    $rewardValue = random_int(50, 150);
                    break;

                case MinigamePlay::REWARD_UNCOMMON:
                    $rewardValue = random_int(150, 300);
                    break;

                case MinigamePlay::REWARD_RARE:
                    // 50% chance for gold, 50% chance for rare item
                    if (random_int(0, 1) === 0) {
                        $rewardValue = random_int(300, 500);
                    } else {
                        $rewardItem = Item::where('rarity', 'rare')
                            ->where('is_tradeable', true)
                            ->inRandomOrder()
                            ->first();
                    }
                    break;

                case MinigamePlay::REWARD_EPIC:
                    // Gold AND epic item
                    $rewardValue = random_int(500, 1000);
                    $rewardItem = Item::where('rarity', 'epic')
                        ->where('is_tradeable', true)
                        ->inRandomOrder()
                        ->first();
                    break;

                case MinigamePlay::REWARD_MYSTERY:
                    // Mystery Box: Random rare/epic/legendary item with weighted chances
                    // 50% rare, 35% epic, 15% legendary
                    $roll = random_int(1, 100);
                    $itemRarity = match (true) {
                        $roll <= 50 => 'rare',
                        $roll <= 85 => 'epic',
                        default => 'legendary',
                    };
                    $rewardItem = Item::where('rarity', $itemRarity)
                        ->where('is_tradeable', true)
                        ->inRandomOrder()
                        ->first();
                    // Small gold bonus too
                    $rewardValue = random_int(100, 300);
                    break;
            }

            // Give gold reward
            if ($rewardValue > 0) {
                $user->increment('gold', $rewardValue);
            }

            // Give item reward
            if ($rewardItem) {
                $this->inventoryService->addItem($user, $rewardItem, 1);
                $rewardItemData = [
                    'id' => $rewardItem->id,
                    'name' => $rewardItem->name,
                    'rarity' => $rewardItem->rarity,
                ];
            }

            // Create minigame play record
            MinigamePlay::create([
                'user_id' => $user->id,
                'played_at' => today(),
                'streak_count' => $streakCount,
                'reward_type' => $rewardType,
                'reward_value' => $rewardValue,
                'reward_item_id' => $rewardItem?->id,
            ]);

            return [
                'success' => true,
                'reward_type' => $rewardType,
                'reward_value' => $rewardValue,
                'reward_item' => $rewardItemData,
                'streak' => $streakCount,
            ];
        });
    }
}

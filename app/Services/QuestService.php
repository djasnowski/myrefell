<?php

namespace App\Services;

use App\Models\PlayerQuest;
use App\Models\Quest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuestService
{
    /**
     * Maximum active quests per player.
     */
    public const MAX_ACTIVE_QUESTS = 5;

    public function __construct(
        protected InventoryService $inventoryService,
        protected BeliefEffectService $beliefEffectService
    ) {}

    /**
     * Get available quests for the notice board.
     */
    public function getAvailableQuests(User $user): Collection
    {
        $activeQuestIds = PlayerQuest::where('user_id', $user->id)
            ->where('status', PlayerQuest::STATUS_ACTIVE)
            ->pluck('quest_id');

        // Get quests the player hasn't claimed recently
        $recentlyClaimedIds = PlayerQuest::where('user_id', $user->id)
            ->where('status', PlayerQuest::STATUS_CLAIMED)
            ->where('claimed_at', '>', now()->subDay())
            ->pluck('quest_id');

        return Quest::where('is_active', true)
            ->whereNotIn('id', $activeQuestIds)
            ->whereNotIn('id', $recentlyClaimedIds)
            ->get()
            ->filter(fn ($quest) => $quest->playerMeetsRequirements($user))
            ->map(fn ($quest) => $this->formatQuest($quest, $user))
            ->values();
    }

    /**
     * Get the player's active quests.
     */
    public function getActiveQuests(User $user): Collection
    {
        return PlayerQuest::where('user_id', $user->id)
            ->where('status', PlayerQuest::STATUS_ACTIVE)
            ->with('quest')
            ->get()
            ->map(fn ($pq) => $this->formatPlayerQuest($pq));
    }

    /**
     * Get the player's completed quests ready to claim.
     */
    public function getCompletedQuests(User $user): Collection
    {
        return PlayerQuest::where('user_id', $user->id)
            ->where('status', PlayerQuest::STATUS_COMPLETED)
            ->with('quest')
            ->get()
            ->map(fn ($pq) => $this->formatPlayerQuest($pq));
    }

    /**
     * Accept a quest.
     */
    public function acceptQuest(User $user, Quest $quest): array
    {
        // Check if already has this quest
        $existing = PlayerQuest::where('user_id', $user->id)
            ->where('quest_id', $quest->id)
            ->where('status', PlayerQuest::STATUS_ACTIVE)
            ->exists();

        if ($existing) {
            return [
                'success' => false,
                'message' => 'You already have this quest.',
            ];
        }

        // Check max quests
        $activeCount = PlayerQuest::where('user_id', $user->id)
            ->where('status', PlayerQuest::STATUS_ACTIVE)
            ->count();

        if ($activeCount >= self::MAX_ACTIVE_QUESTS) {
            return [
                'success' => false,
                'message' => 'You have too many active quests. Complete or abandon some first.',
            ];
        }

        // Check requirements
        if (! $quest->playerMeetsRequirements($user)) {
            return [
                'success' => false,
                'message' => 'You do not meet the requirements for this quest.',
            ];
        }

        // Check cooldown for repeatable quests
        if ($quest->repeatable && $quest->cooldown_hours > 0) {
            $lastClaimed = PlayerQuest::where('user_id', $user->id)
                ->where('quest_id', $quest->id)
                ->where('status', PlayerQuest::STATUS_CLAIMED)
                ->orderByDesc('claimed_at')
                ->first();

            if ($lastClaimed && $lastClaimed->claimed_at->addHours($quest->cooldown_hours)->isFuture()) {
                $remaining = $lastClaimed->claimed_at->addHours($quest->cooldown_hours)->diffForHumans();

                return [
                    'success' => false,
                    'message' => "You can accept this quest again {$remaining}.",
                ];
            }
        }

        PlayerQuest::create([
            'user_id' => $user->id,
            'quest_id' => $quest->id,
            'status' => PlayerQuest::STATUS_ACTIVE,
            'current_progress' => 0,
            'accepted_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => "Quest accepted: {$quest->name}",
        ];
    }

    /**
     * Abandon a quest.
     */
    public function abandonQuest(User $user, PlayerQuest $playerQuest): array
    {
        if ($playerQuest->user_id !== $user->id) {
            return [
                'success' => false,
                'message' => 'This quest does not belong to you.',
            ];
        }

        if ($playerQuest->status !== PlayerQuest::STATUS_ACTIVE) {
            return [
                'success' => false,
                'message' => 'This quest cannot be abandoned.',
            ];
        }

        $playerQuest->abandon();

        return [
            'success' => true,
            'message' => 'Quest abandoned.',
        ];
    }

    /**
     * Claim rewards for a completed quest.
     */
    public function claimReward(User $user, PlayerQuest $playerQuest): array
    {
        if ($playerQuest->user_id !== $user->id) {
            return [
                'success' => false,
                'message' => 'This quest does not belong to you.',
            ];
        }

        if ($playerQuest->status !== PlayerQuest::STATUS_COMPLETED) {
            return [
                'success' => false,
                'message' => 'This quest is not completed yet.',
            ];
        }

        $quest = $playerQuest->quest;
        $rewards = [];

        return DB::transaction(function () use ($user, $playerQuest, $quest, &$rewards) {
            // Calculate quest XP bonus from beliefs (Wisdom belief: +10%)
            $questXpBonus = $this->beliefEffectService->getEffect($user, 'quest_xp_bonus');

            // Grant gold reward
            if ($quest->gold_reward > 0) {
                $user->increment('gold', $quest->gold_reward);
                $rewards['gold'] = $quest->gold_reward;
            }

            // Grant XP reward (with belief bonus)
            if ($quest->xp_reward > 0 && $quest->xp_skill) {
                $skill = $user->skills()->where('skill_name', $quest->xp_skill)->first();

                if (! $skill) {
                    $skill = $user->skills()->create([
                        'skill_name' => $quest->xp_skill,
                        'level' => 1,
                        'xp' => 0,
                    ]);
                }

                $xpReward = $quest->xp_reward;
                if ($questXpBonus > 0) {
                    $xpReward = (int) ceil($xpReward * (1 + $questXpBonus / 100));
                }

                $skill->addXp($xpReward);
                $rewards['xp'] = [
                    'amount' => $xpReward,
                    'skill' => $quest->xp_skill,
                ];
            }

            // Grant item rewards
            if ($quest->item_rewards && is_array($quest->item_rewards)) {
                $itemRewards = [];
                foreach ($quest->item_rewards as $itemReward) {
                    $added = $this->inventoryService->addItem(
                        $user,
                        $itemReward['item_id'],
                        $itemReward['quantity']
                    );
                    if ($added) {
                        $itemRewards[] = $itemReward;
                    }
                }
                if (! empty($itemRewards)) {
                    $rewards['items'] = $itemRewards;
                }
            }

            // Mark as claimed
            $playerQuest->claim();

            return [
                'success' => true,
                'message' => 'Rewards claimed!',
                'rewards' => $rewards,
            ];
        });
    }

    /**
     * Record progress for a quest type.
     */
    public function recordProgress(User $user, string $questType, ?string $targetIdentifier = null, int $amount = 1): void
    {
        $query = PlayerQuest::where('user_id', $user->id)
            ->where('status', PlayerQuest::STATUS_ACTIVE)
            ->whereHas('quest', function ($q) use ($questType, $targetIdentifier) {
                $q->where('quest_type', $questType);
                if ($targetIdentifier) {
                    $q->where(function ($q2) use ($targetIdentifier) {
                        $q2->whereNull('target_identifier')
                            ->orWhere('target_identifier', $targetIdentifier);
                    });
                }
            });

        $quests = $query->get();

        foreach ($quests as $quest) {
            $quest->addProgress($amount);
        }
    }

    /**
     * Format a quest for display.
     */
    protected function formatQuest(Quest $quest, User $user): array
    {
        return [
            'id' => $quest->id,
            'name' => $quest->name,
            'icon' => $quest->icon,
            'description' => $quest->description,
            'objective' => $quest->objective,
            'category' => $quest->category,
            'category_display' => $quest->category_display,
            'quest_type' => $quest->quest_type,
            'target_amount' => $quest->target_amount,
            'gold_reward' => $quest->gold_reward,
            'xp_reward' => $quest->xp_reward,
            'xp_skill' => $quest->xp_skill,
            'repeatable' => $quest->repeatable,
            'required_level' => $quest->required_level,
            'required_skill' => $quest->required_skill,
            'required_skill_level' => $quest->required_skill_level,
        ];
    }

    /**
     * Format a player quest for display.
     */
    protected function formatPlayerQuest(PlayerQuest $playerQuest): array
    {
        $quest = $playerQuest->quest;

        return [
            'id' => $playerQuest->id,
            'quest_id' => $quest->id,
            'name' => $quest->name,
            'icon' => $quest->icon,
            'description' => $quest->description,
            'objective' => $quest->objective,
            'category' => $quest->category,
            'target_amount' => $quest->target_amount,
            'current_progress' => $playerQuest->current_progress,
            'progress_percent' => $playerQuest->progress_percent,
            'status' => $playerQuest->status,
            'can_claim' => $playerQuest->status === PlayerQuest::STATUS_COMPLETED,
            'gold_reward' => $quest->gold_reward,
            'xp_reward' => $quest->xp_reward,
            'xp_skill' => $quest->xp_skill,
            'accepted_at' => $playerQuest->accepted_at->toISOString(),
        ];
    }

    /**
     * Seed default quests.
     */
    public static function seedDefaultQuests(): void
    {
        $quests = [
            // Combat quests
            [
                'name' => 'Pest Control',
                'icon' => 'bug',
                'description' => 'The village has been plagued by rats. Clear them out!',
                'objective' => 'Defeat rats in the village cellar',
                'category' => 'combat',
                'quest_type' => 'kill',
                'target_type' => 'monster',
                'target_identifier' => 'rat',
                'target_amount' => 5,
                'gold_reward' => 25,
                'xp_reward' => 30,
                'xp_skill' => 'attack',
                'repeatable' => true,
                'cooldown_hours' => 24,
            ],
            [
                'name' => 'Wolf Pack',
                'icon' => 'paw-print',
                'description' => 'A wolf pack has been threatening the livestock. Hunt them down.',
                'objective' => 'Defeat wolves near the village',
                'category' => 'combat',
                'quest_type' => 'kill',
                'target_type' => 'monster',
                'target_identifier' => 'wolf',
                'target_amount' => 3,
                'required_level' => 5,
                'gold_reward' => 50,
                'xp_reward' => 60,
                'xp_skill' => 'strength',
                'repeatable' => true,
                'cooldown_hours' => 24,
            ],

            // Gathering quests
            [
                'name' => 'Ore Shipment',
                'icon' => 'gem',
                'description' => 'The blacksmith needs copper ore for a special order.',
                'objective' => 'Gather copper ore from the mines',
                'category' => 'gathering',
                'quest_type' => 'gather',
                'target_type' => 'item',
                'target_identifier' => 'Copper Ore',
                'target_amount' => 10,
                'gold_reward' => 30,
                'xp_reward' => 40,
                'xp_skill' => 'mining',
                'repeatable' => true,
                'cooldown_hours' => 12,
            ],
            [
                'name' => 'Fresh Fish',
                'icon' => 'fish',
                'description' => 'The tavern needs fresh fish for tonight\'s dinner.',
                'objective' => 'Catch fish from the river',
                'category' => 'gathering',
                'quest_type' => 'gather',
                'target_type' => 'item',
                'target_identifier' => 'Raw Trout',
                'target_amount' => 5,
                'required_skill' => 'fishing',
                'required_skill_level' => 10,
                'gold_reward' => 40,
                'xp_reward' => 50,
                'xp_skill' => 'fishing',
                'repeatable' => true,
                'cooldown_hours' => 12,
            ],
            [
                'name' => 'Wood for Winter',
                'icon' => 'axe',
                'description' => 'Help stockpile wood for the coming winter.',
                'objective' => 'Chop wood from the forest',
                'category' => 'gathering',
                'quest_type' => 'gather',
                'target_type' => 'item',
                'target_identifier' => 'Wood',
                'target_amount' => 15,
                'gold_reward' => 25,
                'xp_reward' => 35,
                'xp_skill' => 'crafting',
                'repeatable' => true,
                'cooldown_hours' => 12,
            ],

            // Crafting quests
            [
                'name' => 'Bronze Bars',
                'icon' => 'hammer',
                'description' => 'The blacksmith needs bronze bars for new tools.',
                'objective' => 'Smith bronze bars',
                'category' => 'delivery',
                'quest_type' => 'craft',
                'target_type' => 'item',
                'target_identifier' => 'Bronze Bar',
                'target_amount' => 5,
                'gold_reward' => 35,
                'xp_reward' => 45,
                'xp_skill' => 'smithing',
                'repeatable' => true,
                'cooldown_hours' => 24,
            ],
            [
                'name' => 'Bread for the Poor',
                'icon' => 'utensils',
                'description' => 'Help feed the village by baking bread.',
                'objective' => 'Bake bread for the village',
                'category' => 'delivery',
                'quest_type' => 'craft',
                'target_type' => 'item',
                'target_identifier' => 'Bread',
                'target_amount' => 10,
                'gold_reward' => 30,
                'xp_reward' => 40,
                'xp_skill' => 'cooking',
                'repeatable' => true,
                'cooldown_hours' => 24,
            ],
        ];

        foreach ($quests as $questData) {
            Quest::updateOrCreate(
                ['name' => $questData['name']],
                $questData
            );
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\MinigameReward;
use App\Models\MinigameScore;
use App\Models\PlayerSkill;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\MinigameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MinigameController extends Controller
{
    public function __construct(
        protected MinigameService $minigameService,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Display the minigames page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $playerInfo = $this->minigameService->getPlayerInfo($user);
        $rewardsHistory = $this->minigameService->getRewardsHistory($user);

        return Inertia::render('Minigames/Index', [
            'can_play' => $playerInfo['can_play'],
            'streak' => $playerInfo['streak'],
            'last_played' => $playerInfo['last_play'],
            'reward_chances' => $playerInfo['reward_chances'],
            'recent_plays' => $this->mapRecentPlays($rewardsHistory),
        ]);
    }

    /**
     * Map recent plays to frontend format.
     */
    protected function mapRecentPlays(array $plays): array
    {
        return array_map(fn (array $play, int $index) => [
            'id' => $index,
            'reward_type' => $play['reward_item'] ? 'item' : 'gold',
            'reward_amount' => $play['reward_value'],
            'reward_rarity' => $play['reward_type'],
            'item_name' => $play['reward_item'],
            'played_at' => $play['played_at'],
        ], $plays, array_keys($plays));
    }

    /**
     * Spin the wheel of fortune.
     */
    public function spin(Request $request): RedirectResponse
    {
        $user = $request->user();

        try {
            $result = $this->minigameService->play($user);

            if (! $result['success']) {
                return back()->with('error', $result['error'] ?? 'You have already played today.');
            }

            // Map to frontend expected format
            $spinResult = [
                'success' => true,
                'reward_type' => $result['reward_item'] ? 'item' : 'gold',
                'reward_amount' => $result['reward_value'],
                'reward_item' => $result['reward_item'],
                'rarity' => $result['reward_type'],
                'message' => $this->formatRewardMessage($result),
                'new_streak' => $result['streak'],
                'segment_index' => $this->calculateSegmentIndex($result),
            ];

            // Only send result data - don't send success flash to avoid
            // revealing reward before wheel animation completes
            return back()->with('result', $spinResult);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Submit a score after playing a minigame.
     */
    public function submitScore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'minigame' => 'required|string|max:50',
            'score' => 'required|integer|min:0',
            'location_type' => 'required|string|in:village,town,barony,duchy,kingdom',
            'location_id' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $minigame = $validated['minigame'];
        $score = $validated['score'];

        // Check if daily-limited game and user already played today
        if (MinigameScore::isDailyLimited($minigame) && MinigameScore::hasPlayedToday($user->id, $minigame)) {
            return back()->with('error', 'You have already played this minigame today. Come back tomorrow!');
        }

        try {
            DB::transaction(function () use ($user, $validated, $score) {
                // Create the score record
                MinigameScore::create([
                    'user_id' => $user->id,
                    'minigame' => $validated['minigame'],
                    'score' => $score,
                    'location_type' => $validated['location_type'],
                    'location_id' => $validated['location_id'],
                    'played_at' => now(),
                ]);

                // Award range XP based on score
                $this->awardRangeXp($user, $score);
            });

            return back()->with('success', "Score submitted! You earned {$score} Range XP.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to submit score. Please try again.');
        }
    }

    /**
     * Get leaderboard data for a minigame.
     */
    public function getLeaderboards(Request $request, string $minigame): JsonResponse
    {
        $user = $request->user();

        // Get global leaderboards (across all locations)
        $dailyLeaderboard = MinigameScore::getGlobalLeaderboard($minigame, 'daily', 10);
        $weeklyLeaderboard = MinigameScore::getGlobalLeaderboard($minigame, 'weekly', 10);
        $monthlyLeaderboard = MinigameScore::getGlobalLeaderboard($minigame, 'monthly', 10);

        // Format leaderboards with user info
        $formatLeaderboard = function ($leaderboard) {
            return $leaderboard->map(function ($entry, $index) {
                $entryUser = User::find($entry->user_id);

                return [
                    'rank' => $index + 1,
                    'user_id' => $entry->user_id,
                    'username' => $entryUser?->username ?? 'Unknown',
                    'score' => $entry->best_score,
                ];
            })->values();
        };

        // Get user's ranks
        $userDailyRank = MinigameScore::getUserGlobalRank($user->id, $minigame, 'daily');
        $userWeeklyRank = MinigameScore::getUserGlobalRank($user->id, $minigame, 'weekly');
        $userMonthlyRank = MinigameScore::getUserGlobalRank($user->id, $minigame, 'monthly');

        return response()->json([
            'success' => true,
            'minigame' => $minigame,
            'daily' => [
                'leaderboard' => $formatLeaderboard($dailyLeaderboard),
                'user_rank' => $userDailyRank,
            ],
            'weekly' => [
                'leaderboard' => $formatLeaderboard($weeklyLeaderboard),
                'user_rank' => $userWeeklyRank,
            ],
            'monthly' => [
                'leaderboard' => $formatLeaderboard($monthlyLeaderboard),
                'user_rank' => $userMonthlyRank,
            ],
        ]);
    }

    /**
     * Collect pending rewards at the user's current location.
     */
    public function collectRewards(Request $request): RedirectResponse
    {
        $user = $request->user();
        $locationType = $user->current_location_type;
        $locationId = $user->current_location_id;

        if (! $locationType || ! $locationId) {
            return back()->with('error', 'You must be at a location to collect rewards.');
        }

        // Get uncollected rewards at current location
        $rewards = MinigameReward::getUncollectedAtLocation($user->id, $locationType, $locationId);

        if ($rewards->isEmpty()) {
            return back()->with('error', 'You have no rewards to collect at this location.');
        }

        $totalGold = 0;
        $itemsCollected = [];

        try {
            DB::transaction(function () use ($user, $rewards, &$totalGold, &$itemsCollected) {
                foreach ($rewards as $reward) {
                    // Award gold
                    if ($reward->gold_amount > 0) {
                        $user->increment('gold', $reward->gold_amount);
                        $totalGold += $reward->gold_amount;
                    }

                    // Award item
                    if ($reward->hasItem() && $reward->item) {
                        $this->inventoryService->addItem($user, $reward->item, 1);
                        $itemsCollected[] = [
                            'name' => $reward->item->name,
                            'rarity' => $reward->item_rarity,
                        ];
                    }

                    // Mark as collected
                    $reward->collect();
                }
            });

            $message = $this->formatCollectionMessage($totalGold, $itemsCollected);

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to collect rewards. Please try again.');
        }
    }

    /**
     * Get user's pending (uncollected) rewards.
     */
    public function getPendingRewards(Request $request): JsonResponse
    {
        $user = $request->user();

        $rewards = MinigameReward::getUncollectedForUser($user->id);

        // Group rewards by location
        $groupedRewards = $rewards->groupBy(function ($reward) {
            return $reward->location_type.'_'.$reward->location_id;
        })->map(function ($locationRewards) {
            $first = $locationRewards->first();

            return [
                'location_type' => $first->location_type,
                'location_id' => $first->location_id,
                'location_name' => $this->getLocationName($first->location_type, $first->location_id),
                'rewards' => $locationRewards->map(function ($reward) {
                    return [
                        'id' => $reward->id,
                        'minigame' => $reward->minigame,
                        'reward_type' => $reward->reward_type,
                        'rank' => $reward->rank,
                        'gold_amount' => $reward->gold_amount,
                        'item' => $reward->item ? [
                            'id' => $reward->item->id,
                            'name' => $reward->item->name,
                            'rarity' => $reward->item_rarity,
                        ] : null,
                        'period_start' => $reward->period_start->toDateString(),
                        'period_end' => $reward->period_end->toDateString(),
                    ];
                })->values(),
                'total_gold' => $locationRewards->sum('gold_amount'),
                'item_count' => $locationRewards->filter(fn ($r) => $r->hasItem())->count(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'total_pending' => $rewards->count(),
            'locations' => $groupedRewards,
        ]);
    }

    /**
     * Award Range XP to the user based on score.
     */
    protected function awardRangeXp(User $user, int $xpAmount): void
    {
        $skill = $user->skills()->where('skill_name', 'range')->first();

        if (! $skill) {
            $skill = PlayerSkill::create([
                'player_id' => $user->id,
                'skill_name' => 'range',
                'level' => 5, // Combat skills start at level 5
                'xp' => PlayerSkill::xpForLevel(5),
            ]);
        }

        $skill->addXp($xpAmount);
    }

    /**
     * Get the name of a location.
     */
    protected function getLocationName(string $locationType, int $locationId): string
    {
        $model = match ($locationType) {
            'village' => \App\Models\Village::class,
            'town' => \App\Models\Town::class,
            'barony' => \App\Models\Barony::class,
            'duchy' => \App\Models\Duchy::class,
            'kingdom' => \App\Models\Kingdom::class,
            default => null,
        };

        if (! $model) {
            return 'Unknown Location';
        }

        $location = $model::find($locationId);

        return $location?->name ?? 'Unknown Location';
    }

    /**
     * Format the reward collection message.
     */
    protected function formatCollectionMessage(int $totalGold, array $itemsCollected): string
    {
        $parts = [];

        if ($totalGold > 0) {
            $parts[] = "{$totalGold} gold";
        }

        if (! empty($itemsCollected)) {
            $itemNames = array_map(fn ($item) => $item['name'], $itemsCollected);
            $parts[] = implode(', ', $itemNames);
        }

        if (empty($parts)) {
            return 'Rewards collected!';
        }

        return 'Collected: '.implode(' and ', $parts);
    }

    /**
     * Calculate which wheel segment to land on based on result.
     */
    protected function calculateSegmentIndex(array $result): int
    {
        // Map rewards to segment indexes
        // Segments: 0=50g, 1=rare item, 2=100g, 3=150g, 4=epic item, 5=200g,
        //           6=mystery, 7=300g, 8=500g, 9=jackpot, 10=750g, 11=1000g

        // Mystery Box always lands on segment 6
        if ($result['reward_type'] === 'mystery') {
            return 6;
        }

        // Items land on their rarity segment
        if ($result['reward_item']) {
            return $result['reward_type'] === 'epic' ? 4 : 1;
        }

        $gold = $result['reward_value'];

        return match (true) {
            $gold <= 75 => 0,    // 50 Gold
            $gold <= 125 => 2,   // 100 Gold
            $gold <= 175 => 3,   // 150 Gold
            $gold <= 250 => 5,   // 200 Gold
            $gold <= 400 => 7,   // 300 Gold
            $gold <= 600 => 8,   // 500 Gold
            $gold <= 875 => 10,  // 750 Gold
            default => 11,       // 1000 Gold
        };
    }

    /**
     * Format reward message for display.
     */
    protected function formatRewardMessage(array $result): string
    {
        if (! $result['success']) {
            return $result['error'] ?? 'Something went wrong.';
        }

        $parts = [];
        $rewardType = ucfirst($result['reward_type'] ?? 'unknown');

        if ($result['reward_value'] > 0) {
            $parts[] = "{$result['reward_value']} gold";
        }

        if ($result['reward_item']) {
            $parts[] = $result['reward_item']['name'];
        }

        if (empty($parts)) {
            return 'You spun the wheel!';
        }

        return "{$rewardType} reward! You won: ".implode(' and ', $parts);
    }
}

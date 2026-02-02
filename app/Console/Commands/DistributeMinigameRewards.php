<?php

namespace App\Console\Commands;

use App\Models\MinigameReward;
use App\Models\MinigameScore;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DistributeMinigameRewards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'minigames:distribute-rewards {--minigame=archery : The minigame to distribute rewards for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Distribute minigame leaderboard rewards (run daily)';

    /**
     * Reward structure for each rank.
     *
     * @var array<int, array{item_rarity: string|null, gold: int}>
     */
    protected array $rewardStructure = [
        1 => ['item_rarity' => 'legendary', 'gold' => 1000],
        2 => ['item_rarity' => 'epic', 'gold' => 500],
        3 => ['item_rarity' => 'rare', 'gold' => 250],
        4 => ['item_rarity' => null, 'gold' => 100],
        5 => ['item_rarity' => null, 'gold' => 100],
        6 => ['item_rarity' => null, 'gold' => 100],
        7 => ['item_rarity' => null, 'gold' => 100],
        8 => ['item_rarity' => null, 'gold' => 100],
        9 => ['item_rarity' => null, 'gold' => 100],
        10 => ['item_rarity' => null, 'gold' => 100],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minigame = $this->option('minigame');
        $today = now();

        $this->info("Distributing rewards for minigame: {$minigame}");
        Log::info("Starting minigame reward distribution for: {$minigame}");

        $totalRewards = 0;

        // Always distribute daily rewards (for yesterday)
        $dailyRewards = $this->distributeRewards(
            $minigame,
            MinigameReward::TYPE_DAILY,
            $today->copy()->subDay()->startOfDay(),
            $today->copy()->subDay()->endOfDay()
        );
        $totalRewards += $dailyRewards;
        $this->info("Daily rewards distributed: {$dailyRewards}");

        // Distribute weekly rewards if it's Monday (for last week)
        if ($today->isMonday()) {
            $weeklyRewards = $this->distributeRewards(
                $minigame,
                MinigameReward::TYPE_WEEKLY,
                $today->copy()->subWeek()->startOfWeek(),
                $today->copy()->subWeek()->endOfWeek()
            );
            $totalRewards += $weeklyRewards;
            $this->info("Weekly rewards distributed: {$weeklyRewards}");
        }

        // Distribute monthly rewards if it's the 1st of the month (for last month)
        if ($today->day === 1) {
            $monthlyRewards = $this->distributeRewards(
                $minigame,
                MinigameReward::TYPE_MONTHLY,
                $today->copy()->subMonth()->startOfMonth(),
                $today->copy()->subMonth()->endOfMonth()
            );
            $totalRewards += $monthlyRewards;
            $this->info("Monthly rewards distributed: {$monthlyRewards}");
        }

        $this->info("Total rewards distributed: {$totalRewards}");
        Log::info('Minigame reward distribution complete', [
            'minigame' => $minigame,
            'total_rewards' => $totalRewards,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Distribute rewards for a specific leaderboard type and period.
     */
    protected function distributeRewards(
        string $minigame,
        string $rewardType,
        Carbon $periodStart,
        Carbon $periodEnd
    ): int {
        // Get the top 10 players for this period, with their highest score and the location where it was achieved
        $rankings = $this->getTopPlayersWithLocation($minigame, $periodStart, $periodEnd);

        $rewardsCreated = 0;

        foreach ($rankings as $rank => $entry) {
            $playerRank = $rank + 1; // Convert 0-indexed to 1-indexed rank

            if ($playerRank > 10) {
                break;
            }

            $rewardConfig = $this->rewardStructure[$playerRank];

            // Check if reward already exists for this player/period/type
            $existingReward = MinigameReward::where('user_id', $entry->user_id)
                ->where('minigame', $minigame)
                ->where('reward_type', $rewardType)
                ->where('period_start', $periodStart->toDateString())
                ->where('period_end', $periodEnd->toDateString())
                ->exists();

            if ($existingReward) {
                Log::info('Reward already exists for user', [
                    'user_id' => $entry->user_id,
                    'minigame' => $minigame,
                    'reward_type' => $rewardType,
                    'period' => $periodStart->toDateString(),
                ]);

                continue;
            }

            MinigameReward::create([
                'user_id' => $entry->user_id,
                'minigame' => $minigame,
                'reward_type' => $rewardType,
                'rank' => $playerRank,
                'location_type' => $entry->location_type,
                'location_id' => $entry->location_id,
                'gold_amount' => $rewardConfig['gold'],
                'item_rarity' => $rewardConfig['item_rarity'],
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ]);

            $rewardsCreated++;

            Log::info('Distributed minigame reward', [
                'user_id' => $entry->user_id,
                'minigame' => $minigame,
                'reward_type' => $rewardType,
                'rank' => $playerRank,
                'gold' => $rewardConfig['gold'],
                'item_rarity' => $rewardConfig['item_rarity'],
                'location_type' => $entry->location_type,
                'location_id' => $entry->location_id,
                'period' => $periodStart->toDateString().' to '.$periodEnd->toDateString(),
            ]);
        }

        return $rewardsCreated;
    }

    /**
     * Get top players with the location where their highest score was achieved.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    protected function getTopPlayersWithLocation(
        string $minigame,
        Carbon $periodStart,
        Carbon $periodEnd
    ): \Illuminate\Support\Collection {
        // Subquery to get each user's maximum score in the period
        $maxScoreSubquery = MinigameScore::query()
            ->select('user_id', DB::raw('MAX(score) as max_score'))
            ->where('minigame', $minigame)
            ->whereBetween('played_at', [$periodStart, $periodEnd])
            ->groupBy('user_id');

        // Join with original table to get the location where the max score was achieved
        return MinigameScore::query()
            ->select([
                'minigame_scores.user_id',
                'minigame_scores.score as best_score',
                'minigame_scores.location_type',
                'minigame_scores.location_id',
            ])
            ->joinSub($maxScoreSubquery, 'max_scores', function ($join) {
                $join->on('minigame_scores.user_id', '=', 'max_scores.user_id')
                    ->on('minigame_scores.score', '=', 'max_scores.max_score');
            })
            ->where('minigame_scores.minigame', $minigame)
            ->whereBetween('minigame_scores.played_at', [$periodStart, $periodEnd])
            ->groupBy('minigame_scores.user_id') // Handle ties (same user, same score, different locations)
            ->orderByDesc('best_score')
            ->limit(10)
            ->get();
    }
}

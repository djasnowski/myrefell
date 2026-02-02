<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Kingdom;
use App\Models\MinigameReward;
use App\Models\MinigameScore;
use App\Models\Town;
use App\Models\User;
use App\Models\Village;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArenaController extends Controller
{
    /**
     * Show the arena page.
     */
    public function index(
        Request $request,
        ?Village $village = null,
        ?Town $town = null,
        ?Barony $barony = null,
        ?Duchy $duchy = null,
        ?Kingdom $kingdom = null
    ): Response {
        $user = $request->user();
        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);

        // Check if user has played archery today
        $hasPlayedToday = MinigameScore::hasPlayedToday($user->id, 'archery');

        // Get leaderboards for archery at this location
        $leaderboards = $this->getLeaderboards('archery', $locationType, $location?->id);

        // Get user's ranks in each leaderboard
        $userRanks = $this->getUserRanks($user->id, 'archery', $locationType, $location?->id);

        // Get pending rewards at this location
        $pendingRewards = $this->getPendingRewardsAtLocation($user->id, $locationType, $location?->id);

        return Inertia::render('Arena/Index', [
            'location' => [
                'id' => $location?->id,
                'name' => $location?->name,
                'type' => $locationType,
            ],
            'player' => [
                'id' => $user->id,
                'username' => $user->username,
                'gold' => $user->gold,
                'energy' => $user->energy,
                'max_energy' => $user->max_energy,
            ],
            'has_played_today' => $hasPlayedToday,
            'leaderboards' => $leaderboards,
            'user_ranks' => $userRanks,
            'pending_rewards' => $pendingRewards,
        ]);
    }

    /**
     * Get leaderboards for a minigame at a location.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function getLeaderboards(string $minigame, ?string $locationType, ?int $locationId): array
    {
        if (! $locationType || ! $locationId) {
            return [
                'daily' => [],
                'weekly' => [],
                'monthly' => [],
            ];
        }

        $formatLeaderboard = function ($leaderboard) {
            return $leaderboard->map(function ($entry, $index) {
                $user = User::find($entry->user_id);

                return [
                    'rank' => $index + 1,
                    'user_id' => $entry->user_id,
                    'username' => $user?->username ?? 'Unknown',
                    'score' => $entry->best_score,
                ];
            })->values()->toArray();
        };

        return [
            'daily' => $formatLeaderboard(MinigameScore::getDailyLeaderboard($minigame, $locationType, $locationId)),
            'weekly' => $formatLeaderboard(MinigameScore::getWeeklyLeaderboard($minigame, $locationType, $locationId)),
            'monthly' => $formatLeaderboard(MinigameScore::getMonthlyLeaderboard($minigame, $locationType, $locationId)),
        ];
    }

    /**
     * Get user's ranks in each leaderboard period.
     *
     * @return array<string, int|null>
     */
    protected function getUserRanks(int $userId, string $minigame, ?string $locationType, ?int $locationId): array
    {
        if (! $locationType || ! $locationId) {
            return [
                'daily' => null,
                'weekly' => null,
                'monthly' => null,
            ];
        }

        return [
            'daily' => MinigameScore::getUserDailyRank($userId, $minigame, $locationType, $locationId),
            'weekly' => MinigameScore::getUserWeeklyRank($userId, $minigame, $locationType, $locationId),
            'monthly' => MinigameScore::getUserMonthlyRank($userId, $minigame, $locationType, $locationId),
        ];
    }

    /**
     * Get pending rewards at a specific location.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getPendingRewardsAtLocation(int $userId, ?string $locationType, ?int $locationId): array
    {
        if (! $locationType || ! $locationId) {
            return [];
        }

        $rewards = MinigameReward::getUncollectedAtLocation($userId, $locationType, $locationId);

        return $rewards->map(function ($reward) {
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
        })->values()->toArray();
    }

    /**
     * Get the location type from the model.
     */
    protected function getLocationType(mixed $location): ?string
    {
        return match (true) {
            $location instanceof Village => 'village',
            $location instanceof Town => 'town',
            $location instanceof Barony => 'barony',
            $location instanceof Duchy => 'duchy',
            $location instanceof Kingdom => 'kingdom',
            default => null,
        };
    }
}

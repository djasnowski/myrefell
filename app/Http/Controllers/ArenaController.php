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

        // Check if user has played archery today and get where they played
        $todaysPlay = MinigameScore::where('user_id', $user->id)
            ->where('minigame', 'archery')
            ->whereDate('played_at', today())
            ->first();

        $hasPlayedToday = $todaysPlay !== null;
        $playedAtDifferentLocation = false;
        $playedAtLocation = null;

        if ($todaysPlay) {
            // Check if they played at a different location
            $playedAtDifferentLocation = $todaysPlay->location_type !== $locationType
                || $todaysPlay->location_id !== $location?->id;

            if ($playedAtDifferentLocation) {
                $playedAtLocation = $this->getLocationName($todaysPlay->location_type, $todaysPlay->location_id);
            }
        }

        // Get GLOBAL leaderboards for archery
        $leaderboards = $this->getGlobalLeaderboards('archery');

        // Get user's GLOBAL ranks in each leaderboard
        $userRanks = $this->getGlobalUserRanks($user->id, 'archery');

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
            'played_at_different_location' => $playedAtDifferentLocation,
            'played_at_location' => $playedAtLocation,
            'leaderboards' => $leaderboards,
            'user_ranks' => $userRanks,
            'pending_rewards' => $pendingRewards,
        ]);
    }

    /**
     * Get GLOBAL leaderboards for a minigame (across all locations).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function getGlobalLeaderboards(string $minigame): array
    {
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
            'daily' => $formatLeaderboard(MinigameScore::getGlobalLeaderboard($minigame, 'daily')),
            'weekly' => $formatLeaderboard(MinigameScore::getGlobalLeaderboard($minigame, 'weekly')),
            'monthly' => $formatLeaderboard(MinigameScore::getGlobalLeaderboard($minigame, 'monthly')),
        ];
    }

    /**
     * Get user's GLOBAL ranks in each leaderboard period.
     *
     * @return array<string, int|null>
     */
    protected function getGlobalUserRanks(int $userId, string $minigame): array
    {
        return [
            'daily' => MinigameScore::getUserGlobalRank($userId, $minigame, 'daily'),
            'weekly' => MinigameScore::getUserGlobalRank($userId, $minigame, 'weekly'),
            'monthly' => MinigameScore::getUserGlobalRank($userId, $minigame, 'monthly'),
        ];
    }

    /**
     * Get location name from type and ID.
     */
    protected function getLocationName(string $locationType, int $locationId): ?string
    {
        $model = match ($locationType) {
            'village' => Village::class,
            'town' => Town::class,
            'barony' => Barony::class,
            'duchy' => Duchy::class,
            'kingdom' => Kingdom::class,
            default => null,
        };

        if (! $model) {
            return null;
        }

        return $model::find($locationId)?->name;
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

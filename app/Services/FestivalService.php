<?php

namespace App\Services;

use App\Models\Festival;
use App\Models\FestivalParticipant;
use App\Models\FestivalType;
use App\Models\Tournament;
use App\Models\TournamentCompetitor;
use App\Models\TournamentMatch;
use App\Models\TournamentType;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FestivalService
{
    /**
     * Schedule a seasonal festival.
     */
    public function scheduleFestival(
        FestivalType $type,
        string $locationType,
        int $locationId,
        \DateTimeInterface $startsAt,
        ?User $organizer = null,
        ?string $customName = null
    ): Festival {
        $endsAt = (clone $startsAt)->modify("+{$type->duration_days} days");

        return Festival::create([
            'festival_type_id' => $type->id,
            'location_type' => $locationType,
            'location_id' => $locationId,
            'name' => $customName ?? $type->name,
            'status' => Festival::STATUS_SCHEDULED,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'organized_by_user_id' => $organizer?->id,
        ]);
    }

    /**
     * Start a festival (change status to active).
     */
    public function startFestival(Festival $festival): Festival
    {
        $festival->update(['status' => Festival::STATUS_ACTIVE]);
        return $festival;
    }

    /**
     * End a festival.
     */
    public function endFestival(Festival $festival): Festival
    {
        $festival->update([
            'status' => Festival::STATUS_COMPLETED,
            'attendance_count' => $festival->participants()->count(),
        ]);
        return $festival;
    }

    /**
     * Join a festival as a participant.
     */
    public function joinFestival(Festival $festival, User $user, string $role = 'attendee'): array
    {
        if (!$festival->isActive()) {
            return ['success' => false, 'message' => 'Festival is not active.'];
        }

        $existing = FestivalParticipant::where('festival_id', $festival->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return ['success' => false, 'message' => 'Already participating in this festival.'];
        }

        $participant = FestivalParticipant::create([
            'festival_id' => $festival->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return ['success' => true, 'participant' => $participant];
    }

    /**
     * Create a tournament (standalone or part of festival).
     */
    public function createTournament(
        TournamentType $type,
        string $locationType,
        int $locationId,
        string $name,
        \DateTimeInterface $registrationEndsAt,
        \DateTimeInterface $startsAt,
        ?Festival $festival = null,
        ?User $sponsor = null,
        int $sponsorContribution = 0
    ): Tournament {
        return Tournament::create([
            'tournament_type_id' => $type->id,
            'festival_id' => $festival?->id,
            'location_type' => $locationType,
            'location_id' => $locationId,
            'name' => $name,
            'status' => Tournament::STATUS_REGISTRATION,
            'registration_ends_at' => $registrationEndsAt,
            'starts_at' => $startsAt,
            'prize_pool' => $sponsorContribution,
            'sponsored_by_user_id' => $sponsor?->id,
            'sponsor_contribution' => $sponsorContribution,
        ]);
    }

    /**
     * Register for a tournament.
     */
    public function registerForTournament(Tournament $tournament, User $user): array
    {
        if (!$tournament->isRegistrationOpen()) {
            return ['success' => false, 'message' => 'Registration is closed.'];
        }

        $type = $tournament->tournamentType;

        // Check minimum level
        $combatLevel = $user->combat_level ?? 1;
        if ($combatLevel < $type->min_level) {
            return ['success' => false, 'message' => "Minimum combat level {$type->min_level} required."];
        }

        // Check max participants
        if ($tournament->competitor_count >= $type->max_participants) {
            return ['success' => false, 'message' => 'Tournament is full.'];
        }

        // Check entry fee
        if ($user->gold < $type->entry_fee) {
            return ['success' => false, 'message' => "Entry fee of {$type->entry_fee} gold required."];
        }

        // Check not already registered
        $existing = TournamentCompetitor::where('tournament_id', $tournament->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return ['success' => false, 'message' => 'Already registered for this tournament.'];
        }

        return DB::transaction(function () use ($tournament, $user, $type) {
            // Pay entry fee
            $user->decrement('gold', $type->entry_fee);
            $tournament->increment('prize_pool', $type->entry_fee);

            $competitor = TournamentCompetitor::create([
                'tournament_id' => $tournament->id,
                'user_id' => $user->id,
                'status' => TournamentCompetitor::STATUS_REGISTERED,
            ]);

            return ['success' => true, 'competitor' => $competitor];
        });
    }

    /**
     * Start a tournament and generate bracket.
     */
    public function startTournament(Tournament $tournament): array
    {
        if ($tournament->status !== Tournament::STATUS_REGISTRATION) {
            return ['success' => false, 'message' => 'Tournament not in registration phase.'];
        }

        $competitors = $tournament->competitors()->get();
        $count = $competitors->count();

        if ($count < 2) {
            return ['success' => false, 'message' => 'Need at least 2 competitors.'];
        }

        return DB::transaction(function () use ($tournament, $competitors, $count) {
            // Calculate rounds needed (log2, rounded up)
            $totalRounds = (int) ceil(log($count, 2));

            // Shuffle and seed competitors
            $shuffled = $competitors->shuffle()->values();
            foreach ($shuffled as $index => $competitor) {
                $competitor->update([
                    'seed' => $index + 1,
                    'status' => TournamentCompetitor::STATUS_ACTIVE,
                ]);
            }

            // Generate first round matches
            $this->generateRoundMatches($tournament, 1, $shuffled);

            $tournament->update([
                'status' => Tournament::STATUS_IN_PROGRESS,
                'current_round' => 1,
                'total_rounds' => $totalRounds,
            ]);

            return ['success' => true, 'total_rounds' => $totalRounds];
        });
    }

    /**
     * Generate matches for a round.
     */
    protected function generateRoundMatches(Tournament $tournament, int $round, Collection $competitors): void
    {
        $pairs = $competitors->chunk(2);
        $matchNumber = 1;

        foreach ($pairs as $pair) {
            $pair = $pair->values();
            TournamentMatch::create([
                'tournament_id' => $tournament->id,
                'round_number' => $round,
                'match_number' => $matchNumber++,
                'competitor1_id' => $pair[0]->id,
                'competitor2_id' => $pair[1]->id ?? null, // null = bye
                'status' => TournamentMatch::STATUS_PENDING,
            ]);
        }
    }

    /**
     * Resolve a tournament match.
     */
    public function resolveMatch(TournamentMatch $match): array
    {
        if ($match->status !== TournamentMatch::STATUS_PENDING) {
            return ['success' => false, 'message' => 'Match not pending.'];
        }

        // Handle bye
        if ($match->isBye()) {
            $match->update([
                'winner_id' => $match->competitor1_id,
                'status' => TournamentMatch::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
            $match->competitor1->increment('wins');
            return ['success' => true, 'winner' => $match->competitor1, 'bye' => true];
        }

        return DB::transaction(function () use ($match) {
            $c1 = $match->competitor1;
            $c2 = $match->competitor2;
            $type = $match->tournament->tournamentType;

            // Get combat stats
            $c1Stats = $this->getCompetitorStats($c1->user, $type);
            $c2Stats = $this->getCompetitorStats($c2->user, $type);

            // Simple combat resolution
            $c1Score = 0;
            $c2Score = 0;
            $combatLog = [];

            for ($round = 1; $round <= 3; $round++) {
                $c1Roll = rand(1, 100) + $c1Stats;
                $c2Roll = rand(1, 100) + $c2Stats;

                if ($c1Roll > $c2Roll) {
                    $c1Score++;
                    $combatLog[] = "Round {$round}: {$c1->user->username} wins";
                } else {
                    $c2Score++;
                    $combatLog[] = "Round {$round}: {$c2->user->username} wins";
                }
            }

            $winner = $c1Score > $c2Score ? $c1 : $c2;
            $loser = $c1Score > $c2Score ? $c2 : $c1;

            $match->update([
                'winner_id' => $winner->id,
                'status' => TournamentMatch::STATUS_COMPLETED,
                'competitor1_score' => $c1Score,
                'competitor2_score' => $c2Score,
                'combat_log' => $combatLog,
                'started_at' => now(),
                'completed_at' => now(),
            ]);

            $winner->increment('wins');
            $loser->increment('losses');
            $loser->update(['status' => TournamentCompetitor::STATUS_ELIMINATED]);

            return [
                'success' => true,
                'winner' => $winner,
                'loser' => $loser,
                'score' => "{$c1Score}-{$c2Score}",
            ];
        });
    }

    /**
     * Get competitor stats for combat.
     */
    protected function getCompetitorStats(User $user, TournamentType $type): int
    {
        return match ($type->primary_stat) {
            'attack' => $user->attack_level ?? 1,
            'strength' => $user->strength_level ?? 1,
            'defense' => $user->defense_level ?? 1,
            'combat_level' => $user->combat_level ?? 1,
            default => 1,
        };
    }

    /**
     * Advance tournament to next round or complete it.
     */
    public function advanceTournament(Tournament $tournament): array
    {
        $currentRoundMatches = $tournament->matches()
            ->where('round_number', $tournament->current_round)
            ->get();

        // Check all matches completed
        if ($currentRoundMatches->where('status', '!=', TournamentMatch::STATUS_COMPLETED)->count() > 0) {
            return ['success' => false, 'message' => 'Not all matches completed.'];
        }

        // Get winners
        $winners = $currentRoundMatches->map(fn($m) => $m->winner)->filter();

        if ($winners->count() === 1) {
            // Tournament complete
            return $this->completeTournament($tournament, $winners->first());
        }

        // Generate next round
        $nextRound = $tournament->current_round + 1;
        $this->generateRoundMatches($tournament, $nextRound, $winners);
        $tournament->update(['current_round' => $nextRound]);

        return ['success' => true, 'next_round' => $nextRound, 'competitors_remaining' => $winners->count()];
    }

    /**
     * Complete a tournament and distribute prizes.
     */
    protected function completeTournament(Tournament $tournament, TournamentCompetitor $winner): array
    {
        return DB::transaction(function () use ($tournament, $winner) {
            $winner->update([
                'status' => TournamentCompetitor::STATUS_WINNER,
                'final_placement' => 1,
            ]);

            // Distribute prize pool
            $distribution = $tournament->tournamentType->prize_distribution ?? ['1st' => 100];
            $prizePool = $tournament->prize_pool;

            $firstPrize = (int) ($prizePool * ($distribution['1st'] ?? 100) / 100);
            $winner->update(['prize_won' => $firstPrize]);
            $winner->user->increment('gold', $firstPrize);

            $tournament->update([
                'status' => Tournament::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            return [
                'success' => true,
                'winner' => $winner,
                'prize' => $firstPrize,
            ];
        });
    }

    /**
     * Get upcoming festivals.
     */
    public function getUpcomingFestivals(string $locationType = null, int $locationId = null): Collection
    {
        $query = Festival::with('festivalType')
            ->where('status', Festival::STATUS_SCHEDULED)
            ->where('starts_at', '>', now())
            ->orderBy('starts_at');

        if ($locationType && $locationId) {
            $query->where('location_type', $locationType)
                ->where('location_id', $locationId);
        }

        return $query->get();
    }

    /**
     * Schedule seasonal festivals for the world.
     */
    public function scheduleSeasonalFestivals(string $season, int $year): array
    {
        $festivalTypes = FestivalType::where('category', 'seasonal')
            ->where('season', $season)
            ->get();

        $scheduled = [];

        // For each village/town, schedule appropriate festivals
        // This would be called by the calendar tick job

        return $scheduled;
    }
}

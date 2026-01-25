<?php

namespace App\Http\Controllers;

use App\Models\Festival;
use App\Models\FestivalParticipant;
use App\Models\RoyalEvent;
use App\Models\Tournament;
use App\Models\TournamentCompetitor;
use App\Models\TournamentMatch;
use App\Services\CalendarService;
use App\Services\FestivalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function __construct(
        protected FestivalService $festivalService,
        protected CalendarService $calendarService
    ) {}

    /**
     * Display the events calendar page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $calendar = $this->calendarService->getCalendarData();

        // Active festivals (currently happening)
        $activeFestivals = Festival::with(['festivalType', 'organizer', 'participants'])
            ->where('status', Festival::STATUS_ACTIVE)
            ->orderBy('ends_at')
            ->get()
            ->map(fn($f) => $this->formatFestival($f, $user));

        // Upcoming festivals (scheduled for the future)
        $upcomingFestivals = Festival::with(['festivalType', 'organizer', 'participants'])
            ->where('status', Festival::STATUS_SCHEDULED)
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->limit(10)
            ->get()
            ->map(fn($f) => $this->formatFestival($f, $user));

        // Registration open tournaments
        $registrationOpenTournaments = Tournament::with(['tournamentType', 'competitors', 'festival'])
            ->where('status', Tournament::STATUS_REGISTRATION)
            ->where('registration_ends_at', '>', now())
            ->orderBy('registration_ends_at')
            ->get()
            ->map(fn($t) => $this->formatTournament($t, $user));

        // In progress tournaments
        $inProgressTournaments = Tournament::with(['tournamentType', 'competitors', 'festival'])
            ->where('status', Tournament::STATUS_IN_PROGRESS)
            ->orderBy('starts_at')
            ->get()
            ->map(fn($t) => $this->formatTournament($t, $user));

        // Upcoming royal events
        $upcomingRoyalEvents = RoyalEvent::with(['primaryParticipant', 'secondaryParticipant'])
            ->whereIn('status', [RoyalEvent::STATUS_SCHEDULED, RoyalEvent::STATUS_ACTIVE])
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get()
            ->map(fn($e) => $this->formatRoyalEvent($e));

        return Inertia::render('Events/Index', [
            'active_festivals' => $activeFestivals,
            'upcoming_festivals' => $upcomingFestivals,
            'registration_open_tournaments' => $registrationOpenTournaments,
            'in_progress_tournaments' => $inProgressTournaments,
            'upcoming_royal_events' => $upcomingRoyalEvents,
            'calendar' => $calendar,
            'user_gold' => $user->gold,
            'user_combat_level' => $user->combat_level ?? 1,
        ]);
    }

    /**
     * Show festival detail page.
     */
    public function showFestival(Request $request, Festival $festival): Response
    {
        $user = $request->user();

        // Load all relationships
        $festival->load(['festivalType', 'organizer', 'participants.user', 'tournaments.tournamentType', 'tournaments.competitors']);

        // Get participant info for current user
        $userParticipation = $festival->participants()
            ->where('user_id', $user->id)
            ->first();

        // Format participants with details
        $participants = $festival->participants->map(fn($p) => [
            'id' => $p->id,
            'user_id' => $p->user_id,
            'username' => $p->user?->username ?? 'Unknown',
            'role' => $p->role,
            'gold_spent' => $p->gold_spent ?? 0,
            'gold_earned' => $p->gold_earned ?? 0,
            'activities_completed' => $p->activities_completed ?? [],
            'joined_at' => $p->created_at?->toISOString(),
        ]);

        // Format tournaments
        $tournaments = $festival->tournaments->map(fn($t) => $this->formatTournament($t, $user));

        // Calculate festival duration in days
        $durationDays = $festival->starts_at && $festival->ends_at
            ? $festival->starts_at->diffInDays($festival->ends_at) + 1
            : $festival->festivalType->duration_days ?? 1;

        // Calculate current day of festival if active
        $currentDay = null;
        if ($festival->status === Festival::STATUS_ACTIVE && $festival->starts_at) {
            $currentDay = now()->diffInDays($festival->starts_at) + 1;
        }

        return Inertia::render('Events/FestivalShow', [
            'festival' => [
                'id' => $festival->id,
                'name' => $festival->name,
                'type' => [
                    'id' => $festival->festivalType->id,
                    'name' => $festival->festivalType->name,
                    'category' => $festival->festivalType->category,
                    'description' => $festival->festivalType->description,
                    'activities' => $festival->festivalType->activities ?? [],
                    'bonuses' => $festival->festivalType->bonuses ?? [],
                    'season' => $festival->festivalType->season,
                ],
                'status' => $festival->status,
                'location_type' => $festival->location_type,
                'location_id' => $festival->location_id,
                'location_name' => $festival->location?->name ?? 'Unknown',
                'starts_at' => $festival->starts_at?->toISOString(),
                'ends_at' => $festival->ends_at?->toISOString(),
                'starts_at_formatted' => $festival->starts_at?->format('M j, Y'),
                'ends_at_formatted' => $festival->ends_at?->format('M j, Y'),
                'organizer_id' => $festival->organized_by_user_id,
                'organizer_name' => $festival->organizer?->username,
                'budget' => $festival->budget ?? 0,
                'attendance_count' => $festival->attendance_count ?? $festival->participants->count(),
                'results' => $festival->results ?? [],
                'duration_days' => $durationDays,
                'current_day' => $currentDay,
            ],
            'participants' => $participants->toArray(),
            'tournaments' => $tournaments->toArray(),
            'is_participating' => $userParticipation !== null,
            'user_participation' => $userParticipation ? [
                'role' => $userParticipation->role,
                'gold_spent' => $userParticipation->gold_spent ?? 0,
                'gold_earned' => $userParticipation->gold_earned ?? 0,
                'activities_completed' => $userParticipation->activities_completed ?? [],
            ] : null,
            'user_gold' => $user->gold,
            'user_combat_level' => $user->combat_level ?? 1,
        ]);
    }

    /**
     * Join a festival.
     */
    public function joinFestival(Request $request, Festival $festival): JsonResponse
    {
        $request->validate([
            'role' => 'sometimes|string|in:attendee,performer,vendor',
        ]);

        $result = $this->festivalService->joinFestival(
            $festival,
            $request->user(),
            $request->input('role', 'attendee')
        );

        return response()->json($result);
    }

    /**
     * Leave a festival.
     */
    public function leaveFestival(Request $request, Festival $festival): JsonResponse
    {
        $user = $request->user();

        // Check if user is participating
        $participant = FestivalParticipant::where('festival_id', $festival->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'You are not participating in this festival.',
            ]);
        }

        // Cannot leave if festival is completed
        if ($festival->status === Festival::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot leave a completed festival.',
            ]);
        }

        // Delete participation
        $participant->delete();

        return response()->json([
            'success' => true,
            'message' => 'You have left the festival.',
        ]);
    }

    /**
     * Show tournament detail page with bracket.
     */
    public function showTournament(Request $request, Tournament $tournament): Response
    {
        $user = $request->user();

        // Load all relationships
        $tournament->load([
            'tournamentType',
            'festival',
            'sponsor',
            'competitors.user',
            'matches.competitor1.user',
            'matches.competitor2.user',
            'matches.winner.user',
        ]);

        // Get user's competitor info if registered
        $userCompetitor = $tournament->competitors()
            ->where('user_id', $user->id)
            ->first();

        // Format competitors with details
        $competitors = $tournament->competitors->map(fn($c) => [
            'id' => $c->id,
            'user_id' => $c->user_id,
            'username' => $c->user?->username ?? 'Unknown',
            'seed' => $c->seed,
            'status' => $c->status,
            'wins' => $c->wins ?? 0,
            'losses' => $c->losses ?? 0,
            'final_placement' => $c->final_placement,
            'prize_won' => $c->prize_won ?? 0,
            'fame_earned' => $c->fame_earned ?? 0,
        ])->sortBy('seed')->values();

        // Format matches grouped by round
        $matchesByRound = $tournament->matches
            ->groupBy('round_number')
            ->map(fn($roundMatches) => $roundMatches->sortBy('match_number')->values()->map(fn($m) => [
                'id' => $m->id,
                'round_number' => $m->round_number,
                'match_number' => $m->match_number,
                'competitor1' => $m->competitor1 ? [
                    'id' => $m->competitor1->id,
                    'username' => $m->competitor1->user?->username ?? 'Unknown',
                    'seed' => $m->competitor1->seed,
                ] : null,
                'competitor2' => $m->competitor2 ? [
                    'id' => $m->competitor2->id,
                    'username' => $m->competitor2->user?->username ?? 'Unknown',
                    'seed' => $m->competitor2->seed,
                ] : null,
                'winner_id' => $m->winner_id,
                'winner_username' => $m->winner?->user?->username,
                'status' => $m->status,
                'competitor1_score' => $m->competitor1_score ?? 0,
                'competitor2_score' => $m->competitor2_score ?? 0,
                'is_bye' => $m->isBye(),
                'combat_log' => $m->combat_log ?? [],
            ]));

        // Get user's next match if in tournament and active
        $userNextMatch = null;
        if ($userCompetitor && in_array($userCompetitor->status, ['registered', 'active'])) {
            $userNextMatch = $tournament->matches()
                ->where('status', '!=', TournamentMatch::STATUS_COMPLETED)
                ->where(function ($q) use ($userCompetitor) {
                    $q->where('competitor1_id', $userCompetitor->id)
                        ->orWhere('competitor2_id', $userCompetitor->id);
                })
                ->orderBy('round_number')
                ->first();

            if ($userNextMatch) {
                $userNextMatch->load(['competitor1.user', 'competitor2.user']);
                $opponent = $userNextMatch->competitor1_id === $userCompetitor->id
                    ? $userNextMatch->competitor2
                    : $userNextMatch->competitor1;

                $userNextMatch = [
                    'id' => $userNextMatch->id,
                    'round_number' => $userNextMatch->round_number,
                    'opponent_username' => $opponent?->user?->username ?? 'BYE',
                    'status' => $userNextMatch->status,
                ];
            }
        }

        // Find the winner if tournament is completed
        $winner = null;
        if ($tournament->status === Tournament::STATUS_COMPLETED) {
            $winnerCompetitor = $tournament->competitors()
                ->where('status', TournamentCompetitor::STATUS_WINNER)
                ->with('user')
                ->first();
            if ($winnerCompetitor) {
                $winner = [
                    'id' => $winnerCompetitor->id,
                    'username' => $winnerCompetitor->user?->username ?? 'Unknown',
                    'prize_won' => $winnerCompetitor->prize_won ?? 0,
                ];
            }
        }

        return Inertia::render('Events/TournamentShow', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'type' => [
                    'id' => $tournament->tournamentType->id,
                    'name' => $tournament->tournamentType->name,
                    'combat_type' => $tournament->tournamentType->combat_type,
                    'description' => $tournament->tournamentType->description,
                    'entry_fee' => $tournament->tournamentType->entry_fee,
                    'min_level' => $tournament->tournamentType->min_level,
                    'max_participants' => $tournament->tournamentType->max_participants,
                    'is_lethal' => $tournament->tournamentType->is_lethal,
                ],
                'status' => $tournament->status,
                'location_type' => $tournament->location_type,
                'location_id' => $tournament->location_id,
                'location_name' => $tournament->location?->name ?? 'Unknown',
                'registration_ends_at' => $tournament->registration_ends_at?->toISOString(),
                'registration_ends_formatted' => $tournament->registration_ends_at?->format('M j, Y'),
                'starts_at' => $tournament->starts_at?->toISOString(),
                'starts_at_formatted' => $tournament->starts_at?->format('M j, Y'),
                'completed_at' => $tournament->completed_at?->toISOString(),
                'completed_at_formatted' => $tournament->completed_at?->format('M j, Y'),
                'prize_pool' => $tournament->prize_pool,
                'current_round' => $tournament->current_round,
                'total_rounds' => $tournament->total_rounds,
                'sponsor_name' => $tournament->sponsor?->username,
                'sponsor_contribution' => $tournament->sponsor_contribution ?? 0,
                'festival_id' => $tournament->festival_id,
                'festival_name' => $tournament->festival?->name,
                'is_registration_open' => $tournament->isRegistrationOpen(),
            ],
            'competitors' => $competitors->toArray(),
            'matches_by_round' => $matchesByRound->toArray(),
            'is_registered' => $userCompetitor !== null,
            'user_competitor' => $userCompetitor ? [
                'id' => $userCompetitor->id,
                'seed' => $userCompetitor->seed,
                'status' => $userCompetitor->status,
                'wins' => $userCompetitor->wins ?? 0,
                'losses' => $userCompetitor->losses ?? 0,
                'prize_won' => $userCompetitor->prize_won ?? 0,
            ] : null,
            'user_next_match' => $userNextMatch,
            'winner' => $winner,
            'user_gold' => $user->gold,
            'user_combat_level' => $user->combat_level ?? 1,
        ]);
    }

    /**
     * Register for a tournament.
     */
    public function registerForTournament(Request $request, Tournament $tournament): JsonResponse
    {
        $result = $this->festivalService->registerForTournament(
            $tournament,
            $request->user()
        );

        return response()->json($result);
    }

    /**
     * Withdraw from a tournament.
     */
    public function withdrawFromTournament(Request $request, Tournament $tournament): JsonResponse
    {
        $user = $request->user();

        // Find user's competitor entry
        $competitor = TournamentCompetitor::where('tournament_id', $tournament->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$competitor) {
            return response()->json([
                'success' => false,
                'message' => 'You are not registered for this tournament.',
            ]);
        }

        // Can only withdraw during registration phase
        if ($tournament->status !== Tournament::STATUS_REGISTRATION) {
            return response()->json([
                'success' => false,
                'message' => 'You can only withdraw during the registration period.',
            ]);
        }

        // Update status to withdrew
        $competitor->update(['status' => TournamentCompetitor::STATUS_WITHDREW]);

        return response()->json([
            'success' => true,
            'message' => 'You have withdrawn from the tournament.',
        ]);
    }

    /**
     * Format festival for frontend.
     */
    protected function formatFestival(Festival $festival, $user): array
    {
        $location = $festival->location;
        $isParticipating = $festival->participants()
            ->where('user_id', $user->id)
            ->exists();

        return [
            'id' => $festival->id,
            'name' => $festival->name,
            'type' => [
                'id' => $festival->festivalType->id,
                'name' => $festival->festivalType->name,
                'category' => $festival->festivalType->category,
                'description' => $festival->festivalType->description,
                'activities' => $festival->festivalType->activities ?? [],
                'bonuses' => $festival->festivalType->bonuses ?? [],
            ],
            'status' => $festival->status,
            'location_type' => $festival->location_type,
            'location_id' => $festival->location_id,
            'location_name' => $location?->name ?? 'Unknown',
            'starts_at' => $festival->starts_at?->toISOString(),
            'ends_at' => $festival->ends_at?->toISOString(),
            'starts_at_formatted' => $festival->starts_at?->format('M j, Y'),
            'ends_at_formatted' => $festival->ends_at?->format('M j, Y'),
            'organizer_name' => $festival->organizer?->username,
            'participant_count' => $festival->participants->count(),
            'is_participating' => $isParticipating,
            'has_tournaments' => $festival->tournaments()->count() > 0,
        ];
    }

    /**
     * Format tournament for frontend.
     */
    protected function formatTournament(Tournament $tournament, $user): array
    {
        $location = $tournament->location;
        $type = $tournament->tournamentType;
        $isRegistered = $tournament->competitors()
            ->where('user_id', $user->id)
            ->exists();

        $userCompetitor = $tournament->competitors()
            ->where('user_id', $user->id)
            ->first();

        return [
            'id' => $tournament->id,
            'name' => $tournament->name,
            'type' => [
                'id' => $type->id,
                'name' => $type->name,
                'combat_type' => $type->combat_type,
                'description' => $type->description,
                'entry_fee' => $type->entry_fee,
                'min_level' => $type->min_level,
                'max_participants' => $type->max_participants,
                'is_lethal' => $type->is_lethal,
            ],
            'status' => $tournament->status,
            'location_type' => $tournament->location_type,
            'location_id' => $tournament->location_id,
            'location_name' => $location?->name ?? 'Unknown',
            'registration_ends_at' => $tournament->registration_ends_at?->toISOString(),
            'registration_ends_formatted' => $tournament->registration_ends_at?->format('M j, Y'),
            'starts_at' => $tournament->starts_at?->toISOString(),
            'starts_at_formatted' => $tournament->starts_at?->format('M j, Y'),
            'prize_pool' => $tournament->prize_pool,
            'competitor_count' => $tournament->competitors->count(),
            'current_round' => $tournament->current_round,
            'total_rounds' => $tournament->total_rounds,
            'is_registered' => $isRegistered,
            'user_status' => $userCompetitor?->status,
            'festival_id' => $tournament->festival_id,
            'festival_name' => $tournament->festival?->name,
            'is_registration_open' => $tournament->isRegistrationOpen(),
        ];
    }

    /**
     * Format royal event for frontend.
     */
    protected function formatRoyalEvent(RoyalEvent $event): array
    {
        $location = $event->location;

        return [
            'id' => $event->id,
            'event_type' => $event->event_type,
            'event_type_name' => $event->event_type_name,
            'title' => $event->title,
            'description' => $event->description,
            'status' => $event->status,
            'location_type' => $event->location_type,
            'location_id' => $event->location_id,
            'location_name' => $location?->name ?? 'Unknown',
            'scheduled_at' => $event->scheduled_at?->toISOString(),
            'scheduled_at_formatted' => $event->scheduled_at?->format('M j, Y'),
            'primary_participant_name' => $event->primaryParticipant?->username,
            'secondary_participant_name' => $event->secondaryParticipant?->username,
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Festival;
use App\Models\FestivalParticipant;
use App\Models\RoyalEvent;
use App\Models\Tournament;
use App\Models\TournamentCompetitor;
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

<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\Kingdom;
use App\Models\Town;
use App\Models\Village;
use App\Services\ElectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ElectionController extends Controller
{
    public function __construct(
        protected ElectionService $electionService
    ) {}

    /**
     * List all elections.
     */
    public function index(): Response
    {
        $elections = Election::with(['domain', 'winner', 'initiatedBy'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('Elections/Index', [
            'elections' => $elections->through(fn ($election) => [
                'id' => $election->id,
                'election_type' => $election->election_type,
                'role' => $election->role,
                'domain_type' => $election->domain_type,
                'domain_name' => $election->domain?->name,
                'status' => $election->status,
                'voting_starts_at' => $election->voting_starts_at?->toIso8601String(),
                'voting_ends_at' => $election->voting_ends_at?->toIso8601String(),
                'votes_cast' => $election->votes_cast,
                'quorum_required' => $election->quorum_required,
                'quorum_met' => $election->quorum_met,
                'winner' => $election->winner ? [
                    'id' => $election->winner->id,
                    'username' => $election->winner->username,
                ] : null,
                'is_self_appointment' => $election->is_self_appointment,
            ]),
        ]);
    }

    /**
     * Show election details.
     */
    public function show(Election $election): Response
    {
        $election->load(['domain', 'candidates.user', 'winner', 'initiatedBy']);

        $user = auth()->user();
        $hasVoted = $election->votes()->where('voter_user_id', $user->id)->exists();
        $isEligible = $this->electionService->validateVoterEligibility($election, $user);

        return Inertia::render('Elections/Show', [
            'election' => [
                'id' => $election->id,
                'election_type' => $election->election_type,
                'role' => $election->role,
                'domain_type' => $election->domain_type,
                'domain_id' => $election->domain_id,
                'domain_name' => $election->domain?->name,
                'status' => $election->status,
                'voting_starts_at' => $election->voting_starts_at?->toIso8601String(),
                'voting_ends_at' => $election->voting_ends_at?->toIso8601String(),
                'finalized_at' => $election->finalized_at?->toIso8601String(),
                'votes_cast' => $election->votes_cast,
                'quorum_required' => $election->quorum_required,
                'quorum_met' => $election->quorum_met,
                'eligible_voters' => $this->electionService->getEligibleVoterCount($election),
                'is_open' => $election->isOpen(),
                'has_ended' => $election->hasEnded(),
                'winner' => $election->winner ? [
                    'id' => $election->winner->id,
                    'username' => $election->winner->username,
                ] : null,
                'is_self_appointment' => $election->is_self_appointment,
                'notes' => $election->notes,
                'initiated_by' => $election->initiatedBy ? [
                    'id' => $election->initiatedBy->id,
                    'username' => $election->initiatedBy->username,
                ] : null,
            ],
            'candidates' => $election->candidates->map(fn ($candidate) => [
                'id' => $candidate->id,
                'user_id' => $candidate->user_id,
                'username' => $candidate->user->username,
                'platform' => $candidate->platform,
                'vote_count' => $candidate->vote_count,
                'is_active' => $candidate->is_active,
                'declared_at' => $candidate->declared_at?->toIso8601String(),
            ]),
            'user_state' => [
                'has_voted' => $hasVoted,
                'is_eligible' => $isEligible,
                'can_vote' => $election->canVote($user) && $isEligible,
                'is_candidate' => $election->candidates->contains('user_id', $user->id),
            ],
        ]);
    }

    /**
     * Get election status (for polling).
     */
    public function status(Election $election): JsonResponse
    {
        return response()->json([
            'status' => $election->status,
            'votes_cast' => $election->votes_cast,
            'quorum_met' => $election->quorum_met,
            'is_open' => $election->isOpen(),
            'has_ended' => $election->hasEnded(),
            'winner_user_id' => $election->winner_user_id,
        ]);
    }

    /**
     * Declare candidacy for an election.
     */
    public function declareCandidacy(Request $request, Election $election): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'platform' => 'nullable|string|max:1000',
        ]);

        try {
            $candidate = $this->electionService->declareCandidacy(
                $election,
                $request->user(),
                $request->input('platform')
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'candidate' => [
                        'id' => $candidate->id,
                        'user_id' => $candidate->user_id,
                        'platform' => $candidate->platform,
                        'declared_at' => $candidate->declared_at->toIso8601String(),
                    ],
                ]);
            }

            return back()->with('success', 'You have declared your candidacy.');
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 422);
            }
            return back()->withErrors(['candidacy' => $e->getMessage()]);
        }
    }

    /**
     * Withdraw candidacy from an election.
     */
    public function withdrawCandidacy(Request $request, Election $election): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();

        $candidate = ElectionCandidate::where('election_id', $election->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (! $candidate) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'You are not an active candidate in this election.',
                ], 422);
            }
            return back()->withErrors(['candidacy' => 'You are not an active candidate in this election.']);
        }

        if (! $election->isOpen()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cannot withdraw from a closed election.',
                ], 422);
            }
            return back()->withErrors(['candidacy' => 'Cannot withdraw from a closed election.']);
        }

        $candidate->withdraw();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
            ]);
        }

        return back()->with('success', 'You have withdrawn your candidacy.');
    }

    /**
     * Cast a vote for a candidate.
     */
    public function vote(Request $request, Election $election): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'candidate_id' => 'required|integer|exists:election_candidates,id',
        ]);

        $candidate = ElectionCandidate::findOrFail($request->input('candidate_id'));

        try {
            $vote = $this->electionService->castVote(
                $election,
                $request->user(),
                $candidate
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'vote' => [
                        'id' => $vote->id,
                        'voted_at' => $vote->voted_at->toIso8601String(),
                    ],
                    'election' => [
                        'votes_cast' => $election->fresh()->votes_cast,
                        'quorum_met' => $election->fresh()->quorum_met,
                    ],
                ]);
            }

            return back()->with('success', 'Your vote has been cast.');
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 422);
            }
            return back()->withErrors(['vote' => $e->getMessage()]);
        }
    }

    /**
     * Start a village role election.
     */
    public function startVillageElection(Request $request, Village $village): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'role' => 'required|string|in:' . implode(',', Election::VILLAGE_ROLES),
        ]);

        $user = $request->user();

        // Validate user is a resident
        if ($user->home_village_id !== $village->id) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'You must be a resident of this village to start an election.',
                ], 422);
            }
            return back()->withErrors(['election' => 'You must be a resident of this village to start an election.']);
        }

        try {
            $election = $this->electionService->startElection(
                'village_role',
                $request->input('role'),
                $village,
                $user
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'election' => [
                        'id' => $election->id,
                        'status' => $election->status,
                        'voting_ends_at' => $election->voting_ends_at->toIso8601String(),
                    ],
                ]);
            }

            return back()->with('success', 'Election started successfully.');
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 422);
            }
            return back()->withErrors(['election' => $e->getMessage()]);
        }
    }

    /**
     * Self-appoint to a village role (for small villages).
     */
    public function selfAppoint(Request $request, Village $village): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'role' => 'required|string|in:' . implode(',', Election::VILLAGE_ROLES),
        ]);

        $user = $request->user();

        try {
            $election = $this->electionService->selfAppoint(
                $user,
                $village,
                $request->input('role')
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'election' => [
                        'id' => $election->id,
                        'status' => $election->status,
                        'is_self_appointment' => true,
                    ],
                    'message' => "You have been appointed as {$request->input('role')} of {$village->name}.",
                ]);
            }

            return back()->with('success', "You have been appointed as {$request->input('role')} of {$village->name}.");
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 422);
            }
            return back()->withErrors(['appointment' => $e->getMessage()]);
        }
    }

    /**
     * Start a mayor election for a town.
     */
    public function startMayorElection(Request $request, Town $town): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        // Validate user lives in a village in this town
        if (! $this->electionService->validateVoterEligibility(
            new Election([
                'domain_type' => 'town',
                'domain_id' => $town->id,
                'election_type' => 'mayor',
            ]),
            $user
        )) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'You must be a resident of a village in this town to start an election.',
                ], 422);
            }
            return back()->withErrors(['election' => 'You must be a resident of a village in this town to start an election.']);
        }

        try {
            $election = $this->electionService->startElection(
                'mayor',
                null,
                $town,
                $user
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'election' => [
                        'id' => $election->id,
                        'status' => $election->status,
                        'voting_ends_at' => $election->voting_ends_at->toIso8601String(),
                    ],
                ]);
            }

            return back()->with('success', 'Election started successfully.');
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 422);
            }
            return back()->withErrors(['election' => $e->getMessage()]);
        }
    }

    /**
     * Start a king election for a kingdom.
     */
    public function startKingElection(Request $request, Kingdom $kingdom): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        // Validate user lives in a village in this kingdom
        if (! $this->electionService->validateVoterEligibility(
            new Election([
                'domain_type' => 'kingdom',
                'domain_id' => $kingdom->id,
                'election_type' => 'king',
            ]),
            $user
        )) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'You must be a resident of a village in this kingdom to start an election.',
                ], 422);
            }
            return back()->withErrors(['election' => 'You must be a resident of a village in this kingdom to start an election.']);
        }

        try {
            $election = $this->electionService->startElection(
                'king',
                null,
                $kingdom,
                $user
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'election' => [
                        'id' => $election->id,
                        'status' => $election->status,
                        'voting_ends_at' => $election->voting_ends_at->toIso8601String(),
                    ],
                ]);
            }

            return back()->with('success', 'Election started successfully.');
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 422);
            }
            return back()->withErrors(['election' => $e->getMessage()]);
        }
    }
}

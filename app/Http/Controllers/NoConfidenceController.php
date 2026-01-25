<?php

namespace App\Http\Controllers;

use App\Models\Kingdom;
use App\Models\NoConfidenceVote;
use App\Models\Town;
use App\Models\User;
use App\Models\Village;
use App\Services\ElectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NoConfidenceController extends Controller
{
    public function __construct(
        protected ElectionService $electionService
    ) {}

    /**
     * List all no confidence votes.
     */
    public function index(): Response
    {
        $votes = NoConfidenceVote::with(['domain', 'targetPlayer', 'initiatedBy'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('Elections/NoConfidence', [
            'votes' => $votes->through(fn ($vote) => [
                'id' => $vote->id,
                'target_role' => $vote->target_role,
                'target_player' => [
                    'id' => $vote->targetPlayer->id,
                    'username' => $vote->targetPlayer->username,
                ],
                'domain_type' => $vote->domain_type,
                'domain_name' => $vote->domain?->name,
                'status' => $vote->status,
                'voting_starts_at' => $vote->voting_starts_at?->toIso8601String(),
                'voting_ends_at' => $vote->voting_ends_at?->toIso8601String(),
                'votes_for' => $vote->votes_for,
                'votes_against' => $vote->votes_against,
                'votes_cast' => $vote->votes_cast,
                'quorum_required' => $vote->quorum_required,
                'quorum_met' => $vote->quorum_met,
                'reason' => $vote->reason,
                'initiated_by' => $vote->initiatedBy ? [
                    'id' => $vote->initiatedBy->id,
                    'username' => $vote->initiatedBy->username,
                ] : null,
            ]),
        ]);
    }

    /**
     * Show no confidence vote details.
     */
    public function show(NoConfidenceVote $noConfidenceVote): Response
    {
        $noConfidenceVote->load(['domain', 'targetPlayer', 'initiatedBy', 'ballots.voter']);

        $user = auth()->user();
        $hasVoted = $noConfidenceVote->hasVoted($user);
        $isEligible = $this->electionService->validateNoConfidenceEligibility($user, $noConfidenceVote->domain);

        return Inertia::render('Elections/NoConfidenceShow', [
            'vote' => [
                'id' => $noConfidenceVote->id,
                'target_role' => $noConfidenceVote->target_role,
                'target_player' => [
                    'id' => $noConfidenceVote->targetPlayer->id,
                    'username' => $noConfidenceVote->targetPlayer->username,
                ],
                'domain_type' => $noConfidenceVote->domain_type,
                'domain_id' => $noConfidenceVote->domain_id,
                'domain_name' => $noConfidenceVote->domain?->name,
                'status' => $noConfidenceVote->status,
                'voting_starts_at' => $noConfidenceVote->voting_starts_at?->toIso8601String(),
                'voting_ends_at' => $noConfidenceVote->voting_ends_at?->toIso8601String(),
                'finalized_at' => $noConfidenceVote->finalized_at?->toIso8601String(),
                'votes_for' => $noConfidenceVote->votes_for,
                'votes_against' => $noConfidenceVote->votes_against,
                'votes_cast' => $noConfidenceVote->votes_cast,
                'quorum_required' => $noConfidenceVote->quorum_required,
                'quorum_met' => $noConfidenceVote->quorum_met,
                'eligible_voters' => $this->electionService->getNoConfidenceEligibleVoterCount($noConfidenceVote->domain),
                'is_open' => $noConfidenceVote->isOpen(),
                'has_ended' => $noConfidenceVote->hasEnded(),
                'percentage_for' => $noConfidenceVote->getPercentageFor(),
                'percentage_against' => $noConfidenceVote->getPercentageAgainst(),
                'reason' => $noConfidenceVote->reason,
                'notes' => $noConfidenceVote->notes,
                'initiated_by' => $noConfidenceVote->initiatedBy ? [
                    'id' => $noConfidenceVote->initiatedBy->id,
                    'username' => $noConfidenceVote->initiatedBy->username,
                ] : null,
            ],
            'user_state' => [
                'has_voted' => $hasVoted,
                'is_eligible' => $isEligible,
                'can_vote' => $noConfidenceVote->canVote($user) && $isEligible,
                'is_target' => $user->id === $noConfidenceVote->target_player_id,
                'is_initiator' => $user->id === $noConfidenceVote->initiated_by_user_id,
            ],
        ]);
    }

    /**
     * Get vote status (for polling).
     */
    public function status(NoConfidenceVote $noConfidenceVote): JsonResponse
    {
        return response()->json([
            'status' => $noConfidenceVote->status,
            'votes_for' => $noConfidenceVote->votes_for,
            'votes_against' => $noConfidenceVote->votes_against,
            'votes_cast' => $noConfidenceVote->votes_cast,
            'quorum_met' => $noConfidenceVote->quorum_met,
            'is_open' => $noConfidenceVote->isOpen(),
            'has_ended' => $noConfidenceVote->hasEnded(),
        ]);
    }

    /**
     * Cast a ballot in a no confidence vote.
     */
    public function vote(Request $request, NoConfidenceVote $noConfidenceVote): JsonResponse|RedirectResponse
    {
        $request->validate([
            'vote_for_removal' => 'required|boolean',
        ]);

        try {
            $ballot = $this->electionService->castNoConfidenceBallot(
                $noConfidenceVote,
                $request->user(),
                $request->boolean('vote_for_removal')
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'ballot' => [
                        'id' => $ballot->id,
                        'vote_for_removal' => $ballot->vote_for_removal,
                        'voted_at' => $ballot->voted_at->toIso8601String(),
                    ],
                    'vote' => [
                        'votes_for' => $noConfidenceVote->fresh()->votes_for,
                        'votes_against' => $noConfidenceVote->fresh()->votes_against,
                        'votes_cast' => $noConfidenceVote->fresh()->votes_cast,
                        'quorum_met' => $noConfidenceVote->fresh()->quorum_met,
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
     * Start a no confidence vote against a village role holder.
     */
    public function startVillageNoConfidence(Request $request, Village $village): JsonResponse|RedirectResponse
    {
        $request->validate([
            'role' => 'required|string|in:elder,blacksmith,merchant,guard_captain,healer',
            'reason' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        $role = $request->input('role');

        // Find the role holder
        $target = $this->electionService->getRoleHolder($role, $village);

        if (! $target) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => "No one currently holds the '{$role}' role in this village.",
                ], 422);
            }
            return back()->withErrors(['vote' => "No one currently holds the '{$role}' role in this village."]);
        }

        try {
            $vote = $this->electionService->startNoConfidenceVote(
                $user,
                $target,
                $role,
                $village,
                $request->input('reason')
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'vote' => [
                        'id' => $vote->id,
                        'status' => $vote->status,
                        'voting_ends_at' => $vote->voting_ends_at->toIso8601String(),
                    ],
                ]);
            }

            return redirect()->route('no-confidence.show', $vote)->with('success', 'No confidence vote started.');
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
     * Start a no confidence vote against a town mayor.
     */
    public function startTownNoConfidence(Request $request, Town $town): JsonResponse|RedirectResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        // Find the mayor
        $target = $this->electionService->getRoleHolder('mayor', $town);

        if (! $target) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'This town does not have a mayor.',
                ], 422);
            }
            return back()->withErrors(['vote' => 'This town does not have a mayor.']);
        }

        try {
            $vote = $this->electionService->startNoConfidenceVote(
                $user,
                $target,
                'mayor',
                $town,
                $request->input('reason')
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'vote' => [
                        'id' => $vote->id,
                        'status' => $vote->status,
                        'voting_ends_at' => $vote->voting_ends_at->toIso8601String(),
                    ],
                ]);
            }

            return redirect()->route('no-confidence.show', $vote)->with('success', 'No confidence vote started.');
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
     * Start a no confidence vote against a kingdom's king.
     */
    public function startKingdomNoConfidence(Request $request, Kingdom $kingdom): JsonResponse|RedirectResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        // Find the king
        $target = $this->electionService->getRoleHolder('king', $kingdom);

        if (! $target) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'This kingdom does not have a king.',
                ], 422);
            }
            return back()->withErrors(['vote' => 'This kingdom does not have a king.']);
        }

        try {
            $vote = $this->electionService->startNoConfidenceVote(
                $user,
                $target,
                'king',
                $kingdom,
                $request->input('reason')
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'vote' => [
                        'id' => $vote->id,
                        'status' => $vote->status,
                        'voting_ends_at' => $vote->voting_ends_at->toIso8601String(),
                    ],
                ]);
            }

            return redirect()->route('no-confidence.show', $vote)->with('success', 'No confidence vote started.');
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
}

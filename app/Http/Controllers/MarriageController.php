<?php

namespace App\Http\Controllers;

use App\Models\Dynasty;
use App\Models\DynastyMember;
use App\Models\Marriage;
use App\Models\MarriageProposal;
use App\Services\MarriageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MarriageController extends Controller
{
    public function __construct(
        protected MarriageService $marriageService
    ) {}

    /**
     * Display marriage proposals page.
     */
    public function proposals(Request $request): Response
    {
        $user = $request->user();

        if (! $user->dynasty_id) {
            return Inertia::render('Dynasty/Proposals', [
                'has_dynasty' => false,
                'incoming' => [],
                'outgoing' => [],
                'marriages' => [],
                'can_propose' => false,
            ]);
        }

        $dynasty = Dynasty::find($user->dynasty_id);
        $memberIds = DynastyMember::where('dynasty_id', $dynasty->id)->pluck('id');

        // Incoming proposals - proposals where the target is in our dynasty
        $incoming = MarriageProposal::whereIn('proposed_member_id', $memberIds)
            ->pending()
            ->with(['proposer.dynasty', 'proposed.dynasty', 'proposerGuardian', 'proposedGuardian'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($proposal) => $this->mapProposal($proposal, 'incoming'));

        // Outgoing proposals - proposals where the proposer is in our dynasty
        $outgoing = MarriageProposal::whereIn('proposer_member_id', $memberIds)
            ->with(['proposer.dynasty', 'proposed.dynasty', 'proposerGuardian', 'proposedGuardian'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($proposal) => $this->mapProposal($proposal, 'outgoing'));

        // Recent marriages in our dynasty
        $marriages = Marriage::where(function ($q) use ($memberIds) {
            $q->whereIn('spouse1_id', $memberIds)
                ->orWhereIn('spouse2_id', $memberIds);
        })
            ->with(['spouse1.dynasty', 'spouse2.dynasty'])
            ->orderBy('wedding_date', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($marriage) => $this->mapMarriage($marriage));

        // Check if user can propose (has unmarried members of marriageable age)
        $canPropose = DynastyMember::where('dynasty_id', $dynasty->id)
            ->alive()
            ->where(function ($q) {
                $q->whereDoesntHave('marriagesAsSpouse1', fn ($q) => $q->active())
                    ->whereDoesntHave('marriagesAsSpouse2', fn ($q) => $q->active());
            })
            ->whereRaw("DATE_PART('year', AGE(NOW(), birth_date)) >= 16")
            ->exists();

        return Inertia::render('Dynasty/Proposals', [
            'has_dynasty' => true,
            'dynasty_name' => $dynasty->name,
            'incoming' => $incoming->toArray(),
            'outgoing' => $outgoing->toArray(),
            'marriages' => $marriages->toArray(),
            'can_propose' => $canPropose,
            'is_head' => $dynasty->current_head_id === $user->id,
        ]);
    }

    /**
     * Accept a marriage proposal.
     */
    public function accept(Request $request, MarriageProposal $proposal)
    {
        $user = $request->user();

        if (! $this->canRespondToProposal($user, $proposal)) {
            return back()->with('error', 'You cannot accept this proposal.');
        }

        if (! $proposal->canRespond()) {
            return back()->with('error', 'This proposal can no longer be accepted.');
        }

        try {
            // Use the service to properly create marriage with alliances and events
            $marriage = $this->marriageService->acceptProposal($proposal);

            return redirect()->route('dynasty.proposals')->with('success', 'Marriage proposal accepted! The wedding has taken place.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject a marriage proposal.
     */
    public function reject(Request $request, MarriageProposal $proposal)
    {
        $user = $request->user();

        if (! $this->canRespondToProposal($user, $proposal)) {
            return back()->with('error', 'You cannot reject this proposal.');
        }

        if (! $proposal->canRespond()) {
            return back()->with('error', 'This proposal can no longer be rejected.');
        }

        try {
            $this->marriageService->rejectProposal($proposal);

            return redirect()->route('dynasty.proposals')->with('success', 'Marriage proposal rejected.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show the propose marriage form.
     */
    public function proposeForm(Request $request): Response
    {
        $user = $request->user();

        if (! $user->dynasty_id) {
            return Inertia::render('Dynasty/ProposeMarriage', [
                'has_dynasty' => false,
                'eligible_members' => [],
                'candidates' => [],
                'dynasties' => [],
                'player_gold' => $user->gold ?? 0,
            ]);
        }

        $dynasty = Dynasty::find($user->dynasty_id);

        // Get eligible members from our dynasty (unmarried, age >= 14, alive)
        $eligibleMembers = DynastyMember::where('dynasty_id', $dynasty->id)
            ->alive()
            ->whereRaw("DATE_PART('year', AGE(NOW(), birth_date)) >= 14")
            ->where(function ($q) {
                $q->whereDoesntHave('marriagesAsSpouse1', fn ($q) => $q->where('status', 'active'))
                    ->whereDoesntHave('marriagesAsSpouse2', fn ($q) => $q->where('status', 'active'));
            })
            ->get()
            ->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->full_name,
                'first_name' => $member->first_name,
                'age' => $member->age,
                'gender' => $member->gender,
                'generation' => $member->generation,
            ]);

        // Get candidate members from other dynasties (unmarried, age >= 14, alive)
        $candidates = DynastyMember::where('dynasty_id', '!=', $dynasty->id)
            ->alive()
            ->whereRaw("DATE_PART('year', AGE(NOW(), birth_date)) >= 14")
            ->where(function ($q) {
                $q->whereDoesntHave('marriagesAsSpouse1', fn ($q) => $q->where('status', 'active'))
                    ->whereDoesntHave('marriagesAsSpouse2', fn ($q) => $q->where('status', 'active'));
            })
            ->with('dynasty')
            ->get()
            ->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->full_name,
                'first_name' => $member->first_name,
                'age' => $member->age,
                'gender' => $member->gender,
                'generation' => $member->generation,
                'dynasty_id' => $member->dynasty_id,
                'dynasty_name' => $member->dynasty?->name,
                'dynasty_prestige' => $member->dynasty?->prestige ?? 0,
            ]);

        // Get distinct dynasties for filtering
        $dynasties = Dynasty::where('id', '!=', $dynasty->id)
            ->orderBy('name')
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'prestige' => $d->prestige,
            ]);

        return Inertia::render('Dynasty/ProposeMarriage', [
            'has_dynasty' => true,
            'dynasty_name' => $dynasty->name,
            'eligible_members' => $eligibleMembers->toArray(),
            'candidates' => $candidates->toArray(),
            'dynasties' => $dynasties->toArray(),
            'player_gold' => $user->gold ?? 0,
            'is_head' => $dynasty->current_head_id === $user->id,
        ]);
    }

    /**
     * Store a new marriage proposal.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'proposer_member_id' => 'required|exists:dynasty_members,id',
            'proposed_member_id' => 'required|exists:dynasty_members,id',
            'offered_dowry' => 'nullable|integer|min:0',
            'message' => 'nullable|string|max:500',
        ]);

        if (! $user->dynasty_id) {
            return back()->with('error', 'You must have a dynasty to propose marriage.');
        }

        $dynasty = Dynasty::find($user->dynasty_id);

        // Validate proposer is from our dynasty
        $proposer = DynastyMember::find($validated['proposer_member_id']);
        if (! $proposer || $proposer->dynasty_id !== $dynasty->id) {
            return back()->with('error', 'Invalid proposer selected.');
        }

        // Validate proposer can marry
        if (! $proposer->canMarry()) {
            return back()->with('error', 'This dynasty member cannot marry (already married, too young, or not alive).');
        }

        // Validate proposed is from different dynasty
        $proposed = DynastyMember::find($validated['proposed_member_id']);
        if (! $proposed) {
            return back()->with('error', 'Invalid candidate selected.');
        }

        if ($proposed->dynasty_id === $dynasty->id) {
            return back()->with('error', 'Cannot propose marriage to a member of your own dynasty.');
        }

        // Validate proposed can marry
        if (! $proposed->canMarry()) {
            return back()->with('error', 'This candidate cannot marry (already married, too young, or not alive).');
        }

        // Check if there's already a pending proposal between these members
        $existingProposal = MarriageProposal::where(function ($q) use ($proposer, $proposed) {
            $q->where('proposer_member_id', $proposer->id)
                ->where('proposed_member_id', $proposed->id);
        })->orWhere(function ($q) use ($proposer, $proposed) {
            $q->where('proposer_member_id', $proposed->id)
                ->where('proposed_member_id', $proposer->id);
        })->pending()->exists();

        if ($existingProposal) {
            return back()->with('error', 'A pending proposal already exists between these members.');
        }

        // Check dowry
        $offeredDowry = $validated['offered_dowry'] ?? 0;
        if ($offeredDowry > 0 && ($user->gold ?? 0) < $offeredDowry) {
            return back()->with('error', 'You do not have enough gold for this dowry.');
        }

        try {
            // Use the service to create proposal
            $this->marriageService->propose(
                $proposer,
                $proposed,
                $offeredDowry,
                [],
                $validated['message'] ?? null,
                14
            );

            return redirect()->route('dynasty.proposals')->with('success', 'Marriage proposal sent successfully!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Withdraw a pending outgoing proposal.
     */
    public function withdraw(Request $request, MarriageProposal $proposal)
    {
        $user = $request->user();

        if (! $this->canWithdrawProposal($user, $proposal)) {
            return back()->with('error', 'You cannot withdraw this proposal.');
        }

        if (! $proposal->isPending()) {
            return back()->with('error', 'This proposal can no longer be withdrawn.');
        }

        $proposal->update([
            'status' => MarriageProposal::STATUS_WITHDRAWN,
            'responded_at' => now(),
        ]);

        return redirect()->route('dynasty.proposals')->with('success', 'Marriage proposal withdrawn.');
    }

    /**
     * Check if user can respond to a proposal.
     */
    private function canRespondToProposal($user, MarriageProposal $proposal): bool
    {
        // Must be the guardian of the proposed member
        if ($proposal->proposed_guardian_id === $user->id) {
            return true;
        }

        // Or must be dynasty head of the proposed member's dynasty
        $proposedMember = $proposal->proposed;
        if ($proposedMember && $proposedMember->dynasty) {
            return $proposedMember->dynasty->current_head_id === $user->id;
        }

        return false;
    }

    /**
     * Check if user can withdraw a proposal.
     */
    private function canWithdrawProposal($user, MarriageProposal $proposal): bool
    {
        // Must be the guardian of the proposer
        if ($proposal->proposer_guardian_id === $user->id) {
            return true;
        }

        // Or must be dynasty head of the proposer's dynasty
        $proposerMember = $proposal->proposer;
        if ($proposerMember && $proposerMember->dynasty) {
            return $proposerMember->dynasty->current_head_id === $user->id;
        }

        return false;
    }

    /**
     * Map proposal for frontend.
     */
    private function mapProposal(MarriageProposal $proposal, string $direction): array
    {
        return [
            'id' => $proposal->id,
            'status' => $proposal->status,
            'direction' => $direction,
            'proposer' => [
                'id' => $proposal->proposer->id,
                'name' => $proposal->proposer->full_name,
                'age' => $proposal->proposer->age,
                'gender' => $proposal->proposer->gender,
                'dynasty_name' => $proposal->proposer->dynasty?->name,
            ],
            'proposed' => [
                'id' => $proposal->proposed->id,
                'name' => $proposal->proposed->full_name,
                'age' => $proposal->proposed->age,
                'gender' => $proposal->proposed->gender,
                'dynasty_name' => $proposal->proposed->dynasty?->name,
            ],
            'proposer_dynasty' => $proposal->proposer->dynasty ? [
                'id' => $proposal->proposer->dynasty->id,
                'name' => $proposal->proposer->dynasty->name,
            ] : null,
            'proposed_dynasty' => $proposal->proposed->dynasty ? [
                'id' => $proposal->proposed->dynasty->id,
                'name' => $proposal->proposed->dynasty->name,
            ] : null,
            'offered_dowry' => $proposal->offered_dowry ?? 0,
            'message' => $proposal->message,
            'response_message' => $proposal->response_message,
            'created_at' => $proposal->created_at->diffForHumans(),
            'responded_at' => $proposal->responded_at?->diffForHumans(),
            'expires_at' => $proposal->expires_at?->diffForHumans(),
            'can_respond' => $proposal->canRespond(),
        ];
    }

    /**
     * Map marriage for frontend.
     */
    private function mapMarriage(Marriage $marriage): array
    {
        return [
            'id' => $marriage->id,
            'status' => $marriage->status,
            'marriage_type' => $marriage->marriage_type,
            'spouse1' => [
                'id' => $marriage->spouse1->id,
                'name' => $marriage->spouse1->full_name,
                'dynasty_name' => $marriage->spouse1->dynasty?->name,
            ],
            'spouse2' => [
                'id' => $marriage->spouse2->id,
                'name' => $marriage->spouse2->full_name,
                'dynasty_name' => $marriage->spouse2->dynasty?->name,
            ],
            'wedding_date' => $marriage->wedding_date?->format('M j, Y'),
            'wedding_year' => $marriage->wedding_date?->format('Y'),
            'end_date' => $marriage->end_date?->format('M j, Y'),
            'end_reason' => $marriage->end_reason,
            'duration' => $marriage->duration,
        ];
    }
}

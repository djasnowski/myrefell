<?php

namespace App\Http\Controllers;

use App\Models\Dynasty;
use App\Models\DynastyMember;
use App\Models\Marriage;
use App\Models\MarriageProposal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MarriageController extends Controller
{
    /**
     * Display marriage proposals page.
     */
    public function proposals(Request $request): Response
    {
        $user = $request->user();

        if (!$user->dynasty_id) {
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
            ->whereRaw('TIMESTAMPDIFF(YEAR, birth_date, NOW()) >= 16')
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

        if (!$this->canRespondToProposal($user, $proposal)) {
            return back()->with('error', 'You cannot accept this proposal.');
        }

        if (!$proposal->canRespond()) {
            return back()->with('error', 'This proposal can no longer be accepted.');
        }

        // Create the marriage
        $marriage = Marriage::create([
            'spouse1_id' => $proposal->proposer_member_id,
            'spouse2_id' => $proposal->proposed_member_id,
            'status' => Marriage::STATUS_ACTIVE,
            'marriage_type' => Marriage::TYPE_STANDARD,
            'dowry_amount' => $proposal->offered_dowry,
            'dowry_items' => $proposal->offered_items,
            'contract_terms' => $proposal->requested_terms,
            'wedding_date' => now(),
        ]);

        // Update proposal status
        $proposal->update([
            'status' => MarriageProposal::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        return redirect()->route('dynasty.proposals')->with('success', 'Marriage proposal accepted! The wedding has taken place.');
    }

    /**
     * Reject a marriage proposal.
     */
    public function reject(Request $request, MarriageProposal $proposal)
    {
        $user = $request->user();

        if (!$this->canRespondToProposal($user, $proposal)) {
            return back()->with('error', 'You cannot reject this proposal.');
        }

        if (!$proposal->canRespond()) {
            return back()->with('error', 'This proposal can no longer be rejected.');
        }

        $proposal->update([
            'status' => MarriageProposal::STATUS_REJECTED,
            'responded_at' => now(),
        ]);

        return redirect()->route('dynasty.proposals')->with('success', 'Marriage proposal rejected.');
    }

    /**
     * Withdraw a pending outgoing proposal.
     */
    public function withdraw(Request $request, MarriageProposal $proposal)
    {
        $user = $request->user();

        if (!$this->canWithdrawProposal($user, $proposal)) {
            return back()->with('error', 'You cannot withdraw this proposal.');
        }

        if (!$proposal->isPending()) {
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

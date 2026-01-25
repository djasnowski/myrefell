<?php

namespace App\Services;

use App\Models\Birth;
use App\Models\Dynasty;
use App\Models\DynastyAlliance;
use App\Models\DynastyEvent;
use App\Models\DynastyMember;
use App\Models\Marriage;
use App\Models\MarriageProposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MarriageService
{
    protected DynastyService $dynastyService;

    public function __construct(DynastyService $dynastyService)
    {
        $this->dynastyService = $dynastyService;
    }

    /**
     * Create a marriage proposal.
     */
    public function propose(
        DynastyMember $proposer,
        DynastyMember $proposed,
        int $offeredDowry = 0,
        array $offeredItems = [],
        ?string $message = null,
        ?int $expiresInDays = 7
    ): MarriageProposal {
        // Validate both can marry
        if (!$proposer->canMarry()) {
            throw new \Exception('Proposer cannot marry.');
        }
        if (!$proposed->canMarry()) {
            throw new \Exception('Proposed person cannot marry.');
        }

        // Check for existing pending proposal
        $existing = MarriageProposal::pending()
            ->where(function ($q) use ($proposer, $proposed) {
                $q->where('proposer_member_id', $proposer->id)
                    ->where('proposed_member_id', $proposed->id);
            })
            ->orWhere(function ($q) use ($proposer, $proposed) {
                $q->where('proposer_member_id', $proposed->id)
                    ->where('proposed_member_id', $proposer->id);
            })
            ->exists();

        if ($existing) {
            throw new \Exception('A proposal already exists between these two.');
        }

        return MarriageProposal::create([
            'proposer_member_id' => $proposer->id,
            'proposed_member_id' => $proposed->id,
            'proposer_guardian_id' => $proposer->user_id,
            'proposed_guardian_id' => $proposed->user_id,
            'status' => MarriageProposal::STATUS_PENDING,
            'offered_dowry' => $offeredDowry,
            'offered_items' => $offeredItems,
            'message' => $message,
            'expires_at' => $expiresInDays ? now()->addDays($expiresInDays) : null,
        ]);
    }

    /**
     * Accept a marriage proposal.
     */
    public function acceptProposal(MarriageProposal $proposal, ?string $responseMessage = null): Marriage
    {
        if (!$proposal->canRespond()) {
            throw new \Exception('Cannot respond to this proposal.');
        }

        return DB::transaction(function () use ($proposal, $responseMessage) {
            $proposal->update([
                'status' => MarriageProposal::STATUS_ACCEPTED,
                'response_message' => $responseMessage,
                'responded_at' => now(),
            ]);

            // Create the marriage
            $marriage = $this->createMarriage(
                $proposal->proposer,
                $proposal->proposed,
                $proposal->offered_dowry,
                $proposal->offered_items
            );

            return $marriage;
        });
    }

    /**
     * Reject a marriage proposal.
     */
    public function rejectProposal(MarriageProposal $proposal, ?string $responseMessage = null): void
    {
        if (!$proposal->canRespond()) {
            throw new \Exception('Cannot respond to this proposal.');
        }

        $proposal->update([
            'status' => MarriageProposal::STATUS_REJECTED,
            'response_message' => $responseMessage,
            'responded_at' => now(),
        ]);
    }

    /**
     * Create a marriage directly (for arranged marriages, etc.).
     */
    public function createMarriage(
        DynastyMember $spouse1,
        DynastyMember $spouse2,
        int $dowryAmount = 0,
        array $dowryItems = [],
        string $marriageType = Marriage::TYPE_STANDARD,
        ?string $locationType = null,
        ?int $locationId = null
    ): Marriage {
        if (!$spouse1->canMarry() || !$spouse2->canMarry()) {
            throw new \Exception('One or both parties cannot marry.');
        }

        return DB::transaction(function () use (
            $spouse1, $spouse2, $dowryAmount, $dowryItems, $marriageType, $locationType, $locationId
        ) {
            $marriage = Marriage::create([
                'spouse1_id' => $spouse1->id,
                'spouse2_id' => $spouse2->id,
                'status' => Marriage::STATUS_ACTIVE,
                'marriage_type' => $marriageType,
                'dowry_amount' => $dowryAmount,
                'dowry_items' => $dowryItems,
                'location_type' => $locationType,
                'location_id' => $locationId,
                'wedding_date' => now(),
            ]);

            // Update user spouse references if applicable
            if ($spouse1->user_id && $spouse2->user_id) {
                User::where('id', $spouse1->user_id)->update(['spouse_id' => $spouse2->user_id]);
                User::where('id', $spouse2->user_id)->update(['spouse_id' => $spouse1->user_id]);
            }

            // Log marriage events for both dynasties
            $this->logMarriageEvent($spouse1, $spouse2);
            if ($spouse1->dynasty_id !== $spouse2->dynasty_id) {
                $this->logMarriageEvent($spouse2, $spouse1);

                // Create alliance if different dynasties
                if ($marriageType !== Marriage::TYPE_SECRET) {
                    $this->dynastyService->formAlliance(
                        $spouse1->dynasty,
                        $spouse2->dynasty,
                        DynastyAlliance::TYPE_MARRIAGE,
                        $marriage->id
                    );
                }
            }

            return $marriage;
        });
    }

    /**
     * Log marriage event.
     */
    protected function logMarriageEvent(DynastyMember $member, DynastyMember $spouse): void
    {
        DynastyEvent::create([
            'dynasty_id' => $member->dynasty_id,
            'member_id' => $member->id,
            'event_type' => DynastyEvent::TYPE_MARRIAGE,
            'title' => 'Member Married',
            'description' => "{$member->full_name} married {$spouse->full_name}.",
            'prestige_change' => 20,
            'metadata' => ['spouse_id' => $spouse->id],
            'occurred_at' => now(),
        ]);

        $member->dynasty->increment('prestige', 20);
    }

    /**
     * Divorce a marriage.
     */
    public function divorce(Marriage $marriage, string $reason = null): void
    {
        if (!$marriage->isActive()) {
            throw new \Exception('Marriage is not active.');
        }

        DB::transaction(function () use ($marriage, $reason) {
            $marriage->update([
                'status' => Marriage::STATUS_DIVORCED,
                'end_date' => now(),
                'end_reason' => $reason ?? 'divorce',
            ]);

            // Clear user spouse references
            if ($marriage->spouse1->user_id) {
                User::where('id', $marriage->spouse1->user_id)->update(['spouse_id' => null]);
            }
            if ($marriage->spouse2->user_id) {
                User::where('id', $marriage->spouse2->user_id)->update(['spouse_id' => null]);
            }

            // Log divorce (prestige loss)
            foreach ([$marriage->spouse1, $marriage->spouse2] as $member) {
                DynastyEvent::create([
                    'dynasty_id' => $member->dynasty_id,
                    'member_id' => $member->id,
                    'event_type' => DynastyEvent::TYPE_DIVORCE,
                    'title' => 'Marriage Ended',
                    'description' => "{$member->full_name}'s marriage ended in divorce.",
                    'prestige_change' => -30,
                    'occurred_at' => now(),
                ]);
                $member->dynasty->decrement('prestige', 30);
            }

            // Break marriage alliance if exists
            $alliance = $marriage->alliance;
            if ($alliance && $alliance->isActive()) {
                $alliance->update([
                    'status' => DynastyAlliance::STATUS_BROKEN,
                    'ended_at' => now(),
                ]);
            }
        });
    }

    /**
     * Annul a marriage (as if it never happened, usually by church).
     */
    public function annul(Marriage $marriage, string $reason = null): void
    {
        if (!$marriage->isActive()) {
            throw new \Exception('Marriage is not active.');
        }

        DB::transaction(function () use ($marriage, $reason) {
            $marriage->update([
                'status' => Marriage::STATUS_ANNULLED,
                'end_date' => now(),
                'end_reason' => $reason ?? 'annulment',
            ]);

            // Clear user spouse references
            if ($marriage->spouse1->user_id) {
                User::where('id', $marriage->spouse1->user_id)->update(['spouse_id' => null]);
            }
            if ($marriage->spouse2->user_id) {
                User::where('id', $marriage->spouse2->user_id)->update(['spouse_id' => null]);
            }

            // Children become illegitimate
            Birth::where('marriage_id', $marriage->id)->update(['is_legitimate' => false]);
            DynastyMember::whereIn('id', function ($q) use ($marriage) {
                $q->select('child_id')->from('births')->where('marriage_id', $marriage->id);
            })->update(['is_legitimate' => false]);

            // Recalculate heirs for affected dynasties
            $this->dynastyService->recalculateHeir($marriage->spouse1->dynasty);
            if ($marriage->spouse1->dynasty_id !== $marriage->spouse2->dynasty_id) {
                $this->dynastyService->recalculateHeir($marriage->spouse2->dynasty);
            }
        });
    }

    /**
     * Record a birth.
     */
    public function recordBirth(
        DynastyMember $mother,
        ?DynastyMember $father,
        string $childName,
        string $gender,
        ?Marriage $marriage = null,
        bool $isTwins = false
    ): DynastyMember {
        return DB::transaction(function () use ($mother, $father, $childName, $gender, $marriage, $isTwins) {
            // Determine legitimacy
            $isLegitimate = $marriage && $marriage->isActive();

            // Determine which dynasty the child belongs to
            // Traditional: child belongs to father's dynasty
            $dynasty = $father?->dynasty ?? $mother->dynasty;

            // Create the dynasty member
            $child = $this->dynastyService->addMember(
                $dynasty,
                $childName,
                $gender,
                $father,
                $mother,
                null,
                $isLegitimate
            );

            // Record the birth
            Birth::create([
                'marriage_id' => $marriage?->id,
                'mother_id' => $mother->id,
                'father_id' => $father?->id,
                'child_id' => $child->id,
                'is_legitimate' => $isLegitimate,
                'is_twins' => $isTwins,
                'birth_date' => now(),
            ]);

            // Log birth event
            DynastyEvent::create([
                'dynasty_id' => $dynasty->id,
                'member_id' => $child->id,
                'event_type' => DynastyEvent::TYPE_BIRTH,
                'title' => 'Child Born',
                'description' => "{$childName} was born to {$mother->full_name}" .
                    ($father ? " and {$father->full_name}" : '') . '.',
                'prestige_change' => $isLegitimate ? 15 : 0,
                'occurred_at' => now(),
            ]);

            if ($isLegitimate) {
                $dynasty->increment('prestige', 15);
            }

            return $child;
        });
    }

    /**
     * Check and expire old proposals.
     */
    public function expireOldProposals(): int
    {
        return MarriageProposal::where('status', MarriageProposal::STATUS_PENDING)
            ->where('expires_at', '<', now())
            ->update(['status' => MarriageProposal::STATUS_EXPIRED]);
    }

    /**
     * Get compatible marriage candidates for a member.
     */
    public function getMarriageCandidates(DynastyMember $member, int $limit = 20)
    {
        if (!$member->canMarry()) {
            return collect();
        }

        return DynastyMember::alive()
            ->where('id', '!=', $member->id)
            ->where('dynasty_id', '!=', $member->dynasty_id) // Different dynasty preferred
            ->whereNull('user_id') // NPCs or
            ->orWhereNotNull('user_id') // Players
            ->whereDoesntHave('marriagesAsSpouse1', fn ($q) => $q->active())
            ->whereDoesntHave('marriagesAsSpouse2', fn ($q) => $q->active())
            ->limit($limit)
            ->get()
            ->filter(fn ($candidate) => $candidate->canMarry());
    }
}

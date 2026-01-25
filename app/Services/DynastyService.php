<?php

namespace App\Services;

use App\Models\Dynasty;
use App\Models\DynastyAlliance;
use App\Models\DynastyEvent;
use App\Models\DynastyMember;
use App\Models\SuccessionRule;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DynastyService
{
    /**
     * Found a new dynasty.
     */
    public function foundDynasty(
        User $founder,
        string $name,
        ?string $motto = null,
        string $successionType = SuccessionRule::TYPE_PRIMOGENITURE,
        string $genderLaw = SuccessionRule::GENDER_AGNATIC_COGNATIC
    ): Dynasty {
        return DB::transaction(function () use ($founder, $name, $motto, $successionType, $genderLaw) {
            $dynasty = Dynasty::create([
                'name' => $name,
                'motto' => $motto,
                'founder_id' => $founder->id,
                'current_head_id' => $founder->id,
                'prestige' => 0,
                'wealth_score' => 0,
                'members_count' => 1,
                'generations' => 1,
                'founded_at' => now(),
            ]);

            // Create founder as dynasty member
            $member = DynastyMember::create([
                'dynasty_id' => $dynasty->id,
                'user_id' => $founder->id,
                'member_type' => 'player',
                'first_name' => $founder->name,
                'gender' => 'male', // Default, should be from user profile
                'generation' => 1,
                'birth_order' => 1,
                'is_legitimate' => true,
                'is_heir' => false, // Founder is not heir, they are the head
                'status' => DynastyMember::STATUS_ALIVE,
            ]);

            // Set succession rules
            SuccessionRule::create([
                'dynasty_id' => $dynasty->id,
                'succession_type' => $successionType,
                'gender_law' => $genderLaw,
                'allows_bastards' => false,
                'allows_adoption' => false,
                'minimum_age' => 16,
            ]);

            // Update user with dynasty
            $founder->update([
                'dynasty_id' => $dynasty->id,
                'dynasty_member_id' => $member->id,
            ]);

            // Log founding event
            DynastyEvent::create([
                'dynasty_id' => $dynasty->id,
                'member_id' => $member->id,
                'event_type' => DynastyEvent::TYPE_ACHIEVEMENT,
                'title' => 'Dynasty Founded',
                'description' => "The {$name} dynasty was founded by {$founder->name}.",
                'prestige_change' => 100,
                'occurred_at' => now(),
            ]);

            $dynasty->increment('prestige', 100);

            return $dynasty;
        });
    }

    /**
     * Add a member to a dynasty.
     */
    public function addMember(
        Dynasty $dynasty,
        string $firstName,
        string $gender,
        ?DynastyMember $father = null,
        ?DynastyMember $mother = null,
        ?User $user = null,
        bool $isLegitimate = true
    ): DynastyMember {
        $generation = 1;
        if ($father) {
            $generation = max($generation, $father->generation + 1);
        }
        if ($mother) {
            $generation = max($generation, $mother->generation + 1);
        }

        // Calculate birth order among siblings
        $birthOrder = 1;
        if ($father || $mother) {
            $siblings = DynastyMember::where('dynasty_id', $dynasty->id)
                ->where(function ($q) use ($father, $mother) {
                    if ($father) $q->where('father_id', $father->id);
                    if ($mother) $q->orWhere('mother_id', $mother->id);
                })
                ->count();
            $birthOrder = $siblings + 1;
        }

        $member = DynastyMember::create([
            'dynasty_id' => $dynasty->id,
            'user_id' => $user?->id,
            'member_type' => $user ? 'player' : 'npc',
            'father_id' => $father?->id,
            'mother_id' => $mother?->id,
            'first_name' => $firstName,
            'gender' => $gender,
            'generation' => $generation,
            'birth_order' => $birthOrder,
            'is_legitimate' => $isLegitimate,
            'status' => DynastyMember::STATUS_ALIVE,
            'birth_date' => now(),
        ]);

        if ($user) {
            $user->update([
                'dynasty_id' => $dynasty->id,
                'dynasty_member_id' => $member->id,
            ]);
        }

        $dynasty->recalculateMembers();
        $this->recalculateHeir($dynasty);

        return $member;
    }

    /**
     * Record a death.
     */
    public function recordDeath(DynastyMember $member, string $cause = 'natural'): void
    {
        DB::transaction(function () use ($member, $cause) {
            $member->update([
                'status' => DynastyMember::STATUS_DEAD,
                'death_date' => now(),
                'death_cause' => $cause,
            ]);

            // Handle widowing of spouse
            $marriage = $member->currentMarriage();
            if ($marriage) {
                $marriage->update([
                    'status' => 'widowed',
                    'end_date' => now(),
                    'end_reason' => 'death',
                ]);
            }

            // Log death event
            DynastyEvent::create([
                'dynasty_id' => $member->dynasty_id,
                'member_id' => $member->id,
                'event_type' => DynastyEvent::TYPE_DEATH,
                'title' => 'Member Died',
                'description' => "{$member->full_name} has died ({$cause}).",
                'prestige_change' => -10,
                'occurred_at' => now(),
            ]);

            $member->dynasty->decrement('prestige', 10);
            $member->dynasty->recalculateMembers();

            // Check if we need succession
            if ($member->dynasty->current_head_id === $member->user_id) {
                $this->processSuccession($member->dynasty);
            } else {
                $this->recalculateHeir($member->dynasty);
            }
        });
    }

    /**
     * Process succession when head dies.
     */
    public function processSuccession(Dynasty $dynasty): ?DynastyMember
    {
        $rules = $dynasty->successionRules;
        if (!$rules) {
            return null;
        }

        $heirs = $rules->determineHeirs();
        if (empty($heirs)) {
            // Dynasty ends with no heirs
            DynastyEvent::create([
                'dynasty_id' => $dynasty->id,
                'event_type' => DynastyEvent::TYPE_SUCCESSION,
                'title' => 'Dynasty Extinct',
                'description' => "The {$dynasty->name} dynasty has no eligible heirs.",
                'prestige_change' => -500,
                'occurred_at' => now(),
            ]);
            return null;
        }

        $newHead = $heirs[0];
        if ($newHead instanceof DynastyMember) {
            $newHead = $newHead;
        } else {
            $newHead = DynastyMember::find($newHead['id']);
        }

        $dynasty->update(['current_head_id' => $newHead->user_id]);

        DynastyEvent::create([
            'dynasty_id' => $dynasty->id,
            'member_id' => $newHead->id,
            'event_type' => DynastyEvent::TYPE_SUCCESSION,
            'title' => 'New Dynasty Head',
            'description' => "{$newHead->full_name} has become head of the {$dynasty->name} dynasty.",
            'prestige_change' => 50,
            'occurred_at' => now(),
        ]);

        $newHead->update(['is_heir' => false]);
        $dynasty->increment('prestige', 50);

        $this->recalculateHeir($dynasty);

        return $newHead;
    }

    /**
     * Recalculate and set the heir.
     */
    public function recalculateHeir(Dynasty $dynasty): void
    {
        // Clear current heir
        $dynasty->members()->update(['is_heir' => false]);

        $rules = $dynasty->successionRules;
        if (!$rules) {
            return;
        }

        $heirs = $rules->determineHeirs();
        if (!empty($heirs)) {
            $heir = $heirs[0];
            $heirId = $heir instanceof DynastyMember ? $heir->id : $heir['id'];
            DynastyMember::where('id', $heirId)->update(['is_heir' => true]);
        }
    }

    /**
     * Disinherit a member.
     */
    public function disinherit(DynastyMember $member, string $reason = null): void
    {
        $member->update([
            'is_disinherited' => true,
            'is_heir' => false,
        ]);

        DynastyEvent::create([
            'dynasty_id' => $member->dynasty_id,
            'member_id' => $member->id,
            'event_type' => DynastyEvent::TYPE_SCANDAL,
            'title' => 'Member Disinherited',
            'description' => "{$member->full_name} has been disinherited." . ($reason ? " Reason: {$reason}" : ''),
            'prestige_change' => -25,
            'occurred_at' => now(),
        ]);

        $member->dynasty->decrement('prestige', 25);
        $this->recalculateHeir($member->dynasty);
    }

    /**
     * Form an alliance between dynasties.
     */
    public function formAlliance(
        Dynasty $dynasty1,
        Dynasty $dynasty2,
        string $allianceType = DynastyAlliance::TYPE_PACT,
        ?int $marriageId = null,
        array $terms = [],
        ?int $durationDays = null
    ): DynastyAlliance {
        $alliance = DynastyAlliance::create([
            'dynasty1_id' => $dynasty1->id,
            'dynasty2_id' => $dynasty2->id,
            'marriage_id' => $marriageId,
            'alliance_type' => $allianceType,
            'status' => DynastyAlliance::STATUS_ACTIVE,
            'terms' => $terms,
            'formed_at' => now(),
            'expires_at' => $durationDays ? now()->addDays($durationDays) : null,
        ]);

        // Log for both dynasties
        foreach ([$dynasty1, $dynasty2] as $dynasty) {
            $otherDynasty = $dynasty->id === $dynasty1->id ? $dynasty2 : $dynasty1;
            DynastyEvent::create([
                'dynasty_id' => $dynasty->id,
                'event_type' => DynastyEvent::TYPE_ALLIANCE,
                'title' => 'Alliance Formed',
                'description' => "Alliance formed with the {$otherDynasty->name} dynasty.",
                'prestige_change' => 25,
                'occurred_at' => now(),
            ]);
            $dynasty->increment('prestige', 25);
        }

        return $alliance;
    }

    /**
     * Break an alliance.
     */
    public function breakAlliance(DynastyAlliance $alliance, Dynasty $breakingDynasty): void
    {
        $alliance->update([
            'status' => DynastyAlliance::STATUS_BROKEN,
            'ended_at' => now(),
        ]);

        // Prestige loss for breaking
        DynastyEvent::create([
            'dynasty_id' => $breakingDynasty->id,
            'event_type' => DynastyEvent::TYPE_SCANDAL,
            'title' => 'Alliance Broken',
            'description' => "{$breakingDynasty->name} broke their alliance.",
            'prestige_change' => -50,
            'occurred_at' => now(),
        ]);

        $breakingDynasty->decrement('prestige', 50);
    }
}

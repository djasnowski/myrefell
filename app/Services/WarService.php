<?php

namespace App\Services;

use App\Models\Army;
use App\Models\PeaceTreaty;
use App\Models\War;
use App\Models\WarGoal;
use App\Models\WarParticipant;
use Illuminate\Support\Facades\DB;

class WarService
{
    /**
     * Declare war.
     */
    public function declareWar(
        string $name,
        string $casusBelli,
        string $attackerType,
        int $attackerId,
        string $defenderType,
        int $defenderId,
        ?int $attackerKingdomId = null,
        ?int $defenderKingdomId = null
    ): War {
        return DB::transaction(function () use (
            $name, $casusBelli, $attackerType, $attackerId,
            $defenderType, $defenderId, $attackerKingdomId, $defenderKingdomId
        ) {
            // Check for existing truce
            if ($this->hasActiveTruce($attackerType, $attackerId, $defenderType, $defenderId)) {
                throw new \Exception('Cannot declare war: active truce exists.');
            }

            $war = War::create([
                'name' => $name,
                'casus_belli' => $casusBelli,
                'attacker_kingdom_id' => $attackerKingdomId,
                'defender_kingdom_id' => $defenderKingdomId,
                'attacker_type' => $attackerType,
                'attacker_id' => $attackerId,
                'defender_type' => $defenderType,
                'defender_id' => $defenderId,
                'status' => War::STATUS_ACTIVE,
                'declared_at' => now(),
            ]);

            // Add primary participants
            $this->addParticipant($war, $attackerType, $attackerId, 'attacker', 'primary', true);
            $this->addParticipant($war, $defenderType, $defenderId, 'defender', 'primary', true);

            return $war;
        });
    }

    /**
     * Check for active truce between parties.
     */
    public function hasActiveTruce(
        string $attackerType,
        int $attackerId,
        string $defenderType,
        int $defenderId
    ): bool {
        return PeaceTreaty::activeTruce()
            ->whereHas('war', function ($q) use ($attackerType, $attackerId, $defenderType, $defenderId) {
                $q->where(function ($q) use ($attackerType, $attackerId, $defenderType, $defenderId) {
                    $q->where('attacker_type', $attackerType)
                        ->where('attacker_id', $attackerId)
                        ->where('defender_type', $defenderType)
                        ->where('defender_id', $defenderId);
                })->orWhere(function ($q) use ($attackerType, $attackerId, $defenderType, $defenderId) {
                    $q->where('attacker_type', $defenderType)
                        ->where('attacker_id', $defenderId)
                        ->where('defender_type', $attackerType)
                        ->where('defender_id', $attackerId);
                });
            })
            ->exists();
    }

    /**
     * Add participant to a war.
     */
    public function addParticipant(
        War $war,
        string $participantType,
        int $participantId,
        string $side,
        string $role = 'ally',
        bool $isWarLeader = false
    ): WarParticipant {
        return WarParticipant::create([
            'war_id' => $war->id,
            'participant_type' => $participantType,
            'participant_id' => $participantId,
            'side' => $side,
            'role' => $role,
            'is_war_leader' => $isWarLeader,
            'joined_at' => now(),
        ]);
    }

    /**
     * Add war goal.
     */
    public function addWarGoal(
        War $war,
        string $goalType,
        string $claimantType,
        int $claimantId,
        ?string $targetType = null,
        ?int $targetId = null,
        int $warScoreValue = 100
    ): WarGoal {
        return WarGoal::create([
            'war_id' => $war->id,
            'goal_type' => $goalType,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'claimant_type' => $claimantType,
            'claimant_id' => $claimantId,
            'war_score_value' => $warScoreValue,
        ]);
    }

    /**
     * Update war score.
     */
    public function updateWarScore(War $war, string $side, int $amount): void
    {
        if ($side === 'attacker') {
            $war->increment('attacker_war_score', $amount);
        } else {
            $war->increment('defender_war_score', $amount);
        }

        $this->updateWarStatus($war->fresh());
    }

    /**
     * Update war status based on war scores.
     */
    protected function updateWarStatus(War $war): void
    {
        if ($war->attacker_war_score >= 100) {
            $war->update(['status' => War::STATUS_ATTACKER_WINNING]);
        } elseif ($war->defender_war_score >= 100) {
            $war->update(['status' => War::STATUS_DEFENDER_WINNING]);
        } elseif ($war->attacker_war_score > $war->defender_war_score + 30) {
            $war->update(['status' => War::STATUS_ATTACKER_WINNING]);
        } elseif ($war->defender_war_score > $war->attacker_war_score + 30) {
            $war->update(['status' => War::STATUS_DEFENDER_WINNING]);
        } else {
            $war->update(['status' => War::STATUS_ACTIVE]);
        }
    }

    /**
     * Offer peace.
     */
    public function offerPeace(
        War $war,
        string $treatyType,
        ?string $winnerSide = null,
        array $territoryChanges = [],
        int $goldPayment = 0,
        int $truceDays = 365
    ): PeaceTreaty {
        return DB::transaction(function () use (
            $war, $treatyType, $winnerSide, $territoryChanges, $goldPayment, $truceDays
        ) {
            $treaty = PeaceTreaty::create([
                'war_id' => $war->id,
                'treaty_type' => $treatyType,
                'winner_side' => $winnerSide,
                'territory_changes' => $territoryChanges,
                'gold_payment' => $goldPayment,
                'truce_days' => $truceDays,
                'signed_at' => now(),
                'truce_expires_at' => now()->addDays($truceDays),
            ]);

            // End the war
            $status = match ($winnerSide) {
                'attacker' => War::STATUS_ATTACKER_VICTORY,
                'defender' => War::STATUS_DEFENDER_VICTORY,
                default => War::STATUS_WHITE_PEACE,
            };

            $war->update([
                'status' => $status,
                'ended_at' => now(),
            ]);

            // Apply territory changes if any
            if (!empty($territoryChanges)) {
                $this->applyTerritoryChanges($territoryChanges);
            }

            return $treaty;
        });
    }

    /**
     * Apply territory changes from a peace treaty.
     */
    protected function applyTerritoryChanges(array $changes): void
    {
        foreach ($changes as $change) {
            // In real implementation, this would transfer ownership
            // of villages, towns, or baronies to the new owner
        }
    }

    /**
     * Remove participant from war.
     */
    public function removeParticipant(War $war, string $participantType, int $participantId): void
    {
        $war->participants()
            ->where('participant_type', $participantType)
            ->where('participant_id', $participantId)
            ->update(['left_at' => now()]);
    }

    /**
     * Check if entity is at war.
     */
    public function isAtWar(string $entityType, int $entityId): bool
    {
        return WarParticipant::active()
            ->where('participant_type', $entityType)
            ->where('participant_id', $entityId)
            ->whereHas('war', fn ($q) => $q->active())
            ->exists();
    }

    /**
     * Get active wars for an entity.
     */
    public function getActiveWars(string $entityType, int $entityId)
    {
        return War::active()
            ->whereHas('participants', function ($q) use ($entityType, $entityId) {
                $q->active()
                    ->where('participant_type', $entityType)
                    ->where('participant_id', $entityId);
            })
            ->with(['participants', 'goals', 'battles'])
            ->get();
    }

    /**
     * Calculate contribution score for a participant.
     */
    public function updateContributionScore(WarParticipant $participant, int $amount): void
    {
        $participant->increment('contribution_score', $amount);
    }
}

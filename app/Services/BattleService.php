<?php

namespace App\Services;

use App\Models\Army;
use App\Models\ArmyUnit;
use App\Models\Battle;
use App\Models\BattleParticipant;
use App\Models\War;
use Illuminate\Support\Facades\DB;

class BattleService
{
    protected WarService $warService;

    public function __construct(WarService $warService)
    {
        $this->warService = $warService;
    }

    /**
     * Initiate a battle.
     */
    public function initiateBattle(
        array $attackerArmies,
        array $defenderArmies,
        string $locationType,
        int $locationId,
        ?War $war = null,
        string $battleType = Battle::TYPE_FIELD
    ): Battle {
        return DB::transaction(function () use (
            $attackerArmies, $defenderArmies, $locationType, $locationId, $war, $battleType
        ) {
            $attackerTroops = $this->countTotalTroops($attackerArmies);
            $defenderTroops = $this->countTotalTroops($defenderArmies);

            $battle = Battle::create([
                'name' => $this->generateBattleName($locationType, $locationId),
                'war_id' => $war?->id,
                'location_type' => $locationType,
                'location_id' => $locationId,
                'battle_type' => $battleType,
                'status' => Battle::STATUS_ONGOING,
                'phase' => Battle::PHASE_ENGAGEMENT,
                'attacker_troops_start' => $attackerTroops,
                'defender_troops_start' => $defenderTroops,
                'terrain_modifiers' => $this->getTerrainModifiers($locationType),
                'started_at' => now(),
            ]);

            // Add participants
            foreach ($attackerArmies as $army) {
                $this->addBattleParticipant($battle, $army, 'attacker');
                $army->update(['status' => Army::STATUS_IN_BATTLE]);
            }

            foreach ($defenderArmies as $army) {
                $this->addBattleParticipant($battle, $army, 'defender');
                $army->update(['status' => Army::STATUS_IN_BATTLE]);
            }

            return $battle;
        });
    }

    /**
     * Add army to battle.
     */
    protected function addBattleParticipant(Battle $battle, Army $army, string $side): BattleParticipant
    {
        return BattleParticipant::create([
            'battle_id' => $battle->id,
            'army_id' => $army->id,
            'side' => $side,
            'is_commander' => false, // First army on each side could be set to true
            'troops_committed' => $army->total_troops,
            'morale_at_start' => $army->morale,
        ]);
    }

    /**
     * Process a daily battle tick.
     */
    public function processBattleTick(Battle $battle): array
    {
        if (!$battle->isOngoing()) {
            return ['status' => 'ended'];
        }

        $results = [
            'day' => $battle->day,
            'phase' => $battle->phase,
            'attacker_casualties' => 0,
            'defender_casualties' => 0,
            'events' => [],
        ];

        // Get combat strength for each side
        $attackerStrength = $this->calculateSideStrength($battle, 'attacker');
        $defenderStrength = $this->calculateSideStrength($battle, 'defender');

        // Apply terrain modifiers (defender usually has advantage)
        $terrainMod = $battle->terrain_modifiers ?? [];
        $defenderStrength *= ($terrainMod['defender_bonus'] ?? 1.1);

        // Calculate casualties
        $attackerDamage = $this->calculateDamage($attackerStrength, $defenderStrength);
        $defenderDamage = $this->calculateDamage($defenderStrength, $attackerStrength);

        // Apply casualties to each side
        $results['attacker_casualties'] = $this->applyCasualties($battle, 'attacker', $defenderDamage);
        $results['defender_casualties'] = $this->applyCasualties($battle, 'defender', $attackerDamage);

        // Update battle totals
        $battle->increment('attacker_casualties', $results['attacker_casualties']);
        $battle->increment('defender_casualties', $results['defender_casualties']);
        $battle->increment('day');

        // Check for morale breaks and routing
        $this->checkMoraleBreaks($battle, $results);

        // Check for battle resolution
        $this->checkBattleResolution($battle, $results);

        // Log the battle day
        $log = $battle->battle_log ?? [];
        $log[] = [
            'day' => $battle->day,
            'attacker_casualties' => $results['attacker_casualties'],
            'defender_casualties' => $results['defender_casualties'],
            'events' => $results['events'],
        ];
        $battle->update(['battle_log' => $log]);

        return $results;
    }

    /**
     * Calculate side's combat strength.
     */
    protected function calculateSideStrength(Battle $battle, string $side): int
    {
        $strength = 0;
        $participants = $battle->participants()->where('side', $side)->with('army.units')->get();

        foreach ($participants as $participant) {
            $army = $participant->army;
            if (!$army || $army->status === Army::STATUS_DISBANDED) {
                continue;
            }

            $armyStrength = $army->total_attack;
            $moraleModifier = $army->morale / 100;
            $strength += (int) ($armyStrength * $moraleModifier);
        }

        return max(1, $strength);
    }

    /**
     * Calculate damage dealt.
     */
    protected function calculateDamage(int $attackerStrength, int $defenderStrength): int
    {
        $ratio = $attackerStrength / max(1, $defenderStrength);
        $baseDamage = (int) ($attackerStrength * 0.1); // 10% of strength as base
        $modifier = min(2.0, max(0.5, $ratio)); // Cap between 0.5x and 2x

        return (int) ($baseDamage * $modifier * (rand(80, 120) / 100));
    }

    /**
     * Apply casualties to a side.
     */
    protected function applyCasualties(Battle $battle, string $side, int $damage): int
    {
        $totalCasualties = 0;
        $participants = $battle->participants()->where('side', $side)->with('army.units')->get();

        foreach ($participants as $participant) {
            $army = $participant->army;
            if (!$army) continue;

            $armyShare = (int) ($damage / max(1, $participants->count()));
            $armyCasualties = $this->distributeArmyCasualties($army, $armyShare);

            $participant->increment('casualties', $armyCasualties);
            $totalCasualties += $armyCasualties;

            // Update army morale based on casualties
            $casualtyRate = $armyCasualties / max(1, $army->total_troops);
            $moraleLoss = (int) ($casualtyRate * 100 * 0.5);
            $army->decrement('morale', min($army->morale, $moraleLoss));
        }

        return $totalCasualties;
    }

    /**
     * Distribute casualties among army units.
     */
    protected function distributeArmyCasualties(Army $army, int $casualties): int
    {
        $totalApplied = 0;
        $units = $army->units()->where('status', '!=', ArmyUnit::STATUS_DESTROYED)->get();

        if ($units->isEmpty()) {
            return 0;
        }

        $casualtiesPerUnit = (int) ceil($casualties / $units->count());

        foreach ($units as $unit) {
            $unitCasualties = min($unit->count, $casualtiesPerUnit);
            $unit->decrement('count', $unitCasualties);
            $totalApplied += $unitCasualties;

            if ($unit->count <= 0) {
                $unit->update(['status' => ArmyUnit::STATUS_DESTROYED]);
            }
        }

        return $totalApplied;
    }

    /**
     * Check for morale breaks.
     */
    protected function checkMoraleBreaks(Battle $battle, array &$results): void
    {
        foreach (['attacker', 'defender'] as $side) {
            $participants = $battle->participants()->where('side', $side)->with('army')->get();

            foreach ($participants as $participant) {
                $army = $participant->army;
                if (!$army) continue;

                if ($army->morale <= 20) {
                    // Army routs
                    $results['events'][] = "{$army->name} is routing!";
                    $army->units()->update(['status' => ArmyUnit::STATUS_ROUTED]);
                    $participant->update(['outcome' => BattleParticipant::OUTCOME_ROUTED]);
                }
            }
        }
    }

    /**
     * Check if battle should end.
     */
    protected function checkBattleResolution(Battle $battle, array &$results): void
    {
        $attackerRemaining = $this->getRemainingTroops($battle, 'attacker');
        $defenderRemaining = $this->getRemainingTroops($battle, 'defender');

        $status = null;
        $winnerSide = null;

        if ($attackerRemaining <= 0 && $defenderRemaining <= 0) {
            $status = Battle::STATUS_DRAW;
        } elseif ($attackerRemaining <= 0) {
            $status = Battle::STATUS_DEFENDER_VICTORY;
            $winnerSide = 'defender';
        } elseif ($defenderRemaining <= 0) {
            $status = Battle::STATUS_ATTACKER_VICTORY;
            $winnerSide = 'attacker';
        } elseif ($battle->day >= 7) {
            // Prolonged battle - check for decisive victory
            if ($attackerRemaining > $defenderRemaining * 3) {
                $status = Battle::STATUS_ATTACKER_VICTORY;
                $winnerSide = 'attacker';
            } elseif ($defenderRemaining > $attackerRemaining * 3) {
                $status = Battle::STATUS_DEFENDER_VICTORY;
                $winnerSide = 'defender';
            } elseif ($battle->day >= 14) {
                $status = Battle::STATUS_INCONCLUSIVE;
            }
        }

        if ($status) {
            $this->endBattle($battle, $status, $winnerSide);
            $results['events'][] = "Battle ended: {$status}";
        }
    }

    /**
     * Get remaining troops for a side.
     */
    protected function getRemainingTroops(Battle $battle, string $side): int
    {
        $total = 0;
        $participants = $battle->participants()->where('side', $side)->with('army.units')->get();

        foreach ($participants as $participant) {
            $army = $participant->army;
            if ($army && $army->status !== Army::STATUS_DISBANDED) {
                $total += $army->units()
                    ->whereIn('status', [ArmyUnit::STATUS_READY, ArmyUnit::STATUS_EXHAUSTED])
                    ->sum('count');
            }
        }

        return $total;
    }

    /**
     * End a battle.
     */
    public function endBattle(Battle $battle, string $status, ?string $winnerSide): void
    {
        DB::transaction(function () use ($battle, $status, $winnerSide) {
            // Update battle status
            $battle->update([
                'status' => $status,
                'phase' => Battle::PHASE_AFTERMATH,
                'ended_at' => now(),
            ]);

            // Update participant outcomes
            foreach ($battle->participants as $participant) {
                $outcome = match (true) {
                    $participant->side === $winnerSide => BattleParticipant::OUTCOME_VICTORY,
                    is_null($winnerSide) => BattleParticipant::OUTCOME_WITHDREW,
                    default => BattleParticipant::OUTCOME_DEFEAT,
                };

                $participant->update([
                    'outcome' => $outcome,
                    'morale_at_end' => $participant->army?->morale,
                ]);

                // Update army status
                if ($participant->army) {
                    $participant->army->update(['status' => Army::STATUS_ENCAMPED]);
                }
            }

            // Update war score if part of a war
            if ($battle->war_id && $winnerSide) {
                $warScoreGain = $this->calculateBattleWarScore($battle);
                $this->warService->updateWarScore($battle->war, $winnerSide, $warScoreGain);
            }
        });
    }

    /**
     * Calculate war score from battle.
     */
    protected function calculateBattleWarScore(Battle $battle): int
    {
        $totalTroops = $battle->attacker_troops_start + $battle->defender_troops_start;
        $baseScore = min(25, (int) ($totalTroops / 100));

        // Bonus for decisive victories
        $casualtyRatio = $battle->attacker_casualties / max(1, $battle->defender_casualties);
        if ($casualtyRatio < 0.5 || $casualtyRatio > 2) {
            $baseScore += 5; // Decisive victory bonus
        }

        return $baseScore;
    }

    /**
     * Generate battle name.
     */
    protected function generateBattleName(string $locationType, int $locationId): string
    {
        $location = match ($locationType) {
            'village' => \App\Models\Village::find($locationId),
            'town' => \App\Models\Town::find($locationId),
            'castle' => \App\Models\Castle::find($locationId),
            default => null,
        };

        $locationName = $location?->name ?? 'Unknown';
        return "Battle of {$locationName}";
    }

    /**
     * Get terrain modifiers for location.
     */
    protected function getTerrainModifiers(string $locationType): array
    {
        return match ($locationType) {
            'castle' => ['defender_bonus' => 1.5, 'description' => 'Fortified position'],
            'town' => ['defender_bonus' => 1.2, 'description' => 'Urban terrain'],
            'village' => ['defender_bonus' => 1.1, 'description' => 'Familiar ground'],
            default => ['defender_bonus' => 1.0, 'description' => 'Open field'],
        };
    }

    /**
     * Count total troops in army array.
     */
    protected function countTotalTroops(array $armies): int
    {
        return array_reduce($armies, fn ($sum, $army) => $sum + $army->total_troops, 0);
    }
}

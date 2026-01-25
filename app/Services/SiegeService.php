<?php

namespace App\Services;

use App\Models\Army;
use App\Models\Siege;
use App\Models\War;
use Illuminate\Support\Facades\DB;

class SiegeService
{
    protected WarService $warService;
    protected BattleService $battleService;

    public function __construct(WarService $warService, BattleService $battleService)
    {
        $this->warService = $warService;
        $this->battleService = $battleService;
    }

    /**
     * Start a siege.
     */
    public function startSiege(
        Army $attackingArmy,
        string $targetType,
        int $targetId,
        ?War $war = null
    ): Siege {
        // Check if there's already an active siege
        $existingSiege = Siege::active()
            ->atTarget($targetType, $targetId)
            ->first();

        if ($existingSiege) {
            throw new \Exception('Target is already under siege.');
        }

        $fortificationLevel = $this->getTargetFortificationLevel($targetType, $targetId);
        $garrisonStrength = $this->getGarrisonStrength($targetType, $targetId);

        $siege = Siege::create([
            'war_id' => $war?->id,
            'attacking_army_id' => $attackingArmy->id,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'status' => Siege::STATUS_ACTIVE,
            'fortification_level' => $fortificationLevel,
            'garrison_strength' => $garrisonStrength,
            'garrison_morale' => 100,
            'supplies_remaining' => 100,
            'siege_equipment' => [],
            'started_at' => now(),
        ]);

        $attackingArmy->update(['status' => Army::STATUS_BESIEGING]);

        return $siege;
    }

    /**
     * Process daily siege tick.
     */
    public function processSiegeTick(Siege $siege): array
    {
        if (!$siege->isActive()) {
            return ['status' => 'ended'];
        }

        $results = [
            'day' => $siege->days_besieged + 1,
            'events' => [],
            'fortification_damage' => 0,
            'supplies_consumed' => 0,
        ];

        // Increment days besieged
        $siege->increment('days_besieged');

        // Consume defender supplies
        $supplyConsumption = max(1, (int) ($siege->garrison_strength / 50));
        $newSupplies = max(0, $siege->supplies_remaining - $supplyConsumption);
        $results['supplies_consumed'] = $supplyConsumption;

        // Siege equipment damages fortifications
        $equipment = $siege->siege_equipment ?? [];
        $fortDamage = $this->calculateFortificationDamage($equipment);
        $results['fortification_damage'] = $fortDamage;

        $newFortLevel = max(0, $siege->fortification_level - $fortDamage);

        // Check for breach
        $hasBreach = $siege->has_breach || $newFortLevel <= 30;
        if ($hasBreach && !$siege->has_breach) {
            $results['events'][] = 'The walls have been breached!';
        }

        // Morale effects from starvation
        $moraleChange = 0;
        if ($newSupplies <= 0) {
            $moraleChange -= 10;
            $results['events'][] = 'The garrison is starving!';
        } elseif ($newSupplies < 20) {
            $moraleChange -= 3;
            $results['events'][] = 'Food supplies are running low.';
        }

        // Prolonged siege morale loss
        if ($siege->days_besieged > 30) {
            $moraleChange -= 2;
        }

        $newMorale = max(0, min(100, $siege->garrison_morale + $moraleChange));

        // Update siege
        $siege->update([
            'fortification_level' => $newFortLevel,
            'supplies_remaining' => $newSupplies,
            'garrison_morale' => $newMorale,
            'has_breach' => $hasBreach,
        ]);

        // Check for automatic surrender
        if ($this->checkAutoSurrender($siege)) {
            $this->captureSiege($siege);
            $results['events'][] = 'The garrison has surrendered!';
        }

        // Log siege day
        $log = $siege->siege_log ?? [];
        $log[] = $results;
        $siege->update(['siege_log' => $log]);

        return $results;
    }

    /**
     * Calculate fortification damage from siege equipment.
     */
    protected function calculateFortificationDamage(array $equipment): int
    {
        $damage = 1; // Base daily wear

        foreach ($equipment as $item => $count) {
            $damage += match ($item) {
                'battering_ram' => $count * 2,
                'trebuchet' => $count * 5,
                'catapult' => $count * 3,
                'siege_tower' => $count * 1,
                'sappers' => $count * 4,
                default => 0,
            };
        }

        return $damage;
    }

    /**
     * Add siege equipment.
     */
    public function addSiegeEquipment(Siege $siege, string $equipment, int $count = 1): void
    {
        $current = $siege->siege_equipment ?? [];
        $current[$equipment] = ($current[$equipment] ?? 0) + $count;
        $siege->update(['siege_equipment' => $current]);
    }

    /**
     * Attempt an assault.
     */
    public function attemptAssault(Siege $siege): array
    {
        if (!$siege->canAssault()) {
            throw new \Exception('Cannot assault: fortifications too strong and no breach.');
        }

        $siege->update(['status' => Siege::STATUS_ASSAULT]);

        $attackingArmy = $siege->attackingArmy;
        $attackerStrength = $attackingArmy->total_attack;

        // Defender strength based on garrison and fortifications
        $defenderStrength = $siege->garrison_strength * 2; // Garrison gets bonus
        $defenderStrength *= ($siege->fortification_level / 100 + 0.5);
        $defenderStrength *= ($siege->garrison_morale / 100);

        // Breach reduces defender effectiveness
        if ($siege->has_breach) {
            $defenderStrength *= 0.6;
        }

        $results = [
            'attacker_strength' => $attackerStrength,
            'defender_strength' => (int) $defenderStrength,
            'success' => false,
            'attacker_casualties' => 0,
            'defender_casualties' => 0,
        ];

        // Resolve assault
        $ratio = $attackerStrength / max(1, $defenderStrength);

        if ($ratio > 2 || ($ratio > 1.2 && rand(1, 100) <= 60)) {
            // Assault succeeds
            $results['success'] = true;
            $results['attacker_casualties'] = (int) ($attackingArmy->total_troops * rand(10, 25) / 100);
            $results['defender_casualties'] = $siege->garrison_strength;

            $this->captureSiege($siege);
        } else {
            // Assault fails
            $results['attacker_casualties'] = (int) ($attackingArmy->total_troops * rand(20, 40) / 100);
            $results['defender_casualties'] = (int) ($siege->garrison_strength * rand(5, 15) / 100);

            // Apply casualties
            $siege->update([
                'status' => Siege::STATUS_ACTIVE,
                'garrison_strength' => max(0, $siege->garrison_strength - $results['defender_casualties']),
            ]);
        }

        // Apply attacker casualties
        $this->applyAssaultCasualties($attackingArmy, $results['attacker_casualties']);

        return $results;
    }

    /**
     * Apply casualties from assault.
     */
    protected function applyAssaultCasualties(Army $army, int $casualties): void
    {
        $units = $army->units;
        $perUnit = (int) ceil($casualties / max(1, $units->count()));

        foreach ($units as $unit) {
            $unit->decrement('count', min($unit->count, $perUnit));
        }
    }

    /**
     * Capture the siege target.
     */
    public function captureSiege(Siege $siege): void
    {
        DB::transaction(function () use ($siege) {
            $siege->update([
                'status' => Siege::STATUS_CAPTURED,
                'ended_at' => now(),
            ]);

            $siege->attackingArmy->update(['status' => Army::STATUS_ENCAMPED]);

            // Update war score if part of war
            if ($siege->war_id) {
                $warScore = $this->calculateSiegeWarScore($siege);
                $this->warService->updateWarScore($siege->war, 'attacker', $warScore);
            }

            // Transfer ownership would happen here
        });
    }

    /**
     * Lift the siege (attackers withdraw).
     */
    public function liftSiege(Siege $siege): void
    {
        $siege->update([
            'status' => Siege::STATUS_LIFTED,
            'ended_at' => now(),
        ]);

        $siege->attackingArmy->update(['status' => Army::STATUS_ENCAMPED]);

        // Defender war score
        if ($siege->war_id) {
            $this->warService->updateWarScore($siege->war, 'defender', 10);
        }
    }

    /**
     * Check for automatic surrender.
     */
    protected function checkAutoSurrender(Siege $siege): bool
    {
        // Surrender if: no supplies and low morale
        if ($siege->supplies_remaining <= 0 && $siege->garrison_morale <= 20) {
            return true;
        }

        // Surrender if: garrison destroyed
        if ($siege->garrison_strength <= 0) {
            return true;
        }

        return false;
    }

    /**
     * Calculate war score from siege.
     */
    protected function calculateSiegeWarScore(Siege $siege): int
    {
        return match ($siege->target_type) {
            'castle' => 50,
            'town' => 30,
            'village' => 10,
            default => 10,
        };
    }

    /**
     * Get target's fortification level.
     */
    protected function getTargetFortificationLevel(string $targetType, int $targetId): int
    {
        // In real implementation, this would check the target's fortification building
        return match ($targetType) {
            'castle' => 100,
            'town' => 50,
            'village' => 20,
            default => 10,
        };
    }

    /**
     * Get garrison strength.
     */
    protected function getGarrisonStrength(string $targetType, int $targetId): int
    {
        // In real implementation, this would check for stationed armies
        return match ($targetType) {
            'castle' => 200,
            'town' => 100,
            'village' => 20,
            default => 10,
        };
    }
}

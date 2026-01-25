<?php

namespace App\Services;

use App\Models\Army;
use App\Models\ArmyUnit;
use App\Models\MercenaryCompany;
use App\Models\SupplyLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ArmyService
{
    const RECRUITMENT_COSTS = [
        ArmyUnit::TYPE_LEVY => 5,
        ArmyUnit::TYPE_MILITIA => 15,
        ArmyUnit::TYPE_MEN_AT_ARMS => 50,
        ArmyUnit::TYPE_KNIGHTS => 200,
        ArmyUnit::TYPE_ARCHERS => 25,
        ArmyUnit::TYPE_CROSSBOWMEN => 40,
        ArmyUnit::TYPE_CAVALRY => 100,
        ArmyUnit::TYPE_SIEGE_ENGINEERS => 75,
    ];

    /**
     * Raise a new army.
     */
    public function raiseArmy(
        string $name,
        string $ownerType,
        int $ownerId,
        string $locationType,
        int $locationId,
        ?int $commanderId = null,
        ?int $npcCommanderId = null
    ): Army {
        return Army::create([
            'name' => $name,
            'commander_id' => $commanderId,
            'npc_commander_id' => $npcCommanderId,
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'location_type' => $locationType,
            'location_id' => $locationId,
            'status' => Army::STATUS_MUSTERING,
            'morale' => 100,
            'supplies' => 30,
            'mustered_at' => now(),
        ]);
    }

    /**
     * Recruit soldiers into an army.
     */
    public function recruitUnit(Army $army, string $unitType, int $count): ArmyUnit
    {
        $stats = ArmyUnit::getBaseStats($unitType);

        $unit = $army->units()->where('unit_type', $unitType)->first();

        if ($unit) {
            $unit->update([
                'count' => $unit->count + $count,
                'max_count' => $unit->max_count + $count,
            ]);
        } else {
            $unit = ArmyUnit::create([
                'army_id' => $army->id,
                'unit_type' => $unitType,
                'count' => $count,
                'max_count' => $count,
                'attack' => $stats['attack'],
                'defense' => $stats['defense'],
                'morale_bonus' => $stats['morale_bonus'],
                'upkeep_per_soldier' => $stats['upkeep'],
                'status' => ArmyUnit::STATUS_READY,
            ]);
        }

        $this->updateArmyComposition($army);
        $this->calculateUpkeep($army);

        return $unit;
    }

    /**
     * Update army composition summary.
     */
    protected function updateArmyComposition(Army $army): void
    {
        $composition = [];
        foreach ($army->units as $unit) {
            $composition[$unit->unit_type] = $unit->count;
        }
        $army->update(['composition' => $composition]);
    }

    /**
     * Calculate and update army upkeep.
     */
    public function calculateUpkeep(Army $army): void
    {
        $goldUpkeep = 0;
        $supplyConsumption = 0;

        foreach ($army->units as $unit) {
            $goldUpkeep += $unit->total_upkeep;
            $supplyConsumption += (int) ceil($unit->count / 10); // 1 supply per 10 soldiers
        }

        $army->update([
            'gold_upkeep' => $goldUpkeep,
            'daily_supply_cost' => $supplyConsumption,
        ]);
    }

    /**
     * Move army to a new location.
     */
    public function moveArmy(Army $army, string $locationType, int $locationId): Army
    {
        if ($army->status === Army::STATUS_IN_BATTLE) {
            throw new \Exception('Cannot move army while in battle.');
        }

        $army->update([
            'location_type' => $locationType,
            'location_id' => $locationId,
            'status' => Army::STATUS_MARCHING,
        ]);

        return $army->fresh();
    }

    /**
     * Set army to encamped status.
     */
    public function encampArmy(Army $army): Army
    {
        $army->update(['status' => Army::STATUS_ENCAMPED]);
        return $army->fresh();
    }

    /**
     * Disband an army.
     */
    public function disbandArmy(Army $army): void
    {
        DB::transaction(function () use ($army) {
            $army->units()->delete();
            $army->supplyLines()->delete();
            $army->update(['status' => Army::STATUS_DISBANDED]);
        });
    }

    /**
     * Process daily army maintenance.
     */
    public function processDailyMaintenance(Army $army): array
    {
        $results = [
            'supplies_consumed' => 0,
            'morale_change' => 0,
            'desertions' => 0,
        ];

        if (!$army->isOperational()) {
            return $results;
        }

        // Consume supplies
        $supplyConsumption = $army->daily_supply_cost;
        $newSupplies = max(0, $army->supplies - $supplyConsumption);
        $results['supplies_consumed'] = $supplyConsumption;

        // Resupply from supply lines
        $resupply = $army->supplyLines()
            ->operational()
            ->sum('supply_rate');
        $newSupplies = min(100, $newSupplies + $resupply);

        // Morale effects
        $moraleChange = 0;
        if ($newSupplies <= 0) {
            $moraleChange -= 10; // No supplies
            $results['desertions'] = $this->processDesertions($army);
        } elseif ($newSupplies < 20) {
            $moraleChange -= 5; // Low supplies
        }

        // Commander bonus
        if ($army->commander_id || $army->npc_commander_id) {
            $moraleChange += 1;
        }

        $newMorale = max(0, min(100, $army->morale + $moraleChange));
        $results['morale_change'] = $moraleChange;

        $army->update([
            'supplies' => $newSupplies,
            'morale' => $newMorale,
        ]);

        return $results;
    }

    /**
     * Process desertions from low morale/supplies.
     */
    protected function processDesertions(Army $army): int
    {
        $totalDesertions = 0;

        foreach ($army->units as $unit) {
            if ($unit->unit_type === ArmyUnit::TYPE_LEVY) {
                // Levies desert more easily
                $desertions = (int) ceil($unit->count * 0.05);
            } else {
                $desertions = (int) ceil($unit->count * 0.02);
            }

            if ($desertions > 0) {
                $unit->update(['count' => max(0, $unit->count - $desertions)]);
                $totalDesertions += $desertions;
            }
        }

        $this->updateArmyComposition($army);
        return $totalDesertions;
    }

    /**
     * Establish a supply line.
     */
    public function establishSupplyLine(
        Army $army,
        string $sourceType,
        int $sourceId,
        int $supplyRate = 10,
        int $distance = 1
    ): SupplyLine {
        return SupplyLine::create([
            'army_id' => $army->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'status' => SupplyLine::STATUS_ACTIVE,
            'supply_rate' => $supplyRate,
            'distance' => $distance,
            'safety' => 100,
        ]);
    }

    /**
     * Hire a mercenary company.
     */
    public function hireMercenaries(
        MercenaryCompany $company,
        User $hirer,
        ?string $hirerType = 'player',
        ?int $hirerEntityId = null,
        int $contractDays = 30
    ): MercenaryCompany {
        if (!$company->is_available) {
            throw new \Exception('Mercenary company is not available for hire.');
        }

        $company->update([
            'is_available' => false,
            'hired_by_id' => $hirer->id,
            'hired_by_type' => $hirerType,
            'hired_by_entity_id' => $hirerEntityId,
            'contract_days_remaining' => $contractDays,
        ]);

        return $company->fresh();
    }

    /**
     * Release a mercenary company.
     */
    public function releaseMercenaries(MercenaryCompany $company): void
    {
        $company->update([
            'is_available' => true,
            'hired_by_id' => null,
            'hired_by_type' => null,
            'hired_by_entity_id' => null,
            'contract_days_remaining' => null,
        ]);
    }

    /**
     * Get recruitment cost for a unit type.
     */
    public function getRecruitmentCost(string $unitType, int $count): int
    {
        return (self::RECRUITMENT_COSTS[$unitType] ?? 10) * $count;
    }
}

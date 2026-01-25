<?php

namespace App\Services;

use App\Models\Building;
use App\Models\BuildingDamage;
use App\Models\Disaster;
use App\Models\DisasterType;
use App\Models\Village;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DisasterService
{
    /**
     * Check for and trigger random disasters based on season.
     */
    public function checkForDisasters(string $currentSeason): array
    {
        $triggered = [];

        $disasterTypes = DisasterType::all();

        foreach ($disasterTypes as $type) {
            if (!$type->canOccurInSeason($currentSeason)) {
                continue;
            }

            // Check each village for potential disaster
            Village::chunk(100, function ($villages) use ($type, &$triggered) {
                foreach ($villages as $village) {
                    if (rand(1, 100) <= $type->base_chance) {
                        $disaster = $this->triggerDisaster($type, 'village', $village->id);
                        $triggered[] = [
                            'disaster' => $disaster,
                            'location' => $village->name,
                        ];
                    }
                }
            });
        }

        return $triggered;
    }

    /**
     * Trigger a disaster at a location.
     */
    public function triggerDisaster(
        DisasterType $type,
        string $locationType,
        int $locationId,
        ?int $severity = null
    ): Disaster {
        $severity = $severity ?? rand(30, 100);

        return DB::transaction(function () use ($type, $locationType, $locationId, $severity) {
            $disaster = Disaster::create([
                'disaster_type_id' => $type->id,
                'location_type' => $locationType,
                'location_id' => $locationId,
                'status' => 'active',
                'severity' => $severity,
                'started_at' => now(),
            ]);

            // Apply damage
            $damageReport = $this->applyDisasterDamage($disaster, $type, $severity);
            $disaster->update([
                'buildings_damaged' => $damageReport['buildings_damaged'],
                'buildings_destroyed' => $damageReport['buildings_destroyed'],
                'casualties' => $damageReport['casualties'],
                'gold_damage' => $damageReport['gold_damage'],
                'damage_report' => $damageReport,
            ]);

            return $disaster;
        });
    }

    /**
     * Apply disaster damage to a location.
     */
    protected function applyDisasterDamage(Disaster $disaster, DisasterType $type, int $severity): array
    {
        $report = [
            'buildings_damaged' => 0,
            'buildings_destroyed' => 0,
            'casualties' => 0,
            'gold_damage' => 0,
            'details' => [],
        ];

        // Calculate damage modifier based on severity
        $damageModifier = $severity / 100;
        $buildingDamage = (int) ($type->building_damage * $damageModifier);

        // Get buildings at location
        $buildings = Building::atLocation($disaster->location_type, $disaster->location_id)
            ->whereIn('status', [Building::STATUS_OPERATIONAL, Building::STATUS_DAMAGED])
            ->get();

        foreach ($buildings as $building) {
            // Check if building has protection
            if ($this->hasBuildingProtection($building, $type)) {
                continue;
            }

            // Roll for damage
            if (rand(1, 100) <= 50) { // 50% chance to be affected
                $actualDamage = rand((int) ($buildingDamage * 0.5), $buildingDamage);
                $this->damageBuilding($building, $disaster, $actualDamage);

                if ($building->condition <= 0) {
                    $report['buildings_destroyed']++;
                } else {
                    $report['buildings_damaged']++;
                }

                $report['gold_damage'] += $actualDamage * 10; // Estimated repair cost
                $report['details'][] = [
                    'building' => $building->buildingType->name,
                    'damage' => $actualDamage,
                    'destroyed' => $building->condition <= 0,
                ];
            }
        }

        // Calculate casualties (simplified)
        if ($type->casualty_rate > 0) {
            $casualtyChance = (int) ($type->casualty_rate * $damageModifier);
            // In a real implementation, this would affect NPCs/players at the location
            $report['casualties'] = rand(0, $casualtyChance);
        }

        return $report;
    }

    /**
     * Damage a building.
     */
    protected function damageBuilding(Building $building, Disaster $disaster, int $damage): void
    {
        $conditionBefore = $building->condition;
        $conditionAfter = max(0, $building->condition - $damage);

        BuildingDamage::create([
            'building_id' => $building->id,
            'disaster_id' => $disaster->id,
            'damage_amount' => $damage,
            'condition_before' => $conditionBefore,
            'condition_after' => $conditionAfter,
            'cause' => $disaster->disasterType->name,
            'occurred_at' => now(),
        ]);

        $newStatus = $conditionAfter <= 0
            ? Building::STATUS_DESTROYED
            : ($conditionAfter < 50 ? Building::STATUS_DAMAGED : $building->status);

        $building->update([
            'condition' => $conditionAfter,
            'status' => $newStatus,
        ]);
    }

    /**
     * Check if building has protection against disaster type.
     */
    protected function hasBuildingProtection(Building $building, DisasterType $type): bool
    {
        if (empty($type->preventable_by)) {
            return false;
        }

        // Check if location has protective buildings
        $protectiveBuildings = Building::atLocation($building->location_type, $building->location_id)
            ->operational()
            ->whereHas('buildingType', function ($q) use ($type) {
                $q->whereIn('slug', $type->preventable_by);
            })
            ->exists();

        return $protectiveBuildings;
    }

    /**
     * End a disaster.
     */
    public function endDisaster(Disaster $disaster): void
    {
        $disaster->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);
    }

    /**
     * Process active disasters (called daily).
     */
    public function processDailyDisasters(): array
    {
        $results = [];

        Disaster::active()
            ->with('disasterType')
            ->each(function ($disaster) use (&$results) {
                $type = $disaster->disasterType;
                $daysSinceStart = $disaster->started_at->diffInDays(now());

                if ($daysSinceStart >= $type->duration_days) {
                    $this->endDisaster($disaster);
                    $results[] = [
                        'disaster_id' => $disaster->id,
                        'action' => 'ended',
                    ];
                }
            });

        return $results;
    }

    /**
     * Get active disasters at a location.
     */
    public function getActiveDisasters(string $locationType, int $locationId): Collection
    {
        return Disaster::active()
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->with('disasterType')
            ->get();
    }
}

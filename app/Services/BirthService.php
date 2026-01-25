<?php

namespace App\Services;

use App\Models\PlayerTitle;
use App\Models\Town;
use App\Models\User;
use App\Models\Village;

class BirthService
{
    /**
     * Weight for capital town villages (very low chance).
     */
    public const CAPITAL_VILLAGE_WEIGHT = 5;

    /**
     * Weight for regular villages (high chance).
     */
    public const REGULAR_VILLAGE_WEIGHT = 100;

    /**
     * Select a birth village using weighted random selection.
     * Capital town villages have a much lower chance of being selected.
     */
    public function selectBirthVillage(): ?Village
    {
        $villages = Village::with(['barony.kingdom'])->get();

        if ($villages->isEmpty()) {
            return null;
        }

        // Build weighted list
        $weightedVillages = [];

        foreach ($villages as $village) {
            $isCapitalVillage = $village->barony?->isCapitalBarony() ?? false;
            $weight = $isCapitalVillage ? self::CAPITAL_VILLAGE_WEIGHT : self::REGULAR_VILLAGE_WEIGHT;

            for ($i = 0; $i < $weight; $i++) {
                $weightedVillages[] = $village;
            }
        }

        if (empty($weightedVillages)) {
            return $villages->random();
        }

        return $weightedVillages[array_rand($weightedVillages)];
    }

    /**
     * Assign a new player to their home village and grant peasant title.
     */
    public function assignNewPlayer(User $user): void
    {
        // Select birth village
        $village = $this->selectBirthVillage();

        if ($village) {
            $user->home_village_id = $village->id;
            $user->current_location_type = 'village';
            $user->current_location_id = $village->id;
        }

        // Set default title
        $user->primary_title = 'peasant';
        $user->title_tier = 1;
        $user->save();

        // Create player title record
        if ($village) {
            PlayerTitle::create([
                'user_id' => $user->id,
                'title' => 'peasant',
                'tier' => PlayerTitle::TITLES['peasant'],
                'domain_type' => 'village',
                'domain_id' => $village->id,
                'acquisition_method' => 'signup',
                'is_active' => true,
                'granted_at' => now(),
            ]);
        }
    }

    /**
     * Get the weight for a specific village.
     */
    public function getVillageWeight(Village $village): int
    {
        $isCapitalVillage = $village->barony?->isCapitalBarony() ?? false;

        return $isCapitalVillage ? self::CAPITAL_VILLAGE_WEIGHT : self::REGULAR_VILLAGE_WEIGHT;
    }

    /**
     * Calculate the probability of being born in a capital village.
     */
    public function getCapitalBirthProbability(): float
    {
        $villages = Village::with(['barony.kingdom'])->get();

        if ($villages->isEmpty()) {
            return 0.0;
        }

        $totalWeight = 0;
        $capitalWeight = 0;

        foreach ($villages as $village) {
            $weight = $this->getVillageWeight($village);
            $totalWeight += $weight;

            if ($village->barony?->isCapitalBarony()) {
                $capitalWeight += $weight;
            }
        }

        return $totalWeight > 0 ? $capitalWeight / $totalWeight : 0.0;
    }
}

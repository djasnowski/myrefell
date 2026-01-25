<?php

namespace App\Services;

use App\Models\LocationNpc;
use App\Models\Role;
use App\Models\WorldState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NpcLifecycleService
{
    /**
     * Process yearly aging for all living NPCs.
     * Called when the calendar year advances.
     *
     * @return array{aged: int, died: int, replaced: int}
     */
    public function processYearlyAging(): array
    {
        $worldState = WorldState::current();
        $currentYear = $worldState->current_year;

        $results = [
            'aged' => 0,
            'died' => 0,
            'replaced' => 0,
        ];

        // Get all living NPCs that are elderly (could die this year)
        $elderlyNpcs = LocationNpc::alive()
            ->elderly($currentYear)
            ->get();

        foreach ($elderlyNpcs as $npc) {
            $deathProbability = $npc->getDeathProbability($currentYear);

            // Roll for death
            if ($this->rollForDeath($deathProbability)) {
                $this->handleNpcDeath($npc, $currentYear);
                $results['died']++;

                // If NPC was active (holding a role), replace them
                if ($npc->is_active) {
                    $replaced = $this->replaceDeadNpc($npc, $currentYear);
                    if ($replaced) {
                        $results['replaced']++;
                    }
                }
            }
        }

        // Count all living NPCs as "aged"
        $results['aged'] = LocationNpc::alive()->count();

        Log::info("NPC yearly aging processed", [
            'year' => $currentYear,
            'npcs_aged' => $results['aged'],
            'npcs_died' => $results['died'],
            'npcs_replaced' => $results['replaced'],
        ]);

        return $results;
    }

    /**
     * Roll for NPC death based on probability.
     */
    protected function rollForDeath(float $probability): bool
    {
        return mt_rand(0, 100) / 100 <= $probability;
    }

    /**
     * Handle NPC death.
     */
    protected function handleNpcDeath(LocationNpc $npc, int $deathYear): void
    {
        DB::transaction(function () use ($npc, $deathYear) {
            $npc->die($deathYear);

            Log::info("NPC died of old age", [
                'npc_id' => $npc->id,
                'npc_name' => $npc->npc_name,
                'age' => $deathYear - $npc->birth_year,
                'location_type' => $npc->location_type,
                'location_id' => $npc->location_id,
                'role' => $npc->role?->name,
            ]);
        });
    }

    /**
     * Replace a dead NPC with a new one for the same role/location.
     */
    protected function replaceDeadNpc(LocationNpc $deadNpc, int $currentYear): bool
    {
        return DB::transaction(function () use ($deadNpc, $currentYear) {
            $role = $deadNpc->role;

            if (! $role) {
                return false;
            }

            // Check if a player now holds this role
            $playerHoldsRole = $deadNpc->shouldBeActive() === false;
            if ($playerHoldsRole) {
                // Player holds the role, no need to create new NPC
                return false;
            }

            // Create a new NPC to replace the dead one
            $newNpc = $this->createReplacementNpc($deadNpc, $role, $currentYear);

            Log::info("NPC replaced after death", [
                'old_npc_id' => $deadNpc->id,
                'new_npc_id' => $newNpc->id,
                'new_npc_name' => $newNpc->npc_name,
                'role' => $role->name,
                'location_type' => $deadNpc->location_type,
                'location_id' => $deadNpc->location_id,
            ]);

            return true;
        });
    }

    /**
     * Create a new NPC to replace a dead one.
     */
    protected function createReplacementNpc(LocationNpc $deadNpc, Role $role, int $currentYear): LocationNpc
    {
        // New NPC should be an adult (age 20-40)
        $age = rand(20, 40);
        $birthYear = $currentYear - $age;

        $familyName = LocationNpc::generateFamilyName();
        $npcName = LocationNpc::generateNpcName($role->slug);

        return LocationNpc::create([
            'role_id' => $role->id,
            'location_type' => $deadNpc->location_type,
            'location_id' => $deadNpc->location_id,
            'npc_name' => $npcName,
            'family_name' => $familyName,
            'npc_description' => $deadNpc->npc_description,
            'npc_icon' => $deadNpc->npc_icon,
            'is_active' => true,
            'birth_year' => $birthYear,
            'death_year' => null,
            'personality_traits' => LocationNpc::generatePersonalityTraits(),
        ]);
    }

    /**
     * Create a new NPC for a role at a location.
     */
    public function createNpcForRole(Role $role, string $locationType, int $locationId, ?int $currentYear = null): LocationNpc
    {
        $currentYear = $currentYear ?? WorldState::current()->current_year;

        // NPC age between 25-50
        $age = rand(25, 50);
        $birthYear = $currentYear - $age;

        $familyName = LocationNpc::generateFamilyName();
        $npcName = LocationNpc::generateNpcName($role->slug);

        return LocationNpc::create([
            'role_id' => $role->id,
            'location_type' => $locationType,
            'location_id' => $locationId,
            'npc_name' => $npcName,
            'family_name' => $familyName,
            'npc_description' => "The local {$role->name}.",
            'npc_icon' => $role->icon ?? 'user',
            'is_active' => true,
            'birth_year' => $birthYear,
            'death_year' => null,
            'personality_traits' => LocationNpc::generatePersonalityTraits(),
        ]);
    }

    /**
     * Get statistics about living NPCs.
     */
    public function getNpcStatistics(): array
    {
        $currentYear = WorldState::current()->current_year;

        $living = LocationNpc::alive()->count();
        $dead = LocationNpc::dead()->count();
        $elderly = LocationNpc::alive()->elderly($currentYear)->count();
        $active = LocationNpc::alive()->active()->count();

        return [
            'living' => $living,
            'dead' => $dead,
            'elderly' => $elderly,
            'active' => $active,
            'current_year' => $currentYear,
        ];
    }

    /**
     * Initialize lifecycle fields for existing NPCs that don't have them.
     */
    public function initializeExistingNpcs(): int
    {
        $currentYear = WorldState::current()->current_year;
        $updated = 0;

        LocationNpc::whereNull('birth_year')
            ->orWhere('birth_year', 0)
            ->chunk(100, function ($npcs) use ($currentYear, &$updated) {
                foreach ($npcs as $npc) {
                    // Assign random age 25-50
                    $age = rand(25, 50);
                    $birthYear = max(1, $currentYear - $age);

                    $npc->update([
                        'birth_year' => $birthYear,
                        'family_name' => $npc->family_name ?? LocationNpc::generateFamilyName(),
                        'personality_traits' => $npc->personality_traits ?? LocationNpc::generatePersonalityTraits(),
                    ]);

                    $updated++;
                }
            });

        Log::info("Initialized lifecycle fields for existing NPCs", [
            'npcs_updated' => $updated,
        ]);

        return $updated;
    }
}

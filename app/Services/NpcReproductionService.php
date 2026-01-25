<?php

namespace App\Services;

use App\Models\LocationNpc;
use App\Models\WorldState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NpcReproductionService
{
    /**
     * Base probability of having a child per year for a married couple.
     */
    public const BASE_FERTILITY_RATE = 0.3;

    /**
     * Probability reduction per existing child.
     */
    public const CHILD_PENALTY = 0.05;

    /**
     * Maximum children per couple.
     */
    public const MAX_CHILDREN = 6;

    /**
     * Process yearly reproduction for all eligible NPCs.
     * Called when the calendar year advances.
     *
     * @return array{marriages: int, births: int}
     */
    public function processYearlyReproduction(): array
    {
        $worldState = WorldState::current();
        $currentYear = $worldState->current_year;

        $results = [
            'marriages' => 0,
            'births' => 0,
        ];

        // First, process new marriages
        $results['marriages'] = $this->processMarriages($currentYear);

        // Then, process births for married couples
        $results['births'] = $this->processBirths($currentYear);

        Log::info('NPC yearly reproduction processed', [
            'year' => $currentYear,
            'new_marriages' => $results['marriages'],
            'new_births' => $results['births'],
        ]);

        return $results;
    }

    /**
     * Process marriages for unmarried adult NPCs.
     */
    protected function processMarriages(int $currentYear): int
    {
        $marriagesCreated = 0;

        // Get all unmarried, living, adult female NPCs
        $eligibleFemales = LocationNpc::alive()
            ->unmarried()
            ->where('gender', 'female')
            ->ofReproductiveAge($currentYear)
            ->get();

        foreach ($eligibleFemales as $female) {
            // Find a compatible unmarried male at the same location
            $eligibleMale = LocationNpc::alive()
                ->unmarried()
                ->where('gender', 'male')
                ->where('location_type', $female->location_type)
                ->where('location_id', $female->location_id)
                ->where('id', '!=', $female->id)
                ->ofReproductiveAge($currentYear)
                // Avoid marrying siblings
                ->where(function ($query) use ($female) {
                    $query->whereNull('parent1_id')
                        ->orWhere(function ($q) use ($female) {
                            $q->where('parent1_id', '!=', $female->parent1_id)
                                ->orWhereNull('parent1_id');
                        });
                })
                ->inRandomOrder()
                ->first();

            if ($eligibleMale) {
                $this->createMarriage($female, $eligibleMale);
                $marriagesCreated++;
            }
        }

        return $marriagesCreated;
    }

    /**
     * Create a marriage between two NPCs.
     */
    protected function createMarriage(LocationNpc $npc1, LocationNpc $npc2): void
    {
        DB::transaction(function () use ($npc1, $npc2) {
            $npc1->marry($npc2);

            Log::info('NPC marriage created', [
                'npc1_id' => $npc1->id,
                'npc1_name' => $npc1->npc_name,
                'npc2_id' => $npc2->id,
                'npc2_name' => $npc2->npc_name,
                'location_type' => $npc1->location_type,
                'location_id' => $npc1->location_id,
            ]);
        });
    }

    /**
     * Process births for married couples.
     */
    protected function processBirths(int $currentYear): int
    {
        $birthsCreated = 0;

        // Get all married females who can reproduce
        $eligibleMothers = LocationNpc::alive()
            ->married()
            ->where('gender', 'female')
            ->canReproduce($currentYear)
            ->with('spouse')
            ->get();

        foreach ($eligibleMothers as $mother) {
            $father = $mother->spouse;

            // Check if father is alive and can reproduce
            if (! $father || ! $father->isAlive() || ! $father->canHaveChild($currentYear)) {
                continue;
            }

            // Check if couple already has max children
            $childCount = $this->getChildCount($mother, $father);
            if ($childCount >= self::MAX_CHILDREN) {
                continue;
            }

            // Calculate fertility probability
            $fertility = $this->calculateFertilityRate($mother, $father, $currentYear, $childCount);

            // Roll for birth
            if ($this->rollForBirth($fertility)) {
                $this->createChild($mother, $father, $currentYear);
                $birthsCreated++;
            }
        }

        return $birthsCreated;
    }

    /**
     * Get the number of children a couple has together.
     */
    protected function getChildCount(LocationNpc $parent1, LocationNpc $parent2): int
    {
        return LocationNpc::where(function ($query) use ($parent1, $parent2) {
            $query->where(function ($q) use ($parent1, $parent2) {
                $q->where('parent1_id', $parent1->id)
                    ->where('parent2_id', $parent2->id);
            })->orWhere(function ($q) use ($parent1, $parent2) {
                $q->where('parent1_id', $parent2->id)
                    ->where('parent2_id', $parent1->id);
            });
        })->count();
    }

    /**
     * Calculate fertility rate based on various factors.
     */
    protected function calculateFertilityRate(
        LocationNpc $mother,
        LocationNpc $father,
        int $currentYear,
        int $existingChildren
    ): float {
        $rate = self::BASE_FERTILITY_RATE;

        // Reduce rate for existing children
        $rate -= $existingChildren * self::CHILD_PENALTY;

        // Age factor for mother (peaks at 25, decreases after 35)
        $motherAge = $mother->getAge($currentYear);
        if ($motherAge > 35) {
            $rate *= max(0.2, 1 - (($motherAge - 35) * 0.08));
        }

        // Personality affects fertility slightly
        if ($mother->hasTrait('content') || $father->hasTrait('content')) {
            $rate *= 1.1;
        }
        if ($mother->hasTrait('ambitious') || $father->hasTrait('ambitious')) {
            $rate *= 0.9; // Ambitious NPCs focus more on career
        }

        return max(0.05, min(0.5, $rate));
    }

    /**
     * Roll for birth based on probability.
     */
    protected function rollForBirth(float $probability): bool
    {
        return mt_rand(0, 100) / 100 <= $probability;
    }

    /**
     * Create a child NPC.
     */
    protected function createChild(LocationNpc $mother, LocationNpc $father, int $currentYear): LocationNpc
    {
        return DB::transaction(function () use ($mother, $father, $currentYear) {
            // Determine gender randomly
            $gender = rand(0, 1) === 0 ? 'male' : 'female';

            // Child inherits family name from father (medieval tradition)
            $familyName = $father->family_name ?? LocationNpc::generateFamilyName();

            // Generate first name
            $firstName = LocationNpc::generateFirstName($gender);

            // Inherit personality traits (mix from parents or random)
            $traits = $this->inheritTraits($mother, $father);

            // Create the child NPC (born this year, no role initially)
            $child = LocationNpc::create([
                'role_id' => null, // Children don't hold roles
                'location_type' => $mother->location_type,
                'location_id' => $mother->location_id,
                'npc_name' => $firstName,
                'family_name' => $familyName,
                'gender' => $gender,
                'parent1_id' => $mother->id,
                'parent2_id' => $father->id,
                'npc_description' => "Child of {$mother->npc_name} and {$father->npc_name}.",
                'npc_icon' => 'user',
                'is_active' => false, // Children are not active role holders
                'birth_year' => $currentYear,
                'death_year' => null,
                'personality_traits' => $traits,
            ]);

            // Update parents' last birth year
            $mother->update(['last_birth_year' => $currentYear]);
            $father->update(['last_birth_year' => $currentYear]);

            Log::info('NPC child born', [
                'child_id' => $child->id,
                'child_name' => "{$firstName} {$familyName}",
                'gender' => $gender,
                'mother_id' => $mother->id,
                'mother_name' => $mother->npc_name,
                'father_id' => $father->id,
                'father_name' => $father->npc_name,
                'location_type' => $mother->location_type,
                'location_id' => $mother->location_id,
            ]);

            return $child;
        });
    }

    /**
     * Inherit personality traits from parents.
     */
    protected function inheritTraits(LocationNpc $parent1, LocationNpc $parent2): array
    {
        $parentTraits = array_merge(
            $parent1->personality_traits ?? [],
            $parent2->personality_traits ?? []
        );
        $parentTraits = array_unique($parentTraits);

        // 50% chance to inherit from parents, 50% chance for random
        if (! empty($parentTraits) && rand(0, 1) === 0) {
            shuffle($parentTraits);
            $count = rand(1, min(2, count($parentTraits)));

            return array_slice($parentTraits, 0, $count);
        }

        return LocationNpc::generatePersonalityTraits();
    }

    /**
     * Get reproduction statistics.
     */
    public function getReproductionStatistics(): array
    {
        $currentYear = WorldState::current()->current_year;

        return [
            'total_living' => LocationNpc::alive()->count(),
            'married_couples' => (int) (LocationNpc::alive()->married()->count() / 2),
            'eligible_for_reproduction' => LocationNpc::canReproduce($currentYear)->count(),
            'children_born_this_year' => LocationNpc::where('birth_year', $currentYear)->count(),
            'current_year' => $currentYear,
        ];
    }

    /**
     * Initialize gender for existing NPCs that don't have it set.
     */
    public function initializeExistingNpcs(): int
    {
        $updated = 0;

        LocationNpc::where('gender', 'male')
            ->whereNull('spouse_id')
            ->chunk(100, function ($npcs) use (&$updated) {
                foreach ($npcs as $npc) {
                    // Randomly assign gender if still default
                    if ($npc->gender === 'male' && rand(0, 1) === 1) {
                        $npc->update(['gender' => 'female']);
                        $updated++;
                    }
                }
            });

        Log::info('Initialized reproduction fields for existing NPCs', [
            'npcs_updated' => $updated,
        ]);

        return $updated;
    }
}

<?php

namespace Database\Factories;

use App\Models\LocationNpc;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LocationNpc>
 */
class LocationNpcFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_id' => Role::factory(),
            'location_type' => 'village',
            'location_id' => 1,
            'npc_name' => 'Elder Ironforge',
            'family_name' => 'Ironforge',
            'gender' => $this->faker->randomElement(['male', 'female']),
            'spouse_id' => null,
            'parent1_id' => null,
            'parent2_id' => null,
            'last_birth_year' => null,
            'npc_description' => 'The village elder.',
            'npc_icon' => 'crown',
            'is_active' => true,
            'birth_year' => 1,
            'death_year' => null,
            'personality_traits' => ['ambitious'],
        ];
    }

    /**
     * Create an NPC with a specific age.
     */
    public function age(int $age, int $currentYear = 50): static
    {
        return $this->state(fn (array $attributes) => [
            'birth_year' => $currentYear - $age,
        ]);
    }

    /**
     * Create an elderly NPC (50+ years old).
     */
    public function elderly(int $currentYear = 50): static
    {
        $age = rand(LocationNpc::MIN_DEATH_AGE, LocationNpc::MAX_DEATH_AGE - 5);

        return $this->age($age, $currentYear);
    }

    /**
     * Create a young adult NPC (20-30 years old).
     */
    public function youngAdult(int $currentYear = 50): static
    {
        return $this->age(rand(20, 30), $currentYear);
    }

    /**
     * Create a dead NPC.
     */
    public function dead(int $deathYear = 45): static
    {
        return $this->state(fn (array $attributes) => [
            'death_year' => $deathYear,
            'is_active' => false,
        ]);
    }

    /**
     * Create an inactive NPC.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create an NPC with specific personality traits.
     */
    public function withTraits(array $traits): static
    {
        return $this->state(fn (array $attributes) => [
            'personality_traits' => $traits,
        ]);
    }

    /**
     * Create a male NPC.
     */
    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => 'male',
        ]);
    }

    /**
     * Create a female NPC.
     */
    public function female(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => 'female',
        ]);
    }

    /**
     * Create an NPC of reproductive age (18-45).
     */
    public function ofReproductiveAge(int $currentYear = 50): static
    {
        $age = rand(LocationNpc::MIN_REPRODUCTION_AGE, LocationNpc::MAX_REPRODUCTION_AGE - 5);

        return $this->age($age, $currentYear);
    }

    /**
     * Create an NPC with a spouse.
     */
    public function withSpouse(LocationNpc $spouse): static
    {
        return $this->state(fn (array $attributes) => [
            'spouse_id' => $spouse->id,
        ]);
    }

    /**
     * Create an NPC with parents.
     */
    public function withParents(LocationNpc $parent1, LocationNpc $parent2): static
    {
        return $this->state(fn (array $attributes) => [
            'parent1_id' => $parent1->id,
            'parent2_id' => $parent2->id,
        ]);
    }
}

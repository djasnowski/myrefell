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
}

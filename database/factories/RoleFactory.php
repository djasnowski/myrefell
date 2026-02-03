<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uniqueId = $this->faker->unique()->uuid();

        return [
            'name' => 'Elder',
            'slug' => 'elder-'.$uniqueId,
            'icon' => 'crown',
            'description' => 'The village elder.',
            'location_type' => 'village',
            'permissions' => ['approve_migration'],
            'bonuses' => [],
            'salary' => 10,
            'tier' => 1,
            'is_elected' => true,
            'is_active' => true,
            'max_per_location' => 1,
        ];
    }

    /**
     * Create a blacksmith role.
     */
    public function blacksmith(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Blacksmith',
            'slug' => 'blacksmith',
            'icon' => 'hammer',
            'description' => 'The village blacksmith.',
            'is_elected' => false,
        ]);
    }

    /**
     * Create a baron role.
     */
    public function baron(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Baron',
            'slug' => 'baron',
            'icon' => 'crown',
            'description' => 'The ruling baron.',
            'location_type' => 'barony',
            'tier' => 3,
            'salary' => 50,
        ]);
    }
}

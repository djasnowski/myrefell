<?php

namespace Database\Factories;

use App\Models\Barony;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Village>
 */
class VillageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $biomes = ['plains', 'forest', 'tundra', 'coastal', 'desert', 'mountains'];

        return [
            'name' => fake()->unique()->city(),
            'description' => fake()->sentence(),
            'barony_id' => Barony::factory(),
            'is_town' => false,
            'is_port' => fake()->boolean(20),
            'population' => fake()->numberBetween(50, 500),
            'wealth' => fake()->numberBetween(100, 5000),
            'biome' => fake()->randomElement($biomes),
            'coordinates_x' => fake()->numberBetween(0, 100),
            'coordinates_y' => fake()->numberBetween(0, 100),
        ];
    }

    public function port(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_port' => true,
            'biome' => 'coastal',
        ]);
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kingdom>
 */
class KingdomFactory extends Factory
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
            'name' => fake()->unique()->city().' Kingdom',
            'description' => fake()->sentence(),
            'biome' => fake()->randomElement($biomes),
            'tax_rate' => fake()->numberBetween(5, 25),
            'coordinates_x' => fake()->numberBetween(0, 100),
            'coordinates_y' => fake()->numberBetween(0, 100),
        ];
    }
}

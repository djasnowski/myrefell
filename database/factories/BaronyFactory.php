<?php

namespace Database\Factories;

use App\Models\Kingdom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Barony>
 */
class BaronyFactory extends Factory
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
            'name' => fake()->unique()->city().' Barony',
            'description' => fake()->sentence(),
            'kingdom_id' => Kingdom::factory(),
            'biome' => fake()->randomElement($biomes),
            'tax_rate' => fake()->numberBetween(5, 15),
            'coordinates_x' => fake()->numberBetween(0, 100),
            'coordinates_y' => fake()->numberBetween(0, 100),
        ];
    }
}

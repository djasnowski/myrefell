<?php

namespace Database\Factories;

use App\Models\Barony;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Town>
 */
class TownFactory extends Factory
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
            'is_capital' => false,
            'is_port' => fake()->boolean(30),
            'population' => fake()->numberBetween(500, 5000),
            'wealth' => fake()->numberBetween(1000, 50000),
            'biome' => fake()->randomElement($biomes),
            'tax_rate' => fake()->numberBetween(5, 15),
            'coordinates_x' => fake()->numberBetween(0, 100),
            'coordinates_y' => fake()->numberBetween(0, 100),
        ];
    }

    public function capital(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_capital' => true,
        ]);
    }

    public function port(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_port' => true,
            'biome' => 'coastal',
        ]);
    }
}

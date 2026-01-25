<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorldState>
 */
class WorldStateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'current_year' => 1,
            'current_season' => 'spring',
            'current_week' => 1,
            'last_tick_at' => now(),
        ];
    }

    /**
     * Set the season to spring.
     */
    public function spring(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_season' => 'spring',
        ]);
    }

    /**
     * Set the season to summer.
     */
    public function summer(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_season' => 'summer',
        ]);
    }

    /**
     * Set the season to autumn.
     */
    public function autumn(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_season' => 'autumn',
        ]);
    }

    /**
     * Set the season to winter.
     */
    public function winter(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_season' => 'winter',
        ]);
    }
}

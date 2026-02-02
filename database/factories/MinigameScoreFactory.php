<?php

namespace Database\Factories;

use App\Models\MinigameScore;
use App\Models\User;
use App\Models\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MinigameScore>
 */
class MinigameScoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'minigame' => fake()->randomElement(['archery', 'jousting', 'darts']),
            'score' => fake()->numberBetween(0, 1000),
            'location_type' => MinigameScore::LOCATION_VILLAGE,
            'location_id' => Village::factory(),
            'played_at' => now(),
        ];
    }

    /**
     * Set a specific minigame.
     */
    public function forMinigame(string $minigame): static
    {
        return $this->state(fn (array $attributes) => [
            'minigame' => $minigame,
        ]);
    }

    /**
     * Set the score to a specific value.
     */
    public function withScore(int $score): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $score,
        ]);
    }

    /**
     * Set the location.
     */
    public function atLocation(string $locationType, int $locationId): static
    {
        return $this->state(fn (array $attributes) => [
            'location_type' => $locationType,
            'location_id' => $locationId,
        ]);
    }

    /**
     * Set as played today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'played_at' => now(),
        ]);
    }

    /**
     * Set as played on a specific date.
     */
    public function playedAt(\DateTimeInterface $date): static
    {
        return $this->state(fn (array $attributes) => [
            'played_at' => $date,
        ]);
    }
}

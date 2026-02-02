<?php

namespace Database\Factories;

use App\Models\MinigameReward;
use App\Models\User;
use App\Models\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MinigameReward>
 */
class MinigameRewardFactory extends Factory
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
            'reward_type' => MinigameReward::TYPE_DAILY,
            'rank' => fake()->numberBetween(1, 10),
            'location_type' => MinigameReward::LOCATION_VILLAGE,
            'location_id' => Village::factory(),
            'gold_amount' => fake()->numberBetween(10, 1000),
            'item_id' => null,
            'item_rarity' => null,
            'period_start' => today(),
            'period_end' => today(),
            'collected_at' => null,
        ];
    }

    /**
     * Mark the reward as collected.
     */
    public function collected(): static
    {
        return $this->state(fn (array $attributes) => [
            'collected_at' => now(),
        ]);
    }

    /**
     * Mark as a daily reward.
     */
    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'reward_type' => MinigameReward::TYPE_DAILY,
            'period_start' => today(),
            'period_end' => today(),
        ]);
    }

    /**
     * Mark as a weekly reward.
     */
    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'reward_type' => MinigameReward::TYPE_WEEKLY,
            'period_start' => now()->startOfWeek()->toDateString(),
            'period_end' => now()->endOfWeek()->toDateString(),
        ]);
    }

    /**
     * Mark as a monthly reward.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'reward_type' => MinigameReward::TYPE_MONTHLY,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
        ]);
    }

    /**
     * Set a specific rank.
     */
    public function withRank(int $rank): static
    {
        return $this->state(fn (array $attributes) => [
            'rank' => $rank,
        ]);
    }

    /**
     * Add an item reward.
     */
    public function withItem(int $itemId, string $rarity): static
    {
        return $this->state(fn (array $attributes) => [
            'item_id' => $itemId,
            'item_rarity' => $rarity,
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
     * First place reward (legendary item).
     */
    public function firstPlace(): static
    {
        return $this->state(fn (array $attributes) => [
            'rank' => 1,
            'gold_amount' => 1000,
            'item_rarity' => MinigameReward::RARITY_LEGENDARY,
        ]);
    }

    /**
     * Second place reward (epic item).
     */
    public function secondPlace(): static
    {
        return $this->state(fn (array $attributes) => [
            'rank' => 2,
            'gold_amount' => 500,
            'item_rarity' => MinigameReward::RARITY_EPIC,
        ]);
    }

    /**
     * Third place reward (rare item).
     */
    public function thirdPlace(): static
    {
        return $this->state(fn (array $attributes) => [
            'rank' => 3,
            'gold_amount' => 250,
            'item_rarity' => MinigameReward::RARITY_RARE,
        ]);
    }
}

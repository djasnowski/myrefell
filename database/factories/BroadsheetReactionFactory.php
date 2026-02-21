<?php

namespace Database\Factories;

use App\Models\Broadsheet;
use App\Models\BroadsheetReaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BroadsheetReaction>
 */
class BroadsheetReactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'broadsheet_id' => Broadsheet::factory(),
            'user_id' => User::factory(),
            'type' => fake()->randomElement([BroadsheetReaction::TYPE_ENDORSE, BroadsheetReaction::TYPE_DENOUNCE]),
        ];
    }

    /**
     * Set the reaction to endorse.
     */
    public function endorse(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => BroadsheetReaction::TYPE_ENDORSE,
        ]);
    }

    /**
     * Set the reaction to denounce.
     */
    public function denounce(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => BroadsheetReaction::TYPE_DENOUNCE,
        ]);
    }
}

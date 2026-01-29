<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserBan>
 */
class UserBanFactory extends Factory
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
            'banned_by' => User::factory()->state(['is_admin' => true]),
            'reason' => fake()->sentence(),
            'banned_at' => now(),
            'unbanned_at' => null,
            'unbanned_by' => null,
            'unban_reason' => null,
        ];
    }

    /**
     * Indicate that the ban has been lifted.
     */
    public function unbanned(): static
    {
        return $this->state(fn (array $attributes) => [
            'unbanned_at' => now(),
            'unbanned_by' => User::factory()->state(['is_admin' => true]),
            'unban_reason' => fake()->sentence(),
        ]);
    }
}

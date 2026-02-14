<?php

namespace Database\Factories;

use App\Models\PlayerRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RolePetition>
 */
class RolePetitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'petitioner_id' => User::factory(),
            'target_player_role_id' => PlayerRole::factory(),
            'authority_user_id' => User::factory(),
            'authority_role_slug' => 'elder',
            'location_type' => 'village',
            'location_id' => 1,
            'status' => 'pending',
            'petition_reason' => fake()->sentence(),
            'request_appointment' => false,
            'response_message' => null,
            'responded_at' => null,
            'expires_at' => now()->addDays(7),
        ];
    }

    /**
     * Mark the petition as approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'response_message' => 'Approved.',
            'responded_at' => now(),
        ]);
    }

    /**
     * Mark the petition as denied.
     */
    public function denied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'denied',
            'response_message' => 'Denied.',
            'responded_at' => now(),
        ]);
    }

    /**
     * Mark as expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}

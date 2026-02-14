<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlayerMail>
 */
class PlayerMailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'recipient_id' => User::factory(),
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'is_read' => false,
            'read_at' => null,
            'is_deleted_by_sender' => false,
            'is_deleted_by_recipient' => false,
            'gold_cost' => 0,
            'is_carrier_pigeon' => false,
        ];
    }

    /**
     * Mark the mail as read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark as carrier pigeon mail.
     */
    public function carrierPigeon(): static
    {
        return $this->state(fn (array $attributes) => [
            'gold_cost' => 300,
            'is_carrier_pigeon' => true,
        ]);
    }
}

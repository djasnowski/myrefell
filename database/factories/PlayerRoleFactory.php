<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlayerRole>
 */
class PlayerRoleFactory extends Factory
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
            'role_id' => Role::factory(),
            'location_type' => 'village',
            'location_id' => 1,
            'status' => 'active',
            'appointed_at' => now(),
            'expires_at' => null,
            'total_salary_earned' => 0,
            'legitimacy' => 50,
            'months_in_office' => 0,
        ];
    }
}

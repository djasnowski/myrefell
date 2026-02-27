<?php

namespace Database\Factories;

use App\Models\Kingdom;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shop>
 */
class ShopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Shop';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'npc_name' => fake()->name(),
            'npc_description' => fake()->sentence(),
            'description' => fake()->sentence(),
            'location_type' => 'kingdom',
            'location_id' => Kingdom::factory(),
            'icon' => 'shopping-bag',
            'map_position_x' => fake()->numberBetween(20, 80),
            'map_position_y' => fake()->numberBetween(20, 80),
            'is_active' => true,
        ];
    }

    /**
     * Set the shop's location.
     */
    public function atLocation(string $locationType, int $locationId): static
    {
        return $this->state(fn () => [
            'location_type' => $locationType,
            'location_id' => $locationId,
        ]);
    }

    /**
     * Mark the shop as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}

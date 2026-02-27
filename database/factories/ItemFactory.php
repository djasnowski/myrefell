<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(Item::TYPES),
            'rarity' => 'common',
            'stackable' => true,
            'max_stack' => 100,
            'base_value' => fake()->numberBetween(10, 1000),
            'is_tradeable' => true,
        ];
    }
}

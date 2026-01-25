<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MarketPrice>
 */
class MarketPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $basePrice = fake()->numberBetween(10, 100);

        return [
            'location_type' => 'village',
            'location_id' => 1,
            'item_id' => Item::factory(),
            'base_price' => $basePrice,
            'current_price' => $basePrice,
            'supply_quantity' => fake()->numberBetween(0, 100),
            'demand_level' => 50,
            'seasonal_modifier' => 1.00,
            'supply_modifier' => 1.00,
            'last_updated_at' => now(),
        ];
    }

    /**
     * Set for a specific location.
     */
    public function atLocation(string $type, int $id): static
    {
        return $this->state(fn (array $attributes) => [
            'location_type' => $type,
            'location_id' => $id,
        ]);
    }

    /**
     * Set high supply (lower prices).
     */
    public function highSupply(): static
    {
        return $this->state(fn (array $attributes) => [
            'supply_quantity' => fake()->numberBetween(100, 200),
            'supply_modifier' => 0.7,
        ]);
    }

    /**
     * Set low supply (higher prices).
     */
    public function lowSupply(): static
    {
        return $this->state(fn (array $attributes) => [
            'supply_quantity' => fake()->numberBetween(0, 10),
            'supply_modifier' => 1.3,
        ]);
    }
}

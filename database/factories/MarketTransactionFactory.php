<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\MarketTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MarketTransaction>
 */
class MarketTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pricePerUnit = fake()->numberBetween(10, 100);
        $quantity = fake()->numberBetween(1, 10);

        return [
            'user_id' => User::factory(),
            'location_type' => 'village',
            'location_id' => 1,
            'item_id' => Item::factory(),
            'type' => fake()->randomElement([MarketTransaction::TYPE_BUY, MarketTransaction::TYPE_SELL]),
            'quantity' => $quantity,
            'price_per_unit' => $pricePerUnit,
            'total_gold' => $pricePerUnit * $quantity,
        ];
    }

    /**
     * Set as a buy transaction.
     */
    public function buy(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => MarketTransaction::TYPE_BUY,
        ]);
    }

    /**
     * Set as a sell transaction.
     */
    public function sell(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => MarketTransaction::TYPE_SELL,
        ]);
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
}

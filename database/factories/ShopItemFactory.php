<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShopItem>
 */
class ShopItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'item_id' => Item::factory(),
            'price' => fake()->numberBetween(100, 100000),
            'stock_quantity' => null,
            'max_stock' => null,
            'restock_hours' => null,
            'last_restocked_at' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    /**
     * Set limited stock.
     */
    public function limitedStock(int $quantity, int $restockHours = 24): static
    {
        return $this->state(fn () => [
            'stock_quantity' => $quantity,
            'max_stock' => $quantity,
            'restock_hours' => $restockHours,
            'last_restocked_at' => now(),
        ]);
    }
}

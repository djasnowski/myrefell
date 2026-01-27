<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\LocationStockpile;
use App\Models\Village;
use Illuminate\Database\Seeder;

class MarketStockSeeder extends Seeder
{
    /**
     * Seed initial market stock for all villages.
     */
    public function run(): void
    {
        $villages = Village::all();

        // Get tradeable items
        $resources = Item::where('type', 'resource')->get();
        $consumables = Item::where('type', 'consumable')->get();
        $tools = Item::where('type', 'tool')->get();

        foreach ($villages as $village) {
            // Skip hamlets - they use parent village market
            if ($village->isHamlet()) {
                continue;
            }

            // Add resources based on village biome
            foreach ($resources as $item) {
                $quantity = $this->getResourceQuantity($item, $village);
                if ($quantity > 0) {
                    $this->addStock($village, $item->id, $quantity);
                }
            }

            // Add some consumables (food)
            foreach ($consumables as $item) {
                $quantity = $this->getConsumableQuantity($item, $village);
                if ($quantity > 0) {
                    $this->addStock($village, $item->id, $quantity);
                }
            }

            // Add basic tools
            foreach ($tools as $item) {
                $quantity = $this->getToolQuantity($item, $village);
                if ($quantity > 0) {
                    $this->addStock($village, $item->id, $quantity);
                }
            }
        }

        $this->command->info('Market stock seeded for ' . $villages->count() . ' villages.');
    }

    /**
     * Get resource quantity based on biome and village size.
     */
    protected function getResourceQuantity(Item $item, Village $village): int
    {
        $baseQuantity = rand(20, 50);
        $name = strtolower($item->name);

        // Biome-specific bonuses
        $biome = $village->biome ?? 'temperate';

        $biomeBonus = match (true) {
            // Forest biomes have more wood
            str_contains($biome, 'forest') && str_contains($name, 'wood') => 2.0,
            str_contains($biome, 'forest') && str_contains($name, 'log') => 2.0,

            // Coastal/river have more fish
            str_contains($biome, 'coastal') && str_contains($name, 'fish') => 2.5,
            str_contains($biome, 'river') && str_contains($name, 'fish') => 1.5,

            // Mountain/hills have more ore
            str_contains($biome, 'mountain') && str_contains($name, 'ore') => 2.0,
            str_contains($biome, 'hill') && str_contains($name, 'ore') => 1.5,

            // Plains have more grain/wheat
            str_contains($biome, 'plain') && str_contains($name, 'grain') => 2.0,
            str_contains($biome, 'plain') && str_contains($name, 'wheat') => 2.0,

            default => 1.0,
        };

        // Population bonus
        $populationMultiplier = 1 + ($village->population / 500);

        return (int) round($baseQuantity * $biomeBonus * $populationMultiplier);
    }

    /**
     * Get consumable quantity (food items).
     */
    protected function getConsumableQuantity(Item $item, Village $village): int
    {
        $baseQuantity = rand(10, 30);
        $populationMultiplier = 1 + ($village->population / 500);

        return (int) round($baseQuantity * $populationMultiplier);
    }

    /**
     * Get tool quantity.
     */
    protected function getToolQuantity(Item $item, Village $village): int
    {
        // Tools are less common
        return rand(3, 10);
    }

    /**
     * Add stock to a village's market.
     */
    protected function addStock(Village $village, int $itemId, int $quantity): void
    {
        $stockpile = LocationStockpile::getOrCreate('village', $village->id, $itemId);
        $stockpile->addQuantity($quantity);
    }
}

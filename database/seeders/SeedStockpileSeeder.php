<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\LocationStockpile;
use App\Models\Town;
use App\Models\Village;
use Illuminate\Database\Seeder;

class SeedStockpileSeeder extends Seeder
{
    /**
     * Seeds to stock in village/town markets.
     * Format: 'Seed Name' => ['village' => qty, 'town' => qty]
     */
    private const SEED_STOCK = [
        // Common crops - high stock
        'Wheat Seeds' => ['village' => 50, 'town' => 100],
        'Potato Seeds' => ['village' => 40, 'town' => 80],
        'Carrot Seeds' => ['village' => 40, 'town' => 80],
        'Cabbage Seeds' => ['village' => 35, 'town' => 70],
        'Onion Seeds' => ['village' => 35, 'town' => 70],
        'Lettuce Seeds' => ['village' => 30, 'town' => 60],

        // Medium crops
        'Corn Seeds' => ['village' => 25, 'town' => 50],
        'Tomato Seeds' => ['village' => 25, 'town' => 50],
        'Cucumber Seeds' => ['village' => 20, 'town' => 40],
        'Pepper Seeds' => ['village' => 20, 'town' => 40],
        'Bean Seeds' => ['village' => 25, 'town' => 50],
        'Flax Seeds' => ['village' => 20, 'town' => 40],

        // Rare/specialty crops - lower stock
        'Pumpkin Seeds' => ['village' => 15, 'town' => 30],
        'Squash Seeds' => ['village' => 15, 'town' => 30],
        'Melon Seeds' => ['village' => 10, 'town' => 20],
        'Watermelon Seeds' => ['village' => 10, 'town' => 20],

        // Berry seeds
        'Strawberry Seeds' => ['village' => 15, 'town' => 30],
        'Blueberry Seeds' => ['village' => 12, 'town' => 25],
        'Raspberry Seeds' => ['village' => 12, 'town' => 25],

        // Special seeds - limited stock
        'Grape Seeds' => ['village' => 8, 'town' => 15],
        'Hop Seeds' => ['village' => 10, 'town' => 20],
        'Herb Seeds' => ['village' => 10, 'town' => 20],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $villagesStocked = 0;
        $townsStocked = 0;

        // Stock all villages
        $villages = Village::all();
        foreach ($villages as $village) {
            $this->stockLocation('village', $village->id, 'village');
            $villagesStocked++;
        }

        // Stock all towns
        $towns = Town::all();
        foreach ($towns as $town) {
            $this->stockLocation('town', $town->id, 'town');
            $townsStocked++;
        }

        $this->command->info("Seeds stocked in {$villagesStocked} villages and {$townsStocked} towns.");
    }

    /**
     * Stock a location with seeds.
     */
    protected function stockLocation(string $locationType, int $locationId, string $stockLevel): void
    {
        foreach (self::SEED_STOCK as $seedName => $quantities) {
            $item = Item::where('name', $seedName)->first();
            if (! $item) {
                continue;
            }

            $quantity = $quantities[$stockLevel] ?? 0;
            if ($quantity <= 0) {
                continue;
            }

            $stockpile = LocationStockpile::getOrCreate($locationType, $locationId, $item->id);

            // Only add if stockpile is low (below target)
            if ($stockpile->quantity < $quantity) {
                $stockpile->addQuantity($quantity - $stockpile->quantity);
            }
        }
    }

    /**
     * Restock seeds at a specific location (for scheduled restocking).
     * Only restocks up to 50% of the target to simulate gradual supply.
     */
    public static function restockLocation(string $locationType, int $locationId): int
    {
        $stockLevel = $locationType === 'town' ? 'town' : 'village';
        $itemsRestocked = 0;

        foreach (self::SEED_STOCK as $seedName => $quantities) {
            $item = Item::where('name', $seedName)->first();
            if (! $item) {
                continue;
            }

            $targetQuantity = $quantities[$stockLevel] ?? 0;
            if ($targetQuantity <= 0) {
                continue;
            }

            $stockpile = LocationStockpile::getOrCreate($locationType, $locationId, $item->id);

            // Only restock if below 25% of target
            $restockThreshold = (int) ($targetQuantity * 0.25);
            if ($stockpile->quantity < $restockThreshold) {
                // Add 25-50% of target quantity
                $restockAmount = rand((int) ($targetQuantity * 0.25), (int) ($targetQuantity * 0.5));
                $stockpile->addQuantity($restockAmount);
                $itemsRestocked++;
            }
        }

        return $itemsRestocked;
    }
}

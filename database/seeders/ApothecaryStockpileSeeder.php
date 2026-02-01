<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\LocationStockpile;
use App\Models\Town;
use App\Models\Village;
use Illuminate\Database\Seeder;

class ApothecaryStockpileSeeder extends Seeder
{
    /**
     * Items to stock in village/town markets for apothecary.
     */
    private const SHOP_ITEMS = [
        // Basic supplies - available everywhere
        'Vial' => ['village' => 50, 'town' => 100],
        'Crystal Vial' => ['village' => 20, 'town' => 50],
        'Holy Water' => ['village' => 15, 'town' => 30],

        // Basic healing supplies
        'Bandage' => ['village' => 30, 'town' => 60],
        'Minor Health Potion' => ['village' => 10, 'town' => 25],
        'Antidote' => ['village' => 10, 'town' => 20],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Stock all villages
        $villages = Village::all();
        foreach ($villages as $village) {
            $this->stockLocation('village', $village->id, 'village');
        }

        // Stock all towns
        $towns = Town::all();
        foreach ($towns as $town) {
            $this->stockLocation('town', $town->id, 'town');
        }

        $this->command->info('Apothecary supplies stocked in all villages and towns.');
    }

    /**
     * Stock a location with apothecary items.
     */
    protected function stockLocation(string $locationType, int $locationId, string $stockLevel): void
    {
        foreach (self::SHOP_ITEMS as $itemName => $quantities) {
            $item = Item::where('name', $itemName)->first();
            if (! $item) {
                continue;
            }

            $quantity = $quantities[$stockLevel] ?? 0;
            if ($quantity <= 0) {
                continue;
            }

            $stockpile = LocationStockpile::getOrCreate($locationType, $locationId, $item->id);

            // Only add if stockpile is low
            if ($stockpile->quantity < $quantity) {
                $stockpile->addQuantity($quantity - $stockpile->quantity);
            }
        }
    }
}

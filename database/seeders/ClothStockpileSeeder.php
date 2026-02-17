<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\LocationStockpile;
use App\Models\Town;
use App\Models\Village;
use Illuminate\Database\Seeder;

class ClothStockpileSeeder extends Seeder
{
    /**
     * Cloth stock levels — kept very scarce.
     * Only a handful of villages and towns carry any at all.
     */
    private const STOCK_CHANCE = 20; // % chance each village/town gets any stock

    private const VILLAGE_QTY = 2;

    private const TOWN_QTY = 4;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $item = Item::where('name', 'Cloth')->first();
        if (! $item) {
            $this->command->warn('Cloth item not found, skipping.');

            return;
        }

        $stocked = 0;

        foreach (Village::all() as $village) {
            if (rand(1, 100) <= self::STOCK_CHANCE) {
                $stockpile = LocationStockpile::getOrCreate('village', $village->id, $item->id);
                if ($stockpile->quantity < self::VILLAGE_QTY) {
                    $stockpile->addQuantity(self::VILLAGE_QTY - $stockpile->quantity);
                    $stocked++;
                }
            }
        }

        foreach (Town::all() as $town) {
            if (rand(1, 100) <= self::STOCK_CHANCE) {
                $stockpile = LocationStockpile::getOrCreate('town', $town->id, $item->id);
                if ($stockpile->quantity < self::TOWN_QTY) {
                    $stockpile->addQuantity(self::TOWN_QTY - $stockpile->quantity);
                    $stocked++;
                }
            }
        }

        $this->command->info("Cloth stocked in {$stocked} locations.");
    }
}

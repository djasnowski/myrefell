<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\LocationStockpile;
use App\Models\Town;
use App\Models\Village;
use Illuminate\Database\Seeder;

class CraftingToolsStockpileSeeder extends Seeder
{
    /**
     * Crafting tools to stock in village/town markets.
     * Format: 'Item Name' => ['village' => qty, 'town' => qty]
     * Kept scarce to encourage exploration and trading.
     */
    private const TOOL_STOCK = [
        // Gem cutting tool - slightly more common
        'Chisel' => ['village' => 2, 'town' => 5],

        // Jewelry moulds - scarce
        'Ring Mould' => ['village' => 1, 'town' => 3],
        'Necklace Mould' => ['village' => 1, 'town' => 3],
        'Bracelet Mould' => ['village' => 1, 'town' => 2],
        'Amulet Mould' => ['village' => 0, 'town' => 2],
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

        $this->command->info("Crafting tools stocked in {$villagesStocked} villages and {$townsStocked} towns.");
    }

    /**
     * Stock a location with crafting tools.
     */
    protected function stockLocation(string $locationType, int $locationId, string $stockLevel): void
    {
        foreach (self::TOOL_STOCK as $itemName => $quantities) {
            $item = Item::where('name', $itemName)->first();
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
     * Restock crafting tools at a specific location (for scheduled restocking).
     * Very slow restock rate due to scarcity.
     */
    public static function restockLocation(string $locationType, int $locationId): int
    {
        $stockLevel = $locationType === 'town' ? 'town' : 'village';
        $itemsRestocked = 0;

        foreach (self::TOOL_STOCK as $itemName => $quantities) {
            $item = Item::where('name', $itemName)->first();
            if (! $item) {
                continue;
            }

            $targetQuantity = $quantities[$stockLevel] ?? 0;
            if ($targetQuantity <= 0) {
                continue;
            }

            $stockpile = LocationStockpile::getOrCreate($locationType, $locationId, $item->id);

            // Only restock if completely out of stock (very scarce)
            if ($stockpile->quantity === 0) {
                // 50% chance to restock 1 item
                if (rand(1, 100) <= 50) {
                    $stockpile->addQuantity(1);
                    $itemsRestocked++;
                }
            }
        }

        return $itemsRestocked;
    }
}

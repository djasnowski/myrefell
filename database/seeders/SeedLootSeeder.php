<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Monster;
use App\Models\MonsterLootTable;
use Illuminate\Database\Seeder;

class SeedLootSeeder extends Seeder
{
    /**
     * Seed items mapped to monsters that can drop them.
     * Format: 'Seed Name' => ['monster_names' => [...], 'drop_chance' => X, 'qty_min' => Y, 'qty_max' => Z]
     */
    private const SEED_DROPS = [
        // Common seeds - low level monsters
        'Wheat Seeds' => [
            'monsters' => ['Rat', 'Goblin', 'Wolf', 'Skeleton', 'Bandit'],
            'drop_chance' => 8.0,
            'qty_min' => 1,
            'qty_max' => 3,
        ],
        'Potato Seeds' => [
            'monsters' => ['Rat', 'Goblin', 'Wolf', 'Skeleton', 'Bandit'],
            'drop_chance' => 8.0,
            'qty_min' => 1,
            'qty_max' => 3,
        ],
        'Carrot Seeds' => [
            'monsters' => ['Goblin', 'Wolf', 'Skeleton', 'Bandit', 'Zombie'],
            'drop_chance' => 7.0,
            'qty_min' => 1,
            'qty_max' => 2,
        ],
        'Cabbage Seeds' => [
            'monsters' => ['Goblin', 'Wolf', 'Bandit', 'Zombie'],
            'drop_chance' => 7.0,
            'qty_min' => 1,
            'qty_max' => 2,
        ],
        'Onion Seeds' => [
            'monsters' => ['Skeleton', 'Bandit', 'Zombie', 'Hobgoblin'],
            'drop_chance' => 6.0,
            'qty_min' => 1,
            'qty_max' => 2,
        ],
        'Lettuce Seeds' => [
            'monsters' => ['Goblin', 'Wolf', 'Bandit'],
            'drop_chance' => 6.0,
            'qty_min' => 1,
            'qty_max' => 2,
        ],

        // Medium seeds - mid level monsters
        'Corn Seeds' => [
            'monsters' => ['Zombie', 'Hobgoblin', 'Bear', 'Dark Mage'],
            'drop_chance' => 5.0,
            'qty_min' => 1,
            'qty_max' => 2,
        ],
        'Tomato Seeds' => [
            'monsters' => ['Hobgoblin', 'Bear', 'Dark Mage', 'Troll'],
            'drop_chance' => 5.0,
            'qty_min' => 1,
            'qty_max' => 2,
        ],
        'Cucumber Seeds' => [
            'monsters' => ['Bear', 'Dark Mage', 'Troll'],
            'drop_chance' => 5.0,
            'qty_min' => 1,
            'qty_max' => 2,
        ],
        'Pepper Seeds' => [
            'monsters' => ['Dark Mage', 'Troll', 'Fire Elemental'],
            'drop_chance' => 4.0,
            'qty_min' => 1,
            'qty_max' => 2,
        ],
        'Bean Seeds' => [
            'monsters' => ['Hobgoblin', 'Bear', 'Troll', 'Ogre'],
            'drop_chance' => 4.0,
            'qty_min' => 1,
            'qty_max' => 2,
        ],
        'Flax Seeds' => [
            'monsters' => ['Bandit', 'Zombie', 'Hobgoblin', 'Bear'],
            'drop_chance' => 5.0,
            'qty_min' => 1,
            'qty_max' => 2,
        ],

        // Rare seeds - higher level monsters
        'Pumpkin Seeds' => [
            'monsters' => ['Troll', 'Ice Elemental', 'Fire Elemental', 'Ogre'],
            'drop_chance' => 3.0,
            'qty_min' => 1,
            'qty_max' => 2,
        ],
        'Squash Seeds' => [
            'monsters' => ['Troll', 'Ice Elemental', 'Ogre'],
            'drop_chance' => 3.0,
            'qty_min' => 1,
            'qty_max' => 2,
        ],
        'Melon Seeds' => [
            'monsters' => ['Ogre', 'Goblin King', 'Demon'],
            'drop_chance' => 2.5,
            'qty_min' => 1,
            'qty_max' => 1,
        ],
        'Watermelon Seeds' => [
            'monsters' => ['Ogre', 'Goblin King', 'Demon', 'Wyvern'],
            'drop_chance' => 2.0,
            'qty_min' => 1,
            'qty_max' => 1,
        ],

        // Berry seeds - nature monsters
        'Strawberry Seeds' => [
            'monsters' => ['Wolf', 'Bear', 'Ice Elemental'],
            'drop_chance' => 4.0,
            'qty_min' => 1,
            'qty_max' => 2,
        ],
        'Blueberry Seeds' => [
            'monsters' => ['Bear', 'Ice Elemental', 'Troll'],
            'drop_chance' => 3.5,
            'qty_min' => 1,
            'qty_max' => 2,
        ],
        'Raspberry Seeds' => [
            'monsters' => ['Wolf', 'Bear', 'Troll'],
            'drop_chance' => 3.5,
            'qty_min' => 1,
            'qty_max' => 2,
        ],

        // Special seeds - specific monsters
        'Grape Seeds' => [
            'monsters' => ['Dark Mage', 'Demon', 'Lich'],
            'drop_chance' => 2.0,
            'qty_min' => 1,
            'qty_max' => 1,
        ],
        'Hop Seeds' => [
            'monsters' => ['Bandit', 'Hobgoblin', 'Goblin King'],
            'drop_chance' => 3.0,
            'qty_min' => 1,
            'qty_max' => 2,
        ],
        'Herb Seeds' => [
            'monsters' => ['Dark Mage', 'Lich', 'Demon'],
            'drop_chance' => 2.5,
            'qty_min' => 1,
            'qty_max' => 1,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seedsAdded = 0;

        foreach (self::SEED_DROPS as $seedName => $config) {
            $item = Item::where('name', $seedName)->first();
            if (! $item) {
                $this->command->warn("Seed item not found: {$seedName}");

                continue;
            }

            foreach ($config['monsters'] as $monsterName) {
                $monster = Monster::where('name', $monsterName)->first();
                if (! $monster) {
                    $this->command->warn("Monster not found: {$monsterName}");

                    continue;
                }

                // Check if this loot entry already exists
                $exists = MonsterLootTable::where('monster_id', $monster->id)
                    ->where('item_id', $item->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                MonsterLootTable::create([
                    'monster_id' => $monster->id,
                    'item_id' => $item->id,
                    'drop_chance' => $config['drop_chance'],
                    'quantity_min' => $config['qty_min'],
                    'quantity_max' => $config['qty_max'],
                ]);

                $seedsAdded++;
            }
        }

        $this->command->info("Added {$seedsAdded} seed loot entries to monsters.");
    }
}

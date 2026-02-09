<?php

use App\Models\Item;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $items = [
            [
                'name' => 'Limestone',
                'description' => 'A pale sedimentary stone quarried from ancient cliffs. Used as a base material for bricks and mortar.',
                'type' => 'resource',
                'subtype' => 'ore',
                'rarity' => 'common',
                'stackable' => true,
                'max_stack' => 100,
                'base_value' => 30,
            ],
            [
                'name' => 'Limestone Brick',
                'description' => 'A sturdy brick cut and shaped from limestone. Essential for fireplaces, furnaces, and stone construction.',
                'type' => 'misc',
                'subtype' => 'material',
                'rarity' => 'common',
                'stackable' => true,
                'max_stack' => 100,
                'base_value' => 100,
            ],
            [
                'name' => 'Marble Block',
                'description' => 'A polished block of white marble veined with grey. Reserved for the finest architectural works.',
                'type' => 'misc',
                'subtype' => 'material',
                'rarity' => 'uncommon',
                'stackable' => true,
                'max_stack' => 50,
                'base_value' => 5000,
            ],
            [
                'name' => 'Gold Leaf',
                'description' => 'Paper-thin sheets of hammered gold. Used to gild furniture and religious icons.',
                'type' => 'misc',
                'subtype' => 'material',
                'rarity' => 'uncommon',
                'stackable' => true,
                'max_stack' => 50,
                'base_value' => 1500,
            ],
            [
                'name' => 'Magic Stone',
                'description' => 'A shimmering stone that pulses with arcane energy. Extremely rare, found only in the deepest dungeons.',
                'type' => 'misc',
                'subtype' => 'material',
                'rarity' => 'rare',
                'stackable' => true,
                'max_stack' => 20,
                'base_value' => 15000,
            ],
        ];

        foreach ($items as $item) {
            Item::firstOrCreate(
                ['name' => $item['name']],
                $item,
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Item::whereIn('name', ['Limestone', 'Limestone Brick', 'Marble Block', 'Gold Leaf', 'Magic Stone'])->delete();
    }
};

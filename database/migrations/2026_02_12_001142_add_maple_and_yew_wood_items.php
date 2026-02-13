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
        $woods = [
            [
                'name' => 'Maple Wood',
                'description' => 'Hard, pale timber with a tight, swirling grain. Carpenters covet it for furniture that will outlast the maker.',
                'type' => 'resource',
                'subtype' => 'wood',
                'rarity' => 'uncommon',
                'stackable' => true,
                'max_stack' => 100,
                'base_value' => 25,
            ],
            [
                'name' => 'Yew Wood',
                'description' => 'Dense, reddish timber from ancient slow-growing trees. Resists rot like nothing else and takes a polish fit for royalty.',
                'type' => 'resource',
                'subtype' => 'wood',
                'rarity' => 'uncommon',
                'stackable' => true,
                'max_stack' => 100,
                'base_value' => 40,
            ],
        ];

        foreach ($woods as $wood) {
            Item::firstOrCreate(['name' => $wood['name']], $wood);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Item::whereIn('name', ['Maple Wood', 'Yew Wood'])->delete();
    }
};

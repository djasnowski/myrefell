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
        // Add Silver Ore
        Item::create([
            'name' => 'Silver Ore',
            'description' => 'A lustrous ore with a pale gleam. Silver has been prized since ancient times for its beauty and purity. Smiths value it for jewelry and ceremonial items.',
            'type' => 'resource',
            'subtype' => 'ore',
            'rarity' => 'uncommon',
            'stackable' => true,
            'max_stack' => 100,
            'base_value' => 50,
        ]);

        // Add Silver Bar
        Item::create([
            'name' => 'Silver Bar',
            'description' => 'Refined silver, bright as moonlight. This precious metal is too soft for weapons but perfect for jewelry, holy symbols, and items of great beauty.',
            'type' => 'resource',
            'subtype' => 'bar',
            'rarity' => 'uncommon',
            'stackable' => true,
            'max_stack' => 100,
            'base_value' => 75,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Item::where('name', 'Silver Ore')->delete();
        Item::where('name', 'Silver Bar')->delete();
    }
};

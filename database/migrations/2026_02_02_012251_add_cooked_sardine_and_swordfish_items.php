<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('items')->insert([
            [
                'name' => 'Cooked Sardine',
                'description' => 'A small but tasty cooked fish. Restores 5 HP when eaten.',
                'type' => 'consumable',
                'subtype' => 'food',
                'rarity' => 'common',
                'stackable' => true,
                'max_stack' => 50,
                'hp_bonus' => 5,
                'base_value' => 12,
                'is_tradeable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cooked Swordfish',
                'description' => 'A magnificent cooked swordfish. Restores 20 HP when eaten.',
                'type' => 'consumable',
                'subtype' => 'food',
                'rarity' => 'uncommon',
                'stackable' => true,
                'max_stack' => 50,
                'hp_bonus' => 20,
                'base_value' => 80,
                'is_tradeable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('items')->whereIn('name', ['Cooked Sardine', 'Cooked Swordfish'])->delete();
    }
};

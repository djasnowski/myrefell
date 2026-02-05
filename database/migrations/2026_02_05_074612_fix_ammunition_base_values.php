<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix ammunition item base values to prevent insane profits from smithing.
     * Ammunition should result in a small loss compared to bar value (like OSRS).
     */
    public function up(): void
    {
        $fixes = [
            // Arrowtips (15x per bar) - slight loss
            'Bronze Arrowtips' => 1,
            'Iron Arrowtips' => 2,
            'Steel Arrowtips' => 5,
            'Mithril Arrowtips' => 17,
            'Celestial Arrowtips' => 48,
            'Oria Arrowtips' => 90,

            // Dart Tips (10x per bar) - slight loss
            'Bronze Dart Tips' => 1,
            'Iron Dart Tips' => 3,
            'Steel Dart Tips' => 8,
            'Mithril Dart Tips' => 25,
            'Celestial Dart Tips' => 70,
            'Oria Dart Tips' => 130,

            // Javelin Tips (5x per bar) - slight loss
            'Bronze Javelin Tips' => 2,
            'Iron Javelin Tips' => 6,
            'Steel Javelin Tips' => 16,
            'Mithril Javelin Tips' => 50,
            'Celestial Javelin Tips' => 140,
            'Oria Javelin Tips' => 260,

            // Throwing Knives (5x per bar) - slight loss
            'Bronze Throwing Knives' => 2,
            'Iron Throwing Knives' => 6,
            'Steel Throwing Knives' => 16,
            'Mithril Throwing Knives' => 50,
            'Celestial Throwing Knives' => 140,
            'Oria Throwing Knives' => 260,
        ];

        foreach ($fixes as $name => $newValue) {
            DB::table('items')
                ->where('name', $name)
                ->update(['base_value' => $newValue]);
        }

        // Also reset market prices for these items so they reflect new base values
        $itemIds = DB::table('items')
            ->whereIn('name', array_keys($fixes))
            ->pluck('id');

        DB::table('market_prices')
            ->whereIn('item_id', $itemIds)
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Original values (before fix)
        $originals = [
            'Bronze Arrowtips' => 23,
            'Iron Arrowtips' => 90,
            'Steel Arrowtips' => 300,
            'Mithril Arrowtips' => 1350,
            'Celestial Arrowtips' => 4800,
            'Oria Arrowtips' => 11250,
            'Bronze Dart Tips' => 23,
            'Iron Dart Tips' => 90,
            'Steel Dart Tips' => 300,
            'Mithril Dart Tips' => 1350,
            'Celestial Dart Tips' => 4800,
            'Oria Dart Tips' => 11250,
            'Bronze Javelin Tips' => 23,
            'Iron Javelin Tips' => 90,
            'Steel Javelin Tips' => 300,
            'Mithril Javelin Tips' => 1350,
            'Celestial Javelin Tips' => 4800,
            'Oria Javelin Tips' => 11250,
            'Bronze Throwing Knives' => 23,
            'Iron Throwing Knives' => 90,
            'Steel Throwing Knives' => 300,
            'Mithril Throwing Knives' => 1350,
            'Celestial Throwing Knives' => 4800,
            'Oria Throwing Knives' => 11250,
        ];

        foreach ($originals as $name => $value) {
            DB::table('items')
                ->where('name', $name)
                ->update(['base_value' => $value]);
        }
    }
};

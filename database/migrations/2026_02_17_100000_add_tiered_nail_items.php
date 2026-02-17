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
        // Rename existing "Nails" to "Bronze Nails"
        Item::where('name', 'Nails')->update([
            'name' => 'Bronze Nails',
            'description' => 'Small bronze spikes for basic construction. Cheap, plentiful, and good enough for simple builds.',
            'base_value' => 2,
            'max_stack' => 1000,
        ]);

        // Update existing "Steel Nails" to ensure correct properties
        Item::where('name', 'Steel Nails')->update([
            'type' => 'misc',
            'subtype' => 'material',
            'stackable' => true,
            'max_stack' => 1000,
            'base_value' => 20,
        ]);

        // Create new nail tiers
        $nails = [
            [
                'name' => 'Iron Nails',
                'description' => 'Sturdy iron nails for mid-level construction. Strong enough for furniture that needs to last.',
                'type' => 'misc',
                'subtype' => 'material',
                'rarity' => 'common',
                'stackable' => true,
                'max_stack' => 1000,
                'base_value' => 6,
            ],
            [
                'name' => 'Mithril Nails',
                'description' => 'Gleaming mithril nails that never rust. Reserved for the finest craftsmanship.',
                'type' => 'misc',
                'subtype' => 'material',
                'rarity' => 'uncommon',
                'stackable' => true,
                'max_stack' => 1000,
                'base_value' => 90,
            ],
            [
                'name' => 'Celestial Nails',
                'description' => 'Nails forged from celestial metal, faintly glowing with inner light. For construction that transcends the ordinary.',
                'type' => 'misc',
                'subtype' => 'material',
                'rarity' => 'rare',
                'stackable' => true,
                'max_stack' => 1000,
                'base_value' => 320,
            ],
            [
                'name' => 'Oria Nails',
                'description' => 'The finest nails known to exist. Oria metal holds fast through earthquakes and dragon fire alike.',
                'type' => 'misc',
                'subtype' => 'material',
                'rarity' => 'epic',
                'stackable' => true,
                'max_stack' => 1000,
                'base_value' => 750,
            ],
        ];

        foreach ($nails as $nail) {
            Item::firstOrCreate(
                ['name' => $nail['name']],
                $nail
            );
        }

        // Update construction config references in player inventories:
        // Any existing "Nails" inventory entries already point to the renamed Bronze Nails item
        // (same ID), so no inventory migration needed.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename Bronze Nails back to Nails
        Item::where('name', 'Bronze Nails')->update([
            'name' => 'Nails',
            'description' => 'Small iron spikes that hold civilization together. Houses, fences, coffins—nails make them all. Never travel without spares.',
            'base_value' => 1,
            'max_stack' => 500,
        ]);

        // Delete new nail tiers
        Item::whereIn('name', ['Iron Nails', 'Mithril Nails', 'Celestial Nails', 'Oria Nails'])->delete();
    }
};

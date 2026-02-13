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
        // New wood types (resources)
        $woodTypes = [
            [
                'name' => 'Mahogany Wood',
                'description' => 'Rich dark timber from ancient tropical trees. Prized by master craftsmen for its deep color and resistance to rot.',
                'type' => 'resource',
                'subtype' => 'wood',
                'rarity' => 'uncommon',
                'stackable' => true,
                'max_stack' => 100,
                'base_value' => 50,
            ],
        ];

        // New plank items
        $planks = [
            [
                'name' => 'Plank',
                'description' => 'A rough-hewn plank of common wood. Good enough for basic construction and patching holes in roofs.',
                'type' => 'misc',
                'subtype' => 'material',
                'rarity' => 'common',
                'stackable' => true,
                'max_stack' => 100,
                'base_value' => 20,
            ],
            [
                'name' => 'Willow Plank',
                'description' => 'Flexible willow wood, carefully dried and cut. Bends without breaking, ideal for curved construction.',
                'type' => 'misc',
                'subtype' => 'material',
                'rarity' => 'common',
                'stackable' => true,
                'max_stack' => 100,
                'base_value' => 180,
            ],
            [
                'name' => 'Maple Plank',
                'description' => 'Hard maple wood planed to perfection. Its tight grain makes furniture that lasts generations.',
                'type' => 'misc',
                'subtype' => 'material',
                'rarity' => 'uncommon',
                'stackable' => true,
                'max_stack' => 100,
                'base_value' => 450,
            ],
            [
                'name' => 'Yew Plank',
                'description' => 'Dense yew timber with a warm reddish hue. Resistant to decay and favored for fine woodworking.',
                'type' => 'misc',
                'subtype' => 'material',
                'rarity' => 'uncommon',
                'stackable' => true,
                'max_stack' => 100,
                'base_value' => 1000,
            ],
            [
                'name' => 'Mahogany Plank',
                'description' => 'Exquisite mahogany lumber with a deep, lustrous finish. The mark of master construction.',
                'type' => 'misc',
                'subtype' => 'material',
                'rarity' => 'rare',
                'stackable' => true,
                'max_stack' => 100,
                'base_value' => 2500,
            ],
        ];

        foreach (array_merge($woodTypes, $planks) as $item) {
            Item::updateOrCreate(
                ['name' => $item['name']],
                $item,
            );
        }

        // Update Oak Plank base_value to 75
        Item::where('name', 'Oak Plank')->update(['base_value' => 75]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $names = ['Mahogany Wood', 'Plank', 'Willow Plank', 'Maple Plank', 'Yew Plank', 'Mahogany Plank'];
        Item::whereIn('name', $names)->delete();

        // Restore Oak Plank base_value
        Item::where('name', 'Oak Plank')->update(['base_value' => 20]);
    }
};

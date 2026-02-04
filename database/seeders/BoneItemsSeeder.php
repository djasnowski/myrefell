<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Monster;
use App\Models\MonsterLootTable;
use Illuminate\Database\Seeder;

class BoneItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define bone items with their prayer XP values
        $bones = [
            [
                'name' => 'Bones',
                'description' => 'All that remains when the soul departs. Priests use bones in rituals; alchemists grind them for reagents.',
                'prayer_bonus' => 4,
                'base_value' => 32,
            ],
            [
                'name' => 'Wolf Bones',
                'description' => 'The remains of a wolf. Slightly larger than regular bones, these hold more spiritual essence.',
                'prayer_bonus' => 5,
                'base_value' => 68,
            ],
            [
                'name' => 'Big Bones',
                'description' => 'Large bones from a powerful creature. Their size makes them valuable for religious ceremonies.',
                'prayer_bonus' => 15,
                'base_value' => 312,
            ],
            [
                'name' => 'Demon Bones',
                'description' => 'Charred bones emanating dark energy. Sacrificing these is said to please the gods greatly.',
                'prayer_bonus' => 50,
                'base_value' => 2450,
            ],
            [
                'name' => 'Lich Bones',
                'description' => 'Ancient bones infused with necromantic power. Incredibly potent for prayer rituals.',
                'prayer_bonus' => 65,
                'base_value' => 4125,
            ],
            [
                'name' => 'Wyvern Bones',
                'description' => 'Hollow yet sturdy bones from a wyvern. Their aerial origins make them prized by priests.',
                'prayer_bonus' => 85,
                'base_value' => 7680,
            ],
            [
                'name' => 'Dragon Bones',
                'description' => 'Massive bones radiating ancient power. The most sacred bones for sacrifice, revered by all religions.',
                'prayer_bonus' => 115,
                'base_value' => 13324,
            ],
        ];

        // Create or update bone items
        foreach ($bones as $boneData) {
            Item::updateOrCreate(
                ['name' => $boneData['name']],
                [
                    'description' => $boneData['description'],
                    'type' => 'misc',
                    'subtype' => 'remains',
                    'rarity' => $boneData['prayer_bonus'] >= 50 ? 'uncommon' : 'common',
                    'stackable' => true,
                    'max_stack' => 100,
                    'base_value' => $boneData['base_value'],
                    'prayer_bonus' => $boneData['prayer_bonus'],
                    'is_tradeable' => true,
                ]
            );
        }

        // Map monsters to their bone drops
        $monsterBones = [
            // Regular bones (4 XP)
            'Rat' => 'Bones',
            'Goblin' => 'Bones',
            'Skeleton' => 'Bones',
            'Bandit' => 'Bones',
            'Zombie' => 'Bones',
            'Hobgoblin' => 'Bones',
            'Dark Mage' => 'Bones',

            // Wolf bones (5 XP)
            'Wolf' => 'Wolf Bones',

            // Big bones (15 XP)
            'Bear' => 'Big Bones',
            'Troll' => 'Big Bones',
            'Goblin King' => 'Big Bones',
            'Ogre' => 'Big Bones',

            // Demon bones (50 XP)
            'Demon' => 'Demon Bones',

            // Lich bones (65 XP)
            'Lich' => 'Lich Bones',

            // Wyvern bones (85 XP)
            'Wyvern' => 'Wyvern Bones',

            // Dragon bones (115 XP)
            'Elder Dragon' => 'Dragon Bones',
        ];

        // Update monster loot tables
        foreach ($monsterBones as $monsterName => $boneName) {
            $monster = Monster::where('name', $monsterName)->first();
            $bone = Item::where('name', $boneName)->first();

            if ($monster && $bone) {
                // Remove any existing bone drops for this monster
                MonsterLootTable::where('monster_id', $monster->id)
                    ->whereHas('item', fn ($q) => $q->where('subtype', 'remains'))
                    ->delete();

                // Add the correct bone drop
                MonsterLootTable::updateOrCreate(
                    [
                        'monster_id' => $monster->id,
                        'item_id' => $bone->id,
                    ],
                    [
                        'drop_chance' => 100.00,
                        'quantity_min' => 1,
                        'quantity_max' => 1,
                    ]
                );
            }
        }

        $this->command->info('Bone items and monster drops updated successfully!');
    }
}

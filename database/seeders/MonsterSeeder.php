<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Monster;
use App\Models\MonsterLootTable;
use Illuminate\Database\Seeder;

class MonsterSeeder extends Seeder
{
    /**
     * Defense type modifiers by monster type.
     * Values are multipliers applied to defense_level to get stab/slash/crush defense.
     */
    public const DEFENSE_TYPE_MODIFIERS = [
        'humanoid' => ['stab' => 1.0, 'slash' => 1.0, 'crush' => 1.0],
        'beast' => ['stab' => 0.8, 'slash' => 1.1, 'crush' => 1.0],
        'undead' => ['stab' => 1.2, 'slash' => 1.1, 'crush' => 0.8],
        'dragon' => ['stab' => 0.8, 'slash' => 1.3, 'crush' => 1.1],
        'demon' => ['stab' => 1.1, 'slash' => 1.0, 'crush' => 0.9],
        'elemental' => ['stab' => 1.0, 'slash' => 1.2, 'crush' => 0.8],
        'giant' => ['stab' => 1.0, 'slash' => 0.9, 'crush' => 1.1],
        'goblinoid' => ['stab' => 0.9, 'slash' => 1.0, 'crush' => 1.0],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $monsters = [
            // === LOW LEVEL (1-10) ===
            [
                'name' => 'Chicken',
                'description' => 'A plump farm chicken. Clucks angrily when threatened.',
                'type' => 'beast',
                'biome' => null, // Available everywhere
                'hp' => 5,
                'max_hp' => 5,
                'attack_level' => 1,
                'strength_level' => 1,
                'defense_level' => 1,
                'combat_level' => 1,
                'xp_reward' => 3,
                'gold_drop_min' => 0,
                'gold_drop_max' => 2,
                'min_player_combat_level' => 1,
            ],
            [
                'name' => 'Rat',
                'description' => 'A filthy sewer rat.',
                'type' => 'beast',
                'biome' => null, // Available everywhere
                'hp' => 10,
                'max_hp' => 10,
                'attack_level' => 1,
                'strength_level' => 1,
                'defense_level' => 1,
                'combat_level' => 1,
                'xp_reward' => 5,
                'gold_drop_min' => 1,
                'gold_drop_max' => 5,
                'min_player_combat_level' => 1,
            ],
            [
                'name' => 'Goblin',
                'description' => 'A small green goblin armed with a rusty knife.',
                'type' => 'goblinoid',
                'biome' => null,
                'hp' => 18,
                'max_hp' => 18,
                'attack_level' => 3,
                'strength_level' => 2,
                'defense_level' => 2,
                'combat_level' => 3,
                'xp_reward' => 12,
                'gold_drop_min' => 5,
                'gold_drop_max' => 15,
                'min_player_combat_level' => 1,
            ],
            [
                'name' => 'Wolf',
                'description' => 'A hungry gray wolf.',
                'type' => 'beast',
                'biome' => 'forest',
                'hp' => 25,
                'max_hp' => 25,
                'attack_level' => 5,
                'strength_level' => 4,
                'defense_level' => 3,
                'combat_level' => 5,
                'xp_reward' => 20,
                'gold_drop_min' => 0,
                'gold_drop_max' => 5,
                'min_player_combat_level' => 3,
            ],
            [
                'name' => 'Skeleton',
                'description' => 'The reanimated bones of a fallen warrior.',
                'type' => 'undead',
                'biome' => null,
                'hp' => 30,
                'max_hp' => 30,
                'attack_level' => 6,
                'strength_level' => 5,
                'defense_level' => 5,
                'combat_level' => 6,
                'xp_reward' => 28,
                'gold_drop_min' => 10,
                'gold_drop_max' => 30,
                'min_player_combat_level' => 5,
            ],
            [
                'name' => 'Bandit',
                'description' => 'A criminal lurking in the shadows.',
                'type' => 'humanoid',
                'biome' => null,
                'hp' => 35,
                'max_hp' => 35,
                'attack_level' => 8,
                'strength_level' => 6,
                'defense_level' => 4,
                'combat_level' => 7,
                'xp_reward' => 35,
                'gold_drop_min' => 20,
                'gold_drop_max' => 50,
                'min_player_combat_level' => 5,
            ],

            // === MID LEVEL (10-30) ===
            [
                'name' => 'Zombie',
                'description' => 'A shambling corpse seeking flesh.',
                'type' => 'undead',
                'biome' => 'swamp',
                'hp' => 50,
                'max_hp' => 50,
                'attack_level' => 10,
                'strength_level' => 12,
                'defense_level' => 5,
                'combat_level' => 10,
                'xp_reward' => 45,
                'gold_drop_min' => 15,
                'gold_drop_max' => 40,
                'min_player_combat_level' => 8,
            ],
            [
                'name' => 'Hobgoblin',
                'description' => 'A larger, more dangerous cousin of the goblin.',
                'type' => 'goblinoid',
                'biome' => null,
                'hp' => 55,
                'max_hp' => 55,
                'attack_level' => 12,
                'strength_level' => 10,
                'defense_level' => 8,
                'combat_level' => 12,
                'xp_reward' => 55,
                'gold_drop_min' => 25,
                'gold_drop_max' => 60,
                'min_player_combat_level' => 10,
            ],
            [
                'name' => 'Bear',
                'description' => 'A massive brown bear.',
                'type' => 'beast',
                'biome' => 'forest',
                'hp' => 70,
                'max_hp' => 70,
                'attack_level' => 15,
                'strength_level' => 18,
                'defense_level' => 10,
                'combat_level' => 15,
                'xp_reward' => 70,
                'gold_drop_min' => 0,
                'gold_drop_max' => 10,
                'min_player_combat_level' => 12,
            ],
            [
                'name' => 'Dark Mage',
                'description' => 'A practitioner of forbidden magic.',
                'type' => 'humanoid',
                'biome' => null,
                'hp' => 60,
                'max_hp' => 60,
                'attack_level' => 20,
                'strength_level' => 8,
                'defense_level' => 12,
                'combat_level' => 18,
                'attack_style' => 'magic',
                'xp_reward' => 85,
                'gold_drop_min' => 50,
                'gold_drop_max' => 120,
                'min_player_combat_level' => 15,
            ],
            [
                'name' => 'Troll',
                'description' => 'A hulking regenerating monster.',
                'type' => 'giant',
                'biome' => 'mountain',
                'hp' => 100,
                'max_hp' => 100,
                'attack_level' => 18,
                'strength_level' => 25,
                'defense_level' => 15,
                'combat_level' => 22,
                'xp_reward' => 120,
                'gold_drop_min' => 30,
                'gold_drop_max' => 80,
                'min_player_combat_level' => 18,
            ],
            [
                'name' => 'Ice Elemental',
                'description' => 'A being of pure frozen energy.',
                'type' => 'elemental',
                'biome' => 'tundra',
                'hp' => 85,
                'max_hp' => 85,
                'attack_level' => 22,
                'strength_level' => 20,
                'defense_level' => 20,
                'combat_level' => 25,
                'attack_style' => 'magic',
                'xp_reward' => 140,
                'gold_drop_min' => 0,
                'gold_drop_max' => 0,
                'min_player_combat_level' => 20,
            ],
            [
                'name' => 'Fire Elemental',
                'description' => 'A being of pure flame and heat.',
                'type' => 'elemental',
                'biome' => 'volcano',
                'hp' => 85,
                'max_hp' => 85,
                'attack_level' => 22,
                'strength_level' => 22,
                'defense_level' => 18,
                'combat_level' => 25,
                'attack_style' => 'magic',
                'xp_reward' => 145,
                'gold_drop_min' => 0,
                'gold_drop_max' => 0,
                'min_player_combat_level' => 20,
            ],

            // === HIGH LEVEL (30-50) ===
            [
                'name' => 'Ogre',
                'description' => 'A massive brute with enormous strength.',
                'type' => 'giant',
                'biome' => null,
                'hp' => 130,
                'max_hp' => 130,
                'attack_level' => 25,
                'strength_level' => 35,
                'defense_level' => 20,
                'combat_level' => 30,
                'xp_reward' => 180,
                'gold_drop_min' => 50,
                'gold_drop_max' => 150,
                'min_player_combat_level' => 25,
            ],
            [
                'name' => 'Demon',
                'description' => 'A fiend from the underworld.',
                'type' => 'demon',
                'biome' => 'volcano',
                'hp' => 150,
                'max_hp' => 150,
                'attack_level' => 35,
                'strength_level' => 30,
                'defense_level' => 30,
                'combat_level' => 38,
                'attack_style' => 'magic',
                'xp_reward' => 250,
                'gold_drop_min' => 100,
                'gold_drop_max' => 300,
                'min_player_combat_level' => 32,
            ],
            [
                'name' => 'Wyvern',
                'description' => 'A lesser dragon with venomous tail.',
                'type' => 'dragon',
                'biome' => 'mountain',
                'hp' => 180,
                'max_hp' => 180,
                'attack_level' => 40,
                'strength_level' => 38,
                'defense_level' => 35,
                'combat_level' => 45,
                'xp_reward' => 350,
                'gold_drop_min' => 150,
                'gold_drop_max' => 400,
                'min_player_combat_level' => 38,
            ],

            // === BOSS MONSTERS ===
            [
                'name' => 'Goblin King',
                'description' => 'The ruler of the goblin horde.',
                'type' => 'goblinoid',
                'biome' => null,
                'hp' => 60,
                'max_hp' => 60,
                'attack_level' => 12,
                'strength_level' => 10,
                'defense_level' => 10,
                'combat_level' => 12,
                'xp_reward' => 150,
                'gold_drop_min' => 100,
                'gold_drop_max' => 200,
                'min_player_combat_level' => 5,
                'is_boss' => true,
            ],
            [
                'name' => 'Necromancer',
                'description' => 'A dark mage who commands the undead.',
                'type' => 'undead',
                'biome' => null,
                'hp' => 80,
                'max_hp' => 80,
                'attack_level' => 18,
                'strength_level' => 15,
                'defense_level' => 14,
                'combat_level' => 18,
                'attack_style' => 'magic',
                'xp_reward' => 200,
                'gold_drop_min' => 150,
                'gold_drop_max' => 300,
                'min_player_combat_level' => 10,
                'is_boss' => true,
            ],
            [
                'name' => 'Hobgoblin Warlord',
                'description' => 'A fearsome hobgoblin commander who rules through strength.',
                'type' => 'goblinoid',
                'biome' => 'mountain',
                'hp' => 150,
                'max_hp' => 150,
                'attack_level' => 28,
                'strength_level' => 30,
                'defense_level' => 25,
                'combat_level' => 30,
                'xp_reward' => 400,
                'gold_drop_min' => 300,
                'gold_drop_max' => 600,
                'min_player_combat_level' => 20,
                'is_boss' => true,
            ],
            [
                'name' => 'Mummy Lord',
                'description' => 'An ancient pharaoh risen from the dead to protect his tomb.',
                'type' => 'undead',
                'biome' => 'coastal',
                'hp' => 120,
                'max_hp' => 120,
                'attack_level' => 24,
                'strength_level' => 22,
                'defense_level' => 20,
                'combat_level' => 25,
                'xp_reward' => 300,
                'gold_drop_min' => 200,
                'gold_drop_max' => 450,
                'min_player_combat_level' => 15,
                'is_boss' => true,
            ],
            [
                'name' => 'Lich',
                'description' => 'An undead sorcerer of immense power.',
                'type' => 'undead',
                'biome' => null,
                'hp' => 250,
                'max_hp' => 250,
                'attack_level' => 50,
                'strength_level' => 25,
                'defense_level' => 40,
                'combat_level' => 50,
                'attack_style' => 'magic',
                'xp_reward' => 600,
                'gold_drop_min' => 400,
                'gold_drop_max' => 800,
                'min_player_combat_level' => 42,
                'is_boss' => true,
            ],
            [
                'name' => 'Elder Dragon',
                'description' => 'An ancient dragon of terrifying power.',
                'type' => 'dragon',
                'biome' => 'volcano',
                'hp' => 400,
                'max_hp' => 400,
                'attack_level' => 65,
                'strength_level' => 60,
                'defense_level' => 55,
                'combat_level' => 70,
                'xp_reward' => 1500,
                'gold_drop_min' => 1000,
                'gold_drop_max' => 3000,
                'min_player_combat_level' => 55,
                'is_boss' => true,
            ],
        ];

        foreach ($monsters as $monsterData) {
            // Derive typed defenses from defense_level and monster type
            $defenseLevel = $monsterData['defense_level'];
            $type = $monsterData['type'];
            $modifiers = self::DEFENSE_TYPE_MODIFIERS[$type] ?? ['stab' => 1.0, 'slash' => 1.0, 'crush' => 1.0];

            $monsterData['stab_defense'] = max(0, (int) round($defenseLevel * $modifiers['stab']));
            $monsterData['slash_defense'] = max(0, (int) round($defenseLevel * $modifiers['slash']));
            $monsterData['crush_defense'] = max(0, (int) round($defenseLevel * $modifiers['crush']));

            Monster::updateOrCreate(
                ['name' => $monsterData['name']],
                $monsterData
            );
        }

        // Add loot tables
        $this->seedLootTables();
    }

    /**
     * Seed monster loot tables.
     */
    protected function seedLootTables(): void
    {
        $lootTables = [
            // Low level monsters
            'Chicken' => [
                ['item' => 'Bones', 'chance' => 100, 'min' => 1, 'max' => 1],
                ['item' => 'Feather', 'chance' => 100, 'min' => 5, 'max' => 15],
                ['item' => 'Raw Chicken', 'chance' => 100, 'min' => 1, 'max' => 1],
            ],
            'Rat' => [
                ['item' => 'Bones', 'chance' => 100, 'min' => 1, 'max' => 1],
                ['item' => 'Venom Sac', 'chance' => 5, 'min' => 1, 'max' => 1],
            ],
            'Goblin' => [
                ['item' => 'Bones', 'chance' => 100, 'min' => 1, 'max' => 1],
                ['item' => 'Bronze Dagger', 'chance' => 10, 'min' => 1, 'max' => 1],
                ['item' => 'Feather', 'chance' => 40, 'min' => 1, 'max' => 3],
            ],
            'Wolf' => [
                ['item' => 'Bones', 'chance' => 100, 'min' => 1, 'max' => 1],
                ['item' => 'Leather', 'chance' => 50, 'min' => 1, 'max' => 2],
            ],
            'Skeleton' => [
                ['item' => 'Bones', 'chance' => 100, 'min' => 1, 'max' => 2],
                ['item' => 'Iron Dagger', 'chance' => 8, 'min' => 1, 'max' => 1],
            ],
            'Bandit' => [
                ['item' => 'Bones', 'chance' => 100, 'min' => 1, 'max' => 1],
                ['item' => 'Iron Sword', 'chance' => 5, 'min' => 1, 'max' => 1],
                ['item' => 'Leather Vest', 'chance' => 8, 'min' => 1, 'max' => 1],
                ['item' => 'Venom Sac', 'chance' => 10, 'min' => 1, 'max' => 1],
                ['item' => 'Feather', 'chance' => 35, 'min' => 2, 'max' => 5],
                ['item' => 'Cloth', 'chance' => 12, 'min' => 1, 'max' => 1],
            ],

            // Mid level monsters
            'Zombie' => [
                ['item' => 'Bones', 'chance' => 100, 'min' => 1, 'max' => 2],
                ['item' => 'Nightshade', 'chance' => 15, 'min' => 1, 'max' => 1],
                ['item' => 'Cloth', 'chance' => 8, 'min' => 1, 'max' => 1],
            ],
            'Hobgoblin' => [
                ['item' => 'Bones', 'chance' => 100, 'min' => 1, 'max' => 1],
                ['item' => 'Iron Sword', 'chance' => 10, 'min' => 1, 'max' => 1],
                ['item' => 'Iron Sq Shield', 'chance' => 8, 'min' => 1, 'max' => 1],
                ['item' => 'Turtle Shell Powder', 'chance' => 15, 'min' => 1, 'max' => 1],
                ['item' => 'Cloth', 'chance' => 10, 'min' => 1, 'max' => 2],
            ],
            'Bear' => [
                ['item' => 'Big Bones', 'chance' => 100, 'min' => 1, 'max' => 1],
                ['item' => 'Leather', 'chance' => 80, 'min' => 2, 'max' => 4],
            ],
            'Dark Mage' => [
                ['item' => 'Bones', 'chance' => 100, 'min' => 1, 'max' => 1],
                ['item' => 'Void Essence', 'chance' => 10, 'min' => 1, 'max' => 1],
                ['item' => 'Nightshade', 'chance' => 25, 'min' => 1, 'max' => 2],
                ['item' => 'Cloth', 'chance' => 15, 'min' => 1, 'max' => 2],
            ],
            'Troll' => [
                ['item' => 'Big Bones', 'chance' => 100, 'min' => 1, 'max' => 2],
                ['item' => 'Steel Sword', 'chance' => 5, 'min' => 1, 'max' => 1],
                ['item' => 'Giant Essence', 'chance' => 25, 'min' => 1, 'max' => 1],
            ],
            'Ice Elemental' => [
                ['item' => 'Starlight Essence', 'chance' => 20, 'min' => 1, 'max' => 1],
            ],
            'Fire Elemental' => [
                ['item' => 'Phoenix Feather', 'chance' => 12, 'min' => 1, 'max' => 1],
            ],

            // High level monsters
            'Ogre' => [
                ['item' => 'Big Bones', 'chance' => 100, 'min' => 1, 'max' => 2],
                ['item' => 'Giant Essence', 'chance' => 35, 'min' => 1, 'max' => 2],
            ],
            'Demon' => [
                ['item' => 'Big Bones', 'chance' => 100, 'min' => 1, 'max' => 2],
                ['item' => 'Void Essence', 'chance' => 20, 'min' => 1, 'max' => 1],
            ],
            'Wyvern' => [
                ['item' => 'Big Bones', 'chance' => 100, 'min' => 1, 'max' => 2],
                ['item' => 'Phoenix Feather', 'chance' => 8, 'min' => 1, 'max' => 1],
                ['item' => 'Venom Sac', 'chance' => 30, 'min' => 1, 'max' => 2],
                ['item' => 'Feather', 'chance' => 80, 'min' => 5, 'max' => 15],
            ],

            // Boss monsters
            'Goblin King' => [
                ['item' => 'Big Bones', 'chance' => 100, 'min' => 2, 'max' => 3],
                ['item' => 'Steel Sword', 'chance' => 25, 'min' => 1, 'max' => 1],
                ['item' => 'Ring of Strength', 'chance' => 10, 'min' => 1, 'max' => 1],
                ['item' => 'Giant Essence', 'chance' => 50, 'min' => 1, 'max' => 2],
            ],
            'Necromancer' => [
                ['item' => 'Big Bones', 'chance' => 100, 'min' => 1, 'max' => 2],
                ['item' => 'Void Essence', 'chance' => 30, 'min' => 1, 'max' => 2],
                ['item' => 'Amulet of Defense', 'chance' => 8, 'min' => 1, 'max' => 1],
            ],
            'Hobgoblin Warlord' => [
                ['item' => 'Big Bones', 'chance' => 100, 'min' => 2, 'max' => 3],
                ['item' => 'Mithril Sword', 'chance' => 15, 'min' => 1, 'max' => 1],
                ['item' => 'Ring of Strength', 'chance' => 12, 'min' => 1, 'max' => 1],
                ['item' => 'Giant Essence', 'chance' => 60, 'min' => 2, 'max' => 3],
            ],
            'Mummy Lord' => [
                ['item' => 'Big Bones', 'chance' => 100, 'min' => 2, 'max' => 3],
                ['item' => 'Void Essence', 'chance' => 35, 'min' => 1, 'max' => 2],
                ['item' => 'Amulet of Defense', 'chance' => 10, 'min' => 1, 'max' => 1],
                ['item' => 'Gold Bar', 'chance' => 20, 'min' => 1, 'max' => 2],
            ],
            'Lich' => [
                ['item' => 'Big Bones', 'chance' => 100, 'min' => 2, 'max' => 4],
                ['item' => 'Amulet of Defense', 'chance' => 15, 'min' => 1, 'max' => 1],
                ['item' => 'Void Essence', 'chance' => 40, 'min' => 1, 'max' => 2],
                ['item' => 'Unicorn Tears', 'chance' => 5, 'min' => 1, 'max' => 1],
            ],
            'Elder Dragon' => [
                ['item' => 'Dragon Bones', 'chance' => 100, 'min' => 3, 'max' => 5],
                ['item' => 'Dragon Slayer', 'chance' => 2, 'min' => 1, 'max' => 1],
                ['item' => 'Amulet of Power', 'chance' => 5, 'min' => 1, 'max' => 1],
                ['item' => 'Phoenix Feather', 'chance' => 25, 'min' => 1, 'max' => 3],
                ['item' => 'Unicorn Tears', 'chance' => 10, 'min' => 1, 'max' => 1],
            ],
        ];

        foreach ($lootTables as $monsterName => $drops) {
            $monster = Monster::where('name', $monsterName)->first();
            if (! $monster) {
                continue;
            }

            foreach ($drops as $drop) {
                $item = Item::where('name', $drop['item'])->first();
                if (! $item) {
                    continue;
                }

                MonsterLootTable::updateOrCreate(
                    [
                        'monster_id' => $monster->id,
                        'item_id' => $item->id,
                    ],
                    [
                        'drop_chance' => $drop['chance'],
                        'quantity_min' => $drop['min'],
                        'quantity_max' => $drop['max'],
                    ]
                );
            }
        }
    }
}

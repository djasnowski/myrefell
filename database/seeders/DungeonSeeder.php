<?php

namespace Database\Seeders;

use App\Models\Dungeon;
use App\Models\DungeonFloor;
use App\Models\DungeonFloorMonster;
use App\Models\Kingdom;
use App\Models\Monster;
use Illuminate\Database\Seeder;

class DungeonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch kingdoms for assignment
        $valdoria = Kingdom::where('name', 'Valdoria')->first();
        $frostholm = Kingdom::where('name', 'Frostholm')->first();
        $sandmar = Kingdom::where('name', 'Sandmar')->first();
        $ashenfell = Kingdom::where('name', 'Ashenfell')->first();

        $dungeons = [
            // === VALDORIA DUNGEONS (Plains biome - beginner friendly) ===
            [
                'name' => 'Goblin Warrens',
                'description' => 'A network of tunnels infested with goblins.',
                'theme' => 'goblin_fortress',
                'difficulty' => 'easy',
                'biome' => null,
                'kingdom_id' => $valdoria?->id,
                'min_combat_level' => 1,
                'recommended_level' => 5,
                'floor_count' => 3,
                'boss_monster' => 'Goblin King',
                'xp_reward_base' => 100,
                'gold_reward_min' => 50,
                'gold_reward_max' => 150,
                'energy_cost' => 10,
                'floors' => [
                    ['name' => 'Entrance Tunnels', 'monster_count' => 3, 'monsters' => ['Rat', 'Goblin']],
                    ['name' => 'Deep Warrens', 'monster_count' => 4, 'monsters' => ['Goblin']],
                    ['name' => 'Throne Room', 'monster_count' => 3, 'is_boss' => true, 'monsters' => ['Goblin', 'Hobgoblin']],
                ],
            ],
            [
                'name' => 'Bandit Hideout',
                'description' => 'An abandoned mine taken over by criminals.',
                'theme' => 'bandit_hideout',
                'difficulty' => 'easy',
                'biome' => null,
                'kingdom_id' => $valdoria?->id,
                'min_combat_level' => 5,
                'recommended_level' => 10,
                'floor_count' => 3,
                'boss_monster' => null,
                'xp_reward_base' => 150,
                'gold_reward_min' => 100,
                'gold_reward_max' => 250,
                'energy_cost' => 12,
                'floors' => [
                    ['name' => 'Mine Entrance', 'monster_count' => 3, 'monsters' => ['Bandit']],
                    ['name' => 'Storage Chambers', 'monster_count' => 4, 'monsters' => ['Bandit']],
                    ['name' => 'Leader\'s Quarters', 'monster_count' => 3, 'is_boss' => true, 'monsters' => ['Bandit']],
                ],
            ],
            [
                'name' => 'Forgotten Crypt',
                'description' => 'An ancient burial ground where the dead refuse to rest.',
                'theme' => 'undead_crypt',
                'difficulty' => 'normal',
                'biome' => null,
                'kingdom_id' => $valdoria?->id,
                'min_combat_level' => 10,
                'recommended_level' => 18,
                'floor_count' => 4,
                'boss_monster' => 'Lich',
                'xp_reward_base' => 300,
                'gold_reward_min' => 200,
                'gold_reward_max' => 500,
                'energy_cost' => 15,
                'floors' => [
                    ['name' => 'Entrance Hall', 'monster_count' => 3, 'monsters' => ['Skeleton']],
                    ['name' => 'Burial Chambers', 'monster_count' => 4, 'monsters' => ['Skeleton', 'Zombie']],
                    ['name' => 'Inner Sanctum', 'monster_count' => 4, 'monsters' => ['Zombie']],
                    ['name' => 'Lich\'s Throne', 'monster_count' => 3, 'is_boss' => true, 'monsters' => ['Skeleton', 'Zombie']],
                ],
            ],
            [
                'name' => 'Forest Depths',
                'description' => 'The dangerous heart of an ancient forest.',
                'theme' => 'ancient_ruins',
                'difficulty' => 'normal',
                'biome' => 'forest',
                'kingdom_id' => $valdoria?->id,
                'min_combat_level' => 12,
                'recommended_level' => 20,
                'floor_count' => 4,
                'boss_monster' => null,
                'xp_reward_base' => 350,
                'gold_reward_min' => 150,
                'gold_reward_max' => 400,
                'energy_cost' => 15,
                'floors' => [
                    ['name' => 'Forest Edge', 'monster_count' => 3, 'monsters' => ['Wolf']],
                    ['name' => 'Dense Woods', 'monster_count' => 4, 'monsters' => ['Wolf', 'Bear']],
                    ['name' => 'Ancient Grove', 'monster_count' => 4, 'monsters' => ['Bear']],
                    ['name' => 'Heart of the Forest', 'monster_count' => 3, 'is_boss' => true, 'monsters' => ['Bear']],
                ],
            ],

            // === FROSTHOLM DUNGEONS (Tundra biome - ice themed) ===
            [
                'name' => 'Frozen Caverns',
                'description' => 'A cave system encased in eternal ice.',
                'theme' => 'elemental_cavern',
                'difficulty' => 'easy',
                'biome' => 'tundra',
                'kingdom_id' => $frostholm?->id,
                'min_combat_level' => 1,
                'recommended_level' => 8,
                'floor_count' => 3,
                'boss_monster' => null,
                'xp_reward_base' => 120,
                'gold_reward_min' => 60,
                'gold_reward_max' => 180,
                'energy_cost' => 10,
                'floors' => [
                    ['name' => 'Icy Entrance', 'monster_count' => 3, 'monsters' => ['Rat', 'Goblin']],
                    ['name' => 'Crystal Chambers', 'monster_count' => 4, 'monsters' => ['Goblin', 'Wolf']],
                    ['name' => 'Frost Core', 'monster_count' => 3, 'is_boss' => true, 'monsters' => ['Wolf']],
                ],
            ],
            [
                'name' => 'Elemental Caverns',
                'description' => 'Caves where raw elemental forces clash.',
                'theme' => 'elemental_cavern',
                'difficulty' => 'hard',
                'biome' => 'tundra',
                'kingdom_id' => $frostholm?->id,
                'min_combat_level' => 22,
                'recommended_level' => 32,
                'floor_count' => 5,
                'boss_monster' => null,
                'xp_reward_base' => 550,
                'gold_reward_min' => 300,
                'gold_reward_max' => 700,
                'energy_cost' => 20,
                'floors' => [
                    ['name' => 'Frozen Entry', 'monster_count' => 4, 'monsters' => ['Ice Elemental']],
                    ['name' => 'Crystal Caverns', 'monster_count' => 5, 'monsters' => ['Ice Elemental']],
                    ['name' => 'Convergence Point', 'monster_count' => 5, 'monsters' => ['Ice Elemental']],
                    ['name' => 'Elemental Nexus', 'monster_count' => 4, 'monsters' => ['Ice Elemental']],
                    ['name' => 'Heart of Ice', 'monster_count' => 3, 'is_boss' => true, 'monsters' => ['Ice Elemental']],
                ],
            ],

            // === SANDMAR DUNGEONS (Coastal biome - trade/bandit themed) ===
            [
                'name' => 'Smuggler\'s Cove',
                'description' => 'A hidden cove used by pirates and smugglers.',
                'theme' => 'bandit_hideout',
                'difficulty' => 'easy',
                'biome' => 'coastal',
                'kingdom_id' => $sandmar?->id,
                'min_combat_level' => 1,
                'recommended_level' => 6,
                'floor_count' => 3,
                'boss_monster' => null,
                'xp_reward_base' => 110,
                'gold_reward_min' => 80,
                'gold_reward_max' => 200,
                'energy_cost' => 10,
                'floors' => [
                    ['name' => 'Beach Entrance', 'monster_count' => 3, 'monsters' => ['Rat', 'Goblin']],
                    ['name' => 'Storage Caves', 'monster_count' => 4, 'monsters' => ['Goblin', 'Bandit']],
                    ['name' => 'Captain\'s Quarters', 'monster_count' => 3, 'is_boss' => true, 'monsters' => ['Bandit']],
                ],
            ],
            [
                'name' => 'Desert Ruins',
                'description' => 'Ancient ruins half-buried in the desert sands.',
                'theme' => 'ancient_ruins',
                'difficulty' => 'normal',
                'biome' => 'coastal',
                'kingdom_id' => $sandmar?->id,
                'min_combat_level' => 15,
                'recommended_level' => 25,
                'floor_count' => 4,
                'boss_monster' => 'Lich',
                'xp_reward_base' => 400,
                'gold_reward_min' => 250,
                'gold_reward_max' => 550,
                'energy_cost' => 18,
                'floors' => [
                    ['name' => 'Sand-Covered Entry', 'monster_count' => 3, 'monsters' => ['Skeleton']],
                    ['name' => 'Collapsed Halls', 'monster_count' => 4, 'monsters' => ['Skeleton', 'Zombie']],
                    ['name' => 'Treasure Vault', 'monster_count' => 4, 'monsters' => ['Zombie']],
                    ['name' => 'Pharaoh\'s Chamber', 'monster_count' => 3, 'is_boss' => true, 'monsters' => ['Skeleton', 'Zombie']],
                ],
            ],

            // === ASHENFELL DUNGEONS (Volcano biome - fire/demon themed) ===
            [
                'name' => 'Lava Tunnels',
                'description' => 'Tunnels carved by flowing lava through the mountain.',
                'theme' => 'elemental_cavern',
                'difficulty' => 'normal',
                'biome' => 'volcano',
                'kingdom_id' => $ashenfell?->id,
                'min_combat_level' => 10,
                'recommended_level' => 18,
                'floor_count' => 4,
                'boss_monster' => null,
                'xp_reward_base' => 320,
                'gold_reward_min' => 200,
                'gold_reward_max' => 500,
                'energy_cost' => 15,
                'floors' => [
                    ['name' => 'Magma Entry', 'monster_count' => 3, 'monsters' => ['Skeleton', 'Zombie']],
                    ['name' => 'Sulfur Pits', 'monster_count' => 4, 'monsters' => ['Zombie']],
                    ['name' => 'Obsidian Halls', 'monster_count' => 4, 'monsters' => ['Zombie']],
                    ['name' => 'Volcanic Core', 'monster_count' => 3, 'is_boss' => true, 'monsters' => ['Dark Mage']],
                ],
            ],
            [
                'name' => 'Mountain Fortress',
                'description' => 'A massive stronghold carved into the mountainside.',
                'theme' => 'goblin_fortress',
                'difficulty' => 'hard',
                'biome' => 'mountain',
                'kingdom_id' => $ashenfell?->id,
                'min_combat_level' => 20,
                'recommended_level' => 30,
                'floor_count' => 5,
                'boss_monster' => 'Goblin King',
                'xp_reward_base' => 500,
                'gold_reward_min' => 400,
                'gold_reward_max' => 800,
                'energy_cost' => 20,
                'floors' => [
                    ['name' => 'Outer Gates', 'monster_count' => 4, 'monsters' => ['Hobgoblin']],
                    ['name' => 'Barracks', 'monster_count' => 5, 'monsters' => ['Hobgoblin', 'Troll']],
                    ['name' => 'War Room', 'monster_count' => 5, 'monsters' => ['Hobgoblin', 'Ogre']],
                    ['name' => 'Treasury', 'monster_count' => 4, 'monsters' => ['Ogre', 'Troll']],
                    ['name' => 'King\'s Chamber', 'monster_count' => 3, 'is_boss' => true, 'monsters' => ['Hobgoblin', 'Ogre']],
                ],
            ],
            [
                'name' => 'Dragon\'s Lair',
                'description' => 'The volcanic home of an ancient dragon.',
                'theme' => 'dragon_lair',
                'difficulty' => 'nightmare',
                'biome' => 'volcano',
                'kingdom_id' => $ashenfell?->id,
                'min_combat_level' => 40,
                'recommended_level' => 55,
                'floor_count' => 6,
                'boss_monster' => 'Elder Dragon',
                'xp_reward_base' => 1000,
                'gold_reward_min' => 1000,
                'gold_reward_max' => 2500,
                'energy_cost' => 30,
                'floors' => [
                    ['name' => 'Volcanic Entry', 'monster_count' => 4, 'monsters' => ['Fire Elemental']],
                    ['name' => 'Lava Tunnels', 'monster_count' => 5, 'monsters' => ['Fire Elemental', 'Demon']],
                    ['name' => 'Obsidian Halls', 'monster_count' => 5, 'monsters' => ['Demon', 'Wyvern']],
                    ['name' => 'Dragon Nursery', 'monster_count' => 4, 'monsters' => ['Wyvern']],
                    ['name' => 'Treasure Hoard', 'monster_count' => 4, 'monsters' => ['Demon', 'Wyvern']],
                    ['name' => 'Elder Dragon\'s Chamber', 'monster_count' => 3, 'is_boss' => true, 'monsters' => ['Wyvern', 'Demon']],
                ],
            ],
            [
                'name' => 'Demon Pit',
                'description' => 'A rift to the underworld, spewing forth demons.',
                'theme' => 'demon_pit',
                'difficulty' => 'nightmare',
                'biome' => 'volcano',
                'kingdom_id' => $ashenfell?->id,
                'min_combat_level' => 35,
                'recommended_level' => 50,
                'floor_count' => 6,
                'boss_monster' => null,
                'xp_reward_base' => 900,
                'gold_reward_min' => 800,
                'gold_reward_max' => 2000,
                'energy_cost' => 28,
                'floors' => [
                    ['name' => 'Rift Opening', 'monster_count' => 4, 'monsters' => ['Demon']],
                    ['name' => 'Burning Descent', 'monster_count' => 5, 'monsters' => ['Demon', 'Fire Elemental']],
                    ['name' => 'Demon Barracks', 'monster_count' => 5, 'monsters' => ['Demon']],
                    ['name' => 'Torture Chambers', 'monster_count' => 4, 'monsters' => ['Demon']],
                    ['name' => 'Sacrificial Altar', 'monster_count' => 4, 'monsters' => ['Demon']],
                    ['name' => 'Pit of Despair', 'monster_count' => 4, 'is_boss' => true, 'monsters' => ['Demon']],
                ],
            ],
        ];

        foreach ($dungeons as $dungeonData) {
            $floorsData = $dungeonData['floors'];
            unset($dungeonData['floors']);

            // Find boss monster
            $bossMonster = null;
            if (! empty($dungeonData['boss_monster'])) {
                $bossMonster = Monster::where('name', $dungeonData['boss_monster'])->first();
                unset($dungeonData['boss_monster']);
            } else {
                unset($dungeonData['boss_monster']);
            }

            $dungeonData['boss_monster_id'] = $bossMonster?->id;

            // Skip if kingdom doesn't exist (for development)
            if (isset($dungeonData['kingdom_id']) && $dungeonData['kingdom_id'] === null) {
                continue;
            }

            // Use updateOrCreate to handle existing dungeons
            $dungeon = Dungeon::updateOrCreate(
                ['name' => $dungeonData['name']],
                $dungeonData
            );

            // Delete existing floors and recreate them to apply rebalance
            $dungeon->floors()->each(function ($floor) {
                $floor->monsters()->delete();
                $floor->delete();
            });
            $this->seedFloors($dungeon, $floorsData);
        }
    }

    /**
     * Seed dungeon floors and their monster spawns.
     */
    protected function seedFloors(Dungeon $dungeon, array $floorsData): void
    {
        foreach ($floorsData as $index => $floorData) {
            $floorNumber = $index + 1;
            $isBossFloor = $floorData['is_boss'] ?? false;

            // Calculate multipliers based on floor depth
            $xpMultiplier = 1.0 + ($floorNumber * 0.1);
            $lootMultiplier = 1.0 + ($floorNumber * 0.15);

            $floor = DungeonFloor::create([
                'dungeon_id' => $dungeon->id,
                'floor_number' => $floorNumber,
                'name' => $floorData['name'] ?? null,
                'monster_count' => $floorData['monster_count'],
                'is_boss_floor' => $isBossFloor,
                'xp_multiplier' => $xpMultiplier,
                'loot_multiplier' => $lootMultiplier,
            ]);

            // Add monster spawns
            $this->seedFloorMonsters($floor, $floorData['monsters']);
        }
    }

    /**
     * Seed monster spawns for a floor.
     */
    protected function seedFloorMonsters(DungeonFloor $floor, array $monsterNames): void
    {
        // Distribute spawn weights among monsters
        $baseWeight = 100;
        $weightPerMonster = (int) ($baseWeight / count($monsterNames));

        foreach ($monsterNames as $index => $monsterName) {
            $monster = Monster::where('name', $monsterName)->first();
            if (! $monster) {
                continue;
            }

            // First monster in list gets higher weight
            $weight = $index === 0 ? $weightPerMonster + 20 : $weightPerMonster;

            DungeonFloorMonster::create([
                'dungeon_floor_id' => $floor->id,
                'monster_id' => $monster->id,
                'spawn_weight' => $weight,
                'min_count' => 1,
                'max_count' => 1,
            ]);
        }
    }
}

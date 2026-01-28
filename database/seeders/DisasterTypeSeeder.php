<?php

namespace Database\Seeders;

use App\Models\DisasterType;
use Illuminate\Database\Seeder;

class DisasterTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $disasterTypes = [
            // Weather-based disasters
            [
                'name' => 'Flood',
                'slug' => 'flood',
                'description' => 'Heavy rains cause rivers to overflow, damaging buildings and drowning crops.',
                'category' => 'weather',
                'affected_seasons' => ['spring', 'autumn'],
                'base_chance' => 2, // 2% chance per village per day in affected seasons
                'duration_days' => 3,
                'building_damage' => 25,
                'crop_damage' => 40,
                'casualty_rate' => 5,
                'preventable_by' => ['levee', 'dam', 'drainage_canal'],
            ],
            [
                'name' => 'Drought',
                'slug' => 'drought',
                'description' => 'Extended lack of rainfall withers crops and dries up wells.',
                'category' => 'weather',
                'affected_seasons' => ['summer'],
                'base_chance' => 3,
                'duration_days' => 14,
                'building_damage' => 0,
                'crop_damage' => 60,
                'casualty_rate' => 2,
                'preventable_by' => ['well', 'aqueduct', 'irrigation'],
            ],
            [
                'name' => 'Blizzard',
                'slug' => 'blizzard',
                'description' => 'Fierce snowstorms bury buildings and freeze the unprepared.',
                'category' => 'weather',
                'affected_seasons' => ['winter'],
                'base_chance' => 3,
                'duration_days' => 2,
                'building_damage' => 15,
                'crop_damage' => 0, // No crops in winter
                'casualty_rate' => 8,
                'preventable_by' => ['stone_walls', 'granary'], // Stored food helps survival
            ],
            [
                'name' => 'Hailstorm',
                'slug' => 'hailstorm',
                'description' => 'Violent hail destroys crops and damages rooftops.',
                'category' => 'weather',
                'affected_seasons' => ['spring', 'summer'],
                'base_chance' => 2,
                'duration_days' => 1,
                'building_damage' => 20,
                'crop_damage' => 50,
                'casualty_rate' => 3,
                'preventable_by' => [],
            ],
            [
                'name' => 'Tornado',
                'slug' => 'tornado',
                'description' => 'A devastating whirlwind tears through the settlement.',
                'category' => 'weather',
                'affected_seasons' => ['spring', 'summer'],
                'base_chance' => 1, // Rare
                'duration_days' => 1,
                'building_damage' => 50,
                'crop_damage' => 30,
                'casualty_rate' => 15,
                'preventable_by' => [],
            ],

            // Fire disasters
            [
                'name' => 'Fire',
                'slug' => 'fire',
                'description' => 'Fire breaks out and spreads through the settlement.',
                'category' => 'fire',
                'affected_seasons' => ['spring', 'summer', 'autumn', 'winter'],
                'base_chance' => 2,
                'duration_days' => 1,
                'building_damage' => 35,
                'crop_damage' => 20,
                'casualty_rate' => 10,
                'preventable_by' => ['well', 'fire_brigade', 'stone_buildings'],
            ],
            [
                'name' => 'Great Fire',
                'slug' => 'great_fire',
                'description' => 'A catastrophic fire engulfs much of the settlement.',
                'category' => 'fire',
                'affected_seasons' => ['summer'], // Dry conditions
                'base_chance' => 1, // Very rare
                'duration_days' => 3,
                'building_damage' => 70,
                'crop_damage' => 40,
                'casualty_rate' => 20,
                'preventable_by' => ['fire_brigade', 'stone_buildings'],
            ],

            // Geological disasters
            [
                'name' => 'Earthquake',
                'slug' => 'earthquake',
                'description' => 'The earth shakes violently, toppling buildings.',
                'category' => 'geological',
                'affected_seasons' => ['spring', 'summer', 'autumn', 'winter'],
                'base_chance' => 1, // Rare
                'duration_days' => 1,
                'building_damage' => 45,
                'crop_damage' => 10,
                'casualty_rate' => 12,
                'preventable_by' => [],
            ],
            [
                'name' => 'Landslide',
                'slug' => 'landslide',
                'description' => 'Heavy rains loosen the hillside, sending mud and rocks cascading down.',
                'category' => 'geological',
                'affected_seasons' => ['spring', 'autumn'],
                'base_chance' => 1,
                'duration_days' => 1,
                'building_damage' => 30,
                'crop_damage' => 25,
                'casualty_rate' => 8,
                'preventable_by' => ['retaining_wall', 'drainage_canal'],
            ],

            // Infestation disasters
            [
                'name' => 'Locust Swarm',
                'slug' => 'locust_swarm',
                'description' => 'A massive swarm of locusts descends, devouring all vegetation.',
                'category' => 'other',
                'affected_seasons' => ['summer', 'autumn'],
                'base_chance' => 1,
                'duration_days' => 3,
                'building_damage' => 0,
                'crop_damage' => 80,
                'casualty_rate' => 0,
                'preventable_by' => [],
            ],
            [
                'name' => 'Rat Infestation',
                'slug' => 'rat_infestation',
                'description' => 'Rats overrun the granaries, eating stored food and spreading filth.',
                'category' => 'other',
                'affected_seasons' => ['spring', 'summer', 'autumn', 'winter'],
                'base_chance' => 2,
                'duration_days' => 7,
                'building_damage' => 5,
                'crop_damage' => 30, // Stored food
                'casualty_rate' => 1,
                'preventable_by' => ['cats', 'stone_granary'],
            ],
            [
                'name' => 'Weevil Infestation',
                'slug' => 'weevil_infestation',
                'description' => 'Weevils infest the grain stores, ruining much of the harvest.',
                'category' => 'other',
                'affected_seasons' => ['summer', 'autumn'],
                'base_chance' => 2,
                'duration_days' => 14,
                'building_damage' => 0,
                'crop_damage' => 40,
                'casualty_rate' => 0,
                'preventable_by' => ['stone_granary', 'proper_storage'],
            ],

            // Social disasters
            [
                'name' => 'Bandit Raid',
                'slug' => 'bandit_raid',
                'description' => 'Bandits attack the settlement, stealing goods and harming villagers.',
                'category' => 'other',
                'affected_seasons' => ['spring', 'summer', 'autumn'],
                'base_chance' => 2,
                'duration_days' => 1,
                'building_damage' => 15,
                'crop_damage' => 10,
                'casualty_rate' => 8,
                'preventable_by' => ['militia', 'watchtower', 'palisade'],
            ],
            [
                'name' => 'Riot',
                'slug' => 'riot',
                'description' => 'Civil unrest erupts into violence as angry mobs rampage.',
                'category' => 'other',
                'affected_seasons' => ['spring', 'summer', 'autumn', 'winter'],
                'base_chance' => 1,
                'duration_days' => 2,
                'building_damage' => 25,
                'crop_damage' => 15,
                'casualty_rate' => 5,
                'preventable_by' => ['militia', 'strong_leadership'],
            ],

            // Famine (triggered by other disasters or poor harvests)
            [
                'name' => 'Famine',
                'slug' => 'famine',
                'description' => 'Food stores run dangerously low. People begin to starve.',
                'category' => 'other',
                'affected_seasons' => ['winter', 'spring'], // After failed harvests
                'base_chance' => 1,
                'duration_days' => 30,
                'building_damage' => 0,
                'crop_damage' => 0,
                'casualty_rate' => 15,
                'preventable_by' => ['granary', 'trade_routes'],
            ],
        ];

        foreach ($disasterTypes as $type) {
            DisasterType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}

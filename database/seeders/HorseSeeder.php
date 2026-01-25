<?php

namespace Database\Seeders;

use App\Models\Horse;
use App\Models\Role;
use Illuminate\Database\Seeder;

class HorseSeeder extends Seeder
{
    public function run(): void
    {
        // Create horse types
        $horses = [
            [
                'name' => 'Draft Horse',
                'slug' => 'draft-horse',
                'description' => 'A sturdy working horse. Not fast, but reliable for travel.',
                'speed_multiplier' => 2.0,
                'base_price' => 500,
                'min_location_type' => 'village',
                'rarity' => 80,
            ],
            [
                'name' => 'Riding Horse',
                'slug' => 'riding-horse',
                'description' => 'A proper riding horse bred for comfort and moderate speed.',
                'speed_multiplier' => 2.5,
                'base_price' => 2000,
                'min_location_type' => 'village',
                'rarity' => 60,
            ],
            [
                'name' => 'Courser',
                'slug' => 'courser',
                'description' => 'A swift horse favored by messengers and light cavalry.',
                'speed_multiplier' => 3.0,
                'base_price' => 5000,
                'min_location_type' => 'town',
                'rarity' => 40,
            ],
            [
                'name' => 'Destrier',
                'slug' => 'destrier',
                'description' => 'A powerful warhorse trained for battle and speed.',
                'speed_multiplier' => 3.5,
                'base_price' => 15000,
                'min_location_type' => 'castle',
                'rarity' => 20,
            ],
            [
                'name' => 'Warhorse',
                'slug' => 'warhorse',
                'description' => 'The finest breed in the realm. Only royalty can afford such magnificence.',
                'speed_multiplier' => 4.0,
                'base_price' => 50000,
                'min_location_type' => 'kingdom',
                'rarity' => 5,
            ],
        ];

        foreach ($horses as $horse) {
            Horse::updateOrCreate(
                ['slug' => $horse['slug']],
                $horse
            );
        }

        // Add Stable Master role for villages, towns, and castles
        $stableMasterLocations = ['village', 'town', 'castle'];

        foreach ($stableMasterLocations as $locationType) {
            Role::updateOrCreate(
                ['slug' => "stable-master-{$locationType}"],
                [
                    'name' => 'Stable Master',
                    'slug' => "stable-master-{$locationType}",
                    'icon' => 'horse',
                    'description' => 'Tends to horses and sells mounts to travelers.',
                    'location_type' => $locationType,
                    'is_elected' => false,
                    'permissions' => ['sell_horses', 'buy_horses', 'stable_horses'],
                    'bonuses' => ['horse_discount' => 0.1],
                    'salary' => $locationType === 'village' ? 50 : ($locationType === 'town' ? 100 : 150),
                    'tier' => 1,
                    'is_active' => true,
                    'max_per_location' => 1,
                ]
            );
        }
    }
}

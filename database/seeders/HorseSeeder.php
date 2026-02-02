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
        // Better horses have more stamina and lower stamina cost per travel
        $horses = [
            // Common (Village) - rarity 80+
            [
                'name' => 'Draft Horse',
                'slug' => 'draft-horse',
                'description' => 'A sturdy working horse. Not fast, but reliable for travel.',
                'speed_multiplier' => 2.0,
                'base_price' => 2000,
                'min_location_type' => 'village',
                'base_stamina' => 80,
                'stamina_cost_per_travel' => 15,
                'rarity' => 85,
            ],
            [
                'name' => 'Mule',
                'slug' => 'mule',
                'description' => 'A hardy pack animal. Slow but incredibly enduring.',
                'speed_multiplier' => 1.5,
                'base_price' => 1000,
                'min_location_type' => 'village',
                'base_stamina' => 120,
                'stamina_cost_per_travel' => 10,
                'rarity' => 90,
            ],

            // Uncommon (Village) - rarity 50-79
            [
                'name' => 'Riding Horse',
                'slug' => 'riding-horse',
                'description' => 'A proper riding horse bred for comfort and moderate speed.',
                'speed_multiplier' => 2.5,
                'base_price' => 8000,
                'min_location_type' => 'village',
                'base_stamina' => 100,
                'stamina_cost_per_travel' => 12,
                'rarity' => 65,
            ],
            [
                'name' => 'Palfrey',
                'slug' => 'palfrey',
                'description' => 'A smooth-gaited horse favored by nobles for long journeys.',
                'speed_multiplier' => 2.3,
                'base_price' => 5000,
                'min_location_type' => 'village',
                'base_stamina' => 110,
                'stamina_cost_per_travel' => 11,
                'rarity' => 55,
            ],

            // Rare (Town) - rarity 25-49
            [
                'name' => 'Courser',
                'slug' => 'courser',
                'description' => 'A swift horse favored by messengers and light cavalry.',
                'speed_multiplier' => 3.0,
                'base_price' => 20000,
                'min_location_type' => 'town',
                'base_stamina' => 120,
                'stamina_cost_per_travel' => 10,
                'rarity' => 40,
            ],
            [
                'name' => 'Hunter',
                'slug' => 'hunter',
                'description' => 'A nimble horse bred for chasing game across rough terrain.',
                'speed_multiplier' => 2.8,
                'base_price' => 15000,
                'min_location_type' => 'town',
                'base_stamina' => 140,
                'stamina_cost_per_travel' => 9,
                'rarity' => 35,
            ],

            // Epic (Barony) - rarity 10-24
            [
                'name' => 'Destrier',
                'slug' => 'destrier',
                'description' => 'A powerful warhorse trained for battle and speed.',
                'speed_multiplier' => 3.5,
                'base_price' => 75000,
                'min_location_type' => 'barony',
                'base_stamina' => 150,
                'stamina_cost_per_travel' => 8,
                'rarity' => 20,
            ],
            [
                'name' => 'Charger',
                'slug' => 'charger',
                'description' => 'A heavy cavalry mount, bred for devastating battlefield charges.',
                'speed_multiplier' => 3.2,
                'base_price' => 50000,
                'min_location_type' => 'barony',
                'base_stamina' => 180,
                'stamina_cost_per_travel' => 7,
                'rarity' => 15,
            ],

            // Legendary (Kingdom) - rarity <10
            [
                'name' => 'Warhorse',
                'slug' => 'warhorse',
                'description' => 'The finest breed in the realm. Only royalty can afford such magnificence.',
                'speed_multiplier' => 4.0,
                'base_price' => 150000,
                'min_location_type' => 'kingdom',
                'base_stamina' => 200,
                'stamina_cost_per_travel' => 5,
                'rarity' => 8,
            ],
            [
                'name' => 'Shadowmere',
                'slug' => 'shadowmere',
                'description' => 'A legendary black stallion of unmatched speed and stamina.',
                'speed_multiplier' => 4.5,
                'base_price' => 225000,
                'min_location_type' => 'kingdom',
                'base_stamina' => 250,
                'stamina_cost_per_travel' => 4,
                'rarity' => 3,
            ],
        ];

        foreach ($horses as $horse) {
            Horse::updateOrCreate(
                ['slug' => $horse['slug']],
                $horse
            );
        }

        // Add Stable Master role for villages and baronies
        $stableMasterLocations = ['village', 'barony'];

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
                    'salary' => $locationType === 'village' ? 50 : 150,
                    'tier' => 1,
                    'is_active' => true,
                    'max_per_location' => 1,
                ]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Castle;
use App\Models\Kingdom;
use App\Models\Town;
use Illuminate\Database\Seeder;

class CastleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $valdoria = Kingdom::where('name', 'Valdoria')->first();
        $frostholm = Kingdom::where('name', 'Frostholm')->first();
        $sandmar = Kingdom::where('name', 'Sandmar')->first();
        $ashenfell = Kingdom::where('name', 'Ashenfell')->first();

        // Get all towns keyed by name
        $towns = Town::all()->keyBy('name');

        $castles = [
            // Valdoria Castles (3)
            [
                'name' => 'Greenhold',
                'description' => 'The capital castle of Valdoria, surrounded by vast farmlands and ancient oak forests.',
                'kingdom_id' => $valdoria->id,
                'town_name' => 'Greenhold Town',
                'biome' => 'forest',
                'tax_rate' => 10.00,
                'coordinates_x' => 10,
                'coordinates_y' => 5,
            ],
            [
                'name' => 'Riverwatch',
                'description' => 'A strategic fortress overlooking the great river that feeds Valdoria\'s farmlands.',
                'kingdom_id' => $valdoria->id,
                'town_name' => 'Riverwatch Town',
                'biome' => 'plains',
                'tax_rate' => 8.00,
                'coordinates_x' => -20,
                'coordinates_y' => 15,
            ],
            [
                'name' => 'Thornkeep',
                'description' => 'A castle at the edge of the Thornwood, guarding against creatures from the swamps.',
                'kingdom_id' => $valdoria->id,
                'town_name' => 'Thornkeep Town',
                'biome' => 'swamps',
                'tax_rate' => 7.00,
                'coordinates_x' => 30,
                'coordinates_y' => -25,
            ],

            // Frostholm Castles (2)
            [
                'name' => 'Winterspire',
                'description' => 'The frozen seat of Frostholm\'s power, built into the side of a glacier.',
                'kingdom_id' => $frostholm->id,
                'town_name' => 'Winterspire Town',
                'biome' => 'tundra',
                'tax_rate' => 8.00,
                'coordinates_x' => 110,
                'coordinates_y' => 210,
            ],
            [
                'name' => 'Ironpeak',
                'description' => 'A mountain fortress rich in iron ore, supplying Frostholm\'s legendary smiths.',
                'kingdom_id' => $frostholm->id,
                'town_name' => 'Ironpeak Town',
                'biome' => 'mountains',
                'tax_rate' => 9.00,
                'coordinates_x' => 90,
                'coordinates_y' => 180,
            ],

            // Sandmar Castles (3)
            [
                'name' => 'Tidekeep',
                'description' => 'The great harbor castle of Sandmar, controlling all trade along the coast.',
                'kingdom_id' => $sandmar->id,
                'town_name' => 'Tidekeep Town',
                'biome' => 'coastal',
                'tax_rate' => 12.00,
                'coordinates_x' => -140,
                'coordinates_y' => -90,
            ],
            [
                'name' => 'Sunspear',
                'description' => 'A desert fortress that guards the eastern trade routes through the dunes.',
                'kingdom_id' => $sandmar->id,
                'town_name' => 'Sunspear Town',
                'biome' => 'desert',
                'tax_rate' => 11.00,
                'coordinates_x' => -180,
                'coordinates_y' => -120,
            ],
            [
                'name' => 'Oasishold',
                'description' => 'Built around the largest oasis in the desert, a haven for travelers and merchants.',
                'kingdom_id' => $sandmar->id,
                'town_name' => 'Oasishold Town',
                'biome' => 'desert',
                'tax_rate' => 10.00,
                'coordinates_x' => -130,
                'coordinates_y' => -140,
            ],

            // Ashenfell Castles (2)
            [
                'name' => 'Embercrown',
                'description' => 'The volcanic capital of Ashenfell, its forges never cool and its fires never dim.',
                'kingdom_id' => $ashenfell->id,
                'town_name' => 'Embercrown Town',
                'biome' => 'volcano',
                'tax_rate' => 15.00,
                'coordinates_x' => 210,
                'coordinates_y' => -140,
            ],
            [
                'name' => 'Cinderfall',
                'description' => 'A fortress built on the ashen plains where volcanic soil yields rare minerals.',
                'kingdom_id' => $ashenfell->id,
                'town_name' => 'Cinderfall Town',
                'biome' => 'volcano',
                'tax_rate' => 13.00,
                'coordinates_x' => 180,
                'coordinates_y' => -170,
            ],
        ];

        foreach ($castles as $castleData) {
            $townName = $castleData['town_name'];
            unset($castleData['town_name']);

            $town = $towns[$townName] ?? null;
            $castleData['town_id'] = $town?->id;

            Castle::create($castleData);
        }
    }
}

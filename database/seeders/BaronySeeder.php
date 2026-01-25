<?php

namespace Database\Seeders;

use App\Models\Barony;
use App\Models\Kingdom;
use Illuminate\Database\Seeder;

class BaronySeeder extends Seeder
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

        $baronies = [
            // Valdoria Baronies (3)
            [
                'name' => 'Greenhold',
                'description' => 'The capital barony of Valdoria, surrounded by vast farmlands and ancient oak forests.',
                'kingdom_id' => $valdoria->id,
                'biome' => 'forest',
                'tax_rate' => 10.00,
                'coordinates_x' => 220,
                'coordinates_y' => 240,
            ],
            [
                'name' => 'Riverwatch',
                'description' => 'A strategic barony overlooking the great river that feeds Valdoria\'s farmlands.',
                'kingdom_id' => $valdoria->id,
                'biome' => 'plains',
                'tax_rate' => 8.00,
                'coordinates_x' => 140,
                'coordinates_y' => 200,
            ],
            [
                'name' => 'Thornkeep',
                'description' => 'A barony at the edge of the Thornwood, guarding against creatures from the swamps.',
                'kingdom_id' => $valdoria->id,
                'biome' => 'swamps',
                'tax_rate' => 7.00,
                'coordinates_x' => 300,
                'coordinates_y' => 120,
            ],

            // Frostholm Baronies (2)
            [
                'name' => 'Winterspire',
                'description' => 'The frozen seat of Frostholm\'s power, built into the side of a glacier.',
                'kingdom_id' => $frostholm->id,
                'biome' => 'tundra',
                'tax_rate' => 8.00,
                'coordinates_x' => 840,
                'coordinates_y' => 850,
            ],
            [
                'name' => 'Ironpeak',
                'description' => 'A mountain barony rich in iron ore, supplying Frostholm\'s legendary smiths.',
                'kingdom_id' => $frostholm->id,
                'biome' => 'mountains',
                'tax_rate' => 9.00,
                'coordinates_x' => 700,
                'coordinates_y' => 740,
            ],

            // Sandmar Baronies (3)
            [
                'name' => 'Tidekeep',
                'description' => 'The great harbor barony of Sandmar, controlling all trade along the coast.',
                'kingdom_id' => $sandmar->id,
                'biome' => 'coastal',
                'tax_rate' => 12.00,
                'coordinates_x' => 160,
                'coordinates_y' => 850,
            ],
            [
                'name' => 'Sunspear',
                'description' => 'A desert barony that guards the eastern trade routes through the dunes.',
                'kingdom_id' => $sandmar->id,
                'biome' => 'desert',
                'tax_rate' => 11.00,
                'coordinates_x' => 310,
                'coordinates_y' => 880,
            ],
            [
                'name' => 'Oasishold',
                'description' => 'Built around the largest oasis in the desert, a haven for travelers and merchants.',
                'kingdom_id' => $sandmar->id,
                'biome' => 'desert',
                'tax_rate' => 10.00,
                'coordinates_x' => 120,
                'coordinates_y' => 720,
            ],

            // Ashenfell Baronies (2)
            [
                'name' => 'Embercrown',
                'description' => 'The volcanic capital of Ashenfell, its forges never cool and its fires never dim.',
                'kingdom_id' => $ashenfell->id,
                'biome' => 'volcano',
                'tax_rate' => 15.00,
                'coordinates_x' => 850,
                'coordinates_y' => 240,
            ],
            [
                'name' => 'Cinderfall',
                'description' => 'A barony built on the ashen plains where volcanic soil yields rare minerals.',
                'kingdom_id' => $ashenfell->id,
                'biome' => 'volcano',
                'tax_rate' => 13.00,
                'coordinates_x' => 720,
                'coordinates_y' => 120,
            ],
        ];

        foreach ($baronies as $baronyData) {
            Barony::create($baronyData);
        }
    }
}

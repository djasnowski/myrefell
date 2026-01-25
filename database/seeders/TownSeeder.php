<?php

namespace Database\Seeders;

use App\Models\Barony;
use App\Models\Kingdom;
use App\Models\Town;
use Illuminate\Database\Seeder;

class TownSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baronies = Barony::all()->keyBy('name');

        // Towns now belong to baronies, not kingdoms directly
        $towns = [
            // Valdoria Towns (3) - under their respective baronies
            [
                'name' => 'Greenhold Town',
                'description' => 'The capital town of Valdoria, surrounded by vast farmlands and ancient oak forests.',
                'barony_name' => 'Greenhold',
                'is_capital' => true,
                'biome' => 'forest',
                'tax_rate' => 10.00,
                'population' => 5000,
                'wealth' => 100000,
                'coordinates_x' => 200,
                'coordinates_y' => 220,
            ],
            [
                'name' => 'Riverwatch Town',
                'description' => 'A strategic town overlooking the great river that feeds Valdoria\'s farmlands.',
                'barony_name' => 'Riverwatch',
                'is_capital' => false,
                'biome' => 'plains',
                'tax_rate' => 8.00,
                'population' => 3000,
                'wealth' => 60000,
                'coordinates_x' => 120,
                'coordinates_y' => 180,
            ],
            [
                'name' => 'Thornkeep Town',
                'description' => 'A town at the edge of the Thornwood, guarding against creatures from the swamps.',
                'barony_name' => 'Thornkeep',
                'is_capital' => false,
                'biome' => 'swamps',
                'tax_rate' => 7.00,
                'population' => 2000,
                'wealth' => 40000,
                'coordinates_x' => 280,
                'coordinates_y' => 140,
            ],

            // Frostholm Towns (2)
            [
                'name' => 'Winterspire Town',
                'description' => 'The frozen seat of Frostholm\'s power, built into the side of a glacier.',
                'barony_name' => 'Winterspire',
                'is_capital' => true,
                'biome' => 'tundra',
                'tax_rate' => 8.00,
                'population' => 4000,
                'wealth' => 80000,
                'coordinates_x' => 820,
                'coordinates_y' => 820,
            ],
            [
                'name' => 'Ironpeak Town',
                'description' => 'A mountain town rich in iron ore, supplying Frostholm\'s legendary smiths.',
                'barony_name' => 'Ironpeak',
                'is_capital' => false,
                'biome' => 'mountains',
                'tax_rate' => 9.00,
                'population' => 2500,
                'wealth' => 70000,
                'coordinates_x' => 720,
                'coordinates_y' => 760,
            ],

            // Sandmar Towns (3)
            [
                'name' => 'Tidekeep Town',
                'description' => 'The great harbor town of Sandmar, controlling all trade along the coast.',
                'barony_name' => 'Tidekeep',
                'is_capital' => true,
                'biome' => 'coastal',
                'tax_rate' => 12.00,
                'population' => 6000,
                'wealth' => 150000,
                'coordinates_x' => 180,
                'coordinates_y' => 820,
            ],
            [
                'name' => 'Sunspear Town',
                'description' => 'A desert town that guards the eastern trade routes through the dunes.',
                'barony_name' => 'Sunspear',
                'is_capital' => false,
                'biome' => 'desert',
                'tax_rate' => 11.00,
                'population' => 2000,
                'wealth' => 50000,
                'coordinates_x' => 280,
                'coordinates_y' => 860,
            ],
            [
                'name' => 'Oasishold Town',
                'description' => 'Built around the largest oasis in the desert, a haven for travelers and merchants.',
                'barony_name' => 'Oasishold',
                'is_capital' => false,
                'biome' => 'desert',
                'tax_rate' => 10.00,
                'population' => 3500,
                'wealth' => 80000,
                'coordinates_x' => 140,
                'coordinates_y' => 740,
            ],

            // Ashenfell Towns (2)
            [
                'name' => 'Embercrown Town',
                'description' => 'The volcanic capital of Ashenfell, its forges never cool and its fires never dim.',
                'barony_name' => 'Embercrown',
                'is_capital' => true,
                'biome' => 'volcano',
                'tax_rate' => 15.00,
                'population' => 4500,
                'wealth' => 120000,
                'coordinates_x' => 820,
                'coordinates_y' => 220,
            ],
            [
                'name' => 'Cinderfall Town',
                'description' => 'A town built on the ashen plains where volcanic soil yields rare minerals.',
                'barony_name' => 'Cinderfall',
                'is_capital' => false,
                'biome' => 'volcano',
                'tax_rate' => 13.00,
                'population' => 2000,
                'wealth' => 60000,
                'coordinates_x' => 740,
                'coordinates_y' => 140,
            ],
        ];

        foreach ($towns as $townData) {
            $barony = $baronies[$townData['barony_name']];
            $isCapital = $townData['is_capital'];

            unset($townData['barony_name'], $townData['is_capital']);
            $townData['barony_id'] = $barony->id;

            $town = Town::create($townData);

            // Set as capital town for the kingdom
            if ($isCapital) {
                Kingdom::where('id', $barony->kingdom_id)
                    ->update(['capital_town_id' => $town->id]);
            }
        }
    }
}

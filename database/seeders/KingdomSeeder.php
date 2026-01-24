<?php

namespace Database\Seeders;

use App\Models\Kingdom;
use Illuminate\Database\Seeder;

class KingdomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kingdoms = [
            [
                'name' => 'Valdoria',
                'description' => 'A prosperous kingdom nestled in fertile plains and lush forests. Known for its bountiful harvests and skilled craftsmen.',
                'biome' => 'plains',
                'tax_rate' => 10.00,
                'coordinates_x' => 0,
                'coordinates_y' => 0,
            ],
            [
                'name' => 'Frostholm',
                'description' => 'A hardy kingdom in the frozen north. Its people are resilient warriors who have adapted to the harsh tundra.',
                'biome' => 'tundra',
                'tax_rate' => 8.00,
                'coordinates_x' => 100,
                'coordinates_y' => 200,
            ],
            [
                'name' => 'Sandmar',
                'description' => 'A wealthy trading kingdom along the coastal deserts. Masters of commerce and seafaring.',
                'biome' => 'coastal',
                'tax_rate' => 12.00,
                'coordinates_x' => -150,
                'coordinates_y' => -100,
            ],
            [
                'name' => 'Ashenfell',
                'description' => 'A mysterious kingdom built around an active volcano. Its forges produce the finest weapons in all of Myrefell.',
                'biome' => 'volcano',
                'tax_rate' => 15.00,
                'coordinates_x' => 200,
                'coordinates_y' => -150,
            ],
        ];

        foreach ($kingdoms as $kingdom) {
            Kingdom::create($kingdom);
        }
    }
}

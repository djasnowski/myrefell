<?php

namespace Database\Seeders;

use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Kingdom;
use Illuminate\Database\Seeder;

class DuchySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $duchies = [
            // Valdoria - 2 duchies
            [
                'name' => 'Greenwood',
                'description' => 'The verdant heart of Valdoria, rich in forests and fertile farmland.',
                'kingdom_name' => 'Valdoria',
                'biome' => 'forest',
                'baronies' => ['Greenhold', 'Riverwatch'],
            ],
            [
                'name' => 'Thornmark',
                'description' => 'The wild eastern frontier of Valdoria, where the forests grow dark and deep.',
                'kingdom_name' => 'Valdoria',
                'biome' => 'forest',
                'baronies' => ['Thornkeep'],
            ],

            // Frostholm - 1 duchy
            [
                'name' => 'Frostpeak',
                'description' => 'The frozen realm of Frostholm, where ice never melts and hardy folk endure.',
                'kingdom_name' => 'Frostholm',
                'biome' => 'tundra',
                'baronies' => ['Winterspire', 'Ironpeak'],
            ],

            // Sandmar - 2 duchies
            [
                'name' => 'Tidewater',
                'description' => 'The prosperous coastal duchy of Sandmar, home to merchants and sailors.',
                'kingdom_name' => 'Sandmar',
                'biome' => 'coastal',
                'baronies' => ['Tidekeep', 'Sunspear'],
            ],
            [
                'name' => 'Oasis Lands',
                'description' => 'The inland duchy of Sandmar, where precious water means life and power.',
                'kingdom_name' => 'Sandmar',
                'biome' => 'desert',
                'baronies' => ['Oasishold'],
            ],

            // Ashenfell - 1 duchy
            [
                'name' => 'Emberfall',
                'description' => 'The volcanic duchy of Ashenfell, where fire forges strong steel and stronger people.',
                'kingdom_name' => 'Ashenfell',
                'biome' => 'volcanic',
                'baronies' => ['Embercrown', 'Cinderfall'],
            ],
        ];

        foreach ($duchies as $duchyData) {
            $kingdom = Kingdom::where('name', $duchyData['kingdom_name'])->first();

            if (!$kingdom) {
                $this->command->warn("Kingdom {$duchyData['kingdom_name']} not found, skipping duchy {$duchyData['name']}");
                continue;
            }

            $duchy = Duchy::updateOrCreate(
                ['name' => $duchyData['name'], 'kingdom_id' => $kingdom->id],
                [
                    'description' => $duchyData['description'],
                    'biome' => $duchyData['biome'],
                    'tax_rate' => 8.00,
                ]
            );

            // Assign baronies to this duchy
            foreach ($duchyData['baronies'] as $baronyName) {
                Barony::where('name', $baronyName)
                    ->where('kingdom_id', $kingdom->id)
                    ->update(['duchy_id' => $duchy->id]);
            }

            $this->command->info("Created duchy: {$duchy->name} with " . count($duchyData['baronies']) . " baronies");
        }
    }
}

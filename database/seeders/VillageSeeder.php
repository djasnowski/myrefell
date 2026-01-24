<?php

namespace Database\Seeders;

use App\Models\Castle;
use App\Models\Village;
use Illuminate\Database\Seeder;

class VillageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $castles = Castle::all()->keyBy('name');

        $villages = [
            // Greenhold Villages (6) - Forest/Plains
            ['name' => 'Oakvale', 'castle' => 'Greenhold', 'biome' => 'forest', 'description' => 'A peaceful village surrounded by ancient oaks.'],
            ['name' => 'Millbrook', 'castle' => 'Greenhold', 'biome' => 'plains', 'description' => 'Known for its water mills and fresh bread.'],
            ['name' => 'Fernglen', 'castle' => 'Greenhold', 'biome' => 'forest', 'description' => 'Hidden among ferns and wildflowers.'],
            ['name' => 'Hayfield', 'castle' => 'Greenhold', 'biome' => 'plains', 'description' => 'The breadbasket of Valdoria.'],
            ['name' => 'Willowmere', 'castle' => 'Greenhold', 'biome' => 'forest', 'description' => 'A village by a tranquil lake of willows.'],
            ['name' => 'Honeyhill', 'castle' => 'Greenhold', 'biome' => 'plains', 'description' => 'Famous for its apiaries and sweet mead.'],

            // Riverwatch Villages (6) - Plains - includes port
            ['name' => 'Riverside Landing', 'castle' => 'Riverwatch', 'biome' => 'plains', 'description' => 'Where the great river meets the sea. Ships depart for distant kingdoms.', 'is_port' => true],
            ['name' => 'Brookside', 'castle' => 'Riverwatch', 'biome' => 'plains', 'description' => 'A fishing village along the great river.'],
            ['name' => 'Grainford', 'castle' => 'Riverwatch', 'biome' => 'plains', 'description' => 'Where the grain barges cross the ford.'],
            ['name' => 'Meadowbrook', 'castle' => 'Riverwatch', 'biome' => 'plains', 'description' => 'Rolling meadows as far as the eye can see.'],
            ['name' => 'Shepherds Rest', 'castle' => 'Riverwatch', 'biome' => 'plains', 'description' => 'Home to the finest wool in the kingdom.'],
            ['name' => 'Barleywick', 'castle' => 'Riverwatch', 'biome' => 'plains', 'description' => 'Brewers of renowned ales.'],

            // Thornkeep Villages (4) - Swamps/Forest edge
            ['name' => 'Bogmire', 'castle' => 'Thornkeep', 'biome' => 'swamps', 'description' => 'Hardy folk who harvest the swamp\'s secrets.'],
            ['name' => 'Marshhollow', 'castle' => 'Thornkeep', 'biome' => 'swamps', 'description' => 'Built on stilts above the murky waters.'],
            ['name' => 'Fenwick', 'castle' => 'Thornkeep', 'biome' => 'swamps', 'description' => 'Gatherers of rare herbs and mosses.'],
            ['name' => 'Briarwood', 'castle' => 'Thornkeep', 'biome' => 'forest', 'description' => 'At the edge where forest meets swamp.'],

            // Winterspire Villages (6) - Tundra/Mountains - includes port
            ['name' => 'Icebreaker Bay', 'castle' => 'Winterspire', 'biome' => 'tundra', 'description' => 'Hardy sailors brave the frozen waters to reach distant shores.', 'is_port' => true],
            ['name' => 'Frostford', 'castle' => 'Winterspire', 'biome' => 'tundra', 'description' => 'Where the frozen river can be crossed.'],
            ['name' => 'Snowhaven', 'castle' => 'Winterspire', 'biome' => 'tundra', 'description' => 'A warm hearth in the frozen wastes.'],
            ['name' => 'Icewind', 'castle' => 'Winterspire', 'biome' => 'tundra', 'description' => 'The northernmost settlement in Myrefell.'],
            ['name' => 'Whitepine', 'castle' => 'Winterspire', 'biome' => 'tundra', 'description' => 'Lumber from the frozen forests.'],
            ['name' => 'Glacierfall', 'castle' => 'Winterspire', 'biome' => 'tundra', 'description' => 'Where ice calves from the great glacier.'],

            // Ironpeak Villages (5) - Mountains
            ['name' => 'Copperdale', 'castle' => 'Ironpeak', 'biome' => 'mountains', 'description' => 'Rich copper veins in the hills.'],
            ['name' => 'Stonebridge', 'castle' => 'Ironpeak', 'biome' => 'mountains', 'description' => 'An ancient bridge spans the gorge.'],
            ['name' => 'Pickaxe Point', 'castle' => 'Ironpeak', 'biome' => 'mountains', 'description' => 'Miners work the deep tunnels.'],
            ['name' => 'Highcliff', 'castle' => 'Ironpeak', 'biome' => 'mountains', 'description' => 'Perched on a dramatic cliff face.'],
            ['name' => 'Quarrytown', 'castle' => 'Ironpeak', 'biome' => 'mountains', 'description' => 'Stone for all of Frostholm comes from here.'],

            // Tidekeep Villages (5) - Coastal - includes port
            ['name' => 'Saltmere', 'castle' => 'Tidekeep', 'biome' => 'coastal', 'description' => 'Salt harvested from evaporation pools.'],
            ['name' => 'Fishermans Cove', 'castle' => 'Tidekeep', 'biome' => 'coastal', 'description' => 'The finest catch in all of Sandmar.'],
            ['name' => 'Seashell Bay', 'castle' => 'Tidekeep', 'biome' => 'coastal', 'description' => 'Pearl divers and shell collectors.'],
            ['name' => 'Anchor Point', 'castle' => 'Tidekeep', 'biome' => 'coastal', 'description' => 'The great harbor of Sandmar. Ships sail to all corners of the world.', 'is_port' => true],
            ['name' => 'Coraltown', 'castle' => 'Tidekeep', 'biome' => 'coastal', 'description' => 'Beautiful coral jewelry is crafted here.'],

            // Sunspear Villages (5) - Desert
            ['name' => 'Dustwell', 'castle' => 'Sunspear', 'biome' => 'desert', 'description' => 'A precious well in the endless dunes.'],
            ['name' => 'Sandstone', 'castle' => 'Sunspear', 'biome' => 'desert', 'description' => 'Carved from the living rock.'],
            ['name' => 'Mirage', 'castle' => 'Sunspear', 'biome' => 'desert', 'description' => 'Appears and disappears with the heat.'],
            ['name' => 'Scorpions Tail', 'castle' => 'Sunspear', 'biome' => 'desert', 'description' => 'Named for the rock formation above.'],
            ['name' => 'Dunewatch', 'castle' => 'Sunspear', 'biome' => 'desert', 'description' => 'Scouts watch for sandstorms here.'],

            // Oasishold Villages (5) - Desert
            ['name' => 'Palm Springs', 'castle' => 'Oasishold', 'biome' => 'desert', 'description' => 'Date palms and fresh springs.'],
            ['name' => 'Camelback', 'castle' => 'Oasishold', 'biome' => 'desert', 'description' => 'Trading post for caravans.'],
            ['name' => 'Silkroad', 'castle' => 'Oasishold', 'biome' => 'desert', 'description' => 'Where silk merchants rest.'],
            ['name' => 'Starfall', 'castle' => 'Oasishold', 'biome' => 'desert', 'description' => 'Astronomers study the clear night skies.'],
            ['name' => 'Spicetown', 'castle' => 'Oasishold', 'biome' => 'desert', 'description' => 'Exotic spices from distant lands.'],

            // Embercrown Villages (6) - Volcano - includes port
            ['name' => 'Ember Harbor', 'castle' => 'Embercrown', 'biome' => 'volcano', 'description' => 'Where lava meets sea. Ships dock at obsidian piers.', 'is_port' => true],
            ['name' => 'Ashdale', 'castle' => 'Embercrown', 'biome' => 'volcano', 'description' => 'Fertile ash soil for rare crops.'],
            ['name' => 'Sulfur Springs', 'castle' => 'Embercrown', 'biome' => 'volcano', 'description' => 'Hot springs with healing properties.'],
            ['name' => 'Obsidian', 'castle' => 'Embercrown', 'biome' => 'volcano', 'description' => 'Black glass harvested from lava flows.'],
            ['name' => 'Flamekeep', 'castle' => 'Embercrown', 'biome' => 'volcano', 'description' => 'The eternal flame burns here.'],
            ['name' => 'Smolder', 'castle' => 'Embercrown', 'biome' => 'volcano', 'description' => 'Where the ground still smokes.'],

            // Cinderfall Villages (5) - Volcano
            ['name' => 'Charcoal', 'castle' => 'Cinderfall', 'biome' => 'volcano', 'description' => 'Produces the finest fuel for forges.'],
            ['name' => 'Lavabend', 'castle' => 'Cinderfall', 'biome' => 'volcano', 'description' => 'Where the ancient lava river turned.'],
            ['name' => 'Ember', 'castle' => 'Cinderfall', 'biome' => 'volcano', 'description' => 'Glowing coals in the night.'],
            ['name' => 'Pumice', 'castle' => 'Cinderfall', 'biome' => 'volcano', 'description' => 'Light volcanic stone is mined here.'],
            ['name' => 'Caldera', 'castle' => 'Cinderfall', 'biome' => 'volcano', 'description' => 'In the bowl of an ancient volcano.'],
        ];

        // Port positions - absolute coordinates for precise placement
        // World coords: (0,0) at bottom-left, max ~(772, 817)
        $portAbsoluteCoords = [
            'Riverside Landing' => ['x' => 77, 'y' => 80],    // Valdoria - top right
            'Anchor Point' => ['x' => 154, 'y' => 524],       // Sandmar - bottom right
            'Icebreaker Bay' => ['x' => 752, 'y' => 800],     // Frostholm - bottom
            'Ember Harbor' => ['x' => 767, 'y' => 115],       // Ashenfell - top
        ];

        // Track village index per castle for spreading villages around each castle
        $castleVillageIndex = [];

        foreach ($villages as $villageData) {
            $castle = $castles[$villageData['castle']];
            $castleName = $villageData['castle'];

            // Initialize counter for this castle
            if (! isset($castleVillageIndex[$castleName])) {
                $castleVillageIndex[$castleName] = 0;
            }

            $index = $castleVillageIndex[$castleName];
            $castleVillageIndex[$castleName]++;

            // Check if this is a port with absolute positioning
            if (isset($portAbsoluteCoords[$villageData['name']])) {
                $absoluteX = $portAbsoluteCoords[$villageData['name']]['x'];
                $absoluteY = $portAbsoluteCoords[$villageData['name']]['y'];
            } else {
                // Spread villages in a circle around the castle (±30 units)
                // Each village gets a different angle to distribute them evenly
                $angle = ($index * 60 + rand(-15, 15)) * (M_PI / 180); // Convert to radians
                $distance = 20 + rand(0, 15); // 20-35 units from castle

                $offsetX = (int) round(cos($angle) * $distance);
                $offsetY = (int) round(sin($angle) * $distance);
            }

            // Use absolute coords for ports, offset coords for regular villages
            $finalX = isset($absoluteX) ? $absoluteX : $castle->coordinates_x + $offsetX;
            $finalY = isset($absoluteY) ? $absoluteY : $castle->coordinates_y + $offsetY;

            Village::create([
                'name' => $villageData['name'],
                'description' => $villageData['description'],
                'castle_id' => $castle->id,
                'is_town' => false,
                'is_port' => $villageData['is_port'] ?? false,
                'population' => rand(50, 500),
                'wealth' => rand(1000, 50000),
                'biome' => $villageData['biome'],
                'coordinates_x' => $finalX,
                'coordinates_y' => $finalY,
            ]);

            // Reset for next iteration
            unset($absoluteX, $absoluteY, $offsetX, $offsetY);
        }
    }
}

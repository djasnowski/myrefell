<?php

namespace Database\Seeders;

use App\Models\Barony;
use App\Models\Village;
use Illuminate\Database\Seeder;

class VillageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baronies = Barony::all()->keyBy('name');

        $villages = [
            // Greenhold Villages (6) - Forest/Plains
            ['name' => 'Oakvale', 'barony' => 'Greenhold', 'biome' => 'forest', 'description' => 'A peaceful village surrounded by ancient oaks.'],
            ['name' => 'Millbrook', 'barony' => 'Greenhold', 'biome' => 'plains', 'description' => 'Known for its water mills and fresh bread.'],
            ['name' => 'Fernglen', 'barony' => 'Greenhold', 'biome' => 'forest', 'description' => 'Hidden among ferns and wildflowers.'],
            ['name' => 'Hayfield', 'barony' => 'Greenhold', 'biome' => 'plains', 'description' => 'The breadbasket of Valdoria.'],
            ['name' => 'Willowmere', 'barony' => 'Greenhold', 'biome' => 'forest', 'description' => 'A village by a tranquil lake of willows.'],
            ['name' => 'Honeyhill', 'barony' => 'Greenhold', 'biome' => 'plains', 'description' => 'Famous for its apiaries and sweet mead.'],

            // Riverwatch Villages (6) - Plains - includes port
            ['name' => 'Riverside Landing', 'barony' => 'Riverwatch', 'biome' => 'plains', 'description' => 'Where the great river meets the sea. Ships depart for distant kingdoms.', 'is_port' => true],
            ['name' => 'Brookside', 'barony' => 'Riverwatch', 'biome' => 'plains', 'description' => 'A fishing village along the great river.'],
            ['name' => 'Grainford', 'barony' => 'Riverwatch', 'biome' => 'plains', 'description' => 'Where the grain barges cross the ford.'],
            ['name' => 'Meadowbrook', 'barony' => 'Riverwatch', 'biome' => 'plains', 'description' => 'Rolling meadows as far as the eye can see.'],
            ['name' => 'Shepherds Rest', 'barony' => 'Riverwatch', 'biome' => 'plains', 'description' => 'Home to the finest wool in the kingdom.'],
            ['name' => 'Barleywick', 'barony' => 'Riverwatch', 'biome' => 'plains', 'description' => 'Brewers of renowned ales.'],

            // Thornkeep Villages (4) - Swamps/Forest edge
            ['name' => 'Bogmire', 'barony' => 'Thornkeep', 'biome' => 'swamps', 'description' => 'Hardy folk who harvest the swamp\'s secrets.'],
            ['name' => 'Marshhollow', 'barony' => 'Thornkeep', 'biome' => 'swamps', 'description' => 'Built on stilts above the murky waters.'],
            ['name' => 'Fenwick', 'barony' => 'Thornkeep', 'biome' => 'swamps', 'description' => 'Gatherers of rare herbs and mosses.'],
            ['name' => 'Briarwood', 'barony' => 'Thornkeep', 'biome' => 'forest', 'description' => 'At the edge where forest meets swamp.'],

            // Winterspire Villages (6) - Tundra/Mountains - includes port
            ['name' => 'Icebreaker Bay', 'barony' => 'Winterspire', 'biome' => 'tundra', 'description' => 'Hardy sailors brave the frozen waters to reach distant shores.', 'is_port' => true],
            ['name' => 'Frostford', 'barony' => 'Winterspire', 'biome' => 'tundra', 'description' => 'Where the frozen river can be crossed.'],
            ['name' => 'Snowhaven', 'barony' => 'Winterspire', 'biome' => 'tundra', 'description' => 'A warm hearth in the frozen wastes.'],
            ['name' => 'Icewind', 'barony' => 'Winterspire', 'biome' => 'tundra', 'description' => 'The northernmost settlement in Myrefell.'],
            ['name' => 'Whitepine', 'barony' => 'Winterspire', 'biome' => 'tundra', 'description' => 'Lumber from the frozen forests.'],
            ['name' => 'Glacierfall', 'barony' => 'Winterspire', 'biome' => 'tundra', 'description' => 'Where ice calves from the great glacier.'],

            // Ironpeak Villages (5) - Mountains
            ['name' => 'Copperdale', 'barony' => 'Ironpeak', 'biome' => 'mountains', 'description' => 'Rich copper veins in the hills.'],
            ['name' => 'Stonebridge', 'barony' => 'Ironpeak', 'biome' => 'mountains', 'description' => 'An ancient bridge spans the gorge.'],
            ['name' => 'Pickaxe Point', 'barony' => 'Ironpeak', 'biome' => 'mountains', 'description' => 'Miners work the deep tunnels.'],
            ['name' => 'Highcliff', 'barony' => 'Ironpeak', 'biome' => 'mountains', 'description' => 'Perched on a dramatic cliff face.'],
            ['name' => 'Quarrytown', 'barony' => 'Ironpeak', 'biome' => 'mountains', 'description' => 'Stone for all of Frostholm comes from here.'],

            // Tidekeep Villages (5) - Coastal - includes port
            ['name' => 'Saltmere', 'barony' => 'Tidekeep', 'biome' => 'coastal', 'description' => 'Salt harvested from evaporation pools.'],
            ['name' => 'Fishermans Cove', 'barony' => 'Tidekeep', 'biome' => 'coastal', 'description' => 'The finest catch in all of Sandmar.'],
            ['name' => 'Seashell Bay', 'barony' => 'Tidekeep', 'biome' => 'coastal', 'description' => 'Pearl divers and shell collectors.'],
            ['name' => 'Anchor Point', 'barony' => 'Tidekeep', 'biome' => 'coastal', 'description' => 'The great harbor of Sandmar. Ships sail to all corners of the world.', 'is_port' => true],
            ['name' => 'Coraltown', 'barony' => 'Tidekeep', 'biome' => 'coastal', 'description' => 'Beautiful coral jewelry is crafted here.'],

            // Sunspear Villages (5) - Desert
            ['name' => 'Dustwell', 'barony' => 'Sunspear', 'biome' => 'desert', 'description' => 'A precious well in the endless dunes.'],
            ['name' => 'Sandstone', 'barony' => 'Sunspear', 'biome' => 'desert', 'description' => 'Carved from the living rock.'],
            ['name' => 'Mirage', 'barony' => 'Sunspear', 'biome' => 'desert', 'description' => 'Appears and disappears with the heat.'],
            ['name' => 'Scorpions Tail', 'barony' => 'Sunspear', 'biome' => 'desert', 'description' => 'Named for the rock formation above.'],
            ['name' => 'Dunewatch', 'barony' => 'Sunspear', 'biome' => 'desert', 'description' => 'Scouts watch for sandstorms here.'],

            // Oasishold Villages (5) - Desert
            ['name' => 'Palm Springs', 'barony' => 'Oasishold', 'biome' => 'desert', 'description' => 'Date palms and fresh springs.'],
            ['name' => 'Camelback', 'barony' => 'Oasishold', 'biome' => 'desert', 'description' => 'Trading post for caravans.'],
            ['name' => 'Silkroad', 'barony' => 'Oasishold', 'biome' => 'desert', 'description' => 'Where silk merchants rest.'],
            ['name' => 'Starfall', 'barony' => 'Oasishold', 'biome' => 'desert', 'description' => 'Astronomers study the clear night skies.'],
            ['name' => 'Spicetown', 'barony' => 'Oasishold', 'biome' => 'desert', 'description' => 'Exotic spices from distant lands.'],

            // Embercrown Villages (6) - Volcano - includes port
            ['name' => 'Ember Harbor', 'barony' => 'Embercrown', 'biome' => 'volcano', 'description' => 'Where lava meets sea. Ships dock at obsidian piers.', 'is_port' => true],
            ['name' => 'Ashdale', 'barony' => 'Embercrown', 'biome' => 'volcano', 'description' => 'Fertile ash soil for rare crops.'],
            ['name' => 'Sulfur Springs', 'barony' => 'Embercrown', 'biome' => 'volcano', 'description' => 'Hot springs with healing properties.'],
            ['name' => 'Obsidian', 'barony' => 'Embercrown', 'biome' => 'volcano', 'description' => 'Black glass harvested from lava flows.'],
            ['name' => 'Flamekeep', 'barony' => 'Embercrown', 'biome' => 'volcano', 'description' => 'The eternal flame burns here.'],
            ['name' => 'Smolder', 'barony' => 'Embercrown', 'biome' => 'volcano', 'description' => 'Where the ground still smokes.'],

            // Cinderfall Villages (5) - Volcano
            ['name' => 'Charcoal', 'barony' => 'Cinderfall', 'biome' => 'volcano', 'description' => 'Produces the finest fuel for forges.'],
            ['name' => 'Lavabend', 'barony' => 'Cinderfall', 'biome' => 'volcano', 'description' => 'Where the ancient lava river turned.'],
            ['name' => 'Ember', 'barony' => 'Cinderfall', 'biome' => 'volcano', 'description' => 'Glowing coals in the night.'],
            ['name' => 'Pumice', 'barony' => 'Cinderfall', 'biome' => 'volcano', 'description' => 'Light volcanic stone is mined here.'],
            ['name' => 'Caldera', 'barony' => 'Cinderfall', 'biome' => 'volcano', 'description' => 'In the bowl of an ancient volcano.'],
        ];

        // Port positions - absolute coordinates for precise placement
        $portAbsoluteCoords = [
            'Riverside Landing' => ['x' => 77, 'y' => 80],
            'Anchor Point' => ['x' => 154, 'y' => 524],
            'Icebreaker Bay' => ['x' => 752, 'y' => 800],
            'Ember Harbor' => ['x' => 767, 'y' => 115],
        ];

        // Track village index per barony for spreading villages around each barony
        $baronyVillageIndex = [];

        foreach ($villages as $villageData) {
            $barony = $baronies[$villageData['barony']];
            $baronyName = $villageData['barony'];

            // Initialize counter for this barony
            if (! isset($baronyVillageIndex[$baronyName])) {
                $baronyVillageIndex[$baronyName] = 0;
            }

            $index = $baronyVillageIndex[$baronyName];
            $baronyVillageIndex[$baronyName]++;

            // Check if this is a port with absolute positioning
            if (isset($portAbsoluteCoords[$villageData['name']])) {
                $absoluteX = $portAbsoluteCoords[$villageData['name']]['x'];
                $absoluteY = $portAbsoluteCoords[$villageData['name']]['y'];
            } else {
                // Spread villages in a circle around the barony (±30 units)
                $angle = ($index * 60 + rand(-15, 15)) * (M_PI / 180);
                $distance = 20 + rand(0, 15);

                $offsetX = (int) round(cos($angle) * $distance);
                $offsetY = (int) round(sin($angle) * $distance);
            }

            // Use absolute coords for ports, offset coords for regular villages
            $finalX = isset($absoluteX) ? $absoluteX : $barony->coordinates_x + $offsetX;
            $finalY = isset($absoluteY) ? $absoluteY : $barony->coordinates_y + $offsetY;

            Village::create([
                'name' => $villageData['name'],
                'description' => $villageData['description'],
                'barony_id' => $barony->id,
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

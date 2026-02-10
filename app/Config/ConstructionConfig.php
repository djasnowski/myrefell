<?php

namespace App\Config;

class ConstructionConfig
{
    /**
     * House tier definitions.
     */
    public const HOUSE_TIERS = [
        'cottage' => [
            'name' => 'Cottage',
            'level' => 1,
            'title_level' => 2,
            'cost' => 50000,
            'grid' => 3,
            'max_rooms' => 3,
            'storage' => 100,
            'upkeep' => 250,
        ],
        // Future tiers (not purchasable in Phase 1)
        'house' => [
            'name' => 'House',
            'level' => 20,
            'title_level' => 3,
            'cost' => 250000,
            'grid' => 4,
            'max_rooms' => 6,
            'storage' => 250,
            'upkeep' => 750,
        ],
        'manor' => [
            'name' => 'Manor',
            'level' => 40,
            'title_level' => 4,
            'cost' => 1000000,
            'grid' => 5,
            'max_rooms' => 10,
            'storage' => 500,
            'upkeep' => 1500,
        ],
    ];

    /**
     * Room definitions with hotspots and furniture options.
     */
    public const ROOMS = [
        'parlour' => [
            'name' => 'Parlour',
            'description' => 'A welcoming room for greeting visitors.',
            'level' => 1,
            'cost' => 15000,
            'hotspots' => [
                'chair' => [
                    'name' => 'Chair',
                    'options' => [
                        'crude_chair' => [
                            'name' => 'Crude Chair',
                            'level' => 1,
                            'materials' => ['Plank' => 3, 'Nails' => 2],
                            'xp' => 30,
                        ],
                        'wooden_chair' => [
                            'name' => 'Wooden Chair',
                            'level' => 10,
                            'materials' => ['Oak Plank' => 3],
                            'xp' => 120,
                        ],
                    ],
                ],
                'bookcase' => [
                    'name' => 'Bookcase',
                    'options' => [
                        'wooden_bookcase' => [
                            'name' => 'Wooden Bookcase',
                            'level' => 4,
                            'materials' => ['Plank' => 4, 'Nails' => 3],
                            'xp' => 50,
                        ],
                        'oak_bookcase' => [
                            'name' => 'Oak Bookcase',
                            'level' => 20,
                            'materials' => ['Oak Plank' => 4],
                            'xp' => 200,
                        ],
                    ],
                ],
                'fireplace' => [
                    'name' => 'Fireplace',
                    'options' => [
                        'clay_fireplace' => [
                            'name' => 'Clay Fireplace',
                            'level' => 3,
                            'materials' => ['Plank' => 3, 'Steel Bar' => 1],
                            'xp' => 40,
                        ],
                        'stone_fireplace' => [
                            'name' => 'Stone Fireplace',
                            'level' => 30,
                            'materials' => ['Oak Plank' => 3, 'Steel Bar' => 3],
                            'xp' => 250,
                        ],
                    ],
                ],
                'rug' => [
                    'name' => 'Rug',
                    'options' => [
                        'brown_rug' => [
                            'name' => 'Brown Rug',
                            'level' => 2,
                            'materials' => ['Cloth' => 2],
                            'xp' => 20,
                        ],
                        'patterned_rug' => [
                            'name' => 'Patterned Rug',
                            'level' => 15,
                            'materials' => ['Cloth' => 3],
                            'xp' => 100,
                        ],
                    ],
                ],
            ],
        ],
        'kitchen' => [
            'name' => 'Kitchen',
            'description' => 'A place to prepare food and store provisions.',
            'level' => 5,
            'cost' => 50000,
            'hotspots' => [
                'stove' => [
                    'name' => 'Stove',
                    'options' => [
                        'firepit' => [
                            'name' => 'Firepit',
                            'level' => 5,
                            'materials' => ['Plank' => 5, 'Nails' => 3],
                            'xp' => 50,
                            'effect' => ['burn_reduction' => 25],
                        ],
                        'iron_stove' => [
                            'name' => 'Iron Stove',
                            'level' => 20,
                            'materials' => ['Oak Plank' => 3, 'Steel Bar' => 2],
                            'xp' => 150,
                            'effect' => ['burn_reduction' => 45],
                        ],
                    ],
                ],
                'larder' => [
                    'name' => 'Larder',
                    'options' => [
                        'wooden_larder' => [
                            'name' => 'Wooden Larder',
                            'level' => 9,
                            'materials' => ['Plank' => 6, 'Nails' => 4],
                            'xp' => 70,
                        ],
                        'oak_larder' => [
                            'name' => 'Oak Larder',
                            'level' => 30,
                            'materials' => ['Oak Plank' => 5],
                            'xp' => 250,
                        ],
                    ],
                ],
                'shelf' => [
                    'name' => 'Shelves',
                    'options' => [
                        'wooden_shelves' => [
                            'name' => 'Wooden Shelves',
                            'level' => 6,
                            'materials' => ['Plank' => 4, 'Nails' => 2],
                            'xp' => 45,
                        ],
                        'oak_shelves' => [
                            'name' => 'Oak Shelves',
                            'level' => 25,
                            'materials' => ['Oak Plank' => 3],
                            'xp' => 200,
                        ],
                    ],
                ],
                'sink' => [
                    'name' => 'Sink',
                    'options' => [
                        'pump_and_drain' => [
                            'name' => 'Pump & Drain',
                            'level' => 7,
                            'materials' => ['Plank' => 3, 'Steel Bar' => 1],
                            'xp' => 60,
                        ],
                    ],
                ],
                'table' => [
                    'name' => 'Table',
                    'options' => [
                        'kitchen_table' => [
                            'name' => 'Kitchen Table',
                            'level' => 8,
                            'materials' => ['Plank' => 4, 'Nails' => 2],
                            'xp' => 65,
                        ],
                        'oak_table' => [
                            'name' => 'Oak Table',
                            'level' => 30,
                            'materials' => ['Oak Plank' => 4],
                            'xp' => 280,
                        ],
                    ],
                ],
            ],
        ],
        'bedroom' => [
            'name' => 'Bedroom',
            'description' => 'A restful chamber for recovery and contemplation.',
            'level' => 10,
            'cost' => 50000,
            'hotspots' => [
                'bed' => [
                    'name' => 'Bed',
                    'options' => [
                        'straw_bed' => [
                            'name' => 'Straw Bed',
                            'level' => 10,
                            'materials' => ['Plank' => 5, 'Cloth' => 2],
                            'xp' => 80,
                            'effect' => ['energy_regen_bonus' => 5],
                        ],
                        'wooden_bed' => [
                            'name' => 'Wooden Bed',
                            'level' => 20,
                            'materials' => ['Oak Plank' => 4, 'Cloth' => 3],
                            'xp' => 200,
                            'effect' => ['energy_regen_bonus' => 10],
                        ],
                    ],
                ],
                'wardrobe' => [
                    'name' => 'Wardrobe',
                    'options' => [
                        'wooden_wardrobe' => [
                            'name' => 'Wooden Wardrobe',
                            'level' => 12,
                            'materials' => ['Plank' => 4, 'Nails' => 3],
                            'xp' => 90,
                        ],
                    ],
                ],
                'dresser' => [
                    'name' => 'Dresser',
                    'options' => [
                        'wooden_dresser' => [
                            'name' => 'Wooden Dresser',
                            'level' => 11,
                            'materials' => ['Plank' => 3, 'Nails' => 2],
                            'xp' => 75,
                        ],
                    ],
                ],
                'rug' => [
                    'name' => 'Rug',
                    'options' => [
                        'brown_rug' => [
                            'name' => 'Brown Rug',
                            'level' => 2,
                            'materials' => ['Cloth' => 2],
                            'xp' => 20,
                        ],
                    ],
                ],
            ],
        ],
        'workshop' => [
            'name' => 'Workshop',
            'description' => 'A practical space for crafting and tool maintenance.',
            'level' => 20,
            'cost' => 150000,
            'hotspots' => [
                'workbench' => [
                    'name' => 'Workbench',
                    'options' => [
                        'wooden_workbench' => [
                            'name' => 'Wooden Workbench',
                            'level' => 20,
                            'materials' => ['Oak Plank' => 4, 'Steel Bar' => 2],
                            'xp' => 180,
                            'effect' => ['crafting_xp_bonus' => 3],
                        ],
                        'oak_workbench' => [
                            'name' => 'Oak Workbench',
                            'level' => 35,
                            'materials' => ['Willow Plank' => 5, 'Steel Bar' => 3],
                            'xp' => 350,
                            'effect' => ['crafting_xp_bonus' => 5],
                        ],
                    ],
                ],
                'repair_bench' => [
                    'name' => 'Repair Bench',
                    'options' => [
                        'basic_repair_bench' => [
                            'name' => 'Basic Repair Bench',
                            'level' => 22,
                            'materials' => ['Oak Plank' => 3, 'Steel Bar' => 2],
                            'xp' => 200,
                        ],
                        'reinforced_repair_bench' => [
                            'name' => 'Reinforced Repair Bench',
                            'level' => 40,
                            'materials' => ['Willow Plank' => 4, 'Mithril Bar' => 2],
                            'xp' => 400,
                        ],
                    ],
                ],
                'tool_rack' => [
                    'name' => 'Tool Rack',
                    'options' => [
                        'wooden_tool_rack' => [
                            'name' => 'Wooden Tool Rack',
                            'level' => 21,
                            'materials' => ['Oak Plank' => 3, 'Nails' => 2],
                            'xp' => 160,
                        ],
                        'steel_tool_rack' => [
                            'name' => 'Steel Tool Rack',
                            'level' => 38,
                            'materials' => ['Willow Plank' => 3, 'Steel Bar' => 3],
                            'xp' => 360,
                        ],
                    ],
                ],
                'whetstone' => [
                    'name' => 'Whetstone',
                    'options' => [
                        'rough_whetstone' => [
                            'name' => 'Rough Whetstone',
                            'level' => 25,
                            'materials' => ['Limestone Brick' => 2, 'Steel Bar' => 1],
                            'xp' => 220,
                            'effect' => ['attack_bonus' => 1],
                        ],
                        'fine_whetstone' => [
                            'name' => 'Fine Whetstone',
                            'level' => 45,
                            'materials' => ['Marble Block' => 2, 'Mithril Bar' => 1],
                            'xp' => 500,
                            'effect' => ['attack_bonus' => 3],
                        ],
                    ],
                ],
                'heraldry_stand' => [
                    'name' => 'Heraldry Stand',
                    'options' => [
                        'wooden_stand' => [
                            'name' => 'Wooden Stand',
                            'level' => 23,
                            'materials' => ['Oak Plank' => 3, 'Cloth' => 2],
                            'xp' => 190,
                        ],
                    ],
                ],
            ],
        ],
        'study' => [
            'name' => 'Study',
            'description' => 'A quiet room for learning and contemplation.',
            'level' => 30,
            'cost' => 200000,
            'hotspots' => [
                'lectern' => [
                    'name' => 'Lectern',
                    'options' => [
                        'wooden_lectern' => [
                            'name' => 'Wooden Lectern',
                            'level' => 30,
                            'materials' => ['Oak Plank' => 4, 'Cloth' => 2],
                            'xp' => 250,
                        ],
                        'eagle_lectern' => [
                            'name' => 'Eagle Lectern',
                            'level' => 50,
                            'materials' => ['Maple Plank' => 4, 'Gold Bar' => 1],
                            'xp' => 500,
                        ],
                    ],
                ],
                'globe' => [
                    'name' => 'Globe',
                    'options' => [
                        'small_globe' => [
                            'name' => 'Small Globe',
                            'level' => 32,
                            'materials' => ['Oak Plank' => 3, 'Gold Bar' => 1],
                            'xp' => 280,
                        ],
                    ],
                ],
                'bookcase' => [
                    'name' => 'Bookcase',
                    'options' => [
                        'oak_bookcase' => [
                            'name' => 'Oak Bookcase',
                            'level' => 30,
                            'materials' => ['Oak Plank' => 5],
                            'xp' => 260,
                            'effect' => ['gathering_xp_bonus' => 2],
                        ],
                        'marble_bookcase' => [
                            'name' => 'Marble Bookcase',
                            'level' => 55,
                            'materials' => ['Marble Block' => 3, 'Gold Leaf' => 2],
                            'xp' => 700,
                            'effect' => ['gathering_xp_bonus' => 5],
                        ],
                    ],
                ],
                'telescope' => [
                    'name' => 'Telescope',
                    'options' => [
                        'bronze_telescope' => [
                            'name' => 'Bronze Telescope',
                            'level' => 35,
                            'materials' => ['Willow Plank' => 3, 'Steel Bar' => 2],
                            'xp' => 320,
                            'effect' => ['farming_yield_bonus' => 3],
                        ],
                        'gilded_telescope' => [
                            'name' => 'Gilded Telescope',
                            'level' => 60,
                            'materials' => ['Yew Plank' => 3, 'Gold Leaf' => 1],
                            'xp' => 750,
                            'effect' => ['farming_yield_bonus' => 8],
                        ],
                    ],
                ],
            ],
        ],
        'hearth_room' => [
            'name' => 'Hearth Room',
            'description' => 'A warm gathering place centered around a grand fireplace.',
            'level' => 30,
            'cost' => 150000,
            'hotspots' => [
                'fireplace' => [
                    'name' => 'Fireplace',
                    'options' => [
                        'stone_fireplace' => [
                            'name' => 'Stone Fireplace',
                            'level' => 30,
                            'materials' => ['Limestone Brick' => 3, 'Oak Plank' => 2],
                            'xp' => 250,
                            'effect' => ['max_hp_bonus' => 3],
                        ],
                        'marble_fireplace' => [
                            'name' => 'Marble Fireplace',
                            'level' => 55,
                            'materials' => ['Marble Block' => 3, 'Gold Leaf' => 1],
                            'xp' => 700,
                            'effect' => ['max_hp_bonus' => 8],
                        ],
                    ],
                ],
                'armchair' => [
                    'name' => 'Armchair',
                    'options' => [
                        'oak_armchair' => [
                            'name' => 'Oak Armchair',
                            'level' => 30,
                            'materials' => ['Oak Plank' => 3, 'Cloth' => 2],
                            'xp' => 240,
                        ],
                        'cushioned_armchair' => [
                            'name' => 'Cushioned Armchair',
                            'level' => 45,
                            'materials' => ['Willow Plank' => 3, 'Cloth' => 3],
                            'xp' => 450,
                        ],
                    ],
                ],
                'rug' => [
                    'name' => 'Rug',
                    'options' => [
                        'woven_rug' => [
                            'name' => 'Woven Rug',
                            'level' => 30,
                            'materials' => ['Cloth' => 3],
                            'xp' => 200,
                        ],
                        'ornate_rug' => [
                            'name' => 'Ornate Rug',
                            'level' => 50,
                            'materials' => ['Cloth' => 5, 'Gold Bar' => 1],
                            'xp' => 500,
                        ],
                    ],
                ],
            ],
        ],
        'forge' => [
            'name' => 'Forge',
            'description' => 'A blazing hot forge for working metal and crafting weapons.',
            'level' => 35,
            'cost' => 300000,
            'hotspots' => [
                'anvil' => [
                    'name' => 'Anvil',
                    'options' => [
                        'iron_anvil' => [
                            'name' => 'Iron Anvil',
                            'level' => 35,
                            'materials' => ['Steel Bar' => 3, 'Oak Plank' => 3],
                            'xp' => 300,
                            'effect' => ['smithing_xp_bonus' => 3],
                        ],
                        'steel_anvil' => [
                            'name' => 'Steel Anvil',
                            'level' => 55,
                            'materials' => ['Mithril Bar' => 3, 'Willow Plank' => 3],
                            'xp' => 650,
                            'effect' => ['smithing_xp_bonus' => 6],
                        ],
                    ],
                ],
                'furnace' => [
                    'name' => 'Furnace',
                    'options' => [
                        'basic_furnace' => [
                            'name' => 'Basic Furnace',
                            'level' => 36,
                            'materials' => ['Limestone Brick' => 4, 'Steel Bar' => 3],
                            'xp' => 320,
                        ],
                        'blast_furnace' => [
                            'name' => 'Blast Furnace',
                            'level' => 60,
                            'materials' => ['Marble Block' => 3, 'Mithril Bar' => 3],
                            'xp' => 800,
                        ],
                    ],
                ],
                'quench_trough' => [
                    'name' => 'Quench Trough',
                    'options' => [
                        'wooden_trough' => [
                            'name' => 'Wooden Trough',
                            'level' => 35,
                            'materials' => ['Oak Plank' => 4, 'Steel Bar' => 1],
                            'xp' => 280,
                        ],
                    ],
                ],
                'bellows' => [
                    'name' => 'Bellows',
                    'options' => [
                        'leather_bellows' => [
                            'name' => 'Leather Bellows',
                            'level' => 37,
                            'materials' => ['Oak Plank' => 2, 'Cloth' => 3],
                            'xp' => 300,
                            'effect' => ['smithing_speed_bonus' => 5],
                        ],
                        'iron_bellows' => [
                            'name' => 'Iron Bellows',
                            'level' => 50,
                            'materials' => ['Willow Plank' => 2, 'Steel Bar' => 2],
                            'xp' => 500,
                            'effect' => ['smithing_speed_bonus' => 10],
                        ],
                    ],
                ],
                'tool_storage' => [
                    'name' => 'Tool Storage',
                    'options' => [
                        'tool_chest' => [
                            'name' => 'Tool Chest',
                            'level' => 38,
                            'materials' => ['Oak Plank' => 4, 'Nails' => 3],
                            'xp' => 310,
                        ],
                        'reinforced_chest' => [
                            'name' => 'Reinforced Chest',
                            'level' => 52,
                            'materials' => ['Willow Plank' => 4, 'Steel Bar' => 2],
                            'xp' => 550,
                        ],
                    ],
                ],
            ],
        ],
        'dining_room' => [
            'name' => 'Dining Room',
            'description' => 'A spacious room for meals and entertaining guests.',
            'level' => 15,
            'cost' => 75000,
            'hotspots' => [
                'table' => [
                    'name' => 'Table',
                    'options' => [
                        'wooden_table' => [
                            'name' => 'Wooden Table',
                            'level' => 15,
                            'materials' => ['Plank' => 4, 'Nails' => 2],
                            'xp' => 100,
                        ],
                        'oak_table' => [
                            'name' => 'Oak Table',
                            'level' => 30,
                            'materials' => ['Oak Plank' => 4],
                            'xp' => 280,
                        ],
                        'willow_table' => [
                            'name' => 'Willow Table',
                            'level' => 50,
                            'materials' => ['Willow Plank' => 5, 'Gold Bar' => 1],
                            'xp' => 550,
                        ],
                    ],
                ],
                'bench' => [
                    'name' => 'Bench',
                    'options' => [
                        'wooden_bench' => [
                            'name' => 'Wooden Bench',
                            'level' => 15,
                            'materials' => ['Plank' => 3, 'Nails' => 2],
                            'xp' => 90,
                        ],
                        'oak_bench' => [
                            'name' => 'Oak Bench',
                            'level' => 30,
                            'materials' => ['Oak Plank' => 3],
                            'xp' => 240,
                        ],
                    ],
                ],
                'bell_pull' => [
                    'name' => 'Bell Pull',
                    'options' => [
                        'rope_bell' => [
                            'name' => 'Rope Bell',
                            'level' => 20,
                            'materials' => ['Plank' => 2, 'Cloth' => 2],
                            'xp' => 130,
                        ],
                        'brass_bell' => [
                            'name' => 'Brass Bell',
                            'level' => 40,
                            'materials' => ['Willow Plank' => 2, 'Steel Bar' => 2],
                            'xp' => 380,
                            'effect' => ['servant_speed_bonus' => 10],
                        ],
                    ],
                ],
                'decoration' => [
                    'name' => 'Decoration',
                    'options' => [
                        'wall_tapestry' => [
                            'name' => 'Wall Tapestry',
                            'level' => 16,
                            'materials' => ['Cloth' => 3],
                            'xp' => 80,
                        ],
                        'fine_tapestry' => [
                            'name' => 'Fine Tapestry',
                            'level' => 35,
                            'materials' => ['Cloth' => 4, 'Gold Bar' => 1],
                            'xp' => 300,
                        ],
                    ],
                ],
            ],
        ],
        'chapel' => [
            'name' => 'Chapel',
            'description' => 'A sacred space for prayer and spiritual reflection.',
            'level' => 40,
            'cost' => 400000,
            'hotspots' => [
                'altar' => [
                    'name' => 'Altar',
                    'options' => [
                        'wooden_altar' => [
                            'name' => 'Wooden Altar',
                            'level' => 40,
                            'materials' => ['Oak Plank' => 6, 'Cloth' => 2],
                            'xp' => 400,
                            'effect' => ['prayer_xp_bonus' => 50],
                        ],
                        'stone_altar' => [
                            'name' => 'Stone Altar',
                            'level' => 55,
                            'materials' => ['Limestone Brick' => 4, 'Marble Block' => 3],
                            'xp' => 700,
                            'effect' => ['prayer_xp_bonus' => 100],
                        ],
                    ],
                ],
                'incense_burner' => [
                    'name' => 'Incense Burner',
                    'options' => [
                        'wooden_burner' => [
                            'name' => 'Wooden Burner',
                            'level' => 41,
                            'materials' => ['Oak Plank' => 3],
                            'xp' => 350,
                            'effect' => ['prayer_xp_bonus' => 25],
                        ],
                        'steel_burner' => [
                            'name' => 'Steel Burner',
                            'level' => 55,
                            'materials' => ['Steel Bar' => 2, 'Willow Plank' => 2],
                            'xp' => 600,
                            'effect' => ['prayer_xp_bonus' => 50],
                        ],
                    ],
                ],
                'icon' => [
                    'name' => 'Icon',
                    'options' => [
                        'holy_symbol' => [
                            'name' => 'Holy Symbol',
                            'level' => 42,
                            'materials' => ['Oak Plank' => 2, 'Silver Bar' => 1],
                            'xp' => 360,
                            'effect' => ['prayer_bonus' => 1],
                        ],
                        'icon_of_faith' => [
                            'name' => 'Icon of Faith',
                            'level' => 60,
                            'materials' => ['Maple Plank' => 2, 'Gold Bar' => 1],
                            'xp' => 650,
                            'effect' => ['prayer_bonus' => 2],
                        ],
                    ],
                ],
                'rug' => [
                    'name' => 'Rug',
                    'options' => [
                        'prayer_rug' => [
                            'name' => 'Prayer Rug',
                            'level' => 40,
                            'materials' => ['Cloth' => 3],
                            'xp' => 300,
                        ],
                        'blessed_rug' => [
                            'name' => 'Blessed Rug',
                            'level' => 55,
                            'materials' => ['Cloth' => 4, 'Gold Bar' => 1],
                            'xp' => 550,
                        ],
                    ],
                ],
            ],
        ],
        'servant_quarters' => [
            'name' => 'Servant Quarters',
            'description' => 'Living quarters for household servants.',
            'level' => 40,
            'cost' => 250000,
            'hotspots' => [
                'bed' => [
                    'name' => 'Bed',
                    'options' => [
                        'servant_cot' => [
                            'name' => 'Servant Cot',
                            'level' => 40,
                            'materials' => ['Oak Plank' => 4, 'Cloth' => 2],
                            'xp' => 380,
                        ],
                        'servant_bed' => [
                            'name' => 'Servant Bed',
                            'level' => 55,
                            'materials' => ['Willow Plank' => 4, 'Cloth' => 3],
                            'xp' => 600,
                        ],
                    ],
                ],
                'wardrobe' => [
                    'name' => 'Wardrobe',
                    'options' => [
                        'simple_wardrobe' => [
                            'name' => 'Simple Wardrobe',
                            'level' => 40,
                            'materials' => ['Oak Plank' => 3],
                            'xp' => 350,
                        ],
                        'oak_wardrobe' => [
                            'name' => 'Oak Wardrobe',
                            'level' => 55,
                            'materials' => ['Willow Plank' => 4],
                            'xp' => 580,
                        ],
                    ],
                ],
                'bell' => [
                    'name' => 'Bell',
                    'options' => [
                        'rope_bell' => [
                            'name' => 'Rope Bell',
                            'level' => 42,
                            'materials' => ['Oak Plank' => 2, 'Cloth' => 2],
                            'xp' => 370,
                        ],
                        'brass_bell' => [
                            'name' => 'Brass Bell',
                            'level' => 55,
                            'materials' => ['Willow Plank' => 2, 'Steel Bar' => 2],
                            'xp' => 600,
                            'effect' => ['servant_speed_bonus' => 15],
                        ],
                    ],
                ],
            ],
        ],
        'trophy_hall' => [
            'name' => 'Trophy Hall',
            'description' => 'Display monster trophies for permanent combat bonuses.',
            'level' => 55,
            'cost' => 500000,
            'hotspots' => [
                'display_1' => [
                    'name' => 'Display Case 1',
                    'options' => [
                        'wooden_display' => [
                            'name' => 'Wooden Display',
                            'level' => 55,
                            'materials' => ['Willow Plank' => 4],
                            'xp' => 500,
                        ],
                        'oak_display' => [
                            'name' => 'Oak Display',
                            'level' => 65,
                            'materials' => ['Yew Plank' => 4, 'Gold Bar' => 1],
                            'xp' => 800,
                        ],
                        'ornate_display' => [
                            'name' => 'Ornate Display',
                            'level' => 80,
                            'materials' => ['Mahogany Plank' => 4, 'Gold Leaf' => 2],
                            'xp' => 1400,
                        ],
                    ],
                ],
                'display_2' => [
                    'name' => 'Display Case 2',
                    'options' => [
                        'wooden_display' => [
                            'name' => 'Wooden Display',
                            'level' => 55,
                            'materials' => ['Willow Plank' => 4],
                            'xp' => 500,
                        ],
                        'oak_display' => [
                            'name' => 'Oak Display',
                            'level' => 65,
                            'materials' => ['Yew Plank' => 4, 'Gold Bar' => 1],
                            'xp' => 800,
                        ],
                        'ornate_display' => [
                            'name' => 'Ornate Display',
                            'level' => 80,
                            'materials' => ['Mahogany Plank' => 4, 'Gold Leaf' => 2],
                            'xp' => 1400,
                        ],
                    ],
                ],
                'display_3' => [
                    'name' => 'Display Case 3',
                    'options' => [
                        'wooden_display' => [
                            'name' => 'Wooden Display',
                            'level' => 55,
                            'materials' => ['Willow Plank' => 4],
                            'xp' => 500,
                        ],
                        'oak_display' => [
                            'name' => 'Oak Display',
                            'level' => 65,
                            'materials' => ['Yew Plank' => 4, 'Gold Bar' => 1],
                            'xp' => 800,
                        ],
                        'ornate_display' => [
                            'name' => 'Ornate Display',
                            'level' => 80,
                            'materials' => ['Mahogany Plank' => 4, 'Gold Leaf' => 2],
                            'xp' => 1400,
                        ],
                    ],
                ],
                'pedestal' => [
                    'name' => 'Boss Pedestal',
                    'options' => [
                        'stone_pedestal' => [
                            'name' => 'Stone Pedestal',
                            'level' => 55,
                            'materials' => ['Willow Plank' => 3, 'Limestone Brick' => 2],
                            'xp' => 550,
                        ],
                        'marble_pedestal' => [
                            'name' => 'Marble Pedestal',
                            'level' => 70,
                            'materials' => ['Marble Block' => 3],
                            'xp' => 1000,
                        ],
                        'gilded_pedestal' => [
                            'name' => 'Gilded Pedestal',
                            'level' => 85,
                            'materials' => ['Marble Block' => 3, 'Gold Leaf' => 2],
                            'xp' => 1600,
                        ],
                    ],
                ],
                'lighting' => [
                    'name' => 'Lighting',
                    'options' => [
                        'torch_sconce' => [
                            'name' => 'Torch Sconce',
                            'level' => 55,
                            'materials' => ['Willow Plank' => 2, 'Steel Bar' => 1],
                            'xp' => 480,
                            'effect' => ['combat_xp_bonus' => 2],
                        ],
                        'lantern' => [
                            'name' => 'Lantern',
                            'level' => 65,
                            'materials' => ['Yew Plank' => 2, 'Steel Bar' => 2],
                            'xp' => 780,
                            'effect' => ['combat_xp_bonus' => 3],
                        ],
                        'chandelier' => [
                            'name' => 'Chandelier',
                            'level' => 80,
                            'materials' => ['Mahogany Plank' => 4, 'Gold Bar' => 3],
                            'xp' => 1300,
                            'effect' => ['combat_xp_bonus' => 5],
                        ],
                    ],
                ],
            ],
        ],
        'garden' => [
            'name' => 'Garden',
            'description' => 'An indoor garden for growing herbs year-round.',
            'level' => 25,
            'cost' => 125000,
            'hotspots' => [
                'planter_1' => [
                    'name' => 'Planter Bed 1',
                    'options' => [
                        'wooden_planter' => ['name' => 'Wooden Planter', 'level' => 25, 'materials' => ['Plank' => 4, 'Steel Nails' => 5], 'xp' => 300],
                        'stone_planter' => ['name' => 'Stone Planter', 'level' => 40, 'materials' => ['Limestone Brick' => 3, 'Willow Plank' => 2], 'xp' => 600],
                        'marble_planter' => ['name' => 'Marble Planter', 'level' => 60, 'materials' => ['Marble Block' => 2, 'Yew Plank' => 2], 'xp' => 1000],
                    ],
                ],
                'planter_2' => [
                    'name' => 'Planter Bed 2',
                    'options' => [
                        'wooden_planter' => ['name' => 'Wooden Planter', 'level' => 25, 'materials' => ['Plank' => 4, 'Steel Nails' => 5], 'xp' => 300],
                        'stone_planter' => ['name' => 'Stone Planter', 'level' => 40, 'materials' => ['Limestone Brick' => 3, 'Willow Plank' => 2], 'xp' => 600],
                        'marble_planter' => ['name' => 'Marble Planter', 'level' => 60, 'materials' => ['Marble Block' => 2, 'Yew Plank' => 2], 'xp' => 1000],
                    ],
                ],
                'planter_3' => [
                    'name' => 'Planter Bed 3',
                    'options' => [
                        'wooden_planter' => ['name' => 'Wooden Planter', 'level' => 25, 'materials' => ['Plank' => 4, 'Steel Nails' => 5], 'xp' => 300],
                        'stone_planter' => ['name' => 'Stone Planter', 'level' => 40, 'materials' => ['Limestone Brick' => 3, 'Willow Plank' => 2], 'xp' => 600],
                        'marble_planter' => ['name' => 'Marble Planter', 'level' => 60, 'materials' => ['Marble Block' => 2, 'Yew Plank' => 2], 'xp' => 1000],
                    ],
                ],
                'planter_4' => [
                    'name' => 'Planter Bed 4',
                    'options' => [
                        'wooden_planter' => ['name' => 'Wooden Planter', 'level' => 25, 'materials' => ['Plank' => 4, 'Steel Nails' => 5], 'xp' => 300],
                        'stone_planter' => ['name' => 'Stone Planter', 'level' => 40, 'materials' => ['Limestone Brick' => 3, 'Willow Plank' => 2], 'xp' => 600],
                        'marble_planter' => ['name' => 'Marble Planter', 'level' => 60, 'materials' => ['Marble Block' => 2, 'Yew Plank' => 2], 'xp' => 1000],
                    ],
                ],
                'compost_bin' => [
                    'name' => 'Compost Bin',
                    'options' => [
                        'basic_compost' => ['name' => 'Basic Compost Bin', 'level' => 25, 'materials' => ['Plank' => 3, 'Steel Nails' => 3], 'xp' => 250],
                        'advanced_compost' => ['name' => 'Advanced Compost Bin', 'level' => 45, 'materials' => ['Willow Plank' => 4, 'Steel Bar' => 2], 'xp' => 700],
                    ],
                ],
                'irrigation' => [
                    'name' => 'Irrigation',
                    'options' => [
                        'basic_watering' => ['name' => 'Watering Can Stand', 'level' => 25, 'materials' => ['Plank' => 2, 'Bronze Bar' => 2], 'xp' => 200, 'effect' => ['herblore_xp_bonus' => 2]],
                        'drip_system' => ['name' => 'Drip System', 'level' => 45, 'materials' => ['Willow Plank' => 3, 'Steel Bar' => 3], 'xp' => 650, 'effect' => ['herblore_xp_bonus' => 3, 'auto_water' => 1]],
                        'sprinkler' => ['name' => 'Sprinkler System', 'level' => 65, 'materials' => ['Yew Plank' => 3, 'Gold Bar' => 2], 'xp' => 1100, 'effect' => ['herblore_xp_bonus' => 5, 'auto_water' => 1]],
                    ],
                ],
                'lighting' => [
                    'name' => 'Grow Lights',
                    'options' => [
                        'candle_rack' => ['name' => 'Candle Rack', 'level' => 25, 'materials' => ['Plank' => 2, 'Bronze Bar' => 1], 'xp' => 200, 'effect' => ['farming_xp_bonus' => 2]],
                        'lantern_array' => ['name' => 'Lantern Array', 'level' => 40, 'materials' => ['Willow Plank' => 3, 'Steel Bar' => 2], 'xp' => 550, 'effect' => ['farming_xp_bonus' => 3]],
                        'crystal_lights' => ['name' => 'Crystal Grow Lights', 'level' => 60, 'materials' => ['Yew Plank' => 3, 'Gold Bar' => 2], 'xp' => 1000, 'effect' => ['farming_xp_bonus' => 5]],
                    ],
                ],
            ],
        ],
        'portal_chamber' => [
            'name' => 'Portal Chamber',
            'description' => 'A mystical room housing teleportation portals to distant lands.',
            'level' => 45,
            'cost' => 750000,
            'hotspots' => [
                'portal_1' => [
                    'name' => 'Portal 1',
                    'options' => [
                        'basic_portal' => [
                            'name' => 'Basic Portal',
                            'level' => 45,
                            'materials' => ['Willow Plank' => 5, 'Marble Block' => 2],
                            'xp' => 450,
                        ],
                        'enhanced_portal' => [
                            'name' => 'Enhanced Portal',
                            'level' => 60,
                            'materials' => ['Yew Plank' => 4, 'Gold Leaf' => 2],
                            'xp' => 750,
                        ],
                    ],
                ],
                'portal_2' => [
                    'name' => 'Portal 2',
                    'options' => [
                        'basic_portal' => [
                            'name' => 'Basic Portal',
                            'level' => 45,
                            'materials' => ['Willow Plank' => 5, 'Marble Block' => 2],
                            'xp' => 450,
                        ],
                        'enhanced_portal' => [
                            'name' => 'Enhanced Portal',
                            'level' => 60,
                            'materials' => ['Yew Plank' => 4, 'Gold Leaf' => 2],
                            'xp' => 750,
                        ],
                    ],
                ],
                'portal_3' => [
                    'name' => 'Portal 3',
                    'options' => [
                        'basic_portal' => [
                            'name' => 'Basic Portal',
                            'level' => 45,
                            'materials' => ['Willow Plank' => 5, 'Marble Block' => 2],
                            'xp' => 450,
                        ],
                        'enhanced_portal' => [
                            'name' => 'Enhanced Portal',
                            'level' => 60,
                            'materials' => ['Yew Plank' => 4, 'Gold Leaf' => 2],
                            'xp' => 750,
                        ],
                    ],
                ],
            ],
        ],
    ];

    /**
     * Adjacency bonus definitions.
     * Each entry: [roomA, roomB, effect_key, value, description]
     *
     * @var array<int, array{0: string, 1: string, 2: string, 3: int, 4: string}>
     */
    public const ADJACENCY_BONUSES = [
        ['kitchen', 'dining_room', 'cooking_xp_bonus', 3, 'Kitchen + Dining Room: +3% Cooking XP'],
        ['forge', 'workshop', 'smithing_xp_bonus', 3, 'Forge + Workshop: +3% Smithing XP'],
        ['chapel', 'study', 'prayer_xp_bonus', 3, 'Chapel + Study: +3% Prayer XP'],
        ['bedroom', 'hearth_room', 'energy_regen_bonus', 5, 'Bedroom + Hearth Room: +5% Energy Regen'],
        ['garden', 'kitchen', 'herblore_xp_bonus', 3, 'Garden + Kitchen: +3% Herblore XP'],
    ];

    /**
     * Portal configuration costs.
     *
     * @var array<string, array{set_cost: int}>
     */
    public const PORTAL_CONFIG = [
        'basic_portal' => ['set_cost' => 5000],
        'enhanced_portal' => ['set_cost' => 2500],
    ];

    /**
     * Construction contract tiers.
     */
    public const CONTRACT_TIERS = [
        'beginner' => [
            'name' => 'Beginner',
            'level' => 1,
            'planks' => [3, 5],
            'plank_type' => 'Plank',
            'xp' => [50, 80],
            'gold' => [10, 20],
            'energy' => 3,
        ],
        'apprentice' => [
            'name' => 'Apprentice',
            'level' => 20,
            'planks' => [4, 6],
            'plank_type' => 'Oak Plank',
            'xp' => [120, 180],
            'gold' => [25, 50],
            'energy' => 4,
        ],
        'journeyman' => [
            'name' => 'Journeyman',
            'level' => 40,
            'planks' => [5, 8],
            'plank_type' => 'Willow Plank',
            'xp' => [250, 400],
            'gold' => [50, 100],
            'energy' => 5,
        ],
        'expert' => [
            'name' => 'Expert',
            'level' => 60,
            'planks' => [6, 10],
            'plank_type' => 'Yew Plank',
            'xp' => [500, 750],
            'gold' => [100, 200],
            'energy' => 6,
        ],
        'master' => [
            'name' => 'Master',
            'level' => 80,
            'planks' => [8, 12],
            'plank_type' => 'Mahogany Plank',
            'xp' => [900, 1200],
            'gold' => [200, 400],
            'energy' => 7,
        ],
    ];

    /**
     * Sawmill plank conversion recipes.
     */
    /**
     * Servant tier configuration.
     *
     * @var array<string, array{name: string, level: int, hire_cost: int, weekly_wage: int, carry_capacity: int, base_speed: int}>
     */
    public const SERVANT_TIERS = [
        'handyman' => ['name' => 'Handyman', 'level' => 20, 'hire_cost' => 5000, 'weekly_wage' => 100, 'carry_capacity' => 6, 'base_speed' => 60],
        'maid' => ['name' => 'Maid', 'level' => 30, 'hire_cost' => 15000, 'weekly_wage' => 250, 'carry_capacity' => 10, 'base_speed' => 30],
        'butler' => ['name' => 'Butler', 'level' => 45, 'hire_cost' => 50000, 'weekly_wage' => 500, 'carry_capacity' => 16, 'base_speed' => 15],
        'head_butler' => ['name' => 'Head Butler', 'level' => 60, 'hire_cost' => 150000, 'weekly_wage' => 1000, 'carry_capacity' => 24, 'base_speed' => 8],
    ];

    /**
     * Sawmill plank conversion recipes.
     */
    public const PLANK_RECIPES = [
        'Plank' => ['log' => 'Wood', 'fee' => 10],
        'Oak Plank' => ['log' => 'Oak Wood', 'fee' => 40],
        'Willow Plank' => ['log' => 'Willow Wood', 'fee' => 100],
        'Maple Plank' => ['log' => 'Maple Wood', 'fee' => 250],
        'Yew Plank' => ['log' => 'Yew Wood', 'fee' => 600],
        'Mahogany Plank' => ['log' => 'Mahogany Wood', 'fee' => 1500],
    ];
}

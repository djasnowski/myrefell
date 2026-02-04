<?php

namespace Database\Seeders;

use App\Models\HqFeatureType;
use Illuminate\Database\Seeder;

class HqFeatureTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            // ==========================================
            // TIER 1 - Chapel (2 features)
            // ==========================================
            [
                'slug' => 'sacred-altar',
                'name' => 'Sacred Altar',
                'description' => 'A consecrated altar that amplifies the devotion gained from all religious activities.',
                'icon' => 'flame',
                'category' => 'altar',
                'min_hq_tier' => 1,
                'max_level' => 5,
                'effects' => [
                    1 => ['devotion_bonus' => 5],
                    2 => ['devotion_bonus' => 10],
                    3 => ['devotion_bonus' => 15],
                    4 => ['devotion_bonus' => 22],
                    5 => ['devotion_bonus' => 30],
                ],
                'level_costs' => [
                    1 => ['gold' => 10_000, 'devotion' => 1_000, 'items' => []],
                    2 => ['gold' => 50_000, 'devotion' => 5_000, 'items' => []],
                    3 => ['gold' => 150_000, 'devotion' => 15_000, 'items' => []],
                    4 => ['gold' => 400_000, 'devotion' => 40_000, 'items' => []],
                    5 => ['gold' => 1_000_000, 'devotion' => 100_000, 'items' => []],
                ],
            ],
            [
                'slug' => 'offering-box',
                'name' => 'Offering Box',
                'description' => 'A blessed collection box that increases the gold deposited into the treasury from donations.',
                'icon' => 'inbox',
                'category' => 'vault',
                'min_hq_tier' => 1,
                'max_level' => 5,
                'effects' => [
                    1 => ['treasury_bonus' => 5],
                    2 => ['treasury_bonus' => 10],
                    3 => ['treasury_bonus' => 15],
                    4 => ['treasury_bonus' => 22],
                    5 => ['treasury_bonus' => 30],
                ],
                'level_costs' => [
                    1 => ['gold' => 10_000, 'devotion' => 1_000, 'items' => []],
                    2 => ['gold' => 50_000, 'devotion' => 5_000, 'items' => []],
                    3 => ['gold' => 150_000, 'devotion' => 15_000, 'items' => []],
                    4 => ['gold' => 400_000, 'devotion' => 40_000, 'items' => []],
                    5 => ['gold' => 1_000_000, 'devotion' => 100_000, 'items' => []],
                ],
            ],

            // ==========================================
            // TIER 2 - Church (3 features)
            // ==========================================
            [
                'slug' => 'prayer-candles',
                'name' => 'Prayer Candles',
                'description' => 'Blessed candles infused with sacred herbs that enhance your herblore knowledge.',
                'icon' => 'candle',
                'category' => 'altar',
                'min_hq_tier' => 2,
                'max_level' => 5,
                'effects' => [
                    1 => ['herblore_xp_bonus' => 5],
                    2 => ['herblore_xp_bonus' => 10],
                    3 => ['herblore_xp_bonus' => 15],
                    4 => ['herblore_xp_bonus' => 22],
                    5 => ['herblore_xp_bonus' => 30],
                ],
                'level_costs' => [
                    1 => ['gold' => 25_000, 'devotion' => 2_500, 'items' => []],
                    2 => ['gold' => 100_000, 'devotion' => 10_000, 'items' => []],
                    3 => ['gold' => 300_000, 'devotion' => 30_000, 'items' => []],
                    4 => ['gold' => 750_000, 'devotion' => 75_000, 'items' => []],
                    5 => ['gold' => 2_000_000, 'devotion' => 200_000, 'items' => []],
                ],
            ],
            [
                'slug' => 'scripture-hall',
                'name' => 'Scripture Hall',
                'description' => 'A hall of sacred texts that increases Prayer XP gained from all activities.',
                'icon' => 'book-open',
                'category' => 'library',
                'min_hq_tier' => 2,
                'max_level' => 5,
                'effects' => [
                    1 => ['prayer_xp_bonus' => 5],
                    2 => ['prayer_xp_bonus' => 10],
                    3 => ['prayer_xp_bonus' => 15],
                    4 => ['prayer_xp_bonus' => 22],
                    5 => ['prayer_xp_bonus' => 30],
                ],
                'level_costs' => [
                    1 => ['gold' => 30_000, 'devotion' => 3_000, 'items' => []],
                    2 => ['gold' => 120_000, 'devotion' => 12_000, 'items' => []],
                    3 => ['gold' => 350_000, 'devotion' => 35_000, 'items' => []],
                    4 => ['gold' => 900_000, 'devotion' => 90_000, 'items' => []],
                    5 => ['gold' => 2_500_000, 'devotion' => 250_000, 'items' => []],
                ],
            ],
            [
                'slug' => 'meditation-garden',
                'name' => 'Meditation Garden',
                'description' => 'A peaceful garden that increases energy recovery rate for all members.',
                'icon' => 'flower-2',
                'category' => 'garden',
                'min_hq_tier' => 2,
                'max_level' => 5,
                'effects' => [
                    1 => ['energy_recovery_bonus' => 2],
                    2 => ['energy_recovery_bonus' => 4],
                    3 => ['energy_recovery_bonus' => 6],
                    4 => ['energy_recovery_bonus' => 9],
                    5 => ['energy_recovery_bonus' => 12],
                ],
                'level_costs' => [
                    1 => ['gold' => 35_000, 'devotion' => 3_500, 'items' => []],
                    2 => ['gold' => 140_000, 'devotion' => 14_000, 'items' => []],
                    3 => ['gold' => 400_000, 'devotion' => 40_000, 'items' => []],
                    4 => ['gold' => 1_000_000, 'devotion' => 100_000, 'items' => []],
                    5 => ['gold' => 2_800_000, 'devotion' => 280_000, 'items' => []],
                ],
            ],

            // ==========================================
            // TIER 3 - Temple (4 features)
            // ==========================================
            [
                'slug' => 'tome-of-blessings',
                'name' => 'Tome of Blessings',
                'description' => 'An ancient tome that allows members to maintain additional active blessings.',
                'icon' => 'book-marked',
                'category' => 'library',
                'min_hq_tier' => 3,
                'max_level' => 5,
                'effects' => [
                    1 => ['blessing_slots' => 1],
                    2 => ['blessing_slots' => 1],
                    3 => ['blessing_slots' => 2],
                    4 => ['blessing_slots' => 2],
                    5 => ['blessing_slots' => 3],
                ],
                'level_costs' => [
                    1 => ['gold' => 100_000, 'devotion' => 10_000, 'items' => []],
                    2 => ['gold' => 400_000, 'devotion' => 40_000, 'items' => []],
                    3 => ['gold' => 1_200_000, 'devotion' => 120_000, 'items' => []],
                    4 => ['gold' => 3_000_000, 'devotion' => 300_000, 'items' => []],
                    5 => ['gold' => 8_000_000, 'devotion' => 800_000, 'items' => []],
                ],
            ],
            [
                'slug' => 'prophets-sanctum',
                'name' => "Prophet's Sanctum",
                'description' => 'A private sanctum that increases the duration of blessings you grant to others.',
                'icon' => 'crown',
                'category' => 'sanctum',
                'min_hq_tier' => 3,
                'max_level' => 5,
                'effects' => [
                    1 => ['prophet_blessing_duration' => 5],
                    2 => ['prophet_blessing_duration' => 8],
                    3 => ['prophet_blessing_duration' => 12],
                    4 => ['prophet_blessing_duration' => 16],
                    5 => ['prophet_blessing_duration' => 20],
                ],
                'level_costs' => [
                    1 => ['gold' => 80_000, 'devotion' => 8_000, 'items' => []],
                    2 => ['gold' => 300_000, 'devotion' => 30_000, 'items' => []],
                    3 => ['gold' => 900_000, 'devotion' => 90_000, 'items' => []],
                    4 => ['gold' => 2_500_000, 'devotion' => 250_000, 'items' => []],
                    5 => ['gold' => 6_000_000, 'devotion' => 600_000, 'items' => []],
                ],
            ],
            [
                'slug' => 'blessed-vault',
                'name' => 'Blessed Vault',
                'description' => 'A sacred vault blessed by the divine that increases ore yield when mining.',
                'icon' => 'pickaxe',
                'category' => 'vault',
                'min_hq_tier' => 3,
                'max_level' => 5,
                'effects' => [
                    1 => ['mining_yield_bonus' => 5],
                    2 => ['mining_yield_bonus' => 10],
                    3 => ['mining_yield_bonus' => 15],
                    4 => ['mining_yield_bonus' => 22],
                    5 => ['mining_yield_bonus' => 30],
                ],
                'level_costs' => [
                    1 => ['gold' => 150_000, 'devotion' => 15_000, 'items' => []],
                    2 => ['gold' => 500_000, 'devotion' => 50_000, 'items' => []],
                    3 => ['gold' => 1_500_000, 'devotion' => 150_000, 'items' => []],
                    4 => ['gold' => 4_000_000, 'devotion' => 400_000, 'items' => []],
                    5 => ['gold' => 10_000_000, 'devotion' => 1_000_000, 'items' => []],
                ],
            ],
            [
                'slug' => 'relic-chamber',
                'name' => 'Relic Chamber',
                'description' => 'A secure chamber for holy relics that increases your chance to land critical hits on monsters.',
                'icon' => 'gem',
                'category' => 'reliquary',
                'min_hq_tier' => 3,
                'max_level' => 5,
                'effects' => [
                    1 => ['monster_crit_chance' => 3],
                    2 => ['monster_crit_chance' => 6],
                    3 => ['monster_crit_chance' => 10],
                    4 => ['monster_crit_chance' => 15],
                    5 => ['monster_crit_chance' => 20],
                ],
                'level_costs' => [
                    1 => ['gold' => 120_000, 'devotion' => 12_000, 'items' => []],
                    2 => ['gold' => 450_000, 'devotion' => 45_000, 'items' => []],
                    3 => ['gold' => 1_300_000, 'devotion' => 130_000, 'items' => []],
                    4 => ['gold' => 3_500_000, 'devotion' => 350_000, 'items' => []],
                    5 => ['gold' => 9_000_000, 'devotion' => 900_000, 'items' => []],
                ],
            ],

            // ==========================================
            // TIER 4 - Cathedral (4 features)
            // ==========================================
            [
                'slug' => 'divine-font',
                'name' => 'Divine Font',
                'description' => 'A mystical font that reduces the gold cost of all blessings for members.',
                'icon' => 'sparkles',
                'category' => 'sanctum',
                'min_hq_tier' => 4,
                'max_level' => 5,
                'effects' => [
                    1 => ['blessing_cost_reduction' => 10],
                    2 => ['blessing_cost_reduction' => 18],
                    3 => ['blessing_cost_reduction' => 25],
                    4 => ['blessing_cost_reduction' => 32],
                    5 => ['blessing_cost_reduction' => 40],
                ],
                'level_costs' => [
                    1 => ['gold' => 250_000, 'devotion' => 25_000, 'items' => []],
                    2 => ['gold' => 900_000, 'devotion' => 90_000, 'items' => []],
                    3 => ['gold' => 2_500_000, 'devotion' => 250_000, 'items' => []],
                    4 => ['gold' => 6_000_000, 'devotion' => 600_000, 'items' => []],
                    5 => ['gold' => 15_000_000, 'devotion' => 1_500_000, 'items' => []],
                ],
            ],
            [
                'slug' => 'healing-springs',
                'name' => 'Healing Springs',
                'description' => 'Sacred springs that provide passive HP regeneration to members while they are online.',
                'icon' => 'droplets',
                'category' => 'garden',
                'min_hq_tier' => 4,
                'max_level' => 5,
                'effects' => [
                    1 => ['hp_regen_per_minute' => 1],
                    2 => ['hp_regen_per_minute' => 2],
                    3 => ['hp_regen_per_minute' => 3],
                    4 => ['hp_regen_per_minute' => 5],
                    5 => ['hp_regen_per_minute' => 8],
                ],
                'level_costs' => [
                    1 => ['gold' => 300_000, 'devotion' => 30_000, 'items' => []],
                    2 => ['gold' => 1_000_000, 'devotion' => 100_000, 'items' => []],
                    3 => ['gold' => 3_000_000, 'devotion' => 300_000, 'items' => []],
                    4 => ['gold' => 7_500_000, 'devotion' => 750_000, 'items' => []],
                    5 => ['gold' => 18_000_000, 'devotion' => 1_800_000, 'items' => []],
                ],
            ],
            [
                'slug' => 'training-grounds',
                'name' => 'Training Grounds',
                'description' => 'Consecrated grounds where members gain increased XP from all combat activities.',
                'icon' => 'swords',
                'category' => 'training',
                'min_hq_tier' => 4,
                'max_level' => 5,
                'effects' => [
                    1 => ['combat_xp_bonus' => 3],
                    2 => ['combat_xp_bonus' => 6],
                    3 => ['combat_xp_bonus' => 10],
                    4 => ['combat_xp_bonus' => 15],
                    5 => ['combat_xp_bonus' => 20],
                ],
                'level_costs' => [
                    1 => ['gold' => 350_000, 'devotion' => 35_000, 'items' => []],
                    2 => ['gold' => 1_200_000, 'devotion' => 120_000, 'items' => []],
                    3 => ['gold' => 3_500_000, 'devotion' => 350_000, 'items' => []],
                    4 => ['gold' => 8_500_000, 'devotion' => 850_000, 'items' => []],
                    5 => ['gold' => 20_000_000, 'devotion' => 2_000_000, 'items' => []],
                ],
            ],
            [
                'slug' => 'reliquary-of-saints',
                'name' => 'Reliquary of Saints',
                'description' => 'Houses sacred remains that provide defense bonuses to all members.',
                'icon' => 'shield',
                'category' => 'reliquary',
                'min_hq_tier' => 4,
                'max_level' => 5,
                'effects' => [
                    1 => ['defense_bonus' => 2],
                    2 => ['defense_bonus' => 4],
                    3 => ['defense_bonus' => 7],
                    4 => ['defense_bonus' => 10],
                    5 => ['defense_bonus' => 15],
                ],
                'level_costs' => [
                    1 => ['gold' => 280_000, 'devotion' => 28_000, 'items' => []],
                    2 => ['gold' => 950_000, 'devotion' => 95_000, 'items' => []],
                    3 => ['gold' => 2_800_000, 'devotion' => 280_000, 'items' => []],
                    4 => ['gold' => 7_000_000, 'devotion' => 700_000, 'items' => []],
                    5 => ['gold' => 17_000_000, 'devotion' => 1_700_000, 'items' => []],
                ],
            ],

            // ==========================================
            // TIER 5 - Grand Cathedral (4 features)
            // ==========================================
            [
                'slug' => 'eternal-flame',
                'name' => 'Eternal Flame',
                'description' => 'A never-dying flame that empowers your attacks with divine fury.',
                'icon' => 'flame-kindling',
                'category' => 'altar',
                'min_hq_tier' => 5,
                'max_level' => 5,
                'effects' => [
                    1 => ['attack_bonus' => 3],
                    2 => ['attack_bonus' => 6],
                    3 => ['attack_bonus' => 10],
                    4 => ['attack_bonus' => 15],
                    5 => ['attack_bonus' => 20],
                ],
                'level_costs' => [
                    1 => ['gold' => 1_000_000, 'devotion' => 100_000, 'items' => []],
                    2 => ['gold' => 4_000_000, 'devotion' => 400_000, 'items' => []],
                    3 => ['gold' => 12_000_000, 'devotion' => 1_200_000, 'items' => []],
                    4 => ['gold' => 30_000_000, 'devotion' => 3_000_000, 'items' => []],
                    5 => ['gold' => 75_000_000, 'devotion' => 7_500_000, 'items' => []],
                ],
            ],
            [
                'slug' => 'grand-library',
                'name' => 'Grand Library',
                'description' => 'Ancient texts reveal secrets of raw physical power, increasing your strength.',
                'icon' => 'library',
                'category' => 'library',
                'min_hq_tier' => 5,
                'max_level' => 5,
                'effects' => [
                    1 => ['strength_bonus' => 3],
                    2 => ['strength_bonus' => 6],
                    3 => ['strength_bonus' => 10],
                    4 => ['strength_bonus' => 15],
                    5 => ['strength_bonus' => 20],
                ],
                'level_costs' => [
                    1 => ['gold' => 1_500_000, 'devotion' => 150_000, 'items' => []],
                    2 => ['gold' => 5_500_000, 'devotion' => 550_000, 'items' => []],
                    3 => ['gold' => 15_000_000, 'devotion' => 1_500_000, 'items' => []],
                    4 => ['gold' => 40_000_000, 'devotion' => 4_000_000, 'items' => []],
                    5 => ['gold' => 100_000_000, 'devotion' => 10_000_000, 'items' => []],
                ],
            ],
            [
                'slug' => 'divine-treasury',
                'name' => 'Divine Treasury',
                'description' => 'A blessed treasury that increases gold drops from all monster kills for members.',
                'icon' => 'coins',
                'category' => 'vault',
                'min_hq_tier' => 5,
                'max_level' => 5,
                'effects' => [
                    1 => ['gold_drop_bonus' => 5],
                    2 => ['gold_drop_bonus' => 10],
                    3 => ['gold_drop_bonus' => 15],
                    4 => ['gold_drop_bonus' => 22],
                    5 => ['gold_drop_bonus' => 30],
                ],
                'level_costs' => [
                    1 => ['gold' => 2_000_000, 'devotion' => 200_000, 'items' => []],
                    2 => ['gold' => 7_000_000, 'devotion' => 700_000, 'items' => []],
                    3 => ['gold' => 20_000_000, 'devotion' => 2_000_000, 'items' => []],
                    4 => ['gold' => 50_000_000, 'devotion' => 5_000_000, 'items' => []],
                    5 => ['gold' => 120_000_000, 'devotion' => 12_000_000, 'items' => []],
                ],
            ],
            [
                'slug' => 'sanctuary-of-peace',
                'name' => 'Sanctuary of Peace',
                'description' => 'A sacred space that increases your maximum HP through divine protection.',
                'icon' => 'shield-check',
                'category' => 'sanctum',
                'min_hq_tier' => 5,
                'max_level' => 5,
                'effects' => [
                    1 => ['max_hp_bonus' => 5],
                    2 => ['max_hp_bonus' => 10],
                    3 => ['max_hp_bonus' => 15],
                    4 => ['max_hp_bonus' => 22],
                    5 => ['max_hp_bonus' => 30],
                ],
                'level_costs' => [
                    1 => ['gold' => 1_800_000, 'devotion' => 180_000, 'items' => []],
                    2 => ['gold' => 6_000_000, 'devotion' => 600_000, 'items' => []],
                    3 => ['gold' => 18_000_000, 'devotion' => 1_800_000, 'items' => []],
                    4 => ['gold' => 45_000_000, 'devotion' => 4_500_000, 'items' => []],
                    5 => ['gold' => 110_000_000, 'devotion' => 11_000_000, 'items' => []],
                ],
            ],

            // ==========================================
            // TIER 6 - Holy Sanctum (5 features)
            // ==========================================
            [
                'slug' => 'celestial-altar',
                'name' => 'Celestial Altar',
                'description' => 'An altar touched by divine power that grants a chance for double devotion gains.',
                'icon' => 'sun',
                'category' => 'altar',
                'min_hq_tier' => 6,
                'max_level' => 5,
                'effects' => [
                    1 => ['double_devotion_chance' => 5],
                    2 => ['double_devotion_chance' => 10],
                    3 => ['double_devotion_chance' => 15],
                    4 => ['double_devotion_chance' => 22],
                    5 => ['double_devotion_chance' => 30],
                ],
                'level_costs' => [
                    1 => ['gold' => 5_000_000, 'devotion' => 500_000, 'items' => []],
                    2 => ['gold' => 20_000_000, 'devotion' => 2_000_000, 'items' => []],
                    3 => ['gold' => 60_000_000, 'devotion' => 6_000_000, 'items' => []],
                    4 => ['gold' => 150_000_000, 'devotion' => 15_000_000, 'items' => []],
                    5 => ['gold' => 400_000_000, 'devotion' => 40_000_000, 'items' => []],
                ],
            ],
            [
                'slug' => 'hall-of-legends',
                'name' => 'Hall of Legends',
                'description' => 'A hall commemorating legendary members, increasing your chance to find rare loot from monsters.',
                'icon' => 'trophy',
                'category' => 'library',
                'min_hq_tier' => 6,
                'max_level' => 5,
                'effects' => [
                    1 => ['rare_loot_drop_bonus' => 5],
                    2 => ['rare_loot_drop_bonus' => 10],
                    3 => ['rare_loot_drop_bonus' => 15],
                    4 => ['rare_loot_drop_bonus' => 22],
                    5 => ['rare_loot_drop_bonus' => 30],
                ],
                'level_costs' => [
                    1 => ['gold' => 8_000_000, 'devotion' => 800_000, 'items' => []],
                    2 => ['gold' => 30_000_000, 'devotion' => 3_000_000, 'items' => []],
                    3 => ['gold' => 90_000_000, 'devotion' => 9_000_000, 'items' => []],
                    4 => ['gold' => 220_000_000, 'devotion' => 22_000_000, 'items' => []],
                    5 => ['gold' => 550_000_000, 'devotion' => 55_000_000, 'items' => []],
                ],
            ],
            [
                'slug' => 'paradise-gardens',
                'name' => 'Paradise Gardens',
                'description' => 'Heavenly gardens that restore your energy when you pray here.',
                'icon' => 'trees',
                'category' => 'garden',
                'min_hq_tier' => 6,
                'max_level' => 5,
                'effects' => [
                    1 => ['energy_restore' => 25],
                    2 => ['energy_restore' => 50],
                    3 => ['energy_restore' => 100],
                    4 => ['energy_restore' => 150],
                    5 => ['energy_restore' => 200],
                ],
                'level_costs' => [
                    1 => ['gold' => 6_000_000, 'devotion' => 600_000, 'items' => []],
                    2 => ['gold' => 25_000_000, 'devotion' => 2_500_000, 'items' => []],
                    3 => ['gold' => 75_000_000, 'devotion' => 7_500_000, 'items' => []],
                    4 => ['gold' => 180_000_000, 'devotion' => 18_000_000, 'items' => []],
                    5 => ['gold' => 450_000_000, 'devotion' => 45_000_000, 'items' => []],
                ],
            ],
            [
                'slug' => 'vault-of-ages',
                'name' => 'Vault of Ages',
                'description' => 'An ancient vault containing relics of legendary warriors, boosting all combat XP gains.',
                'icon' => 'landmark',
                'category' => 'vault',
                'min_hq_tier' => 6,
                'max_level' => 5,
                'effects' => [
                    1 => ['all_combat_xp_bonus' => 5],
                    2 => ['all_combat_xp_bonus' => 10],
                    3 => ['all_combat_xp_bonus' => 15],
                    4 => ['all_combat_xp_bonus' => 22],
                    5 => ['all_combat_xp_bonus' => 30],
                ],
                'level_costs' => [
                    1 => ['gold' => 10_000_000, 'devotion' => 1_000_000, 'items' => []],
                    2 => ['gold' => 40_000_000, 'devotion' => 4_000_000, 'items' => []],
                    3 => ['gold' => 120_000_000, 'devotion' => 12_000_000, 'items' => []],
                    4 => ['gold' => 300_000_000, 'devotion' => 30_000_000, 'items' => []],
                    5 => ['gold' => 750_000_000, 'devotion' => 75_000_000, 'items' => []],
                ],
            ],
            [
                'slug' => 'divine-armory',
                'name' => 'Divine Armory',
                'description' => 'A blessed armory that grants all combat stat bonuses to members.',
                'icon' => 'axe',
                'category' => 'training',
                'min_hq_tier' => 6,
                'max_level' => 5,
                'effects' => [
                    1 => ['all_combat_stats_bonus' => 2],
                    2 => ['all_combat_stats_bonus' => 4],
                    3 => ['all_combat_stats_bonus' => 6],
                    4 => ['all_combat_stats_bonus' => 9],
                    5 => ['all_combat_stats_bonus' => 12],
                ],
                'level_costs' => [
                    1 => ['gold' => 12_000_000, 'devotion' => 1_200_000, 'items' => []],
                    2 => ['gold' => 45_000_000, 'devotion' => 4_500_000, 'items' => []],
                    3 => ['gold' => 130_000_000, 'devotion' => 13_000_000, 'items' => []],
                    4 => ['gold' => 320_000_000, 'devotion' => 32_000_000, 'items' => []],
                    5 => ['gold' => 800_000_000, 'devotion' => 80_000_000, 'items' => []],
                ],
            ],
        ];

        foreach ($features as $feature) {
            HqFeatureType::updateOrCreate(
                ['slug' => $feature['slug']],
                [
                    'name' => $feature['name'],
                    'description' => $feature['description'],
                    'icon' => $feature['icon'],
                    'category' => $feature['category'],
                    'min_hq_tier' => $feature['min_hq_tier'],
                    'max_level' => $feature['max_level'],
                    'effects' => $feature['effects'],
                    'level_costs' => $feature['level_costs'],
                ]
            );
        }
    }
}

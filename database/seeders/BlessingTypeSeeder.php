<?php

namespace Database\Seeders;

use App\Models\BlessingType;
use Illuminate\Database\Seeder;

class BlessingTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $blessings = [
            // Level 1 - Basic blessings
            [
                'name' => 'Blessing of Strength',
                'slug' => 'strength',
                'icon' => 'swords',
                'description' => 'Increases your attack power in combat.',
                'category' => 'combat',
                'effects' => ['attack_bonus' => 5],
                'duration_minutes' => 60,
                'cooldown_minutes' => 0,
                'prayer_level_required' => 1,
                'gold_cost' => 25,
                'energy_cost' => 5,
            ],
            [
                'name' => 'Blessing of Protection',
                'slug' => 'protection',
                'icon' => 'shield',
                'description' => 'Increases your defense against attacks.',
                'category' => 'combat',
                'effects' => ['defense_bonus' => 5],
                'duration_minutes' => 60,
                'cooldown_minutes' => 0,
                'prayer_level_required' => 1,
                'gold_cost' => 25,
                'energy_cost' => 5,
            ],
            [
                'name' => 'Blessing of the Harvest',
                'slug' => 'harvest',
                'icon' => 'wheat',
                'description' => 'Increases experience gained from farming.',
                'category' => 'skill',
                'effects' => ['farming_xp_bonus' => 10],
                'duration_minutes' => 120,
                'cooldown_minutes' => 0,
                'prayer_level_required' => 1,
                'gold_cost' => 30,
                'energy_cost' => 5,
            ],
            [
                'name' => 'Blessing of Fortune',
                'slug' => 'fortune',
                'icon' => 'coins',
                'description' => 'Increases gold found from all sources.',
                'category' => 'general',
                'effects' => ['gold_find_bonus' => 5],
                'duration_minutes' => 60,
                'cooldown_minutes' => 0,
                'prayer_level_required' => 1,
                'gold_cost' => 50,
                'energy_cost' => 5,
            ],

            // Level 10 - Gathering blessings
            [
                'name' => 'Blessing of the Sea',
                'slug' => 'sea',
                'icon' => 'fish',
                'description' => 'Improves your fishing yields.',
                'category' => 'skill',
                'effects' => ['fishing_yield_bonus' => 10, 'fishing_xp_bonus' => 10],
                'duration_minutes' => 120,
                'cooldown_minutes' => 0,
                'prayer_level_required' => 10,
                'gold_cost' => 50,
                'energy_cost' => 8,
            ],
            [
                'name' => 'Blessing of the Forest',
                'slug' => 'forest',
                'icon' => 'tree-deciduous',
                'description' => 'Improves your woodcutting abilities.',
                'category' => 'skill',
                'effects' => ['woodcutting_yield_bonus' => 10, 'woodcutting_xp_bonus' => 10],
                'duration_minutes' => 120,
                'cooldown_minutes' => 0,
                'prayer_level_required' => 10,
                'gold_cost' => 50,
                'energy_cost' => 8,
            ],
            [
                'name' => 'Blessing of the Earth',
                'slug' => 'earth',
                'icon' => 'pickaxe',
                'description' => 'Improves your mining abilities.',
                'category' => 'skill',
                'effects' => ['mining_yield_bonus' => 10, 'mining_xp_bonus' => 10],
                'duration_minutes' => 120,
                'cooldown_minutes' => 0,
                'prayer_level_required' => 10,
                'gold_cost' => 50,
                'energy_cost' => 8,
            ],

            // Level 20 - Utility blessings
            [
                'name' => 'Blessing of Endurance',
                'slug' => 'endurance',
                'icon' => 'zap',
                'description' => 'Improves your energy regeneration rate.',
                'category' => 'general',
                'effects' => ['energy_regen_bonus' => 20],
                'duration_minutes' => 240,
                'cooldown_minutes' => 0,
                'prayer_level_required' => 20,
                'gold_cost' => 100,
                'energy_cost' => 10,
            ],
            [
                'name' => 'Blessing of Restoration',
                'slug' => 'restoration',
                'icon' => 'heart-pulse',
                'description' => 'Improves your hitpoint regeneration.',
                'category' => 'general',
                'effects' => ['hp_regen_bonus' => 25],
                'duration_minutes' => 180,
                'cooldown_minutes' => 0,
                'prayer_level_required' => 20,
                'gold_cost' => 75,
                'energy_cost' => 10,
            ],

            // Level 30 - Combat upgrades
            [
                'name' => 'Blessing of Vitality',
                'slug' => 'vitality',
                'icon' => 'heart',
                'description' => 'Temporarily increases your maximum hitpoints.',
                'category' => 'combat',
                'effects' => ['max_hp_bonus' => 20],
                'duration_minutes' => 120,
                'cooldown_minutes' => 0,
                'prayer_level_required' => 30,
                'gold_cost' => 150,
                'energy_cost' => 12,
            ],
            [
                'name' => 'Blessing of Swiftness',
                'slug' => 'swiftness',
                'icon' => 'wind',
                'description' => 'Reduces travel time between locations.',
                'category' => 'general',
                'effects' => ['travel_speed_bonus' => 25],
                'duration_minutes' => 120,
                'cooldown_minutes' => 0,
                'prayer_level_required' => 30,
                'gold_cost' => 150,
                'energy_cost' => 12,
            ],

            // Level 40 - Crafting mastery
            [
                'name' => 'Blessing of the Craftsman',
                'slug' => 'craftsman',
                'icon' => 'wrench',
                'description' => 'Boosts experience gained from smithing and crafting.',
                'category' => 'skill',
                'effects' => ['smithing_xp_bonus' => 20, 'crafting_xp_bonus' => 20],
                'duration_minutes' => 120,
                'cooldown_minutes' => 0,
                'prayer_level_required' => 40,
                'gold_cost' => 200,
                'energy_cost' => 15,
            ],

            // Level 50 - Luck and rare drops
            [
                'name' => 'Blessing of Luck',
                'slug' => 'luck',
                'icon' => 'sparkles',
                'description' => 'Increases your chance of rare drops.',
                'category' => 'general',
                'effects' => ['rare_drop_bonus' => 20],
                'duration_minutes' => 60,
                'cooldown_minutes' => 30,
                'prayer_level_required' => 50,
                'gold_cost' => 300,
                'energy_cost' => 15,
            ],

            // Level 60 - Combat mastery
            [
                'name' => 'Blessing of the Warrior',
                'slug' => 'warrior',
                'icon' => 'sword',
                'description' => 'A powerful blessing that boosts all combat abilities.',
                'category' => 'combat',
                'effects' => ['attack_bonus' => 10, 'defense_bonus' => 10, 'strength_bonus' => 10],
                'duration_minutes' => 60,
                'cooldown_minutes' => 60,
                'prayer_level_required' => 60,
                'gold_cost' => 500,
                'energy_cost' => 20,
            ],

            // Level 70 - XP mastery
            [
                'name' => 'Blessing of Wisdom',
                'slug' => 'wisdom',
                'icon' => 'book-open',
                'description' => 'Increases all experience gained.',
                'category' => 'general',
                'effects' => ['all_xp_bonus' => 15],
                'duration_minutes' => 60,
                'cooldown_minutes' => 60,
                'prayer_level_required' => 70,
                'gold_cost' => 750,
                'energy_cost' => 20,
            ],

            // Level 15 - Haste blessing
            [
                'name' => 'Blessing of Haste',
                'slug' => 'haste',
                'icon' => 'zap',
                'description' => 'Drastically reduces action cooldowns to 1.5 seconds.',
                'category' => 'general',
                'effects' => ['action_cooldown_seconds' => 1.5],
                'duration_minutes' => 30,
                'cooldown_minutes' => 60,
                'prayer_level_required' => 15,
                'gold_cost' => 1000,
                'energy_cost' => 0,
            ],
        ];

        foreach ($blessings as $blessing) {
            BlessingType::updateOrCreate(
                ['slug' => $blessing['slug']],
                $blessing
            );
        }
    }
}

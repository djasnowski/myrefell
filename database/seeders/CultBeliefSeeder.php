<?php

namespace Database\Seeders;

use App\Models\Belief;
use Illuminate\Database\Seeder;

class CultBeliefSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $beliefs = [
            // Tier 1 - Hidden Cellar (unlocked immediately when hideout is built)
            [
                'name' => "Shadow's Embrace",
                'description' => 'The shadows welcome you as kin. +15% thieving success rate, +10% thieving XP.',
                'icon' => 'moon',
                'type' => 'vice',
                'cult_only' => true,
                'required_hideout_tier' => 1,
                'hp_cost' => 5,
                'energy_cost' => 10,
                'effects' => [
                    'thieving_success_bonus' => 15,
                    'thieving_xp_bonus' => 10,
                ],
            ],
            [
                'name' => 'Shadow Step',
                'description' => 'Move through darkness with supernatural grace. -15% energy cost for thieving.',
                'icon' => 'footprints',
                'type' => 'vice',
                'cult_only' => true,
                'required_hideout_tier' => 1,
                'hp_cost' => 5,
                'energy_cost' => 10,
                'effects' => [
                    'thieving_energy_reduction' => 15,
                ],
            ],

            // Tier 2 - Underground Den
            [
                'name' => 'Dark Whispers',
                'description' => 'Secrets flow to you from the shadows. +20% thieving XP, -20% catch penalties.',
                'icon' => 'ear',
                'type' => 'vice',
                'cult_only' => true,
                'required_hideout_tier' => 2,
                'hp_cost' => 5,
                'energy_cost' => 15,
                'effects' => [
                    'thieving_xp_bonus' => 20,
                    'catch_penalty_reduction' => 20,
                ],
            ],
            [
                'name' => 'Blood Tithe',
                'description' => 'Spill blood for coin. +25% gold from monster kills, but -10% devotion gains.',
                'icon' => 'droplet',
                'type' => 'vice',
                'cult_only' => true,
                'required_hideout_tier' => 2,
                'hp_cost' => 5,
                'energy_cost' => 15,
                'effects' => [
                    'monster_gold_bonus' => 25,
                    'devotion_gain_penalty' => -10,
                ],
            ],

            // Tier 3 - Secret Sanctum
            [
                'name' => "Assassin's Creed",
                'description' => 'Strike first, strike true. +25% first-strike damage in combat, but -10% defense.',
                'icon' => 'crosshair',
                'type' => 'vice',
                'cult_only' => true,
                'required_hideout_tier' => 3,
                'hp_cost' => 10,
                'energy_cost' => 15,
                'effects' => [
                    'first_strike_damage_bonus' => 25,
                    'defense_penalty' => -10,
                ],
            ],
            [
                'name' => 'Forbidden Wealth',
                'description' => 'Gold flows to those who abandon morality. +15% ALL gold gains, but -5% all XP.',
                'icon' => 'gem',
                'type' => 'vice',
                'cult_only' => true,
                'required_hideout_tier' => 3,
                'hp_cost' => 10,
                'energy_cost' => 15,
                'effects' => [
                    'all_gold_bonus' => 15,
                    'all_xp_penalty' => -5,
                ],
            ],

            // Tier 4 - Shadow Temple
            [
                'name' => 'Night Stalker',
                'description' => 'Hunt the wealthy and well-guarded. +20% success on high-level targets, +15% bonus loot chance.',
                'icon' => 'eye',
                'type' => 'vice',
                'cult_only' => true,
                'required_hideout_tier' => 4,
                'hp_cost' => 10,
                'energy_cost' => 20,
                'effects' => [
                    'high_level_thieving_bonus' => 20,
                    'thieving_loot_bonus' => 15,
                ],
            ],
            [
                'name' => 'Soul Siphon',
                'description' => 'Drain life from your enemies. Heal 10% of damage dealt as HP.',
                'icon' => 'heart-pulse',
                'type' => 'vice',
                'cult_only' => true,
                'required_hideout_tier' => 4,
                'hp_cost' => 10,
                'energy_cost' => 20,
                'effects' => [
                    'combat_hp_leech' => 10,
                ],
            ],

            // Tier 5 - Dark Citadel
            [
                'name' => 'Sacrificial Rites',
                'description' => 'Master the forbidden arts of sacrifice. +50% sacrifice devotion/XP, but -25% regular prayer rewards.',
                'icon' => 'flame',
                'type' => 'vice',
                'cult_only' => true,
                'required_hideout_tier' => 5,
                'hp_cost' => 15,
                'energy_cost' => 30,
                'effects' => [
                    'sacrifice_devotion_bonus' => 50,
                    'sacrifice_xp_bonus' => 50,
                    'prayer_devotion_penalty' => -25,
                ],
            ],
        ];

        foreach ($beliefs as $beliefData) {
            Belief::updateOrCreate(
                ['name' => $beliefData['name']],
                $beliefData
            );
        }
    }
}

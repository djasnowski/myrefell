<?php

namespace Database\Seeders;

use App\Models\Belief;
use App\Models\Religion;
use Illuminate\Database\Seeder;

class ReligionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedBeliefs();
        $this->seedDefaultReligions();
    }

    /**
     * Seed the beliefs.
     */
    protected function seedBeliefs(): void
    {
        $beliefs = [
            // Virtues (positive effects)
            [
                'name' => 'Industriousness',
                'description' => 'Hard work is a form of worship. Gain bonus XP from gathering activities.',
                'icon' => 'hammer',
                'type' => 'virtue',
                'effects' => [
                    'gathering_xp_bonus' => 10,
                ],
            ],
            [
                'name' => 'Martial Prowess',
                'description' => 'Combat honors the divine. Gain bonus XP from combat.',
                'icon' => 'sword',
                'type' => 'virtue',
                'effects' => [
                    'combat_xp_bonus' => 10,
                ],
            ],
            [
                'name' => 'Craftsmanship',
                'description' => 'Creation is sacred. Gain bonus XP from crafting.',
                'icon' => 'anvil',
                'type' => 'virtue',
                'effects' => [
                    'crafting_xp_bonus' => 10,
                ],
            ],
            [
                'name' => 'Charity',
                'description' => 'Giving enriches the soul. Gain extra devotion from donations.',
                'icon' => 'heart',
                'type' => 'virtue',
                'effects' => [
                    'donation_devotion_bonus' => 25,
                ],
            ],
            [
                'name' => 'Vigilance',
                'description' => 'The watchful are blessed. Gain bonus daily task rewards.',
                'icon' => 'eye',
                'type' => 'virtue',
                'effects' => [
                    'daily_task_bonus' => 15,
                ],
            ],
            [
                'name' => 'Temperance',
                'description' => 'Self-control is strength. Energy regenerates faster.',
                'icon' => 'clock',
                'type' => 'virtue',
                'effects' => [
                    'energy_regen_bonus' => 5,
                ],
            ],
            [
                'name' => 'Wisdom',
                'description' => 'Knowledge is divine. Quest XP rewards increased.',
                'icon' => 'book',
                'type' => 'virtue',
                'effects' => [
                    'quest_xp_bonus' => 10,
                ],
            ],
            [
                'name' => 'Fortitude',
                'description' => 'Endurance is blessed. Max HP increased.',
                'icon' => 'shield',
                'type' => 'virtue',
                'effects' => [
                    'max_hp_bonus' => 5,
                ],
            ],

            // Vices (trade-offs, not purely negative)
            [
                'name' => 'Bloodlust',
                'description' => 'Violence is virtue. Combat bonuses at the cost of peaceful skills.',
                'icon' => 'skull',
                'type' => 'vice',
                'effects' => [
                    'combat_xp_bonus' => 20,
                    'crafting_xp_penalty' => -10,
                ],
            ],
            [
                'name' => 'Greed',
                'description' => 'Wealth is worship. Gold gains increased but donation costs more.',
                'icon' => 'coins',
                'type' => 'vice',
                'effects' => [
                    'gold_bonus' => 10,
                    'donation_cost_penalty' => 25,
                ],
            ],
            [
                'name' => 'Sloth',
                'description' => 'Rest is sacred. Energy costs reduced but XP gains lowered.',
                'icon' => 'moon',
                'type' => 'vice',
                'effects' => [
                    'energy_cost_reduction' => 10,
                    'xp_penalty' => -5,
                ],
            ],
            [
                'name' => 'Pride',
                'description' => 'Glory above all. Combat and crafting bonuses but reduced devotion gains.',
                'icon' => 'crown',
                'type' => 'vice',
                'effects' => [
                    'combat_xp_bonus' => 10,
                    'crafting_xp_bonus' => 10,
                    'devotion_penalty' => -15,
                ],
            ],

            // Neutral (balanced trade-offs)
            [
                'name' => 'Asceticism',
                'description' => 'Simplicity is purity. Devotion gains increased but gold gains reduced.',
                'icon' => 'leaf',
                'type' => 'neutral',
                'effects' => [
                    'devotion_bonus' => 20,
                    'gold_penalty' => -10,
                ],
            ],
            [
                'name' => 'Mysticism',
                'description' => 'The arcane calls. Ritual and sacrifice effects increased.',
                'icon' => 'sparkles',
                'type' => 'neutral',
                'effects' => [
                    'ritual_devotion_bonus' => 25,
                    'sacrifice_devotion_bonus' => 25,
                ],
            ],
            [
                'name' => 'Communion',
                'description' => 'Unity is strength. Group bonuses at structures.',
                'icon' => 'users',
                'type' => 'neutral',
                'effects' => [
                    'structure_bonus' => 15,
                ],
            ],
            [
                'name' => 'Pilgrimage',
                'description' => 'Travel enlightens. Pilgrimage rewards doubled but travel costs more energy.',
                'icon' => 'footprints',
                'type' => 'neutral',
                'effects' => [
                    'pilgrimage_bonus' => 100,
                    'travel_energy_penalty' => 10,
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

    /**
     * Seed some default religions (optional, for testing/initial content).
     */
    protected function seedDefaultReligions(): void
    {
        // Create a few default public religions for players to join
        $religions = [
            [
                'name' => 'The Order of Light',
                'description' => 'An ancient religion devoted to righteousness, hard work, and the protection of the realm.',
                'icon' => 'sun',
                'color' => '#fbbf24', // amber
                'type' => 'religion',
                'is_public' => true,
                'member_limit' => 0,
                'beliefs' => ['Industriousness', 'Fortitude'],
            ],
            [
                'name' => 'The Shadow Covenant',
                'description' => 'A mysterious faith that embraces the darker aspects of mortal existence.',
                'icon' => 'moon',
                'color' => '#6366f1', // indigo
                'type' => 'religion',
                'is_public' => true,
                'member_limit' => 0,
                'beliefs' => ['Bloodlust', 'Mysticism'],
            ],
            [
                'name' => 'The Merchant\'s Guild Chapel',
                'description' => 'Where commerce and faith intertwine. Gold is the ultimate offering.',
                'icon' => 'coins',
                'color' => '#22c55e', // green
                'type' => 'religion',
                'is_public' => true,
                'member_limit' => 0,
                'beliefs' => ['Greed', 'Craftsmanship'],
            ],
        ];

        foreach ($religions as $religionData) {
            $beliefNames = $religionData['beliefs'];
            unset($religionData['beliefs']);

            $religion = Religion::updateOrCreate(
                ['name' => $religionData['name']],
                $religionData
            );

            // Attach beliefs
            $beliefIds = Belief::whereIn('name', $beliefNames)->pluck('id');
            $religion->beliefs()->sync($beliefIds);
        }
    }
}

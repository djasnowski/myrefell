<?php

namespace Database\Seeders;

use App\Models\FestivalType;
use App\Models\TournamentType;
use Illuminate\Database\Seeder;

class FestivalAndTournamentSeeder extends Seeder
{
    public function run(): void
    {
        // Seasonal Festivals
        $seasonalFestivals = [
            [
                'name' => 'Planting Festival',
                'slug' => 'planting-festival',
                'description' => 'Celebrate the beginning of the planting season with fertility rites and new beginnings.',
                'category' => 'seasonal',
                'season' => 'spring',
                'duration_days' => 3,
                'bonuses' => ['farming_bonus' => 10, 'happiness' => 5],
                'activities' => ['dancing', 'seed_blessing', 'fertility_rites'],
            ],
            [
                'name' => 'Midsummer Fair',
                'slug' => 'midsummer-fair',
                'description' => 'The grandest celebration of the year with trade, tournaments, and revelry.',
                'category' => 'seasonal',
                'season' => 'summer',
                'duration_days' => 7,
                'bonuses' => ['trade_bonus' => 15, 'happiness' => 10],
                'activities' => ['tournaments', 'trade_fair', 'feasting', 'music'],
            ],
            [
                'name' => 'Harvest Festival',
                'slug' => 'harvest-festival',
                'description' => 'Give thanks for the bounty of the harvest with feasting and celebration.',
                'category' => 'seasonal',
                'season' => 'autumn',
                'duration_days' => 5,
                'bonuses' => ['food_bonus' => 20, 'happiness' => 8],
                'activities' => ['feasting', 'thanksgiving', 'competitions'],
            ],
            [
                'name' => 'Midwinter Feast',
                'slug' => 'midwinter-feast',
                'description' => 'Gather together during the coldest months for warmth and community.',
                'category' => 'seasonal',
                'season' => 'winter',
                'duration_days' => 3,
                'bonuses' => ['morale' => 10, 'happiness' => 5],
                'activities' => ['feasting', 'storytelling', 'gift_giving'],
            ],
        ];

        foreach ($seasonalFestivals as $festival) {
            FestivalType::updateOrCreate(
                ['slug' => $festival['slug']],
                $festival
            );
        }

        // Tournament Types
        $tournamentTypes = [
            [
                'name' => 'Grand Melee',
                'slug' => 'grand-melee',
                'description' => 'A chaotic free-for-all battle where the last fighter standing wins.',
                'combat_type' => 'melee',
                'primary_stat' => 'attack',
                'secondary_stat' => 'defense',
                'entry_fee' => 100,
                'min_level' => 5,
                'max_participants' => 16,
                'prize_distribution' => ['1st' => 50, '2nd' => 30, '3rd' => 20],
                'is_lethal' => false,
            ],
            [
                'name' => 'Joust',
                'slug' => 'joust',
                'description' => 'Noble knights clash on horseback in tests of skill and valor.',
                'combat_type' => 'joust',
                'primary_stat' => 'strength',
                'secondary_stat' => 'defense',
                'entry_fee' => 250,
                'min_level' => 10,
                'max_participants' => 8,
                'prize_distribution' => ['1st' => 60, '2nd' => 30, '3rd' => 10],
                'is_lethal' => false,
            ],
            [
                'name' => 'Archery Contest',
                'slug' => 'archery-contest',
                'description' => 'Test your aim and precision against the finest archers in the realm.',
                'combat_type' => 'archery',
                'primary_stat' => 'attack',
                'secondary_stat' => null,
                'entry_fee' => 50,
                'min_level' => 1,
                'max_participants' => 32,
                'prize_distribution' => ['1st' => 50, '2nd' => 30, '3rd' => 20],
                'is_lethal' => false,
            ],
            [
                'name' => 'Wrestling Match',
                'slug' => 'wrestling',
                'description' => 'A test of raw strength and grappling skill.',
                'combat_type' => 'wrestling',
                'primary_stat' => 'strength',
                'secondary_stat' => null,
                'entry_fee' => 25,
                'min_level' => 1,
                'max_participants' => 16,
                'prize_distribution' => ['1st' => 60, '2nd' => 40],
                'is_lethal' => false,
            ],
            [
                'name' => 'Trial by Combat',
                'slug' => 'trial-by-combat',
                'description' => 'A deadly duel to resolve disputes through combat.',
                'combat_type' => 'mixed',
                'primary_stat' => 'combat_level',
                'secondary_stat' => null,
                'entry_fee' => 0,
                'min_level' => 1,
                'max_participants' => 2,
                'prize_distribution' => ['1st' => 100],
                'is_lethal' => true,
            ],
        ];

        foreach ($tournamentTypes as $type) {
            TournamentType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}

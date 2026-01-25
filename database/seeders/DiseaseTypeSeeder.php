<?php

namespace Database\Seeders;

use App\Models\DiseaseType;
use Illuminate\Database\Seeder;

class DiseaseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $diseases = [
            [
                'name' => 'Common Cold',
                'slug' => 'common-cold',
                'description' => 'A mild respiratory illness that causes sneezing and fatigue.',
                'severity' => 'minor',
                'base_spread_rate' => 15,
                'mortality_rate' => 0,
                'base_duration_days' => 5,
                'incubation_days' => 1,
                'symptoms' => ['sneezing', 'fatigue', 'runny_nose'],
                'stat_penalties' => ['energy_regen' => -25],
                'is_contagious' => true,
                'grants_immunity' => false,
            ],
            [
                'name' => 'Fever',
                'slug' => 'fever',
                'description' => 'A moderate illness causing high temperature and weakness.',
                'severity' => 'moderate',
                'base_spread_rate' => 10,
                'mortality_rate' => 2,
                'base_duration_days' => 7,
                'incubation_days' => 2,
                'symptoms' => ['high_temperature', 'weakness', 'headache'],
                'stat_penalties' => ['energy_regen' => -50, 'max_hp' => -10],
                'is_contagious' => true,
                'grants_immunity' => true,
            ],
            [
                'name' => 'Pox',
                'slug' => 'pox',
                'description' => 'A severe disease causing skin lesions and high fever.',
                'severity' => 'severe',
                'base_spread_rate' => 20,
                'mortality_rate' => 10,
                'base_duration_days' => 14,
                'incubation_days' => 3,
                'symptoms' => ['skin_lesions', 'high_fever', 'severe_weakness'],
                'stat_penalties' => ['energy_regen' => -75, 'max_hp' => -25],
                'is_contagious' => true,
                'grants_immunity' => true,
            ],
            [
                'name' => 'The Plague',
                'slug' => 'plague',
                'description' => 'A deadly epidemic disease that spreads rapidly and kills many.',
                'severity' => 'plague',
                'base_spread_rate' => 30,
                'mortality_rate' => 25,
                'base_duration_days' => 10,
                'incubation_days' => 2,
                'symptoms' => ['buboes', 'black_spots', 'extreme_fever', 'delirium'],
                'stat_penalties' => ['energy_regen' => -100, 'max_hp' => -50],
                'is_contagious' => true,
                'grants_immunity' => true,
            ],
            [
                'name' => 'Food Poisoning',
                'slug' => 'food-poisoning',
                'description' => 'Illness from consuming spoiled or contaminated food.',
                'severity' => 'minor',
                'base_spread_rate' => 0,
                'mortality_rate' => 1,
                'base_duration_days' => 3,
                'incubation_days' => 0,
                'symptoms' => ['nausea', 'vomiting', 'stomach_pain'],
                'stat_penalties' => ['energy_regen' => -50],
                'is_contagious' => false,
                'grants_immunity' => false,
            ],
        ];

        foreach ($diseases as $disease) {
            DiseaseType::updateOrCreate(
                ['slug' => $disease['slug']],
                $disease
            );
        }
    }
}

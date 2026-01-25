<?php

namespace Database\Seeders;

use App\Models\CrimeType;
use Illuminate\Database\Seeder;

class CrimeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $crimeTypes = [
            [
                'slug' => CrimeType::THEFT,
                'name' => 'Theft',
                'description' => 'Taking property that belongs to another without permission.',
                'severity' => CrimeType::SEVERITY_MINOR,
                'court_level' => CrimeType::COURT_VILLAGE,
                'base_fine' => 100,
                'base_jail_days' => 1,
                'can_be_outlawed' => false,
                'can_be_executed' => false,
                'is_religious' => false,
            ],
            [
                'slug' => CrimeType::ASSAULT,
                'name' => 'Assault',
                'description' => 'Physical attack on another person causing injury.',
                'severity' => CrimeType::SEVERITY_MODERATE,
                'court_level' => CrimeType::COURT_BARONY,
                'base_fine' => 500,
                'base_jail_days' => 7,
                'can_be_outlawed' => false,
                'can_be_executed' => false,
                'is_religious' => false,
            ],
            [
                'slug' => CrimeType::MURDER,
                'name' => 'Murder',
                'description' => 'Unlawful killing of another person.',
                'severity' => CrimeType::SEVERITY_MAJOR,
                'court_level' => CrimeType::COURT_KINGDOM,
                'base_fine' => 5000,
                'base_jail_days' => 30,
                'can_be_outlawed' => true,
                'can_be_executed' => true,
                'is_religious' => false,
            ],
            [
                'slug' => CrimeType::TREASON,
                'name' => 'Treason',
                'description' => 'Betrayal of one\'s lord or kingdom.',
                'severity' => CrimeType::SEVERITY_CAPITAL,
                'court_level' => CrimeType::COURT_KINGDOM,
                'base_fine' => 10000,
                'base_jail_days' => 0,
                'can_be_outlawed' => true,
                'can_be_executed' => true,
                'is_religious' => false,
            ],
            [
                'slug' => CrimeType::HERESY,
                'name' => 'Heresy',
                'description' => 'Holding beliefs contrary to the established faith.',
                'severity' => CrimeType::SEVERITY_MODERATE,
                'court_level' => CrimeType::COURT_CHURCH,
                'base_fine' => 200,
                'base_jail_days' => 3,
                'can_be_outlawed' => false,
                'can_be_executed' => true,
                'is_religious' => true,
            ],
            [
                'slug' => CrimeType::DESERTION,
                'name' => 'Desertion',
                'description' => 'Abandoning military duty without permission.',
                'severity' => CrimeType::SEVERITY_MAJOR,
                'court_level' => CrimeType::COURT_KINGDOM,
                'base_fine' => 2000,
                'base_jail_days' => 14,
                'can_be_outlawed' => true,
                'can_be_executed' => false,
                'is_religious' => false,
            ],
            [
                'slug' => CrimeType::FALSE_ACCUSATION,
                'name' => 'False Accusation',
                'description' => 'Making a knowingly false accusation against another.',
                'severity' => CrimeType::SEVERITY_MODERATE,
                'court_level' => CrimeType::COURT_BARONY,
                'base_fine' => 300,
                'base_jail_days' => 5,
                'can_be_outlawed' => false,
                'can_be_executed' => false,
                'is_religious' => false,
            ],
            [
                'slug' => CrimeType::TRESPASSING,
                'name' => 'Trespassing',
                'description' => 'Entering property without permission.',
                'severity' => CrimeType::SEVERITY_MINOR,
                'court_level' => CrimeType::COURT_VILLAGE,
                'base_fine' => 50,
                'base_jail_days' => 0,
                'can_be_outlawed' => false,
                'can_be_executed' => false,
                'is_religious' => false,
            ],
            [
                'slug' => CrimeType::FRAUD,
                'name' => 'Fraud',
                'description' => 'Deception for financial gain.',
                'severity' => CrimeType::SEVERITY_MODERATE,
                'court_level' => CrimeType::COURT_BARONY,
                'base_fine' => 1000,
                'base_jail_days' => 7,
                'can_be_outlawed' => false,
                'can_be_executed' => false,
                'is_religious' => false,
            ],
            [
                'slug' => CrimeType::SMUGGLING,
                'name' => 'Smuggling',
                'description' => 'Illegally transporting goods to avoid taxes or bans.',
                'severity' => CrimeType::SEVERITY_MODERATE,
                'court_level' => CrimeType::COURT_BARONY,
                'base_fine' => 500,
                'base_jail_days' => 3,
                'can_be_outlawed' => false,
                'can_be_executed' => false,
                'is_religious' => false,
            ],
        ];

        foreach ($crimeTypes as $crimeType) {
            CrimeType::updateOrCreate(
                ['slug' => $crimeType['slug']],
                $crimeType
            );
        }
    }
}

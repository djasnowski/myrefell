<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed the world in order (kingdoms -> baronies -> towns -> villages)
        $this->call([
            KingdomSeeder::class,
            BaronySeeder::class,
            DuchySeeder::class,
            TownSeeder::class,
            VillageSeeder::class,
            ItemSeeder::class,
            CropTypeSeeder::class,
            BlessingTypeSeeder::class,
            MonsterSeeder::class,
            DailyTaskSeeder::class,
            QuestSeeder::class,
            JobSeeder::class,
            RoleSeeder::class,
            ReligionSeeder::class,
            CharterSeeder::class,
            HorseSeeder::class,
            BusinessTypeSeeder::class,
            DisasterTypeSeeder::class,
            DiseaseTypeSeeder::class,
            BuildingAndDisasterSeeder::class,
            CrimeTypeSeeder::class,
            DungeonSeeder::class,
            FestivalAndTournamentSeeder::class,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\BirthService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function __construct(
        protected BirthService $birthService
    ) {}

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed the world in order (kingdoms -> towns -> castles -> villages)
        $this->call([
            KingdomSeeder::class,
            TownSeeder::class,
            CastleSeeder::class,
            VillageSeeder::class,
            ItemSeeder::class,
            MonsterSeeder::class,
            DailyTaskSeeder::class,
            QuestSeeder::class,
            JobSeeder::class,
            RoleSeeder::class,
            DanAdminSeeder::class,
        ]);

        // Create a test user and assign via BirthService
        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        // Use BirthService to assign home village and title
        $this->birthService->assignNewPlayer($user);
    }
}

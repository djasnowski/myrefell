<?php

namespace Database\Seeders;

use App\Models\PlayerSkill;
use App\Models\User;
use App\Services\BirthService;
use App\Services\DailyTaskService;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;

class DanAdminSeeder extends Seeder
{
    public function __construct(
        protected BirthService $birthService,
        protected InventoryService $inventoryService,
        protected DailyTaskService $dailyTaskService
    ) {}

    /**
     * Seed the admin user "dan".
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'username' => 'dan',
            'email' => 'dan@example.com',
            'password' => 'soccer',
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        // Create initial skills
        $this->createInitialSkills($user);

        // Give starter items
        $this->inventoryService->giveStarterKit($user);

        // Use BirthService to assign home village and title
        $this->birthService->assignNewPlayer($user);

        // Assign daily tasks (getTodaysTasks handles duplicates)
        $this->dailyTaskService->getTodaysTasks($user);

        $this->command->info('Admin user "dan" created with password "soccer"');
    }

    /**
     * Create initial skills for the player.
     */
    protected function createInitialSkills(User $user): void
    {
        foreach (PlayerSkill::SKILLS as $skill) {
            $isCombatSkill = in_array($skill, PlayerSkill::COMBAT_SKILLS);
            $startingLevel = $isCombatSkill ? 5 : 1;
            $startingXp = PlayerSkill::xpForLevel($startingLevel);

            PlayerSkill::create([
                'player_id' => $user->id,
                'skill_name' => $skill,
                'level' => $startingLevel,
                'xp' => $startingXp,
            ]);
        }
    }
}

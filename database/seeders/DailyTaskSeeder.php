<?php

namespace Database\Seeders;

use App\Services\DailyTaskService;
use Illuminate\Database\Seeder;

class DailyTaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DailyTaskService::seedDefaultTasks();

        $this->command->info('Daily tasks seeded successfully!');
    }
}

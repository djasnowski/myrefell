<?php

namespace Database\Seeders;

use App\Services\QuestService;
use Illuminate\Database\Seeder;

class QuestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        QuestService::seedDefaultQuests();
    }
}

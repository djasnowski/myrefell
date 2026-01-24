<?php

namespace Database\Seeders;

use App\Services\JobService;
use Illuminate\Database\Seeder;

class JobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        JobService::seedDefaultJobs();
    }
}

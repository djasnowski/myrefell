<?php

namespace Database\Seeders;

use App\Services\BusinessService;
use Illuminate\Database\Seeder;

class BusinessTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BusinessService::seedDefaultBusinessTypes();
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Town;
use App\Models\Village;
use Database\Seeders\SeedStockpileSeeder;
use Illuminate\Console\Command;

class RestockSeeds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'market:restock-seeds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restock seeds at village and town markets (farmer supply run)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $totalRestocked = 0;

        // Restock villages
        $villages = Village::all();
        foreach ($villages as $village) {
            $restocked = SeedStockpileSeeder::restockLocation('village', $village->id);
            $totalRestocked += $restocked;
        }

        // Restock towns
        $towns = Town::all();
        foreach ($towns as $town) {
            $restocked = SeedStockpileSeeder::restockLocation('town', $town->id);
            $totalRestocked += $restocked;
        }

        $this->info("Farmer supply run complete. Restocked {$totalRestocked} seed types across all locations.");

        return Command::SUCCESS;
    }
}

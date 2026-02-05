<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BiomeService;
use Illuminate\Console\Command;

class BackfillPlayerKingdoms extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'players:backfill-kingdoms';

    /**
     * The console command description.
     */
    protected $description = 'Backfill current_kingdom_id for all players based on their current location';

    /**
     * Execute the console command.
     */
    public function handle(BiomeService $biomeService): int
    {
        $this->info('Backfilling player kingdoms...');

        $players = User::whereNull('current_kingdom_id')
            ->whereNotNull('current_location_type')
            ->whereNotNull('current_location_id')
            ->get();

        $bar = $this->output->createProgressBar($players->count());
        $bar->start();

        $updated = 0;
        foreach ($players as $player) {
            $biomeService->updatePlayerKingdom($player);
            if ($player->current_kingdom_id) {
                $updated++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Updated {$updated} players with their current kingdom.");

        return Command::SUCCESS;
    }
}

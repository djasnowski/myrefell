<?php

namespace App\Jobs;

use App\Services\EnergyService;
use Illuminate\Support\Facades\Log;

class RegenerateEnergy
{

    /**
     * Execute the job.
     */
    public function handle(EnergyService $energyService): void
    {
        $affected = $energyService->regenerateAllPlayers();

        if ($affected > 0) {
            Log::info("Energy regenerated for {$affected} players.");
        }
    }
}

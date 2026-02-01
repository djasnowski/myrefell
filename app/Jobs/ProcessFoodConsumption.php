<?php

namespace App\Jobs;

use App\Services\FoodConsumptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessFoodConsumption implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(FoodConsumptionService $foodService): void
    {
        $results = $foodService->processWeeklyConsumption();

        Log::info('ProcessFoodConsumption job completed', [
            'villages_processed' => $results['villages_processed'],
            'towns_processed' => $results['towns_processed'],
            'food_consumed' => $results['food_consumed'],
            'npcs_starving' => $results['npcs_starving'],
            'npcs_died' => $results['npcs_died'],
            'npcs_emigrated' => $results['npcs_emigrated'],
            'players_starving' => $results['players_starving'],
            'players_penalized' => $results['players_penalized'],
        ]);
    }
}

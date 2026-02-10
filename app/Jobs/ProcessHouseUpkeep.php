<?php

namespace App\Jobs;

use App\Services\HouseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessHouseUpkeep implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(HouseService $houseService): void
    {
        $results = $houseService->processUpkeepDegradation();

        Log::info('House upkeep processed', [
            'processed' => $results['processed'],
            'degraded' => $results['degraded'],
            'abandoned' => $results['abandoned'],
        ]);
    }
}

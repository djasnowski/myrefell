<?php

namespace App\Jobs;

use App\Services\ServantService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessServantWages implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(ServantService $servantService): void
    {
        $results = $servantService->processWeeklyWages();

        Log::info('Servant wages processed', [
            'total' => $results['total'],
            'paid' => $results['paid'],
            'strikes' => $results['strikes'],
        ]);
    }
}

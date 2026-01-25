<?php

namespace App\Jobs;

use App\Services\NpcLifecycleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AgeNpcs implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(NpcLifecycleService $lifecycleService): void
    {
        $results = $lifecycleService->processYearlyAging();

        Log::info("AgeNpcs job completed", [
            'npcs_aged' => $results['aged'],
            'npcs_died' => $results['died'],
            'npcs_replaced' => $results['replaced'],
        ]);
    }
}

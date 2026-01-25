<?php

namespace App\Jobs;

use App\Services\ResourceDecayService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessResourceDecay implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(ResourceDecayService $decayService): void
    {
        $results = $decayService->processWeeklyDecay();

        Log::info('ProcessResourceDecay job completed', [
            'stockpiles_processed' => $results['stockpiles_processed'],
            'inventory_processed' => $results['inventory_processed'],
            'items_decayed' => $results['items_decayed'],
            'items_spoiled' => $results['items_spoiled'],
            'items_destroyed' => $results['items_destroyed'],
        ]);
    }
}

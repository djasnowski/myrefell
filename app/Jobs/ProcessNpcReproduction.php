<?php

namespace App\Jobs;

use App\Services\NpcReproductionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessNpcReproduction implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(NpcReproductionService $reproductionService): void
    {
        $results = $reproductionService->processYearlyReproduction();

        Log::info('ProcessNpcReproduction job completed', [
            'new_marriages' => $results['marriages'],
            'new_births' => $results['births'],
        ]);
    }
}

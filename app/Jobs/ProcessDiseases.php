<?php

namespace App\Jobs;

use App\Services\DiseaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDiseases implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(DiseaseService $diseaseService): void
    {
        // Process all active infections (progression, recovery, death, spread)
        $results = $diseaseService->processDailyTick();

        if ($results['processed'] > 0) {
            Log::info('Disease processing completed', [
                'processed' => $results['processed'],
                'recovered' => $results['recovered'],
                'deceased' => $results['deceased'],
                'new_symptoms' => $results['new_symptoms'],
            ]);
        }
    }
}

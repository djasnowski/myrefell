<?php

namespace App\Jobs;

use App\Models\Calendar;
use App\Services\DisasterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDisasters implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(DisasterService $disasterService): void
    {
        // Get current season from calendar
        $calendar = Calendar::current();
        $season = $calendar?->season ?? 'spring';

        // Check for new disasters
        $triggered = $disasterService->checkForDisasters($season);

        if (count($triggered) > 0) {
            Log::info('Disasters triggered', [
                'count' => count($triggered),
                'season' => $season,
            ]);
        }

        // Process ongoing disasters (end those that have run their course)
        $results = $disasterService->processDailyDisasters();

        if (count($results) > 0) {
            Log::info('Disasters processed', $results);
        }
    }
}

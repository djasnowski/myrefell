<?php

namespace App\Jobs;

use App\Services\CalendarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AdvanceWorldTime implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(CalendarService $calendarService): void
    {
        $processed = $calendarService->processTick();

        if ($processed) {
            $state = $calendarService->getCurrentState();
            Log::info("World time tick processed: {$state->getFormattedDate()}");
        }
    }
}

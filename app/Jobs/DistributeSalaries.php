<?php

namespace App\Jobs;

use App\Services\TaxService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DistributeSalaries implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(TaxService $taxService): void
    {
        $results = $taxService->distributeSalaries();

        Log::info("Daily salaries distributed", [
            'salaries_paid' => $results['salaries_paid'],
            'total_amount' => $results['total_amount'],
            'failed' => $results['failed'],
        ]);
    }
}

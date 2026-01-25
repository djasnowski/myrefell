<?php

namespace App\Jobs;

use App\Services\TaxService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CollectDailyTaxes implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(TaxService $taxService): void
    {
        $results = $taxService->collectDailyTaxes();

        Log::info("Daily taxes collected", [
            'players_taxed' => $results['players_taxed'],
            'player_tax_total' => $results['player_tax_total'],
            'village_upstream_total' => $results['village_upstream_total'],
            'castle_upstream_total' => $results['castle_upstream_total'],
        ]);
    }
}

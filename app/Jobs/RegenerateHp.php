<?php

namespace App\Jobs;

use App\Services\HpService;
use Illuminate\Support\Facades\Log;

class RegenerateHp
{
    /**
     * Execute the job.
     */
    public function handle(HpService $hpService): void
    {
        $affected = $hpService->regenerateAllPlayers();

        if ($affected > 0) {
            Log::info("HP regenerated for {$affected} players.");
        }
    }
}

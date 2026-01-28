<?php

namespace App\Jobs;

use App\Services\MarriageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireMarriageProposals implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(MarriageService $marriageService): void
    {
        $expired = $marriageService->expireOldProposals();

        if ($expired > 0) {
            Log::info('Marriage proposals expired', ['count' => $expired]);
        }
    }
}

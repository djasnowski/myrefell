<?php

namespace App\Jobs;

use App\Models\Election;
use App\Services\ElectionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FinalizeElections implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(ElectionService $electionService): void
    {
        // Find all open elections that have ended
        $elections = Election::where('status', Election::STATUS_OPEN)
            ->where('voting_ends_at', '<=', now())
            ->get();

        if ($elections->isEmpty()) {
            return;
        }

        $finalized = 0;
        $failed = 0;

        foreach ($elections as $election) {
            try {
                $result = $electionService->finalizeElection($election);

                if ($result->status === Election::STATUS_COMPLETED) {
                    $finalized++;
                    Log::info("Election #{$election->id} completed. Winner: User #{$result->winner_user_id}");
                } else {
                    $failed++;
                    Log::info("Election #{$election->id} failed: {$result->notes}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to finalize election #{$election->id}: {$e->getMessage()}");
            }
        }

        if ($finalized > 0 || $failed > 0) {
            Log::info("Election finalization: {$finalized} completed, {$failed} failed.");
        }
    }
}

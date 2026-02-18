<?php

namespace App\Jobs;

use App\Models\ActionQueue;
use App\Models\User;
use App\Services\AgilityService;
use App\Services\CookingService;
use App\Services\CraftingService;
use App\Services\GatheringService;
use App\Services\TrainingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessActionQueue implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(
        public int $queueId
    ) {
        $this->onQueue('action-queue');
    }

    public function handle(
        CookingService $cookingService,
        CraftingService $craftingService,
        GatheringService $gatheringService,
        TrainingService $trainingService,
        AgilityService $agilityService
    ): void {
        $queue = ActionQueue::find($this->queueId);

        if (! $queue || $queue->status !== 'active') {
            return;
        }

        $user = User::find($queue->user_id);

        if (! $user) {
            $queue->update(['status' => 'failed', 'stop_reason' => 'Player not found.']);

            return;
        }

        // Refresh user to get latest state
        $user->refresh();

        // Check if player is traveling or in infirmary
        if ($user->isTraveling()) {
            $queue->update(['status' => 'cancelled', 'stop_reason' => 'You started traveling.']);

            return;
        }

        if ($user->isInInfirmary()) {
            $queue->update(['status' => 'cancelled', 'stop_reason' => 'You were sent to the infirmary.']);

            return;
        }

        $params = $queue->action_params;
        $locationType = $params['location_type'] ?? $user->current_location_type;
        $locationId = $params['location_id'] ?? $user->current_location_id;

        // Execute the action via the appropriate service
        $result = match ($queue->action_type) {
            'cook' => $cookingService->cook(
                $user,
                $params['recipe'],
                $locationType,
                (int) $locationId
            ),
            'craft', 'smelt' => $craftingService->craft(
                $user,
                $params['recipe'],
                $locationType,
                $locationId
            ),
            'gather' => $gatheringService->gather(
                $user,
                $params['activity'],
                $locationType,
                $locationId,
                $params['resource'] ?? null
            ),
            'train' => $trainingService->train(
                $user,
                $params['exercise'],
                $locationType,
                $locationId
            ),
            'agility' => $agilityService->train(
                $user,
                $params['obstacle'],
                $locationType,
                $locationId
            ),
            default => ['success' => false, 'message' => 'Unknown action type.'],
        };

        // Determine if this action should count as a continuation
        $isAgility = $queue->action_type === 'agility';
        $shouldContinue = $result['success'] || ($isAgility && isset($result['failed']) && $result['failed'] === true);

        if (! $shouldContinue) {
            $queue->update([
                'status' => 'failed',
                'stop_reason' => $result['message'] ?? 'Action failed.',
            ]);

            return;
        }

        // Update queue stats
        $queue->completed++;
        $queue->total_xp += $result['xp_awarded'] ?? 0;

        // Track item/resource name and quantity
        if (isset($result['item'])) {
            $queue->item_name = $result['item']['name'];
            $queue->total_quantity += $result['item']['quantity'] ?? 1;
        } elseif (isset($result['resource'])) {
            $queue->item_name = $result['resource']['name'];
            $queue->total_quantity += $result['quantity'] ?? 1;
        } else {
            $queue->total_quantity += 1;
        }

        // Track level ups
        if (! empty($result['leveled_up']) && ! empty($result['new_level']) && ! empty($result['skill'])) {
            $queue->last_level_up = [
                'skill' => $result['skill'],
                'level' => $result['new_level'],
            ];
        }

        // Check if queue is complete (total=0 means infinite)
        if ($queue->total > 0 && $queue->completed >= $queue->total) {
            $queue->status = 'completed';
            $queue->save();

            return;
        }

        $queue->save();

        // Dispatch next iteration with delay
        ProcessActionQueue::dispatch($this->queueId)
            ->onQueue('action-queue')
            ->delay(now()->addSeconds(3));
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        $queue = ActionQueue::find($this->queueId);

        if ($queue && $queue->status === 'active') {
            $queue->update([
                'status' => 'failed',
                'stop_reason' => 'An unexpected error occurred.',
            ]);
        }

        Log::error('ProcessActionQueue failed', [
            'queue_id' => $this->queueId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}

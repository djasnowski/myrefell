<?php

namespace App\Services;

use App\Jobs\ProcessActionQueue;
use App\Models\ActionQueue;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ActionQueueService
{
    /**
     * Start a new action queue for the user.
     *
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message: string, queue?: ActionQueue}
     */
    public function startQueue(User $user, string $actionType, array $params, int $total): array
    {
        return DB::transaction(function () use ($user, $actionType, $params, $total) {
            // Check for existing active queue (with lock to prevent race conditions)
            $existing = ActionQueue::query()
                ->forUser($user->id)
                ->active()
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return ['success' => false, 'message' => 'You already have an active queue running.'];
            }

            $queue = ActionQueue::create([
                'user_id' => $user->id,
                'action_type' => $actionType,
                'action_params' => $params,
                'status' => 'active',
                'total' => $total,
                'completed' => 0,
                'total_xp' => 0,
                'total_quantity' => 0,
            ]);

            ProcessActionQueue::dispatch($queue->id)->onQueue('action-queue');

            return ['success' => true, 'message' => 'Queue started.', 'queue' => $queue];
        });
    }

    /**
     * Cancel the user's active queue.
     *
     * @return array{success: bool, message: string}
     */
    public function cancelQueue(User $user): array
    {
        $queue = ActionQueue::query()
            ->forUser($user->id)
            ->active()
            ->first();

        if (! $queue) {
            return ['success' => false, 'message' => 'No active queue to cancel.'];
        }

        $queue->update([
            'status' => 'cancelled',
            'stop_reason' => 'Cancelled by player.',
        ]);

        return ['success' => true, 'message' => 'Queue cancelled.'];
    }

    /**
     * Get the user's currently active queue.
     */
    public function getActiveQueue(User $user): ?ActionQueue
    {
        return ActionQueue::query()
            ->forUser($user->id)
            ->active()
            ->first();
    }

    /**
     * Get the latest visible queue for display in the UI.
     */
    public function getLatestQueue(User $user): ?ActionQueue
    {
        return ActionQueue::query()
            ->forUser($user->id)
            ->visible()
            ->latest()
            ->first();
    }

    /**
     * Dismiss a completed queue notification so it no longer shows in the UI.
     *
     * @return array{success: bool, message: string}
     */
    public function dismissQueue(User $user, int $queueId): array
    {
        $queue = ActionQueue::query()
            ->forUser($user->id)
            ->where('id', $queueId)
            ->whereIn('status', ['completed', 'cancelled', 'failed'])
            ->first();

        if (! $queue) {
            return ['success' => false, 'message' => 'Queue not found.'];
        }

        $queue->update(['dismissed_at' => now()]);

        return ['success' => true, 'message' => 'Queue dismissed.'];
    }

    /**
     * Mark stale active queues as failed.
     * A queue is stale if it's been active but not updated in over 5 minutes.
     */
    public function cleanupStaleQueues(): int
    {
        return ActionQueue::query()
            ->active()
            ->where('updated_at', '<', now()->subMinutes(5))
            ->update([
                'status' => 'failed',
                'stop_reason' => 'Queue timed out (worker may have stopped).',
            ]);
    }
}

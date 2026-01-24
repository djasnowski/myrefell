<?php

namespace App\Http\Controllers;

use App\Models\PlayerDailyTask;
use App\Services\DailyTaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DailyTaskController extends Controller
{
    public function __construct(
        protected DailyTaskService $dailyTaskService
    ) {}

    /**
     * Display the daily tasks page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tasks = $this->dailyTaskService->getTodaysTasks($user);
        $stats = $this->dailyTaskService->getTaskStats($user);

        return Inertia::render('DailyTasks/Index', [
            'tasks' => $tasks->map(fn ($playerTask) => [
                'id' => $playerTask->id,
                'name' => $playerTask->dailyTask->name,
                'description' => $playerTask->dailyTask->description,
                'category' => $playerTask->dailyTask->category,
                'task_type' => $playerTask->dailyTask->task_type,
                'current_progress' => $playerTask->current_progress,
                'target_amount' => $playerTask->target_amount,
                'progress_percent' => $playerTask->progress_percent,
                'status' => $playerTask->status,
                'rewards' => [
                    'gold' => $playerTask->dailyTask->gold_reward,
                    'xp' => $playerTask->dailyTask->xp_reward,
                    'xp_skill' => $playerTask->dailyTask->xp_skill,
                ],
                'energy_cost' => $playerTask->dailyTask->energy_cost,
            ]),
            'stats' => $stats,
        ]);
    }

    /**
     * Get tasks as JSON (for sidebar/dashboard).
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $tasks = $this->dailyTaskService->getTodaysTasks($user);
        $stats = $this->dailyTaskService->getTaskStats($user);

        return response()->json([
            'tasks' => $tasks->map(fn ($playerTask) => [
                'id' => $playerTask->id,
                'name' => $playerTask->dailyTask->name,
                'current_progress' => $playerTask->current_progress,
                'target_amount' => $playerTask->target_amount,
                'progress_percent' => $playerTask->progress_percent,
                'status' => $playerTask->status,
            ]),
            'stats' => $stats,
        ]);
    }

    /**
     * Claim reward for a completed task.
     */
    public function claim(Request $request, PlayerDailyTask $task): RedirectResponse
    {
        $user = $request->user();

        try {
            $rewards = $this->dailyTaskService->claimReward($user, $task);

            return back()->with('success', $this->formatRewardMessage($rewards));
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Manually progress a task (for testing/demo).
     */
    public function progress(Request $request, PlayerDailyTask $task): RedirectResponse
    {
        $user = $request->user();

        if ($task->user_id !== $user->id) {
            return back()->with('error', 'Task does not belong to you.');
        }

        $request->validate([
            'amount' => 'sometimes|integer|min:1|max:100',
        ]);

        $amount = $request->input('amount', 1);

        if (! $task->isActive()) {
            return back()->with('error', 'Task is not active.');
        }

        // Check and consume energy
        $energyCost = $task->dailyTask->energy_cost ?? 0;
        if ($energyCost > 0) {
            if (! $user->hasEnergy($energyCost)) {
                return back()->with('error', "Not enough energy. Need {$energyCost} energy.");
            }
            $user->consumeEnergy($energyCost);
        }

        $task->addProgress($amount);

        $message = $task->isCompleted()
            ? 'Task completed! Claim your reward.'
            : "Progress: {$task->current_progress}/{$task->target_amount}";

        return back()->with('success', $message);
    }

    /**
     * Format reward message for display.
     */
    protected function formatRewardMessage(array $rewards): string
    {
        $parts = [];

        if (isset($rewards['gold'])) {
            $parts[] = "{$rewards['gold']} gold";
        }

        if (isset($rewards['xp'])) {
            $skill = ucfirst($rewards['xp']['skill']);
            $parts[] = "{$rewards['xp']['amount']} {$skill} XP";
        }

        return 'Received: '.implode(', ', $parts);
    }
}

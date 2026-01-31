<?php

namespace App\Services;

use App\Models\DailyTask;
use App\Models\PlayerDailyTask;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DailyTaskService
{
    /**
     * Number of daily tasks to assign per day.
     */
    public const TASKS_PER_DAY = 3;

    /**
     * Get or generate today's tasks for a user.
     */
    public function getTodaysTasks(User $user): Collection
    {
        $today = today();

        // Check if user already has tasks for today
        $existingTasks = PlayerDailyTask::where('user_id', $user->id)
            ->where('assigned_date', $today)
            ->with('dailyTask')
            ->get();

        if ($existingTasks->count() >= self::TASKS_PER_DAY) {
            return $existingTasks;
        }

        // Generate new tasks if needed
        return $this->assignDailyTasks($user);
    }

    /**
     * Assign daily tasks to a user.
     */
    public function assignDailyTasks(User $user): Collection
    {
        $today = today();

        // Expire old tasks
        PlayerDailyTask::where('user_id', $user->id)
            ->where('assigned_date', '<', $today)
            ->where('status', PlayerDailyTask::STATUS_ACTIVE)
            ->update(['status' => PlayerDailyTask::STATUS_EXPIRED]);

        // Get already assigned task IDs for today
        $assignedTaskIds = PlayerDailyTask::where('user_id', $user->id)
            ->where('assigned_date', $today)
            ->pluck('daily_task_id');

        // Get available tasks the player qualifies for
        $availableTasks = DailyTask::where('is_active', true)
            ->whereNotIn('id', $assignedTaskIds)
            ->get()
            ->filter(fn ($task) => $task->playerMeetsRequirements($user));

        // Select tasks using weighted random
        $tasksNeeded = self::TASKS_PER_DAY - $assignedTaskIds->count();
        $selectedTasks = $this->weightedRandomSelect($availableTasks, $tasksNeeded);

        // Create player task assignments (use firstOrCreate to avoid duplicates)
        foreach ($selectedTasks as $task) {
            PlayerDailyTask::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'daily_task_id' => $task->id,
                    'assigned_date' => $today,
                ],
                [
                    'current_progress' => 0,
                    'target_amount' => $task->target_amount,
                    'status' => PlayerDailyTask::STATUS_ACTIVE,
                ]
            );
        }

        return PlayerDailyTask::where('user_id', $user->id)
            ->where('assigned_date', $today)
            ->with('dailyTask')
            ->get();
    }

    /**
     * Select tasks using weighted random selection.
     */
    protected function weightedRandomSelect(Collection $tasks, int $count): Collection
    {
        if ($tasks->isEmpty() || $count <= 0) {
            return collect();
        }

        $selected = collect();
        $remaining = $tasks->values();

        while ($selected->count() < $count && $remaining->isNotEmpty()) {
            $totalWeight = $remaining->sum('weight');

            if ($totalWeight <= 0) {
                break;
            }

            $random = mt_rand(1, $totalWeight);
            $cumulative = 0;

            foreach ($remaining as $index => $task) {
                $cumulative += $task->weight;
                if ($random <= $cumulative) {
                    $selected->push($task);
                    $remaining = $remaining->except($index)->values();
                    break;
                }
            }
        }

        return $selected;
    }

    /**
     * Record progress for a task type.
     */
    public function recordProgress(User $user, string $taskType, ?string $targetIdentifier = null, int $amount = 1): void
    {
        $query = PlayerDailyTask::where('user_id', $user->id)
            ->where('assigned_date', today())
            ->where('status', PlayerDailyTask::STATUS_ACTIVE)
            ->whereHas('dailyTask', function ($q) use ($taskType, $targetIdentifier) {
                $q->where('task_type', $taskType);
                if ($targetIdentifier) {
                    $q->where(function ($q2) use ($targetIdentifier) {
                        $q2->whereNull('target_identifier')
                            ->orWhere('target_identifier', $targetIdentifier);
                    });
                }
            });

        $tasks = $query->get();

        foreach ($tasks as $task) {
            $task->addProgress($amount);
        }
    }

    /**
     * Claim rewards for a completed task.
     */
    public function claimReward(User $user, PlayerDailyTask $playerTask): array
    {
        if ($playerTask->user_id !== $user->id) {
            throw new \InvalidArgumentException('Task does not belong to this user.');
        }

        if (! $playerTask->isCompleted()) {
            throw new \InvalidArgumentException('Task is not completed yet.');
        }

        $task = $playerTask->dailyTask;
        $rewards = [];

        return DB::transaction(function () use ($user, $playerTask, $task, &$rewards) {
            // Grant gold reward
            if ($task->gold_reward > 0) {
                $user->increment('gold', $task->gold_reward);
                $rewards['gold'] = $task->gold_reward;
            }

            // Grant XP reward
            if ($task->xp_reward > 0 && $task->xp_skill) {
                $skill = $user->skills()->where('skill_name', $task->xp_skill)->first();

                if (! $skill) {
                    $skill = $user->skills()->create([
                        'skill_name' => $task->xp_skill,
                        'level' => 1,
                        'xp' => 0,
                    ]);
                }

                $skill->addXp($task->xp_reward);
                $rewards['xp'] = [
                    'amount' => $task->xp_reward,
                    'skill' => $task->xp_skill,
                ];
            }

            // Mark as claimed
            $playerTask->claim();

            return $rewards;
        });
    }

    /**
     * Get task statistics for a user.
     */
    public function getTaskStats(User $user): array
    {
        $today = today();

        return [
            'completed_today' => PlayerDailyTask::where('user_id', $user->id)
                ->where('assigned_date', $today)
                ->whereIn('status', [PlayerDailyTask::STATUS_COMPLETED, PlayerDailyTask::STATUS_CLAIMED])
                ->count(),
            'total_today' => PlayerDailyTask::where('user_id', $user->id)
                ->where('assigned_date', $today)
                ->count(),
            'total_completed' => PlayerDailyTask::where('user_id', $user->id)
                ->whereIn('status', [PlayerDailyTask::STATUS_COMPLETED, PlayerDailyTask::STATUS_CLAIMED])
                ->count(),
        ];
    }

    /**
     * Seed default daily tasks.
     */
    public static function seedDefaultTasks(): void
    {
        $tasks = [
            // Combat tasks
            [
                'name' => 'Rat Exterminator',
                'icon' => 'bug',
                'description' => 'Clear rats from the village cellar.',
                'category' => 'combat',
                'task_type' => 'kill',
                'target_type' => 'monster',
                'target_identifier' => 'rat',
                'target_amount' => 3,
                'gold_reward' => 15,
                'xp_reward' => 20,
                'xp_skill' => 'attack',
                'weight' => 100,
            ],
            [
                'name' => 'Wolf Hunter',
                'icon' => 'crosshair',
                'description' => 'Protect the shepherds from wolves.',
                'category' => 'combat',
                'task_type' => 'kill',
                'target_type' => 'monster',
                'target_identifier' => 'wolf',
                'target_amount' => 2,
                'required_skill' => 'attack',
                'required_skill_level' => 10,
                'gold_reward' => 35,
                'xp_reward' => 50,
                'xp_skill' => 'strength',
                'weight' => 80,
            ],

            // Gathering tasks
            [
                'name' => 'Firewood Collection',
                'icon' => 'tree-deciduous',
                'description' => 'Chop wood for the village.',
                'category' => 'gathering',
                'task_type' => 'chop',
                'target_type' => 'resource',
                'target_identifier' => 'wood',
                'target_amount' => 5,
                'gold_reward' => 20,
                'xp_reward' => 30,
                'xp_skill' => 'woodcutting',
                'energy_cost' => 5,
                'weight' => 100,
            ],
            [
                'name' => 'Iron Collection',
                'icon' => 'pickaxe',
                'description' => 'Mine iron ore for the blacksmith.',
                'category' => 'gathering',
                'task_type' => 'mine',
                'target_type' => 'resource',
                'target_identifier' => 'iron_ore',
                'target_amount' => 3,
                'required_skill' => 'mining',
                'required_skill_level' => 5,
                'gold_reward' => 30,
                'xp_reward' => 40,
                'xp_skill' => 'mining',
                'energy_cost' => 10,
                'weight' => 90,
            ],
            [
                'name' => 'Fresh Catch',
                'icon' => 'fish',
                'description' => 'Catch fish for the tavern.',
                'category' => 'gathering',
                'task_type' => 'fish',
                'target_type' => 'resource',
                'target_identifier' => 'fish',
                'target_amount' => 4,
                'gold_reward' => 25,
                'xp_reward' => 35,
                'xp_skill' => 'fishing',
                'energy_cost' => 8,
                'weight' => 95,
            ],

            // Crafting tasks
            [
                'name' => 'Bread Baker',
                'icon' => 'croissant',
                'description' => 'Bake bread for the village.',
                'category' => 'crafting',
                'task_type' => 'cook',
                'target_type' => 'item',
                'target_identifier' => 'bread',
                'target_amount' => 3,
                'gold_reward' => 20,
                'xp_reward' => 25,
                'xp_skill' => 'cooking',
                'weight' => 100,
            ],
            [
                'name' => 'Nail Maker',
                'icon' => 'hammer',
                'description' => 'Smith nails for construction.',
                'category' => 'crafting',
                'task_type' => 'smith',
                'target_type' => 'item',
                'target_identifier' => 'nails',
                'target_amount' => 10,
                'required_skill' => 'smithing',
                'required_skill_level' => 5,
                'gold_reward' => 35,
                'xp_reward' => 45,
                'xp_skill' => 'smithing',
                'weight' => 85,
            ],

            // Service tasks
            [
                'name' => 'Training Dummy',
                'icon' => 'swords',
                'description' => 'Practice your combat skills.',
                'category' => 'service',
                'task_type' => 'train',
                'target_type' => 'skill',
                'target_identifier' => 'attack',
                'target_amount' => 1,
                'gold_reward' => 10,
                'xp_reward' => 50,
                'xp_skill' => 'attack',
                'energy_cost' => 5,
                'weight' => 100,
            ],
            [
                'name' => 'Water Carrier',
                'icon' => 'droplets',
                'description' => 'Deliver water from the well.',
                'category' => 'service',
                'task_type' => 'deliver',
                'target_type' => 'item',
                'target_identifier' => 'water_bucket',
                'target_amount' => 5,
                'gold_reward' => 15,
                'xp_reward' => 15,
                'xp_skill' => 'strength',
                'weight' => 100,
            ],
            [
                'name' => 'Daily Devotion',
                'icon' => 'church',
                'description' => 'Pray at the shrine to honor the gods.',
                'category' => 'service',
                'task_type' => 'pray',
                'target_type' => 'action',
                'target_identifier' => null,
                'target_amount' => 1,
                'gold_reward' => 10,
                'xp_reward' => 25,
                'xp_skill' => 'prayer',
                'weight' => 100,
            ],
        ];

        foreach ($tasks as $taskData) {
            DailyTask::updateOrCreate(
                ['name' => $taskData['name']],
                $taskData
            );
        }
    }
}

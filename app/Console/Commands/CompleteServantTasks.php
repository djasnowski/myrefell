<?php

namespace App\Console\Commands;

use App\Models\ServantTask;
use App\Services\ServantService;
use Illuminate\Console\Command;

class CompleteServantTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'servant:complete-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Complete servant tasks whose timers have expired';

    /**
     * Execute the console command.
     */
    public function handle(ServantService $servantService): int
    {
        $tasks = ServantTask::where('status', 'in_progress')
            ->where('estimated_completion', '<=', now())
            ->get();

        if ($tasks->isEmpty()) {
            $this->info('No servant tasks ready for completion.');

            return self::SUCCESS;
        }

        $this->info("Found {$tasks->count()} task(s) to complete.");

        foreach ($tasks as $task) {
            $servantService->completeTask($task);
            $this->line("- Completed: {$task->task_type} (#{$task->id})");
        }

        $this->info('All ready tasks have been completed.');

        return self::SUCCESS;
    }
}

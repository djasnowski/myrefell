<?php

namespace App\Console\Commands;

use App\Models\CultHideoutProject;
use App\Services\CultHideoutService;
use Illuminate\Console\Command;

class CompleteCultHideoutProjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hideout:complete-construction';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Complete cult hideout construction projects whose timers have expired';

    /**
     * Execute the console command.
     */
    public function handle(CultHideoutService $hideoutService): int
    {
        $projects = CultHideoutProject::where('status', CultHideoutProject::STATUS_CONSTRUCTING)
            ->where('construction_ends_at', '<=', now())
            ->with('religion')
            ->get();

        if ($projects->isEmpty()) {
            $this->info('No hideout construction projects ready for completion.');

            return self::SUCCESS;
        }

        $this->info("Found {$projects->count()} hideout project(s) to complete.");

        foreach ($projects as $project) {
            $hideoutService->finalizeProject($project);
            $this->line("- Completed: {$project->description}");
        }

        $this->info('All ready hideout projects have been completed.');

        return self::SUCCESS;
    }
}

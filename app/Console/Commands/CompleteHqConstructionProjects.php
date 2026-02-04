<?php

namespace App\Console\Commands;

use App\Models\HqConstructionProject;
use App\Services\ReligionHeadquartersService;
use Illuminate\Console\Command;

class CompleteHqConstructionProjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hq:complete-construction';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Complete HQ construction projects whose timers have expired';

    /**
     * Execute the console command.
     */
    public function handle(ReligionHeadquartersService $hqService): int
    {
        $projects = HqConstructionProject::where('status', HqConstructionProject::STATUS_CONSTRUCTING)
            ->where('construction_ends_at', '<=', now())
            ->with(['headquarters.religion', 'featureType'])
            ->get();

        if ($projects->isEmpty()) {
            $this->info('No construction projects ready for completion.');

            return self::SUCCESS;
        }

        $this->info("Found {$projects->count()} project(s) to complete.");

        foreach ($projects as $project) {
            $hqService->finalizeProject($project);
            $this->line("- Completed: {$project->description}");
        }

        $this->info('All ready projects have been completed.');

        return self::SUCCESS;
    }
}

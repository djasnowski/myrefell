<?php

namespace App\Console\Commands;

use App\Models\TabActivityLog;
use Illuminate\Console\Command;

class PruneTabActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tab-activity:prune {--days=3 : Number of days to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune tab activity logs older than specified days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $deleted = TabActivityLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Pruned {$deleted} tab activity logs older than {$days} days.");

        return self::SUCCESS;
    }
}

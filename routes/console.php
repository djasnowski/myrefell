<?php

use App\Jobs\AdvanceWorldTime;
use App\Jobs\CollectDailyTaxes;
use App\Jobs\DistributeSalaries;
use App\Jobs\ExpireMarriageProposals;
use App\Jobs\FinalizeElections;
use App\Jobs\ProcessDisasters;
use App\Jobs\ProcessDiseases;
use App\Jobs\RegenerateEnergy;
use App\Jobs\RegenerateHp;
use App\Services\DungeonLootService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Energy regeneration - +10 every 5 minutes (10 seconds in local for testing)
if (app()->environment('local')) {
    Schedule::job(new RegenerateEnergy)->everyTenSeconds();
} else {
    Schedule::job(new RegenerateEnergy)->everyFiveMinutes();
}

// HP regeneration - 5% of max HP every 5 minutes (with bonuses)
Schedule::job(new RegenerateHp)->everyFiveMinutes();

// Election finalization - every 5 minutes
Schedule::job(new FinalizeElections)->everyFiveMinutes();

// Tax collection - daily at midnight
Schedule::job(new CollectDailyTaxes)->dailyAt('00:00');

// Salary distribution - daily at 00:15 (after taxes collected)
Schedule::job(new DistributeSalaries)->dailyAt('00:15');

// World time advancement - daily at midnight (1 real day = 1 game week)
Schedule::job(new AdvanceWorldTime)->dailyAt('00:00');

// Disaster processing - daily at 06:00 (check for new disasters, process ongoing)
Schedule::job(new ProcessDisasters)->dailyAt('06:00');

// Disease processing - daily at 06:30 (infection progression, spread, recovery)
Schedule::job(new ProcessDiseases)->dailyAt('06:30');

// Marriage proposal expiration - daily at 00:30
Schedule::job(new ExpireMarriageProposals)->dailyAt('00:30');

// Seed restocking - twice daily (farmer supply runs)
Schedule::command('market:restock-seeds')->twiceDaily(8, 20);

// HQ construction completion - every minute (check for timer expirations)
Schedule::command('hq:complete-construction')->everyMinute();

// Cult hideout construction completion - every minute (check for timer expirations)
Schedule::command('hideout:complete-construction')->everyMinute();

// Prune old tab activity logs - daily at 03:00
Schedule::command('tab-activity:prune')->dailyAt('03:00');

// Clean up expired dungeon loot - daily at 04:00
Schedule::call(function () {
    $deleted = DungeonLootService::cleanupExpiredLoot();
    Log::info("Cleaned up {$deleted} expired dungeon loot entries");
})->dailyAt('04:00');

<?php

use App\Jobs\AdvanceWorldTime;
use App\Jobs\CollectDailyTaxes;
use App\Jobs\DistributeSalaries;
use App\Jobs\FinalizeElections;
use App\Jobs\RegenerateEnergy;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Energy regeneration - every 5 minutes
Schedule::job(new RegenerateEnergy)->everyFiveMinutes();

// Election finalization - every minute
Schedule::job(new FinalizeElections)->everyMinute();

// Tax collection - daily at midnight
Schedule::job(new CollectDailyTaxes)->dailyAt('00:00');

// Salary distribution - daily at 00:15 (after taxes collected)
Schedule::job(new DistributeSalaries)->dailyAt('00:15');

// World time advancement - daily at midnight (1 real day = 1 game week)
Schedule::job(new AdvanceWorldTime)->dailyAt('00:00');

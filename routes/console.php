<?php

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

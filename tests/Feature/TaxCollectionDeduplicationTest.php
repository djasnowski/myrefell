<?php

use App\Models\User;
use App\Models\Village;
use App\Services\TaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('collects taxes on the first run', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'home_village_id' => $village->id,
        'gold' => 1000,
    ]);

    $taxService = app(TaxService::class);
    $results = $taxService->collectDailyTaxes();

    expect($results['players_taxed'])->toBe(1);
    expect($user->fresh()->gold)->toBeLessThan(1000);
});

it('skips tax collection if already collected today', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'home_village_id' => $village->id,
        'gold' => 1000,
    ]);

    $taxService = app(TaxService::class);

    // First run collects taxes
    $taxService->collectDailyTaxes();
    $goldAfterFirstRun = $user->fresh()->gold;

    // Second run should be skipped
    $results = $taxService->collectDailyTaxes();

    expect($results['players_taxed'])->toBe(0);
    expect($user->fresh()->gold)->toBe($goldAfterFirstRun);
});

it('collects taxes on a new day after previous collection', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'home_village_id' => $village->id,
        'gold' => 1000,
    ]);

    $taxService = app(TaxService::class);

    // Collect for yesterday
    $this->travel(-1)->days();
    $taxService->collectDailyTaxes();
    $goldAfterFirstRun = $user->fresh()->gold;

    // Collect for today should work
    $this->travelBack();
    $results = $taxService->collectDailyTaxes();

    expect($results['players_taxed'])->toBe(1);
    expect($user->fresh()->gold)->toBeLessThan($goldAfterFirstRun);
});

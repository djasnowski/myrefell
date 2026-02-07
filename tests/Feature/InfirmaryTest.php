<?php

use App\Models\User;
use App\Services\InfirmaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admitPlayer sets infirmary state and timer', function () {
    $user = User::factory()->create(['hp' => 0]);
    $service = app(InfirmaryService::class);

    $service->admitPlayer($user);
    $user->refresh();

    expect($user->is_in_infirmary)->toBeTrue();
    expect($user->infirmary_started_at)->not->toBeNull();
    expect($user->infirmary_heals_at)->not->toBeNull();
    expect($user->infirmary_heals_at->isFuture())->toBeTrue();
});

test('isInInfirmary returns true when in infirmary with future timer', function () {
    $user = User::factory()->create([
        'is_in_infirmary' => true,
        'infirmary_started_at' => now(),
        'infirmary_heals_at' => now()->addMinutes(10),
    ]);

    expect($user->isInInfirmary())->toBeTrue();
});

test('isInInfirmary returns false when timer has expired', function () {
    $user = User::factory()->create([
        'is_in_infirmary' => true,
        'infirmary_started_at' => now()->subMinutes(15),
        'infirmary_heals_at' => now()->subMinutes(5),
    ]);

    expect($user->isInInfirmary())->toBeFalse();
});

test('isInInfirmary returns false when not in infirmary', function () {
    $user = User::factory()->create([
        'is_in_infirmary' => false,
    ]);

    expect($user->isInInfirmary())->toBeFalse();
});

test('checkAndDischarge restores full HP when timer expired', function () {
    $user = User::factory()->create([
        'hp' => 0,
        'is_in_infirmary' => true,
        'infirmary_started_at' => now()->subMinutes(15),
        'infirmary_heals_at' => now()->subMinutes(5),
    ]);

    $service = app(InfirmaryService::class);
    $result = $service->checkAndDischarge($user);
    $user->refresh();

    expect($result)->toBeTrue();
    expect($user->hp)->toBe($user->max_hp);
    expect($user->is_in_infirmary)->toBeFalse();
    expect($user->infirmary_started_at)->toBeNull();
    expect($user->infirmary_heals_at)->toBeNull();
});

test('checkAndDischarge does not discharge when timer is still active', function () {
    $user = User::factory()->create([
        'hp' => 0,
        'is_in_infirmary' => true,
        'infirmary_started_at' => now(),
        'infirmary_heals_at' => now()->addMinutes(5),
    ]);

    $service = app(InfirmaryService::class);
    $result = $service->checkAndDischarge($user);
    $user->refresh();

    expect($result)->toBeFalse();
    expect($user->is_in_infirmary)->toBeTrue();
    expect($user->hp)->toBe(0);
});

test('getInfirmaryStatus returns null when not in infirmary', function () {
    $user = User::factory()->create();
    $service = app(InfirmaryService::class);

    expect($service->getInfirmaryStatus($user))->toBeNull();
});

test('getInfirmaryStatus returns status data when in infirmary', function () {
    $user = User::factory()->create([
        'is_in_infirmary' => true,
        'infirmary_started_at' => now(),
        'infirmary_heals_at' => now()->addMinutes(10),
    ]);

    $service = app(InfirmaryService::class);
    $status = $service->getInfirmaryStatus($user);

    expect($status)->not->toBeNull();
    expect($status['is_in_infirmary'])->toBeTrue();
    expect($status['remaining_seconds'])->toBeGreaterThan(0);
    expect($status['heals_at'])->not->toBeNull();
    expect($status['started_at'])->not->toBeNull();
});

test('discharge endpoint works when timer expired', function () {
    $user = User::factory()->create([
        'hp' => 0,
        'is_in_infirmary' => true,
        'infirmary_started_at' => now()->subMinutes(15),
        'infirmary_heals_at' => now()->subMinutes(5),
    ]);

    $this->actingAs($user)
        ->post('/infirmary/discharge')
        ->assertRedirect();

    $user->refresh();
    expect($user->is_in_infirmary)->toBeFalse();
    expect($user->hp)->toBe($user->max_hp);
});

test('discharge endpoint rejects when timer not expired', function () {
    $user = User::factory()->create([
        'hp' => 0,
        'is_in_infirmary' => true,
        'infirmary_started_at' => now(),
        'infirmary_heals_at' => now()->addMinutes(5),
    ]);

    $this->actingAs($user)
        ->post('/infirmary/discharge')
        ->assertRedirect()
        ->assertSessionHas('error');

    $user->refresh();
    expect($user->is_in_infirmary)->toBeTrue();
});

test('discharge endpoint rejects when not in infirmary', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/infirmary/discharge')
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('infirmary blocks combat', function () {
    $user = User::factory()->create([
        'hp' => 10,
        'is_in_infirmary' => true,
        'infirmary_started_at' => now(),
        'infirmary_heals_at' => now()->addMinutes(10),
    ]);

    $combatService = app(\App\Services\CombatService::class);
    $result = $combatService->startCombat($user, 1);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('infirmary');
});

test('infirmary blocks healer access', function () {
    $user = User::factory()->create([
        'is_in_infirmary' => true,
        'infirmary_started_at' => now(),
        'infirmary_heals_at' => now()->addMinutes(10),
        'current_location_type' => 'village',
        'current_location_id' => 1,
    ]);

    $healerService = app(\App\Services\HealerService::class);

    expect($healerService->canAccessHealer($user))->toBeFalse();
});

test('healer page shows infirmary view when player is in infirmary at location', function () {
    $village = \App\Models\Village::factory()->create();

    $user = User::factory()->create([
        'hp' => 0,
        'is_in_infirmary' => true,
        'infirmary_started_at' => now(),
        'infirmary_heals_at' => now()->addMinutes(10),
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
    ]);

    $response = $this->actingAs($user)
        ->get("/villages/{$village->id}/healer");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Healer/Index')
        ->has('infirmary')
        ->where('infirmary.is_in_infirmary', true)
        ->where('infirmary.remaining_seconds', fn ($val) => $val > 0)
    );
});

test('infirmary blocks travel', function () {
    $user = User::factory()->create([
        'is_in_infirmary' => true,
        'infirmary_started_at' => now(),
        'infirmary_heals_at' => now()->addMinutes(10),
    ]);

    $travelService = app(\App\Services\TravelService::class);

    expect(fn () => $travelService->startTravel($user, 'village', 1))
        ->toThrow(\InvalidArgumentException::class, 'infirmary');
});

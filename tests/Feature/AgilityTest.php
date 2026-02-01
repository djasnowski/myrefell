<?php

use App\Models\User;
use App\Models\Village;
use App\Services\AgilityService;

beforeEach(function () {
    User::query()->delete();
});

test('can view agility page at village', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'energy' => 100,
    ]);

    $this->actingAs($user)
        ->get("/villages/{$village->id}/agility")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Agility/Index'));
});

test('agility service returns available obstacles', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'energy' => 100,
    ]);

    $service = app(AgilityService::class);
    $info = $service->getAgilityInfo($user);

    expect($info)->toHaveKey('obstacles');
    expect($info['obstacles'])->toBeArray();
    expect($info['obstacles'])->not->toBeEmpty();
    expect($info['can_train'])->toBeTrue();
});

test('can train agility at village', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'energy' => 100,
    ]);

    $this->actingAs($user)
        ->postJson("/villages/{$village->id}/agility/train", [
            'obstacle' => 'log_balance',
        ])
        ->assertSuccessful();

    // Energy should be consumed
    $user->refresh();
    expect($user->energy)->toBeLessThan(100);
});

test('cannot train agility without enough energy', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'energy' => 0,
    ]);

    $this->actingAs($user)
        ->postJson("/villages/{$village->id}/agility/train", [
            'obstacle' => 'log_balance',
        ])
        ->assertStatus(422);
});

test('cannot train obstacles above current level', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'energy' => 100,
    ]);

    // Try to do a level 30 obstacle at level 1
    $this->actingAs($user)
        ->postJson("/villages/{$village->id}/agility/train", [
            'obstacle' => 'spinning_logs',
        ])
        ->assertStatus(422);
});

test('agility not available at kingdom', function () {
    $user = User::factory()->create([
        'current_location_type' => 'kingdom',
        'current_location_id' => 1,
        'energy' => 100,
    ]);

    $service = app(AgilityService::class);
    expect($service->canTrain($user))->toBeFalse();
});

test('higher level obstacles only at higher tier locations', function () {
    $service = app(AgilityService::class);

    // Level 60+ obstacles should only be at duchy
    $obstacle = AgilityService::OBSTACLES['tower_ascent'];
    expect($obstacle['location_types'])->toBe(['duchy']);
});

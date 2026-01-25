<?php

use App\Models\Item;
use App\Models\User;
use App\Models\Village;
use App\Models\WorldState;
use App\Services\GatheringService;
use App\Services\InventoryService;

beforeEach(function () {
    WorldState::query()->delete();

    // Ensure mining resource items exist
    Item::firstOrCreate(['name' => 'Copper Ore'], [
        'type' => 'resource',
        'description' => 'A chunk of copper ore',
        'stackable' => true,
        'max_stack' => 100,
    ]);
});

test('gathering service returns correct seasonal modifier for each season', function () {
    $service = app(GatheringService::class);

    WorldState::factory()->spring()->create();
    expect($service->getSeasonalModifier())->toBe(0.8);

    WorldState::query()->delete();
    WorldState::factory()->summer()->create();
    expect($service->getSeasonalModifier())->toBe(1.0);

    WorldState::query()->delete();
    WorldState::factory()->autumn()->create();
    expect($service->getSeasonalModifier())->toBe(1.3);

    WorldState::query()->delete();
    WorldState::factory()->winter()->create();
    expect($service->getSeasonalModifier())->toBe(0.5);
});

test('calculate yield returns 1 for normal modifier', function () {
    $service = app(GatheringService::class);

    // With modifier of 1.0, should always return 1
    $results = collect(range(1, 100))->map(fn () => $service->calculateYield(1.0));

    expect($results->unique()->toArray())->toBe([1]);
});

test('calculate yield can return 2 for bonus modifier', function () {
    $service = app(GatheringService::class);

    // With modifier of 1.3 (30% bonus chance), over many iterations should get some 2s
    $results = collect(range(1, 1000))->map(fn () => $service->calculateYield(1.3));

    expect($results->contains(2))->toBeTrue();
    expect($results->contains(1))->toBeTrue();
    expect($results->filter(fn ($v) => $v === 2)->count())->toBeGreaterThan(200); // ~30% of 1000
    expect($results->filter(fn ($v) => $v === 2)->count())->toBeLessThan(400);
});

test('calculate yield returns 1 for penalty modifier', function () {
    $service = app(GatheringService::class);

    // With penalty modifiers, we still return 1 (no total failure)
    $results = collect(range(1, 100))->map(fn () => $service->calculateYield(0.5));

    expect($results->unique()->toArray())->toBe([1]);
});

test('gathering includes seasonal data in activity info', function () {
    WorldState::factory()->autumn()->create();

    $village = Village::factory()->create();
    $user = User::factory()->create([
        'home_village_id' => $village->id,
        'current_location_id' => $village->id,
        'current_location_type' => 'village',
    ]);

    $service = app(GatheringService::class);
    $info = $service->getActivityInfo($user, 'mining');

    expect($info)->toHaveKey('seasonal_modifier');
    expect($info)->toHaveKey('current_season');
    expect($info['seasonal_modifier'])->toBe(1.3);
    expect($info['current_season'])->toBe('autumn');
});

test('gathering includes seasonal data in different seasons', function () {
    WorldState::factory()->winter()->create();

    $village = Village::factory()->create();
    $user = User::factory()->create([
        'home_village_id' => $village->id,
        'current_location_id' => $village->id,
        'current_location_type' => 'village',
    ]);

    $service = app(GatheringService::class);
    $info = $service->getActivityInfo($user, 'mining');

    expect($info['seasonal_modifier'])->toBe(0.5);
    expect($info['current_season'])->toBe('winter');
});

test('gathering result includes seasonal bonus flag when bonus yield occurs', function () {
    WorldState::factory()->autumn()->create();

    $village = Village::factory()->create();
    $user = User::factory()->create([
        'home_village_id' => $village->id,
        'current_location_id' => $village->id,
        'current_location_type' => 'village',
        'energy' => 100,
    ]);

    $service = app(GatheringService::class);

    // Run many gathering attempts to eventually get a bonus
    $gotBonus = false;
    for ($i = 0; $i < 50; $i++) {
        $user->energy = 100;
        $user->save();

        $result = $service->gather($user, 'mining');

        if ($result['success'] && isset($result['seasonal_bonus']) && $result['seasonal_bonus']) {
            $gotBonus = true;
            expect($result['quantity'])->toBe(2);
            break;
        }
    }

    expect($gotBonus)->toBeTrue();
});

test('gathering index endpoint includes seasonal data', function () {
    WorldState::factory()->summer()->create();

    $village = Village::factory()->create();
    $user = User::factory()->create([
        'home_village_id' => $village->id,
        'current_location_id' => $village->id,
        'current_location_type' => 'village',
    ]);

    $this->actingAs($user)
        ->get(route('gathering.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Gathering/Index')
            ->has('seasonal')
            ->where('seasonal.season', 'summer')
            ->where('seasonal.modifier', 1.0)
        );
});

test('gathering show endpoint includes seasonal data in activity', function () {
    WorldState::factory()->spring()->create();

    $village = Village::factory()->create();
    $user = User::factory()->create([
        'home_village_id' => $village->id,
        'current_location_id' => $village->id,
        'current_location_type' => 'village',
    ]);

    $this->actingAs($user)
        ->get(route('gathering.show', 'mining'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Gathering/Activity')
            ->where('activity.seasonal_modifier', 0.8)
            ->where('activity.current_season', 'spring')
        );
});

test('get seasonal data returns complete information', function () {
    WorldState::factory()->create([
        'current_season' => 'autumn',
    ]);

    $service = app(GatheringService::class);
    $data = $service->getSeasonalData();

    expect($data)->toHaveKey('season');
    expect($data)->toHaveKey('modifier');
    expect($data)->toHaveKey('description');
    expect($data['season'])->toBe('autumn');
    expect($data['modifier'])->toBe(1.3);
    expect($data['description'])->toContain('Harvest season');
});

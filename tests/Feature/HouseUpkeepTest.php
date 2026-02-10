<?php

use App\Models\HouseFurniture;
use App\Models\HouseRoom;
use App\Models\Kingdom;
use App\Models\PlayerHouse;
use App\Models\User;
use App\Services\HouseBuffService;
use App\Services\HouseService;

test('can pay upkeep', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['gold' => 10000]);
    PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'Test House',
        'tier' => 'cottage',
        'condition' => 100,
        'upkeep_due_at' => now()->subDay(),
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->payUpkeep($user);

    expect($result['success'])->toBeTrue();

    $user->refresh();
    expect($user->gold)->toBe(10000 - 100);

    $house = PlayerHouse::where('player_id', $user->id)->first();
    expect($house->upkeep_due_at->isFuture())->toBeTrue();
});

test('cannot pay upkeep without enough gold', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['gold' => 10]);
    PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'Test House',
        'tier' => 'cottage',
        'condition' => 100,
        'upkeep_due_at' => now()->subDay(),
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->payUpkeep($user);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Not enough gold');
});

test('can repair house', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['gold' => 50000]);
    PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'Test House',
        'tier' => 'cottage',
        'condition' => 50,
        'upkeep_due_at' => now()->addDays(7),
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->repairHouse($user);

    expect($result['success'])->toBeTrue();

    $house = PlayerHouse::where('player_id', $user->id)->first();
    expect($house->condition)->toBe(100);

    // Cost should be ceil(100 * (100-50) * 0.5) = 2500
    $user->refresh();
    expect($user->gold)->toBe(50000 - 2500);
});

test('cannot repair at full condition', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['gold' => 50000]);
    PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'Test House',
        'tier' => 'cottage',
        'condition' => 100,
        'upkeep_due_at' => now()->addDays(7),
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->repairHouse($user);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('perfect condition');
});

test('degrades overdue houses by 10 condition', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create();
    PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'Test House',
        'tier' => 'cottage',
        'condition' => 80,
        'upkeep_due_at' => now()->subDay(),
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->processUpkeepDegradation();

    expect($result['degraded'])->toBe(1);
    expect($result['abandoned'])->toBe(0);

    $house = PlayerHouse::where('player_id', $user->id)->first();
    expect($house->condition)->toBe(70);
});

test('abandons house at condition 0', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create();
    PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'Test House',
        'tier' => 'cottage',
        'condition' => 5,
        'upkeep_due_at' => now()->subDay(),
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->processUpkeepDegradation();

    expect($result['abandoned'])->toBe(1);
    expect(PlayerHouse::where('player_id', $user->id)->exists())->toBeFalse();
});

test('disables buffs when condition 50 or below', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create();
    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'Test House',
        'tier' => 'cottage',
        'condition' => 50,
        'upkeep_due_at' => now()->addDays(7),
        'kingdom_id' => $kingdom->id,
    ]);

    $room = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'bedroom',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'bed',
        'furniture_key' => 'straw_bed',
    ]);

    $buffService = app(HouseBuffService::class);
    $effects = $buffService->getHouseEffects($user);

    expect($effects)->toBeEmpty();
});

test('disables portals when condition 25 or below', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create();
    PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'Test House',
        'tier' => 'cottage',
        'condition' => 25,
        'upkeep_due_at' => now()->addDays(7),
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->teleportFromPortal($user, 1);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('poor house condition');
});

test('disables storage when condition 25 or below', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create();
    PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'Test House',
        'tier' => 'cottage',
        'condition' => 25,
        'upkeep_due_at' => now()->addDays(7),
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->depositItem($user, 'Wood', 1);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('storage is disabled');
});

test('buffs work when condition above 50', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create();
    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'Test House',
        'tier' => 'cottage',
        'condition' => 51,
        'upkeep_due_at' => now()->addDays(7),
        'kingdom_id' => $kingdom->id,
    ]);

    $room = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'bedroom',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'bed',
        'furniture_key' => 'straw_bed',
    ]);

    $buffService = app(HouseBuffService::class);
    $effects = $buffService->getHouseEffects($user);

    expect($effects)->not->toBeEmpty();
    expect($effects['energy_regen_bonus'])->toBe(5);
});

<?php

use App\Config\ConstructionConfig;
use App\Models\HouseFurniture;
use App\Models\HouseRoom;
use App\Models\Kingdom;
use App\Models\PlayerHouse;
use App\Models\PlayerSkill;
use App\Models\User;
use App\Models\Village;
use App\Services\HouseService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function createUserWithPortalChamber(string $furnitureKey = 'basic_portal', int $gold = 100000, int $energy = 100): array
{
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => $gold,
        'energy' => $energy,
        'title_tier' => 4,
        'current_kingdom_id' => $kingdom->id,
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 60, 'xp' => 0]);

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'Test House',
        'tier' => 'manor',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $portalRoom = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'portal_chamber',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    // Build portal furniture at slot 1
    HouseFurniture::create([
        'house_room_id' => $portalRoom->id,
        'hotspot_slug' => 'portal_1',
        'furniture_key' => $furnitureKey,
    ]);

    return ['user' => $user, 'house' => $house, 'room' => $portalRoom, 'kingdom' => $kingdom];
}

test('can set portal destination with valid slot and destination', function () {
    $data = createUserWithPortalChamber();
    $village = Village::factory()->create();

    $service = app(HouseService::class);
    $result = $service->setPortalDestination($data['user'], 1, 'village', $village->id);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain($village->name);

    $data['user']->refresh();
    $setCost = ConstructionConfig::PORTAL_CONFIG['basic_portal']['set_cost'];
    expect($data['user']->gold)->toBe(100000 - $setCost);
});

test('cannot set portal without portal chamber room built', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 100000,
        'title_tier' => 4,
        'current_kingdom_id' => $kingdom->id,
    ]);

    PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'Test House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $village = Village::factory()->create();

    $service = app(HouseService::class);
    $result = $service->setPortalDestination($user, 1, 'village', $village->id);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Portal Chamber');
});

test('cannot set portal without furniture at that slot', function () {
    $data = createUserWithPortalChamber();
    $village = Village::factory()->create();

    // Slot 2 has no furniture
    $service = app(HouseService::class);
    $result = $service->setPortalDestination($data['user'], 2, 'village', $village->id);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('No portal built');
});

test('setting portal charges gold', function () {
    $data = createUserWithPortalChamber('basic_portal', 10000);
    $village = Village::factory()->create();

    $service = app(HouseService::class);
    $result = $service->setPortalDestination($data['user'], 1, 'village', $village->id);

    expect($result['success'])->toBeTrue();

    $data['user']->refresh();
    expect($data['user']->gold)->toBe(10000 - ConstructionConfig::PORTAL_CONFIG['basic_portal']['set_cost']);
});

test('cannot set portal without enough gold', function () {
    $data = createUserWithPortalChamber('basic_portal', 100);
    $village = Village::factory()->create();

    $service = app(HouseService::class);
    $result = $service->setPortalDestination($data['user'], 1, 'village', $village->id);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Not enough gold');
});

test('can teleport to configured portal destination', function () {
    $data = createUserWithPortalChamber();
    $village = Village::factory()->create();

    $service = app(HouseService::class);
    $service->setPortalDestination($data['user'], 1, 'village', $village->id);
    $data['user']->refresh();

    $result = $service->teleportFromPortal($data['user'], 1);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain($village->name);

    $data['user']->refresh();
    expect($data['user']->current_location_type)->toBe('village');
    expect($data['user']->current_location_id)->toBe($village->id);
});

test('teleport is instant with no travel timer', function () {
    $data = createUserWithPortalChamber();
    $village = Village::factory()->create();

    $service = app(HouseService::class);
    $service->setPortalDestination($data['user'], 1, 'village', $village->id);
    $data['user']->refresh();

    $service->teleportFromPortal($data['user'], 1);

    $data['user']->refresh();
    expect($data['user']->is_traveling)->toBeFalsy();
    expect($data['user']->current_location_type)->toBe('village');
});

test('teleport charges energy', function () {
    $data = createUserWithPortalChamber('basic_portal', 100000, 50);
    $village = Village::factory()->create();

    $service = app(HouseService::class);
    $service->setPortalDestination($data['user'], 1, 'village', $village->id);
    $data['user']->refresh();

    $energyBefore = $data['user']->energy;
    $service->teleportFromPortal($data['user'], 1);

    $data['user']->refresh();
    expect($data['user']->energy)->toBe($energyBefore - 5);
});

test('cannot teleport when traveling', function () {
    $data = createUserWithPortalChamber();
    $village = Village::factory()->create();

    $service = app(HouseService::class);
    $service->setPortalDestination($data['user'], 1, 'village', $village->id);

    $data['user']->is_traveling = true;
    $data['user']->travel_arrives_at = now()->addMinutes(5);
    $data['user']->save();

    $result = $service->teleportFromPortal($data['user'], 1);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('traveling');
});

test('cannot teleport when in infirmary', function () {
    $data = createUserWithPortalChamber();
    $village = Village::factory()->create();

    $service = app(HouseService::class);
    $service->setPortalDestination($data['user'], 1, 'village', $village->id);

    $data['user']->is_in_infirmary = true;
    $data['user']->infirmary_heals_at = now()->addMinutes(5);
    $data['user']->save();

    $result = $service->teleportFromPortal($data['user'], 1);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('infirmary');
});

test('cannot teleport to unconfigured portal slot', function () {
    $data = createUserWithPortalChamber();

    // Slot 1 has furniture but no destination configured
    $service = app(HouseService::class);
    $result = $service->teleportFromPortal($data['user'], 1);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('no destination');
});

test('enhanced portal has lower set cost', function () {
    $data = createUserWithPortalChamber('enhanced_portal', 100000);
    $village = Village::factory()->create();

    $service = app(HouseService::class);
    $result = $service->setPortalDestination($data['user'], 1, 'village', $village->id);

    expect($result['success'])->toBeTrue();

    $data['user']->refresh();
    $enhancedCost = ConstructionConfig::PORTAL_CONFIG['enhanced_portal']['set_cost'];
    expect($data['user']->gold)->toBe(100000 - $enhancedCost);
    expect($enhancedCost)->toBeLessThan(ConstructionConfig::PORTAL_CONFIG['basic_portal']['set_cost']);
});

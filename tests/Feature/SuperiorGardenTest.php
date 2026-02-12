<?php

use App\Config\ConstructionConfig;
use App\Models\DiseaseInfection;
use App\Models\DiseaseType;
use App\Models\HouseFurniture;
use App\Models\HouseRoom;
use App\Models\Kingdom;
use App\Models\PlayerHouse;
use App\Models\PlayerSkill;
use App\Models\User;
use App\Services\HouseService;

function createHouseWithPool(User $user, Kingdom $kingdom, string $poolKey = 'restoration_pool'): array
{
    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'estate',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
        'location_type' => 'kingdom',
        'location_id' => $kingdom->id,
    ]);

    $room = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'superior_garden',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    $furniture = HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'pool',
        'furniture_key' => $poolKey,
    ]);

    return ['house' => $house, 'room' => $room, 'furniture' => $furniture];
}

// ===== Pool Data Tests =====

test('pool data returns correct capabilities for restoration pool', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['current_kingdom_id' => $kingdom->id]);

    createHouseWithPool($user, $kingdom, 'restoration_pool');

    $service = app(HouseService::class);
    $data = $service->getPoolData($user);

    expect($data)->not->toBeNull();
    expect($data['pool_name'])->toBe('Restoration Pool');
    expect($data['restore_hp'])->toBeTrue();
    expect($data['restore_energy'])->toBeFalse();
    expect($data['cure_disease'])->toBeFalse();
    expect($data['restore_all'])->toBeFalse();
});

test('pool data returns correct capabilities for revitalisation pool', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['current_kingdom_id' => $kingdom->id]);

    createHouseWithPool($user, $kingdom, 'revitalisation_pool');

    $service = app(HouseService::class);
    $data = $service->getPoolData($user);

    expect($data)->not->toBeNull();
    expect($data['pool_name'])->toBe('Revitalisation Pool');
    expect($data['restore_hp'])->toBeTrue();
    expect($data['restore_energy'])->toBeTrue();
    expect($data['cure_disease'])->toBeFalse();
});

test('pool data returns correct capabilities for rejuvenation pool', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['current_kingdom_id' => $kingdom->id]);

    createHouseWithPool($user, $kingdom, 'rejuvenation_pool');

    $service = app(HouseService::class);
    $data = $service->getPoolData($user);

    expect($data)->not->toBeNull();
    expect($data['restore_hp'])->toBeTrue();
    expect($data['restore_energy'])->toBeTrue();
    expect($data['cure_disease'])->toBeTrue();
    expect($data['restore_all'])->toBeFalse();
});

test('pool data returns correct capabilities for ornate rejuvenation pool', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['current_kingdom_id' => $kingdom->id]);

    createHouseWithPool($user, $kingdom, 'ornate_rejuvenation_pool');

    $service = app(HouseService::class);
    $data = $service->getPoolData($user);

    expect($data)->not->toBeNull();
    expect($data['restore_hp'])->toBeTrue();
    expect($data['restore_energy'])->toBeTrue();
    expect($data['cure_disease'])->toBeTrue();
    expect($data['restore_all'])->toBeTrue();
});

test('pool data returns null without superior garden', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['current_kingdom_id' => $kingdom->id]);

    PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'estate',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
        'location_type' => 'kingdom',
        'location_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    expect($service->getPoolData($user))->toBeNull();
});

// ===== Pool Usage Tests =====

test('restoration pool restores hp to full', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['current_kingdom_id' => $kingdom->id]);

    // max_hp is derived from hitpoints skill level
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'hitpoints', 'level' => 100, 'xp' => 0]);
    $user->update(['hp' => 50]);

    createHouseWithPool($user, $kingdom, 'restoration_pool');

    $service = app(HouseService::class);
    $result = $service->usePool($user);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('Restoration Pool');

    $user->refresh();
    expect($user->hp)->toBe(100);
});

test('revitalisation pool restores hp and energy', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 30,
        'max_energy' => 300,
        'current_kingdom_id' => $kingdom->id,
    ]);

    // max_hp is derived from hitpoints skill level
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'hitpoints', 'level' => 100, 'xp' => 0]);
    $user->update(['hp' => 50]);

    createHouseWithPool($user, $kingdom, 'revitalisation_pool');

    $service = app(HouseService::class);
    $result = $service->usePool($user);

    expect($result['success'])->toBeTrue();

    $user->refresh();
    expect($user->hp)->toBe(100);
    expect($user->energy)->toBe(300);
});

test('rejuvenation pool cures disease', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['current_kingdom_id' => $kingdom->id]);

    // max_hp is derived from hitpoints skill level
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'hitpoints', 'level' => 100, 'xp' => 0]);
    $user->update(['hp' => 50]);

    createHouseWithPool($user, $kingdom, 'rejuvenation_pool');

    // Create a disease type and infect the user
    $diseaseType = DiseaseType::create([
        'name' => 'Test Plague',
        'slug' => 'test_plague',
        'description' => 'A test disease',
        'severity' => 'minor',
        'base_duration_days' => 7,
    ]);
    DiseaseInfection::create([
        'disease_type_id' => $diseaseType->id,
        'user_id' => $user->id,
        'status' => DiseaseInfection::STATUS_SYMPTOMATIC,
        'severity_modifier' => 0,
        'infected_at' => now()->subDays(2),
    ]);

    $service = app(HouseService::class);
    $result = $service->usePool($user);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('cured');

    // Verify infection is resolved
    $activeInfections = DiseaseInfection::where('user_id', $user->id)->active()->count();
    expect($activeInfections)->toBe(0);
});

test('pool cooldown prevents spam', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['current_kingdom_id' => $kingdom->id]);

    // max_hp is derived from hitpoints skill level
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'hitpoints', 'level' => 100, 'xp' => 0]);
    $user->update(['hp' => 50]);

    createHouseWithPool($user, $kingdom, 'restoration_pool');

    $service = app(HouseService::class);

    // First use should work
    $result = $service->usePool($user);
    expect($result['success'])->toBeTrue();

    // Drop HP again
    $user->refresh();
    $user->update(['hp' => 50]);

    // Second use immediately should fail
    $result2 = $service->usePool($user->fresh());
    expect($result2['success'])->toBeFalse();
    expect($result2['message'])->toContain('wait');
});

test('pool unavailable when house condition is low', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['current_kingdom_id' => $kingdom->id]);

    // max_hp is derived from hitpoints skill level
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'hitpoints', 'level' => 100, 'xp' => 0]);
    $user->update(['hp' => 50]);

    $data = createHouseWithPool($user, $kingdom, 'restoration_pool');
    $data['house']->update(['condition' => 30]);

    $service = app(HouseService::class);
    $result = $service->usePool($user);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('condition');
});

test('pool unavailable when already at full stats', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['current_kingdom_id' => $kingdom->id]);

    createHouseWithPool($user, $kingdom, 'restoration_pool');

    $service = app(HouseService::class);
    $result = $service->usePool($user);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('full health');
});

test('cannot use pool while traveling', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'is_traveling' => true,
        'travel_arrives_at' => now()->addMinutes(5),
        'current_kingdom_id' => $kingdom->id,
    ]);

    // max_hp is derived from hitpoints skill level
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'hitpoints', 'level' => 100, 'xp' => 0]);
    $user->update(['hp' => 50]);

    createHouseWithPool($user, $kingdom, 'restoration_pool');

    $service = app(HouseService::class);
    $result = $service->usePool($user);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('traveling');
});

test('cannot use pool while in infirmary', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'is_in_infirmary' => true,
        'infirmary_heals_at' => now()->addMinutes(5),
        'current_kingdom_id' => $kingdom->id,
    ]);

    // max_hp is derived from hitpoints skill level
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'hitpoints', 'level' => 100, 'xp' => 0]);
    $user->update(['hp' => 50]);

    createHouseWithPool($user, $kingdom, 'restoration_pool');

    $service = app(HouseService::class);
    $result = $service->usePool($user);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('infirmary');
});

// ===== Config Tests =====

test('superior garden config has correct room definition', function () {
    $garden = ConstructionConfig::ROOMS['superior_garden'];

    expect($garden['name'])->toBe('Superior Garden');
    expect($garden['level'])->toBe(60);
    expect($garden['cost'])->toBe(300000);
    expect($garden['hotspots'])->toHaveKeys(['pool', 'fence', 'tree', 'fountain']);
});

test('pool route exists', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['current_kingdom_id' => $kingdom->id]);

    createHouseWithPool($user, $kingdom, 'restoration_pool');

    $this->actingAs($user)
        ->post('/house/pool')
        ->assertRedirect();
});

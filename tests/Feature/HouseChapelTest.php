<?php

use App\Models\HouseFurniture;
use App\Models\HouseRoom;
use App\Models\Kingdom;
use App\Models\PlayerHouse;
use App\Models\PlayerSkill;
use App\Models\Religion;
use App\Models\ReligionMember;
use App\Models\User;
use App\Services\HouseService;

function createHouseWithChapel(User $user, Kingdom $kingdom, string $altarKey = 'wooden_altar'): array
{
    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
        'location_type' => 'kingdom',
        'location_id' => $kingdom->id,
    ]);

    $room = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'chapel',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    $furniture = HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'altar',
        'furniture_key' => $altarKey,
    ]);

    return ['house' => $house, 'room' => $room, 'furniture' => $furniture];
}

test('can pray at home with chapel and altar', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 100,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'prayer', 'level' => 1, 'xp' => 0]);

    createHouseWithChapel($user, $kingdom);

    $religion = Religion::create([
        'name' => 'Test Religion',
        'description' => 'A test religion',
        'icon' => 'cross',
        'color' => '#ffffff',
        'type' => 'cult',
        'founder_id' => $user->id,
        'is_public' => true,
        'is_active' => true,
    ]);

    ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'follower',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    $service = app(HouseService::class);
    $result = $service->prayAtHome($user);

    expect($result['success'])->toBeTrue();
    expect($result['devotion_gained'])->toBe(10);
    expect($result['prayer_xp_gained'])->toBeGreaterThan(0);
});

test('cannot pray without altar', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 100,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
    ]);

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
        'location_type' => 'kingdom',
        'location_id' => $kingdom->id,
    ]);

    HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'chapel',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    $service = app(HouseService::class);
    $result = $service->prayAtHome($user);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('altar');
});

test('cannot pray without religion membership', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 100,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
    ]);

    createHouseWithChapel($user, $kingdom);

    $service = app(HouseService::class);
    $result = $service->prayAtHome($user);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('religion');
});

test('prayer awards devotion and xp with furniture bonus', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 100,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'prayer', 'level' => 1, 'xp' => 0]);

    $chapelData = createHouseWithChapel($user, $kingdom);

    // Add incense burner for extra prayer XP bonus
    HouseFurniture::create([
        'house_room_id' => $chapelData['room']->id,
        'hotspot_slug' => 'incense_burner',
        'furniture_key' => 'wooden_burner',
    ]);

    $religion = Religion::create([
        'name' => 'Test Religion',
        'description' => 'A test religion',
        'icon' => 'cross',
        'color' => '#ffffff',
        'type' => 'cult',
        'founder_id' => $user->id,
        'is_public' => true,
        'is_active' => true,
    ]);

    ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'follower',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    $service = app(HouseService::class);
    $result = $service->prayAtHome($user);

    expect($result['success'])->toBeTrue();
    expect($result['devotion_gained'])->toBe(10);
    // wooden_altar (50%) + wooden_burner (25%) = 75% bonus
    // base 5 XP * 1.75 = ceil(8.75) = 9
    expect($result['prayer_xp_gained'])->toBe(9);
});

test('prayer respects 5 minute cooldown', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 100,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'prayer', 'level' => 1, 'xp' => 0]);

    createHouseWithChapel($user, $kingdom);

    $religion = Religion::create([
        'name' => 'Test Religion',
        'description' => 'A test religion',
        'icon' => 'cross',
        'color' => '#ffffff',
        'type' => 'cult',
        'founder_id' => $user->id,
        'is_public' => true,
        'is_active' => true,
    ]);

    ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'follower',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    $service = app(HouseService::class);

    // First prayer should work
    $result1 = $service->prayAtHome($user);
    expect($result1['success'])->toBeTrue();

    // Second prayer immediately should fail (cooldown)
    $result2 = $service->prayAtHome($user->fresh());
    expect($result2['success'])->toBeFalse();
    expect($result2['message'])->toContain('pray again');
});

test('prayer costs energy', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 3, // Not enough energy (need 5)
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
    ]);

    createHouseWithChapel($user, $kingdom);

    $religion = Religion::create([
        'name' => 'Test Religion',
        'description' => 'A test religion',
        'icon' => 'cross',
        'color' => '#ffffff',
        'type' => 'cult',
        'founder_id' => $user->id,
        'is_public' => true,
        'is_active' => true,
    ]);

    ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'follower',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    $service = app(HouseService::class);
    $result = $service->prayAtHome($user);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('energy');
});

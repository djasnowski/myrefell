<?php

use App\Models\HouseFurniture;
use App\Models\HouseRoom;
use App\Models\Kingdom;
use App\Models\PlayerHouse;
use App\Models\PlayerSkill;
use App\Models\User;
use App\Services\HouseBuffService;

beforeEach(function () {
    $this->kingdom = Kingdom::factory()->create();
    $this->user = User::factory()->create([
        'gold' => 500000,
        'title_tier' => 3,
        'current_kingdom_id' => $this->kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $this->user->id, 'skill_name' => 'construction', 'level' => 60, 'xp' => 0]);
    $this->user->load('skills');

    $this->house = PlayerHouse::create([
        'player_id' => $this->user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $this->kingdom->id,
    ]);
});

test('house with no furniture returns empty effects', function () {
    $service = app(HouseBuffService::class);
    $effects = $service->getHouseEffects($this->user);

    expect($effects)->toBe([]);
});

test('bedroom bed provides energy_regen_bonus', function () {
    $room = HouseRoom::create([
        'player_house_id' => $this->house->id,
        'room_type' => 'bedroom',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'bed',
        'furniture_key' => 'wooden_bed',
    ]);

    $service = app(HouseBuffService::class);
    $effects = $service->getHouseEffects($this->user);

    expect($effects)->toHaveKey('energy_regen_bonus');
    expect($effects['energy_regen_bonus'])->toBe(10);
});

test('hearth fireplace provides max_hp_bonus', function () {
    $room = HouseRoom::create([
        'player_house_id' => $this->house->id,
        'room_type' => 'hearth_room',
        'grid_x' => 1,
        'grid_y' => 0,
    ]);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'fireplace',
        'furniture_key' => 'stone_fireplace',
    ]);

    $service = app(HouseBuffService::class);
    $effects = $service->getHouseEffects($this->user);

    expect($effects)->toHaveKey('max_hp_bonus');
    expect($effects['max_hp_bonus'])->toBe(3);
});

test('chapel altar provides prayer_xp_bonus', function () {
    $room = HouseRoom::create([
        'player_house_id' => $this->house->id,
        'room_type' => 'chapel',
        'grid_x' => 2,
        'grid_y' => 0,
    ]);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'altar',
        'furniture_key' => 'wooden_altar',
    ]);

    $service = app(HouseBuffService::class);
    $effects = $service->getHouseEffects($this->user);

    expect($effects)->toHaveKey('prayer_xp_bonus');
    expect($effects['prayer_xp_bonus'])->toBe(50);
});

test('multiple furniture effects aggregate correctly', function () {
    // Chapel with altar + incense burner (both give prayer_xp_bonus)
    $room = HouseRoom::create([
        'player_house_id' => $this->house->id,
        'room_type' => 'chapel',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'altar',
        'furniture_key' => 'wooden_altar', // prayer_xp_bonus: 50
    ]);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'incense_burner',
        'furniture_key' => 'wooden_burner', // prayer_xp_bonus: 25
    ]);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'icon',
        'furniture_key' => 'holy_symbol', // prayer_bonus: 1
    ]);

    $service = app(HouseBuffService::class);
    $effects = $service->getHouseEffects($this->user);

    expect($effects['prayer_xp_bonus'])->toBe(75); // 50 + 25
    expect($effects['prayer_bonus'])->toBe(1);
});

test('workshop whetstone provides attack_bonus', function () {
    $room = HouseRoom::create([
        'player_house_id' => $this->house->id,
        'room_type' => 'workshop',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'whetstone',
        'furniture_key' => 'rough_whetstone',
    ]);

    $service = app(HouseBuffService::class);
    $effects = $service->getHouseEffects($this->user);

    expect($effects)->toHaveKey('attack_bonus');
    expect($effects['attack_bonus'])->toBe(1);
});

test('getHouseBuffSources returns formatted sources', function () {
    $room = HouseRoom::create([
        'player_house_id' => $this->house->id,
        'room_type' => 'workshop',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'workbench',
        'furniture_key' => 'wooden_workbench',
    ]);

    $service = app(HouseBuffService::class);
    $sources = $service->getHouseBuffSources($this->user);

    expect($sources)->toHaveCount(1);
    expect($sources[0]['source'])->toBe('Workshop - Wooden Workbench');
    expect($sources[0]['effect_key'])->toBe('crafting_xp_bonus');
    expect($sources[0]['value'])->toBe(3);
});

test('user without house returns empty sources', function () {
    $otherUser = User::factory()->create();

    $service = app(HouseBuffService::class);
    expect($service->getHouseEffects($otherUser))->toBe([]);
    expect($service->getHouseBuffSources($otherUser))->toBe([]);
});

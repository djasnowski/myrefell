<?php

use App\Models\HouseFurniture;
use App\Models\HouseRoom;
use App\Models\Kingdom;
use App\Models\PlayerHouse;
use App\Models\PlayerSkill;
use App\Models\User;
use App\Services\SkillBonusService;

beforeEach(function () {
    $this->kingdom = Kingdom::factory()->create();
    $this->user = User::factory()->create([
        'gold' => 500000,
        'title_tier' => 3,
        'current_kingdom_id' => $this->kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $this->user->id, 'skill_name' => 'construction', 'level' => 60, 'xp' => 0]);
    $this->user->load('skills');
});

test('house buffs appear in getSkillBonuses output', function () {
    $house = PlayerHouse::create([
        'player_id' => $this->user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $this->kingdom->id,
    ]);

    $room = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'workshop',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'whetstone',
        'furniture_key' => 'rough_whetstone', // attack_bonus: 1
    ]);

    $service = app(SkillBonusService::class);
    $bonuses = $service->getSkillBonuses($this->user);

    expect($bonuses['attack']['flat_bonus'])->toBe(1);
    expect($bonuses['attack']['sources'])->not->toBeEmpty();
    expect($bonuses['attack']['sources'][0]['type'])->toBe('house');
});

test('house buffs appear in getAllActiveBuffs output', function () {
    $house = PlayerHouse::create([
        'player_id' => $this->user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $this->kingdom->id,
    ]);

    $room = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'forge',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'anvil',
        'furniture_key' => 'iron_anvil', // smithing_xp_bonus: 3
    ]);

    $service = app(SkillBonusService::class);
    $buffs = $service->getAllActiveBuffs($this->user);

    $houseBuff = collect($buffs)->firstWhere('type', 'house');
    expect($houseBuff)->not->toBeNull();
    expect($houseBuff['name'])->toBe('House Furniture');
    expect($houseBuff['effects'])->not->toBeEmpty();
});

test('house buff type is house in sources', function () {
    $house = PlayerHouse::create([
        'player_id' => $this->user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $this->kingdom->id,
    ]);

    $room = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'hearth_room',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'fireplace',
        'furniture_key' => 'marble_fireplace', // max_hp_bonus: 8
    ]);

    $service = app(SkillBonusService::class);
    $bonuses = $service->getSkillBonuses($this->user);

    $hpSources = $bonuses['hitpoints']['sources'];
    expect($hpSources)->not->toBeEmpty();
    expect($hpSources[0]['type'])->toBe('house');
    expect($hpSources[0]['value'])->toBe(8);
    expect($hpSources[0]['source'])->toContain('Hearth Room');
});

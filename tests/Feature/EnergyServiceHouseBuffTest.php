<?php

use App\Models\HouseFurniture;
use App\Models\HouseRoom;
use App\Models\Kingdom;
use App\Models\PlayerHouse;
use App\Models\PlayerSkill;
use App\Models\User;
use App\Services\EnergyService;

beforeEach(function () {
    $this->kingdom = Kingdom::factory()->create();
    $this->user = User::factory()->create([
        'gold' => 500000,
        'title_tier' => 3,
        'current_kingdom_id' => $this->kingdom->id,
        'energy' => 50,
        'max_energy' => 100,
    ]);
    PlayerSkill::create(['player_id' => $this->user->id, 'skill_name' => 'construction', 'level' => 20, 'xp' => 0]);
    $this->user->load('skills');
});

test('energy regen includes house bed bonus', function () {
    $house = PlayerHouse::create([
        'player_id' => $this->user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $this->kingdom->id,
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
        'furniture_key' => 'wooden_bed', // energy_regen_bonus: 10
    ]);

    $service = app(EnergyService::class);
    $gained = $service->regenerateEnergy($this->user);

    // Base 10 + 10% bonus = 11
    expect($gained)->toBe(11);
});

test('regen info displays house bonus source', function () {
    $house = PlayerHouse::create([
        'player_id' => $this->user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $this->kingdom->id,
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
        'furniture_key' => 'straw_bed', // energy_regen_bonus: 5
    ]);

    $service = app(EnergyService::class);
    $info = $service->getRegenInfo($this->user);

    $houseBonusSource = collect($info['regen_bonuses'])->firstWhere('source', 'House (Bed)');
    expect($houseBonusSource)->not->toBeNull();
    expect($houseBonusSource['amount'])->toBe('+5%');
});

test('energy regen without house has no house bonus', function () {
    $service = app(EnergyService::class);
    $gained = $service->regenerateEnergy($this->user);

    // Base 10, no bonuses
    expect($gained)->toBe(10);
});

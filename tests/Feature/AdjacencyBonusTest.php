<?php

use App\Models\HouseRoom;
use App\Models\Kingdom;
use App\Models\PlayerHouse;
use App\Models\PlayerSkill;
use App\Models\User;
use App\Services\HouseBuffService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function createHouseWithRooms(array $rooms): array
{
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 500000,
        'title_tier' => 4,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 50, 'xp' => 0]);

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'Test House',
        'tier' => 'manor',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $createdRooms = [];
    foreach ($rooms as $room) {
        $createdRooms[] = HouseRoom::create([
            'player_house_id' => $house->id,
            'room_type' => $room['type'],
            'grid_x' => $room['x'],
            'grid_y' => $room['y'],
        ]);
    }

    $house->load('rooms');

    return ['user' => $user, 'house' => $house, 'rooms' => $createdRooms];
}

test('no adjacency bonus when rooms are not adjacent', function () {
    $data = createHouseWithRooms([
        ['type' => 'kitchen', 'x' => 0, 'y' => 0],
        ['type' => 'dining_room', 'x' => 2, 'y' => 2],
    ]);

    $service = app(HouseBuffService::class);
    $bonuses = $service->getAdjacencyBonuses($data['house']);

    expect($bonuses)->toBeEmpty();
});

test('kitchen and dining room adjacency gives cooking xp bonus', function () {
    $data = createHouseWithRooms([
        ['type' => 'kitchen', 'x' => 0, 'y' => 0],
        ['type' => 'dining_room', 'x' => 1, 'y' => 0],
    ]);

    $service = app(HouseBuffService::class);
    $bonuses = $service->getAdjacencyBonuses($data['house']);

    expect($bonuses)->toHaveKey('cooking_xp_bonus');
    expect($bonuses['cooking_xp_bonus'])->toBe(3);
});

test('bedroom and hearth room adjacency gives energy regen bonus', function () {
    $data = createHouseWithRooms([
        ['type' => 'bedroom', 'x' => 1, 'y' => 0],
        ['type' => 'hearth_room', 'x' => 1, 'y' => 1],
    ]);

    $service = app(HouseBuffService::class);
    $bonuses = $service->getAdjacencyBonuses($data['house']);

    expect($bonuses)->toHaveKey('energy_regen_bonus');
    expect($bonuses['energy_regen_bonus'])->toBe(5);
});

test('adjacency bonus only counts once per pair', function () {
    // Place two kitchens and two dining rooms adjacent in multiple ways
    $data = createHouseWithRooms([
        ['type' => 'kitchen', 'x' => 0, 'y' => 0],
        ['type' => 'dining_room', 'x' => 1, 'y' => 0],
        ['type' => 'kitchen', 'x' => 2, 'y' => 0],
    ]);

    $service = app(HouseBuffService::class);
    $bonuses = $service->getAdjacencyBonuses($data['house']);

    // Should only count once even though kitchen at (2,0) is also adjacent to dining_room at (1,0)
    expect($bonuses)->toHaveKey('cooking_xp_bonus');
    expect($bonuses['cooking_xp_bonus'])->toBe(3);
});

test('adjacency bonus appears in getHouseEffects', function () {
    $data = createHouseWithRooms([
        ['type' => 'kitchen', 'x' => 0, 'y' => 0],
        ['type' => 'dining_room', 'x' => 0, 'y' => 1],
    ]);

    $service = app(HouseBuffService::class);
    $effects = $service->getHouseEffects($data['user']);

    expect($effects)->toHaveKey('cooking_xp_bonus');
    expect($effects['cooking_xp_bonus'])->toBe(3);
});

test('adjacency bonus appears in getHouseBuffSources with correct source name', function () {
    $data = createHouseWithRooms([
        ['type' => 'kitchen', 'x' => 0, 'y' => 0],
        ['type' => 'dining_room', 'x' => 1, 'y' => 0],
    ]);

    $service = app(HouseBuffService::class);
    $sources = $service->getHouseBuffSources($data['user']);

    $adjacencySources = array_filter($sources, fn ($s) => str_starts_with($s['source'], 'Adjacency:'));

    expect($adjacencySources)->toHaveCount(1);

    $source = array_values($adjacencySources)[0];
    expect($source['effect_key'])->toBe('cooking_xp_bonus');
    expect($source['value'])->toBe(3);
    expect($source['source'])->toContain('Kitchen + Dining Room');
});

test('removing a room removes its adjacency bonuses', function () {
    $data = createHouseWithRooms([
        ['type' => 'kitchen', 'x' => 0, 'y' => 0],
        ['type' => 'dining_room', 'x' => 1, 'y' => 0],
    ]);

    $service = app(HouseBuffService::class);
    $bonuses = $service->getAdjacencyBonuses($data['house']);
    expect($bonuses)->toHaveKey('cooking_xp_bonus');

    // Remove the dining room
    HouseRoom::where('player_house_id', $data['house']->id)
        ->where('room_type', 'dining_room')
        ->delete();

    $data['house']->load('rooms');

    // Fresh service instance to avoid caching
    $freshService = app(HouseBuffService::class);
    $bonuses = $freshService->getAdjacencyBonuses($data['house']);

    expect($bonuses)->toBeEmpty();
});

<?php

use App\Config\ConstructionConfig;
use App\Models\HouseFurniture;
use App\Models\HouseRoom;
use App\Models\Item;
use App\Models\Kingdom;
use App\Models\PlayerHouse;
use App\Models\User;
use App\Services\HouseService;
use App\Services\InventoryService;

function createHouseWithCellar(User $user, Kingdom $kingdom, string $cratesKey = 'wooden_crates'): array
{
    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'house',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
        'location_type' => 'kingdom',
        'location_id' => $kingdom->id,
    ]);

    $room = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'cellar',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    $furniture = HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'storage_crates',
        'furniture_key' => $cratesKey,
    ]);

    return ['house' => $house, 'room' => $room, 'furniture' => $furniture];
}

test('cellar wooden crates add 50 storage slots', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['current_kingdom_id' => $kingdom->id]);

    $data = createHouseWithCellar($user, $kingdom, 'wooden_crates');
    $house = $data['house'];

    // House tier 'house' has 200 base storage + 50 from wooden crates
    expect($house->getStorageCapacity())->toBe(250);
});

test('cellar oak crates add 100 storage slots', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['current_kingdom_id' => $kingdom->id]);

    $data = createHouseWithCellar($user, $kingdom, 'oak_crates');
    $house = $data['house'];

    expect($house->getStorageCapacity())->toBe(300);
});

test('cellar reinforced crates add 150 storage slots', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['current_kingdom_id' => $kingdom->id]);

    $data = createHouseWithCellar($user, $kingdom, 'reinforced_crates');
    $house = $data['house'];

    expect($house->getStorageCapacity())->toBe(350);
});

test('cellar fortified crates add 200 storage slots', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['current_kingdom_id' => $kingdom->id]);

    $data = createHouseWithCellar($user, $kingdom, 'fortified_crates');
    $house = $data['house'];

    expect($house->getStorageCapacity())->toBe(400);
});

test('cellar without storage crates does not add bonus', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['current_kingdom_id' => $kingdom->id]);

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'house',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
        'location_type' => 'kingdom',
        'location_id' => $kingdom->id,
    ]);

    HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'cellar',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    // No furniture, so just base storage
    expect($house->getStorageCapacity())->toBe(200);
});

test('can deposit items up to expanded capacity', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'current_kingdom_id' => $kingdom->id,
        'gold' => 100000,
    ]);

    $data = createHouseWithCellar($user, $kingdom, 'wooden_crates');
    $house = $data['house'];

    $item = Item::firstOrCreate(
        ['name' => 'Test Stone'],
        ['type' => 'resource', 'subtype' => 'ore', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 1]
    );

    // Give user the item
    app(InventoryService::class)->addItem($user, $item, 10);

    $service = app(HouseService::class);
    $result = $service->depositItem($user, 'Test Stone', 10);

    expect($result['success'])->toBeTrue();
});

test('cellar config has correct room definition', function () {
    $cellar = ConstructionConfig::ROOMS['cellar'];

    expect($cellar['name'])->toBe('Cellar');
    expect($cellar['level'])->toBe(25);
    expect($cellar['cost'])->toBe(40000);
    expect($cellar['hotspots'])->toHaveKeys(['storage_crates', 'shelving', 'lighting']);
});

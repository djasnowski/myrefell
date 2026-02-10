<?php

use App\Models\HouseFurniture;
use App\Models\HouseRoom;
use App\Models\Item;
use App\Models\Kingdom;
use App\Models\PlayerHouse;
use App\Models\PlayerSkill;
use App\Models\User;
use App\Services\HouseService;
use App\Services\InventoryService;

function createHouseWithWorkshop(User $user, Kingdom $kingdom, string $workbenchKey = 'wooden_workbench'): array
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
        'room_type' => 'workshop',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    $furniture = HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'workbench',
        'furniture_key' => $workbenchKey,
    ]);

    return ['house' => $house, 'room' => $room, 'furniture' => $furniture];
}

function createHouseWithForge(User $user, Kingdom $kingdom, string $anvilKey = 'iron_anvil'): array
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
        'room_type' => 'forge',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    $furniture = HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'anvil',
        'furniture_key' => $anvilKey,
    ]);

    return ['house' => $house, 'room' => $room, 'furniture' => $furniture];
}

beforeEach(function () {
    Item::firstOrCreate(['name' => 'Flax'], ['type' => 'resource', 'subtype' => 'material', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 2]);
    Item::firstOrCreate(['name' => 'Thread'], ['type' => 'misc', 'subtype' => 'material', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 5]);
    Item::firstOrCreate(['name' => 'Copper Ore'], ['type' => 'resource', 'subtype' => 'ore', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 5]);
    Item::firstOrCreate(['name' => 'Tin Ore'], ['type' => 'resource', 'subtype' => 'ore', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 5]);
    Item::firstOrCreate(['name' => 'Bronze Bar'], ['type' => 'resource', 'subtype' => 'bar', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 10]);
});

// ===== Workshop Tests =====

test('can craft at workshop with workbench', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 100,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'crafting', 'level' => 1, 'xp' => 0]);

    createHouseWithWorkshop($user, $kingdom);

    $flax = Item::where('name', 'Flax')->first();
    app(InventoryService::class)->addItem($user, $flax, 1);

    $service = app(HouseService::class);
    $result = $service->craftAtWorkshop($user, 'thread');

    expect($result['success'])->toBeTrue();
    expect($result['item']['name'])->toBe('Thread');
    expect($result['xp_awarded'])->toBeGreaterThan(0);
});

test('cannot craft at workshop without workbench', function () {
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
        'room_type' => 'workshop',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    $service = app(HouseService::class);
    $result = $service->craftAtWorkshop($user, 'thread');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('workbench');
});

test('workshop only allows crafting categories', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 100,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'smithing', 'level' => 1, 'xp' => 0]);

    createHouseWithWorkshop($user, $kingdom);

    // bronze_bar is a smelting recipe - should not work at workshop
    $copperOre = Item::where('name', 'Copper Ore')->first();
    $tinOre = Item::where('name', 'Tin Ore')->first();
    app(InventoryService::class)->addItem($user, $copperOre, 1);
    app(InventoryService::class)->addItem($user, $tinOre, 1);

    $service = app(HouseService::class);
    $result = $service->craftAtWorkshop($user, 'bronze_bar');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('cannot be crafted');
});

test('workshop applies house xp bonus', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 100,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'crafting', 'level' => 1, 'xp' => 0]);

    // oak_workbench has 5% crafting_xp_bonus
    createHouseWithWorkshop($user, $kingdom, 'oak_workbench');

    $flax = Item::where('name', 'Flax')->first();
    app(InventoryService::class)->addItem($user, $flax, 1);

    $service = app(HouseService::class);
    $result = $service->craftAtWorkshop($user, 'thread');

    expect($result['success'])->toBeTrue();
    // Thread recipe gives 5 base XP, with 5% bonus = ceil(5 * 1.05) = 6
    expect($result['xp_awarded'])->toBe(6);
});

// ===== Forge Tests =====

test('can smelt at forge with anvil', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 100,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'smithing', 'level' => 1, 'xp' => 0]);

    createHouseWithForge($user, $kingdom);

    $copperOre = Item::where('name', 'Copper Ore')->first();
    $tinOre = Item::where('name', 'Tin Ore')->first();
    app(InventoryService::class)->addItem($user, $copperOre, 1);
    app(InventoryService::class)->addItem($user, $tinOre, 1);

    $service = app(HouseService::class);
    $result = $service->craftAtForge($user, 'bronze_bar');

    expect($result['success'])->toBeTrue();
    expect($result['item']['name'])->toBe('Bronze Bar');
});

test('cannot smelt at forge without anvil', function () {
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
        'room_type' => 'forge',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    $service = app(HouseService::class);
    $result = $service->craftAtForge($user, 'bronze_bar');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('anvil');
});

test('forge only allows smelting and smithing categories', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 100,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'crafting', 'level' => 1, 'xp' => 0]);

    createHouseWithForge($user, $kingdom);

    // thread is a crafting recipe - should not work at forge
    $flax = Item::where('name', 'Flax')->first();
    app(InventoryService::class)->addItem($user, $flax, 1);

    $service = app(HouseService::class);
    $result = $service->craftAtForge($user, 'thread');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('cannot be crafted');
});

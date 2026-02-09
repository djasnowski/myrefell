<?php

use App\Models\HouseRoom;
use App\Models\Item;
use App\Models\Kingdom;
use App\Models\PlayerHouse;
use App\Models\PlayerSkill;
use App\Models\User;
use App\Services\HouseService;
use App\Services\InventoryService;

beforeEach(function () {
    // Create required items (firstOrCreate since migration may already seed them)
    Item::firstOrCreate(['name' => 'Plank'], ['type' => 'misc', 'subtype' => 'material', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 20]);
    Item::firstOrCreate(['name' => 'Nails'], ['type' => 'misc', 'subtype' => 'material', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 5]);
    Item::firstOrCreate(['name' => 'Wood'], ['type' => 'resource', 'subtype' => 'wood', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 2]);
});

test('can view house page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/house')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('House/Index'));
});

test('can purchase a cottage', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 50000,
        'title_tier' => 2,
        'current_kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->purchaseHouse($user);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('25,000');

    $user->refresh();
    expect($user->gold)->toBe(25000);
    expect(PlayerHouse::where('player_id', $user->id)->exists())->toBeTrue();
});

test('cannot purchase without enough gold', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 100,
        'title_tier' => 2,
        'current_kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->purchaseHouse($user);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Not enough gold');
});

test('cannot purchase without required title', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 50000,
        'title_tier' => 1,
        'current_kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->purchaseHouse($user);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('higher title');
});

test('cannot purchase second house', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 100000,
        'title_tier' => 2,
        'current_kingdom_id' => $kingdom->id,
    ]);

    PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->purchaseHouse($user);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('already own');
});

test('can build a room', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 50000,
        'title_tier' => 2,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 1, 'xp' => 0]);
    $user->load('skills');

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->buildRoom($user, 'parlour', 0, 0);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('Parlour');

    $user->refresh();
    expect($user->gold)->toBe(45000);
    expect(HouseRoom::where('player_house_id', $house->id)->count())->toBe(1);
});

test('cannot build room at occupied position', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 50000,
        'title_tier' => 2,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 1, 'xp' => 0]);
    $user->load('skills');

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'parlour',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    $service = app(HouseService::class);
    $result = $service->buildRoom($user, 'parlour', 0, 0);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('already exists');
});

test('cannot exceed max rooms', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 100000,
        'title_tier' => 2,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 10, 'xp' => 0]);
    $user->load('skills');

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    // Cottage has max 3 rooms
    HouseRoom::create(['player_house_id' => $house->id, 'room_type' => 'parlour', 'grid_x' => 0, 'grid_y' => 0]);
    HouseRoom::create(['player_house_id' => $house->id, 'room_type' => 'kitchen', 'grid_x' => 1, 'grid_y' => 0]);
    HouseRoom::create(['player_house_id' => $house->id, 'room_type' => 'bedroom', 'grid_x' => 2, 'grid_y' => 0]);

    $service = app(HouseService::class);
    $result = $service->buildRoom($user, 'parlour', 0, 1);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Maximum rooms');
});

test('can build furniture at hotspot', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 50000,
        'title_tier' => 2,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 1, 'xp' => 0]);
    $user->load('skills');

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $room = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'parlour',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    // Crude Chair needs 3 Planks + 2 Nails
    $inventoryService = app(InventoryService::class);
    $inventoryService->addItem($user, Item::where('name', 'Plank')->first(), 3);
    $inventoryService->addItem($user, Item::where('name', 'Nails')->first(), 2);

    $service = app(HouseService::class);
    $result = $service->buildFurniture($user, $room->id, 'chair', 'crude_chair');

    expect($result['success'])->toBeTrue();
    expect($result['xp_awarded'])->toBe(30);

    // Materials should be consumed
    expect($inventoryService->countItem($user, Item::where('name', 'Plank')->first()))->toBe(0);
    expect($inventoryService->countItem($user, Item::where('name', 'Nails')->first()))->toBe(0);
});

test('cannot build furniture without materials', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 50000,
        'title_tier' => 2,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 1, 'xp' => 0]);
    $user->load('skills');

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $room = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'parlour',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    $service = app(HouseService::class);
    $result = $service->buildFurniture($user, $room->id, 'chair', 'crude_chair');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Not enough');
});

test('can deposit and withdraw from storage', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 50000,
        'title_tier' => 2,
        'current_kingdom_id' => $kingdom->id,
    ]);

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $inventoryService = app(InventoryService::class);
    $wood = Item::where('name', 'Wood')->first();
    $inventoryService->addItem($user, $wood, 10);

    $service = app(HouseService::class);

    // Deposit
    $result = $service->depositItem($user, 'Wood', 5);
    expect($result['success'])->toBeTrue();
    expect($inventoryService->countItem($user, $wood))->toBe(5);
    expect($house->fresh()->getStorageUsed())->toBe(5);

    // Withdraw
    $result = $service->withdrawItem($user, 'Wood', 3);
    expect($result['success'])->toBeTrue();
    expect($inventoryService->countItem($user, $wood))->toBe(8);
    expect($house->fresh()->getStorageUsed())->toBe(2);
});

test('cannot deposit more than storage capacity', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 50000,
        'title_tier' => 2,
        'current_kingdom_id' => $kingdom->id,
    ]);

    PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $inventoryService = app(InventoryService::class);
    $wood = Item::where('name', 'Wood')->first();
    $inventoryService->addItem($user, $wood, 200);

    $service = app(HouseService::class);
    $result = $service->depositItem($user, 'Wood', 101);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Not enough storage');
});

test('post purchase route works', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 50000,
        'title_tier' => 2,
        'current_kingdom_id' => $kingdom->id,
    ]);

    $this->actingAs($user)
        ->post('/house/purchase')
        ->assertRedirect();

    expect(PlayerHouse::where('player_id', $user->id)->exists())->toBeTrue();
});

test('post build-room route works', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 50000,
        'title_tier' => 2,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 1, 'xp' => 0]);

    PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $this->actingAs($user)
        ->post('/house/build-room', [
            'room_type' => 'parlour',
            'grid_x' => 0,
            'grid_y' => 0,
        ])
        ->assertRedirect();
});

test('can upgrade cottage to house', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 200000,
        'title_tier' => 3,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 20, 'xp' => 0]);
    $user->load('skills');

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->upgradeHouse($user, 'house');

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('House');

    $user->refresh();
    // Cost is 100000 - 25000 = 75000
    expect($user->gold)->toBe(125000);

    $house->refresh();
    expect($house->tier)->toBe('house');
});

test('upgrade fails with insufficient gold', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 10000,
        'title_tier' => 3,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 20, 'xp' => 0]);
    $user->load('skills');

    PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->upgradeHouse($user, 'house');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Not enough gold');
});

test('upgrade fails with insufficient construction level', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 200000,
        'title_tier' => 3,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 5, 'xp' => 0]);
    $user->load('skills');

    PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->upgradeHouse($user, 'house');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Construction level');
});

test('upgrade fails with insufficient title tier', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 200000,
        'title_tier' => 2,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 20, 'xp' => 0]);
    $user->load('skills');

    PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->upgradeHouse($user, 'house');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('higher title');
});

test('cannot skip tiers when upgrading', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 1000000,
        'title_tier' => 4,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 50, 'xp' => 0]);
    $user->load('skills');

    PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->upgradeHouse($user, 'manor');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('next tier');
});

test('upgrade preserves existing rooms and furniture', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 200000,
        'title_tier' => 3,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 20, 'xp' => 0]);
    $user->load('skills');

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $room = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'parlour',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    \App\Models\HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'chair',
        'furniture_key' => 'crude_chair',
    ]);

    $service = app(HouseService::class);
    $result = $service->upgradeHouse($user, 'house');

    expect($result['success'])->toBeTrue();

    // Room and furniture should still exist
    expect(HouseRoom::where('player_house_id', $house->id)->count())->toBe(1);
    expect(\App\Models\HouseFurniture::where('house_room_id', $room->id)->count())->toBe(1);
});

test('post upgrade route works', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 200000,
        'title_tier' => 3,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 20, 'xp' => 0]);

    PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $this->actingAs($user)
        ->post('/house/upgrade', ['target_tier' => 'house'])
        ->assertRedirect();

    expect(PlayerHouse::where('player_id', $user->id)->first()->tier)->toBe('house');
});

test('can build a dining room', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 100000,
        'title_tier' => 2,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 15, 'xp' => 0]);
    $user->load('skills');

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->buildRoom($user, 'dining_room', 0, 0);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('Dining Room');
    expect(HouseRoom::where('player_house_id', $house->id)->where('room_type', 'dining_room')->exists())->toBeTrue();
});

test('can build servant quarters', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 200000,
        'title_tier' => 4,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 40, 'xp' => 0]);
    $user->load('skills');

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'manor',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->buildRoom($user, 'servant_quarters', 0, 0);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('Servant Quarters');
    expect(HouseRoom::where('player_house_id', $house->id)->where('room_type', 'servant_quarters')->exists())->toBeTrue();
});

test('can build portal chamber', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 500000,
        'title_tier' => 4,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 45, 'xp' => 0]);
    $user->load('skills');

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'manor',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->buildRoom($user, 'portal_chamber', 0, 0);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('Portal Chamber');
    expect(HouseRoom::where('player_house_id', $house->id)->where('room_type', 'portal_chamber')->exists())->toBeTrue();
});

test('can demolish furniture and recover materials', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 50000,
        'title_tier' => 2,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 1, 'xp' => 0]);
    $user->load('skills');

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $room = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'parlour',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    // Build a crude chair first (3 Planks + 2 Nails)
    $inventoryService = app(InventoryService::class);
    $inventoryService->addItem($user, Item::where('name', 'Plank')->first(), 3);
    $inventoryService->addItem($user, Item::where('name', 'Nails')->first(), 2);

    $service = app(HouseService::class);
    $service->buildFurniture($user, $room->id, 'chair', 'crude_chair');

    // Demolish — should get 50% back (1 Plank, 1 Nail)
    $result = $service->demolishFurniture($user, $room->id, 'chair');

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('Recovered');
    expect($inventoryService->countItem($user, Item::where('name', 'Plank')->first()))->toBe(1);
    expect($inventoryService->countItem($user, Item::where('name', 'Nails')->first()))->toBe(1);
});

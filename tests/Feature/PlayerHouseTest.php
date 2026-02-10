<?php

use App\Models\GardenPlot;
use App\Models\HouseFurniture;
use App\Models\HousePortal;
use App\Models\HouseRoom;
use App\Models\HouseServant;
use App\Models\HouseStorage;
use App\Models\HouseTrophy;
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
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'current_kingdom_id' => $kingdom->id,
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
    ]);

    PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
        'location_type' => 'kingdom',
        'location_id' => $kingdom->id,
    ]);

    $this->actingAs($user)
        ->get("/kingdoms/{$kingdom->id}/house")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('House/Index'));
});

test('can purchase a cottage', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 50000,
        'title_tier' => 2,
        'current_kingdom_id' => $kingdom->id,
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $result = $service->purchaseHouse($user);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('25,000');

    $user->refresh();
    expect($user->gold)->toBe(25000);

    $house = PlayerHouse::where('player_id', $user->id)->first();
    expect($house)->not->toBeNull();
    expect($house->location_type)->toBe('kingdom');
    expect($house->location_id)->toBe($kingdom->id);
});

test('cannot purchase without enough gold', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 100,
        'title_tier' => 2,
        'current_kingdom_id' => $kingdom->id,
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
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
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
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
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
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
    expect($house->fresh()->getStorageUsed())->toBe(1); // 1 slot used (Wood)

    // Withdraw
    $result = $service->withdrawItem($user, 'Wood', 3);
    expect($result['success'])->toBeTrue();
    expect($inventoryService->countItem($user, $wood))->toBe(8);
    expect($house->fresh()->getStorageUsed())->toBe(1); // Still 1 slot (2 Wood remaining)
});

test('cannot deposit when all storage slots are full', function () {
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

    // Fill all 100 slots with unique items
    $storageCapacity = $house->getStorageCapacity();
    for ($i = 0; $i < $storageCapacity; $i++) {
        $item = Item::create([
            'name' => "Test Item {$i}",
            'type' => 'misc',
            'subtype' => 'material',
            'rarity' => 'common',
            'stackable' => true,
            'max_stack' => 100,
            'base_value' => 1,
        ]);
        \App\Models\HouseStorage::create([
            'player_house_id' => $house->id,
            'item_id' => $item->id,
            'slot_number' => $i,
            'quantity' => 1,
        ]);
    }

    $inventoryService = app(InventoryService::class);
    $wood = Item::where('name', 'Wood')->first();
    $inventoryService->addItem($user, $wood, 10);

    $service = app(HouseService::class);
    $result = $service->depositItem($user, 'Wood', 1);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('No storage slots available');
});

test('can deposit any quantity into existing storage slot', function () {
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
    $inventoryService->addItem($user, $wood, 500);

    $service = app(HouseService::class);

    // First deposit creates the slot
    $result = $service->depositItem($user, 'Wood', 200);
    expect($result['success'])->toBeTrue();

    // Second deposit stacks — doesn't use a new slot
    $result = $service->depositItem($user, 'Wood', 200);
    expect($result['success'])->toBeTrue();

    // Storage used should be 1 slot, not 400
    expect($house->getStorageUsed())->toBe(1);
});

test('post purchase route works', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 50000,
        'title_tier' => 2,
        'current_kingdom_id' => $kingdom->id,
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
    ]);

    $this->actingAs($user)
        ->post('/house/purchase')
        ->assertRedirect("/kingdoms/{$kingdom->id}/house");

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

test('can move storage item to empty slot', function () {
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

    $wood = Item::where('name', 'Wood')->first();
    HouseStorage::create([
        'player_house_id' => $house->id,
        'item_id' => $wood->id,
        'slot_number' => 0,
        'quantity' => 5,
    ]);

    $this->actingAs($user)
        ->post('/house/move-storage-slot', ['from_slot' => 0, 'to_slot' => 3])
        ->assertRedirect();

    expect(HouseStorage::where('player_house_id', $house->id)->where('item_id', $wood->id)->first()->slot_number)->toBe(3);
});

test('can swap two storage items', function () {
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

    $wood = Item::where('name', 'Wood')->first();
    $plank = Item::where('name', 'Plank')->first();

    HouseStorage::create([
        'player_house_id' => $house->id,
        'item_id' => $wood->id,
        'slot_number' => 0,
        'quantity' => 5,
    ]);
    HouseStorage::create([
        'player_house_id' => $house->id,
        'item_id' => $plank->id,
        'slot_number' => 2,
        'quantity' => 10,
    ]);

    $this->actingAs($user)
        ->post('/house/move-storage-slot', ['from_slot' => 0, 'to_slot' => 2])
        ->assertRedirect();

    expect(HouseStorage::where('player_house_id', $house->id)->where('item_id', $wood->id)->first()->slot_number)->toBe(2);
    expect(HouseStorage::where('player_house_id', $house->id)->where('item_id', $plank->id)->first()->slot_number)->toBe(0);
});

test('cannot move storage slot beyond capacity', function () {
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

    $wood = Item::where('name', 'Wood')->first();
    HouseStorage::create([
        'player_house_id' => $house->id,
        'item_id' => $wood->id,
        'slot_number' => 0,
        'quantity' => 5,
    ]);

    // Cottage has 100 storage slots (0-99), so slot 100 is beyond capacity
    $this->actingAs($user)
        ->post('/house/move-storage-slot', ['from_slot' => 0, 'to_slot' => 100])
        ->assertRedirect();

    // Item should NOT have moved
    expect(HouseStorage::where('player_house_id', $house->id)->where('item_id', $wood->id)->first()->slot_number)->toBe(0);
});

test('withdraw does partial transfer when inventory nearly full', function () {
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

    // Create a non-stackable item (like a sword)
    $sword = Item::firstOrCreate(
        ['name' => 'Iron Sword'],
        ['type' => 'weapon', 'subtype' => 'sword', 'rarity' => 'common', 'stackable' => false, 'max_stack' => 1, 'base_value' => 100],
    );

    // Store 5 swords in house storage
    HouseStorage::create([
        'player_house_id' => $house->id,
        'item_id' => $sword->id,
        'slot_number' => 0,
        'quantity' => 5,
    ]);

    // Fill inventory to leave only 2 free slots
    $inventoryService = app(InventoryService::class);
    $maxSlots = \App\Models\PlayerInventory::MAX_SLOTS;
    for ($i = 0; $i < $maxSlots - 2; $i++) {
        $filler = Item::firstOrCreate(
            ['name' => "Filler Item {$i}"],
            ['type' => 'misc', 'subtype' => 'material', 'rarity' => 'common', 'stackable' => false, 'max_stack' => 1, 'base_value' => 1],
        );
        $inventoryService->addItem($user, $filler, 1);
    }

    expect($inventoryService->freeSlots($user))->toBe(2);

    // Try to withdraw all 5 swords — should only withdraw 2
    $service = app(HouseService::class);
    $result = $service->withdrawItem($user, 'Iron Sword', 5);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('2');
    expect($result['message'])->toContain('3 left in storage');

    // 2 swords in inventory, 3 remaining in storage
    expect($inventoryService->freeSlots($user))->toBe(0);
    expect(HouseStorage::where('player_house_id', $house->id)->where('item_id', $sword->id)->first()->quantity)->toBe(3);
});

test('withdraw fails when inventory is completely full', function () {
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

    $sword = Item::firstOrCreate(
        ['name' => 'Iron Sword'],
        ['type' => 'weapon', 'subtype' => 'sword', 'rarity' => 'common', 'stackable' => false, 'max_stack' => 1, 'base_value' => 100],
    );

    HouseStorage::create([
        'player_house_id' => $house->id,
        'item_id' => $sword->id,
        'slot_number' => 0,
        'quantity' => 3,
    ]);

    // Fill inventory completely
    $inventoryService = app(InventoryService::class);
    $maxSlots = \App\Models\PlayerInventory::MAX_SLOTS;
    for ($i = 0; $i < $maxSlots; $i++) {
        $filler = Item::firstOrCreate(
            ['name' => "Filler Item {$i}"],
            ['type' => 'misc', 'subtype' => 'material', 'rarity' => 'common', 'stackable' => false, 'max_stack' => 1, 'base_value' => 1],
        );
        $inventoryService->addItem($user, $filler, 1);
    }

    expect($inventoryService->freeSlots($user))->toBe(0);

    $service = app(HouseService::class);
    $result = $service->withdrawItem($user, 'Iron Sword', 3);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Not enough inventory space');

    // Storage unchanged
    expect(HouseStorage::where('player_house_id', $house->id)->where('item_id', $sword->id)->first()->quantity)->toBe(3);
});

test('can demolish empty room and get gold back', function () {
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

    // Build a parlour (costs 5,000 gold)
    $service = app(HouseService::class);
    $service->buildRoom($user, 'parlour', 0, 0);
    $user->refresh();
    $goldAfterBuild = $user->gold;

    $room = HouseRoom::where('player_house_id', $house->id)->first();

    // Demolish — should get 50% back (2,500 gold)
    $result = $service->demolishRoom($user, $room->id);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('2,500');

    $user->refresh();
    expect($user->gold)->toBe($goldAfterBuild + 2500);
    expect(HouseRoom::where('player_house_id', $house->id)->count())->toBe(0);
});

test('can demolish room with furniture and get gold plus materials back', function () {
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
    $inventoryService = app(InventoryService::class);

    // Build parlour
    $service->buildRoom($user, 'parlour', 0, 0);
    $room = HouseRoom::where('player_house_id', $house->id)->first();

    // Build a crude chair (3 Planks + 2 Nails)
    $inventoryService->addItem($user, Item::where('name', 'Plank')->first(), 3);
    $inventoryService->addItem($user, Item::where('name', 'Nails')->first(), 2);
    $service->buildFurniture($user, $room->id, 'chair', 'crude_chair');

    $user->refresh();
    $goldBeforeDemolish = $user->gold;

    // Demolish room
    $result = $service->demolishRoom($user, $room->id);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('Materials recovered');

    $user->refresh();
    // Should get 50% of parlour cost (2,500 gold)
    expect($user->gold)->toBe($goldBeforeDemolish + 2500);

    // Should get 50% of materials (1 Plank, 1 Nail)
    expect($inventoryService->countItem($user, Item::where('name', 'Plank')->first()))->toBe(1);
    expect($inventoryService->countItem($user, Item::where('name', 'Nails')->first()))->toBe(1);

    // Room and furniture should be deleted
    expect(HouseRoom::where('player_house_id', $house->id)->count())->toBe(0);
    expect(HouseFurniture::where('house_room_id', $room->id)->count())->toBe(0);
});

test('cannot demolish servant quarters with active servant', function () {
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
    $service->buildRoom($user, 'servant_quarters', 0, 0);
    $room = HouseRoom::where('player_house_id', $house->id)->where('room_type', 'servant_quarters')->first();

    // Create an active servant
    HouseServant::create([
        'player_house_id' => $house->id,
        'servant_type' => 'handyman',
        'name' => 'Test Servant',
        'on_strike' => false,
        'hired_at' => now(),
        'last_paid_at' => now(),
    ]);

    $result = $service->demolishRoom($user, $room->id);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Dismiss your servant');
});

test('cannot demolish trophy hall with mounted trophies', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 500000,
        'title_tier' => 4,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 55, 'xp' => 0]);
    $user->load('skills');

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'manor',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $service->buildRoom($user, 'trophy_hall', 0, 0);
    $room = HouseRoom::where('player_house_id', $house->id)->where('room_type', 'trophy_hall')->first();

    // Create a trophy item and mount it
    $trophyItem = Item::create([
        'name' => 'Wolf Fang Trophy',
        'type' => 'misc',
        'subtype' => 'trophy',
        'rarity' => 'uncommon',
        'stackable' => false,
        'max_stack' => 1,
        'base_value' => 50,
    ]);

    HouseTrophy::create([
        'player_house_id' => $house->id,
        'slot' => 'display_1',
        'item_id' => $trophyItem->id,
        'monster_name' => 'Wolf',
        'monster_type' => 'beast',
        'monster_combat_level' => 5,
        'is_boss' => false,
        'mounted_at' => now(),
    ]);

    $result = $service->demolishRoom($user, $room->id);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Remove all trophies');
});

test('cannot demolish garden with active plots', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'gold' => 200000,
        'title_tier' => 4,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 25, 'xp' => 0]);
    $user->load('skills');

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'manor',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(HouseService::class);
    $service->buildRoom($user, 'garden', 0, 0);
    $room = HouseRoom::where('player_house_id', $house->id)->where('room_type', 'garden')->first();

    // Create an active garden plot
    GardenPlot::create([
        'player_house_id' => $house->id,
        'plot_slot' => 'planter_1',
        'status' => 'growing',
        'quality' => 60,
    ]);

    $result = $service->demolishRoom($user, $room->id);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Clear all garden plots');
});

test('cannot demolish portal chamber with configured portals', function () {
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
    $service->buildRoom($user, 'portal_chamber', 0, 0);
    $room = HouseRoom::where('player_house_id', $house->id)->where('room_type', 'portal_chamber')->first();

    // Create a configured portal
    HousePortal::create([
        'player_house_id' => $house->id,
        'portal_slot' => 1,
        'destination_type' => 'kingdom',
        'destination_id' => $kingdom->id,
        'destination_name' => $kingdom->name,
    ]);

    $result = $service->demolishRoom($user, $room->id);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Remove all portal destinations');
});

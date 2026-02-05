<?php

use App\Models\DungeonLootStorage;
use App\Models\Item;
use App\Models\Kingdom;
use App\Models\User;
use App\Services\DungeonLootService;

beforeEach(function () {
    DungeonLootStorage::query()->delete();
    Item::query()->delete();
    Kingdom::query()->delete();
    User::query()->delete();
});

test('can add loot to storage', function () {
    $kingdom = Kingdom::create([
        'name' => 'Test Kingdom',
        'description' => 'A test kingdom',
        'biome' => 'plains',
        'tax_rate' => 10.00,
        'coordinates_x' => 100,
        'coordinates_y' => 100,
    ]);

    $user = User::factory()->create();

    $item = Item::create([
        'name' => 'Iron Ore',
        'description' => 'Raw iron ore',
        'type' => 'resource',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 10,
    ]);

    $storage = DungeonLootStorage::addLoot($user->id, $kingdom->id, $item->id, 5);

    expect($storage)->toBeInstanceOf(DungeonLootStorage::class);
    expect($storage->quantity)->toBe(5);
    expect($storage->user_id)->toBe($user->id);
    expect($storage->kingdom_id)->toBe($kingdom->id);
    expect($storage->item_id)->toBe($item->id);
    expect($storage->expires_at)->toBeGreaterThan(now()->addDays(13));
});

test('adding same item increases quantity', function () {
    $kingdom = Kingdom::create([
        'name' => 'Test Kingdom',
        'description' => 'A test kingdom',
        'biome' => 'plains',
        'tax_rate' => 10.00,
        'coordinates_x' => 100,
        'coordinates_y' => 100,
    ]);

    $user = User::factory()->create();

    $item = Item::create([
        'name' => 'Iron Ore',
        'description' => 'Raw iron ore',
        'type' => 'resource',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 10,
    ]);

    DungeonLootStorage::addLoot($user->id, $kingdom->id, $item->id, 5);
    DungeonLootStorage::addLoot($user->id, $kingdom->id, $item->id, 3);

    $storage = DungeonLootStorage::first();

    expect(DungeonLootStorage::count())->toBe(1);
    expect($storage->quantity)->toBe(8);
});

test('can claim loot from storage', function () {
    $kingdom = Kingdom::create([
        'name' => 'Test Kingdom',
        'description' => 'A test kingdom',
        'biome' => 'plains',
        'tax_rate' => 10.00,
        'coordinates_x' => 100,
        'coordinates_y' => 100,
    ]);

    $user = User::factory()->create();

    $item = Item::create([
        'name' => 'Iron Ore',
        'description' => 'Raw iron ore',
        'type' => 'resource',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 10,
    ]);

    $storage = DungeonLootStorage::addLoot($user->id, $kingdom->id, $item->id, 10);

    $service = app(DungeonLootService::class);
    $result = $service->claimLoot($user, $storage->id, 5);

    expect($result['success'])->toBeTrue();
    expect($result['quantity'])->toBe(5);

    // Check storage was reduced
    $storage->refresh();
    expect($storage->quantity)->toBe(5);

    // Check inventory has item
    $inventory = $user->inventory()->where('item_id', $item->id)->first();
    expect($inventory)->not->toBeNull();
    expect($inventory->quantity)->toBe(5);
});

test('claiming all removes storage entry', function () {
    $kingdom = Kingdom::create([
        'name' => 'Test Kingdom',
        'description' => 'A test kingdom',
        'biome' => 'plains',
        'tax_rate' => 10.00,
        'coordinates_x' => 100,
        'coordinates_y' => 100,
    ]);

    $user = User::factory()->create();

    $item = Item::create([
        'name' => 'Iron Ore',
        'description' => 'Raw iron ore',
        'type' => 'resource',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 10,
    ]);

    $storage = DungeonLootStorage::addLoot($user->id, $kingdom->id, $item->id, 5);

    $service = app(DungeonLootService::class);
    $result = $service->claimLoot($user, $storage->id);

    expect($result['success'])->toBeTrue();
    expect(DungeonLootStorage::find($storage->id))->toBeNull();
});

test('cannot claim expired loot', function () {
    $kingdom = Kingdom::create([
        'name' => 'Test Kingdom',
        'description' => 'A test kingdom',
        'biome' => 'plains',
        'tax_rate' => 10.00,
        'coordinates_x' => 100,
        'coordinates_y' => 100,
    ]);

    $user = User::factory()->create();

    $item = Item::create([
        'name' => 'Iron Ore',
        'description' => 'Raw iron ore',
        'type' => 'resource',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 10,
    ]);

    $storage = DungeonLootStorage::create([
        'user_id' => $user->id,
        'kingdom_id' => $kingdom->id,
        'item_id' => $item->id,
        'quantity' => 5,
        'stored_at' => now()->subWeeks(3),
        'expires_at' => now()->subWeek(),
    ]);

    $service = app(DungeonLootService::class);
    $result = $service->claimLoot($user, $storage->id);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('Loot not found or has expired.');
});

test('claim all loot from kingdom', function () {
    $kingdom = Kingdom::create([
        'name' => 'Test Kingdom',
        'description' => 'A test kingdom',
        'biome' => 'plains',
        'tax_rate' => 10.00,
        'coordinates_x' => 100,
        'coordinates_y' => 100,
    ]);

    $user = User::factory()->create();

    $item1 = Item::create([
        'name' => 'Iron Ore',
        'description' => 'Raw iron ore',
        'type' => 'resource',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 10,
    ]);

    $item2 = Item::create([
        'name' => 'Gold Ore',
        'description' => 'Raw gold ore',
        'type' => 'resource',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 50,
    ]);

    DungeonLootStorage::addLoot($user->id, $kingdom->id, $item1->id, 5);
    DungeonLootStorage::addLoot($user->id, $kingdom->id, $item2->id, 3);

    $service = app(DungeonLootService::class);
    $result = $service->claimAllLoot($user, $kingdom->id);

    expect($result['success'])->toBeTrue();
    expect(DungeonLootStorage::count())->toBe(0);

    // Check inventory
    $inventory = $user->inventory()->get();
    expect($inventory)->toHaveCount(2);
});

test('cleanup removes expired loot', function () {
    $kingdom = Kingdom::create([
        'name' => 'Test Kingdom',
        'description' => 'A test kingdom',
        'biome' => 'plains',
        'tax_rate' => 10.00,
        'coordinates_x' => 100,
        'coordinates_y' => 100,
    ]);

    $user = User::factory()->create();

    $item1 = Item::create([
        'name' => 'Iron Ore',
        'description' => 'Raw iron ore',
        'type' => 'resource',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 10,
    ]);

    $item2 = Item::create([
        'name' => 'Gold Ore',
        'description' => 'Raw gold ore',
        'type' => 'resource',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 50,
    ]);

    // Create expired loot
    DungeonLootStorage::create([
        'user_id' => $user->id,
        'kingdom_id' => $kingdom->id,
        'item_id' => $item1->id,
        'quantity' => 5,
        'stored_at' => now()->subWeeks(3),
        'expires_at' => now()->subWeek(),
    ]);

    // Create fresh loot with different item
    DungeonLootStorage::addLoot($user->id, $kingdom->id, $item2->id, 3);

    $deleted = DungeonLootService::cleanupExpiredLoot();

    expect($deleted)->toBe(1);
    expect(DungeonLootStorage::count())->toBe(1);
});

test('days until expiry is calculated correctly', function () {
    $kingdom = Kingdom::create([
        'name' => 'Test Kingdom',
        'description' => 'A test kingdom',
        'biome' => 'plains',
        'tax_rate' => 10.00,
        'coordinates_x' => 100,
        'coordinates_y' => 100,
    ]);

    $user = User::factory()->create();

    $item = Item::create([
        'name' => 'Iron Ore',
        'description' => 'Raw iron ore',
        'type' => 'resource',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 10,
    ]);

    $storage = DungeonLootStorage::addLoot($user->id, $kingdom->id, $item->id, 5);

    expect($storage->daysUntilExpiry())->toBeGreaterThanOrEqual(13);
    expect($storage->daysUntilExpiry())->toBeLessThanOrEqual(14);
    expect($storage->isExpired())->toBeFalse();
});

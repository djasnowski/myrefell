<?php

use App\Models\Item;
use App\Models\User;
use App\Models\Village;
use App\Services\InventoryService;
use App\Services\SawmillService;

beforeEach(function () {
    // Create required items (firstOrCreate since migration may already seed them)
    Item::firstOrCreate(['name' => 'Wood'], ['type' => 'resource', 'subtype' => 'wood', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 2]);
    Item::firstOrCreate(['name' => 'Plank'], ['type' => 'misc', 'subtype' => 'material', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 20]);
    Item::firstOrCreate(['name' => 'Oak Wood'], ['type' => 'resource', 'subtype' => 'wood', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 8]);
    Item::firstOrCreate(['name' => 'Oak Plank'], ['type' => 'misc', 'subtype' => 'material', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 75]);
});

test('can view sawmill page at village', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
    ]);

    $this->actingAs($user)
        ->get("/villages/{$village->id}/sawmill")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Sawmill/Index'));
});

test('sawmill service returns available plank recipes', function () {
    $user = User::factory()->create(['gold' => 1000]);

    $service = app(SawmillService::class);
    $recipes = $service->getAvailablePlanks($user);

    expect($recipes)->toBeArray();
    expect($recipes)->not->toBeEmpty();
    expect($recipes[0])->toHaveKeys(['plank_name', 'log_name', 'fee', 'player_logs']);
});

test('can convert logs to planks', function () {
    $user = User::factory()->create(['gold' => 1000]);
    $inventoryService = app(InventoryService::class);

    $wood = Item::where('name', 'Wood')->first();
    $inventoryService->addItem($user, $wood, 5);

    $service = app(SawmillService::class);
    $result = $service->makePlanks($user, 'Plank', 3);

    expect($result['success'])->toBeTrue();
    expect($result['planks_made'])->toBe(3);
    expect($result['gold_spent'])->toBe(30);

    $user->refresh();
    expect($user->gold)->toBe(970);
    expect($inventoryService->countItem($user, $wood))->toBe(2);
    expect($inventoryService->countItem($user, Item::where('name', 'Plank')->first()))->toBe(3);
});

test('cannot convert without enough logs', function () {
    $user = User::factory()->create(['gold' => 1000]);
    $inventoryService = app(InventoryService::class);

    $wood = Item::where('name', 'Wood')->first();
    $inventoryService->addItem($user, $wood, 2);

    $service = app(SawmillService::class);
    $result = $service->makePlanks($user, 'Plank', 5);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Not enough Wood');
});

test('cannot convert without enough gold', function () {
    $user = User::factory()->create(['gold' => 5]);
    $inventoryService = app(InventoryService::class);

    $wood = Item::where('name', 'Wood')->first();
    $inventoryService->addItem($user, $wood, 10);

    $service = app(SawmillService::class);
    $result = $service->makePlanks($user, 'Plank', 10);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Not enough gold');
});

test('post convert route works', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'gold' => 1000,
    ]);

    $inventoryService = app(InventoryService::class);
    $wood = Item::where('name', 'Wood')->first();
    $inventoryService->addItem($user, $wood, 5);

    $this->actingAs($user)
        ->post("/villages/{$village->id}/sawmill/convert", [
            'plank_name' => 'Plank',
            'quantity' => 3,
        ])
        ->assertRedirect();
});

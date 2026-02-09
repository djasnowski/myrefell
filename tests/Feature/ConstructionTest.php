<?php

use App\Models\Item;
use App\Models\PlayerSkill;
use App\Models\User;
use App\Models\Village;
use App\Services\InventoryService;
use App\Services\PlayerConstructionService;

beforeEach(function () {
    // Create required items for contracts (firstOrCreate since migration may already seed them)
    Item::firstOrCreate(['name' => 'Plank'], ['type' => 'misc', 'subtype' => 'material', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 20]);
    Item::firstOrCreate(['name' => 'Oak Plank'], ['type' => 'misc', 'subtype' => 'material', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 75]);
});

test('can view construction page at village', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
    ]);

    $this->actingAs($user)
        ->get("/villages/{$village->id}/construction")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Construction/Index'));
});

test('construction service returns available contracts', function () {
    $user = User::factory()->create();

    $service = app(PlayerConstructionService::class);
    $contracts = $service->getAvailableContracts($user);

    expect($contracts)->toBeArray();
    expect($contracts)->not->toBeEmpty();
    expect($contracts[0])->toHaveKeys(['id', 'name', 'level', 'is_unlocked', 'plank_type', 'planks_min', 'planks_max']);
});

test('can complete a beginner contract', function () {
    $user = User::factory()->create(['gold' => 100, 'energy' => 300, 'max_energy' => 300]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 1, 'xp' => 0]);
    $user->load('skills');

    $inventoryService = app(InventoryService::class);
    $plank = Item::where('name', 'Plank')->first();
    $inventoryService->addItem($user, $plank, 10);

    $service = app(PlayerConstructionService::class);
    $result = $service->doContract($user, 'beginner');

    expect($result['success'])->toBeTrue();
    expect($result['xp_awarded'])->toBeGreaterThanOrEqual(50);
    expect($result['xp_awarded'])->toBeLessThanOrEqual(80);
    expect($result['gold_awarded'])->toBeGreaterThanOrEqual(10);
    expect($result['gold_awarded'])->toBeLessThanOrEqual(20);
    expect($result['planks_used'])->toBeGreaterThanOrEqual(3);
    expect($result['planks_used'])->toBeLessThanOrEqual(5);
});

test('cannot do contract without enough planks', function () {
    $user = User::factory()->create(['gold' => 1000, 'energy' => 300, 'max_energy' => 300]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 1, 'xp' => 0]);
    $user->load('skills');

    $inventoryService = app(InventoryService::class);
    $plank = Item::where('name', 'Plank')->first();
    $inventoryService->addItem($user, $plank, 1);

    $service = app(PlayerConstructionService::class);
    $result = $service->doContract($user, 'beginner');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Not enough Plank');
});

test('cannot do contract without enough energy', function () {
    $user = User::factory()->create(['gold' => 1000, 'energy' => 0, 'max_energy' => 300]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 1, 'xp' => 0]);
    $user->load('skills');

    $inventoryService = app(InventoryService::class);
    $plank = Item::where('name', 'Plank')->first();
    $inventoryService->addItem($user, $plank, 10);

    $service = app(PlayerConstructionService::class);
    $result = $service->doContract($user, 'beginner');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Not enough energy');
});

test('cannot do contract above skill level', function () {
    $user = User::factory()->create(['gold' => 1000, 'energy' => 300, 'max_energy' => 300]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 1, 'xp' => 0]);
    $user->load('skills');

    $service = app(PlayerConstructionService::class);
    $result = $service->doContract($user, 'apprentice');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Construction level');
});

test('cannot train while traveling', function () {
    $user = User::factory()->create([
        'is_traveling' => true,
        'travel_arrives_at' => now()->addMinutes(5),
    ]);

    $service = app(PlayerConstructionService::class);
    $result = $service->doContract($user, 'beginner');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('cannot train');
});

test('post contract route works', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'gold' => 1000,
        'energy' => 300,
        'max_energy' => 300,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 1, 'xp' => 0]);

    $inventoryService = app(InventoryService::class);
    $plank = Item::where('name', 'Plank')->first();
    $inventoryService->addItem($user, $plank, 10);

    $this->actingAs($user)
        ->post("/villages/{$village->id}/construction/contract", [
            'tier' => 'beginner',
        ])
        ->assertRedirect();
});

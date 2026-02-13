<?php

use App\Models\Item;
use App\Models\Kingdom;
use App\Models\PlayerSkill;
use App\Models\User;
use App\Services\InventoryService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Item::firstOrCreate(['name' => 'Copper Ore'], ['type' => 'resource', 'subtype' => 'ore', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 5]);
    Item::firstOrCreate(['name' => 'Tin Ore'], ['type' => 'resource', 'subtype' => 'ore', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 5]);
    Item::firstOrCreate(['name' => 'Bronze Bar'], ['type' => 'resource', 'subtype' => 'bar', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 10]);
});

test('forge smelt returns json response on success', function () {
    $kingdom = Kingdom::factory()->create();

    $user = User::factory()->create([
        'energy' => 100,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
    ]);

    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'smithing', 'level' => 1, 'xp' => 0]);

    $copperOre = Item::where('name', 'Copper Ore')->first();
    $tinOre = Item::where('name', 'Tin Ore')->first();
    app(InventoryService::class)->addItem($user, $copperOre, 1);
    app(InventoryService::class)->addItem($user, $tinOre, 1);

    $response = $this->actingAs($user)
        ->postJson("/kingdoms/{$kingdom->id}/forge/smelt", ['recipe' => 'bronze_bar']);

    $response->assertSuccessful()
        ->assertJsonStructure(['success', 'message'])
        ->assertJson(['success' => true]);
});

test('forge smelt returns json response on failure', function () {
    $kingdom = Kingdom::factory()->create();

    $user = User::factory()->create([
        'energy' => 100,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
    ]);

    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'smithing', 'level' => 1, 'xp' => 0]);

    // No ores in inventory - should fail
    $response = $this->actingAs($user)
        ->postJson("/kingdoms/{$kingdom->id}/forge/smelt", ['recipe' => 'bronze_bar']);

    $response->assertSuccessful()
        ->assertJsonStructure(['success', 'message'])
        ->assertJson(['success' => false]);
});

test('forge smelt returns json not redirect', function () {
    $kingdom = Kingdom::factory()->create();

    $user = User::factory()->create([
        'energy' => 100,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
    ]);

    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'smithing', 'level' => 1, 'xp' => 0]);

    $response = $this->actingAs($user)
        ->postJson("/kingdoms/{$kingdom->id}/forge/smelt", ['recipe' => 'bronze_bar']);

    // Should NOT be a redirect
    expect($response->isRedirection())->toBeFalse();

    // Should be JSON
    $response->assertHeader('content-type', 'application/json');
});

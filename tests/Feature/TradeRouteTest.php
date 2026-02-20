<?php

use App\Models\Barony;
use App\Models\Caravan;
use App\Models\Kingdom;
use App\Models\PlayerRole;
use App\Models\Role;
use App\Models\TradeRoute;
use App\Models\User;
use App\Models\Village;

function setupBaronWithRoute(): array
{
    $kingdom = Kingdom::factory()->create();
    $barony = Barony::factory()->create(['kingdom_id' => $kingdom->id]);
    $village = Village::factory()->create(['barony_id' => $barony->id]);
    $otherVillage = Village::factory()->create(['barony_id' => $barony->id]);

    $baronRole = Role::factory()->create([
        'slug' => 'baron',
        'name' => 'Baron',
        'location_type' => 'barony',
        'tier' => 5,
    ]);

    $baron = User::factory()->create();
    PlayerRole::factory()->create([
        'user_id' => $baron->id,
        'role_id' => $baronRole->id,
        'location_type' => 'barony',
        'location_id' => $barony->id,
        'status' => 'active',
    ]);

    $route = TradeRoute::create([
        'name' => 'Test Trade Route',
        'origin_type' => 'village',
        'origin_id' => $village->id,
        'destination_type' => 'village',
        'destination_id' => $otherVillage->id,
        'distance' => 100,
        'base_travel_days' => 2,
        'danger_level' => 'moderate',
        'bandit_chance' => 15,
        'is_active' => true,
    ]);

    return compact('kingdom', 'barony', 'village', 'otherVillage', 'baronRole', 'baron', 'route');
}

it('baron can update trade route name and danger level', function () {
    $data = setupBaronWithRoute();

    $this->actingAs($data['baron'])
        ->putJson("/baronies/{$data['barony']->id}/trade-routes/{$data['route']->id}", [
            'name' => 'Renamed Route',
            'danger_level' => 'perilous',
            'notes' => null,
        ])
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    $data['route']->refresh();
    expect($data['route']->name)->toBe('Renamed Route');
    expect($data['route']->danger_level)->toBe('perilous');
    expect($data['route']->bandit_chance)->toBe(50);
});

it('baron can update trade route notes', function () {
    $data = setupBaronWithRoute();

    $this->actingAs($data['baron'])
        ->putJson("/baronies/{$data['barony']->id}/trade-routes/{$data['route']->id}", [
            'name' => $data['route']->name,
            'danger_level' => $data['route']->danger_level,
            'notes' => 'Watch for bandits near the pass',
        ])
        ->assertSuccessful();

    expect($data['route']->fresh()->notes)->toBe('Watch for bandits near the pass');
});

it('baron can delete trade route', function () {
    $data = setupBaronWithRoute();

    $this->actingAs($data['baron'])
        ->deleteJson("/baronies/{$data['barony']->id}/trade-routes/{$data['route']->id}")
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    expect($data['route']->fresh()->is_active)->toBeFalse();
});

it('cannot edit trade route with active caravans', function () {
    $data = setupBaronWithRoute();

    Caravan::create([
        'name' => 'Test Caravan',
        'owner_id' => $data['baron']->id,
        'trade_route_id' => $data['route']->id,
        'current_location_type' => 'village',
        'current_location_id' => $data['village']->id,
        'destination_type' => 'village',
        'destination_id' => $data['otherVillage']->id,
        'status' => Caravan::STATUS_TRAVELING,
        'capacity' => 100,
        'guards' => 2,
    ]);

    $this->actingAs($data['baron'])
        ->putJson("/baronies/{$data['barony']->id}/trade-routes/{$data['route']->id}", [
            'name' => 'Should Fail',
            'danger_level' => 'perilous',
        ])
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    expect($data['route']->fresh()->name)->toBe('Test Trade Route');
});

it('cannot delete trade route with active caravans', function () {
    $data = setupBaronWithRoute();

    Caravan::create([
        'name' => 'Test Caravan',
        'owner_id' => $data['baron']->id,
        'trade_route_id' => $data['route']->id,
        'current_location_type' => 'village',
        'current_location_id' => $data['village']->id,
        'destination_type' => 'village',
        'destination_id' => $data['otherVillage']->id,
        'status' => Caravan::STATUS_TRAVELING,
        'capacity' => 100,
        'guards' => 2,
    ]);

    $this->actingAs($data['baron'])
        ->deleteJson("/baronies/{$data['barony']->id}/trade-routes/{$data['route']->id}")
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    expect($data['route']->fresh()->is_active)->toBeTrue();
});

it('non-baron cannot update trade route', function () {
    $data = setupBaronWithRoute();
    $randomUser = User::factory()->create();

    $this->actingAs($randomUser)
        ->putJson("/baronies/{$data['barony']->id}/trade-routes/{$data['route']->id}", [
            'name' => 'Hacked',
            'danger_level' => 'safe',
        ])
        ->assertForbidden();
});

it('non-baron cannot delete trade route', function () {
    $data = setupBaronWithRoute();
    $randomUser = User::factory()->create();

    $this->actingAs($randomUser)
        ->deleteJson("/baronies/{$data['barony']->id}/trade-routes/{$data['route']->id}")
        ->assertForbidden();
});

it('cannot update route in another barony', function () {
    $data = setupBaronWithRoute();

    // Create another barony with its own baron
    $otherBarony = Barony::factory()->create(['kingdom_id' => $data['kingdom']->id]);
    $otherBaron = User::factory()->create();
    PlayerRole::factory()->create([
        'user_id' => $otherBaron->id,
        'role_id' => $data['baronRole']->id,
        'location_type' => 'barony',
        'location_id' => $otherBarony->id,
        'status' => 'active',
    ]);

    // Other baron tries to update the first barony's route
    $this->actingAs($otherBaron)
        ->putJson("/baronies/{$data['barony']->id}/trade-routes/{$data['route']->id}", [
            'name' => 'Stolen',
            'danger_level' => 'safe',
        ])
        ->assertForbidden();
});

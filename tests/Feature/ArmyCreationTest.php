<?php

use App\Models\Army;
use App\Models\Town;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('requires a valid location to raise an army', function () {
    $user = User::factory()->create([
        'gold' => 10000,
    ]);

    // Set location_id to null directly via DB (type has NOT NULL constraint)
    DB::table('users')->where('id', $user->id)->update([
        'current_location_id' => null,
    ]);

    $user->refresh();

    $response = $this->actingAs($user)->postJson('/warfare/armies', [
        'name' => 'Test Army',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'You must be at a location to raise an army.',
        ]);
});

it('requires enough gold to raise an army', function () {
    $town = Town::factory()->create();

    $user = User::factory()->create([
        'current_location_type' => 'town',
        'current_location_id' => $town->id,
        'gold' => 100, // Not enough (need 500)
    ]);

    $response = $this->actingAs($user)->postJson('/warfare/armies', [
        'name' => 'Test Army',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Not enough gold. You need 500g to raise an army.',
        ]);
});

it('can raise an army with valid location and gold', function () {
    $town = Town::factory()->create();

    $user = User::factory()->create([
        'current_location_type' => 'town',
        'current_location_id' => $town->id,
        'gold' => 1000,
    ]);

    $response = $this->actingAs($user)->postJson('/warfare/armies', [
        'name' => 'My Army',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $user->refresh();
    expect($user->gold)->toBe(500); // 1000 - 500 cost

    expect(Army::where('owner_type', 'player')
        ->where('owner_id', $user->id)
        ->where('name', 'My Army')
        ->exists())->toBeTrue();
});

it('cannot exceed maximum armies per player', function () {
    $town = Town::factory()->create();

    $user = User::factory()->create([
        'current_location_type' => 'town',
        'current_location_id' => $town->id,
        'gold' => 10000,
    ]);

    // Create 3 armies (the max)
    for ($i = 1; $i <= 3; $i++) {
        Army::create([
            'name' => "Army {$i}",
            'owner_type' => 'player',
            'owner_id' => $user->id,
            'location_type' => 'town',
            'location_id' => $town->id,
            'commander_id' => $user->id,
            'status' => Army::STATUS_MUSTERING,
        ]);
    }

    $response = $this->actingAs($user)->postJson('/warfare/armies', [
        'name' => 'Fourth Army',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'You can only have 3 armies at a time.',
        ]);
});

it('requires a name for the army', function () {
    $town = Town::factory()->create();

    $user = User::factory()->create([
        'current_location_type' => 'town',
        'current_location_id' => $town->id,
        'gold' => 1000,
    ]);

    $response = $this->actingAs($user)->postJson('/warfare/armies', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

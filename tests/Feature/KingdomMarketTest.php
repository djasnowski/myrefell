<?php

use App\Models\Kingdom;
use App\Models\User;
use App\Models\WorldState;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    WorldState::factory()->create([
        'current_year' => 1,
        'current_season' => 'summer',
        'current_week' => 5,
    ]);
});

test('kingdom market page loads for player at kingdom location', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
    ]);

    $response = $this->actingAs($user)->get("/kingdoms/{$kingdom->id}/market");

    $response->assertSuccessful();
});

test('kingdom market is blocked for player not at kingdom', function () {
    $kingdom = Kingdom::factory()->create();
    $otherKingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'kingdom',
        'current_location_id' => $otherKingdom->id,
    ]);

    $response = $this->actingAs($user)->get("/kingdoms/{$kingdom->id}/market");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Market/NotHere'));
});

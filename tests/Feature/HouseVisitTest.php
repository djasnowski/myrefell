<?php

use App\Models\Kingdom;
use App\Models\PlayerHouse;
use App\Models\User;
use App\Models\Village;

test('can visit another player house', function () {
    $kingdom = Kingdom::factory()->create();
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    PlayerHouse::create([
        'player_id' => $owner->id,
        'name' => 'Test House',
        'tier' => 'cottage',
        'condition' => 100,
        'upkeep_due_at' => now()->addDays(7),
        'kingdom_id' => $kingdom->id,
    ]);

    $this->actingAs($visitor)
        ->get("/players/{$owner->username}/house")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('House/Index')
            ->where('isVisiting', true)
            ->where('visitingPlayer', $owner->username)
            ->has('house')
            ->where('house.storage', [])
        );
});

test('shows empty state when player has no house', function () {
    $player = User::factory()->create();
    $visitor = User::factory()->create();

    $this->actingAs($visitor)
        ->get("/players/{$player->username}/house")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('House/Index')
            ->where('isVisiting', true)
            ->where('house', null)
        );
});

test('returns 404 for nonexistent player', function () {
    $visitor = User::factory()->create();

    $this->actingAs($visitor)
        ->get('/players/nonexistent_user_xyz/house')
        ->assertNotFound();
});

test('hides storage from visitors', function () {
    $kingdom = Kingdom::factory()->create();
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    PlayerHouse::create([
        'player_id' => $owner->id,
        'name' => 'Test House',
        'tier' => 'cottage',
        'condition' => 100,
        'upkeep_due_at' => now()->addDays(7),
        'kingdom_id' => $kingdom->id,
    ]);

    $this->actingAs($visitor)
        ->get("/players/{$owner->username}/house")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('house.storage', [])
            ->where('availableDestinations', [])
            ->where('servantData', null)
        );
});

test('unauthenticated user is redirected to login', function () {
    $kingdom = Kingdom::factory()->create();
    $owner = User::factory()->create();
    PlayerHouse::create([
        'player_id' => $owner->id,
        'name' => 'Test House',
        'tier' => 'cottage',
        'condition' => 100,
        'upkeep_due_at' => now()->addDays(7),
        'kingdom_id' => $kingdom->id,
    ]);

    $this->get("/players/{$owner->username}/house")
        ->assertRedirect('/login');
});

test('profile shows visit house link when player has house', function () {
    $kingdom = Kingdom::factory()->create();
    $player = User::factory()->create();
    PlayerHouse::create([
        'player_id' => $player->id,
        'name' => 'Test House',
        'tier' => 'cottage',
        'condition' => 100,
        'upkeep_due_at' => now()->addDays(7),
        'kingdom_id' => $kingdom->id,
    ]);

    $this->get("/players/{$player->username}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('player.has_house', true)
        );
});

test('profile hides visit house link when no house', function () {
    $player = User::factory()->create();

    $this->get("/players/{$player->username}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('player.has_house', false)
        );
});

test('village page shows houses at that location', function () {
    $kingdom = Kingdom::factory()->create();
    $village = Village::factory()->create();
    $owner = User::factory()->create([
        'home_village_id' => $village->id,
    ]);
    $viewer = User::factory()->create([
        'home_village_id' => $village->id,
    ]);

    PlayerHouse::create([
        'player_id' => $owner->id,
        'name' => 'Cozy Cottage',
        'tier' => 'cottage',
        'condition' => 100,
        'upkeep_due_at' => now()->addDays(7),
        'kingdom_id' => $kingdom->id,
        'location_type' => 'village',
        'location_id' => $village->id,
    ]);

    $this->actingAs($viewer)
        ->get("/villages/{$village->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('houses', 1)
            ->where('houses.0.name', 'Cozy Cottage')
            ->where('houses.0.tier_name', 'Cottage')
            ->where('houses.0.owner_username', $owner->username)
        );
});

test('village page hides houses section when none exist', function () {
    $village = Village::factory()->create();
    $viewer = User::factory()->create([
        'home_village_id' => $village->id,
    ]);

    $this->actingAs($viewer)
        ->get("/villages/{$village->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('houses', 0)
        );
});

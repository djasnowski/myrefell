<?php

use App\Models\Kingdom;
use App\Models\PlayerHouse;
use App\Models\User;

test('can visit another player house', function () {
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

    $this->get("/players/{$player->username}/house")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('House/Index')
            ->where('isVisiting', true)
            ->where('house', null)
        );
});

test('returns 404 for nonexistent player', function () {
    $this->get('/players/nonexistent_user_xyz/house')
        ->assertNotFound();
});

test('hides storage from visitors', function () {
    $kingdom = Kingdom::factory()->create();
    $owner = User::factory()->create();
    $house = PlayerHouse::create([
        'player_id' => $owner->id,
        'name' => 'Test House',
        'tier' => 'cottage',
        'condition' => 100,
        'upkeep_due_at' => now()->addDays(7),
        'kingdom_id' => $kingdom->id,
    ]);

    $this->get("/players/{$owner->username}/house")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('house.storage', [])
            ->where('portals', [])
            ->where('servantData', null)
        );
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

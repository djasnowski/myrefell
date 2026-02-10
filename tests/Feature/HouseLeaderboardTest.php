<?php

use App\Models\Kingdom;
use App\Models\PlayerHouse;
use App\Models\User;

test('shows house leaderboard tab', function () {
    $this->get('/leaderboard?tab=houses')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Leaderboard/Index')
            ->where('tab', 'houses')
        );
});

test('ranks houses by value', function () {
    $kingdom = Kingdom::factory()->create();

    $user1 = User::factory()->create();
    PlayerHouse::create([
        'player_id' => $user1->id,
        'name' => 'Small House',
        'tier' => 'cottage',
        'condition' => 100,
        'upkeep_due_at' => now()->addDays(7),
        'kingdom_id' => $kingdom->id,
    ]);

    $user2 = User::factory()->create();
    PlayerHouse::create([
        'player_id' => $user2->id,
        'name' => 'Big House',
        'tier' => 'house',
        'condition' => 100,
        'upkeep_due_at' => now()->addDays(7),
        'kingdom_id' => $kingdom->id,
    ]);

    $this->get('/leaderboard?tab=houses')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('tab', 'houses')
            ->has('leaderboard.entries', 2)
            ->where('leaderboard.entries.0.username', $user2->username)
            ->where('leaderboard.entries.1.username', $user1->username)
        );
});

test('excludes banned players from house leaderboard', function () {
    $kingdom = Kingdom::factory()->create();

    $bannedUser = User::factory()->create(['banned_at' => now()]);
    PlayerHouse::create([
        'player_id' => $bannedUser->id,
        'name' => 'Banned House',
        'tier' => 'manor',
        'condition' => 100,
        'upkeep_due_at' => now()->addDays(7),
        'kingdom_id' => $kingdom->id,
    ]);

    $normalUser = User::factory()->create();
    PlayerHouse::create([
        'player_id' => $normalUser->id,
        'name' => 'Normal House',
        'tier' => 'cottage',
        'condition' => 100,
        'upkeep_due_at' => now()->addDays(7),
        'kingdom_id' => $kingdom->id,
    ]);

    $this->get('/leaderboard?tab=houses')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('leaderboard.entries', 1)
            ->where('leaderboard.entries.0.username', $normalUser->username)
        );
});

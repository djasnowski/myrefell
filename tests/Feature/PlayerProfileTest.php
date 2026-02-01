<?php

use App\Models\PlayerSkill;
use App\Models\User;

test('player profile page can be viewed by anyone', function () {
    $user = User::factory()->create(['username' => 'TestPlayer']);

    $this->get('/players/TestPlayer')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Players/Show')
            ->has('player')
            ->has('skills')
        );
});

test('player profile page is case-insensitive', function () {
    $user = User::factory()->create(['username' => 'TestPlayer']);

    $this->get('/players/testplayer')->assertOk();
    $this->get('/players/TESTPLAYER')->assertOk();
    $this->get('/players/TeStPlAyEr')->assertOk();
});

test('player profile page returns 404 for nonexistent player', function () {
    $this->get('/players/NonexistentPlayer')->assertNotFound();
});

test('player profile shows correct combat level', function () {
    $user = User::factory()->create(['username' => 'CombatTest']);

    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'attack', 'level' => 10, 'xp' => 500]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'strength', 'level' => 20, 'xp' => 2000]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'defense', 'level' => 15, 'xp' => 1000]);

    $this->get('/players/CombatTest')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('player.combat_level', 15)
        );
});

test('player profile shows skill ranks', function () {
    // Create a player with high XP to be ranked #1
    $player1 = User::factory()->create(['username' => 'TopPlayer']);
    PlayerSkill::create(['player_id' => $player1->id, 'skill_name' => 'attack', 'level' => 50, 'xp' => 10000]);

    // Create a player with lower XP to be ranked #2
    $player2 = User::factory()->create(['username' => 'SecondPlayer']);
    PlayerSkill::create(['player_id' => $player2->id, 'skill_name' => 'attack', 'level' => 30, 'xp' => 5000]);

    $this->get('/players/TopPlayer')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('skills.0.rank', 1)
        );

    $this->get('/players/SecondPlayer')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('skills.0.rank', 2)
        );
});

test('player with less than 10 XP is unranked', function () {
    $user = User::factory()->create(['username' => 'NewPlayer']);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'attack', 'level' => 5, 'xp' => 5]);

    $this->get('/players/NewPlayer')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('skills.0.rank', null)
        );
});

test('player profile shows total level', function () {
    $user = User::factory()->create(['username' => 'TotalTest']);

    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'attack', 'level' => 10, 'xp' => 500]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'mining', 'level' => 15, 'xp' => 1000]);

    $this->get('/players/TotalTest')
        ->assertOk()
        ->assertInertia(function ($page) {
            $player = $page->toArray()['props']['player'];
            // Should include default levels for other skills too
            expect($player['total_level'])->toBeGreaterThanOrEqual(25);
        });
});

test('player profile shows total rank', function () {
    // Create a player with some XP
    $player1 = User::factory()->create(['username' => 'RankedPlayer']);
    PlayerSkill::create(['player_id' => $player1->id, 'skill_name' => 'attack', 'level' => 50, 'xp' => 10000]);

    $this->get('/players/RankedPlayer')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('player.total_rank', 1)
        );
});

test('player with no xp has no total rank', function () {
    $user = User::factory()->create(['username' => 'NoXpPlayer']);

    $this->get('/players/NoXpPlayer')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('player.total_rank', null)
        );
});

test('player profile can be viewed without authentication', function () {
    $user = User::factory()->create(['username' => 'PublicProfile']);

    $this->get('/players/PublicProfile')
        ->assertOk();
});

<?php

use App\Models\PlayerSkill;
use App\Models\User;

it('includes total leaderboard in skills list', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/leaderboard');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Leaderboard/Index')
        ->has('skills')
        ->where('skills.0', 'total')
    );
});

it('shows players ranked by total level', function () {
    $user1 = User::factory()->create(['username' => 'HighLevelPlayer']);
    $user2 = User::factory()->create(['username' => 'MidLevelPlayer']);
    $user3 = User::factory()->create(['username' => 'LowLevelPlayer']);

    // User1: total level = 20 (attack 10, strength 10), total xp = 2000
    PlayerSkill::create(['player_id' => $user1->id, 'skill_name' => 'attack', 'level' => 10, 'xp' => 1000]);
    PlayerSkill::create(['player_id' => $user1->id, 'skill_name' => 'strength', 'level' => 10, 'xp' => 1000]);

    // User2: total level = 15 (attack 5, strength 10), total xp = 1500
    PlayerSkill::create(['player_id' => $user2->id, 'skill_name' => 'attack', 'level' => 5, 'xp' => 500]);
    PlayerSkill::create(['player_id' => $user2->id, 'skill_name' => 'strength', 'level' => 10, 'xp' => 1000]);

    // User3: total xp = 0, below 250 threshold - should not appear
    PlayerSkill::create(['player_id' => $user3->id, 'skill_name' => 'attack', 'level' => 1, 'xp' => 0]);
    PlayerSkill::create(['player_id' => $user3->id, 'skill_name' => 'strength', 'level' => 1, 'xp' => 0]);

    $response = $this->actingAs($user1)->get('/leaderboard');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Leaderboard/Index')
        ->has('leaderboard.entries', 2)
        ->where('leaderboard.entries.0.username', 'HighLevelPlayer')
        ->where('leaderboard.entries.0.level', 20)
        ->where('leaderboard.entries.1.username', 'MidLevelPlayer')
        ->where('leaderboard.entries.1.level', 15)
    );
});

it('ranks by total xp when total levels are equal', function () {
    $user1 = User::factory()->create(['username' => 'HighXPPlayer']);
    $user2 = User::factory()->create(['username' => 'LowXPPlayer']);

    // Both have same total level but different XP (both above 250 threshold)
    PlayerSkill::create(['player_id' => $user1->id, 'skill_name' => 'attack', 'level' => 5, 'xp' => 2000]);
    PlayerSkill::create(['player_id' => $user2->id, 'skill_name' => 'attack', 'level' => 5, 'xp' => 1000]);

    $response = $this->actingAs($user1)->get('/leaderboard');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('leaderboard.entries', 2)
        ->where('leaderboard.entries.0.username', 'HighXPPlayer')
        ->where('leaderboard.entries.1.username', 'LowXPPlayer')
    );
});

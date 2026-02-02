<?php

use App\Models\MinigameReward;
use App\Models\MinigameScore;
use App\Models\PlayerSkill;
use App\Models\User;
use App\Models\Village;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests cannot submit scores', function () {
    $this->post(route('minigames.submit-score'), [
        'minigame' => 'archery',
        'score' => 100,
        'location_type' => 'village',
        'location_id' => 1,
    ])->assertRedirect(route('login'));
});

test('authenticated users can submit scores', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
    ]);

    $response = $this->actingAs($user)->post(route('minigames.submit-score'), [
        'minigame' => 'archery',
        'score' => 100,
        'location_type' => 'village',
        'location_id' => $village->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('minigame_scores', [
        'user_id' => $user->id,
        'minigame' => 'archery',
        'score' => 100,
        'location_type' => 'village',
        'location_id' => $village->id,
    ]);
});

test('submitting a score awards range XP', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
    ]);

    $this->actingAs($user)->post(route('minigames.submit-score'), [
        'minigame' => 'archery',
        'score' => 50,
        'location_type' => 'village',
        'location_id' => $village->id,
    ]);

    // Check that range skill was created and XP awarded
    $skill = PlayerSkill::where('player_id', $user->id)
        ->where('skill_name', 'range')
        ->first();

    expect($skill)->not->toBeNull();
    expect($skill->xp)->toBeGreaterThan(PlayerSkill::xpForLevel(5)); // Started at level 5 XP + 50 added
});

test('users cannot play daily-limited games more than once per day', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
    ]);

    // First play should succeed
    $this->actingAs($user)->post(route('minigames.submit-score'), [
        'minigame' => 'archery',
        'score' => 100,
        'location_type' => 'village',
        'location_id' => $village->id,
    ])->assertSessionHas('success');

    // Second play should fail
    $this->actingAs($user)->post(route('minigames.submit-score'), [
        'minigame' => 'archery',
        'score' => 150,
        'location_type' => 'village',
        'location_id' => $village->id,
    ])->assertSessionHas('error');

    // Only one score should be recorded
    expect(MinigameScore::where('user_id', $user->id)->count())->toBe(1);
});

test('non-daily-limited games can be played multiple times', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
    ]);

    // First play
    $this->actingAs($user)->post(route('minigames.submit-score'), [
        'minigame' => 'darts',
        'score' => 100,
        'location_type' => 'village',
        'location_id' => $village->id,
    ])->assertSessionHas('success');

    // Second play should also succeed
    $this->actingAs($user)->post(route('minigames.submit-score'), [
        'minigame' => 'darts',
        'score' => 150,
        'location_type' => 'village',
        'location_id' => $village->id,
    ])->assertSessionHas('success');

    expect(MinigameScore::where('user_id', $user->id)->count())->toBe(2);
});

test('guests cannot view leaderboards', function () {
    $this->get(route('minigames.leaderboards', 'archery'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view leaderboards', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('minigames.leaderboards', 'archery'));

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'minigame' => 'archery',
    ]);
    $response->assertJsonStructure([
        'success',
        'minigame',
        'daily' => ['leaderboard', 'user_rank'],
        'weekly' => ['leaderboard', 'user_rank'],
        'monthly' => ['leaderboard', 'user_rank'],
    ]);
});

test('leaderboards show correct rankings', function () {
    $village = Village::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    // Create scores with different values
    MinigameScore::factory()
        ->forMinigame('archery')
        ->withScore(300)
        ->atLocation('village', $village->id)
        ->today()
        ->for($user1)
        ->create();

    MinigameScore::factory()
        ->forMinigame('archery')
        ->withScore(200)
        ->atLocation('village', $village->id)
        ->today()
        ->for($user2)
        ->create();

    MinigameScore::factory()
        ->forMinigame('archery')
        ->withScore(100)
        ->atLocation('village', $village->id)
        ->today()
        ->for($user3)
        ->create();

    $response = $this->actingAs($user1)->get(route('minigames.leaderboards', 'archery'));

    $response->assertOk();
    $daily = $response->json('daily.leaderboard');

    expect($daily[0]['user_id'])->toBe($user1->id);
    expect($daily[0]['score'])->toBe(300);
    expect($daily[1]['user_id'])->toBe($user2->id);
    expect($daily[1]['score'])->toBe(200);
    expect($daily[2]['user_id'])->toBe($user3->id);
    expect($daily[2]['score'])->toBe(100);
});

test('guests cannot collect rewards', function () {
    $this->post(route('minigames.collect-rewards'))
        ->assertRedirect(route('login'));
});

test('users can collect rewards at their current location', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'gold' => 0,
    ]);

    // Create uncollected reward
    MinigameReward::factory()
        ->atLocation('village', $village->id)
        ->for($user)
        ->create(['gold_amount' => 500]);

    $response = $this->actingAs($user)->post(route('minigames.collect-rewards'));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $user->refresh();
    expect($user->gold)->toBe(500);
    expect(MinigameReward::where('user_id', $user->id)->uncollected()->count())->toBe(0);
});

test('users cannot collect rewards at a different location', function () {
    $village1 = Village::factory()->create();
    $village2 = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village1->id,
        'gold' => 0,
    ]);

    // Create reward at a different location
    MinigameReward::factory()
        ->atLocation('village', $village2->id)
        ->for($user)
        ->create(['gold_amount' => 500]);

    $response = $this->actingAs($user)->post(route('minigames.collect-rewards'));

    $response->assertRedirect();
    $response->assertSessionHas('error');

    $user->refresh();
    expect($user->gold)->toBe(0);
});

test('guests cannot view pending rewards', function () {
    $this->get(route('minigames.pending-rewards'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view their pending rewards', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create();

    // Create some uncollected rewards
    MinigameReward::factory()
        ->atLocation('village', $village->id)
        ->for($user)
        ->count(3)
        ->create();

    $response = $this->actingAs($user)->get(route('minigames.pending-rewards'));

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'total_pending' => 3,
    ]);
});

test('pending rewards are grouped by location', function () {
    $village1 = Village::factory()->create();
    $village2 = Village::factory()->create();
    $user = User::factory()->create();

    // Create rewards at different locations
    MinigameReward::factory()
        ->atLocation('village', $village1->id)
        ->for($user)
        ->count(2)
        ->create();

    MinigameReward::factory()
        ->atLocation('village', $village2->id)
        ->for($user)
        ->count(1)
        ->create();

    $response = $this->actingAs($user)->get(route('minigames.pending-rewards'));

    $response->assertOk();
    $locations = $response->json('locations');

    expect(count($locations))->toBe(2);
});

test('submit score validates required fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('minigames.submit-score'), [])
        ->assertSessionHasErrors(['minigame', 'score', 'location_type', 'location_id']);
});

test('submit score validates location type', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('minigames.submit-score'), [
        'minigame' => 'archery',
        'score' => 100,
        'location_type' => 'invalid',
        'location_id' => 1,
    ])->assertSessionHasErrors(['location_type']);
});

test('submit score validates score is non-negative', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('minigames.submit-score'), [
        'minigame' => 'archery',
        'score' => -5,
        'location_type' => 'village',
        'location_id' => 1,
    ])->assertSessionHasErrors(['score']);
});

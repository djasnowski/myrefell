<?php

use App\Models\TavernDiceGame;
use App\Models\User;
use App\Models\Village;
use App\Services\DiceGameService;

beforeEach(function () {
    User::query()->delete();
    TavernDiceGame::query()->delete();
});

test('can view tavern page with dice games data', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'gold' => 100,
        'energy' => 50,
    ]);

    $this->actingAs($user)
        ->get("/villages/{$village->id}/tavern")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Tavern/Index')
            ->has('dice')
            ->has('dice.can_play')
            ->has('dice.min_wager')
            ->has('dice.max_wager')
            ->has('dice.tavern_stats')
        );
});

test('can play high roll dice game', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'gold' => 100,
        'energy' => 50,
    ]);

    $initialGold = $user->gold;

    $this->actingAs($user)
        ->post("/villages/{$village->id}/tavern/dice", [
            'game_type' => 'high_roll',
            'wager' => 50,
        ])
        ->assertRedirect();

    // Check that a game was recorded
    expect(TavernDiceGame::where('user_id', $user->id)->count())->toBe(1);

    // Check the game was recorded correctly
    $game = TavernDiceGame::where('user_id', $user->id)->first();
    expect($game->game_type)->toBe('high_roll');
    expect($game->wager)->toBe(50);
    expect($game->location_type)->toBe('village');
    expect($game->location_id)->toBe($village->id);

    // Check gold changed appropriately
    // Payout: 1.5x multiplier with 10% rake = 1.35x
    // 50g wager * 1.5 = 75g, minus 10% rake (7g) = 68g win
    $user->refresh();
    if ($game->won) {
        expect($user->gold)->toBe($initialGold + 68);
    } else {
        expect($user->gold)->toBe($initialGold - 50);
    }
});

test('can play hazard dice game', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'gold' => 100,
        'energy' => 50,
    ]);

    $this->actingAs($user)
        ->post("/villages/{$village->id}/tavern/dice", [
            'game_type' => 'hazard',
            'wager' => 20,
        ])
        ->assertRedirect();

    $game = TavernDiceGame::where('user_id', $user->id)->first();
    expect($game->game_type)->toBe('hazard');
    expect($game->rolls)->toBeArray();
});

test('can play doubles dice game', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'gold' => 100,
        'energy' => 50,
    ]);

    $this->actingAs($user)
        ->post("/villages/{$village->id}/tavern/dice", [
            'game_type' => 'doubles',
            'wager' => 30,
        ])
        ->assertRedirect();

    $game = TavernDiceGame::where('user_id', $user->id)->first();
    expect($game->game_type)->toBe('doubles');
});

test('cannot play with insufficient gold', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'gold' => 5,
        'energy' => 50,
    ]);

    $this->actingAs($user)
        ->post("/villages/{$village->id}/tavern/dice", [
            'game_type' => 'high_roll',
            'wager' => 50,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('error');
});

test('cannot play below minimum wager', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'gold' => 100,
        'energy' => 50,
    ]);

    $this->actingAs($user)
        ->post("/villages/{$village->id}/tavern/dice", [
            'game_type' => 'high_roll',
            'wager' => 5,
        ])
        ->assertSessionHasErrors('wager');
});

test('cannot play above maximum wager', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'gold' => 5000,
        'energy' => 50,
    ]);

    $this->actingAs($user)
        ->post("/villages/{$village->id}/tavern/dice", [
            'game_type' => 'high_roll',
            'wager' => 3000,
        ])
        ->assertSessionHasErrors('wager');
});

test('cannot play invalid game type', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'gold' => 100,
        'energy' => 50,
    ]);

    $this->actingAs($user)
        ->post("/villages/{$village->id}/tavern/dice", [
            'game_type' => 'invalid_game',
            'wager' => 50,
        ])
        ->assertSessionHasErrors('game_type');
});

test('cooldown prevents playing again immediately', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'gold' => 500,
        'energy' => 50,
    ]);

    // First game should work
    $this->actingAs($user)
        ->post("/villages/{$village->id}/tavern/dice", [
            'game_type' => 'high_roll',
            'wager' => 50,
        ])
        ->assertRedirect();

    // Second game should fail due to cooldown
    $user->refresh();
    $this->actingAs($user)
        ->post("/villages/{$village->id}/tavern/dice", [
            'game_type' => 'high_roll',
            'wager' => 50,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('error');
});

test('energy is awarded after game', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'gold' => 100,
        'energy' => 50,
        'max_energy' => 100,
    ]);

    $initialEnergy = $user->energy;

    $this->actingAs($user)
        ->post("/villages/{$village->id}/tavern/dice", [
            'game_type' => 'high_roll',
            'wager' => 50,
        ])
        ->assertRedirect();

    $user->refresh();
    $game = TavernDiceGame::where('user_id', $user->id)->first();

    // Energy should increase (either 3 for loss or 10 for win)
    expect($user->energy)->toBeGreaterThan($initialEnergy);
    expect($game->energy_awarded)->toBeIn([3, 10]);
});

test('tavern stats are tracked per location', function () {
    $village1 = Village::factory()->create();
    $village2 = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village1->id,
        'gold' => 1000,
        'energy' => 50,
    ]);

    // Play at village 1
    TavernDiceGame::create([
        'user_id' => $user->id,
        'location_type' => 'village',
        'location_id' => $village1->id,
        'game_type' => 'high_roll',
        'wager' => 50,
        'rolls' => ['player' => [3, 4], 'house' => [2, 2]],
        'won' => true,
        'payout' => 50,
        'energy_awarded' => 10,
        'created_at' => now()->subMinutes(10),
    ]);

    // Play at village 2
    TavernDiceGame::create([
        'user_id' => $user->id,
        'location_type' => 'village',
        'location_id' => $village2->id,
        'game_type' => 'high_roll',
        'wager' => 100,
        'rolls' => ['player' => [1, 1], 'house' => [6, 6]],
        'won' => false,
        'payout' => -100,
        'energy_awarded' => 3,
        'created_at' => now()->subMinutes(10),
    ]);

    $service = app(DiceGameService::class);

    $stats1 = $service->getTavernStats($user, 'village', $village1->id);
    expect($stats1['wins'])->toBe(1);
    expect($stats1['losses'])->toBe(0);
    expect($stats1['total_profit'])->toBe(50);

    $stats2 = $service->getTavernStats($user, 'village', $village2->id);
    expect($stats2['wins'])->toBe(0);
    expect($stats2['losses'])->toBe(1);
    expect($stats2['total_profit'])->toBe(-100);
});

test('doubles win pays 1.8x wager (2x with 10% rake)', function () {
    $service = app(DiceGameService::class);

    // Mock a winning doubles game by testing the service directly
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'gold' => 100,
        'energy' => 50,
    ]);

    // Run multiple games to eventually get a doubles win
    $foundDoublesWin = false;
    for ($i = 0; $i < 100; $i++) {
        TavernDiceGame::query()->delete();

        $result = $service->play($user, 'doubles', 10, 'village', $village->id);
        $user->refresh();

        // Reset for next iteration
        $user->update(['gold' => 100]);

        // Wait for cooldown
        TavernDiceGame::query()->update(['created_at' => now()->subMinutes(10)]);

        if ($result['won']) {
            $foundDoublesWin = true;
            // 2x multiplier = 20g, minus 10% rake (2g) = 18g
            expect($result['payout'])->toBe(18);
            break;
        }
    }

    // We should find at least one doubles win in 100 tries (probability > 99.99%)
    expect($foundDoublesWin)->toBeTrue();
});

test('game history is returned correctly', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'gold' => 1000,
    ]);

    // Create some game history
    TavernDiceGame::create([
        'user_id' => $user->id,
        'location_type' => 'village',
        'location_id' => $village->id,
        'game_type' => 'high_roll',
        'wager' => 50,
        'rolls' => ['player' => [3, 4], 'house' => [2, 2]],
        'won' => true,
        'payout' => 50,
        'energy_awarded' => 10,
        'created_at' => now()->subMinutes(10),
    ]);

    TavernDiceGame::create([
        'user_id' => $user->id,
        'location_type' => 'village',
        'location_id' => $village->id,
        'game_type' => 'hazard',
        'wager' => 100,
        'rolls' => [['dice' => [1, 2], 'total' => 3, 'type' => 'come_out']],
        'won' => false,
        'payout' => -100,
        'energy_awarded' => 3,
        'created_at' => now()->subMinutes(5),
    ]);

    $service = app(DiceGameService::class);
    $history = $service->getGameHistory($user, 10);

    expect($history)->toHaveCount(2);
    expect($history[0]['game_type'])->toBe('hazard'); // Most recent first
    expect($history[1]['game_type'])->toBe('high_roll');
});

test('can play at town location', function () {
    $user = User::factory()->create([
        'current_location_type' => 'town',
        'current_location_id' => 1,
        'gold' => 100,
        'energy' => 50,
    ]);

    $service = app(DiceGameService::class);
    $result = $service->play($user, 'high_roll', 20, 'town', 1);

    expect($result['success'])->toBeTrue();
});

test('can play at barony location', function () {
    $user = User::factory()->create([
        'current_location_type' => 'barony',
        'current_location_id' => 1,
        'gold' => 100,
        'energy' => 50,
    ]);

    $service = app(DiceGameService::class);
    $result = $service->play($user, 'high_roll', 20, 'barony', 1);

    expect($result['success'])->toBeTrue();
});

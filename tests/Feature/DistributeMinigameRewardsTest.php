<?php

use App\Models\MinigameReward;
use App\Models\MinigameScore;
use App\Models\User;
use App\Models\Village;
use Illuminate\Support\Carbon;

beforeEach(function () {
    // Clear relevant data
    MinigameReward::query()->delete();
    MinigameScore::query()->delete();
    User::query()->delete();
    Village::query()->delete();
});

test('distributes daily rewards for top 10 players', function () {
    $village = Village::factory()->create();

    // Create 12 users with scores from yesterday
    $users = collect();
    for ($i = 1; $i <= 12; $i++) {
        $user = User::factory()->create();
        $users->push($user);

        MinigameScore::factory()
            ->forMinigame('archery')
            ->withScore(1000 - ($i * 50)) // Descending scores
            ->atLocation('village', $village->id)
            ->playedAt(now()->subDay())
            ->create(['user_id' => $user->id]);
    }

    // Run the command
    $this->artisan('minigames:distribute-rewards --minigame=archery')
        ->assertSuccessful()
        ->expectsOutputToContain('Daily rewards distributed: 10');

    // Check rewards were created for top 10 only
    expect(MinigameReward::count())->toBe(10);

    // Check 1st place gets legendary + 1000 gold
    $firstPlace = MinigameReward::where('user_id', $users->first()->id)->first();
    expect($firstPlace)->not->toBeNull();
    expect($firstPlace->rank)->toBe(1);
    expect($firstPlace->item_rarity)->toBe('legendary');
    expect($firstPlace->gold_amount)->toBe(1000);

    // Check 2nd place gets epic + 500 gold
    $secondPlace = MinigameReward::where('user_id', $users->get(1)->id)->first();
    expect($secondPlace)->not->toBeNull();
    expect($secondPlace->rank)->toBe(2);
    expect($secondPlace->item_rarity)->toBe('epic');
    expect($secondPlace->gold_amount)->toBe(500);

    // Check 3rd place gets rare + 250 gold
    $thirdPlace = MinigameReward::where('user_id', $users->get(2)->id)->first();
    expect($thirdPlace)->not->toBeNull();
    expect($thirdPlace->rank)->toBe(3);
    expect($thirdPlace->item_rarity)->toBe('rare');
    expect($thirdPlace->gold_amount)->toBe(250);

    // Check 4th-10th place gets 100 gold, no item
    for ($i = 3; $i < 10; $i++) {
        $reward = MinigameReward::where('user_id', $users->get($i)->id)->first();
        expect($reward)->not->toBeNull();
        expect($reward->rank)->toBe($i + 1);
        expect($reward->item_rarity)->toBeNull();
        expect($reward->gold_amount)->toBe(100);
    }

    // Check 11th and 12th place get no rewards
    expect(MinigameReward::where('user_id', $users->get(10)->id)->exists())->toBeFalse();
    expect(MinigameReward::where('user_id', $users->get(11)->id)->exists())->toBeFalse();
});

test('rewards use location of highest score', function () {
    $village1 = Village::factory()->create();
    $village2 = Village::factory()->create();

    $user = User::factory()->create();

    // Lower score at village1
    MinigameScore::factory()
        ->forMinigame('archery')
        ->withScore(500)
        ->atLocation('village', $village1->id)
        ->playedAt(now()->subDay())
        ->create(['user_id' => $user->id]);

    // Higher score at village2
    MinigameScore::factory()
        ->forMinigame('archery')
        ->withScore(1000)
        ->atLocation('village', $village2->id)
        ->playedAt(now()->subDay())
        ->create(['user_id' => $user->id]);

    $this->artisan('minigames:distribute-rewards --minigame=archery')
        ->assertSuccessful();

    $reward = MinigameReward::where('user_id', $user->id)->first();

    // Should use location of highest score (village2)
    expect($reward->location_type)->toBe('village');
    expect($reward->location_id)->toBe($village2->id);
});

test('distributes weekly rewards on monday', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-02 10:00:00')); // Monday

    $village = Village::factory()->create();
    $user = User::factory()->create();

    // Score from last week
    MinigameScore::factory()
        ->forMinigame('archery')
        ->withScore(1000)
        ->atLocation('village', $village->id)
        ->playedAt(now()->subWeek()->startOfWeek()->addDays(2)) // Wednesday of last week
        ->create(['user_id' => $user->id]);

    $this->artisan('minigames:distribute-rewards --minigame=archery')
        ->assertSuccessful()
        ->expectsOutputToContain('Weekly rewards distributed: 1');

    $weeklyReward = MinigameReward::where('reward_type', 'weekly')->first();

    expect($weeklyReward)->not->toBeNull();
    expect($weeklyReward->user_id)->toBe($user->id);
    expect($weeklyReward->rank)->toBe(1);

    Carbon::setTestNow();
});

test('does not distribute weekly rewards on other days', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-03 10:00:00')); // Tuesday

    $village = Village::factory()->create();
    $user = User::factory()->create();

    MinigameScore::factory()
        ->forMinigame('archery')
        ->withScore(1000)
        ->atLocation('village', $village->id)
        ->playedAt(now()->subWeek())
        ->create(['user_id' => $user->id]);

    $this->artisan('minigames:distribute-rewards --minigame=archery')
        ->assertSuccessful();

    expect(MinigameReward::where('reward_type', 'weekly')->count())->toBe(0);

    Carbon::setTestNow();
});

test('distributes monthly rewards on first of month', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-01 10:00:00')); // First of February

    $village = Village::factory()->create();
    $user = User::factory()->create();

    // Score from last month
    MinigameScore::factory()
        ->forMinigame('archery')
        ->withScore(1000)
        ->atLocation('village', $village->id)
        ->playedAt(Carbon::parse('2026-01-15')) // Mid-January
        ->create(['user_id' => $user->id]);

    $this->artisan('minigames:distribute-rewards --minigame=archery')
        ->assertSuccessful()
        ->expectsOutputToContain('Monthly rewards distributed: 1');

    $monthlyReward = MinigameReward::where('reward_type', 'monthly')->first();

    expect($monthlyReward)->not->toBeNull();
    expect($monthlyReward->user_id)->toBe($user->id);

    Carbon::setTestNow();
});

test('does not distribute monthly rewards on other days', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-15 10:00:00')); // 15th of February

    $village = Village::factory()->create();
    $user = User::factory()->create();

    MinigameScore::factory()
        ->forMinigame('archery')
        ->withScore(1000)
        ->atLocation('village', $village->id)
        ->playedAt(now()->subMonth())
        ->create(['user_id' => $user->id]);

    $this->artisan('minigames:distribute-rewards --minigame=archery')
        ->assertSuccessful();

    expect(MinigameReward::where('reward_type', 'monthly')->count())->toBe(0);

    Carbon::setTestNow();
});

test('does not create duplicate rewards', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create();

    MinigameScore::factory()
        ->forMinigame('archery')
        ->withScore(1000)
        ->atLocation('village', $village->id)
        ->playedAt(now()->subDay())
        ->create(['user_id' => $user->id]);

    // Run command twice
    $this->artisan('minigames:distribute-rewards --minigame=archery')
        ->assertSuccessful();

    $this->artisan('minigames:distribute-rewards --minigame=archery')
        ->assertSuccessful();

    // Should only have 1 reward
    expect(MinigameReward::where('user_id', $user->id)->count())->toBe(1);
});

test('filters rewards by minigame option', function () {
    $village = Village::factory()->create();

    $archeryUser = User::factory()->create();
    MinigameScore::factory()
        ->forMinigame('archery')
        ->withScore(1000)
        ->atLocation('village', $village->id)
        ->playedAt(now()->subDay())
        ->create(['user_id' => $archeryUser->id]);

    $joustingUser = User::factory()->create();
    MinigameScore::factory()
        ->forMinigame('jousting')
        ->withScore(1000)
        ->atLocation('village', $village->id)
        ->playedAt(now()->subDay())
        ->create(['user_id' => $joustingUser->id]);

    // Run for archery only
    $this->artisan('minigames:distribute-rewards --minigame=archery')
        ->assertSuccessful();

    // Should only reward archery player
    expect(MinigameReward::where('user_id', $archeryUser->id)->exists())->toBeTrue();
    expect(MinigameReward::where('user_id', $joustingUser->id)->exists())->toBeFalse();

    // Now run for jousting
    $this->artisan('minigames:distribute-rewards --minigame=jousting')
        ->assertSuccessful();

    expect(MinigameReward::where('user_id', $joustingUser->id)->exists())->toBeTrue();
});

test('handles no scores gracefully', function () {
    $this->artisan('minigames:distribute-rewards --minigame=archery')
        ->assertSuccessful()
        ->expectsOutputToContain('Daily rewards distributed: 0');

    expect(MinigameReward::count())->toBe(0);
});

test('sets correct period dates', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-02 10:00:00')); // Monday, Feb 2

    $village = Village::factory()->create();
    $user = User::factory()->create();

    // Score for yesterday (Feb 1)
    MinigameScore::factory()
        ->forMinigame('archery')
        ->withScore(1000)
        ->atLocation('village', $village->id)
        ->playedAt(Carbon::parse('2026-02-01 14:00:00'))
        ->create(['user_id' => $user->id]);

    // Score from last week for weekly reward
    MinigameScore::factory()
        ->forMinigame('archery')
        ->withScore(900)
        ->atLocation('village', $village->id)
        ->playedAt(Carbon::parse('2026-01-28 14:00:00')) // Wednesday of last week
        ->create(['user_id' => $user->id]);

    $this->artisan('minigames:distribute-rewards --minigame=archery')
        ->assertSuccessful();

    // Check daily reward period
    $dailyReward = MinigameReward::where('reward_type', 'daily')->first();
    expect($dailyReward->period_start->toDateString())->toBe('2026-02-01');
    expect($dailyReward->period_end->toDateString())->toBe('2026-02-01');

    // Check weekly reward period
    $weeklyReward = MinigameReward::where('reward_type', 'weekly')->first();
    expect($weeklyReward->period_start->toDateString())->toBe('2026-01-26'); // Monday
    expect($weeklyReward->period_end->toDateString())->toBe('2026-02-01'); // Sunday

    Carbon::setTestNow();
});

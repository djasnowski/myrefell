<?php

use App\Models\Belief;
use App\Models\Religion;
use App\Models\ReligionMember;
use App\Models\User;
use App\Services\BeliefEffectService;

test('can get active effects for user with no religion', function () {
    $user = User::factory()->create();
    $service = app(BeliefEffectService::class);

    $result = $service->getActiveEffects($user);

    expect($result)->toBeArray()->toBeEmpty();
});

test('can get specific effect for user with no religion', function () {
    $user = User::factory()->create();
    $service = app(BeliefEffectService::class);

    $result = $service->getEffect($user, 'combat_xp_bonus');

    expect($result)->toBe(0.0);
});

test('belief xp bonus is applied to skill xp gains', function () {
    $user = User::factory()->create();

    // Create a combat skill
    $skill = $user->skills()->create([
        'skill_name' => 'attack',
        'level' => 1,
        'xp' => 0,
    ]);

    // Create a belief with combat XP bonus
    $belief = Belief::create([
        'name' => 'Test Combat Belief',
        'description' => 'Test',
        'icon' => 'sword',
        'type' => 'virtue',
        'effects' => ['combat_xp_bonus' => 20], // +20% combat XP
    ]);

    // Create a religion with this belief
    $religion = Religion::create([
        'name' => 'Test Religion',
        'description' => 'Test',
        'icon' => 'sun',
        'color' => '#fff',
        'type' => 'religion',
        'is_public' => true,
        'is_active' => true,
    ]);
    $religion->beliefs()->attach($belief->id);

    // Make user a member
    ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'follower',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    // Add 100 XP - should become 120 with 20% bonus
    $skill->addXp(100);

    // Check XP was boosted (100 * 1.2 = 120)
    expect($skill->fresh()->xp)->toBe(120);
});

test('belief max hp bonus is applied', function () {
    $user = User::factory()->create();

    // Create hitpoints skill at level 20
    $user->skills()->create([
        'skill_name' => 'hitpoints',
        'level' => 20,
        'xp' => 1000,
    ]);

    // Base max HP should be 20
    expect($user->fresh()->max_hp)->toBe(20);

    // Create a belief with max HP bonus (Fortitude)
    $belief = Belief::create([
        'name' => 'Test Fortitude Belief',
        'description' => 'Test',
        'icon' => 'shield',
        'type' => 'virtue',
        'effects' => ['max_hp_bonus' => 5], // +5 HP
    ]);

    // Create a religion with this belief
    $religion = Religion::create([
        'name' => 'Test Fortitude Religion',
        'description' => 'Test',
        'icon' => 'sun',
        'color' => '#fff',
        'type' => 'religion',
        'is_public' => true,
        'is_active' => true,
    ]);
    $religion->beliefs()->attach($belief->id);

    // Make user a member
    ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'follower',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    // Max HP should now be 25 (20 base + 5 bonus)
    expect($user->fresh()->max_hp)->toBe(25);
});

test('multiple belief effects stack', function () {
    $user = User::factory()->create();
    $service = app(BeliefEffectService::class);

    // Create two beliefs
    $belief1 = Belief::create([
        'name' => 'Test Belief 1',
        'description' => 'Test',
        'icon' => 'sword',
        'type' => 'virtue',
        'effects' => ['combat_xp_bonus' => 10],
    ]);

    $belief2 = Belief::create([
        'name' => 'Test Belief 2',
        'description' => 'Test',
        'icon' => 'skull',
        'type' => 'vice',
        'effects' => ['combat_xp_bonus' => 20, 'crafting_xp_penalty' => -10],
    ]);

    // Create a religion with both beliefs
    $religion = Religion::create([
        'name' => 'Test Stacking Religion',
        'description' => 'Test',
        'icon' => 'sun',
        'color' => '#fff',
        'type' => 'religion',
        'is_public' => true,
        'is_active' => true,
    ]);
    $religion->beliefs()->attach([$belief1->id, $belief2->id]);

    // Make user a member
    ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'follower',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    // Effects should stack: 10 + 20 = 30% combat bonus
    expect($service->getEffect($user, 'combat_xp_bonus'))->toBe(30.0);
    expect($service->getEffect($user, 'crafting_xp_penalty'))->toBe(-10.0);
});

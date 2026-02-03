<?php

use App\Models\User;
use App\Services\BlessingEffectService;

test('can get action cooldown seconds for user', function () {
    $user = User::factory()->create();
    $service = app(BlessingEffectService::class);

    // Should return null for user with no blessings (and not throw SQL error)
    $result = $service->getActionCooldownSeconds($user);

    expect($result)->toBeNull();
});

test('can check if user has haste blessing', function () {
    $user = User::factory()->create();
    $service = app(BlessingEffectService::class);

    $result = $service->hasHasteBlessing($user);

    expect($result)->toBeFalse();
});

test('can get active effects for user', function () {
    $user = User::factory()->create();
    $service = app(BlessingEffectService::class);

    $result = $service->getActiveEffects($user);

    expect($result)->toBeArray();
});

test('can get specific effect for user', function () {
    $user = User::factory()->create();
    $service = app(BlessingEffectService::class);

    $result = $service->getEffect($user, 'some_effect');

    expect($result)->toBe(0.0);
});

test('blessing xp bonus is applied to skill xp gains', function () {
    $user = User::factory()->create();

    // Create a fishing skill
    $skill = $user->skills()->create([
        'skill_name' => 'fishing',
        'level' => 1,
        'xp' => 0,
    ]);

    // Create a blessing type with fishing XP bonus
    $blessingType = \App\Models\BlessingType::create([
        'name' => 'Test Fishing Blessing',
        'slug' => 'test-fishing',
        'icon' => 'fish',
        'description' => 'Test blessing',
        'category' => 'skill',
        'effects' => ['fishing_xp_bonus' => 50], // 50% bonus
        'duration_minutes' => 60,
        'cooldown_minutes' => 0,
        'prayer_level_required' => 1,
        'gold_cost' => 10,
        'energy_cost' => 5,
    ]);

    // Create an active blessing for the user
    \App\Models\PlayerBlessing::create([
        'user_id' => $user->id,
        'blessing_type_id' => $blessingType->id,
        'expires_at' => now()->addHour(),
        'granted_by' => $user->id,
    ]);

    // Add 100 XP - should become 150 with 50% bonus
    $skill->addXp(100);

    // Check XP was boosted (100 * 1.5 = 150)
    expect($skill->fresh()->xp)->toBe(150);
});

test('blessing all xp bonus is applied to all skills', function () {
    $user = User::factory()->create();

    // Create a crafting skill
    $skill = $user->skills()->create([
        'skill_name' => 'crafting',
        'level' => 1,
        'xp' => 0,
    ]);

    // Create a blessing type with all XP bonus (Wisdom)
    $blessingType = \App\Models\BlessingType::create([
        'name' => 'Test Wisdom Blessing',
        'slug' => 'test-wisdom',
        'icon' => 'book',
        'description' => 'Test blessing',
        'category' => 'general',
        'effects' => ['all_xp_bonus' => 20], // 20% bonus to all XP
        'duration_minutes' => 60,
        'cooldown_minutes' => 0,
        'prayer_level_required' => 1,
        'gold_cost' => 10,
        'energy_cost' => 5,
    ]);

    // Create an active blessing for the user
    \App\Models\PlayerBlessing::create([
        'user_id' => $user->id,
        'blessing_type_id' => $blessingType->id,
        'expires_at' => now()->addHour(),
        'granted_by' => $user->id,
    ]);

    // Add 100 XP - should become 120 with 20% bonus
    $skill->addXp(100);

    // Check XP was boosted (100 * 1.2 = 120)
    expect($skill->fresh()->xp)->toBe(120);
});

test('blessing max hp bonus is applied', function () {
    $user = User::factory()->create();

    // Create hitpoints skill at level 20
    $user->skills()->create([
        'skill_name' => 'hitpoints',
        'level' => 20,
        'xp' => 1000,
    ]);

    // Base max HP should be 20
    expect($user->fresh()->max_hp)->toBe(20);

    // Create a blessing type with max HP bonus
    $blessingType = \App\Models\BlessingType::create([
        'name' => 'Test Vitality Blessing',
        'slug' => 'test-vitality',
        'icon' => 'heart',
        'description' => 'Test blessing',
        'category' => 'combat',
        'effects' => ['max_hp_bonus' => 10], // +10 HP
        'duration_minutes' => 60,
        'cooldown_minutes' => 0,
        'prayer_level_required' => 1,
        'gold_cost' => 10,
        'energy_cost' => 5,
    ]);

    // Create an active blessing for the user
    \App\Models\PlayerBlessing::create([
        'user_id' => $user->id,
        'blessing_type_id' => $blessingType->id,
        'expires_at' => now()->addHour(),
        'granted_by' => $user->id,
    ]);

    // Max HP should now be 30 (20 base + 10 bonus)
    expect($user->fresh()->max_hp)->toBe(30);
});

test('blessing hp regen bonus increases hp regeneration', function () {
    $user = User::factory()->create(['hp' => 10]);

    // Create hitpoints skill at level 20 (max HP = 20)
    $user->skills()->create([
        'skill_name' => 'hitpoints',
        'level' => 20,
        'xp' => 1000,
    ]);

    $hpService = app(\App\Services\HpService::class);

    // Without blessing: 5% of 20 = 1 HP base + 2 skill bonus (level 20 / 10) = 3 HP
    $regenWithoutBlessing = $hpService->regenerateHp($user->fresh());
    expect($regenWithoutBlessing)->toBe(3);

    // Reset HP
    $user->update(['hp' => 10]);

    // Create a blessing type with HP regen bonus
    $blessingType = \App\Models\BlessingType::create([
        'name' => 'Test Restoration Blessing',
        'slug' => 'test-restoration',
        'icon' => 'heart-pulse',
        'description' => 'Test blessing',
        'category' => 'general',
        'effects' => ['hp_regen_bonus' => 100], // +100% HP regen
        'duration_minutes' => 60,
        'cooldown_minutes' => 0,
        'prayer_level_required' => 1,
        'gold_cost' => 10,
        'energy_cost' => 5,
    ]);

    // Create an active blessing for the user
    \App\Models\PlayerBlessing::create([
        'user_id' => $user->id,
        'blessing_type_id' => $blessingType->id,
        'expires_at' => now()->addHour(),
        'granted_by' => $user->id,
    ]);

    // With 100% blessing bonus: base regen doubles (1 * 2 = 2) + 2 skill bonus = 4 HP
    $regenWithBlessing = $hpService->regenerateHp($user->fresh());
    expect($regenWithBlessing)->toBe(4);
});

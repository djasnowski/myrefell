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

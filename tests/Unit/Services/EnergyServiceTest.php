<?php

use App\Models\User;
use App\Services\EnergyService;

beforeEach(function () {
    $this->service = app(EnergyService::class);
});

describe('hasEnergy', function () {
    test('returns true when player has enough energy', function () {
        $user = User::factory()->create(['energy' => 50, 'max_energy' => 100]);

        expect($this->service->hasEnergy($user, 30))->toBeTrue();
        expect($this->service->hasEnergy($user, 50))->toBeTrue();
    });

    test('returns false when player does not have enough energy', function () {
        $user = User::factory()->create(['energy' => 10, 'max_energy' => 100]);

        expect($this->service->hasEnergy($user, 20))->toBeFalse();
        expect($this->service->hasEnergy($user, 100))->toBeFalse();
    });

    test('returns true for zero energy cost', function () {
        $user = User::factory()->create(['energy' => 0, 'max_energy' => 100]);

        expect($this->service->hasEnergy($user, 0))->toBeTrue();
    });
});

describe('consumeEnergy', function () {
    test('deducts energy from player', function () {
        $user = User::factory()->create(['energy' => 50, 'max_energy' => 100]);

        $result = $this->service->consumeEnergy($user, 20);

        expect($result)->toBeTrue();
        expect($user->fresh()->energy)->toBe(30);
    });

    test('returns false when insufficient energy', function () {
        $user = User::factory()->create(['energy' => 10, 'max_energy' => 100]);

        $result = $this->service->consumeEnergy($user, 20);

        expect($result)->toBeFalse();
        expect($user->fresh()->energy)->toBe(10); // Unchanged
    });

    test('can consume all remaining energy', function () {
        $user = User::factory()->create(['energy' => 25, 'max_energy' => 100]);

        $result = $this->service->consumeEnergy($user, 25);

        expect($result)->toBeTrue();
        expect($user->fresh()->energy)->toBe(0);
    });
});

describe('addEnergy', function () {
    test('adds energy to player', function () {
        $user = User::factory()->create(['energy' => 50, 'max_energy' => 100]);

        $gained = $this->service->addEnergy($user, 20);

        expect($gained)->toBe(20);
        expect($user->fresh()->energy)->toBe(70);
    });

    test('caps energy at max_energy', function () {
        $user = User::factory()->create(['energy' => 90, 'max_energy' => 100]);

        $gained = $this->service->addEnergy($user, 50);

        expect($gained)->toBe(10); // Only gained 10 to reach max
        expect($user->fresh()->energy)->toBe(100);
    });

    test('returns zero when already at max', function () {
        $user = User::factory()->create(['energy' => 100, 'max_energy' => 100]);

        $gained = $this->service->addEnergy($user, 20);

        expect($gained)->toBe(0);
        expect($user->fresh()->energy)->toBe(100);
    });
});

describe('setEnergy', function () {
    test('sets energy to specific value', function () {
        $user = User::factory()->create(['energy' => 50, 'max_energy' => 100]);

        $this->service->setEnergy($user, 75);

        expect($user->fresh()->energy)->toBe(75);
    });

    test('clamps to max_energy', function () {
        $user = User::factory()->create(['energy' => 50, 'max_energy' => 100]);

        $this->service->setEnergy($user, 150);

        expect($user->fresh()->energy)->toBe(100);
    });

    test('clamps to zero for negative values', function () {
        $user = User::factory()->create(['energy' => 50, 'max_energy' => 100]);

        $this->service->setEnergy($user, -20);

        expect($user->fresh()->energy)->toBe(0);
    });
});

describe('setEnergyOnDeath', function () {
    test('sets energy to 25% of current value', function () {
        $user = User::factory()->create(['energy' => 100, 'max_energy' => 100]);

        $this->service->setEnergyOnDeath($user);

        expect($user->fresh()->energy)->toBe(25);
    });

    test('floors the result', function () {
        $user = User::factory()->create(['energy' => 50, 'max_energy' => 100]);

        $this->service->setEnergyOnDeath($user);

        expect($user->fresh()->energy)->toBe(12); // floor(50 * 0.25) = 12
    });

    test('results in zero when energy is low', function () {
        $user = User::factory()->create(['energy' => 3, 'max_energy' => 100]);

        $this->service->setEnergyOnDeath($user);

        expect($user->fresh()->energy)->toBe(0); // floor(3 * 0.25) = 0
    });
});

describe('regenerateEnergy', function () {
    test('adds 1 energy when below max', function () {
        $user = User::factory()->create(['energy' => 50, 'max_energy' => 100]);

        $gained = $this->service->regenerateEnergy($user);

        expect($gained)->toBe(1);
        expect($user->fresh()->energy)->toBe(51);
    });

    test('returns zero when at max', function () {
        $user = User::factory()->create(['energy' => 100, 'max_energy' => 100]);

        $gained = $this->service->regenerateEnergy($user);

        expect($gained)->toBe(0);
        expect($user->fresh()->energy)->toBe(100);
    });
});

describe('regenerateAllPlayers', function () {
    test('regenerates energy for all players below max', function () {
        User::factory()->create(['energy' => 50, 'max_energy' => 100]);
        User::factory()->create(['energy' => 75, 'max_energy' => 100]);
        User::factory()->create(['energy' => 100, 'max_energy' => 100]); // At max

        $affected = $this->service->regenerateAllPlayers();

        expect($affected)->toBe(2);
    });

    test('does not exceed max energy', function () {
        $user = User::factory()->create(['energy' => 99, 'max_energy' => 100]);

        $this->service->regenerateAllPlayers();

        expect($user->fresh()->energy)->toBe(100);
    });
});

describe('getRegenInfo', function () {
    test('returns correct info when below max', function () {
        $user = User::factory()->create(['energy' => 50, 'max_energy' => 100]);

        $info = $this->service->getRegenInfo($user);

        expect($info['current'])->toBe(50);
        expect($info['max'])->toBe(100);
        expect($info['at_max'])->toBeFalse();
        expect($info['regen_rate'])->toBe(EnergyService::REGEN_MINUTES);
        expect($info['seconds_until_next'])->toBeInt();
        expect($info['seconds_until_next'])->toBeGreaterThanOrEqual(0);
        expect($info['seconds_until_next'])->toBeLessThanOrEqual(EnergyService::REGEN_MINUTES * 60);
    });

    test('returns null seconds when at max', function () {
        $user = User::factory()->create(['energy' => 100, 'max_energy' => 100]);

        $info = $this->service->getRegenInfo($user);

        expect($info['at_max'])->toBeTrue();
        expect($info['seconds_until_next'])->toBeNull();
    });
});

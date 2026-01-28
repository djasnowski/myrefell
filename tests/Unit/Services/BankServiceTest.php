<?php

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\User;
use App\Models\Village;
use App\Services\BankService;

beforeEach(function () {
    $this->service = app(BankService::class);
});

function createVillage(array $attributes = []): Village
{
    return Village::create(array_merge([
        'name' => 'Test Village ' . uniqid(),
        'population' => 100,
        'wealth' => 1000,
        'biome' => 'plains',
        'granary_capacity' => 500,
    ], $attributes));
}

describe('canAccessBank', function () {
    test('returns true when user is at a village', function () {
        $village = createVillage();
        $user = User::factory()->create([
            'current_location_type' => 'village',
            'current_location_id' => $village->id,
        ]);

        expect($this->service->canAccessBank($user))->toBeTrue();
    });

    test('returns false when user is traveling', function () {
        $village = createVillage();
        $user = User::factory()->create([
            'current_location_type' => 'village',
            'current_location_id' => $village->id,
            'is_traveling' => true,
            'travel_arrives_at' => now()->addHours(1), // Still traveling
        ]);

        expect($this->service->canAccessBank($user))->toBeFalse();
    });

    test('returns false when user is at invalid location type', function () {
        $user = User::factory()->create([
            'current_location_type' => 'wilderness',
            'current_location_id' => 1,
        ]);

        expect($this->service->canAccessBank($user))->toBeFalse();
    });
});

describe('getOrCreateAccount', function () {
    test('creates new account if none exists', function () {
        $village = createVillage();
        $user = User::factory()->create([
            'current_location_type' => 'village',
            'current_location_id' => $village->id,
        ]);

        $account = $this->service->getOrCreateAccount($user);

        expect($account)->not->toBeNull();
        expect($account->user_id)->toBe($user->id);
        expect($account->location_type)->toBe('village');
        expect($account->location_id)->toBe($village->id);
        expect($account->balance)->toBe(0);
    });

    test('returns existing account if one exists', function () {
        $village = createVillage();
        $user = User::factory()->create([
            'current_location_type' => 'village',
            'current_location_id' => $village->id,
        ]);

        // Create existing account
        $existingAccount = BankAccount::create([
            'user_id' => $user->id,
            'location_type' => 'village',
            'location_id' => $village->id,
            'balance' => 500,
        ]);

        $account = $this->service->getOrCreateAccount($user);

        expect($account->id)->toBe($existingAccount->id);
        expect($account->balance)->toBe(500);
    });

    test('returns null when user cannot access bank', function () {
        $user = User::factory()->create([
            'current_location_type' => 'wilderness',
            'current_location_id' => 1,
        ]);

        $account = $this->service->getOrCreateAccount($user);

        expect($account)->toBeNull();
    });
});

describe('deposit', function () {
    test('deposits gold successfully', function () {
        $village = createVillage();
        $user = User::factory()->create([
            'current_location_type' => 'village',
            'current_location_id' => $village->id,
            'gold' => 1000,
        ]);

        $result = $this->service->deposit($user, 500);

        expect($result['success'])->toBeTrue();
        expect($result['new_balance'])->toBe(500);
        expect($result['gold_on_hand'])->toBe(500);
        expect($user->fresh()->gold)->toBe(500);
    });

    test('creates transaction record', function () {
        $village = createVillage();
        $user = User::factory()->create([
            'current_location_type' => 'village',
            'current_location_id' => $village->id,
            'gold' => 1000,
        ]);

        $this->service->deposit($user, 300);

        $transaction = BankTransaction::where('user_id', $user->id)->first();
        expect($transaction)->not->toBeNull();
        expect($transaction->type)->toBe(BankTransaction::TYPE_DEPOSIT);
        expect($transaction->amount)->toBe(300);
        expect($transaction->balance_after)->toBe(300);
    });

    test('fails with zero amount', function () {
        $village = createVillage();
        $user = User::factory()->create([
            'current_location_type' => 'village',
            'current_location_id' => $village->id,
            'gold' => 1000,
        ]);

        $result = $this->service->deposit($user, 0);

        expect($result['success'])->toBeFalse();
        expect($result['message'])->toContain('greater than zero');
    });

    test('fails with negative amount', function () {
        $village = createVillage();
        $user = User::factory()->create([
            'current_location_type' => 'village',
            'current_location_id' => $village->id,
            'gold' => 1000,
        ]);

        $result = $this->service->deposit($user, -100);

        expect($result['success'])->toBeFalse();
    });

    test('fails when user has insufficient gold', function () {
        $village = createVillage();
        $user = User::factory()->create([
            'current_location_type' => 'village',
            'current_location_id' => $village->id,
            'gold' => 100,
        ]);

        $result = $this->service->deposit($user, 500);

        expect($result['success'])->toBeFalse();
        expect($result['message'])->toContain('enough gold');
        expect($user->fresh()->gold)->toBe(100); // Unchanged
    });

    test('fails when user cannot access bank', function () {
        $user = User::factory()->create([
            'current_location_type' => 'wilderness',
            'current_location_id' => 1,
            'gold' => 1000,
        ]);

        $result = $this->service->deposit($user, 500);

        expect($result['success'])->toBeFalse();
        expect($result['message'])->toContain('cannot access');
    });
});

describe('withdraw', function () {
    test('withdraws gold successfully', function () {
        $village = createVillage();
        $user = User::factory()->create([
            'current_location_type' => 'village',
            'current_location_id' => $village->id,
            'gold' => 100,
        ]);

        // Create account with balance
        BankAccount::create([
            'user_id' => $user->id,
            'location_type' => 'village',
            'location_id' => $village->id,
            'balance' => 500,
        ]);

        $result = $this->service->withdraw($user, 200);

        expect($result['success'])->toBeTrue();
        expect($result['new_balance'])->toBe(300);
        expect($result['gold_on_hand'])->toBe(300);
        expect($user->fresh()->gold)->toBe(300);
    });

    test('creates transaction record', function () {
        $village = createVillage();
        $user = User::factory()->create([
            'current_location_type' => 'village',
            'current_location_id' => $village->id,
            'gold' => 0,
        ]);

        BankAccount::create([
            'user_id' => $user->id,
            'location_type' => 'village',
            'location_id' => $village->id,
            'balance' => 500,
        ]);

        $this->service->withdraw($user, 150);

        $transaction = BankTransaction::where('user_id', $user->id)->first();
        expect($transaction)->not->toBeNull();
        expect($transaction->type)->toBe(BankTransaction::TYPE_WITHDRAWAL);
        expect($transaction->amount)->toBe(150);
        expect($transaction->balance_after)->toBe(350);
    });

    test('fails with zero amount', function () {
        $village = createVillage();
        $user = User::factory()->create([
            'current_location_type' => 'village',
            'current_location_id' => $village->id,
        ]);

        $result = $this->service->withdraw($user, 0);

        expect($result['success'])->toBeFalse();
    });

    test('fails when account has insufficient funds', function () {
        $village = createVillage();
        $user = User::factory()->create([
            'current_location_type' => 'village',
            'current_location_id' => $village->id,
            'gold' => 0,
        ]);

        BankAccount::create([
            'user_id' => $user->id,
            'location_type' => 'village',
            'location_id' => $village->id,
            'balance' => 100,
        ]);

        $result = $this->service->withdraw($user, 500);

        expect($result['success'])->toBeFalse();
        expect($result['message'])->toContain('Insufficient funds');
    });

    test('fails when user cannot access bank', function () {
        $user = User::factory()->create([
            'current_location_type' => 'wilderness',
            'current_location_id' => 1,
        ]);

        $result = $this->service->withdraw($user, 100);

        expect($result['success'])->toBeFalse();
    });
});

describe('getBalance', function () {
    test('returns account balance', function () {
        $village = createVillage();
        $user = User::factory()->create([
            'current_location_type' => 'village',
            'current_location_id' => $village->id,
        ]);

        BankAccount::create([
            'user_id' => $user->id,
            'location_type' => 'village',
            'location_id' => $village->id,
            'balance' => 750,
        ]);

        expect($this->service->getBalance($user))->toBe(750);
    });

    test('returns zero for new account', function () {
        $village = createVillage();
        $user = User::factory()->create([
            'current_location_type' => 'village',
            'current_location_id' => $village->id,
        ]);

        expect($this->service->getBalance($user))->toBe(0);
    });

    test('returns zero when cannot access bank', function () {
        $user = User::factory()->create([
            'current_location_type' => 'wilderness',
            'current_location_id' => 1,
        ]);

        expect($this->service->getBalance($user))->toBe(0);
    });
});

describe('getTotalBalance', function () {
    test('sums balance across all accounts', function () {
        $village1 = createVillage();
        $village2 = createVillage();
        $user = User::factory()->create([
            'current_location_type' => 'village',
            'current_location_id' => $village1->id,
        ]);

        BankAccount::create([
            'user_id' => $user->id,
            'location_type' => 'village',
            'location_id' => $village1->id,
            'balance' => 500,
        ]);
        BankAccount::create([
            'user_id' => $user->id,
            'location_type' => 'village',
            'location_id' => $village2->id,
            'balance' => 300,
        ]);

        expect($this->service->getTotalBalance($user))->toBe(800);
    });

    test('returns zero with no accounts', function () {
        $user = User::factory()->create();

        expect($this->service->getTotalBalance($user))->toBe(0);
    });
});

describe('getAllAccounts', function () {
    test('returns all accounts with positive balance', function () {
        $village1 = createVillage(['name' => 'Riverdale']);
        $village2 = createVillage(['name' => 'Oakton']);
        $user = User::factory()->create();

        BankAccount::create([
            'user_id' => $user->id,
            'location_type' => 'village',
            'location_id' => $village1->id,
            'balance' => 500,
        ]);
        BankAccount::create([
            'user_id' => $user->id,
            'location_type' => 'village',
            'location_id' => $village2->id,
            'balance' => 300,
        ]);
        BankAccount::create([
            'user_id' => $user->id,
            'location_type' => 'village',
            'location_id' => 999,
            'balance' => 0, // Zero balance - should not be included
        ]);

        $accounts = $this->service->getAllAccounts($user);

        expect($accounts)->toHaveCount(2);
        expect($accounts->pluck('balance')->toArray())->toContain(500, 300);
    });

    test('returns empty collection when no accounts', function () {
        $user = User::factory()->create();

        $accounts = $this->service->getAllAccounts($user);

        expect($accounts)->toHaveCount(0);
    });
});

describe('getRecentTransactions', function () {
    test('returns recent transactions', function () {
        $village = createVillage();
        $user = User::factory()->create([
            'current_location_type' => 'village',
            'current_location_id' => $village->id,
            'gold' => 1000,
        ]);

        $this->service->deposit($user, 200);
        $this->service->deposit($user, 300);
        $this->service->withdraw($user, 100);

        $transactions = $this->service->getRecentTransactions($user, 10);

        expect($transactions)->toHaveCount(3);
    });

    test('respects limit parameter', function () {
        $village = createVillage();
        $user = User::factory()->create([
            'current_location_type' => 'village',
            'current_location_id' => $village->id,
            'gold' => 1000,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->service->deposit($user, 50);
        }

        $transactions = $this->service->getRecentTransactions($user, 3);

        expect($transactions)->toHaveCount(3);
    });

    test('returns empty when cannot access bank', function () {
        $user = User::factory()->create([
            'current_location_type' => 'wilderness',
            'current_location_id' => 1,
        ]);

        $transactions = $this->service->getRecentTransactions($user);

        expect($transactions)->toHaveCount(0);
    });
});

describe('getBankInfo', function () {
    test('returns complete bank info', function () {
        $village = createVillage(['name' => 'Testville']);
        $user = User::factory()->create([
            'current_location_type' => 'village',
            'current_location_id' => $village->id,
            'gold' => 250,
        ]);

        BankAccount::create([
            'user_id' => $user->id,
            'location_type' => 'village',
            'location_id' => $village->id,
            'balance' => 750,
        ]);

        $info = $this->service->getBankInfo($user);

        expect($info)->not->toBeNull();
        expect($info['location_type'])->toBe('village');
        expect($info['location_name'])->toBe('Testville');
        expect($info['balance'])->toBe(750);
        expect($info['gold_on_hand'])->toBe(250);
        expect($info['total_wealth'])->toBe(1000);
    });

    test('returns null when cannot access bank', function () {
        $user = User::factory()->create([
            'current_location_type' => 'wilderness',
            'current_location_id' => 1,
        ]);

        $info = $this->service->getBankInfo($user);

        expect($info)->toBeNull();
    });
});

<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BankService
{
    /**
     * Valid location types for banking.
     */
    public const VALID_LOCATIONS = ['village', 'castle', 'town'];

    /**
     * Get or create a bank account for a user at their current location.
     */
    public function getOrCreateAccount(User $user): ?BankAccount
    {
        $locationType = $user->current_location_type;
        $locationId = $user->current_location_id;

        if (! $this->canAccessBank($user)) {
            return null;
        }

        return BankAccount::firstOrCreate(
            [
                'user_id' => $user->id,
                'location_type' => $locationType,
                'location_id' => $locationId,
            ],
            [
                'balance' => 0,
            ]
        );
    }

    /**
     * Check if user can access a bank at their current location.
     */
    public function canAccessBank(User $user): bool
    {
        if ($user->isTraveling()) {
            return false;
        }

        $locationType = $user->current_location_type;

        // Check if at a location with a bank
        if (! in_array($locationType, self::VALID_LOCATIONS)) {
            return false;
        }

        return true;
    }

    /**
     * Deposit gold into the bank.
     */
    public function deposit(User $user, int $amount): array
    {
        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'Amount must be greater than zero.',
            ];
        }

        if ($user->gold < $amount) {
            return [
                'success' => false,
                'message' => 'You don\'t have enough gold on you.',
            ];
        }

        $account = $this->getOrCreateAccount($user);

        if (! $account) {
            return [
                'success' => false,
                'message' => 'You cannot access a bank here.',
            ];
        }

        return DB::transaction(function () use ($user, $account, $amount) {
            // Deduct from player
            $user->decrement('gold', $amount);

            // Add to bank account
            $account->increment('balance', $amount);
            $account->refresh();

            // Record transaction
            BankTransaction::create([
                'user_id' => $user->id,
                'bank_account_id' => $account->id,
                'type' => BankTransaction::TYPE_DEPOSIT,
                'amount' => $amount,
                'balance_after' => $account->balance,
                'description' => 'Deposited gold',
            ]);

            return [
                'success' => true,
                'message' => "Deposited {$amount} gold.",
                'new_balance' => $account->balance,
                'gold_on_hand' => $user->fresh()->gold,
            ];
        });
    }

    /**
     * Withdraw gold from the bank.
     */
    public function withdraw(User $user, int $amount): array
    {
        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'Amount must be greater than zero.',
            ];
        }

        $account = $this->getOrCreateAccount($user);

        if (! $account) {
            return [
                'success' => false,
                'message' => 'You cannot access a bank here.',
            ];
        }

        if ($account->balance < $amount) {
            return [
                'success' => false,
                'message' => 'Insufficient funds in your account.',
            ];
        }

        return DB::transaction(function () use ($user, $account, $amount) {
            // Deduct from bank account
            $account->decrement('balance', $amount);
            $account->refresh();

            // Add to player
            $user->increment('gold', $amount);

            // Record transaction
            BankTransaction::create([
                'user_id' => $user->id,
                'bank_account_id' => $account->id,
                'type' => BankTransaction::TYPE_WITHDRAWAL,
                'amount' => $amount,
                'balance_after' => $account->balance,
                'description' => 'Withdrew gold',
            ]);

            return [
                'success' => true,
                'message' => "Withdrew {$amount} gold.",
                'new_balance' => $account->balance,
                'gold_on_hand' => $user->fresh()->gold,
            ];
        });
    }

    /**
     * Get bank account balance at current location.
     */
    public function getBalance(User $user): int
    {
        $account = $this->getOrCreateAccount($user);

        return $account?->balance ?? 0;
    }

    /**
     * Get total balance across all bank accounts.
     */
    public function getTotalBalance(User $user): int
    {
        return BankAccount::where('user_id', $user->id)->sum('balance');
    }

    /**
     * Get all bank accounts for a user.
     */
    public function getAllAccounts(User $user): Collection
    {
        return BankAccount::where('user_id', $user->id)
            ->where('balance', '>', 0)
            ->get()
            ->map(function ($account) {
                $location = $this->resolveLocation($account->location_type, $account->location_id);

                return [
                    'id' => $account->id,
                    'location_type' => $account->location_type,
                    'location_id' => $account->location_id,
                    'location_name' => $location?->name ?? 'Unknown',
                    'balance' => $account->balance,
                ];
            });
    }

    /**
     * Get recent transactions for the current location account.
     */
    public function getRecentTransactions(User $user, int $limit = 10): Collection
    {
        $account = $this->getOrCreateAccount($user);

        if (! $account) {
            return collect();
        }

        return $account->transactions()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($tx) => [
                'id' => $tx->id,
                'type' => $tx->type,
                'amount' => $tx->amount,
                'balance_after' => $tx->balance_after,
                'description' => $tx->description,
                'created_at' => $tx->created_at->toISOString(),
                'formatted_date' => $tx->created_at->format('M j, g:i A'),
            ]);
    }

    /**
     * Resolve a location model by type and ID.
     */
    protected function resolveLocation(string $type, int $id): ?object
    {
        $modelClass = match ($type) {
            'village' => \App\Models\Village::class,
            'castle' => \App\Models\Castle::class,
            'town' => \App\Models\Town::class,
            default => null,
        };

        if (! $modelClass) {
            return null;
        }

        return $modelClass::find($id);
    }

    /**
     * Get bank info for current location.
     */
    public function getBankInfo(User $user): ?array
    {
        if (! $this->canAccessBank($user)) {
            return null;
        }

        $account = $this->getOrCreateAccount($user);
        $location = $this->resolveLocation($user->current_location_type, $user->current_location_id);

        return [
            'location_type' => $user->current_location_type,
            'location_id' => $user->current_location_id,
            'location_name' => $location?->name ?? 'Unknown',
            'balance' => $account?->balance ?? 0,
            'gold_on_hand' => $user->gold,
            'total_wealth' => ($account?->balance ?? 0) + $user->gold,
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReligionTreasury extends Model
{
    protected $fillable = [
        'religion_id',
        'balance',
        'total_collected',
        'total_distributed',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
            'total_collected' => 'integer',
            'total_distributed' => 'integer',
        ];
    }

    /**
     * Get the religion this treasury belongs to.
     */
    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    /**
     * Get all transactions for this treasury.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(ReligionTreasuryTransaction::class);
    }

    /**
     * Get recent transactions.
     */
    public function recentTransactions(int $limit = 10): HasMany
    {
        return $this->transactions()->orderByDesc('created_at')->limit($limit);
    }

    /**
     * Deposit gold into the treasury.
     */
    public function deposit(int $amount, string $type, string $description, ?int $userId = null): ReligionTreasuryTransaction
    {
        $this->increment('balance', $amount);
        $this->increment('total_collected', $amount);
        $this->refresh();

        return $this->transactions()->create([
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $this->balance,
            'description' => $description,
        ]);
    }

    /**
     * Withdraw gold from the treasury.
     */
    public function withdraw(int $amount, string $type, string $description, ?int $userId = null): ?ReligionTreasuryTransaction
    {
        if ($this->balance < $amount) {
            return null;
        }

        $this->decrement('balance', $amount);
        $this->increment('total_distributed', $amount);
        $this->refresh();

        return $this->transactions()->create([
            'user_id' => $userId,
            'type' => $type,
            'amount' => -$amount,
            'balance_after' => $this->balance,
            'description' => $description,
        ]);
    }

    /**
     * Get or create a treasury for a religion.
     */
    public static function getOrCreate(Religion $religion): self
    {
        return static::firstOrCreate(
            ['religion_id' => $religion->id],
            [
                'balance' => 0,
                'total_collected' => 0,
                'total_distributed' => 0,
            ]
        );
    }
}

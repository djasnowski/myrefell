<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReligionTreasuryTransaction extends Model
{
    public const TYPE_DONATION = 'donation';

    public const TYPE_UPGRADE_COST = 'upgrade_cost';

    public const TYPE_FEATURE_COST = 'feature_cost';

    public const TYPE_WITHDRAWAL = 'withdrawal';

    protected $fillable = [
        'religion_treasury_id',
        'user_id',
        'type',
        'amount',
        'balance_after',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    /**
     * Get the treasury this transaction belongs to.
     */
    public function treasury(): BelongsTo
    {
        return $this->belongsTo(ReligionTreasury::class, 'religion_treasury_id');
    }

    /**
     * Get the user who made this transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this is an income transaction.
     */
    public function isIncome(): bool
    {
        return $this->amount > 0;
    }

    /**
     * Check if this is an expense transaction.
     */
    public function isExpense(): bool
    {
        return $this->amount < 0;
    }
}

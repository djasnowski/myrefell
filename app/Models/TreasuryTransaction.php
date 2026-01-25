<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreasuryTransaction extends Model
{
    public const TYPE_TAX_INCOME = 'tax_income';
    public const TYPE_SALARY_PAYMENT = 'salary_payment';
    public const TYPE_TRANSFER_IN = 'transfer_in';
    public const TYPE_TRANSFER_OUT = 'transfer_out';
    public const TYPE_UPSTREAM_TAX = 'upstream_tax';

    protected $fillable = [
        'location_treasury_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'related_user_id',
        'related_location_type',
        'related_location_id',
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
        return $this->belongsTo(LocationTreasury::class, 'location_treasury_id');
    }

    /**
     * Get the related user (if any).
     */
    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_user_id');
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

    /**
     * Get the related location model.
     */
    public function getRelatedLocationAttribute(): Model|null
    {
        if (!$this->related_location_type) {
            return null;
        }

        return match ($this->related_location_type) {
            'village' => Village::find($this->related_location_id),
            'castle' => Castle::find($this->related_location_id),
            'kingdom' => Kingdom::find($this->related_location_id),
            default => null,
        };
    }
}

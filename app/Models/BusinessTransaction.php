<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessTransaction extends Model
{
    use HasFactory;

    public const TYPE_SALE = 'sale';
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_WAGE = 'wage';
    public const TYPE_UPKEEP = 'upkeep';
    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_WITHDRAWAL = 'withdrawal';
    public const TYPE_PRODUCTION = 'production';

    protected $fillable = [
        'player_business_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'related_user_id',
        'related_item_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    /**
     * Get the business this transaction belongs to.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(PlayerBusiness::class, 'player_business_id');
    }

    /**
     * Get the related user (if any).
     */
    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }

    /**
     * Get the related item (if any).
     */
    public function relatedItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'related_item_id');
    }

    /**
     * Check if this is income.
     */
    public function isIncome(): bool
    {
        return $this->amount > 0;
    }

    /**
     * Check if this is an expense.
     */
    public function isExpense(): bool
    {
        return $this->amount < 0;
    }

    /**
     * Get display type.
     */
    public function getTypeDisplayAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_SALE => 'Sale',
            self::TYPE_PURCHASE => 'Purchase',
            self::TYPE_WAGE => 'Wages',
            self::TYPE_UPKEEP => 'Upkeep',
            self::TYPE_DEPOSIT => 'Deposit',
            self::TYPE_WITHDRAWAL => 'Withdrawal',
            self::TYPE_PRODUCTION => 'Production',
            default => ucfirst($this->type),
        };
    }
}

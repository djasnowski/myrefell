<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_QUALIFIED = 'qualified';

    public const STATUS_REWARDED = 'rewarded';

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'status',
        'ip_address',
        'qualified_at',
        'rewarded_at',
        'reward_amount',
        'bonus_rewarded_at',
        'referrer_bonus_item',
        'referred_bonus_item',
    ];

    protected function casts(): array
    {
        return [
            'qualified_at' => 'datetime',
            'rewarded_at' => 'datetime',
            'reward_amount' => 'integer',
            'bonus_rewarded_at' => 'datetime',
        ];
    }

    /**
     * The user who made the referral.
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * The user who was referred.
     */
    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isQualified(): bool
    {
        return $this->status === self::STATUS_QUALIFIED;
    }

    public function isRewarded(): bool
    {
        return $this->status === self::STATUS_REWARDED;
    }
}

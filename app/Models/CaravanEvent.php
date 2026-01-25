<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaravanEvent extends Model
{
    use HasFactory;

    public const TYPE_BANDIT_ATTACK = 'bandit_attack';
    public const TYPE_WEATHER_DELAY = 'weather_delay';
    public const TYPE_BREAKDOWN = 'breakdown';
    public const TYPE_TOLL_PAID = 'toll_paid';
    public const TYPE_GOODS_SPOILED = 'goods_spoiled';
    public const TYPE_MERCHANT_OPPORTUNITY = 'merchant_opportunity';
    public const TYPE_GUARD_DESERTION = 'guard_desertion';
    public const TYPE_SAFE_ARRIVAL = 'safe_arrival';

    protected $fillable = [
        'caravan_id',
        'event_type',
        'description',
        'gold_lost',
        'gold_gained',
        'goods_lost',
        'guards_lost',
        'days_delayed',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'gold_lost' => 'integer',
            'gold_gained' => 'integer',
            'goods_lost' => 'integer',
            'guards_lost' => 'integer',
            'days_delayed' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the caravan.
     */
    public function caravan(): BelongsTo
    {
        return $this->belongsTo(Caravan::class);
    }

    /**
     * Get human-readable event type name.
     */
    public function getEventTypeNameAttribute(): string
    {
        return match ($this->event_type) {
            self::TYPE_BANDIT_ATTACK => 'Bandit Attack',
            self::TYPE_WEATHER_DELAY => 'Weather Delay',
            self::TYPE_BREAKDOWN => 'Cart Breakdown',
            self::TYPE_TOLL_PAID => 'Toll Paid',
            self::TYPE_GOODS_SPOILED => 'Goods Spoiled',
            self::TYPE_MERCHANT_OPPORTUNITY => 'Trading Opportunity',
            self::TYPE_GUARD_DESERTION => 'Guard Desertion',
            self::TYPE_SAFE_ARRIVAL => 'Safe Arrival',
            default => ucwords(str_replace('_', ' ', $this->event_type)),
        };
    }

    /**
     * Check if this was a negative event.
     */
    public function isNegative(): bool
    {
        return $this->gold_lost > 0 || $this->goods_lost > 0 || $this->guards_lost > 0 || $this->days_delayed > 0;
    }

    /**
     * Check if this was a positive event.
     */
    public function isPositive(): bool
    {
        return $this->gold_gained > 0 && !$this->isNegative();
    }

    /**
     * Get net gold impact.
     */
    public function getNetGoldAttribute(): int
    {
        return $this->gold_gained - $this->gold_lost;
    }
}

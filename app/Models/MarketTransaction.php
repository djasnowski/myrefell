<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketTransaction extends Model
{
    use HasFactory;

    public const TYPE_BUY = 'buy';

    public const TYPE_SELL = 'sell';

    protected $fillable = [
        'user_id',
        'location_type',
        'location_id',
        'item_id',
        'type',
        'quantity',
        'price_per_unit',
        'total_gold',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price_per_unit' => 'integer',
            'total_gold' => 'integer',
        ];
    }

    /**
     * Get the user who made this transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the item in this transaction.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the location this transaction occurred at.
     */
    public function getLocationAttribute(): Model|null
    {
        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'town' => Town::find($this->location_id),
            'barony' => Barony::find($this->location_id),
            default => null,
        };
    }

    /**
     * Scope to get transactions at a specific location.
     */
    public function scopeAtLocation($query, string $locationType, int $locationId)
    {
        return $query->where('location_type', $locationType)
            ->where('location_id', $locationId);
    }

    /**
     * Scope to get transactions for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if this is a buy transaction.
     */
    public function isBuy(): bool
    {
        return $this->type === self::TYPE_BUY;
    }

    /**
     * Check if this is a sell transaction.
     */
    public function isSell(): bool
    {
        return $this->type === self::TYPE_SELL;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StableStock extends Model
{
    protected $fillable = [
        'location_type',
        'horse_id',
        'quantity',
        'max_quantity',
        'last_restocked_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'max_quantity' => 'integer',
            'last_restocked_at' => 'datetime',
        ];
    }

    /**
     * Restock interval in hours.
     */
    public const RESTOCK_HOURS = 4;

    /**
     * Max quantity by rarity.
     */
    public const MAX_QUANTITY_BY_RARITY = [
        'common' => 5,      // 80+ rarity
        'uncommon' => 3,    // 50-79 rarity
        'rare' => 2,        // 25-49 rarity
        'epic' => 1,        // 10-24 rarity
        'legendary' => 1,   // <10 rarity
    ];

    public function horse(): BelongsTo
    {
        return $this->belongsTo(Horse::class);
    }

    /**
     * Check if this stock needs restocking.
     */
    public function needsRestock(): bool
    {
        if (! $this->last_restocked_at) {
            return true;
        }

        return $this->last_restocked_at->addHours(self::RESTOCK_HOURS)->isPast();
    }

    /**
     * Restock this item.
     */
    public function restock(): void
    {
        $this->quantity = $this->max_quantity;
        $this->last_restocked_at = now();
        $this->save();
    }

    /**
     * Decrement stock when purchased.
     */
    public function decrementStock(): bool
    {
        if ($this->quantity <= 0) {
            return false;
        }

        $this->decrement('quantity');

        return true;
    }

    /**
     * Check if in stock.
     */
    public function inStock(): bool
    {
        return $this->quantity > 0;
    }

    /**
     * Get max quantity based on horse rarity.
     */
    public static function getMaxQuantityForRarity(int $rarity): int
    {
        return match (true) {
            $rarity >= 80 => self::MAX_QUANTITY_BY_RARITY['common'],
            $rarity >= 50 => self::MAX_QUANTITY_BY_RARITY['uncommon'],
            $rarity >= 25 => self::MAX_QUANTITY_BY_RARITY['rare'],
            $rarity >= 10 => self::MAX_QUANTITY_BY_RARITY['epic'],
            default => self::MAX_QUANTITY_BY_RARITY['legendary'],
        };
    }
}

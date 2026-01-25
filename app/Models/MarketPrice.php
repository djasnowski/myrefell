<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_type',
        'location_id',
        'item_id',
        'base_price',
        'current_price',
        'supply_quantity',
        'demand_level',
        'seasonal_modifier',
        'supply_modifier',
        'last_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'integer',
            'current_price' => 'integer',
            'supply_quantity' => 'integer',
            'demand_level' => 'integer',
            'seasonal_modifier' => 'decimal:2',
            'supply_modifier' => 'decimal:2',
            'last_updated_at' => 'datetime',
        ];
    }

    /**
     * Valid location types for markets.
     */
    public const VALID_LOCATIONS = ['village', 'town', 'barony'];

    /**
     * Get the item for this market price.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the location this price belongs to.
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
     * Scope to get prices at a specific location.
     */
    public function scopeAtLocation($query, string $locationType, int $locationId)
    {
        return $query->where('location_type', $locationType)
            ->where('location_id', $locationId);
    }

    /**
     * Scope to get prices for a specific item.
     */
    public function scopeForItem($query, int $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    /**
     * Get or create a market price entry for an item at a location.
     */
    public static function getOrCreate(string $locationType, int $locationId, Item $item): self
    {
        return self::firstOrCreate(
            [
                'location_type' => $locationType,
                'location_id' => $locationId,
                'item_id' => $item->id,
            ],
            [
                'base_price' => $item->base_value ?? 10,
                'current_price' => $item->base_value ?? 10,
                'supply_quantity' => 0,
                'demand_level' => 50,
                'seasonal_modifier' => 1.00,
                'supply_modifier' => 1.00,
                'last_updated_at' => now(),
            ]
        );
    }

    /**
     * Get the buy price (what player pays to buy from market).
     * Buying is slightly more expensive due to merchant markup.
     */
    public function getBuyPriceAttribute(): int
    {
        return (int) ceil($this->current_price * 1.1); // 10% markup
    }

    /**
     * Get the sell price (what player receives when selling).
     * Selling is at a discount from current price.
     */
    public function getSellPriceAttribute(): int
    {
        return (int) floor($this->current_price * 0.8); // 20% discount
    }
}

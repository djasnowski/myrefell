<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationStockpile extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_type',
        'location_id',
        'item_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    /**
     * Get the item in this stockpile.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the location this stockpile belongs to.
     */
    public function getLocationAttribute(): Model|null
    {
        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'castle' => Castle::find($this->location_id),
            'town' => Town::find($this->location_id),
            default => null,
        };
    }

    /**
     * Check if stockpile has enough quantity.
     */
    public function hasQuantity(int $amount): bool
    {
        return $this->quantity >= $amount;
    }

    /**
     * Add quantity to stockpile.
     */
    public function addQuantity(int $amount): bool
    {
        $this->quantity += $amount;

        return $this->save();
    }

    /**
     * Remove quantity from stockpile.
     */
    public function removeQuantity(int $amount): bool
    {
        if (! $this->hasQuantity($amount)) {
            return false;
        }

        $this->quantity -= $amount;

        return $this->save();
    }

    /**
     * Scope to get stockpiles at a specific location.
     */
    public function scopeAtLocation($query, string $locationType, int $locationId)
    {
        return $query->where('location_type', $locationType)
            ->where('location_id', $locationId);
    }

    /**
     * Scope to get stockpile for a specific item.
     */
    public function scopeForItem($query, int $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    /**
     * Get or create a stockpile entry for an item at a location.
     */
    public static function getOrCreate(string $locationType, int $locationId, int $itemId): self
    {
        return self::firstOrCreate(
            [
                'location_type' => $locationType,
                'location_id' => $locationId,
                'item_id' => $itemId,
            ],
            ['quantity' => 0]
        );
    }
}

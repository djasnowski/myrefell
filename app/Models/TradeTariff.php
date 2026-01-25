<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TradeTariff extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_type',
        'location_id',
        'item_id',
        'tariff_rate',
        'is_active',
        'set_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'tariff_rate' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the item this tariff applies to.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the user who set this tariff.
     */
    public function setBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by_user_id');
    }

    /**
     * Get collections from this tariff.
     */
    public function collections(): HasMany
    {
        return $this->hasMany(TariffCollection::class);
    }

    /**
     * Get the location.
     */
    public function getLocationAttribute(): ?Model
    {
        return match ($this->location_type) {
            'barony' => Barony::find($this->location_id),
            'kingdom' => Kingdom::find($this->location_id),
            default => null,
        };
    }

    /**
     * Calculate tariff amount for a given value.
     */
    public function calculateTariff(int $value): int
    {
        return (int) ceil($value * ($this->tariff_rate / 100));
    }

    /**
     * Check if this tariff applies to a specific item.
     */
    public function appliesToItem(?int $itemId): bool
    {
        // null item_id means applies to all goods
        return $this->item_id === null || $this->item_id === $itemId;
    }

    /**
     * Scope to active tariffs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to tariffs at a location.
     */
    public function scopeAtLocation($query, string $type, int $id)
    {
        return $query->where('location_type', $type)->where('location_id', $id);
    }

    /**
     * Scope to tariffs for a specific item (or general tariffs).
     */
    public function scopeForItem($query, ?int $itemId)
    {
        return $query->where(function ($q) use ($itemId) {
            $q->whereNull('item_id')
                ->orWhere('item_id', $itemId);
        });
    }
}

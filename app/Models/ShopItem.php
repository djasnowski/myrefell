<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'item_id',
        'price',
        'stock_quantity',
        'max_stock',
        'restock_hours',
        'last_restocked_at',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'shop_id' => 'integer',
            'item_id' => 'integer',
            'price' => 'integer',
            'stock_quantity' => 'integer',
            'max_stock' => 'integer',
            'restock_hours' => 'integer',
            'last_restocked_at' => 'datetime',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the shop this item belongs to.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the item definition.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Check if this item is in stock.
     * Null stock_quantity means infinite supply.
     */
    public function inStock(): bool
    {
        if ($this->stock_quantity === null) {
            return true;
        }

        return $this->stock_quantity > 0;
    }

    /**
     * Check if this item needs restocking.
     */
    public function needsRestock(): bool
    {
        if ($this->max_stock === null || $this->restock_hours === null) {
            return false;
        }

        if ($this->stock_quantity >= $this->max_stock) {
            return false;
        }

        if ($this->last_restocked_at === null) {
            return true;
        }

        return $this->last_restocked_at->addHours($this->restock_hours)->isPast();
    }

    /**
     * Restock this item to max.
     */
    public function restock(): void
    {
        if ($this->max_stock === null) {
            return;
        }

        $this->update([
            'stock_quantity' => $this->max_stock,
            'last_restocked_at' => now(),
        ]);
    }

    /**
     * Scope to active items only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

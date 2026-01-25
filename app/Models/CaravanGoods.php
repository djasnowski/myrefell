<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaravanGoods extends Model
{
    use HasFactory;

    protected $fillable = [
        'caravan_id',
        'item_id',
        'quantity',
        'purchase_price',
        'origin_type',
        'origin_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'purchase_price' => 'integer',
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
     * Get the item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the origin location.
     */
    public function getOriginAttribute(): ?Model
    {
        return match ($this->origin_type) {
            'village' => Village::find($this->origin_id),
            'town' => Town::find($this->origin_id),
            default => null,
        };
    }

    /**
     * Get total purchase value.
     */
    public function getTotalValueAttribute(): int
    {
        return $this->quantity * $this->purchase_price;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessInventory extends Model
{
    use HasFactory;

    protected $table = 'business_inventory';

    protected $fillable = [
        'player_business_id',
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
     * Get the business this inventory belongs to.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(PlayerBusiness::class, 'player_business_id');
    }

    /**
     * Get the item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Check if there's enough quantity.
     */
    public function hasQuantity(int $amount): bool
    {
        return $this->quantity >= $amount;
    }

    /**
     * Add quantity.
     */
    public function addQuantity(int $amount): void
    {
        $this->increment('quantity', $amount);
    }

    /**
     * Remove quantity.
     */
    public function removeQuantity(int $amount): bool
    {
        if (! $this->hasQuantity($amount)) {
            return false;
        }

        $this->decrement('quantity', $amount);

        return true;
    }
}

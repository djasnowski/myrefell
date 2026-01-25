<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerHorse extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'horse_id',
        'custom_name',
        'purchase_price',
        'purchased_at',
    ];

    protected $casts = [
        'purchase_price' => 'integer',
        'purchased_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function horse(): BelongsTo
    {
        return $this->belongsTo(Horse::class);
    }

    /**
     * Get the display name (custom name or horse type name)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->custom_name ?? $this->horse->name;
    }

    /**
     * Get the speed multiplier from the horse
     */
    public function getSpeedMultiplierAttribute(): float
    {
        return (float) $this->horse->speed_multiplier;
    }

    /**
     * Get estimated sell price (50% of purchase price)
     */
    public function getSellPriceAttribute(): int
    {
        return (int) ($this->purchase_price * 0.5);
    }
}

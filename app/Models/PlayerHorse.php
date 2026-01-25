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
        'stamina',
        'max_stamina',
        'is_stabled',
        'stabled_location_type',
        'stabled_location_id',
        'purchased_at',
    ];

    protected $casts = [
        'purchase_price' => 'integer',
        'stamina' => 'integer',
        'max_stamina' => 'integer',
        'is_stabled' => 'boolean',
        'stabled_location_id' => 'integer',
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

    /**
     * Check if horse has enough stamina for travel.
     */
    public function hasStamina(int $amount): bool
    {
        return $this->stamina >= $amount;
    }

    /**
     * Consume stamina for travel.
     */
    public function consumeStamina(int $amount): bool
    {
        if (!$this->hasStamina($amount)) {
            return false;
        }

        $this->decrement('stamina', $amount);
        return true;
    }

    /**
     * Rest the horse to restore stamina (when stabled).
     */
    public function restoreStamina(int $amount): void
    {
        $this->stamina = min($this->max_stamina, $this->stamina + $amount);
        $this->save();
    }

    /**
     * Fully restore stamina.
     */
    public function fullyRest(): void
    {
        $this->stamina = $this->max_stamina;
        $this->save();
    }

    /**
     * Check if horse is available for travel (not stabled and has stamina).
     */
    public function isAvailableForTravel(): bool
    {
        return !$this->is_stabled && $this->hasStamina($this->horse->stamina_cost_per_travel);
    }

    /**
     * Get stamina cost per travel from horse type.
     */
    public function getStaminaCostAttribute(): int
    {
        return $this->horse->stamina_cost_per_travel;
    }

    /**
     * Stable the horse at current location.
     */
    public function stable(string $locationType, int $locationId): void
    {
        $this->is_stabled = true;
        $this->stabled_location_type = $locationType;
        $this->stabled_location_id = $locationId;
        $this->save();
    }

    /**
     * Retrieve horse from stable.
     */
    public function retrieve(): bool
    {
        if (!$this->is_stabled) {
            return false;
        }

        $this->is_stabled = false;
        $this->stabled_location_type = null;
        $this->stabled_location_id = null;
        $this->save();

        return true;
    }
}

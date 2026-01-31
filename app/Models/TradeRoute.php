<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TradeRoute extends Model
{
    use HasFactory;

    public const DANGER_SAFE = 'safe';

    public const DANGER_MODERATE = 'moderate';

    public const DANGER_DANGEROUS = 'dangerous';

    public const DANGER_PERILOUS = 'perilous';

    protected $fillable = [
        'name',
        'origin_type',
        'origin_id',
        'destination_type',
        'destination_id',
        'distance',
        'base_travel_days',
        'danger_level',
        'bandit_chance',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'distance' => 'integer',
            'base_travel_days' => 'integer',
            'bandit_chance' => 'integer',
            'is_active' => 'boolean',
        ];
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
     * Get the destination location.
     */
    public function getDestinationAttribute(): ?Model
    {
        return match ($this->destination_type) {
            'village' => Village::find($this->destination_id),
            'town' => Town::find($this->destination_id),
            default => null,
        };
    }

    /**
     * Get caravans using this route.
     */
    public function caravans(): HasMany
    {
        return $this->hasMany(Caravan::class);
    }

    /**
     * Get the origin settlement (polymorphic).
     */
    public function originSettlement(): MorphTo
    {
        return $this->morphTo('originSettlement', 'origin_type', 'origin_id');
    }

    /**
     * Get the destination settlement (polymorphic).
     */
    public function destinationSettlement(): MorphTo
    {
        return $this->morphTo('destinationSettlement', 'destination_type', 'destination_id');
    }

    /**
     * Get danger multiplier for bandit chance.
     */
    public function getDangerMultiplierAttribute(): float
    {
        return match ($this->danger_level) {
            self::DANGER_SAFE => 1.0,
            self::DANGER_MODERATE => 1.5,
            self::DANGER_DANGEROUS => 2.5,
            self::DANGER_PERILOUS => 4.0,
            default => 1.0,
        };
    }

    /**
     * Calculate effective bandit chance based on danger level.
     */
    public function getEffectiveBanditChanceAttribute(): int
    {
        return (int) ($this->bandit_chance * $this->danger_multiplier);
    }

    /**
     * Scope to active routes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to routes from a specific origin.
     */
    public function scopeFromOrigin($query, string $type, int $id)
    {
        return $query->where('origin_type', $type)->where('origin_id', $id);
    }

    /**
     * Scope to routes to a specific destination.
     */
    public function scopeToDestination($query, string $type, int $id)
    {
        return $query->where('destination_type', $type)->where('destination_id', $id);
    }
}

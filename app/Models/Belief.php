<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Belief extends Model
{
    use HasFactory;

    public const TYPE_VIRTUE = 'virtue';

    public const TYPE_VICE = 'vice';

    public const TYPE_NEUTRAL = 'neutral';

    public const TYPES = [
        self::TYPE_VIRTUE,
        self::TYPE_VICE,
        self::TYPE_NEUTRAL,
    ];

    protected $fillable = [
        'name',
        'description',
        'icon',
        'effects',
        'type',
        'cult_only',
        'required_hideout_tier',
        'hp_cost',
        'energy_cost',
    ];

    protected function casts(): array
    {
        return [
            'effects' => 'array',
            'cult_only' => 'boolean',
            'required_hideout_tier' => 'integer',
            'hp_cost' => 'integer',
            'energy_cost' => 'integer',
        ];
    }

    /**
     * Get religions that have adopted this belief.
     */
    public function religions(): BelongsToMany
    {
        return $this->belongsToMany(Religion::class, 'religion_beliefs')
            ->withTimestamps();
    }

    /**
     * Get the effect value for a specific stat.
     */
    public function getEffect(string $stat): int
    {
        return $this->effects[$stat] ?? 0;
    }

    /**
     * Check if this is a positive belief.
     */
    public function isVirtue(): bool
    {
        return $this->type === self::TYPE_VIRTUE;
    }

    /**
     * Check if this is a negative belief.
     */
    public function isVice(): bool
    {
        return $this->type === self::TYPE_VICE;
    }

    /**
     * Check if this is a cult-only belief (Forbidden Art).
     */
    public function isCultOnly(): bool
    {
        return $this->cult_only === true;
    }

    /**
     * Get the HP cost for activating this belief (cult beliefs only).
     */
    public function getHpCost(): int
    {
        if ($this->hp_cost !== null) {
            return $this->hp_cost;
        }

        // Default HP costs based on hideout tier
        return match (true) {
            $this->required_hideout_tier <= 2 => 5,
            $this->required_hideout_tier <= 4 => 10,
            default => 15,
        };
    }

    /**
     * Get the energy cost for activating this belief.
     */
    public function getEnergyCost(): int
    {
        if ($this->energy_cost !== null) {
            return $this->energy_cost;
        }

        // Default energy costs based on hideout tier
        return match (true) {
            $this->required_hideout_tier <= 2 => 10,
            $this->required_hideout_tier <= 3 => 15,
            $this->required_hideout_tier <= 4 => 20,
            default => 30,
        };
    }

    /**
     * Scope to get only cult-only beliefs.
     */
    public function scopeCultOnly($query)
    {
        return $query->where('cult_only', true);
    }

    /**
     * Scope to get regular (non-cult) beliefs.
     */
    public function scopeRegular($query)
    {
        return $query->where('cult_only', false);
    }

    /**
     * Scope to get beliefs available for a specific hideout tier.
     */
    public function scopeAvailableForHideoutTier($query, int $tier)
    {
        return $query->cultOnly()
            ->where('required_hideout_tier', '<=', $tier);
    }
}

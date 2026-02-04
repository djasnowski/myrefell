<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReligionHqFeature extends Model
{
    protected $fillable = [
        'religion_hq_id',
        'hq_feature_type_id',
        'level',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
        ];
    }

    /**
     * Get the headquarters this feature belongs to.
     */
    public function headquarters(): BelongsTo
    {
        return $this->belongsTo(ReligionHeadquarters::class, 'religion_hq_id');
    }

    /**
     * Get the feature type.
     */
    public function featureType(): BelongsTo
    {
        return $this->belongsTo(HqFeatureType::class, 'hq_feature_type_id');
    }

    /**
     * Get the effects for this feature at its current level.
     *
     * @return array<string, int|float>
     */
    public function getEffects(): array
    {
        return $this->featureType->getEffectsForLevel($this->level);
    }

    /**
     * Check if this feature can be upgraded.
     */
    public function canUpgrade(): bool
    {
        return $this->level < $this->featureType->max_level;
    }

    /**
     * Get the cost to upgrade to the next level.
     *
     * @return array{gold: int, devotion: int, items: array}|null
     */
    public function getUpgradeCost(): ?array
    {
        if (! $this->canUpgrade()) {
            return null;
        }

        return $this->featureType->getCostForLevel($this->level + 1);
    }

    /**
     * Get the effects that will be gained at the next level.
     *
     * @return array<string, int|float>|null
     */
    public function getNextLevelEffects(): ?array
    {
        if (! $this->canUpgrade()) {
            return null;
        }

        return $this->featureType->getEffectsForLevel($this->level + 1);
    }

    /**
     * Get the energy cost to pray at this feature.
     */
    public function getPrayerEnergyCost(): int
    {
        return HqFeatureType::PRAYER_ENERGY_COST[$this->featureType->min_hq_tier] ?? 25;
    }

    /**
     * Get the devotion cost to pray at this feature.
     */
    public function getPrayerDevotionCost(): int
    {
        return HqFeatureType::PRAYER_DEVOTION_COST[$this->level] ?? 50;
    }

    /**
     * Get the buff duration in minutes for this feature.
     */
    public function getPrayerDurationMinutes(): int
    {
        return HqFeatureType::PRAYER_DURATION_MINUTES[$this->level] ?? 5;
    }
}

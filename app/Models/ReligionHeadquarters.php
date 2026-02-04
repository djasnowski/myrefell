<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReligionHeadquarters extends Model
{
    public const TIER_CHAPEL = 1;

    public const TIER_CHURCH = 2;

    public const TIER_TEMPLE = 3;

    public const TIER_CATHEDRAL = 4;

    public const TIER_GRAND_CATHEDRAL = 5;

    public const TIER_HOLY_SANCTUM = 6;

    public const MAX_TIER = 6;

    public const TIER_NAMES = [
        1 => 'Chapel',
        2 => 'Church',
        3 => 'Temple',
        4 => 'Cathedral',
        5 => 'Grand Cathedral',
        6 => 'Holy Sanctum',
    ];

    public const TIER_COSTS = [
        1 => ['gold' => 0, 'devotion' => 0, 'items' => []],
        2 => ['gold' => 100_000, 'devotion' => 5_000, 'items' => []],
        3 => ['gold' => 500_000, 'devotion' => 25_000, 'items' => []],
        4 => ['gold' => 2_000_000, 'devotion' => 100_000, 'items' => []],
        5 => ['gold' => 10_000_000, 'devotion' => 500_000, 'items' => []],
        6 => ['gold' => 50_000_000, 'devotion' => 2_000_000, 'items' => []],
    ];

    public const TIER_PRAYER_REQUIREMENTS = [
        1 => 1,
        2 => 15,
        3 => 30,
        4 => 50,
        5 => 70,
        6 => 90,
    ];

    public const TIER_BONUSES = [
        1 => ['blessing_cost' => 0, 'blessing_duration' => 0, 'devotion_gain' => 0],
        2 => ['blessing_cost' => -5, 'blessing_duration' => 10, 'devotion_gain' => 5],
        3 => ['blessing_cost' => -10, 'blessing_duration' => 20, 'devotion_gain' => 10],
        4 => ['blessing_cost' => -15, 'blessing_duration' => 30, 'devotion_gain' => 20],
        5 => ['blessing_cost' => -25, 'blessing_duration' => 50, 'devotion_gain' => 35],
        6 => ['blessing_cost' => -40, 'blessing_duration' => 75, 'devotion_gain' => 50],
    ];

    protected $fillable = [
        'religion_id',
        'location_type',
        'location_id',
        'tier',
        'name',
        'total_devotion_invested',
        'total_gold_invested',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tier' => 'integer',
            'total_devotion_invested' => 'integer',
            'total_gold_invested' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the religion this HQ belongs to.
     */
    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    /**
     * Get all built features in this HQ.
     */
    public function features(): HasMany
    {
        return $this->hasMany(ReligionHqFeature::class, 'religion_hq_id');
    }

    /**
     * Get all construction projects for this HQ.
     */
    public function constructionProjects(): HasMany
    {
        return $this->hasMany(HqConstructionProject::class, 'religion_hq_id');
    }

    /**
     * Get all currently active construction projects (including those under construction).
     */
    public function activeProjects(): HasMany
    {
        return $this->hasMany(HqConstructionProject::class, 'religion_hq_id')
            ->whereIn('status', ['pending', 'in_progress', 'constructing']);
    }

    /**
     * Get the active HQ upgrade project (only one allowed at a time).
     */
    public function activeHqUpgrade(): HasOne
    {
        return $this->hasOne(HqConstructionProject::class, 'religion_hq_id')
            ->where('project_type', 'hq_upgrade')
            ->whereIn('status', ['pending', 'in_progress', 'constructing']);
    }

    /**
     * Check if a feature is already being built or upgraded.
     */
    public function hasActiveProjectForFeature(int $featureTypeId): bool
    {
        return $this->activeProjects()
            ->where('hq_feature_type_id', $featureTypeId)
            ->exists();
    }

    /**
     * Get the location model (polymorphic).
     */
    public function getLocationAttribute(): ?Model
    {
        if (! $this->location_type || ! $this->location_id) {
            return null;
        }

        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'barony' => Barony::find($this->location_id),
            'town' => Town::find($this->location_id),
            'kingdom' => Kingdom::find($this->location_id),
            default => null,
        };
    }

    /**
     * Get the location name.
     */
    public function getLocationNameAttribute(): string
    {
        return $this->location?->name ?? 'Not yet built';
    }

    /**
     * Get the tier name.
     */
    public function getTierNameAttribute(): string
    {
        return self::TIER_NAMES[$this->tier] ?? 'Unknown';
    }

    /**
     * Get tier bonuses.
     *
     * @return array{blessing_cost: int, blessing_duration: int, devotion_gain: int}
     */
    public function getTierBonuses(): array
    {
        return self::TIER_BONUSES[$this->tier] ?? self::TIER_BONUSES[1];
    }

    /**
     * Get the cost to upgrade to the next tier.
     *
     * @return array{gold: int, devotion: int, items: array}|null
     */
    public function getUpgradeCost(): ?array
    {
        $nextTier = $this->tier + 1;

        if ($nextTier > self::MAX_TIER) {
            return null;
        }

        return self::TIER_COSTS[$nextTier];
    }

    /**
     * Check if the HQ can be upgraded.
     */
    public function canUpgrade(): bool
    {
        return $this->tier < self::MAX_TIER
            && ! $this->activeHqUpgrade()->exists()
            && $this->location_type !== null;
    }

    /**
     * Get the required Prayer level to upgrade to the next tier.
     */
    public function getNextTierPrayerRequirement(): ?int
    {
        $nextTier = $this->tier + 1;

        if ($nextTier > self::MAX_TIER) {
            return null;
        }

        return self::TIER_PRAYER_REQUIREMENTS[$nextTier];
    }

    /**
     * Get the required Prayer level for the current tier.
     */
    public function getCurrentTierPrayerRequirement(): int
    {
        return self::TIER_PRAYER_REQUIREMENTS[$this->tier] ?? 1;
    }

    /**
     * Check if the HQ has been built at a location.
     */
    public function isBuilt(): bool
    {
        return $this->location_type !== null && $this->location_id !== null;
    }

    /**
     * Get combined effects from tier bonuses and all features.
     *
     * @return array<string, int|float>
     */
    public function getCombinedEffects(): array
    {
        $effects = [];

        // Start with tier bonuses
        $tierBonuses = $this->getTierBonuses();
        if ($tierBonuses['blessing_cost'] !== 0) {
            $effects['blessing_cost_reduction'] = abs($tierBonuses['blessing_cost']);
        }
        if ($tierBonuses['blessing_duration'] !== 0) {
            $effects['blessing_duration_bonus'] = $tierBonuses['blessing_duration'];
        }
        if ($tierBonuses['devotion_gain'] !== 0) {
            $effects['devotion_bonus'] = $tierBonuses['devotion_gain'];
        }

        // Add feature effects
        foreach ($this->features as $feature) {
            $featureEffects = $feature->getEffects();
            foreach ($featureEffects as $key => $value) {
                $effects[$key] = ($effects[$key] ?? 0) + $value;
            }
        }

        return $effects;
    }

    /**
     * Get available features that can be built at this HQ tier.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, HqFeatureType>
     */
    public function getAvailableFeatures()
    {
        $builtFeatureTypeIds = $this->features->pluck('hq_feature_type_id')->toArray();

        return HqFeatureType::where('min_hq_tier', '<=', $this->tier)
            ->whereNotIn('id', $builtFeatureTypeIds)
            ->orderBy('category')
            ->orderBy('min_hq_tier')
            ->get();
    }

    /**
     * Get features that can be upgraded.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ReligionHqFeature>
     */
    public function getUpgradeableFeatures()
    {
        return $this->features()
            ->with('featureType')
            ->get()
            ->filter(fn ($feature) => $feature->canUpgrade());
    }
}

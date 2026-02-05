<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Religion extends Model
{
    use HasFactory;

    public const TYPE_CULT = 'cult';

    public const TYPE_RELIGION = 'religion';

    public const TYPES = [
        self::TYPE_CULT,
        self::TYPE_RELIGION,
    ];

    // Cult limits
    public const CULT_MEMBER_LIMIT = 5;

    public const CULT_BELIEF_LIMIT = 2;

    public const CULT_FOUNDING_COST = 0;

    // Religion limits/requirements
    public const RELIGION_FOUNDING_COST = 100000;

    public const RELIGION_MIN_MEMBERS = 15;

    public const RELIGION_BELIEF_LIMIT = 5;

    // Cult hideout tier constants
    public const HIDEOUT_TIER_NONE = 0;

    public const HIDEOUT_TIER_HIDDEN_CELLAR = 1;

    public const HIDEOUT_TIER_UNDERGROUND_DEN = 2;

    public const HIDEOUT_TIER_SECRET_SANCTUM = 3;

    public const HIDEOUT_TIER_SHADOW_TEMPLE = 4;

    public const HIDEOUT_TIER_DARK_CITADEL = 5;

    public const HIDEOUT_MAX_TIER = 5;

    /**
     * Hideout tier names and costs.
     */
    public const HIDEOUT_TIERS = [
        1 => ['name' => 'Hidden Cellar', 'gold' => 0, 'devotion' => 0],
        2 => ['name' => 'Underground Den', 'gold' => 50_000, 'devotion' => 2_500],
        3 => ['name' => 'Secret Sanctum', 'gold' => 200_000, 'devotion' => 10_000],
        4 => ['name' => 'Shadow Temple', 'gold' => 750_000, 'devotion' => 35_000],
        5 => ['name' => 'Dark Citadel', 'gold' => 2_000_000, 'devotion' => 100_000],
    ];

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color',
        'type',
        'founder_id',
        'is_public',
        'member_limit',
        'founding_cost',
        'is_active',
        'hideout_tier',
        'hideout_location_type',
        'hideout_location_id',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_active' => 'boolean',
            'member_limit' => 'integer',
            'founding_cost' => 'integer',
            'hideout_tier' => 'integer',
            'hideout_location_id' => 'integer',
        ];
    }

    /**
     * Get the founder of the religion.
     */
    public function founder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'founder_id');
    }

    /**
     * Get the beliefs of this religion.
     */
    public function beliefs(): BelongsToMany
    {
        return $this->belongsToMany(Belief::class, 'religion_beliefs')
            ->withTimestamps();
    }

    /**
     * Get the members of this religion.
     */
    public function members(): HasMany
    {
        return $this->hasMany(ReligionMember::class);
    }

    /**
     * Get the structures of this religion.
     */
    public function structures(): HasMany
    {
        return $this->hasMany(ReligiousStructure::class);
    }

    /**
     * Get kingdom relations for this religion.
     */
    public function kingdomRelations(): HasMany
    {
        return $this->hasMany(KingdomReligion::class);
    }

    /**
     * Get the actions log for this religion.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(ReligiousAction::class);
    }

    /**
     * Get the history logs for this religion.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ReligionLog::class);
    }

    /**
     * Get the treasury for this religion.
     */
    public function treasury(): HasOne
    {
        return $this->hasOne(ReligionTreasury::class);
    }

    /**
     * Get the headquarters for this religion.
     */
    public function headquarters(): HasOne
    {
        return $this->hasOne(ReligionHeadquarters::class);
    }

    /**
     * Get the hideout projects for this cult.
     */
    public function hideoutProjects(): HasMany
    {
        return $this->hasMany(CultHideoutProject::class);
    }

    /**
     * Get active hideout upgrade project.
     */
    public function activeHideoutProject(): HasOne
    {
        return $this->hasOne(CultHideoutProject::class)
            ->whereIn('status', ['pending', 'in_progress', 'constructing']);
    }

    /**
     * Check if this is a cult.
     */
    public function isCult(): bool
    {
        return $this->type === self::TYPE_CULT;
    }

    /**
     * Check if this is a full religion.
     */
    public function isReligion(): bool
    {
        return $this->type === self::TYPE_RELIGION;
    }

    /**
     * Get the max number of beliefs allowed.
     */
    public function getBeliefLimitAttribute(): int
    {
        return $this->isCult() ? self::CULT_BELIEF_LIMIT : self::RELIGION_BELIEF_LIMIT;
    }

    /**
     * Check if the religion can accept more members.
     */
    public function canAcceptMembers(): bool
    {
        return true; // No member limit for cults or religions
    }

    /**
     * Check if the cult can be converted to a religion.
     */
    public function canConvertToReligion(): bool
    {
        return $this->isCult()
            && $this->members()->count() >= self::RELIGION_MIN_MEMBERS;
    }

    /**
     * Get member count.
     */
    public function getMemberCountAttribute(): int
    {
        return $this->members()->count();
    }

    /**
     * Get the prophet (founder/leader).
     */
    public function getProphet(): ?ReligionMember
    {
        return $this->members()->where('rank', ReligionMember::RANK_PROPHET)->first();
    }

    /**
     * Get priests (officers).
     */
    public function getPriests()
    {
        return $this->members()->where('rank', ReligionMember::RANK_PRIEST)->get();
    }

    /**
     * Calculate combined belief effects.
     */
    public function getCombinedEffects(): array
    {
        $effects = [];

        foreach ($this->beliefs as $belief) {
            if (! $belief->effects) {
                continue;
            }

            foreach ($belief->effects as $stat => $value) {
                $effects[$stat] = ($effects[$stat] ?? 0) + $value;
            }
        }

        return $effects;
    }

    /**
     * Check if this cult has a hideout.
     */
    public function hasHideout(): bool
    {
        return $this->isCult() && $this->hideout_tier > 0;
    }

    /**
     * Get the hideout tier name.
     */
    public function getHideoutName(): ?string
    {
        if (! $this->hasHideout()) {
            return null;
        }

        return self::HIDEOUT_TIERS[$this->hideout_tier]['name'] ?? null;
    }

    /**
     * Get the hideout location model.
     */
    public function getHideoutLocationAttribute(): ?Model
    {
        if (! $this->hideout_location_type || ! $this->hideout_location_id) {
            return null;
        }

        return match ($this->hideout_location_type) {
            'village' => Village::find($this->hideout_location_id),
            'barony' => Barony::find($this->hideout_location_id),
            'town' => Town::find($this->hideout_location_id),
            'kingdom' => Kingdom::find($this->hideout_location_id),
            default => null,
        };
    }

    /**
     * Get the hideout location name.
     */
    public function getHideoutLocationNameAttribute(): ?string
    {
        return $this->hideout_location?->name;
    }

    /**
     * Check if the hideout can be upgraded.
     */
    public function canUpgradeHideout(): bool
    {
        return $this->isCult()
            && $this->hasHideout()
            && $this->hideout_tier < self::HIDEOUT_MAX_TIER
            && ! $this->activeHideoutProject()->exists();
    }

    /**
     * Get the cost to upgrade to the next hideout tier.
     */
    public function getHideoutUpgradeCost(): ?array
    {
        $nextTier = $this->hideout_tier + 1;

        if ($nextTier > self::HIDEOUT_MAX_TIER) {
            return null;
        }

        return self::HIDEOUT_TIERS[$nextTier] ?? null;
    }

    /**
     * Get beliefs available for this cult's hideout tier.
     */
    public function getAvailableCultBeliefs()
    {
        if (! $this->isCult() || ! $this->hasHideout()) {
            return collect();
        }

        return Belief::availableForHideoutTier($this->hideout_tier)->get();
    }
}

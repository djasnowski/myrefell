<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_active' => 'boolean',
            'member_limit' => 'integer',
            'founding_cost' => 'integer',
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
        if ($this->isCult()) {
            return $this->members()->count() < $this->member_limit;
        }

        return true; // Religions have no member limit
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
            if (!$belief->effects) {
                continue;
            }

            foreach ($belief->effects as $stat => $value) {
                $effects[$stat] = ($effects[$stat] ?? 0) + $value;
            }
        }

        return $effects;
    }
}

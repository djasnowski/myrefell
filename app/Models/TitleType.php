<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TitleType extends Model
{
    // Categories
    public const CATEGORY_COMMONER = 'commoner';

    public const CATEGORY_MINOR_NOBILITY = 'minor_nobility';

    public const CATEGORY_LANDED_NOBILITY = 'landed_nobility';

    public const CATEGORY_ROYALTY = 'royalty';

    // Progression types
    public const PROGRESSION_AUTOMATIC = 'automatic';

    public const PROGRESSION_PETITION = 'petition';

    public const PROGRESSION_APPOINTMENT = 'appointment';

    public const PROGRESSION_SPECIAL = 'special';

    protected $fillable = [
        'name',
        'slug',
        'tier',
        'category',
        'is_landed',
        'domain_type',
        'limit_per_domain',
        'limit_per_superior',
        'granted_by',
        'progression_type',
        'requirements',
        'can_purchase',
        'purchase_cost',
        'service_days_required',
        'service_title_slug',
        'requires_ceremony',
        'style_of_address',
        'female_variant',
        'description',
        'prestige_bonus',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tier' => 'integer',
            'is_landed' => 'boolean',
            'limit_per_domain' => 'integer',
            'limit_per_superior' => 'integer',
            'requirements' => 'array',
            'can_purchase' => 'boolean',
            'purchase_cost' => 'integer',
            'service_days_required' => 'integer',
            'requires_ceremony' => 'boolean',
            'prestige_bonus' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Check if this title can be automatically granted.
     */
    public function isAutomatic(): bool
    {
        return $this->progression_type === self::PROGRESSION_AUTOMATIC;
    }

    /**
     * Check if this title requires a petition.
     */
    public function requiresPetition(): bool
    {
        return $this->progression_type === self::PROGRESSION_PETITION;
    }

    /**
     * Check if this title is appointment-only.
     */
    public function isAppointmentOnly(): bool
    {
        return $this->progression_type === self::PROGRESSION_APPOINTMENT;
    }

    /**
     * Check if this title has special rules.
     */
    public function hasSpecialRules(): bool
    {
        return $this->progression_type === self::PROGRESSION_SPECIAL;
    }

    /**
     * Check if a user meets the requirements for this title.
     */
    public function userMeetsRequirements(User $user): array
    {
        $requirements = $this->requirements ?? [];
        $met = [];
        $unmet = [];

        foreach ($requirements as $key => $value) {
            if ($key === 'or_conditions') {
                // At least one OR condition must be met
                $orMet = false;
                foreach ($value as $condition) {
                    // Check each condition - this is simplified, would need full implementation
                    $orMet = true; // Placeholder
                }
                if ($orMet) {
                    $met['or_conditions'] = true;
                } else {
                    $unmet['or_conditions'] = $value;
                }

                continue;
            }

            $passes = match ($key) {
                'min_gold' => $user->gold >= $value,
                'min_combat_level' => ($user->attack + $user->strength + $user->defense) / 3 >= $value,
                'min_title_tier' => $user->title_tier >= $value,
                'current_title' => $user->primary_title === $value,
                'social_class' => $user->social_class === $value,
                'owns_property' => $user->homeVillage !== null, // Simplified
                default => true, // Unknown requirements pass by default
            };

            if ($passes) {
                $met[$key] = $value;
            } else {
                $unmet[$key] = $value;
            }
        }

        return [
            'meets_all' => empty($unmet),
            'met' => $met,
            'unmet' => $unmet,
        ];
    }

    /**
     * Get all player titles of this type.
     */
    public function playerTitles(): HasMany
    {
        return $this->hasMany(PlayerTitle::class);
    }

    /**
     * Scope to active title types.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to a specific category.
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to landed titles only.
     */
    public function scopeLanded(Builder $query): Builder
    {
        return $query->where('is_landed', true);
    }

    /**
     * Scope to honorary (non-landed) titles only.
     */
    public function scopeHonorary(Builder $query): Builder
    {
        return $query->where('is_landed', false);
    }

    /**
     * Get title types that can grant this title.
     */
    public function getGrantedByTitlesAttribute(): array
    {
        if (empty($this->granted_by)) {
            return [];
        }

        return explode(',', $this->granted_by);
    }

    /**
     * Check if a specific title type can grant this title.
     */
    public function canBeGrantedBy(string $titleSlug): bool
    {
        return in_array($titleSlug, $this->granted_by_titles);
    }

    /**
     * Get the appropriate display name based on gender.
     */
    public function getDisplayName(bool $isFemale = false): string
    {
        if ($isFemale && $this->female_variant) {
            return $this->female_variant;
        }

        return $this->name;
    }

    /**
     * Get the full styled name for a person.
     */
    public function getStyledName(string $name, bool $isFemale = false): string
    {
        $style = $this->style_of_address;
        $title = $this->getDisplayName($isFemale);

        if ($style) {
            return "{$style} {$name}, {$title}";
        }

        return "{$title} {$name}";
    }

    /**
     * Count current holders of this title in a domain.
     */
    public function countInDomain(string $domainType, int $domainId): int
    {
        return PlayerTitle::where('title_type_id', $this->id)
            ->where('domain_type', $domainType)
            ->where('domain_id', $domainId)
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->count();
    }

    /**
     * Count current holders granted by a specific superior.
     */
    public function countGrantedBy(int $grantedByUserId): int
    {
        return PlayerTitle::where('title_type_id', $this->id)
            ->where('granted_by_user_id', $grantedByUserId)
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->count();
    }

    /**
     * Check if limit is reached for a domain.
     */
    public function isLimitReachedInDomain(string $domainType, int $domainId): bool
    {
        if (! $this->limit_per_domain) {
            return false;
        }

        return $this->countInDomain($domainType, $domainId) >= $this->limit_per_domain;
    }

    /**
     * Check if limit is reached for a superior.
     */
    public function isLimitReachedForSuperior(int $grantedByUserId): bool
    {
        if (! $this->limit_per_superior) {
            return false;
        }

        return $this->countGrantedBy($grantedByUserId) >= $this->limit_per_superior;
    }

    /**
     * Get available slots in a domain.
     */
    public function availableSlotsInDomain(string $domainType, int $domainId): ?int
    {
        if (! $this->limit_per_domain) {
            return null; // Unlimited
        }

        return max(0, $this->limit_per_domain - $this->countInDomain($domainType, $domainId));
    }

    /**
     * Find a title type by slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}

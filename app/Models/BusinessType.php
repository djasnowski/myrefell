<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessType extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'production' => 'Production',
        'service' => 'Service',
        'extraction' => 'Extraction',
    ];

    public const LOCATION_TYPES = [
        'village' => 'Village',
        'town' => 'Town',
        'barony' => 'Barony',
    ];

    protected $fillable = [
        'name',
        'icon',
        'description',
        'category',
        'location_type',
        'purchase_cost',
        'weekly_upkeep',
        'max_employees',
        'primary_skill',
        'required_skill_level',
        'produces',
        'requires',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'purchase_cost' => 'integer',
            'weekly_upkeep' => 'integer',
            'max_employees' => 'integer',
            'required_skill_level' => 'integer',
            'produces' => 'array',
            'requires' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get all player businesses of this type.
     */
    public function playerBusinesses(): HasMany
    {
        return $this->hasMany(PlayerBusiness::class);
    }

    /**
     * Get display text for the category.
     */
    public function getCategoryDisplayAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * Get display text for the location type.
     */
    public function getLocationTypeDisplayAttribute(): string
    {
        return self::LOCATION_TYPES[$this->location_type] ?? $this->location_type;
    }

    /**
     * Check if player meets requirements to own this business type.
     */
    public function playerMeetsRequirements(User $user): bool
    {
        if (! $this->primary_skill) {
            return true;
        }

        $skillLevel = $user->getSkillLevel($this->primary_skill);

        return $skillLevel >= $this->required_skill_level;
    }

    /**
     * Count active businesses of this type at a location.
     */
    public function countAtLocation(string $locationType, int $locationId): int
    {
        return $this->playerBusinesses()
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('status', 'active')
            ->count();
    }
}

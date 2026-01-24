<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmploymentJob extends Model
{
    use HasFactory;

    /**
     * Job categories.
     */
    public const CATEGORIES = [
        'service' => 'Service',
        'labor' => 'Labor',
        'skilled' => 'Skilled',
    ];

    /**
     * Location types where jobs can exist.
     */
    public const LOCATION_TYPES = [
        'village' => 'Village',
        'castle' => 'Castle',
        'town' => 'Town',
    ];

    protected $fillable = [
        'name',
        'icon',
        'description',
        'category',
        'location_type',
        'energy_cost',
        'base_wage',
        'xp_reward',
        'xp_skill',
        'required_skill',
        'required_skill_level',
        'required_level',
        'cooldown_minutes',
        'is_active',
        'max_workers',
    ];

    protected function casts(): array
    {
        return [
            'energy_cost' => 'integer',
            'base_wage' => 'integer',
            'xp_reward' => 'integer',
            'required_skill_level' => 'integer',
            'required_level' => 'integer',
            'cooldown_minutes' => 'integer',
            'is_active' => 'boolean',
            'max_workers' => 'integer',
        ];
    }

    /**
     * Get player employment records for this job.
     */
    public function playerEmployment(): HasMany
    {
        return $this->hasMany(PlayerEmployment::class);
    }

    /**
     * Check if player meets requirements for this job.
     */
    public function playerMeetsRequirements(User $user): bool
    {
        // Check combat level
        if ($user->combat_level < $this->required_level) {
            return false;
        }

        // Check skill requirement
        if ($this->required_skill) {
            $skillLevel = $user->getSkillLevel($this->required_skill);
            if ($skillLevel < $this->required_skill_level) {
                return false;
            }
        }

        return true;
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
     * Count current workers at a location.
     */
    public function countWorkersAtLocation(string $locationType, int $locationId): int
    {
        return $this->playerEmployment()
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('status', 'employed')
            ->count();
    }

    /**
     * Check if there are available slots at a location.
     */
    public function hasAvailableSlots(string $locationType, int $locationId): bool
    {
        return $this->countWorkersAtLocation($locationType, $locationId) < $this->max_workers;
    }
}

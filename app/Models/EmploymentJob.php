<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'town' => 'Town',
        'barony' => 'Barony',
        'duchy' => 'Duchy',
        'kingdom' => 'Kingdom',
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
        'supervisor_role_slug',
        'supervisor_cut_percent',
        'produces_item',
        'production_chance',
        'production_quantity',
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
            'supervisor_cut_percent' => 'integer',
            'production_chance' => 'integer',
            'production_quantity' => 'integer',
        ];
    }

    /**
     * Get the supervising role for this job.
     */
    public function getSupervisorRole(): ?Role
    {
        if (!$this->supervisor_role_slug) {
            return null;
        }

        return Role::where('slug', $this->supervisor_role_slug)->first();
    }

    /**
     * Get the supervisor (player holding the role) at a specific location.
     */
    public function getSupervisorAtLocation(string $locationType, int $locationId): ?User
    {
        if (!$this->supervisor_role_slug) {
            return null;
        }

        $role = $this->getSupervisorRole();
        if (!$role) {
            return null;
        }

        $playerRole = PlayerRole::active()
            ->where('role_id', $role->id)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->with('user')
            ->first();

        return $playerRole?->user;
    }

    /**
     * Get the item this job produces.
     */
    public function getProducedItem(): ?Item
    {
        if (!$this->produces_item) {
            return null;
        }

        return Item::where('name', $this->produces_item)->first();
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

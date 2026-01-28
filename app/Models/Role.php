<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    /**
     * Location types where roles can exist.
     */
    public const LOCATION_TYPES = [
        'village' => 'Village',
        'hamlet' => 'Hamlet',
        'barony' => 'Barony',
        'town' => 'Town',
        'duchy' => 'Duchy',
        'kingdom' => 'Kingdom',
    ];

    /**
     * Village roles.
     */
    public const VILLAGE_ROLES = [
        'elder',
        'blacksmith',
        'merchant',
        'guard_captain',
        'healer',
    ];

    /**
     * Barony roles.
     */
    public const BARONY_ROLES = [
        'baron',
        'steward',
        'marshal',
        'treasurer',
        'jailsman',
        'physician',
        'master_cook',
        'castellan',
        'court_chaplain',
    ];

    /**
     * Town roles.
     */
    public const TOWN_ROLES = [
        'mayor',
        'town_guard_captain',
        'guildmaster',
        'magistrate',
        'harbormaster',
        'town_clerk',
        'market_warden',
        'town_physician',
        'head_chef',
        'town_crier',
        // Skilled trades
        'master_blacksmith',
        'weaponsmith',
        'armorsmith',
        'master_jeweler',
        'master_tailor',
        'master_carpenter',
        'alchemist',
        'tanner',
        'scribe',
        'master_miner',
        'master_fisher',
        'brewmaster',
    ];

    /**
     * Duchy roles.
     */
    public const DUCHY_ROLES = [
        'duke',
        'duchy_chancellor',
        'duchy_marshal',
        'duchy_treasurer',
        'duchy_physician',
        'duchy_chef',
        'high_priest',
        'master_of_hunts',
    ];

    /**
     * Kingdom roles.
     */
    public const KINGDOM_ROLES = [
        'king',
        'chancellor',
        'general',
        'royal_treasurer',
        'archbishop',
        'royal_steward',
        'lord_marshal',
        'master_of_laws',
        'spymaster',
        'royal_herald',
        'royal_physician',
        'royal_chef',
    ];

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'location_type',
        'permissions',
        'bonuses',
        'salary',
        'tier',
        'is_elected',
        'is_active',
        'max_per_location',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'bonuses' => 'array',
            'salary' => 'integer',
            'tier' => 'integer',
            'is_elected' => 'boolean',
            'is_active' => 'boolean',
            'max_per_location' => 'integer',
        ];
    }

    /**
     * Get all player role assignments for this role.
     */
    public function playerRoles(): HasMany
    {
        return $this->hasMany(PlayerRole::class);
    }

    /**
     * Get active player role assignments for this role.
     */
    public function activePlayerRoles(): HasMany
    {
        return $this->hasMany(PlayerRole::class)->where('status', 'active');
    }

    /**
     * Get NPC fallbacks for this role.
     */
    public function locationNpcs(): HasMany
    {
        return $this->hasMany(LocationNpc::class);
    }

    /**
     * Check if a permission is granted by this role.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Get a bonus value by key.
     */
    public function getBonus(string $key, mixed $default = null): mixed
    {
        return $this->bonuses[$key] ?? $default;
    }

    /**
     * Check if this role is available at a location.
     */
    public function isAvailableAt(string $locationType): bool
    {
        return $this->location_type === $locationType && $this->is_active;
    }

    /**
     * Get the display name for the location type.
     */
    public function getLocationTypeDisplayAttribute(): string
    {
        return self::LOCATION_TYPES[$this->location_type] ?? $this->location_type;
    }

    /**
     * Count active holders at a specific location.
     */
    public function countHoldersAt(string $locationType, int $locationId): int
    {
        return $this->activePlayerRoles()
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->count();
    }

    /**
     * Check if there are available slots at a location.
     */
    public function hasAvailableSlots(string $locationType, int $locationId): bool
    {
        return $this->countHoldersAt($locationType, $locationId) < $this->max_per_location;
    }

    /**
     * Get the current holder at a specific location.
     */
    public function getHolderAt(string $locationType, int $locationId): ?PlayerRole
    {
        return $this->activePlayerRoles()
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->first();
    }

    /**
     * Get the NPC for this role at a specific location.
     */
    public function getNpcAt(string $locationType, int $locationId): ?LocationNpc
    {
        return $this->locationNpcs()
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get roles by location type.
     */
    public static function getByLocationType(string $locationType): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('location_type', $locationType)
            ->where('is_active', true)
            ->orderBy('tier', 'desc')
            ->get();
    }
}

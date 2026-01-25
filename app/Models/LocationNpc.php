<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocationNpc extends Model
{
    use HasFactory;

    /**
     * Minimum age for an NPC to die of old age.
     */
    public const MIN_DEATH_AGE = 50;

    /**
     * Maximum age an NPC can reach.
     */
    public const MAX_DEATH_AGE = 80;

    /**
     * Adult age (when NPC can hold roles).
     */
    public const ADULT_AGE = 16;

    /**
     * Minimum age for reproduction.
     */
    public const MIN_REPRODUCTION_AGE = 18;

    /**
     * Maximum age for reproduction (women only).
     */
    public const MAX_REPRODUCTION_AGE = 45;

    /**
     * Minimum years between having children.
     */
    public const BIRTH_COOLDOWN_YEARS = 2;

    /**
     * Available personality traits.
     */
    public const PERSONALITY_TRAITS = [
        'greedy',
        'generous',
        'ambitious',
        'content',
        'aggressive',
        'peaceful',
    ];

    protected $fillable = [
        'role_id',
        'location_type',
        'location_id',
        'npc_name',
        'family_name',
        'gender',
        'spouse_id',
        'parent1_id',
        'parent2_id',
        'last_birth_year',
        'npc_description',
        'npc_icon',
        'is_active',
        'birth_year',
        'death_year',
        'personality_traits',
        'weeks_without_food',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'birth_year' => 'integer',
            'death_year' => 'integer',
            'last_birth_year' => 'integer',
            'personality_traits' => 'array',
            'weeks_without_food' => 'integer',
        ];
    }

    /**
     * Get the role this NPC represents.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the NPC's spouse.
     */
    public function spouse(): BelongsTo
    {
        return $this->belongsTo(LocationNpc::class, 'spouse_id');
    }

    /**
     * Get the NPC's first parent.
     */
    public function parent1(): BelongsTo
    {
        return $this->belongsTo(LocationNpc::class, 'parent1_id');
    }

    /**
     * Get the NPC's second parent.
     */
    public function parent2(): BelongsTo
    {
        return $this->belongsTo(LocationNpc::class, 'parent2_id');
    }

    /**
     * Get all children of this NPC.
     */
    public function childrenAsParent1(): HasMany
    {
        return $this->hasMany(LocationNpc::class, 'parent1_id');
    }

    /**
     * Get all children of this NPC (as second parent).
     */
    public function childrenAsParent2(): HasMany
    {
        return $this->hasMany(LocationNpc::class, 'parent2_id');
    }

    /**
     * Get business employment records for this NPC.
     */
    public function businessEmployment(): HasMany
    {
        return $this->hasMany(BusinessEmployee::class, 'location_npc_id');
    }

    /**
     * Get the NPC's full name.
     */
    public function getNameAttribute(): string
    {
        return $this->npc_name;
    }

    /**
     * Get all children of this NPC (both parent1 and parent2).
     */
    public function getAllChildren(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->childrenAsParent1->merge($this->childrenAsParent2);
    }

    /**
     * Get the location model.
     */
    public function getLocationAttribute(): Model|null
    {
        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'castle' => Castle::find($this->location_id),
            'kingdom' => Kingdom::find($this->location_id),
            default => null,
        };
    }

    /**
     * Get the location name.
     */
    public function getLocationNameAttribute(): string
    {
        return $this->location?->name ?? 'Unknown Location';
    }

    /**
     * Check if this NPC should be active (no player holds the role).
     */
    public function shouldBeActive(): bool
    {
        $playerHoldsRole = PlayerRole::where('role_id', $this->role_id)
            ->where('location_type', $this->location_type)
            ->where('location_id', $this->location_id)
            ->where('status', PlayerRole::STATUS_ACTIVE)
            ->exists();

        return !$playerHoldsRole;
    }

    /**
     * Activate this NPC.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate this NPC.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Scope to active NPCs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to NPCs at a specific location.
     */
    public function scopeAtLocation($query, string $locationType, int $locationId)
    {
        return $query->where('location_type', $locationType)
            ->where('location_id', $locationId);
    }

    /**
     * Get NPC names by role type for random generation.
     */
    public static function generateNpcName(string $roleSlug): string
    {
        $prefixes = [
            'elder' => ['Old', 'Wise', 'Elder'],
            'blacksmith' => ['Iron', 'Steel', 'Forge'],
            'merchant' => ['Rich', 'Trader', 'Merchant'],
            'guard_captain' => ['Captain', 'Sergeant', 'Guard'],
            'healer' => ['Gentle', 'Healing', 'Kind'],
            'lord' => ['Lord', 'Baron', 'Count'],
            'steward' => ['Steward', 'Keeper', 'Master'],
            'marshal' => ['Marshal', 'Commander', 'Warden'],
            'treasurer' => ['Treasurer', 'Keeper', 'Master'],
            'jailsman' => ['Jailer', 'Warden', 'Keeper'],
            'king' => ['King', 'Regent', 'Ruler'],
            'chancellor' => ['Chancellor', 'Advisor', 'Sage'],
            'general' => ['General', 'Commander', 'Marshal'],
            'royal_treasurer' => ['Royal', 'Grand', 'High'],
        ];

        $surnames = [
            'Ironforge', 'Goldsworth', 'Silverbane', 'Oakshield', 'Stoneheart',
            'Meadowbrook', 'Riverwind', 'Thornwood', 'Brightforge', 'Darkhollow',
            'Fairweather', 'Strongarm', 'Swiftfoot', 'Lightbringer', 'Shadowmend',
        ];

        $prefix = $prefixes[$roleSlug] ?? [''];
        $chosenPrefix = $prefix[array_rand($prefix)];
        $surname = $surnames[array_rand($surnames)];

        return trim("$chosenPrefix $surname");
    }

    /**
     * Generate a random family name.
     */
    public static function generateFamilyName(): string
    {
        $familyNames = [
            'Ironforge', 'Goldsworth', 'Silverbane', 'Oakshield', 'Stoneheart',
            'Meadowbrook', 'Riverwind', 'Thornwood', 'Brightforge', 'Darkhollow',
            'Fairweather', 'Strongarm', 'Swiftfoot', 'Lightbringer', 'Shadowmend',
            'Ashford', 'Blackwood', 'Coldwater', 'Dunmore', 'Eastbrook',
            'Fallowfield', 'Greenhill', 'Highcastle', 'Ironwood', 'Lakeshire',
        ];

        return $familyNames[array_rand($familyNames)];
    }

    /**
     * Generate random personality traits (1-2 traits).
     */
    public static function generatePersonalityTraits(): array
    {
        $traits = self::PERSONALITY_TRAITS;
        shuffle($traits);
        $count = rand(1, 2);

        return array_slice($traits, 0, $count);
    }

    /**
     * Get the NPC's current age based on world state.
     */
    public function getAge(int $currentYear): int
    {
        if ($this->isDead()) {
            return $this->death_year - $this->birth_year;
        }

        return $currentYear - $this->birth_year;
    }

    /**
     * Check if the NPC is dead.
     */
    public function isDead(): bool
    {
        return $this->death_year !== null;
    }

    /**
     * Check if the NPC is alive.
     */
    public function isAlive(): bool
    {
        return $this->death_year === null;
    }

    /**
     * Check if the NPC is an adult (can hold roles).
     */
    public function isAdult(int $currentYear): bool
    {
        return $this->getAge($currentYear) >= self::ADULT_AGE;
    }

    /**
     * Check if the NPC is elderly (may die of old age).
     */
    public function isElderly(int $currentYear): bool
    {
        return $this->getAge($currentYear) >= self::MIN_DEATH_AGE;
    }

    /**
     * Calculate the probability of death this year based on age.
     * Returns a value between 0 and 1.
     */
    public function getDeathProbability(int $currentYear): float
    {
        $age = $this->getAge($currentYear);

        if ($age < self::MIN_DEATH_AGE) {
            return 0.0;
        }

        if ($age >= self::MAX_DEATH_AGE) {
            return 1.0;
        }

        // Linear probability from 0% at MIN_DEATH_AGE to 100% at MAX_DEATH_AGE
        $ageRange = self::MAX_DEATH_AGE - self::MIN_DEATH_AGE;
        $yearsOverMin = $age - self::MIN_DEATH_AGE;

        return $yearsOverMin / $ageRange;
    }

    /**
     * Mark the NPC as dead.
     */
    public function die(int $deathYear): void
    {
        $this->update([
            'death_year' => $deathYear,
            'is_active' => false,
        ]);
    }

    /**
     * Check if NPC has a specific personality trait.
     */
    public function hasTrait(string $trait): bool
    {
        return in_array($trait, $this->personality_traits ?? [], true);
    }

    /**
     * Scope to living NPCs.
     */
    public function scopeAlive($query)
    {
        return $query->whereNull('death_year');
    }

    /**
     * Scope to dead NPCs.
     */
    public function scopeDead($query)
    {
        return $query->whereNotNull('death_year');
    }

    /**
     * Scope to elderly NPCs (may die of old age).
     */
    public function scopeElderly($query, int $currentYear)
    {
        $minBirthYear = $currentYear - self::MIN_DEATH_AGE;

        return $query->where('birth_year', '<=', $minBirthYear);
    }

    /**
     * Check if NPC is married.
     */
    public function isMarried(): bool
    {
        return $this->spouse_id !== null;
    }

    /**
     * Check if NPC is of reproductive age.
     */
    public function isOfReproductiveAge(int $currentYear): bool
    {
        $age = $this->getAge($currentYear);

        if ($age < self::MIN_REPRODUCTION_AGE) {
            return false;
        }

        // Women have a max age for reproduction
        if ($this->gender === 'female' && $age > self::MAX_REPRODUCTION_AGE) {
            return false;
        }

        return true;
    }

    /**
     * Check if NPC can have a child this year (not on cooldown).
     */
    public function canHaveChild(int $currentYear): bool
    {
        if (! $this->isAlive()) {
            return false;
        }

        if (! $this->isOfReproductiveAge($currentYear)) {
            return false;
        }

        if ($this->last_birth_year === null) {
            return true;
        }

        return ($currentYear - $this->last_birth_year) >= self::BIRTH_COOLDOWN_YEARS;
    }

    /**
     * Marry another NPC (updates both NPCs).
     */
    public function marry(LocationNpc $spouse): void
    {
        $this->update(['spouse_id' => $spouse->id]);
        $spouse->update(['spouse_id' => $this->id]);
    }

    /**
     * Generate a random first name.
     */
    public static function generateFirstName(string $gender): string
    {
        $maleNames = [
            'William', 'Henry', 'John', 'Thomas', 'Robert', 'Richard', 'Edward',
            'Geoffrey', 'Walter', 'Ralph', 'Hugh', 'Simon', 'Peter', 'Roger',
            'Gilbert', 'Stephen', 'Adam', 'Nicholas', 'Alexander', 'Philip',
        ];

        $femaleNames = [
            'Alice', 'Matilda', 'Joan', 'Agnes', 'Margaret', 'Emma', 'Isabella',
            'Eleanor', 'Cecily', 'Beatrice', 'Mabel', 'Avice', 'Edith', 'Hawise',
            'Juliana', 'Margery', 'Petronilla', 'Sybil', 'Rose', 'Lucy',
        ];

        $names = $gender === 'female' ? $femaleNames : $maleNames;

        return $names[array_rand($names)];
    }

    /**
     * Scope to unmarried NPCs.
     */
    public function scopeUnmarried($query)
    {
        return $query->whereNull('spouse_id');
    }

    /**
     * Scope to married NPCs.
     */
    public function scopeMarried($query)
    {
        return $query->whereNotNull('spouse_id');
    }

    /**
     * Scope to NPCs of reproductive age.
     */
    public function scopeOfReproductiveAge($query, int $currentYear)
    {
        $maxBirthYear = $currentYear - self::MIN_REPRODUCTION_AGE;
        $minBirthYearFemale = $currentYear - self::MAX_REPRODUCTION_AGE;

        return $query->where('birth_year', '<=', $maxBirthYear)
            ->where(function ($q) use ($minBirthYearFemale) {
                $q->where('gender', 'male')
                    ->orWhere(function ($q2) use ($minBirthYearFemale) {
                        $q2->where('gender', 'female')
                            ->where('birth_year', '>=', $minBirthYearFemale);
                    });
            });
    }

    /**
     * Scope to NPCs that can have children (not on cooldown).
     */
    public function scopeCanReproduce($query, int $currentYear)
    {
        $cooldownYear = $currentYear - self::BIRTH_COOLDOWN_YEARS;

        return $query->alive()
            ->ofReproductiveAge($currentYear)
            ->where(function ($q) use ($cooldownYear) {
                $q->whereNull('last_birth_year')
                    ->orWhere('last_birth_year', '<=', $cooldownYear);
            });
    }
}

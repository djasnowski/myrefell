<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Village extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'barony_id',
        'parent_village_id',
        'is_town',
        'population',
        'wealth',
        'granary_capacity',
        'last_food_check_week',
        'biome',
        'is_port',
        'coordinates_x',
        'coordinates_y',
    ];

    protected function casts(): array
    {
        return [
            'is_town' => 'boolean',
            'is_port' => 'boolean',
            'population' => 'integer',
            'wealth' => 'integer',
            'granary_capacity' => 'integer',
            'last_food_check_week' => 'integer',
            'coordinates_x' => 'integer',
            'coordinates_y' => 'integer',
        ];
    }

    /**
     * Get the barony this village belongs to.
     */
    public function barony(): BelongsTo
    {
        return $this->belongsTo(Barony::class);
    }

    /**
     * Get the parent village (if this is a hamlet).
     */
    public function parentVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'parent_village_id');
    }

    /**
     * Get all hamlets of this village.
     */
    public function hamlets(): HasMany
    {
        return $this->hasMany(Village::class, 'parent_village_id');
    }

    /**
     * Get the kingdom this village belongs to (through barony).
     */
    public function kingdom(): ?Kingdom
    {
        return $this->barony?->kingdom;
    }

    /**
     * Get all residents of this village.
     */
    public function residents(): HasMany
    {
        return $this->hasMany(User::class, 'home_village_id');
    }

    /**
     * Check if this village is a town.
     */
    public function isTown(): bool
    {
        return $this->is_town;
    }

    /**
     * Check if this village is a hamlet (has a parent village).
     */
    public function isHamlet(): bool
    {
        return $this->parent_village_id !== null;
    }

    /**
     * Check if this village is independent (no barony allegiance).
     */
    public function isIndependent(): bool
    {
        return $this->barony_id === null;
    }

    /**
     * Check if this village is a port.
     */
    public function isPort(): bool
    {
        return $this->is_port;
    }

    /**
     * Get all elections for this village.
     * Note: Hamlets do not have their own elections.
     */
    public function elections(): MorphMany
    {
        return $this->morphMany(Election::class, 'domain', 'domain_type', 'domain_id');
    }

    /**
     * Get the village that provides services to this location.
     * For hamlets, this returns the parent village.
     * For regular villages, this returns itself.
     */
    public function getServiceProvider(): Village
    {
        return $this->isHamlet() ? $this->parentVillage : $this;
    }
}

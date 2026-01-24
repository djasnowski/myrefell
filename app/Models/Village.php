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
        'castle_id',
        'is_town',
        'population',
        'wealth',
        'biome',
        'coordinates_x',
        'coordinates_y',
    ];

    protected function casts(): array
    {
        return [
            'is_town' => 'boolean',
            'population' => 'integer',
            'wealth' => 'integer',
            'coordinates_x' => 'integer',
            'coordinates_y' => 'integer',
        ];
    }

    /**
     * Get the castle this village belongs to.
     */
    public function castle(): BelongsTo
    {
        return $this->belongsTo(Castle::class);
    }

    /**
     * Get the town this village belongs to (through castle).
     */
    public function town(): ?Town
    {
        return $this->castle?->town;
    }

    /**
     * Get the kingdom this village belongs to (through castle->town).
     */
    public function kingdom(): ?Kingdom
    {
        return $this->castle?->town?->kingdom ?? $this->castle?->kingdom;
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
     * Check if this village is independent (no castle allegiance).
     */
    public function isIndependent(): bool
    {
        return $this->castle_id === null;
    }

    /**
     * Get all elections for this village.
     */
    public function elections(): MorphMany
    {
        return $this->morphMany(Election::class, 'domain', 'domain_type', 'domain_id');
    }
}

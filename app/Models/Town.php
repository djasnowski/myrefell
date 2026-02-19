<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Town extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'barony_id',
        'is_capital',
        'is_port',
        'biome',
        'tax_rate',
        'population',
        'wealth',
        'granary_capacity',
        'mayor_user_id',
        'coordinates_x',
        'coordinates_y',
    ];

    protected function casts(): array
    {
        return [
            'is_capital' => 'boolean',
            'is_port' => 'boolean',
            'tax_rate' => 'decimal:2',
            'population' => 'integer',
            'wealth' => 'integer',
            'granary_capacity' => 'integer',
            'coordinates_x' => 'integer',
            'coordinates_y' => 'integer',
        ];
    }

    /**
     * Get the barony this town belongs to.
     */
    public function barony(): BelongsTo
    {
        return $this->belongsTo(Barony::class);
    }

    /**
     * Get the duchy this town belongs to (through barony).
     */
    public function duchy(): ?Duchy
    {
        return $this->barony?->duchy;
    }

    /**
     * Get the kingdom this town belongs to (through barony).
     */
    public function kingdom(): ?Kingdom
    {
        return $this->barony?->kingdom;
    }

    /**
     * Get the mayor of this town.
     */
    public function mayor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mayor_user_id');
    }

    /**
     * Get players currently visiting this town.
     */
    public function visitors(): HasMany
    {
        return $this->hasMany(User::class, 'current_location_id')
            ->where('current_location_type', 'town')
            ->whereNull('banned_at');
    }

    /**
     * Get players who have their home set to this town.
     */
    public function residents(): HasMany
    {
        return $this->hasMany(User::class, 'home_location_id')
            ->where('home_location_type', 'town')
            ->whereNull('banned_at');
    }

    /**
     * Check if this town is the capital of its kingdom.
     */
    public function isCapital(): bool
    {
        return $this->is_capital;
    }

    /**
     * Check if this town is a port.
     */
    public function isPort(): bool
    {
        return $this->is_port ?? false;
    }

    /**
     * Get all elections for this town.
     */
    public function elections(): MorphMany
    {
        return $this->morphMany(Election::class, 'domain', 'domain_type', 'domain_id');
    }

    /**
     * Get all disasters affecting this town.
     */
    public function disasters(): MorphMany
    {
        return $this->morphMany(Disaster::class, 'location');
    }
}

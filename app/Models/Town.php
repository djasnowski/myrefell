<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Town extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'kingdom_id',
        'is_capital',
        'biome',
        'tax_rate',
        'population',
        'wealth',
        'mayor_user_id',
        'coordinates_x',
        'coordinates_y',
    ];

    protected function casts(): array
    {
        return [
            'is_capital' => 'boolean',
            'tax_rate' => 'decimal:2',
            'population' => 'integer',
            'wealth' => 'integer',
            'coordinates_x' => 'integer',
            'coordinates_y' => 'integer',
        ];
    }

    /**
     * Get the kingdom this town belongs to.
     */
    public function kingdom(): BelongsTo
    {
        return $this->belongsTo(Kingdom::class);
    }

    /**
     * Get all castles in this town.
     */
    public function castles(): HasMany
    {
        return $this->hasMany(Castle::class);
    }

    /**
     * Get all villages in this town (through castles).
     */
    public function villages(): HasManyThrough
    {
        return $this->hasManyThrough(Village::class, Castle::class);
    }

    /**
     * Get the mayor of this town.
     */
    public function mayor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mayor_user_id');
    }

    /**
     * Check if this town is the capital of its kingdom.
     */
    public function isCapital(): bool
    {
        return $this->is_capital;
    }

    /**
     * Get all elections for this town.
     */
    public function elections(): MorphMany
    {
        return $this->morphMany(Election::class, 'domain', 'domain_type', 'domain_id');
    }
}

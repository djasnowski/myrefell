<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Kingdom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'biome',
        'capital_town_id',
        'king_user_id',
        'tax_rate',
        'coordinates_x',
        'coordinates_y',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'coordinates_x' => 'integer',
            'coordinates_y' => 'integer',
        ];
    }

    /**
     * Get the capital town of this kingdom.
     */
    public function capitalTown(): BelongsTo
    {
        return $this->belongsTo(Town::class, 'capital_town_id');
    }

    /**
     * Get all duchies in this kingdom.
     */
    public function duchies(): HasMany
    {
        return $this->hasMany(Duchy::class);
    }

    /**
     * Get all baronies in this kingdom.
     */
    public function baronies(): HasMany
    {
        return $this->hasMany(Barony::class);
    }

    /**
     * Get all towns in this kingdom (through baronies).
     */
    public function towns(): HasManyThrough
    {
        return $this->hasManyThrough(Town::class, Barony::class);
    }

    /**
     * Get the king of this kingdom.
     */
    public function king(): BelongsTo
    {
        return $this->belongsTo(User::class, 'king_user_id');
    }

    /**
     * Get all villages in this kingdom.
     */
    public function villages()
    {
        return Village::whereIn('barony_id', $this->baronies()->pluck('baronies.id'));
    }

    /**
     * Get players currently visiting this kingdom.
     */
    public function visitors(): HasMany
    {
        return $this->hasMany(User::class, 'current_location_id')
            ->where('current_location_type', 'kingdom')
            ->whereNull('banned_at');
    }

    /**
     * Get players who have their home set to this kingdom.
     */
    public function residents(): HasMany
    {
        return $this->hasMany(User::class, 'home_location_id')
            ->where('home_location_type', 'kingdom')
            ->whereNull('banned_at');
    }

    /**
     * Get all elections for this kingdom.
     */
    public function elections(): MorphMany
    {
        return $this->morphMany(Election::class, 'domain', 'domain_type', 'domain_id');
    }

    /**
     * Get all dungeons in this kingdom.
     */
    public function dungeons(): HasMany
    {
        return $this->hasMany(Dungeon::class);
    }
}

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
     * Get all towns in this kingdom.
     */
    public function towns(): HasMany
    {
        return $this->hasMany(Town::class);
    }

    /**
     * Get all castles in this kingdom (through towns).
     */
    public function castles(): HasManyThrough
    {
        return $this->hasManyThrough(Castle::class, Town::class);
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
        return Village::whereIn('castle_id', $this->castles()->pluck('castles.id'));
    }

    /**
     * Get all elections for this kingdom.
     */
    public function elections(): MorphMany
    {
        return $this->morphMany(Election::class, 'domain', 'domain_type', 'domain_id');
    }
}

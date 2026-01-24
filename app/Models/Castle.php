<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Castle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'kingdom_id',
        'town_id',
        'lord_user_id',
        'biome',
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
     * Get the town this castle belongs to.
     */
    public function town(): BelongsTo
    {
        return $this->belongsTo(Town::class);
    }

    /**
     * Get the kingdom this castle belongs to (through town).
     */
    public function kingdom(): BelongsTo
    {
        return $this->belongsTo(Kingdom::class);
    }

    /**
     * Get the lord who controls this castle.
     */
    public function lord(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lord_user_id');
    }

    /**
     * Get all villages under this castle.
     */
    public function villages(): HasMany
    {
        return $this->hasMany(Village::class);
    }

    /**
     * Check if this castle is in the capital town.
     */
    public function isInCapital(): bool
    {
        return $this->town?->isCapital() ?? false;
    }
}

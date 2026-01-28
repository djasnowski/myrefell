<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barony extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'kingdom_id',
        'duchy_id',
        'baron_user_id',
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
     * Get the kingdom this barony belongs to.
     */
    public function kingdom(): BelongsTo
    {
        return $this->belongsTo(Kingdom::class);
    }

    /**
     * Get the duchy this barony belongs to.
     */
    public function duchy(): BelongsTo
    {
        return $this->belongsTo(Duchy::class);
    }

    /**
     * Get the baron who controls this barony.
     */
    public function baron(): BelongsTo
    {
        return $this->belongsTo(User::class, 'baron_user_id');
    }

    /**
     * Get all villages under this barony.
     */
    public function villages(): HasMany
    {
        return $this->hasMany(Village::class);
    }

    /**
     * Get all towns under this barony.
     */
    public function towns(): HasMany
    {
        return $this->hasMany(Town::class);
    }

    /**
     * Check if this barony is in the capital town's region.
     */
    public function isCapitalBarony(): bool
    {
        return $this->kingdom?->capitalTown?->barony_id === $this->id;
    }
}

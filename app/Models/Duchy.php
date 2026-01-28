<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Duchy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'kingdom_id',
        'duke_user_id',
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
     * Get the kingdom this duchy belongs to.
     */
    public function kingdom(): BelongsTo
    {
        return $this->belongsTo(Kingdom::class);
    }

    /**
     * Get the duke who rules this duchy.
     */
    public function duke(): BelongsTo
    {
        return $this->belongsTo(User::class, 'duke_user_id');
    }

    /**
     * Get all baronies under this duchy.
     */
    public function baronies(): HasMany
    {
        return $this->hasMany(Barony::class);
    }

    /**
     * Get all villages under this duchy (through baronies).
     */
    public function villages()
    {
        return Village::whereIn('barony_id', $this->baronies()->pluck('id'));
    }

    /**
     * Get all towns under this duchy (through baronies).
     */
    public function towns()
    {
        return Town::whereIn('barony_id', $this->baronies()->pluck('id'));
    }
}

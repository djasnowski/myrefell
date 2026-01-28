<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlessingType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'category',
        'effects',
        'duration_minutes',
        'cooldown_minutes',
        'prayer_level_required',
        'gold_cost',
        'energy_cost',
        'is_active',
    ];

    protected $casts = [
        'effects' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get all player blessings of this type.
     */
    public function playerBlessings(): HasMany
    {
        return $this->hasMany(PlayerBlessing::class);
    }

    /**
     * Get active blessings of this type.
     */
    public function activeBlessings(): HasMany
    {
        return $this->hasMany(PlayerBlessing::class)->where('expires_at', '>', now());
    }

    /**
     * Scope to only active blessing types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDurationAttribute(): string
    {
        if ($this->duration_minutes < 60) {
            return $this->duration_minutes . ' min';
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($minutes === 0) {
            return $hours . 'h';
        }

        return $hours . 'h ' . $minutes . 'm';
    }
}

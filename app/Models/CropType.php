<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CropType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'grow_time_minutes',
        'farming_level_required',
        'farming_xp',
        'yield_min',
        'yield_max',
        'seed_item_id',
        'harvest_item_id',
        'plant_cost',
        'seasons',
    ];

    protected $casts = [
        'grow_time_minutes' => 'integer',
        'farming_level_required' => 'integer',
        'farming_xp' => 'integer',
        'yield_min' => 'integer',
        'yield_max' => 'integer',
        'plant_cost' => 'integer',
        'seasons' => 'array',
    ];

    /**
     * The seed item needed to plant this crop.
     */
    public function seedItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'seed_item_id');
    }

    /**
     * The item harvested from this crop.
     */
    public function harvestItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'harvest_item_id');
    }

    /**
     * All plots growing this crop type.
     */
    public function farmPlots(): HasMany
    {
        return $this->hasMany(FarmPlot::class);
    }

    /**
     * Check if this crop can be planted in the current season.
     */
    public function canPlantInSeason(?string $season = null): bool
    {
        if (empty($this->seasons)) {
            return true; // Available all year
        }

        $season = $season ?? $this->getCurrentSeason();

        return in_array($season, $this->seasons);
    }

    /**
     * Get the current season based on game calendar.
     */
    protected function getCurrentSeason(): string
    {
        return WorldState::current()->current_season;
    }

    /**
     * Calculate random yield within range.
     */
    public function rollYield(int $qualityBonus = 0): int
    {
        $base = rand($this->yield_min, $this->yield_max);
        $bonus = (int) floor($base * ($qualityBonus / 100));

        return $base + $bonus;
    }
}

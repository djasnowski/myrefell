<?php

namespace App\Models;

use App\Config\ConstructionConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseFurniture extends Model
{
    protected $table = 'house_furniture';

    protected $fillable = [
        'house_room_id',
        'hotspot_slug',
        'furniture_key',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(HouseRoom::class, 'house_room_id');
    }

    /**
     * Get the furniture configuration (effect, materials, XP).
     */
    public function getFurnitureConfig(): ?array
    {
        $roomConfig = ConstructionConfig::ROOMS[$this->room->room_type] ?? null;
        if (! $roomConfig) {
            return null;
        }

        $hotspot = $roomConfig['hotspots'][$this->hotspot_slug] ?? null;
        if (! $hotspot) {
            return null;
        }

        return $hotspot['options'][$this->furniture_key] ?? null;
    }
}

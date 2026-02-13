<?php

namespace App\Models;

use App\Config\ConstructionConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HouseRoom extends Model
{
    protected $fillable = [
        'player_house_id',
        'room_type',
        'grid_x',
        'grid_y',
    ];

    protected function casts(): array
    {
        return [
            'grid_x' => 'integer',
            'grid_y' => 'integer',
        ];
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(PlayerHouse::class, 'player_house_id');
    }

    public function furniture(): HasMany
    {
        return $this->hasMany(HouseFurniture::class);
    }

    /**
     * Get the room type configuration (hotspot definitions).
     */
    public function getRoomTypeConfig(): ?array
    {
        return ConstructionConfig::ROOMS[$this->room_type] ?? null;
    }
}

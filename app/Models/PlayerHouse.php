<?php

namespace App\Models;

use App\Config\ConstructionConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlayerHouse extends Model
{
    protected $fillable = [
        'player_id',
        'name',
        'tier',
        'condition',
        'kingdom_id',
        'compost_charges',
    ];

    protected function casts(): array
    {
        return [
            'condition' => 'integer',
            'compost_charges' => 'integer',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    public function kingdom(): BelongsTo
    {
        return $this->belongsTo(Kingdom::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(HouseRoom::class);
    }

    public function storage(): HasMany
    {
        return $this->hasMany(HouseStorage::class);
    }

    public function portals(): HasMany
    {
        return $this->hasMany(HousePortal::class);
    }

    public function servant(): HasOne
    {
        return $this->hasOne(HouseServant::class);
    }

    public function trophies(): HasMany
    {
        return $this->hasMany(HouseTrophy::class);
    }

    public function gardenPlots(): HasMany
    {
        return $this->hasMany(GardenPlot::class);
    }

    public function getMaxRooms(): int
    {
        return ConstructionConfig::HOUSE_TIERS[$this->tier]['max_rooms'] ?? 3;
    }

    public function getGridSize(): int
    {
        return ConstructionConfig::HOUSE_TIERS[$this->tier]['grid'] ?? 3;
    }

    public function getStorageCapacity(): int
    {
        return ConstructionConfig::HOUSE_TIERS[$this->tier]['storage'] ?? 100;
    }

    public function getStorageUsed(): int
    {
        return $this->storage()->sum('quantity');
    }
}

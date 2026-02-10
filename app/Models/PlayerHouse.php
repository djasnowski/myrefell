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
        'upkeep_due_at',
        'kingdom_id',
        'location_type',
        'location_id',
        'compost_charges',
    ];

    protected function casts(): array
    {
        return [
            'condition' => 'integer',
            'compost_charges' => 'integer',
            'upkeep_due_at' => 'datetime',
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
        return $this->storage()->count();
    }

    public function isUpkeepOverdue(): bool
    {
        return $this->upkeep_due_at && $this->upkeep_due_at->isPast();
    }

    public function getUpkeepCost(): int
    {
        return ConstructionConfig::HOUSE_TIERS[$this->tier]['upkeep'] ?? 100;
    }

    public function getRepairCost(): int
    {
        return (int) ceil($this->getUpkeepCost() * (100 - $this->condition) * 0.5);
    }

    public function areBuffsDisabled(): bool
    {
        return $this->condition <= 50;
    }

    public function arePortalsDisabled(): bool
    {
        return $this->condition <= 25;
    }

    public function areStorageDisabled(): bool
    {
        return $this->condition <= 25;
    }

    /**
     * Get the pluralized location path segment (e.g. "towns", "villages").
     */
    public function getLocationPathPrefix(): string
    {
        return match ($this->location_type) {
            'village' => 'villages',
            'town' => 'towns',
            'barony' => 'baronies',
            'duchy' => 'duchies',
            'kingdom' => 'kingdoms',
            default => $this->location_type.'s',
        };
    }

    /**
     * Get the full house URL path (e.g. "/towns/3/house").
     */
    public function getHouseUrl(): string
    {
        return '/'.$this->getLocationPathPrefix().'/'.$this->location_id.'/house';
    }
}

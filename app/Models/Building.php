<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    use HasFactory;

    public const STATUS_PLANNED = 'planned';
    public const STATUS_UNDER_CONSTRUCTION = 'under_construction';
    public const STATUS_OPERATIONAL = 'operational';
    public const STATUS_DAMAGED = 'damaged';
    public const STATUS_DESTROYED = 'destroyed';
    public const STATUS_ABANDONED = 'abandoned';

    protected $fillable = [
        'building_type_id', 'location_type', 'location_id', 'name', 'status',
        'condition', 'construction_progress', 'owner_id', 'built_by_user_id',
        'construction_started_at', 'completed_at', 'last_maintenance_at',
    ];

    protected function casts(): array
    {
        return [
            'construction_started_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_maintenance_at' => 'datetime',
        ];
    }

    public function buildingType(): BelongsTo
    {
        return $this->belongsTo(BuildingType::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function builtBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'built_by_user_id');
    }

    public function constructionProjects(): HasMany
    {
        return $this->hasMany(ConstructionProject::class);
    }

    public function damages(): HasMany
    {
        return $this->hasMany(BuildingDamage::class);
    }

    public function getLocationAttribute(): ?Model
    {
        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'town' => Town::find($this->location_id),
            default => null,
        };
    }

    public function isOperational(): bool
    {
        return $this->status === self::STATUS_OPERATIONAL;
    }

    public function needsRepair(): bool
    {
        return $this->condition < 50 || $this->status === self::STATUS_DAMAGED;
    }

    public function scopeOperational($query)
    {
        return $query->where('status', self::STATUS_OPERATIONAL);
    }

    public function scopeAtLocation($query, string $type, int $id)
    {
        return $query->where('location_type', $type)->where('location_id', $id);
    }
}

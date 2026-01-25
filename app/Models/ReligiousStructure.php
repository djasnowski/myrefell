<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ReligiousStructure extends Model
{
    use HasFactory;

    public const TYPE_SHRINE = 'shrine';
    public const TYPE_TEMPLE = 'temple';
    public const TYPE_CATHEDRAL = 'cathedral';

    public const TYPES = [
        self::TYPE_SHRINE,
        self::TYPE_TEMPLE,
        self::TYPE_CATHEDRAL,
    ];

    public const BUILD_COSTS = [
        self::TYPE_SHRINE => 10000,
        self::TYPE_TEMPLE => 50000,
        self::TYPE_CATHEDRAL => 200000,
    ];

    // Devotion bonus multipliers for each structure type
    public const DEVOTION_MULTIPLIERS = [
        self::TYPE_SHRINE => 1.0,
        self::TYPE_TEMPLE => 1.5,
        self::TYPE_CATHEDRAL => 2.0,
    ];

    protected $fillable = [
        'religion_id',
        'location_type',
        'location_id',
        'structure_type',
        'name',
        'build_cost',
        'built_by_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'build_cost' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the religion this structure belongs to.
     */
    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    /**
     * Get the location (polymorphic).
     */
    public function location(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the player who built this structure.
     */
    public function builtBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'built_by_id');
    }

    /**
     * Get actions performed at this structure.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(ReligiousAction::class);
    }

    /**
     * Get the devotion multiplier for this structure.
     */
    public function getDevotionMultiplierAttribute(): float
    {
        return self::DEVOTION_MULTIPLIERS[$this->structure_type] ?? 1.0;
    }

    /**
     * Get the build cost for a structure type.
     */
    public static function getBuildCost(string $type): int
    {
        return self::BUILD_COSTS[$type] ?? 10000;
    }

    /**
     * Get structure type display name.
     */
    public function getTypeDisplayAttribute(): string
    {
        return match ($this->structure_type) {
            self::TYPE_SHRINE => 'Shrine',
            self::TYPE_TEMPLE => 'Temple',
            self::TYPE_CATHEDRAL => 'Cathedral',
            default => 'Unknown',
        };
    }

    /**
     * Check if this is a shrine.
     */
    public function isShrine(): bool
    {
        return $this->structure_type === self::TYPE_SHRINE;
    }

    /**
     * Check if this is a temple.
     */
    public function isTemple(): bool
    {
        return $this->structure_type === self::TYPE_TEMPLE;
    }

    /**
     * Check if this is a cathedral.
     */
    public function isCathedral(): bool
    {
        return $this->structure_type === self::TYPE_CATHEDRAL;
    }
}

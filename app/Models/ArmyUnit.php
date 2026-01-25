<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArmyUnit extends Model
{
    use HasFactory;

    const TYPE_LEVY = 'levy';
    const TYPE_MILITIA = 'militia';
    const TYPE_MEN_AT_ARMS = 'men_at_arms';
    const TYPE_KNIGHTS = 'knights';
    const TYPE_ARCHERS = 'archers';
    const TYPE_CROSSBOWMEN = 'crossbowmen';
    const TYPE_CAVALRY = 'cavalry';
    const TYPE_SIEGE_ENGINEERS = 'siege_engineers';

    const STATUS_READY = 'ready';
    const STATUS_EXHAUSTED = 'exhausted';
    const STATUS_ROUTED = 'routed';
    const STATUS_DESTROYED = 'destroyed';

    protected $fillable = [
        'army_id', 'unit_type', 'count', 'max_count', 'attack', 'defense',
        'morale_bonus', 'upkeep_per_soldier', 'status', 'equipment',
    ];

    protected function casts(): array
    {
        return [
            'equipment' => 'array',
        ];
    }

    public function army(): BelongsTo
    {
        return $this->belongsTo(Army::class);
    }

    public function getTotalAttackAttribute(): int
    {
        return $this->count * $this->attack;
    }

    public function getTotalDefenseAttribute(): int
    {
        return $this->count * $this->defense;
    }

    public function getTotalUpkeepAttribute(): int
    {
        return $this->count * $this->upkeep_per_soldier;
    }

    public function isOperational(): bool
    {
        return in_array($this->status, [self::STATUS_READY, self::STATUS_EXHAUSTED]);
    }

    public static function getBaseStats(string $unitType): array
    {
        return match ($unitType) {
            self::TYPE_LEVY => ['attack' => 1, 'defense' => 1, 'upkeep' => 1, 'morale_bonus' => -5],
            self::TYPE_MILITIA => ['attack' => 2, 'defense' => 2, 'upkeep' => 2, 'morale_bonus' => 0],
            self::TYPE_MEN_AT_ARMS => ['attack' => 4, 'defense' => 4, 'upkeep' => 5, 'morale_bonus' => 5],
            self::TYPE_KNIGHTS => ['attack' => 8, 'defense' => 6, 'upkeep' => 15, 'morale_bonus' => 15],
            self::TYPE_ARCHERS => ['attack' => 3, 'defense' => 1, 'upkeep' => 3, 'morale_bonus' => 0],
            self::TYPE_CROSSBOWMEN => ['attack' => 5, 'defense' => 2, 'upkeep' => 4, 'morale_bonus' => 0],
            self::TYPE_CAVALRY => ['attack' => 6, 'defense' => 3, 'upkeep' => 8, 'morale_bonus' => 10],
            self::TYPE_SIEGE_ENGINEERS => ['attack' => 1, 'defense' => 1, 'upkeep' => 10, 'morale_bonus' => 0],
            default => ['attack' => 1, 'defense' => 1, 'upkeep' => 1, 'morale_bonus' => 0],
        };
    }
}

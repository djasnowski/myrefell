<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DungeonSession extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_ABANDONED = 'abandoned';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_ABANDONED,
    ];

    public const TRAINING_STYLES = ['attack', 'strength', 'defense'];

    protected $appends = ['progress_percentage'];

    protected $fillable = [
        'user_id',
        'dungeon_id',
        'current_floor',
        'monsters_defeated',
        'total_monsters_on_floor',
        'status',
        'xp_accumulated',
        'gold_accumulated',
        'loot_accumulated',
        'training_style',
        'entry_location_type',
        'entry_location_id',
    ];

    protected function casts(): array
    {
        return [
            'current_floor' => 'integer',
            'monsters_defeated' => 'integer',
            'total_monsters_on_floor' => 'integer',
            'xp_accumulated' => 'integer',
            'gold_accumulated' => 'integer',
            'loot_accumulated' => 'array',
        ];
    }

    /**
     * Get the player for this session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the dungeon for this session.
     */
    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }

    /**
     * Check if the session is still active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if the session was completed successfully.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if all monsters on current floor are defeated.
     */
    public function isFloorCleared(): bool
    {
        return $this->monsters_defeated >= $this->total_monsters_on_floor;
    }

    /**
     * Check if this is the final floor.
     */
    public function isOnFinalFloor(): bool
    {
        return $this->current_floor >= $this->dungeon->floor_count;
    }

    /**
     * Get the current floor model.
     */
    public function getCurrentFloor(): ?DungeonFloor
    {
        return $this->dungeon->floors()
            ->where('floor_number', $this->current_floor)
            ->first();
    }

    /**
     * Get progress percentage through the dungeon.
     */
    public function getProgressPercentageAttribute(): float
    {
        $totalFloors = $this->dungeon->floor_count;
        $completedFloors = $this->current_floor - 1;
        $currentFloorProgress = $this->total_monsters_on_floor > 0
            ? $this->monsters_defeated / $this->total_monsters_on_floor
            : 0;

        return (($completedFloors + $currentFloorProgress) / $totalFloors) * 100;
    }

    /**
     * Add XP to the accumulated total.
     */
    public function addXp(int $amount): void
    {
        $this->increment('xp_accumulated', $amount);
    }

    /**
     * Add gold to the accumulated total.
     */
    public function addGold(int $amount): void
    {
        $this->increment('gold_accumulated', $amount);
    }

    /**
     * Add loot item to accumulated loot.
     */
    public function addLoot(int $itemId, int $quantity = 1): void
    {
        $loot = $this->loot_accumulated ?? [];

        if (isset($loot[$itemId])) {
            $loot[$itemId] += $quantity;
        } else {
            $loot[$itemId] = $quantity;
        }

        $this->loot_accumulated = $loot;
        $this->save();
    }
}

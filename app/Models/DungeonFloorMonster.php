<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DungeonFloorMonster extends Model
{
    use HasFactory;

    protected $fillable = [
        'dungeon_floor_id',
        'monster_id',
        'spawn_weight',
        'min_count',
        'max_count',
    ];

    protected function casts(): array
    {
        return [
            'spawn_weight' => 'integer',
            'min_count' => 'integer',
            'max_count' => 'integer',
        ];
    }

    /**
     * Get the floor this monster spawn belongs to.
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(DungeonFloor::class, 'dungeon_floor_id');
    }

    /**
     * Get the monster.
     */
    public function monster(): BelongsTo
    {
        return $this->belongsTo(Monster::class);
    }

    /**
     * Get a random count of monsters to spawn.
     */
    public function rollSpawnCount(): int
    {
        if ($this->max_count <= $this->min_count) {
            return $this->min_count;
        }

        return rand($this->min_count, $this->max_count);
    }
}

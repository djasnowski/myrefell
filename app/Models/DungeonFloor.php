<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DungeonFloor extends Model
{
    use HasFactory;

    protected $fillable = [
        'dungeon_id',
        'floor_number',
        'name',
        'monster_count',
        'is_boss_floor',
        'xp_multiplier',
        'loot_multiplier',
    ];

    protected function casts(): array
    {
        return [
            'floor_number' => 'integer',
            'monster_count' => 'integer',
            'is_boss_floor' => 'boolean',
            'xp_multiplier' => 'float',
            'loot_multiplier' => 'float',
        ];
    }

    /**
     * Get the dungeon this floor belongs to.
     */
    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }

    /**
     * Get the monster spawn configurations for this floor.
     */
    public function monsters(): HasMany
    {
        return $this->hasMany(DungeonFloorMonster::class);
    }

    /**
     * Get a random monster for this floor based on spawn weights.
     */
    public function getRandomMonster(): ?Monster
    {
        $spawns = $this->monsters()->with('monster')->get();

        if ($spawns->isEmpty()) {
            return null;
        }

        // Build weighted pool
        $totalWeight = $spawns->sum('spawn_weight');
        $roll = rand(1, $totalWeight);

        $cumulative = 0;
        foreach ($spawns as $spawn) {
            $cumulative += $spawn->spawn_weight;
            if ($roll <= $cumulative) {
                return $spawn->monster;
            }
        }

        return $spawns->first()->monster;
    }

    /**
     * Get the display name for this floor.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->name) {
            return $this->name;
        }

        if ($this->is_boss_floor) {
            return "Boss Chamber";
        }

        return "Floor {$this->floor_number}";
    }
}

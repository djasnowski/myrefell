<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dungeon extends Model
{
    use HasFactory;

    public const DIFFICULTIES = ['easy', 'normal', 'hard', 'nightmare'];

    public const THEMES = [
        'goblin_fortress',
        'undead_crypt',
        'dragon_lair',
        'bandit_hideout',
        'ancient_ruins',
        'elemental_cavern',
        'demon_pit',
    ];

    protected $fillable = [
        'name',
        'description',
        'theme',
        'difficulty',
        'biome',
        'min_combat_level',
        'recommended_level',
        'floor_count',
        'boss_monster_id',
        'xp_reward_base',
        'gold_reward_min',
        'gold_reward_max',
        'energy_cost',
    ];

    protected function casts(): array
    {
        return [
            'min_combat_level' => 'integer',
            'recommended_level' => 'integer',
            'floor_count' => 'integer',
            'xp_reward_base' => 'integer',
            'gold_reward_min' => 'integer',
            'gold_reward_max' => 'integer',
            'energy_cost' => 'integer',
        ];
    }

    /**
     * Get the floors for this dungeon.
     */
    public function floors(): HasMany
    {
        return $this->hasMany(DungeonFloor::class)->orderBy('floor_number');
    }

    /**
     * Get the boss monster for this dungeon.
     */
    public function bossMonster(): BelongsTo
    {
        return $this->belongsTo(Monster::class, 'boss_monster_id');
    }

    /**
     * Get all sessions for this dungeon.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(DungeonSession::class);
    }

    /**
     * Check if a player meets the level requirement.
     */
    public function canBeEnteredBy(User $player): bool
    {
        return $player->combat_level >= $this->min_combat_level;
    }

    /**
     * Get a random gold reward.
     */
    public function rollGoldReward(): int
    {
        if ($this->gold_reward_max <= $this->gold_reward_min) {
            return $this->gold_reward_min;
        }

        return rand($this->gold_reward_min, $this->gold_reward_max);
    }

    /**
     * Get display name with difficulty.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->difficulty})";
    }

    /**
     * Get the difficulty color for UI display.
     */
    public function getDifficultyColorAttribute(): string
    {
        return match ($this->difficulty) {
            'easy' => 'green',
            'normal' => 'yellow',
            'hard' => 'red',
            'nightmare' => 'purple',
            default => 'gray',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Monster extends Model
{
    use HasFactory;

    public const TYPES = [
        'humanoid',
        'beast',
        'undead',
        'dragon',
        'demon',
        'elemental',
        'giant',
        'goblinoid',
    ];

    public const BIOMES = [
        'plains',
        'tundra',
        'coastal',
        'volcano',
        'forest',
        'desert',
        'swamp',
        'mountain',
    ];

    public const ATTACK_STYLES = ['melee', 'ranged', 'magic'];

    protected $fillable = [
        'name',
        'description',
        'type',
        'biome',
        'hp',
        'max_hp',
        'attack_level',
        'strength_level',
        'defense_level',
        'stab_defense',
        'slash_defense',
        'crush_defense',
        'combat_level',
        'attack_style',
        'xp_reward',
        'gold_drop_min',
        'gold_drop_max',
        'min_player_combat_level',
        'is_boss',
        'is_aggressive',
    ];

    protected function casts(): array
    {
        return [
            'hp' => 'integer',
            'max_hp' => 'integer',
            'attack_level' => 'integer',
            'strength_level' => 'integer',
            'defense_level' => 'integer',
            'stab_defense' => 'integer',
            'slash_defense' => 'integer',
            'crush_defense' => 'integer',
            'combat_level' => 'integer',
            'xp_reward' => 'integer',
            'gold_drop_min' => 'integer',
            'gold_drop_max' => 'integer',
            'min_player_combat_level' => 'integer',
            'is_boss' => 'boolean',
            'is_aggressive' => 'boolean',
        ];
    }

    /**
     * Get the loot table for this monster.
     */
    public function lootTable(): HasMany
    {
        return $this->hasMany(MonsterLootTable::class);
    }

    /**
     * Get all combat sessions involving this monster.
     */
    public function combatSessions(): HasMany
    {
        return $this->hasMany(CombatSession::class);
    }

    /**
     * Calculate random gold drop amount.
     */
    public function rollGoldDrop(): int
    {
        if ($this->gold_drop_max <= $this->gold_drop_min) {
            return $this->gold_drop_min;
        }

        return rand($this->gold_drop_min, $this->gold_drop_max);
    }

    /**
     * Check if a player meets the combat level requirement.
     */
    public function canBeAttackedBy(User $player): bool
    {
        return $player->combat_level >= $this->min_player_combat_level;
    }

    /**
     * Get display name with combat level.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} (Level {$this->combat_level})";
    }
}

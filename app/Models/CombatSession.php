<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CombatSession extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_VICTORY = 'victory';

    public const STATUS_DEFEAT = 'defeat';

    public const STATUS_FLED = 'fled';

    public const TRAINING_STYLES = ['attack', 'strength', 'defense'];

    protected $fillable = [
        'user_id',
        'monster_id',
        'player_hp',
        'monster_hp',
        'round',
        'training_style',
        'attack_style_index',
        'xp_gained',
        'status',
        'location_type',
        'location_id',
    ];

    protected function casts(): array
    {
        return [
            'player_hp' => 'integer',
            'monster_hp' => 'integer',
            'round' => 'integer',
            'attack_style_index' => 'integer',
            'xp_gained' => 'integer',
            'location_id' => 'integer',
        ];
    }

    /**
     * Get the player in this combat session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the monster in this combat session.
     */
    public function monster(): BelongsTo
    {
        return $this->belongsTo(Monster::class);
    }

    /**
     * Get all combat logs for this session.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(CombatLog::class)->orderBy('round')->orderBy('id');
    }

    /**
     * Check if the combat is still active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if the player won.
     */
    public function isVictory(): bool
    {
        return $this->status === self::STATUS_VICTORY;
    }

    /**
     * Check if the player lost.
     */
    public function isDefeat(): bool
    {
        return $this->status === self::STATUS_DEFEAT;
    }

    /**
     * Check if the player fled.
     */
    public function hasFled(): bool
    {
        return $this->status === self::STATUS_FLED;
    }

    /**
     * Check if the monster is dead.
     */
    public function isMonsterDead(): bool
    {
        return $this->monster_hp <= 0;
    }

    /**
     * Check if the player is dead in this session.
     */
    public function isPlayerDead(): bool
    {
        return $this->player_hp <= 0;
    }
}

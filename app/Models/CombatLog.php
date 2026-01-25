<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CombatLog extends Model
{
    use HasFactory;

    public const ACTOR_PLAYER = 'player';
    public const ACTOR_MONSTER = 'monster';

    public const ACTION_ATTACK = 'attack';
    public const ACTION_EAT = 'eat';
    public const ACTION_FLEE = 'flee';

    protected $fillable = [
        'combat_session_id',
        'round',
        'actor',
        'action',
        'hit',
        'damage',
        'player_hp_after',
        'monster_hp_after',
        'item_id',
        'hp_restored',
    ];

    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'hit' => 'boolean',
            'damage' => 'integer',
            'player_hp_after' => 'integer',
            'monster_hp_after' => 'integer',
            'hp_restored' => 'integer',
        ];
    }

    /**
     * Get the combat session this log belongs to.
     */
    public function combatSession(): BelongsTo
    {
        return $this->belongsTo(CombatSession::class);
    }

    /**
     * Get the item used (if eating food).
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Check if this was a player action.
     */
    public function isPlayerAction(): bool
    {
        return $this->actor === self::ACTOR_PLAYER;
    }

    /**
     * Check if this was a monster action.
     */
    public function isMonsterAction(): bool
    {
        return $this->actor === self::ACTOR_MONSTER;
    }

    /**
     * Get a human-readable description of this log entry.
     */
    public function getDescriptionAttribute(): string
    {
        $session = $this->combatSession;
        $actorName = $this->actor === self::ACTOR_PLAYER ? 'You' : $session->monster->name;

        return match ($this->action) {
            self::ACTION_ATTACK => $this->hit
                ? "{$actorName} hit for {$this->damage} damage!"
                : "{$actorName} missed!",
            self::ACTION_EAT => "You ate {$this->item?->name} and restored {$this->hp_restored} HP.",
            self::ACTION_FLEE => 'You fled from combat!',
            default => 'Unknown action.',
        };
    }
}

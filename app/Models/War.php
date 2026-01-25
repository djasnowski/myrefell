<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class War extends Model
{
    use HasFactory;

    const CASUS_BELLI_CLAIM = 'claim';
    const CASUS_BELLI_CONQUEST = 'conquest';
    const CASUS_BELLI_REBELLION = 'rebellion';
    const CASUS_BELLI_HOLY_WAR = 'holy_war';
    const CASUS_BELLI_DEFENSE = 'defense';
    const CASUS_BELLI_RAID = 'raid';

    const STATUS_ACTIVE = 'active';
    const STATUS_ATTACKER_WINNING = 'attacker_winning';
    const STATUS_DEFENDER_WINNING = 'defender_winning';
    const STATUS_WHITE_PEACE = 'white_peace';
    const STATUS_ATTACKER_VICTORY = 'attacker_victory';
    const STATUS_DEFENDER_VICTORY = 'defender_victory';

    protected $fillable = [
        'name', 'casus_belli', 'attacker_kingdom_id', 'defender_kingdom_id',
        'attacker_type', 'attacker_id', 'defender_type', 'defender_id',
        'status', 'attacker_war_score', 'defender_war_score', 'war_goals',
        'peace_terms', 'declared_at', 'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'war_goals' => 'array',
            'peace_terms' => 'array',
            'declared_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function attackerKingdom(): BelongsTo
    {
        return $this->belongsTo(Kingdom::class, 'attacker_kingdom_id');
    }

    public function defenderKingdom(): BelongsTo
    {
        return $this->belongsTo(Kingdom::class, 'defender_kingdom_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(WarParticipant::class);
    }

    public function battles(): HasMany
    {
        return $this->hasMany(Battle::class);
    }

    public function sieges(): HasMany
    {
        return $this->hasMany(Siege::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(WarGoal::class);
    }

    public function peaceTreaty(): HasOne
    {
        return $this->hasOne(PeaceTreaty::class);
    }

    public function getAttackerAttribute(): ?Model
    {
        return match ($this->attacker_type) {
            'kingdom' => Kingdom::find($this->attacker_id),
            'barony' => Barony::find($this->attacker_id),
            'player' => User::find($this->attacker_id),
            default => null,
        };
    }

    public function getDefenderAttribute(): ?Model
    {
        return match ($this->defender_type) {
            'kingdom' => Kingdom::find($this->defender_id),
            'barony' => Barony::find($this->barony_id),
            'player' => User::find($this->defender_id),
            default => null,
        };
    }

    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_ACTIVE,
            self::STATUS_ATTACKER_WINNING,
            self::STATUS_DEFENDER_WINNING,
        ]);
    }

    public function isEnded(): bool
    {
        return in_array($this->status, [
            self::STATUS_WHITE_PEACE,
            self::STATUS_ATTACKER_VICTORY,
            self::STATUS_DEFENDER_VICTORY,
        ]);
    }

    public function getWinningSide(): ?string
    {
        if ($this->attacker_war_score >= 100) {
            return 'attacker';
        }
        if ($this->defender_war_score >= 100) {
            return 'defender';
        }
        if ($this->attacker_war_score > $this->defender_war_score + 20) {
            return 'attacker';
        }
        if ($this->defender_war_score > $this->attacker_war_score + 20) {
            return 'defender';
        }
        return null;
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_ACTIVE,
            self::STATUS_ATTACKER_WINNING,
            self::STATUS_DEFENDER_WINNING,
        ]);
    }

    public function scopeEnded($query)
    {
        return $query->whereIn('status', [
            self::STATUS_WHITE_PEACE,
            self::STATUS_ATTACKER_VICTORY,
            self::STATUS_DEFENDER_VICTORY,
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BattleParticipant extends Model
{
    use HasFactory;

    const OUTCOME_VICTORY = 'victory';
    const OUTCOME_DEFEAT = 'defeat';
    const OUTCOME_ROUTED = 'routed';
    const OUTCOME_WITHDREW = 'withdrew';

    protected $fillable = [
        'battle_id', 'army_id', 'side', 'is_commander', 'troops_committed',
        'casualties', 'morale_at_start', 'morale_at_end', 'outcome',
    ];

    protected function casts(): array
    {
        return [
            'is_commander' => 'boolean',
        ];
    }

    public function battle(): BelongsTo
    {
        return $this->belongsTo(Battle::class);
    }

    public function army(): BelongsTo
    {
        return $this->belongsTo(Army::class);
    }

    public function getCasualtyRateAttribute(): float
    {
        if ($this->troops_committed === 0) {
            return 0;
        }
        return ($this->casualties / $this->troops_committed) * 100;
    }

    public function getMoraleLossAttribute(): int
    {
        if (is_null($this->morale_at_end)) {
            return 0;
        }
        return $this->morale_at_start - $this->morale_at_end;
    }
}

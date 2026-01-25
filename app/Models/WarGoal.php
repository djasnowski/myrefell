<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarGoal extends Model
{
    use HasFactory;

    const TYPE_CONQUER_TERRITORY = 'conquer_territory';
    const TYPE_SUBJUGATION = 'subjugation';
    const TYPE_INDEPENDENCE = 'independence';
    const TYPE_RAID = 'raid';
    const TYPE_HUMILIATE = 'humiliate';

    protected $fillable = [
        'war_id', 'goal_type', 'target_type', 'target_id', 'claimant_type',
        'claimant_id', 'is_achieved', 'war_score_value',
    ];

    protected function casts(): array
    {
        return [
            'is_achieved' => 'boolean',
        ];
    }

    public function war(): BelongsTo
    {
        return $this->belongsTo(War::class);
    }

    public function getTargetAttribute(): ?Model
    {
        return match ($this->target_type) {
            'village' => Village::find($this->target_id),
            'town' => Town::find($this->target_id),
            'castle' => Castle::find($this->target_id),
            'barony' => Barony::find($this->target_id),
            'kingdom' => Kingdom::find($this->target_id),
            default => null,
        };
    }

    public function getClaimantAttribute(): ?Model
    {
        return match ($this->claimant_type) {
            'player' => User::find($this->claimant_id),
            'kingdom' => Kingdom::find($this->claimant_id),
            'barony' => Barony::find($this->claimant_id),
            default => null,
        };
    }

    public function scopeAchieved($query)
    {
        return $query->where('is_achieved', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_achieved', false);
    }
}

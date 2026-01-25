<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Siege extends Model
{
    use HasFactory;

    const STATUS_ACTIVE = 'active';
    const STATUS_ASSAULT = 'assault';
    const STATUS_BREACHED = 'breached';
    const STATUS_CAPTURED = 'captured';
    const STATUS_LIFTED = 'lifted';
    const STATUS_ABANDONED = 'abandoned';

    protected $fillable = [
        'war_id', 'attacking_army_id', 'target_type', 'target_id', 'status',
        'fortification_level', 'garrison_strength', 'garrison_morale',
        'supplies_remaining', 'days_besieged', 'has_breach', 'siege_equipment',
        'siege_log', 'started_at', 'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'has_breach' => 'boolean',
            'siege_equipment' => 'array',
            'siege_log' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function war(): BelongsTo
    {
        return $this->belongsTo(War::class);
    }

    public function attackingArmy(): BelongsTo
    {
        return $this->belongsTo(Army::class, 'attacking_army_id');
    }

    public function getTargetAttribute(): ?Model
    {
        return match ($this->target_type) {
            'castle' => Castle::find($this->target_id),
            'town' => Town::find($this->target_id),
            'village' => Village::find($this->target_id),
            default => null,
        };
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_ASSAULT, self::STATUS_BREACHED]);
    }

    public function isEnded(): bool
    {
        return in_array($this->status, [self::STATUS_CAPTURED, self::STATUS_LIFTED, self::STATUS_ABANDONED]);
    }

    public function canAssault(): bool
    {
        return $this->has_breach || $this->fortification_level <= 20;
    }

    public function isStarving(): bool
    {
        return $this->supplies_remaining <= 0;
    }

    public function getAssaultDifficultyAttribute(): int
    {
        $base = 50;
        if ($this->has_breach) {
            $base -= 20;
        }
        $base += ($this->fortification_level / 5);
        $base += ($this->garrison_morale / 10);
        return max(10, min(90, $base));
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_ASSAULT, self::STATUS_BREACHED]);
    }

    public function scopeAtTarget($query, string $targetType, int $targetId)
    {
        return $query->where('target_type', $targetType)
            ->where('target_id', $targetId);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Battle extends Model
{
    use HasFactory;

    const TYPE_FIELD = 'field';
    const TYPE_SIEGE_ASSAULT = 'siege_assault';
    const TYPE_NAVAL = 'naval';
    const TYPE_SKIRMISH = 'skirmish';

    const STATUS_ONGOING = 'ongoing';
    const STATUS_ATTACKER_VICTORY = 'attacker_victory';
    const STATUS_DEFENDER_VICTORY = 'defender_victory';
    const STATUS_DRAW = 'draw';
    const STATUS_INCONCLUSIVE = 'inconclusive';

    const PHASE_ENGAGEMENT = 'engagement';
    const PHASE_MELEE = 'melee';
    const PHASE_PURSUIT = 'pursuit';
    const PHASE_AFTERMATH = 'aftermath';

    protected $fillable = [
        'name', 'war_id', 'location_type', 'location_id', 'battle_type',
        'status', 'phase', 'day', 'attacker_troops_start', 'defender_troops_start',
        'attacker_casualties', 'defender_casualties', 'battle_log',
        'terrain_modifiers', 'weather_modifiers', 'started_at', 'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'battle_log' => 'array',
            'terrain_modifiers' => 'array',
            'weather_modifiers' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function war(): BelongsTo
    {
        return $this->belongsTo(War::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(BattleParticipant::class);
    }

    public function getLocationAttribute(): ?Model
    {
        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'town' => Town::find($this->location_id),
            'castle' => Castle::find($this->location_id),
            default => null,
        };
    }

    public function getAttackersAttribute()
    {
        return $this->participants()->where('side', 'attacker')->with('army')->get();
    }

    public function getDefendersAttribute()
    {
        return $this->participants()->where('side', 'defender')->with('army')->get();
    }

    public function getTotalCasualtiesAttribute(): int
    {
        return $this->attacker_casualties + $this->defender_casualties;
    }

    public function isOngoing(): bool
    {
        return $this->status === self::STATUS_ONGOING;
    }

    public function isEnded(): bool
    {
        return in_array($this->status, [
            self::STATUS_ATTACKER_VICTORY,
            self::STATUS_DEFENDER_VICTORY,
            self::STATUS_DRAW,
            self::STATUS_INCONCLUSIVE,
        ]);
    }

    public function scopeOngoing($query)
    {
        return $query->where('status', self::STATUS_ONGOING);
    }

    public function scopeAtLocation($query, string $locationType, int $locationId)
    {
        return $query->where('location_type', $locationType)
            ->where('location_id', $locationId);
    }
}

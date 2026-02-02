<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Army extends Model
{
    use HasFactory;

    const STATUS_MUSTERING = 'mustering';

    const STATUS_MARCHING = 'marching';

    const STATUS_ENCAMPED = 'encamped';

    const STATUS_BESIEGING = 'besieging';

    const STATUS_IN_BATTLE = 'in_battle';

    const STATUS_DISBANDED = 'disbanded';

    const RENAME_COOLDOWN_DAYS = 90; // 3 months

    protected $fillable = [
        'name', 'commander_id', 'npc_commander_id', 'owner_type', 'owner_id',
        'location_type', 'location_id', 'status', 'morale', 'supplies',
        'daily_supply_cost', 'gold_upkeep', 'treasury', 'composition', 'mustered_at',
        'last_renamed_at',
    ];

    protected function casts(): array
    {
        return [
            'composition' => 'array',
            'mustered_at' => 'datetime',
            'last_renamed_at' => 'datetime',
        ];
    }

    public function canRename(): bool
    {
        if (! $this->last_renamed_at) {
            return true;
        }

        return $this->last_renamed_at->addDays(self::RENAME_COOLDOWN_DAYS)->isPast();
    }

    public function nextRenameAt(): ?\Carbon\Carbon
    {
        if (! $this->last_renamed_at) {
            return null;
        }

        return $this->last_renamed_at->addDays(self::RENAME_COOLDOWN_DAYS);
    }

    public function commander(): BelongsTo
    {
        return $this->belongsTo(User::class, 'commander_id');
    }

    public function npcCommander(): BelongsTo
    {
        return $this->belongsTo(Npc::class, 'npc_commander_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(ArmyUnit::class);
    }

    public function supplyLines(): HasMany
    {
        return $this->hasMany(SupplyLine::class);
    }

    public function battleParticipations(): HasMany
    {
        return $this->hasMany(BattleParticipant::class);
    }

    public function getOwnerAttribute(): ?Model
    {
        return match ($this->owner_type) {
            'kingdom' => Kingdom::find($this->owner_id),
            'barony' => Barony::find($this->owner_id),
            'player' => User::find($this->owner_id),
            'mercenary' => MercenaryCompany::find($this->owner_id),
            default => null,
        };
    }

    public function getLocationAttribute(): ?Model
    {
        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'town' => Town::find($this->location_id),
            'castle' => Castle::find($this->location_id),
            'field' => null,
            default => null,
        };
    }

    public function getTotalTroopsAttribute(): int
    {
        return $this->units()->sum('count');
    }

    public function getTotalAttackAttribute(): int
    {
        return $this->units->sum(fn ($unit) => $unit->count * $unit->attack);
    }

    public function getTotalDefenseAttribute(): int
    {
        return $this->units->sum(fn ($unit) => $unit->count * $unit->defense);
    }

    public function isOperational(): bool
    {
        return ! in_array($this->status, [self::STATUS_DISBANDED]);
    }

    public function hasSupplies(): bool
    {
        return $this->supplies > 0;
    }

    public function scopeOperational($query)
    {
        return $query->where('status', '!=', self::STATUS_DISBANDED);
    }

    public function scopeAtLocation($query, string $locationType, int $locationId)
    {
        return $query->where('location_type', $locationType)
            ->where('location_id', $locationId);
    }
}

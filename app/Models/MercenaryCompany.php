<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MercenaryCompany extends Model
{
    use HasFactory;

    const REPUTATION_UNKNOWN = 'unknown';
    const REPUTATION_POOR = 'poor';
    const REPUTATION_AVERAGE = 'average';
    const REPUTATION_GOOD = 'good';
    const REPUTATION_LEGENDARY = 'legendary';

    const SPECIALIZATION_CAVALRY = 'cavalry';
    const SPECIALIZATION_SIEGE = 'siege';
    const SPECIALIZATION_INFANTRY = 'infantry';
    const SPECIALIZATION_ARCHERS = 'archers';

    protected $fillable = [
        'name', 'reputation', 'army_id', 'hired_by_id', 'hired_by_type',
        'hired_by_entity_id', 'hire_cost', 'daily_cost', 'contract_days_remaining',
        'specialization', 'home_region', 'is_available', 'history',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'history' => 'array',
        ];
    }

    public function army(): BelongsTo
    {
        return $this->belongsTo(Army::class);
    }

    public function hiredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hired_by_id');
    }

    public function getEmployerAttribute(): ?Model
    {
        if ($this->hired_by_type === 'player') {
            return User::find($this->hired_by_id);
        }
        return match ($this->hired_by_type) {
            'kingdom' => Kingdom::find($this->hired_by_entity_id),
            'barony' => Barony::find($this->hired_by_entity_id),
            default => null,
        };
    }

    public function isHired(): bool
    {
        return !$this->is_available && $this->contract_days_remaining > 0;
    }

    public function getReputationModifierAttribute(): float
    {
        return match ($this->reputation) {
            self::REPUTATION_POOR => 0.8,
            self::REPUTATION_UNKNOWN => 1.0,
            self::REPUTATION_AVERAGE => 1.0,
            self::REPUTATION_GOOD => 1.2,
            self::REPUTATION_LEGENDARY => 1.5,
            default => 1.0,
        };
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeBySpecialization($query, string $specialization)
    {
        return $query->where('specialization', $specialization);
    }
}

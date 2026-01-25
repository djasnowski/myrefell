<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyLine extends Model
{
    use HasFactory;

    const STATUS_ACTIVE = 'active';
    const STATUS_DISRUPTED = 'disrupted';
    const STATUS_SEVERED = 'severed';

    protected $fillable = [
        'army_id', 'source_type', 'source_id', 'status', 'supply_rate',
        'distance', 'safety', 'route',
    ];

    protected function casts(): array
    {
        return [
            'route' => 'array',
        ];
    }

    public function army(): BelongsTo
    {
        return $this->belongsTo(Army::class);
    }

    public function getSourceAttribute(): ?Model
    {
        return match ($this->source_type) {
            'village' => Village::find($this->source_id),
            'town' => Town::find($this->source_id),
            'castle' => Castle::find($this->source_id),
            default => null,
        };
    }

    public function getEffectiveSupplyRateAttribute(): int
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => $this->supply_rate,
            self::STATUS_DISRUPTED => (int) ($this->supply_rate * 0.5),
            self::STATUS_SEVERED => 0,
            default => 0,
        };
    }

    public function getDisruptionChanceAttribute(): int
    {
        // Base chance modified by safety and distance
        $base = 5;
        $base += max(0, $this->distance - 3) * 2;
        $base -= (int) ($this->safety / 20);
        return max(1, min(50, $base));
    }

    public function isOperational(): bool
    {
        return $this->status !== self::STATUS_SEVERED;
    }

    public function scopeOperational($query)
    {
        return $query->where('status', '!=', self::STATUS_SEVERED);
    }
}

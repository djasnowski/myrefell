<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiseaseOutbreak extends Model
{
    use HasFactory;

    public const STATUS_EMERGING = 'emerging';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DECLINING = 'declining';
    public const STATUS_CONTAINED = 'contained';
    public const STATUS_ENDED = 'ended';

    protected $fillable = [
        'disease_type_id', 'location_type', 'location_id', 'status',
        'infected_count', 'recovered_count', 'death_count', 'peak_infected',
        'started_at', 'peaked_at', 'ended_at', 'is_quarantined',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'peaked_at' => 'datetime',
            'ended_at' => 'datetime',
            'is_quarantined' => 'boolean',
        ];
    }

    public function diseaseType(): BelongsTo
    {
        return $this->belongsTo(DiseaseType::class);
    }

    public function infections(): HasMany
    {
        return $this->hasMany(DiseaseInfection::class);
    }

    public function quarantineOrders(): HasMany
    {
        return $this->hasMany(QuarantineOrder::class);
    }

    public function getLocationAttribute(): ?Model
    {
        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'town' => Town::find($this->location_id),
            'barony' => Barony::find($this->location_id),
            default => null,
        };
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_EMERGING, self::STATUS_ACTIVE, self::STATUS_DECLINING]);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_EMERGING, self::STATUS_ACTIVE, self::STATUS_DECLINING]);
    }
}

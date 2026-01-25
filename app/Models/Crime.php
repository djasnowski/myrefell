<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Crime extends Model
{
    public const STATUS_UNDETECTED = 'undetected';
    public const STATUS_REPORTED = 'reported';
    public const STATUS_UNDER_INVESTIGATION = 'under_investigation';
    public const STATUS_TRIAL_PENDING = 'trial_pending';
    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'crime_type_id',
        'perpetrator_id',
        'victim_id',
        'location_type',
        'location_id',
        'description',
        'evidence',
        'status',
        'committed_at',
        'detected_at',
    ];

    protected function casts(): array
    {
        return [
            'evidence' => 'array',
            'committed_at' => 'datetime',
            'detected_at' => 'datetime',
        ];
    }

    public function crimeType(): BelongsTo
    {
        return $this->belongsTo(CrimeType::class);
    }

    public function perpetrator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'perpetrator_id');
    }

    public function victim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'victim_id');
    }

    public function witnesses(): HasMany
    {
        return $this->hasMany(CrimeWitness::class);
    }

    public function accusations(): HasMany
    {
        return $this->hasMany(Accusation::class);
    }

    public function trial(): HasOne
    {
        return $this->hasOne(Trial::class);
    }

    public function bounties(): HasMany
    {
        return $this->hasMany(Bounty::class);
    }

    public function getLocation(): Model|null
    {
        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'barony' => Barony::find($this->location_id),
            'kingdom' => Kingdom::find($this->location_id),
            default => null,
        };
    }

    public function isUndetected(): bool
    {
        return $this->status === self::STATUS_UNDETECTED;
    }

    public function isReported(): bool
    {
        return $this->status === self::STATUS_REPORTED;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function hasWitnesses(): bool
    {
        return $this->witnesses()->count() > 0;
    }

    public function detect(): void
    {
        if ($this->status === self::STATUS_UNDETECTED) {
            $this->update([
                'status' => self::STATUS_REPORTED,
                'detected_at' => now(),
            ]);
        }
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNot('status', self::STATUS_RESOLVED);
    }

    public function scopeAtLocation($query, string $type, int $id)
    {
        return $query->where('location_type', $type)->where('location_id', $id);
    }

    public function scopeByPerpetrator($query, int $userId)
    {
        return $query->where('perpetrator_id', $userId);
    }

    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_UNDETECTED => 'Undetected',
            self::STATUS_REPORTED => 'Reported',
            self::STATUS_UNDER_INVESTIGATION => 'Under Investigation',
            self::STATUS_TRIAL_PENDING => 'Trial Pending',
            self::STATUS_RESOLVED => 'Resolved',
            default => 'Unknown',
        };
    }
}

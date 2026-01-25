<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Accusation extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FALSE_ACCUSATION = 'false_accusation';
    public const STATUS_WITHDRAWN = 'withdrawn';

    protected $fillable = [
        'crime_id',
        'accuser_id',
        'accused_id',
        'crime_type_id',
        'location_type',
        'location_id',
        'accusation_text',
        'evidence_provided',
        'status',
        'reviewed_by',
        'review_notes',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'evidence_provided' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function crime(): BelongsTo
    {
        return $this->belongsTo(Crime::class);
    }

    public function accuser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accuser_id');
    }

    public function accused(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accused_id');
    }

    public function crimeType(): BelongsTo
    {
        return $this->belongsTo(CrimeType::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function trial(): HasOne
    {
        return $this->hasOne(Trial::class);
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

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isFalse(): bool
    {
        return $this->status === self::STATUS_FALSE_ACCUSATION;
    }

    public function accept(User $reviewer, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'reviewed_by' => $reviewer->id,
            'review_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }

    public function reject(User $reviewer, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'review_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }

    public function markAsFalse(User $reviewer, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_FALSE_ACCUSATION,
            'reviewed_by' => $reviewer->id,
            'review_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAtLocation($query, string $type, int $id)
    {
        return $query->where('location_type', $type)->where('location_id', $id);
    }

    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_FALSE_ACCUSATION => 'False Accusation',
            self::STATUS_WITHDRAWN => 'Withdrawn',
            default => 'Unknown',
        };
    }
}

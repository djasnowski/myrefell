<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoyalEvent extends Model
{
    use HasFactory;

    public const TYPE_CORONATION = 'coronation';
    public const TYPE_ROYAL_WEDDING = 'royal_wedding';
    public const TYPE_ROYAL_FUNERAL = 'royal_funeral';
    public const TYPE_DECLARATION = 'declaration';
    public const TYPE_TREATY_SIGNING = 'treaty_signing';

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'event_type',
        'location_type',
        'location_id',
        'title',
        'description',
        'status',
        'scheduled_at',
        'completed_at',
        'primary_participant_id',
        'secondary_participant_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function primaryParticipant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_participant_id');
    }

    public function secondaryParticipant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'secondary_participant_id');
    }

    public function getLocationAttribute(): ?Model
    {
        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'town' => Town::find($this->location_id),
            'barony' => Barony::find($this->location_id),
            'kingdom' => Kingdom::find($this->location_id),
            default => null,
        };
    }

    public function getEventTypeNameAttribute(): string
    {
        return match ($this->event_type) {
            self::TYPE_CORONATION => 'Coronation',
            self::TYPE_ROYAL_WEDDING => 'Royal Wedding',
            self::TYPE_ROYAL_FUNERAL => 'Royal Funeral',
            self::TYPE_DECLARATION => 'Royal Declaration',
            self::TYPE_TREATY_SIGNING => 'Treaty Signing',
            default => ucwords(str_replace('_', ' ', $this->event_type)),
        };
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->where('scheduled_at', '>', now());
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Festival extends Model
{
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'festival_type_id',
        'location_type',
        'location_id',
        'name',
        'status',
        'starts_at',
        'ends_at',
        'budget',
        'organized_by_user_id',
        'attendance_count',
        'results',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'budget' => 'integer',
            'attendance_count' => 'integer',
            'results' => 'array',
        ];
    }

    public function festivalType(): BelongsTo
    {
        return $this->belongsTo(FestivalType::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organized_by_user_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(FestivalParticipant::class);
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
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

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->where('starts_at', '>', now());
    }
}

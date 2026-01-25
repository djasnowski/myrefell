<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasFactory;

    public const STATUS_REGISTRATION = 'registration';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tournament_type_id',
        'festival_id',
        'location_type',
        'location_id',
        'name',
        'status',
        'registration_ends_at',
        'starts_at',
        'completed_at',
        'prize_pool',
        'current_round',
        'total_rounds',
        'sponsored_by_user_id',
        'sponsor_contribution',
    ];

    protected function casts(): array
    {
        return [
            'registration_ends_at' => 'datetime',
            'starts_at' => 'datetime',
            'completed_at' => 'datetime',
            'prize_pool' => 'integer',
            'current_round' => 'integer',
            'total_rounds' => 'integer',
            'sponsor_contribution' => 'integer',
        ];
    }

    public function tournamentType(): BelongsTo
    {
        return $this->belongsTo(TournamentType::class);
    }

    public function festival(): BelongsTo
    {
        return $this->belongsTo(Festival::class);
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsored_by_user_id');
    }

    public function competitors(): HasMany
    {
        return $this->hasMany(TournamentCompetitor::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }

    public function getLocationAttribute(): ?Model
    {
        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'town' => Town::find($this->location_id),
            default => null,
        };
    }

    public function isRegistrationOpen(): bool
    {
        return $this->status === self::STATUS_REGISTRATION
            && $this->registration_ends_at > now();
    }

    public function getCompetitorCountAttribute(): int
    {
        return $this->competitors()->count();
    }

    public function scopeRegistrationOpen($query)
    {
        return $query->where('status', self::STATUS_REGISTRATION)
            ->where('registration_ends_at', '>', now());
    }
}

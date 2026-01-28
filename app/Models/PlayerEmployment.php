<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerEmployment extends Model
{
    use HasFactory;

    public const STATUS_EMPLOYED = 'employed';
    public const STATUS_ON_BREAK = 'on_break';
    public const STATUS_QUIT = 'quit';
    public const STATUS_FIRED = 'fired';

    protected $table = 'player_employment';

    protected $fillable = [
        'user_id',
        'employment_job_id',
        'location_type',
        'location_id',
        'status',
        'hired_at',
        'last_worked_at',
        'times_worked',
        'total_earnings',
        'fired_by',
        'fired_at',
        'fired_reason',
    ];

    protected function casts(): array
    {
        return [
            'location_id' => 'integer',
            'hired_at' => 'datetime',
            'last_worked_at' => 'datetime',
            'times_worked' => 'integer',
            'total_earnings' => 'integer',
            'fired_by' => 'integer',
            'fired_at' => 'datetime',
        ];
    }

    /**
     * Get the user that holds this employment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the job definition.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(EmploymentJob::class, 'employment_job_id');
    }

    /**
     * Check if employment is active.
     */
    public function isEmployed(): bool
    {
        return $this->status === self::STATUS_EMPLOYED;
    }

    /**
     * Check if player is on break.
     */
    public function isOnBreak(): bool
    {
        return $this->status === self::STATUS_ON_BREAK;
    }

    /**
     * Check if player has quit.
     */
    public function hasQuit(): bool
    {
        return $this->status === self::STATUS_QUIT;
    }

    /**
     * Check if player was fired.
     */
    public function wasFired(): bool
    {
        return $this->status === self::STATUS_FIRED;
    }

    /**
     * Get the supervisor who fired this worker.
     */
    public function firedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fired_by');
    }

    /**
     * Check if player can work (cooldown has passed).
     */
    public function canWork(): bool
    {
        if (! $this->isEmployed()) {
            return false;
        }

        if (! $this->last_worked_at) {
            return true;
        }

        return $this->last_worked_at->addMinutes($this->job->cooldown_minutes)->isPast();
    }

    /**
     * Get minutes until next work available.
     */
    public function getMinutesUntilWorkAttribute(): int
    {
        if (! $this->last_worked_at) {
            return 0;
        }

        $availableAt = $this->last_worked_at->addMinutes($this->job->cooldown_minutes);

        if ($availableAt->isPast()) {
            return 0;
        }

        return (int) now()->diffInMinutes($availableAt, false);
    }

    /**
     * Get the location model (polymorphic).
     */
    public function getLocationAttribute(): Village|Barony|Town|Duchy|Kingdom|null
    {
        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'barony' => Barony::find($this->location_id),
            'town' => Town::find($this->location_id),
            'duchy' => Duchy::find($this->location_id),
            'kingdom' => Kingdom::find($this->location_id),
            default => null,
        };
    }

    /**
     * Get the location name.
     */
    public function getLocationNameAttribute(): string
    {
        return $this->location?->name ?? 'Unknown';
    }
}

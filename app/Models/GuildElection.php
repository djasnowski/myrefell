<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuildElection extends Model
{
    use HasFactory;

    public const STATUS_NOMINATION = 'nomination';
    public const STATUS_VOTING = 'voting';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_NOMINATION,
        self::STATUS_VOTING,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    // Election timing (in days)
    public const NOMINATION_PERIOD_DAYS = 3;
    public const VOTING_PERIOD_DAYS = 4;

    protected $fillable = [
        'guild_id',
        'status',
        'winner_id',
        'nomination_ends_at',
        'voting_ends_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'nomination_ends_at' => 'datetime',
            'voting_ends_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the guild.
     */
    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class);
    }

    /**
     * Get the winner.
     */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    /**
     * Get the candidates.
     */
    public function candidates(): HasMany
    {
        return $this->hasMany(GuildElectionCandidate::class);
    }

    /**
     * Get the votes.
     */
    public function votes(): HasMany
    {
        return $this->hasMany(GuildElectionVote::class);
    }

    /**
     * Check if in nomination phase.
     */
    public function isInNominationPhase(): bool
    {
        return $this->status === self::STATUS_NOMINATION
            && now()->lt($this->nomination_ends_at);
    }

    /**
     * Check if in voting phase.
     */
    public function isInVotingPhase(): bool
    {
        return $this->status === self::STATUS_VOTING
            && now()->lt($this->voting_ends_at);
    }

    /**
     * Check if election is complete.
     */
    public function isComplete(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Get status display.
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_NOMINATION => 'Nomination Phase',
            self::STATUS_VOTING => 'Voting Phase',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NoConfidenceVote extends Model
{
    use HasFactory;

    /**
     * Vote statuses.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_PASSED = 'passed';
    public const STATUS_FAILED = 'failed';

    /**
     * Duration in hours for no confidence votes.
     */
    public const DURATION_HOURS = 48;

    /**
     * Minimum quorum percentage (majority needed).
     */
    public const QUORUM_PERCENTAGE = 0.5;

    /**
     * Roles that can be challenged with no confidence votes.
     */
    public const CHALLENGEABLE_ROLES = [
        // Village roles
        'elder',
        'blacksmith',
        'merchant',
        'guard_captain',
        'healer',
        // Town roles
        'mayor',
        // Kingdom roles
        'king',
    ];

    protected $fillable = [
        'target_player_id',
        'target_role',
        'domain_type',
        'domain_id',
        'initiated_by_user_id',
        'status',
        'voting_starts_at',
        'voting_ends_at',
        'finalized_at',
        'votes_for',
        'votes_against',
        'votes_cast',
        'quorum_required',
        'quorum_met',
        'reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'voting_starts_at' => 'datetime',
            'voting_ends_at' => 'datetime',
            'finalized_at' => 'datetime',
            'votes_for' => 'integer',
            'votes_against' => 'integer',
            'votes_cast' => 'integer',
            'quorum_required' => 'integer',
            'quorum_met' => 'boolean',
        ];
    }

    /**
     * Get the player being challenged.
     */
    public function targetPlayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_player_id');
    }

    /**
     * Get the user who initiated the vote.
     */
    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    /**
     * Get the domain (village, town, or kingdom) this vote is for.
     */
    public function domain(): MorphTo
    {
        return $this->morphTo('domain', 'domain_type', 'domain_id');
    }

    /**
     * Get all ballots cast in this vote.
     */
    public function ballots(): HasMany
    {
        return $this->hasMany(NoConfidenceBallot::class);
    }

    /**
     * Check if the vote is currently open for voting.
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN
            && $this->voting_starts_at?->isPast()
            && $this->voting_ends_at?->isFuture();
    }

    /**
     * Check if a user can vote in this no confidence vote.
     */
    public function canVote(User $user): bool
    {
        if (! $this->isOpen()) {
            return false;
        }

        // Check if user has already voted
        if ($this->ballots()->where('voter_user_id', $user->id)->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Check if a user has voted.
     */
    public function hasVoted(User $user): bool
    {
        return $this->ballots()->where('voter_user_id', $user->id)->exists();
    }

    /**
     * Check if the voting period has ended.
     */
    public function hasEnded(): bool
    {
        return $this->voting_ends_at?->isPast() ?? false;
    }

    /**
     * Get the percentage of votes for removal.
     */
    public function getPercentageFor(): float
    {
        if ($this->votes_cast === 0) {
            return 0;
        }

        return round(($this->votes_for / $this->votes_cast) * 100, 1);
    }

    /**
     * Get the percentage of votes against removal.
     */
    public function getPercentageAgainst(): float
    {
        if ($this->votes_cast === 0) {
            return 0;
        }

        return round(($this->votes_against / $this->votes_cast) * 100, 1);
    }

    /**
     * Check if the vote has passed (majority voted for removal).
     */
    public function hasPassed(): bool
    {
        return $this->votes_for > $this->votes_against;
    }

    /**
     * Check if a role is challengeable.
     */
    public static function isRoleChallengeable(string $role): bool
    {
        return in_array($role, self::CHALLENGEABLE_ROLES);
    }

    /**
     * Get the domain type for a given role.
     */
    public static function getDomainTypeForRole(string $role): string
    {
        if (in_array($role, ['elder', 'blacksmith', 'merchant', 'guard_captain', 'healer'])) {
            return 'village';
        }

        if ($role === 'mayor') {
            return 'town';
        }

        if ($role === 'king') {
            return 'kingdom';
        }

        return 'village';
    }
}

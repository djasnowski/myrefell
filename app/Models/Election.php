<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Election extends Model
{
    use HasFactory;

    /**
     * Election types.
     */
    public const TYPES = [
        'village_role',
        'mayor',
        'king',
    ];

    /**
     * Village roles that can be elected.
     */
    public const VILLAGE_ROLES = [
        'elder',
        'blacksmith',
        'merchant',
        'guard_captain',
        'healer',
    ];

    /**
     * Election statuses.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Quorum requirements by election type.
     */
    public const QUORUM = [
        'village_role' => 5,
        'mayor' => 10,
        'king' => 20,
    ];

    /**
     * Duration in hours by election type.
     */
    public const DURATION_HOURS = [
        'village_role' => 24,
        'mayor' => 24,
        'king' => 48,
    ];

    protected $fillable = [
        'election_type',
        'role',
        'domain_type',
        'domain_id',
        'status',
        'voting_starts_at',
        'voting_ends_at',
        'finalized_at',
        'quorum_required',
        'votes_cast',
        'quorum_met',
        'winner_user_id',
        'is_self_appointment',
        'initiated_by_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'voting_starts_at' => 'datetime',
            'voting_ends_at' => 'datetime',
            'finalized_at' => 'datetime',
            'quorum_required' => 'integer',
            'votes_cast' => 'integer',
            'quorum_met' => 'boolean',
            'is_self_appointment' => 'boolean',
        ];
    }

    /**
     * Get the domain (village, town, or kingdom) this election is for.
     */
    public function domain(): MorphTo
    {
        return $this->morphTo('domain', 'domain_type', 'domain_id');
    }

    /**
     * Get all candidates for this election.
     */
    public function candidates(): HasMany
    {
        return $this->hasMany(ElectionCandidate::class);
    }

    /**
     * Get active candidates for this election.
     */
    public function activeCandidates(): HasMany
    {
        return $this->hasMany(ElectionCandidate::class)->where('is_active', true);
    }

    /**
     * Get all votes cast in this election.
     */
    public function votes(): HasMany
    {
        return $this->hasMany(ElectionVote::class);
    }

    /**
     * Get the election winner.
     */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    /**
     * Get the user who initiated this election.
     */
    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    /**
     * Check if the election is currently open for voting.
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN
            && $this->voting_starts_at?->isPast()
            && $this->voting_ends_at?->isFuture();
    }

    /**
     * Check if a user can vote in this election.
     */
    public function canVote(User $user): bool
    {
        if (! $this->isOpen()) {
            return false;
        }

        // Check if user has already voted
        if ($this->votes()->where('voter_user_id', $user->id)->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the voting period has ended.
     */
    public function hasEnded(): bool
    {
        return $this->voting_ends_at?->isPast() ?? false;
    }

    /**
     * Check if this is a village role election.
     */
    public function isVillageRoleElection(): bool
    {
        return $this->election_type === 'village_role';
    }

    /**
     * Check if this is a mayor election.
     */
    public function isMayorElection(): bool
    {
        return $this->election_type === 'mayor';
    }

    /**
     * Check if this is a king election.
     */
    public function isKingElection(): bool
    {
        return $this->election_type === 'king';
    }

    /**
     * Get the default quorum for an election type.
     */
    public static function getQuorumForType(string $type): int
    {
        return self::QUORUM[$type] ?? 5;
    }

    /**
     * Get the default duration in hours for an election type.
     */
    public static function getDurationForType(string $type): int
    {
        return self::DURATION_HOURS[$type] ?? 24;
    }
}

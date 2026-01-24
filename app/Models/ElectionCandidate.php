<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElectionCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'user_id',
        'platform',
        'declared_at',
        'withdrawn_at',
        'is_active',
        'vote_count',
    ];

    protected function casts(): array
    {
        return [
            'declared_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'is_active' => 'boolean',
            'vote_count' => 'integer',
        ];
    }

    /**
     * Get the election this candidacy is for.
     */
    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    /**
     * Get the user who is the candidate.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get votes for this candidate.
     */
    public function votes(): HasMany
    {
        return $this->hasMany(ElectionVote::class, 'candidate_id');
    }

    /**
     * Withdraw this candidacy.
     */
    public function withdraw(): bool
    {
        $this->is_active = false;
        $this->withdrawn_at = now();

        return $this->save();
    }

    /**
     * Check if this candidacy is still active.
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->withdrawn_at === null;
    }

    /**
     * Increment the vote count.
     */
    public function incrementVoteCount(): void
    {
        $this->increment('vote_count');
    }
}

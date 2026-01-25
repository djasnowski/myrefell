<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoConfidenceBallot extends Model
{
    use HasFactory;

    protected $fillable = [
        'no_confidence_vote_id',
        'voter_user_id',
        'vote_for_removal',
        'voted_at',
    ];

    protected function casts(): array
    {
        return [
            'vote_for_removal' => 'boolean',
            'voted_at' => 'datetime',
        ];
    }

    /**
     * Get the no confidence vote this ballot belongs to.
     */
    public function noConfidenceVote(): BelongsTo
    {
        return $this->belongsTo(NoConfidenceVote::class);
    }

    /**
     * Get the user who cast this ballot.
     */
    public function voter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voter_user_id');
    }

    /**
     * Check if this is a vote for removal.
     */
    public function isForRemoval(): bool
    {
        return $this->vote_for_removal;
    }

    /**
     * Check if this is a vote against removal.
     */
    public function isAgainstRemoval(): bool
    {
        return ! $this->vote_for_removal;
    }
}

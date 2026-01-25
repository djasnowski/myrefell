<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuildElectionVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'guild_election_id',
        'voter_id',
        'candidate_id',
    ];

    /**
     * Get the election.
     */
    public function election(): BelongsTo
    {
        return $this->belongsTo(GuildElection::class, 'guild_election_id');
    }

    /**
     * Get the voter.
     */
    public function voter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voter_id');
    }

    /**
     * Get the candidate.
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(GuildElectionCandidate::class, 'candidate_id');
    }
}

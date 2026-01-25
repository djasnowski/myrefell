<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuildElectionCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'guild_election_id',
        'user_id',
        'platform',
        'votes',
    ];

    protected function casts(): array
    {
        return [
            'votes' => 'integer',
        ];
    }

    /**
     * Get the election.
     */
    public function election(): BelongsTo
    {
        return $this->belongsTo(GuildElection::class, 'guild_election_id');
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the vote records.
     */
    public function voteRecords(): HasMany
    {
        return $this->hasMany(GuildElectionVote::class, 'candidate_id');
    }
}

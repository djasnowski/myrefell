<?php

namespace App\Services;

use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\ElectionVote;
use App\Models\Kingdom;
use App\Models\PlayerTitle;
use App\Models\Town;
use App\Models\User;
use App\Models\Village;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ElectionService
{
    /**
     * Minimum residents required for election (below this, self-appointment is allowed).
     */
    public const SELF_APPOINT_THRESHOLD = 5;

    /**
     * Check if a village allows self-appointment (less than 5 residents).
     */
    public function canSelfAppoint(Village $village): bool
    {
        return $village->residents()->count() < self::SELF_APPOINT_THRESHOLD;
    }

    /**
     * Self-appoint a user to a village role (for small villages).
     */
    public function selfAppoint(User $user, Village $village, string $role): Election
    {
        if (! $this->canSelfAppoint($village)) {
            throw new \InvalidArgumentException('Village has too many residents for self-appointment. An election is required.');
        }

        if (! in_array($role, Election::VILLAGE_ROLES)) {
            throw new \InvalidArgumentException("Invalid village role: {$role}");
        }

        // Check user is a resident of this village
        if ($user->home_village_id !== $village->id) {
            throw new \InvalidArgumentException('User must be a resident of this village to self-appoint.');
        }

        return DB::transaction(function () use ($user, $village, $role) {
            // Create a completed election record for audit trail
            $election = Election::create([
                'election_type' => 'village_role',
                'role' => $role,
                'domain_type' => 'village',
                'domain_id' => $village->id,
                'status' => Election::STATUS_COMPLETED,
                'voting_starts_at' => now(),
                'voting_ends_at' => now(),
                'finalized_at' => now(),
                'quorum_required' => 0,
                'votes_cast' => 0,
                'quorum_met' => true,
                'winner_user_id' => $user->id,
                'is_self_appointment' => true,
                'initiated_by_user_id' => $user->id,
                'notes' => 'Self-appointment due to village size < 5 residents.',
            ]);

            // Grant the title
            $this->grantElectionTitle($election, $user);

            return $election;
        });
    }

    /**
     * Start a new election.
     */
    public function startElection(
        string $type,
        ?string $role,
        Model $domain,
        User $initiator,
        ?int $durationHours = null
    ): Election {
        // Validate election type
        if (! in_array($type, Election::TYPES)) {
            throw new \InvalidArgumentException("Invalid election type: {$type}");
        }

        // Validate role for village elections
        if ($type === 'village_role') {
            if (! $role || ! in_array($role, Election::VILLAGE_ROLES)) {
                throw new \InvalidArgumentException("Invalid village role: {$role}");
            }
        }

        // Check no active election exists for this domain and role
        $existingElection = Election::where('domain_type', class_basename($domain))
            ->where('domain_id', $domain->id)
            ->where('role', $role)
            ->whereIn('status', [Election::STATUS_PENDING, Election::STATUS_OPEN])
            ->first();

        if ($existingElection) {
            throw new \InvalidArgumentException('An active election already exists for this position.');
        }

        $duration = $durationHours ?? Election::getDurationForType($type);
        $quorum = Election::getQuorumForType($type);

        return Election::create([
            'election_type' => $type,
            'role' => $role,
            'domain_type' => strtolower(class_basename($domain)),
            'domain_id' => $domain->id,
            'status' => Election::STATUS_OPEN,
            'voting_starts_at' => now(),
            'voting_ends_at' => now()->addHours($duration),
            'quorum_required' => $quorum,
            'votes_cast' => 0,
            'quorum_met' => false,
            'is_self_appointment' => false,
            'initiated_by_user_id' => $initiator->id,
        ]);
    }

    /**
     * Declare candidacy for an election.
     */
    public function declareCandidacy(Election $election, User $user, ?string $platform = null): ElectionCandidate
    {
        if (! $election->isOpen()) {
            throw new \InvalidArgumentException('Election is not open for candidacy.');
        }

        // Validate user eligibility to be a candidate
        if (! $this->validateVoterEligibility($election, $user)) {
            throw new \InvalidArgumentException('User is not eligible for this election.');
        }

        // Check if user is already a candidate
        $existing = ElectionCandidate::where('election_id', $election->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            if ($existing->is_active) {
                throw new \InvalidArgumentException('User is already a candidate in this election.');
            }
            // Re-activate withdrawn candidacy
            $existing->is_active = true;
            $existing->withdrawn_at = null;
            $existing->platform = $platform;
            $existing->save();

            return $existing;
        }

        return ElectionCandidate::create([
            'election_id' => $election->id,
            'user_id' => $user->id,
            'platform' => $platform,
            'declared_at' => now(),
            'is_active' => true,
            'vote_count' => 0,
        ]);
    }

    /**
     * Cast a vote for a candidate.
     */
    public function castVote(Election $election, User $voter, ElectionCandidate $candidate): ElectionVote
    {
        if (! $election->isOpen()) {
            throw new \InvalidArgumentException('Election is not open for voting.');
        }

        if (! $election->canVote($voter)) {
            throw new \InvalidArgumentException('User cannot vote in this election.');
        }

        if (! $this->validateVoterEligibility($election, $voter)) {
            throw new \InvalidArgumentException('User is not eligible to vote in this election.');
        }

        if ($candidate->election_id !== $election->id) {
            throw new \InvalidArgumentException('Candidate does not belong to this election.');
        }

        if (! $candidate->is_active) {
            throw new \InvalidArgumentException('Cannot vote for a withdrawn candidate.');
        }

        return DB::transaction(function () use ($election, $voter, $candidate) {
            $vote = ElectionVote::create([
                'election_id' => $election->id,
                'voter_user_id' => $voter->id,
                'candidate_id' => $candidate->id,
                'voted_at' => now(),
            ]);

            // Update vote counts
            $candidate->incrementVoteCount();
            $election->increment('votes_cast');

            // Check if quorum is met
            if ($election->votes_cast >= $election->quorum_required) {
                $election->quorum_met = true;
                $election->save();
            }

            return $vote;
        });
    }

    /**
     * Finalize an election after voting ends.
     */
    public function finalizeElection(Election $election): Election
    {
        if ($election->status !== Election::STATUS_OPEN) {
            throw new \InvalidArgumentException('Election is not open.');
        }

        if (! $election->hasEnded()) {
            throw new \InvalidArgumentException('Election voting period has not ended.');
        }

        return DB::transaction(function () use ($election) {
            // Close the election
            $election->status = Election::STATUS_CLOSED;

            // Check if quorum was met
            if ($election->votes_cast < $election->quorum_required) {
                $election->status = Election::STATUS_FAILED;
                $election->finalized_at = now();
                $election->notes = 'Election failed: Quorum not met.';
                $election->save();

                return $election;
            }

            // Find the winner (candidate with most votes)
            $winner = $election->activeCandidates()
                ->orderByDesc('vote_count')
                ->first();

            if (! $winner) {
                $election->status = Election::STATUS_FAILED;
                $election->finalized_at = now();
                $election->notes = 'Election failed: No active candidates.';
                $election->save();

                return $election;
            }

            $election->winner_user_id = $winner->user_id;
            $election->status = Election::STATUS_COMPLETED;
            $election->finalized_at = now();
            $election->save();

            // Grant the title to the winner
            $this->grantElectionTitle($election, $winner->user);

            return $election;
        });
    }

    /**
     * Validate that a user is eligible to vote in an election.
     */
    public function validateVoterEligibility(Election $election, User $user): bool
    {
        $domain = $election->domain;

        if (! $domain) {
            return false;
        }

        // Village election: user must be a resident of the village
        if ($election->domain_type === 'village') {
            return $user->home_village_id === $domain->id;
        }

        // Town/Mayor election: user's home village must be in the town
        if ($election->domain_type === 'town') {
            $townVillageIds = $domain->villages()->pluck('villages.id');

            return $townVillageIds->contains($user->home_village_id);
        }

        // Kingdom election: user's home village must be in the kingdom
        if ($election->domain_type === 'kingdom') {
            $kingdomVillageIds = $domain->villages()->pluck('id');

            return $kingdomVillageIds->contains($user->home_village_id);
        }

        return false;
    }

    /**
     * Grant the appropriate title to an election winner.
     */
    public function grantElectionTitle(Election $election, User $user): PlayerTitle
    {
        $title = $this->getTitleForElection($election);
        $tier = $this->getTierForElection($election);

        // Revoke any existing title of this type for this domain
        PlayerTitle::where('domain_type', $election->domain_type)
            ->where('domain_id', $election->domain_id)
            ->where('title', $title)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'revoked_at' => now(),
            ]);

        // Create the new title
        $playerTitle = PlayerTitle::create([
            'user_id' => $user->id,
            'title' => $title,
            'tier' => $tier,
            'domain_type' => $election->domain_type,
            'domain_id' => $election->domain_id,
            'acquisition_method' => $election->is_self_appointment ? 'appointment' : 'election',
            'is_active' => true,
            'granted_at' => now(),
        ]);

        // Update user's primary title if this is higher tier
        if ($tier > ($user->title_tier ?? 0)) {
            $user->primary_title = $title;
            $user->title_tier = $tier;
            $user->save();
        }

        // Update domain's leadership reference if applicable
        $this->updateDomainLeadership($election, $user);

        return $playerTitle;
    }

    /**
     * Get the count of eligible voters for an election.
     */
    public function getEligibleVoterCount(Election $election): int
    {
        $domain = $election->domain;

        if (! $domain) {
            return 0;
        }

        if ($election->domain_type === 'village') {
            return $domain->residents()->count();
        }

        if ($election->domain_type === 'town') {
            return User::whereIn('home_village_id', $domain->villages()->pluck('villages.id'))->count();
        }

        if ($election->domain_type === 'kingdom') {
            return User::whereIn('home_village_id', $domain->villages()->pluck('id'))->count();
        }

        return 0;
    }

    /**
     * Get the title name for an election type.
     */
    protected function getTitleForElection(Election $election): string
    {
        if ($election->election_type === 'village_role') {
            return $election->role;
        }

        if ($election->election_type === 'mayor') {
            return 'mayor';
        }

        if ($election->election_type === 'king') {
            return 'king';
        }

        throw new \InvalidArgumentException("Unknown election type: {$election->election_type}");
    }

    /**
     * Get the tier for an election type.
     */
    protected function getTierForElection(Election $election): int
    {
        if ($election->election_type === 'village_role') {
            return PlayerTitle::VILLAGE_ROLES[$election->role] ?? 2;
        }

        if ($election->election_type === 'mayor') {
            return PlayerTitle::MAYOR_TIER;
        }

        if ($election->election_type === 'king') {
            return PlayerTitle::KING_TIER;
        }

        return 1;
    }

    /**
     * Update the domain's leadership reference after an election.
     */
    protected function updateDomainLeadership(Election $election, User $winner): void
    {
        $domain = $election->domain;

        if ($election->election_type === 'mayor' && $domain instanceof Town) {
            $domain->mayor_user_id = $winner->id;
            $domain->save();
        }

        if ($election->election_type === 'king' && $domain instanceof Kingdom) {
            $domain->king_user_id = $winner->id;
            $domain->save();
        }
    }
}

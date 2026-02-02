<?php

namespace App\Services;

use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\ElectionVote;
use App\Models\Kingdom;
use App\Models\NoConfidenceBallot;
use App\Models\NoConfidenceVote;
use App\Models\PlayerTitle;
use App\Models\Town;
use App\Models\User;
use App\Models\Village;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ElectionService
{
    public function __construct(
        protected LegitimacyService $legitimacyService
    ) {}

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
            // Check new polymorphic fields first
            if ($user->home_location_type === 'village' && $user->home_location_id === $domain->id) {
                return true;
            }

            // Fallback to legacy field
            return $user->home_village_id === $domain->id;
        }

        // Town/Mayor election: user must live in the town OR in a village in the same barony
        if ($election->domain_type === 'town') {
            // Direct town resident
            if ($user->home_location_type === 'town' && $user->home_location_id === $domain->id) {
                return true;
            }

            // Village resident in same barony
            if (! $domain->barony) {
                return false;
            }
            $baronyVillageIds = $domain->barony->villages()->pluck('id');

            if ($user->home_location_type === 'village') {
                return $baronyVillageIds->contains($user->home_location_id);
            }

            // Fallback to legacy field
            return $baronyVillageIds->contains($user->home_village_id);
        }

        // Kingdom election: user must live in the kingdom
        if ($election->domain_type === 'kingdom') {
            // Check if user lives in a town in this kingdom
            if ($user->home_location_type === 'town') {
                $homeTown = Town::find($user->home_location_id);

                return $homeTown && $homeTown->barony?->kingdom_id === $domain->id;
            }

            // Check if user lives in a village in this kingdom
            $kingdomVillageIds = $domain->villages()->pluck('id');

            if ($user->home_location_type === 'village') {
                return $kingdomVillageIds->contains($user->home_location_id);
            }

            // Fallback to legacy field
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

        // Revoke any existing title of this type for this domain (previous holder)
        PlayerTitle::where('domain_type', $election->domain_type)
            ->where('domain_id', $election->domain_id)
            ->where('title', $title)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'revoked_at' => now(),
            ]);

        // Revoke ALL existing titles held by this user (one title at a time rule)
        $existingTitles = PlayerTitle::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        foreach ($existingTitles as $existingTitle) {
            // Clear domain leadership for the old title
            $this->clearDomainLeadershipForTitle($existingTitle);

            $existingTitle->is_active = false;
            $existingTitle->revoked_at = now();
            $existingTitle->save();
        }

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
            'legitimacy' => 50, // Start at base legitimacy
        ]);

        // Apply legitimacy based on election results
        if (! $election->is_self_appointment) {
            $this->legitimacyService->handleElectionResult($playerTitle, $election);
        }

        // Always set as primary title (it's the only one)
        $user->primary_title = $title;
        $user->title_tier = $tier;
        $user->save();

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
            // Count direct town residents + village residents in same barony
            $townResidents = User::where('home_location_type', 'town')
                ->where('home_location_id', $domain->id)
                ->count();

            $villageResidents = 0;
            if ($domain->barony) {
                $baronyVillageIds = $domain->barony->villages()->pluck('id');
                $villageResidents = User::where(function ($q) use ($baronyVillageIds) {
                    $q->where('home_location_type', 'village')
                        ->whereIn('home_location_id', $baronyVillageIds);
                })->orWhereIn('home_village_id', $baronyVillageIds)->count();
            }

            return $townResidents + $villageResidents;
        }

        if ($election->domain_type === 'barony') {
            // Count all residents in villages and towns in this barony
            $villageIds = $domain->villages()->pluck('id');
            $townIds = $domain->towns()->pluck('id');

            return User::where(function ($q) use ($villageIds, $townIds) {
                $q->where(function ($sq) use ($villageIds) {
                    $sq->where('home_location_type', 'village')
                        ->whereIn('home_location_id', $villageIds);
                })->orWhere(function ($sq) use ($townIds) {
                    $sq->where('home_location_type', 'town')
                        ->whereIn('home_location_id', $townIds);
                })->orWhereIn('home_village_id', $villageIds);
            })->count();
        }

        if ($election->domain_type === 'kingdom') {
            $kingdomVillageIds = $domain->villages()->pluck('id');
            $kingdomTownIds = Town::whereIn('barony_id', $domain->baronies()->pluck('id'))->pluck('id');

            return User::where(function ($q) use ($kingdomVillageIds, $kingdomTownIds) {
                $q->where(function ($sq) use ($kingdomVillageIds) {
                    $sq->where('home_location_type', 'village')
                        ->whereIn('home_location_id', $kingdomVillageIds);
                })->orWhere(function ($sq) use ($kingdomTownIds) {
                    $sq->where('home_location_type', 'town')
                        ->whereIn('home_location_id', $kingdomTownIds);
                })->orWhereIn('home_village_id', $kingdomVillageIds);
            })->count();
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

    /**
     * Clear domain leadership reference when a title is revoked.
     */
    protected function clearDomainLeadershipForTitle(PlayerTitle $playerTitle): void
    {
        if ($playerTitle->title === 'mayor' && $playerTitle->domain_type === 'town') {
            $town = Town::find($playerTitle->domain_id);
            if ($town && $town->mayor_user_id === $playerTitle->user_id) {
                $town->mayor_user_id = null;
                $town->save();
            }
        }

        if ($playerTitle->title === 'baron' && $playerTitle->domain_type === 'barony') {
            $barony = Barony::find($playerTitle->domain_id);
            if ($barony && $barony->baron_user_id === $playerTitle->user_id) {
                $barony->baron_user_id = null;
                $barony->save();
            }
        }

        if ($playerTitle->title === 'duke' && $playerTitle->domain_type === 'duchy') {
            $duchy = Duchy::find($playerTitle->domain_id);
            if ($duchy && $duchy->duke_user_id === $playerTitle->user_id) {
                $duchy->duke_user_id = null;
                $duchy->save();
            }
        }

        if ($playerTitle->title === 'king' && $playerTitle->domain_type === 'kingdom') {
            $kingdom = Kingdom::find($playerTitle->domain_id);
            if ($kingdom && $kingdom->king_user_id === $playerTitle->user_id) {
                $kingdom->king_user_id = null;
                $kingdom->save();
            }
        }
    }

    /**
     * Start a no confidence vote against a role holder.
     */
    public function startNoConfidenceVote(
        User $initiator,
        User $target,
        string $role,
        Model $domain,
        ?string $reason = null
    ): NoConfidenceVote {
        // Validate the role is challengeable
        if (! NoConfidenceVote::isRoleChallengeable($role)) {
            throw new \InvalidArgumentException("Role '{$role}' cannot be challenged with a no confidence vote.");
        }

        // Validate initiator is eligible (resident of the domain)
        if (! $this->validateNoConfidenceEligibility($initiator, $domain)) {
            throw new \InvalidArgumentException('You are not eligible to initiate a no confidence vote in this domain.');
        }

        // Validate target holds the role in this domain
        if (! $this->validateTargetHoldsRole($target, $role, $domain)) {
            throw new \InvalidArgumentException("Target player does not hold the '{$role}' role in this location.");
        }

        // Check no active no confidence vote exists for this role/domain
        $existingVote = NoConfidenceVote::where('domain_type', strtolower(class_basename($domain)))
            ->where('domain_id', $domain->id)
            ->where('target_role', $role)
            ->whereIn('status', [NoConfidenceVote::STATUS_PENDING, NoConfidenceVote::STATUS_OPEN])
            ->first();

        if ($existingVote) {
            throw new \InvalidArgumentException('An active no confidence vote already exists for this role.');
        }

        // Initiator cannot challenge themselves
        if ($initiator->id === $target->id) {
            throw new \InvalidArgumentException('You cannot initiate a no confidence vote against yourself.');
        }

        $eligibleVoterCount = $this->getNoConfidenceEligibleVoterCount($domain);
        $quorumRequired = max(1, (int) ceil($eligibleVoterCount * NoConfidenceVote::QUORUM_PERCENTAGE));

        return NoConfidenceVote::create([
            'target_player_id' => $target->id,
            'target_role' => $role,
            'domain_type' => strtolower(class_basename($domain)),
            'domain_id' => $domain->id,
            'initiated_by_user_id' => $initiator->id,
            'status' => NoConfidenceVote::STATUS_OPEN,
            'voting_starts_at' => now(),
            'voting_ends_at' => now()->addHours(NoConfidenceVote::DURATION_HOURS),
            'votes_for' => 0,
            'votes_against' => 0,
            'votes_cast' => 0,
            'quorum_required' => $quorumRequired,
            'quorum_met' => false,
            'reason' => $reason,
        ]);
    }

    /**
     * Cast a ballot in a no confidence vote.
     */
    public function castNoConfidenceBallot(
        NoConfidenceVote $vote,
        User $voter,
        bool $voteForRemoval
    ): NoConfidenceBallot {
        if (! $vote->isOpen()) {
            throw new \InvalidArgumentException('This no confidence vote is not open for voting.');
        }

        if (! $vote->canVote($voter)) {
            throw new \InvalidArgumentException('You have already voted or cannot vote in this no confidence vote.');
        }

        if (! $this->validateNoConfidenceEligibility($voter, $vote->domain)) {
            throw new \InvalidArgumentException('You are not eligible to vote in this no confidence vote.');
        }

        return DB::transaction(function () use ($vote, $voter, $voteForRemoval) {
            $ballot = NoConfidenceBallot::create([
                'no_confidence_vote_id' => $vote->id,
                'voter_user_id' => $voter->id,
                'vote_for_removal' => $voteForRemoval,
                'voted_at' => now(),
            ]);

            // Update vote tallies
            if ($voteForRemoval) {
                $vote->increment('votes_for');
            } else {
                $vote->increment('votes_against');
            }
            $vote->increment('votes_cast');

            // Check if quorum is met
            if ($vote->votes_cast >= $vote->quorum_required) {
                $vote->quorum_met = true;
                $vote->save();
            }

            return $ballot;
        });
    }

    /**
     * Finalize a no confidence vote after voting ends.
     */
    public function finalizeNoConfidenceVote(NoConfidenceVote $vote): NoConfidenceVote
    {
        if ($vote->status !== NoConfidenceVote::STATUS_OPEN) {
            throw new \InvalidArgumentException('This no confidence vote is not open.');
        }

        if (! $vote->hasEnded()) {
            throw new \InvalidArgumentException('The voting period has not ended yet.');
        }

        return DB::transaction(function () use ($vote) {
            // Close the vote
            $vote->status = NoConfidenceVote::STATUS_CLOSED;

            // Check if quorum was met
            if (! $vote->quorum_met) {
                $vote->status = NoConfidenceVote::STATUS_FAILED;
                $vote->finalized_at = now();
                $vote->notes = 'Vote failed: Quorum not met.';
                $vote->save();

                return $vote;
            }

            // Determine outcome - majority vote for removal wins
            if ($vote->hasPassed()) {
                $vote->status = NoConfidenceVote::STATUS_PASSED;
                $vote->finalized_at = now();
                $vote->notes = "Vote passed: {$vote->votes_for} for removal, {$vote->votes_against} against.";
                $vote->save();

                // Revoke the title
                $this->revokeRoleFromNoConfidence($vote);
            } else {
                $vote->status = NoConfidenceVote::STATUS_FAILED;
                $vote->finalized_at = now();
                $vote->notes = "Vote failed: {$vote->votes_for} for removal, {$vote->votes_against} against.";
                $vote->save();

                // Apply legitimacy bonus for surviving the vote
                $playerTitle = PlayerTitle::where('user_id', $vote->target_player_id)
                    ->where('domain_type', $vote->domain_type)
                    ->where('domain_id', $vote->domain_id)
                    ->where('title', $vote->target_role)
                    ->where('is_active', true)
                    ->first();

                if ($playerTitle) {
                    $this->legitimacyService->handleNoConfidenceSurvived($playerTitle, $vote);
                }
            }

            return $vote;
        });
    }

    /**
     * Revoke a role after a successful no confidence vote.
     */
    protected function revokeRoleFromNoConfidence(NoConfidenceVote $vote): void
    {
        // Revoke the player's title
        PlayerTitle::where('user_id', $vote->target_player_id)
            ->where('domain_type', $vote->domain_type)
            ->where('domain_id', $vote->domain_id)
            ->where('title', $vote->target_role)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'revoked_at' => now(),
            ]);

        // Update user's primary title if needed
        $target = $vote->targetPlayer;
        if ($target->primary_title === $vote->target_role) {
            // Find their next highest active title
            $nextTitle = PlayerTitle::where('user_id', $target->id)
                ->where('is_active', true)
                ->orderByDesc('tier')
                ->first();

            if ($nextTitle) {
                $target->primary_title = $nextTitle->title;
                $target->title_tier = $nextTitle->tier;
            } else {
                $target->primary_title = 'peasant';
                $target->title_tier = 1;
            }
            $target->save();
        }

        // Clear domain leadership reference if applicable
        $this->clearDomainLeadership($vote);
    }

    /**
     * Clear domain leadership after a no confidence vote passes.
     */
    protected function clearDomainLeadership(NoConfidenceVote $vote): void
    {
        $domain = $vote->domain;

        if ($vote->target_role === 'mayor' && $domain instanceof Town) {
            $domain->mayor_user_id = null;
            $domain->save();
        }

        if ($vote->target_role === 'king' && $domain instanceof Kingdom) {
            $domain->king_user_id = null;
            $domain->save();
        }
    }

    /**
     * Validate that a user is eligible to participate in a no confidence vote.
     */
    public function validateNoConfidenceEligibility(User $user, ?Model $domain): bool
    {
        if (! $domain) {
            return false;
        }

        $domainType = strtolower(class_basename($domain));

        // Village: user must be a resident
        if ($domainType === 'village') {
            if ($user->home_location_type === 'village' && $user->home_location_id === $domain->id) {
                return true;
            }

            return $user->home_village_id === $domain->id;
        }

        // Town: user must live in the town OR in a village in the same barony
        if ($domainType === 'town') {
            if ($user->home_location_type === 'town' && $user->home_location_id === $domain->id) {
                return true;
            }

            if (! $domain->barony) {
                return false;
            }
            $baronyVillageIds = $domain->barony->villages()->pluck('id');

            if ($user->home_location_type === 'village') {
                return $baronyVillageIds->contains($user->home_location_id);
            }

            return $baronyVillageIds->contains($user->home_village_id);
        }

        // Barony: user must live in a village or town in this barony
        if ($domainType === 'barony') {
            $villageIds = $domain->villages()->pluck('id');
            $townIds = $domain->towns()->pluck('id');

            if ($user->home_location_type === 'village') {
                return $villageIds->contains($user->home_location_id);
            }
            if ($user->home_location_type === 'town') {
                return $townIds->contains($user->home_location_id);
            }

            return $villageIds->contains($user->home_village_id);
        }

        // Kingdom: user must live in the kingdom
        if ($domainType === 'kingdom') {
            if ($user->home_location_type === 'town') {
                $homeTown = Town::find($user->home_location_id);

                return $homeTown && $homeTown->barony?->kingdom_id === $domain->id;
            }

            $kingdomVillageIds = $domain->villages()->pluck('id');

            if ($user->home_location_type === 'village') {
                return $kingdomVillageIds->contains($user->home_location_id);
            }

            return $kingdomVillageIds->contains($user->home_village_id);
        }

        return false;
    }

    /**
     * Validate that the target holds the specified role in the domain.
     */
    public function validateTargetHoldsRole(User $target, string $role, Model $domain): bool
    {
        $domainType = strtolower(class_basename($domain));

        return PlayerTitle::where('user_id', $target->id)
            ->where('domain_type', $domainType)
            ->where('domain_id', $domain->id)
            ->where('title', $role)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get the count of eligible voters for a no confidence vote.
     */
    public function getNoConfidenceEligibleVoterCount(Model $domain): int
    {
        $domainType = strtolower(class_basename($domain));

        if ($domainType === 'village') {
            return $domain->residents()->count();
        }

        if ($domainType === 'town') {
            $townResidents = User::where('home_location_type', 'town')
                ->where('home_location_id', $domain->id)
                ->count();

            $villageResidents = 0;
            if ($domain->barony) {
                $baronyVillageIds = $domain->barony->villages()->pluck('id');
                $villageResidents = User::where(function ($q) use ($baronyVillageIds) {
                    $q->where('home_location_type', 'village')
                        ->whereIn('home_location_id', $baronyVillageIds);
                })->orWhereIn('home_village_id', $baronyVillageIds)->count();
            }

            return $townResidents + $villageResidents;
        }

        if ($domainType === 'barony') {
            $villageIds = $domain->villages()->pluck('id');
            $townIds = $domain->towns()->pluck('id');

            return User::where(function ($q) use ($villageIds, $townIds) {
                $q->where(function ($sq) use ($villageIds) {
                    $sq->where('home_location_type', 'village')
                        ->whereIn('home_location_id', $villageIds);
                })->orWhere(function ($sq) use ($townIds) {
                    $sq->where('home_location_type', 'town')
                        ->whereIn('home_location_id', $townIds);
                })->orWhereIn('home_village_id', $villageIds);
            })->count();
        }

        if ($domainType === 'kingdom') {
            $kingdomVillageIds = $domain->villages()->pluck('id');
            $kingdomTownIds = Town::whereIn('barony_id', $domain->baronies()->pluck('id'))->pluck('id');

            return User::where(function ($q) use ($kingdomVillageIds, $kingdomTownIds) {
                $q->where(function ($sq) use ($kingdomVillageIds) {
                    $sq->where('home_location_type', 'village')
                        ->whereIn('home_location_id', $kingdomVillageIds);
                })->orWhere(function ($sq) use ($kingdomTownIds) {
                    $sq->where('home_location_type', 'town')
                        ->whereIn('home_location_id', $kingdomTownIds);
                })->orWhereIn('home_village_id', $kingdomVillageIds);
            })->count();
        }

        return 0;
    }

    /**
     * Get the role holder for a specific role in a domain.
     */
    public function getRoleHolder(string $role, Model $domain): ?User
    {
        $domainType = strtolower(class_basename($domain));

        $title = PlayerTitle::where('domain_type', $domainType)
            ->where('domain_id', $domain->id)
            ->where('title', $role)
            ->where('is_active', true)
            ->first();

        return $title?->user;
    }
}

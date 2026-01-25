<?php

namespace App\Services;

use App\Models\Barony;
use App\Models\Guild;
use App\Models\GuildActivity;
use App\Models\GuildBenefit;
use App\Models\GuildElection;
use App\Models\GuildElectionCandidate;
use App\Models\GuildElectionVote;
use App\Models\GuildMember;
use App\Models\GuildPriceControl;
use App\Models\PlayerSkill;
use App\Models\Town;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GuildService
{
    public function __construct(
        protected EnergyService $energyService
    ) {}

    /**
     * Get available guilds for a player to join at their location.
     */
    public function getAvailableGuilds(User $player): array
    {
        $locationType = $player->current_location_type;
        $locationId = $player->current_location_id;

        // Guilds must be at town or barony
        if (!in_array($locationType, ['town', 'barony'])) {
            return [];
        }

        $guilds = Guild::query()
            ->where('is_active', true)
            ->where('is_public', true)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->with(['founder', 'guildmaster', 'members', 'benefits'])
            ->get()
            ->filter(function ($guild) use ($player) {
                // Exclude guilds player is already a member of
                return !$guild->members->contains('user_id', $player->id);
            })
            ->map(fn ($guild) => $this->formatGuild($guild))
            ->values()
            ->toArray();

        return $guilds;
    }

    /**
     * Get player's guild memberships.
     */
    public function getPlayerGuilds(User $player): array
    {
        return GuildMember::where('user_id', $player->id)
            ->with(['guild', 'guild.benefits', 'guild.founder', 'guild.guildmaster'])
            ->get()
            ->map(fn ($member) => $this->formatMembership($member))
            ->toArray();
    }

    /**
     * Get a guild's details.
     */
    public function getGuildDetails(Guild $guild, User $player): array
    {
        $guild->load(['founder', 'guildmaster', 'members.user', 'benefits', 'priceControls']);

        $membership = GuildMember::where('user_id', $player->id)
            ->where('guild_id', $guild->id)
            ->first();

        $playerSkillLevel = $this->getPlayerSkillLevel($player, $guild->primary_skill);
        $activeElection = $guild->getActiveElection();

        return [
            'guild' => $this->formatGuild($guild),
            'membership' => $membership ? $this->formatMembership($membership) : null,
            'is_member' => $membership !== null,
            'can_join' => $membership === null && $guild->canAcceptMembers() && $playerSkillLevel >= 1,
            'player_skill_level' => $playerSkillLevel,
            'members' => $guild->members->map(fn ($m) => [
                'id' => $m->id,
                'user_id' => $m->user_id,
                'username' => $m->user->username,
                'rank' => $m->rank,
                'rank_display' => $m->rank_display,
                'contribution' => $m->contribution,
                'years_membership' => $m->years_membership,
                'joined_at' => $m->joined_at->toIso8601String(),
            ])->toArray(),
            'active_election' => $activeElection ? $this->formatElection($activeElection) : null,
            'price_controls' => $guild->priceControls->map(fn ($pc) => [
                'id' => $pc->id,
                'item_name' => $pc->item_name,
                'min_price' => $pc->min_price,
                'max_price' => $pc->max_price,
                'min_quality' => $pc->min_quality,
                'is_active' => $pc->is_active,
            ])->toArray(),
        ];
    }

    /**
     * Create a new guild.
     */
    public function createGuild(User $player, string $name, string $description, string $primarySkill): array
    {
        // Validate location
        if (!in_array($player->current_location_type, ['town', 'barony'])) {
            return ['success' => false, 'message' => 'Guilds can only be founded in towns or baronies.'];
        }

        // Check if player is already a guildmaster
        $existingGuildmaster = GuildMember::where('user_id', $player->id)
            ->where('rank', GuildMember::RANK_GUILDMASTER)
            ->exists();

        if ($existingGuildmaster) {
            return ['success' => false, 'message' => 'You are already the guildmaster of another guild.'];
        }

        // Validate name
        if (Guild::where('name', $name)->exists()) {
            return ['success' => false, 'message' => 'A guild with that name already exists.'];
        }

        // Validate skill
        if (!in_array($primarySkill, Guild::GUILD_SKILLS)) {
            return ['success' => false, 'message' => 'Invalid guild skill.'];
        }

        // Check if guild for this skill already exists at location
        $existingGuild = Guild::where('primary_skill', $primarySkill)
            ->where('location_type', $player->current_location_type)
            ->where('location_id', $player->current_location_id)
            ->where('is_active', true)
            ->first();

        if ($existingGuild) {
            return ['success' => false, 'message' => "A {$primarySkill} guild already exists at this location."];
        }

        // Check player skill level
        $playerSkillLevel = $this->getPlayerSkillLevel($player, $primarySkill);
        if ($playerSkillLevel < 10) {
            return ['success' => false, 'message' => "You need at least level 10 in {$primarySkill} to found a guild."];
        }

        // Check gold
        if ($player->gold < Guild::FOUNDING_COST) {
            return ['success' => false, 'message' => 'You need ' . number_format(Guild::FOUNDING_COST) . ' gold to found a guild.'];
        }

        return DB::transaction(function () use ($player, $name, $description, $primarySkill) {
            // Deduct gold
            $player->decrement('gold', Guild::FOUNDING_COST);

            // Create the guild
            $guild = Guild::create([
                'name' => $name,
                'description' => $description,
                'primary_skill' => $primarySkill,
                'location_type' => $player->current_location_type,
                'location_id' => $player->current_location_id,
                'founder_id' => $player->id,
                'guildmaster_id' => $player->id,
                'founding_cost' => Guild::FOUNDING_COST,
                'membership_fee' => Guild::DEFAULT_MEMBERSHIP_FEE,
                'weekly_dues' => Guild::DEFAULT_WEEKLY_DUES,
            ]);

            // Add founder as guildmaster
            GuildMember::create([
                'user_id' => $player->id,
                'guild_id' => $guild->id,
                'rank' => GuildMember::RANK_GUILDMASTER,
                'contribution' => 0,
                'years_membership' => 0,
                'joined_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => "You have founded the {$name} guild.",
                'data' => ['guild' => $this->formatGuild($guild->fresh(['founder', 'guildmaster', 'benefits']))],
            ];
        });
    }

    /**
     * Join a guild.
     */
    public function joinGuild(User $player, int $guildId): array
    {
        $guild = Guild::find($guildId);
        if (!$guild) {
            return ['success' => false, 'message' => 'Guild not found.'];
        }

        // Check if already a member
        if (GuildMember::where('user_id', $player->id)->where('guild_id', $guildId)->exists()) {
            return ['success' => false, 'message' => 'You are already a member of this guild.'];
        }

        // Check if guild is accepting members
        if (!$guild->canAcceptMembers()) {
            return ['success' => false, 'message' => 'This guild is not accepting new members.'];
        }

        // Check location
        if ($player->current_location_type !== $guild->location_type ||
            $player->current_location_id !== $guild->location_id) {
            return ['success' => false, 'message' => 'You must be at the guild location to join.'];
        }

        // Check player skill level
        $playerSkillLevel = $this->getPlayerSkillLevel($player, $guild->primary_skill);
        if ($playerSkillLevel < 1) {
            return ['success' => false, 'message' => "You need at least level 1 in {$guild->primary_skill} to join this guild."];
        }

        // Check membership fee
        if ($player->gold < $guild->membership_fee) {
            return ['success' => false, 'message' => "You need " . number_format($guild->membership_fee) . " gold to pay the membership fee."];
        }

        return DB::transaction(function () use ($player, $guild) {
            // Deduct membership fee
            $player->decrement('gold', $guild->membership_fee);
            $guild->increment('treasury', $guild->membership_fee);

            GuildMember::create([
                'user_id' => $player->id,
                'guild_id' => $guild->id,
                'rank' => GuildMember::RANK_APPRENTICE,
                'contribution' => 0,
                'years_membership' => 0,
                'joined_at' => now(),
                'dues_paid_until' => now()->addWeek(),
            ]);

            return [
                'success' => true,
                'message' => "You have joined {$guild->name} as an Apprentice.",
            ];
        });
    }

    /**
     * Leave a guild.
     */
    public function leaveGuild(User $player, int $guildId): array
    {
        $membership = GuildMember::where('user_id', $player->id)
            ->where('guild_id', $guildId)
            ->first();

        if (!$membership) {
            return ['success' => false, 'message' => 'You are not a member of this guild.'];
        }

        // Guildmasters cannot leave (must resign or be replaced first)
        if ($membership->isGuildmaster()) {
            return ['success' => false, 'message' => 'As the guildmaster, you must appoint a successor before leaving.'];
        }

        return DB::transaction(function () use ($membership) {
            $guildName = $membership->guild->name;
            $membership->delete();

            return [
                'success' => true,
                'message' => "You have left {$guildName}.",
            ];
        });
    }

    /**
     * Make a donation to the guild treasury.
     */
    public function donate(User $player, int $guildId, int $amount): array
    {
        if ($amount < 10) {
            return ['success' => false, 'message' => 'Minimum donation is 10 gold.'];
        }

        $membership = GuildMember::where('user_id', $player->id)
            ->where('guild_id', $guildId)
            ->with('guild')
            ->first();

        if (!$membership) {
            return ['success' => false, 'message' => 'You are not a member of this guild.'];
        }

        if ($player->gold < $amount) {
            return ['success' => false, 'message' => 'You do not have enough gold.'];
        }

        return DB::transaction(function () use ($player, $membership, $amount) {
            // Transfer gold
            $player->decrement('gold', $amount);
            $membership->guild->increment('treasury', $amount);

            // Calculate contribution
            $contribution = GuildActivity::calculateDonationContribution($amount);

            // Add contribution to member
            $membership->addContribution($contribution);

            // Add contribution to guild and check for level up
            $leveledUp = $membership->guild->addContribution($contribution);

            // Log activity
            GuildActivity::create([
                'user_id' => $player->id,
                'guild_id' => $membership->guild_id,
                'activity_type' => GuildActivity::TYPE_DONATION,
                'contribution_gained' => $contribution,
                'gold_amount' => $amount,
            ]);

            $message = "You donated {$amount} gold and earned {$contribution} contribution points.";
            if ($leveledUp) {
                $message .= " The guild has leveled up to level {$membership->guild->fresh()->level}!";
            }

            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'contribution_gained' => $contribution,
                    'total_contribution' => $membership->fresh()->contribution,
                ],
            ];
        });
    }

    /**
     * Pay weekly dues.
     */
    public function payDues(User $player, int $guildId): array
    {
        $membership = GuildMember::where('user_id', $player->id)
            ->where('guild_id', $guildId)
            ->with('guild')
            ->first();

        if (!$membership) {
            return ['success' => false, 'message' => 'You are not a member of this guild.'];
        }

        $duesAmount = $membership->guild->weekly_dues;

        if ($player->gold < $duesAmount) {
            return ['success' => false, 'message' => "You need {$duesAmount} gold to pay your weekly dues."];
        }

        return DB::transaction(function () use ($player, $membership, $duesAmount) {
            // Transfer gold
            $player->decrement('gold', $duesAmount);
            $membership->guild->increment('treasury', $duesAmount);

            // Update dues paid until
            $newDueDate = $membership->dues_paid_until && $membership->dues_paid_until->isFuture()
                ? $membership->dues_paid_until->addWeek()
                : now()->addWeek();

            $membership->update([
                'dues_paid' => true,
                'dues_paid_until' => $newDueDate,
            ]);

            // Log activity
            GuildActivity::create([
                'user_id' => $player->id,
                'guild_id' => $membership->guild_id,
                'activity_type' => GuildActivity::TYPE_DUES,
                'gold_amount' => $duesAmount,
            ]);

            return [
                'success' => true,
                'message' => "You paid {$duesAmount} gold in weekly dues.",
            ];
        });
    }

    /**
     * Promote a member.
     */
    public function promoteMember(User $player, int $memberId): array
    {
        $targetMember = GuildMember::with(['guild', 'user'])->find($memberId);
        if (!$targetMember) {
            return ['success' => false, 'message' => 'Member not found.'];
        }

        // Check if player is the guildmaster
        $playerMembership = GuildMember::where('user_id', $player->id)
            ->where('guild_id', $targetMember->guild_id)
            ->first();

        if (!$playerMembership || !$playerMembership->isGuildmaster()) {
            return ['success' => false, 'message' => 'Only the guildmaster can promote members.'];
        }

        // Check if target can be promoted
        if (!$targetMember->canBePromoted()) {
            $requirements = $targetMember->promotion_requirements;
            if (empty($requirements)) {
                return ['success' => false, 'message' => 'This member cannot be promoted further.'];
            }

            $missing = [];
            if (!$requirements['years_met']) {
                $missing[] = "{$requirements['years_required']} years membership";
            }
            if (!$requirements['contribution_met']) {
                $missing[] = "{$requirements['contribution_required']} contribution points";
            }

            return ['success' => false, 'message' => 'This member needs: ' . implode(' and ', $missing)];
        }

        return DB::transaction(function () use ($targetMember) {
            $newRank = $targetMember->getNextRank();
            $targetMember->update([
                'rank' => $newRank,
                'promoted_at' => now(),
            ]);

            // Log activity
            GuildActivity::create([
                'user_id' => $targetMember->user_id,
                'guild_id' => $targetMember->guild_id,
                'activity_type' => GuildActivity::TYPE_PROMOTION,
                'metadata' => ['new_rank' => $newRank],
            ]);

            $rankDisplay = (new GuildMember(['rank' => $newRank]))->rank_display;
            return [
                'success' => true,
                'message' => "{$targetMember->user->username} has been promoted to {$rankDisplay}.",
            ];
        });
    }

    /**
     * Start a guildmaster election.
     */
    public function startElection(User $player, int $guildId): array
    {
        $guild = Guild::find($guildId);
        if (!$guild) {
            return ['success' => false, 'message' => 'Guild not found.'];
        }

        // Check if player is a master or guildmaster
        $membership = GuildMember::where('user_id', $player->id)
            ->where('guild_id', $guildId)
            ->first();

        if (!$membership || !$membership->hasVotingRights()) {
            return ['success' => false, 'message' => 'Only masters can call for an election.'];
        }

        // Check if there's already an active election
        if ($guild->hasActiveElection()) {
            return ['success' => false, 'message' => 'There is already an active election.'];
        }

        return DB::transaction(function () use ($guild) {
            $nominationEnds = now()->addDays(GuildElection::NOMINATION_PERIOD_DAYS);
            $votingEnds = $nominationEnds->copy()->addDays(GuildElection::VOTING_PERIOD_DAYS);

            GuildElection::create([
                'guild_id' => $guild->id,
                'status' => GuildElection::STATUS_NOMINATION,
                'nomination_ends_at' => $nominationEnds,
                'voting_ends_at' => $votingEnds,
            ]);

            return [
                'success' => true,
                'message' => 'A guildmaster election has been called. Nominations are open.',
            ];
        });
    }

    /**
     * Declare candidacy in an election.
     */
    public function declareCandidacy(User $player, int $electionId, string $platform = ''): array
    {
        $election = GuildElection::with('guild')->find($electionId);
        if (!$election) {
            return ['success' => false, 'message' => 'Election not found.'];
        }

        if (!$election->isInNominationPhase()) {
            return ['success' => false, 'message' => 'Nominations are no longer open.'];
        }

        // Check if player is a master
        $membership = GuildMember::where('user_id', $player->id)
            ->where('guild_id', $election->guild_id)
            ->first();

        if (!$membership || !$membership->hasVotingRights()) {
            return ['success' => false, 'message' => 'Only masters can run for guildmaster.'];
        }

        // Check if already a candidate
        if (GuildElectionCandidate::where('guild_election_id', $electionId)
            ->where('user_id', $player->id)->exists()) {
            return ['success' => false, 'message' => 'You are already a candidate.'];
        }

        return DB::transaction(function () use ($player, $electionId, $platform) {
            GuildElectionCandidate::create([
                'guild_election_id' => $electionId,
                'user_id' => $player->id,
                'platform' => $platform,
            ]);

            return [
                'success' => true,
                'message' => 'You have declared your candidacy for guildmaster.',
            ];
        });
    }

    /**
     * Vote in an election.
     */
    public function voteInElection(User $player, int $electionId, int $candidateId): array
    {
        $election = GuildElection::with('guild')->find($electionId);
        if (!$election) {
            return ['success' => false, 'message' => 'Election not found.'];
        }

        if (!$election->isInVotingPhase()) {
            return ['success' => false, 'message' => 'Voting is not currently open.'];
        }

        // Check if player has voting rights
        $membership = GuildMember::where('user_id', $player->id)
            ->where('guild_id', $election->guild_id)
            ->first();

        if (!$membership || !$membership->hasVotingRights()) {
            return ['success' => false, 'message' => 'Only masters can vote.'];
        }

        // Check if already voted
        if (GuildElectionVote::where('guild_election_id', $electionId)
            ->where('voter_id', $player->id)->exists()) {
            return ['success' => false, 'message' => 'You have already voted.'];
        }

        // Validate candidate
        $candidate = GuildElectionCandidate::where('id', $candidateId)
            ->where('guild_election_id', $electionId)
            ->first();

        if (!$candidate) {
            return ['success' => false, 'message' => 'Invalid candidate.'];
        }

        return DB::transaction(function () use ($player, $electionId, $candidate) {
            GuildElectionVote::create([
                'guild_election_id' => $electionId,
                'voter_id' => $player->id,
                'candidate_id' => $candidate->id,
            ]);

            $candidate->increment('votes');

            return [
                'success' => true,
                'message' => 'Your vote has been cast.',
            ];
        });
    }

    /**
     * Set guild membership fee (guildmaster only).
     */
    public function setMembershipFee(User $player, int $guildId, int $fee): array
    {
        if ($fee < 0 || $fee > 1000000) {
            return ['success' => false, 'message' => 'Fee must be between 0 and 1,000,000 gold.'];
        }

        $membership = GuildMember::where('user_id', $player->id)
            ->where('guild_id', $guildId)
            ->first();

        if (!$membership || !$membership->isGuildmaster()) {
            return ['success' => false, 'message' => 'Only the guildmaster can set the membership fee.'];
        }

        $membership->guild->update(['membership_fee' => $fee]);

        return [
            'success' => true,
            'message' => "Membership fee set to " . number_format($fee) . " gold.",
        ];
    }

    /**
     * Set guild weekly dues (guildmaster only).
     */
    public function setWeeklyDues(User $player, int $guildId, int $dues): array
    {
        if ($dues < 0 || $dues > 10000) {
            return ['success' => false, 'message' => 'Dues must be between 0 and 10,000 gold.'];
        }

        $membership = GuildMember::where('user_id', $player->id)
            ->where('guild_id', $guildId)
            ->first();

        if (!$membership || !$membership->isGuildmaster()) {
            return ['success' => false, 'message' => 'Only the guildmaster can set weekly dues.'];
        }

        $membership->guild->update(['weekly_dues' => $dues]);

        return [
            'success' => true,
            'message' => "Weekly dues set to " . number_format($dues) . " gold.",
        ];
    }

    /**
     * Toggle guild public status (guildmaster only).
     */
    public function setPublicStatus(User $player, int $guildId, bool $isPublic): array
    {
        $membership = GuildMember::where('user_id', $player->id)
            ->where('guild_id', $guildId)
            ->first();

        if (!$membership || !$membership->isGuildmaster()) {
            return ['success' => false, 'message' => 'Only the guildmaster can change guild visibility.'];
        }

        $membership->guild->update(['is_public' => $isPublic]);

        $status = $isPublic ? 'public' : 'private';
        return [
            'success' => true,
            'message' => "Guild is now {$status}.",
        ];
    }

    /**
     * Get guilds at a specific location.
     */
    public function getGuildsAtLocation(string $locationType, int $locationId): array
    {
        return Guild::where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('is_active', true)
            ->with(['founder', 'guildmaster', 'benefits'])
            ->get()
            ->map(fn ($g) => $this->formatGuild($g))
            ->toArray();
    }

    /**
     * Get all guild benefits.
     */
    public function getAllBenefits(): array
    {
        return GuildBenefit::all()
            ->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'description' => $b->description,
                'icon' => $b->icon,
                'skill_name' => $b->skill_name,
                'effects' => $b->effects,
                'required_guild_level' => $b->required_guild_level,
            ])
            ->toArray();
    }

    /**
     * Get player's skill level.
     */
    protected function getPlayerSkillLevel(User $player, string $skillName): int
    {
        $skill = PlayerSkill::where('player_id', $player->id)
            ->where('skill_name', $skillName)
            ->first();

        return $skill ? $skill->level : 0;
    }

    /**
     * Get player's kingdom ID.
     */
    protected function getPlayerKingdomId(User $player): ?int
    {
        return match ($player->current_location_type) {
            'village' => \App\Models\Village::find($player->current_location_id)?->barony?->kingdom_id,
            'barony' => Barony::find($player->current_location_id)?->kingdom_id,
            'town' => Town::find($player->current_location_id)?->barony?->kingdom_id,
            'kingdom' => $player->current_location_id,
            default => null,
        };
    }

    /**
     * Format a guild for API response.
     */
    protected function formatGuild(Guild $guild): array
    {
        return [
            'id' => $guild->id,
            'name' => $guild->name,
            'description' => $guild->description,
            'icon' => $guild->icon,
            'color' => $guild->color,
            'primary_skill' => $guild->primary_skill,
            'skill_display' => $guild->skill_display,
            'location_type' => $guild->location_type,
            'location_id' => $guild->location_id,
            'level' => $guild->level,
            'level_progress' => $guild->level_progress,
            'total_contribution' => $guild->total_contribution,
            'treasury' => $guild->treasury,
            'membership_fee' => $guild->membership_fee,
            'weekly_dues' => $guild->weekly_dues,
            'is_public' => $guild->is_public,
            'has_monopoly' => $guild->has_monopoly,
            'member_count' => $guild->member_count,
            'master_count' => $guild->master_count,
            'founder' => $guild->founder ? [
                'id' => $guild->founder->id,
                'username' => $guild->founder->username,
            ] : null,
            'guildmaster' => $guild->guildmaster ? [
                'id' => $guild->guildmaster->id,
                'username' => $guild->guildmaster->username,
            ] : null,
            'benefits' => $guild->benefits->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'description' => $b->description,
                'icon' => $b->icon,
                'effects' => $b->effects,
            ])->toArray(),
            'combined_effects' => $guild->getCombinedEffects(),
        ];
    }

    /**
     * Format a membership for API response.
     */
    protected function formatMembership(GuildMember $membership): array
    {
        return [
            'id' => $membership->id,
            'guild_id' => $membership->guild_id,
            'guild_name' => $membership->guild->name,
            'guild_icon' => $membership->guild->icon,
            'guild_color' => $membership->guild->color,
            'guild_skill' => $membership->guild->primary_skill,
            'rank' => $membership->rank,
            'rank_display' => $membership->rank_display,
            'contribution' => $membership->contribution,
            'years_membership' => $membership->years_membership,
            'joined_at' => $membership->joined_at->toIso8601String(),
            'dues_paid' => $membership->dues_paid,
            'dues_paid_until' => $membership->dues_paid_until?->toIso8601String(),
            'can_be_promoted' => $membership->canBePromoted(),
            'promotion_requirements' => $membership->promotion_requirements,
            'has_voting_rights' => $membership->hasVotingRights(),
            'is_guildmaster' => $membership->isGuildmaster(),
        ];
    }

    /**
     * Format an election for API response.
     */
    protected function formatElection(GuildElection $election): array
    {
        $election->load(['candidates.user', 'votes']);

        return [
            'id' => $election->id,
            'guild_id' => $election->guild_id,
            'status' => $election->status,
            'status_display' => $election->status_display,
            'nomination_ends_at' => $election->nomination_ends_at->toIso8601String(),
            'voting_ends_at' => $election->voting_ends_at->toIso8601String(),
            'is_nomination_phase' => $election->isInNominationPhase(),
            'is_voting_phase' => $election->isInVotingPhase(),
            'candidates' => $election->candidates->map(fn ($c) => [
                'id' => $c->id,
                'user_id' => $c->user_id,
                'username' => $c->user->username,
                'platform' => $c->platform,
                'votes' => $c->votes,
            ])->toArray(),
            'total_votes' => $election->votes->count(),
        ];
    }
}

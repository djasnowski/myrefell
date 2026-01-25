<?php

namespace App\Http\Controllers;

use App\Models\Guild;
use App\Services\GuildService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuildController extends Controller
{
    public function __construct(
        protected GuildService $guildService
    ) {}

    /**
     * Show the guilds index page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Check if player is traveling
        if ($user->isTraveling()) {
            return Inertia::render('Guilds/NotAvailable', [
                'message' => 'You cannot access guild services while traveling.',
            ]);
        }

        // Check if at a valid location
        if (!in_array($user->current_location_type, ['town', 'barony'])) {
            return Inertia::render('Guilds/NotAvailable', [
                'message' => 'Guilds can only be accessed in towns or baronies.',
            ]);
        }

        $availableGuilds = $this->guildService->getAvailableGuilds($user);
        $myGuilds = $this->guildService->getPlayerGuilds($user);
        $localGuilds = $this->guildService->getGuildsAtLocation(
            $user->current_location_type,
            $user->current_location_id
        );

        return Inertia::render('Guilds/Index', [
            'available_guilds' => $availableGuilds,
            'my_guilds' => $myGuilds,
            'local_guilds' => $localGuilds,
            'guild_skills' => Guild::GUILD_SKILLS,
            'founding_cost' => Guild::FOUNDING_COST,
            'gold' => $user->gold,
            'location' => [
                'type' => $user->current_location_type,
                'id' => $user->current_location_id,
            ],
        ]);
    }

    /**
     * Show a specific guild's details.
     */
    public function show(Request $request, Guild $guild): Response
    {
        $user = $request->user();

        $details = $this->guildService->getGuildDetails($guild, $user);

        return Inertia::render('Guilds/Show', [
            'guild' => $details['guild'],
            'membership' => $details['membership'],
            'is_member' => $details['is_member'],
            'can_join' => $details['can_join'],
            'player_skill_level' => $details['player_skill_level'],
            'members' => $details['members'],
            'active_election' => $details['active_election'],
            'price_controls' => $details['price_controls'],
            'gold' => $user->gold,
        ]);
    }

    /**
     * Create a new guild.
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|min:3|max:50',
            'description' => 'nullable|string|max:500',
            'primary_skill' => 'required|string|in:' . implode(',', Guild::GUILD_SKILLS),
        ]);

        $user = $request->user();
        $result = $this->guildService->createGuild(
            $user,
            $request->input('name'),
            $request->input('description', ''),
            $request->input('primary_skill')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Join a guild.
     */
    public function join(Request $request): JsonResponse
    {
        $request->validate([
            'guild_id' => 'required|integer|exists:guilds,id',
        ]);

        $user = $request->user();
        $result = $this->guildService->joinGuild($user, $request->input('guild_id'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Leave a guild.
     */
    public function leave(Request $request): JsonResponse
    {
        $request->validate([
            'guild_id' => 'required|integer|exists:guilds,id',
        ]);

        $user = $request->user();
        $result = $this->guildService->leaveGuild($user, $request->input('guild_id'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Donate to guild treasury.
     */
    public function donate(Request $request): JsonResponse
    {
        $request->validate([
            'guild_id' => 'required|integer|exists:guilds,id',
            'amount' => 'required|integer|min:10',
        ]);

        $user = $request->user();
        $result = $this->guildService->donate(
            $user,
            $request->input('guild_id'),
            $request->input('amount')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Pay weekly dues.
     */
    public function payDues(Request $request): JsonResponse
    {
        $request->validate([
            'guild_id' => 'required|integer|exists:guilds,id',
        ]);

        $user = $request->user();
        $result = $this->guildService->payDues($user, $request->input('guild_id'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Promote a member.
     */
    public function promote(Request $request): JsonResponse
    {
        $request->validate([
            'member_id' => 'required|integer|exists:guild_members,id',
        ]);

        $user = $request->user();
        $result = $this->guildService->promoteMember($user, $request->input('member_id'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Start an election.
     */
    public function startElection(Request $request): JsonResponse
    {
        $request->validate([
            'guild_id' => 'required|integer|exists:guilds,id',
        ]);

        $user = $request->user();
        $result = $this->guildService->startElection($user, $request->input('guild_id'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Declare candidacy.
     */
    public function declareCandidacy(Request $request): JsonResponse
    {
        $request->validate([
            'election_id' => 'required|integer|exists:guild_elections,id',
            'platform' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $result = $this->guildService->declareCandidacy(
            $user,
            $request->input('election_id'),
            $request->input('platform', '')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Vote in an election.
     */
    public function vote(Request $request): JsonResponse
    {
        $request->validate([
            'election_id' => 'required|integer|exists:guild_elections,id',
            'candidate_id' => 'required|integer|exists:guild_election_candidates,id',
        ]);

        $user = $request->user();
        $result = $this->guildService->voteInElection(
            $user,
            $request->input('election_id'),
            $request->input('candidate_id')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Set membership fee.
     */
    public function setMembershipFee(Request $request): JsonResponse
    {
        $request->validate([
            'guild_id' => 'required|integer|exists:guilds,id',
            'fee' => 'required|integer|min:0|max:1000000',
        ]);

        $user = $request->user();
        $result = $this->guildService->setMembershipFee(
            $user,
            $request->input('guild_id'),
            $request->input('fee')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Set weekly dues.
     */
    public function setWeeklyDues(Request $request): JsonResponse
    {
        $request->validate([
            'guild_id' => 'required|integer|exists:guilds,id',
            'dues' => 'required|integer|min:0|max:10000',
        ]);

        $user = $request->user();
        $result = $this->guildService->setWeeklyDues(
            $user,
            $request->input('guild_id'),
            $request->input('dues')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Set public status.
     */
    public function setPublicStatus(Request $request): JsonResponse
    {
        $request->validate([
            'guild_id' => 'required|integer|exists:guilds,id',
            'is_public' => 'required|boolean',
        ]);

        $user = $request->user();
        $result = $this->guildService->setPublicStatus(
            $user,
            $request->input('guild_id'),
            $request->input('is_public')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get guilds at a specific location.
     */
    public function locationGuilds(Request $request): JsonResponse
    {
        $user = $request->user();
        $guilds = $this->guildService->getGuildsAtLocation(
            $user->current_location_type,
            $user->current_location_id
        );

        return response()->json([
            'success' => true,
            'data' => ['guilds' => $guilds],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\Belief;
use App\Models\BlessingRequest;
use App\Models\BlessingType;
use App\Models\Duchy;
use App\Models\Kingdom;
use App\Models\KingdomReligion;
use App\Models\LocationActivityLog;
use App\Models\PlayerActiveBelief;
use App\Models\PlayerBlessing;
use App\Models\PlayerRole;
use App\Models\PlayerSkill;
use App\Models\Religion;
use App\Models\ReligionMember;
use App\Models\Role;
use App\Models\Town;
use App\Models\User;
use App\Models\Village;
use App\Services\BlessingEffectService;
use App\Services\ReligionHeadquartersService;
use App\Services\ReligionInviteService;
use App\Services\ReligionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlessingController extends Controller
{
    public function __construct(
        protected ReligionHeadquartersService $hqService,
        protected ReligionInviteService $inviteService,
        protected ReligionService $religionService,
        protected BlessingEffectService $blessingEffectService
    ) {}

    /**
     * Show the blessing shrine/church page (location-scoped).
     */
    public function index(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null): Response
    {
        $user = $request->user();
        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);

        // Get user's active blessings
        $activeBlessings = PlayerBlessing::where('user_id', $user->id)
            ->active()
            ->with(['blessingType', 'grantedBy'])
            ->get()
            ->map(fn ($blessing) => $this->formatBlessing($blessing));

        // Check if user is a priest at current location
        $isPriest = $this->isPriestAtLocation($user);
        $priestData = null;

        if ($isPriest) {
            // Get available blessings based on priest's prayer level
            $prayerSkill = PlayerSkill::where('player_id', $user->id)
                ->where('skill_name', 'prayer')
                ->first();
            $prayerLevel = $prayerSkill?->level ?? 1;

            $availableBlessings = BlessingType::active()
                ->where('prayer_level_required', '<=', $prayerLevel)
                ->orderBy('category')
                ->orderBy('prayer_level_required')
                ->get()
                ->map(fn ($type) => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'slug' => $type->slug,
                    'icon' => $type->icon,
                    'description' => $type->description,
                    'category' => $type->category,
                    'effects' => $type->effects,
                    'duration' => $type->formatted_duration,
                    'duration_minutes' => $type->duration_minutes,
                    'gold_cost' => $type->gold_cost,
                    'energy_cost' => $type->energy_cost,
                    'prayer_level_required' => $type->prayer_level_required,
                ]);

            // Get nearby players who can be blessed (at same location), including self
            $nearbyPlayers = User::where('current_location_type', $user->current_location_type)
                ->where('current_location_id', $user->current_location_id)
                ->select('id', 'username as name')
                ->orderByRaw('id = ? DESC', [$user->id]) // Put self first
                ->limit(50)
                ->get();

            // Get pending blessing requests at this location
            $pendingRequests = BlessingRequest::where('status', 'pending')
                ->where('location_type', $user->current_location_type)
                ->where('location_id', $user->current_location_id)
                ->with(['user:id,username', 'blessingType'])
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn ($req) => [
                    'id' => $req->id,
                    'user_id' => $req->user_id,
                    'username' => $req->user->username,
                    'blessing_type_id' => $req->blessing_type_id,
                    'blessing_name' => $req->blessingType->name,
                    'blessing_icon' => $req->blessingType->icon,
                    'message' => $req->message,
                    'created_at' => $req->created_at->diffForHumans(),
                ]);

            $priestData = [
                'prayer_level' => $prayerLevel,
                'prayer_xp' => $prayerSkill?->xp ?? 0,
                'prayer_xp_to_next' => $prayerSkill?->xpToNextLevel() ?? 83,
                'available_blessings' => $availableBlessings,
                'nearby_players' => $nearbyPlayers,
                'blessings_given_today' => $this->getBlessingsGivenToday($user),
                'pending_requests' => $pendingRequests,
            ];
        }

        // Get recent blessings at this location (public)
        $recentBlessings = PlayerBlessing::where('location_type', $user->current_location_type)
            ->where('location_id', $user->current_location_id)
            ->whereNotNull('granted_by')
            ->with(['user:id,username', 'grantedBy:id,username', 'blessingType'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($blessing) => [
                'id' => $blessing->id,
                'recipient' => $blessing->user->username,
                'granted_by' => $blessing->grantedBy->username,
                'blessing_name' => $blessing->blessingType->name,
                'blessing_icon' => $blessing->blessingType->icon,
                'time_ago' => $blessing->created_at->diffForHumans(),
            ]);

        // Get user's prayer level for shrine
        $userPrayerSkill = PlayerSkill::where('player_id', $user->id)
            ->where('skill_name', 'prayer')
            ->first();
        $userPrayerLevel = $userPrayerSkill?->level ?? 1;

        // Get shrine blessings for non-priest self-prayer (with 50% gold penalty, 25% duration reduction)
        // Sorted by prayer level required (lowest to highest)
        $shrineBlessings = BlessingType::active()
            ->orderBy('prayer_level_required')
            ->get()
            ->map(fn ($type) => [
                'id' => $type->id,
                'name' => $type->name,
                'slug' => $type->slug,
                'icon' => $type->icon,
                'description' => $type->description,
                'category' => $type->category,
                'effects' => $type->effects,
                'duration' => $this->formatDuration((int) ($type->duration_minutes * 0.75)),
                'duration_minutes' => (int) ($type->duration_minutes * 0.75),
                'gold_cost' => (int) ($type->gold_cost * 1.5),
                'energy_cost' => $type->energy_cost,
                'prayer_level_required' => $type->prayer_level_required,
            ]);

        // Get religions with HQ at this location
        $localReligions = $this->getLocalReligions($user, $locationType, $location?->id);

        // Get player's pending religion invites
        $pendingInvites = $this->inviteService->getPendingInvitesForUser($user);

        // Get player's current religion membership
        $playerMembership = $this->religionService->getPlayerReligions($user);
        $currentMembership = ! empty($playerMembership) ? $playerMembership[0] : null;

        // Get all beliefs for cult creation (non-cult beliefs only)
        $beliefs = $this->religionService->getAllBeliefs();

        // Get cult-only beliefs with unlock status based on player's cult membership
        $cultBeliefs = [];
        if ($currentMembership && $currentMembership['is_cult']) {
            $cultReligion = Religion::find($currentMembership['religion_id']);
            if ($cultReligion && $cultReligion->hasHideout()) {
                $cultBeliefs = Belief::cultOnly()
                    ->orderBy('required_hideout_tier')
                    ->get()
                    ->map(fn ($b) => [
                        'id' => $b->id,
                        'name' => $b->name,
                        'description' => $b->description,
                        'icon' => $b->icon,
                        'type' => $b->type,
                        'effects' => $b->effects,
                        'required_hideout_tier' => $b->required_hideout_tier,
                        'tier_name' => Religion::HIDEOUT_TIERS[$b->required_hideout_tier]['name'] ?? 'Unknown',
                        'hp_cost' => $b->getHpCost(),
                        'energy_cost' => $b->getEnergyCost(),
                        'is_unlocked' => $b->required_hideout_tier <= $cultReligion->hideout_tier,
                    ]);
            }
        }

        // Get player's active beliefs
        $activeBeliefs = PlayerActiveBelief::where('user_id', $user->id)
            ->active()
            ->with(['belief', 'religion'])
            ->get()
            ->map(fn ($ab) => [
                'id' => $ab->id,
                'belief_id' => $ab->belief_id,
                'belief_name' => $ab->belief->name,
                'belief_icon' => $ab->belief->icon,
                'belief_effects' => $ab->belief->effects,
                'religion_id' => $ab->religion_id,
                'religion_name' => $ab->religion->name,
                'devotion_spent' => $ab->devotion_spent,
                'expires_at' => $ab->expires_at->toIso8601String(),
                'remaining_seconds' => $ab->getRemainingSeconds(),
            ]);

        $data = [
            'active_blessings' => $activeBlessings,
            'active_beliefs' => $activeBeliefs,
            'is_priest' => $isPriest,
            'priest_data' => $priestData,
            'shrine_blessings' => $shrineBlessings,
            'prayer_level' => $userPrayerLevel,
            'recent_blessings' => $recentBlessings,
            'energy' => $user->energy,
            'gold' => $user->gold,
            'current_user_id' => $user->id,
            'location' => [
                'type' => $user->current_location_type,
                'id' => $user->current_location_id,
            ],
            'local_religions' => $localReligions,
            'pending_invites' => $pendingInvites,
            'current_membership' => $currentMembership,
            'beliefs' => $beliefs,
            'belief_activation' => [
                'min_devotion' => PlayerActiveBelief::MIN_DEVOTION,
                'max_devotion' => PlayerActiveBelief::MAX_DEVOTION,
                'min_duration_minutes' => PlayerActiveBelief::MIN_DURATION_MINUTES,
                'max_duration_minutes' => PlayerActiveBelief::MAX_DURATION_MINUTES,
            ],
            'cult_beliefs' => $cultBeliefs,
            'player_hp' => $user->hp,
            'player_max_hp' => $user->max_hp,
            'nearby_players_for_invite' => ($currentMembership && ($currentMembership['is_prophet'] || $currentMembership['is_officer']))
                ? User::where('current_location_type', $user->current_location_type)
                    ->where('current_location_id', $user->current_location_id)
                    ->where('id', '!=', $user->id)
                    ->whereNotIn('id', ReligionMember::where('religion_id', $currentMembership['religion_id'])->pluck('user_id'))
                    ->orderBy('username')
                    ->limit(50)
                    ->get()
                    ->map(fn ($u) => [
                        'id' => $u->id,
                        'username' => $u->username,
                        'combat_level' => $u->combat_level,
                    ])
                : [],
            'pending_outgoing_invites' => ($currentMembership && ($currentMembership['is_prophet'] || $currentMembership['is_officer']))
                ? $this->inviteService->getPendingInvitesForReligion($currentMembership['religion_id'])
                : [],
        ];

        // Add location context with activity feed if location-scoped
        if ($location && $locationType) {
            $data['location'] = [
                'type' => $locationType,
                'id' => $location->id,
                'name' => $location->name,
            ];

            // Get recent blessing activity at this location
            try {
                $data['recent_activity'] = LocationActivityLog::atLocation($locationType, $location->id)
                    ->ofType(LocationActivityLog::TYPE_BLESSING)
                    ->recent(10)
                    ->with('user:id,username')
                    ->get()
                    ->map(fn ($log) => [
                        'id' => $log->id,
                        'username' => $log->user->username ?? 'Unknown',
                        'description' => $log->description,
                        'subtype' => $log->activity_subtype,
                        'metadata' => $log->metadata,
                        'created_at' => $log->created_at->toIso8601String(),
                        'time_ago' => $log->created_at->diffForHumans(),
                    ]);
            } catch (\Illuminate\Database\QueryException $e) {
                $data['recent_activity'] = [];
            }
        }

        return Inertia::render('Shrine/Index', $data);
    }

    /**
     * Priest grants a blessing to a player.
     */
    public function bless(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null): RedirectResponse
    {
        $user = $request->user();

        // Verify user is a priest at this location
        if (! $this->isPriestAtLocation($user)) {
            return back()->withErrors(['error' => 'You must be a Priest to give blessings.']);
        }

        $validated = $request->validate([
            'blessing_type_id' => 'required|exists:blessing_types,id',
            'target_user_id' => 'required|exists:users,id',
        ]);

        $blessingType = BlessingType::findOrFail($validated['blessing_type_id']);
        $targetUser = User::findOrFail($validated['target_user_id']);

        // Check prayer level requirement
        $prayerSkill = PlayerSkill::where('player_id', $user->id)
            ->where('skill_name', 'prayer')
            ->first();
        $prayerLevel = $prayerSkill?->level ?? 1;

        if ($blessingType->prayer_level_required > $prayerLevel) {
            return back()->withErrors([
                'error' => "You need prayer level {$blessingType->prayer_level_required} to give this blessing.",
            ]);
        }

        // Check target is at same location
        if ($targetUser->current_location_type !== $user->current_location_type ||
            $targetUser->current_location_id !== $user->current_location_id) {
            return back()->withErrors(['error' => 'That player is not at this location.']);
        }

        // Check priest has enough energy
        if ($user->energy < $blessingType->energy_cost) {
            return back()->withErrors([
                'error' => "You need {$blessingType->energy_cost} energy to give this blessing.",
            ]);
        }

        // Calculate gold cost with HQ modifier
        $costModifier = $this->hqService->getBlessingCostModifier($targetUser);
        $actualGoldCost = (int) ceil($blessingType->gold_cost * $costModifier);

        // Check target has enough gold for donation
        if ($targetUser->gold < $actualGoldCost) {
            return back()->withErrors([
                'error' => "{$targetUser->username} cannot afford the {$actualGoldCost}g donation.",
            ]);
        }

        // Check if target already has this blessing active
        $existingBlessing = PlayerBlessing::where('user_id', $targetUser->id)
            ->where('blessing_type_id', $blessingType->id)
            ->active()
            ->first();

        if ($existingBlessing) {
            return back()->withErrors([
                'error' => "{$targetUser->username} already has {$blessingType->name} active.",
            ]);
        }

        // Check if target has room for another blessing (slot limit)
        if (! $this->blessingEffectService->canReceiveBlessing($targetUser)) {
            $maxSlots = $this->blessingEffectService->getMaxBlessingSlots($targetUser);

            return back()->withErrors([
                'error' => "{$targetUser->username} already has {$maxSlots} active blessings (maximum).",
            ]);
        }

        // Check cooldown (if priest recently gave this specific blessing to this player)
        if ($blessingType->cooldown_minutes > 0) {
            $recentBlessing = PlayerBlessing::where('user_id', $targetUser->id)
                ->where('blessing_type_id', $blessingType->id)
                ->where('granted_by', $user->id)
                ->where('created_at', '>', now()->subMinutes($blessingType->cooldown_minutes))
                ->first();

            if ($recentBlessing) {
                $waitTime = $blessingType->cooldown_minutes - now()->diffInMinutes($recentBlessing->created_at);

                return back()->withErrors([
                    'error' => "You must wait {$waitTime} more minutes before giving this blessing again.",
                ]);
            }
        }

        // Deduct energy from priest
        $user->decrement('energy', $blessingType->energy_cost);

        // Deduct gold from target (donation) with HQ modifier
        if ($actualGoldCost > 0) {
            $targetUser->decrement('gold', $actualGoldCost);
            // Give portion to priest as offering
            $priestShare = (int) floor($actualGoldCost * 0.5);
            $user->increment('gold', $priestShare);
        }

        // Calculate duration with HQ modifier (from target's HQ tier)
        $durationModifier = $this->hqService->getBlessingDurationModifier($targetUser);

        // Apply priest's prophet blessing duration bonus (from their prayer buff)
        $prophetDurationBonus = $this->blessingEffectService->getEffect($user, 'prophet_blessing_duration');
        if ($prophetDurationBonus > 0) {
            $durationModifier *= (1 + $prophetDurationBonus / 100);
        }

        $actualDuration = (int) ceil($blessingType->duration_minutes * $durationModifier);

        // Create the blessing
        $blessing = PlayerBlessing::create([
            'user_id' => $targetUser->id,
            'blessing_type_id' => $blessingType->id,
            'granted_by' => $user->id,
            'location_type' => $user->current_location_type,
            'location_id' => $user->current_location_id,
            'expires_at' => now()->addMinutes($actualDuration),
        ]);

        // Award prayer XP to priest
        $xpGained = 10 + ($blessingType->prayer_level_required * 2);
        if ($prayerSkill) {
            $prayerSkill->addXp($xpGained);
        }

        $isSelf = $targetUser->id === $user->id;
        $message = $isSelf
            ? "You blessed yourself with {$blessingType->name}! (+{$xpGained} Prayer XP)"
            : "You blessed {$targetUser->username} with {$blessingType->name}! (+{$xpGained} Prayer XP)";

        return back()->with('success', $message);
    }

    /**
     * Player requests a blessing (self-service at shrine when no priest).
     */
    public function pray(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'blessing_type_id' => 'required|exists:blessing_types,id',
        ]);

        $blessingType = BlessingType::findOrFail($validated['blessing_type_id']);

        // Self-service blessings cost more and are weaker (no priest bonus)
        // Apply HQ modifiers on top of self-service penalties
        $hqCostModifier = $this->hqService->getBlessingCostModifier($user);
        $hqDurationModifier = $this->hqService->getBlessingDurationModifier($user);

        $goldCost = (int) ceil($blessingType->gold_cost * 1.5 * $hqCostModifier);
        $duration = (int) floor($blessingType->duration_minutes * 0.75 * $hqDurationModifier); // 75% duration without priest + HQ bonus

        // Check gold
        if ($user->gold < $goldCost) {
            return back()->withErrors([
                'error' => "You need {$goldCost}g to pray for this blessing.",
            ]);
        }

        // Check if already has this blessing
        $existingBlessing = PlayerBlessing::where('user_id', $user->id)
            ->where('blessing_type_id', $blessingType->id)
            ->active()
            ->first();

        if ($existingBlessing) {
            return back()->withErrors([
                'error' => "You already have {$blessingType->name} active.",
            ]);
        }

        // Check if player has room for another blessing (slot limit)
        if (! $this->blessingEffectService->canReceiveBlessing($user)) {
            $maxSlots = $this->blessingEffectService->getMaxBlessingSlots($user);

            return back()->withErrors([
                'error' => "You already have {$maxSlots} active blessings (maximum).",
            ]);
        }

        // Deduct gold
        $user->decrement('gold', $goldCost);

        // Create blessing (no granted_by since it's self-service)
        $blessing = PlayerBlessing::create([
            'user_id' => $user->id,
            'blessing_type_id' => $blessingType->id,
            'granted_by' => null,
            'location_type' => $user->current_location_type,
            'location_id' => $user->current_location_id,
            'expires_at' => now()->addMinutes($duration),
        ]);

        // Award small prayer XP
        $prayerSkill = PlayerSkill::where('player_id', $user->id)
            ->where('skill_name', 'prayer')
            ->first();

        $xpGained = 5;
        if ($prayerSkill) {
            $prayerSkill->addXp($xpGained);
        }

        $message = "Your prayers have been answered! {$blessingType->name} granted. (+{$xpGained} Prayer XP)";

        return back()->with('success', $message);
    }

    /**
     * Approve a blessing request.
     */
    public function approveRequest(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null, ?BlessingRequest $blessingRequest = null): RedirectResponse
    {
        if (! $blessingRequest) {
            return back()->withErrors(['error' => 'Blessing request not found.']);
        }
        $user = $request->user();

        // Verify user is a priest at this location
        if (! $this->isPriestAtLocation($user)) {
            return back()->withErrors(['error' => 'You must be a Priest to approve blessings.']);
        }

        // Verify request is at priest's location
        if ($blessingRequest->location_type !== $user->current_location_type ||
            $blessingRequest->location_id !== $user->current_location_id) {
            return back()->withErrors(['error' => 'This request is not at your location.']);
        }

        if (! $blessingRequest->isPending()) {
            return back()->withErrors(['error' => 'This request has already been handled.']);
        }

        // Check priest has enough energy
        $blessingType = $blessingRequest->blessingType;
        if ($user->energy < $blessingType->energy_cost) {
            return back()->withErrors([
                'error' => "You need {$blessingType->energy_cost} energy to give this blessing.",
            ]);
        }

        // Deduct energy from priest
        $user->decrement('energy', $blessingType->energy_cost);

        // Approve and create the blessing
        $blessing = $blessingRequest->approve($user);

        // Award prayer XP to priest
        $prayerSkill = PlayerSkill::where('player_id', $user->id)
            ->where('skill_name', 'prayer')
            ->first();
        $xpGained = 10 + ($blessingType->prayer_level_required * 2);
        if ($prayerSkill) {
            $prayerSkill->addXp($xpGained);
        }

        return back()->with('success', "Blessed {$blessingRequest->user->username} with {$blessingType->name}! (+{$xpGained} Prayer XP)");
    }

    /**
     * Deny a blessing request.
     */
    public function denyRequest(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null, ?BlessingRequest $blessingRequest = null): RedirectResponse
    {
        if (! $blessingRequest) {
            return back()->withErrors(['error' => 'Blessing request not found.']);
        }
        $user = $request->user();

        // Verify user is a priest at this location
        if (! $this->isPriestAtLocation($user)) {
            return back()->withErrors(['error' => 'You must be a Priest to deny blessings.']);
        }

        // Verify request is at priest's location
        if ($blessingRequest->location_type !== $user->current_location_type ||
            $blessingRequest->location_id !== $user->current_location_id) {
            return back()->withErrors(['error' => 'This request is not at your location.']);
        }

        if (! $blessingRequest->isPending()) {
            return back()->withErrors(['error' => 'This request has already been handled.']);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $blessingRequest->deny($user, $validated['reason'] ?? null);

        return back()->with('success', "Denied blessing request from {$blessingRequest->user->username}.");
    }

    /**
     * Get player's active blessings (for API/sidebar).
     */
    public function getActiveBlessings(Request $request): JsonResponse
    {
        $user = $request->user();

        $activeBlessings = PlayerBlessing::where('user_id', $user->id)
            ->active()
            ->with('blessingType')
            ->get()
            ->map(fn ($blessing) => $this->formatBlessing($blessing));

        return response()->json([
            'blessings' => $activeBlessings,
        ]);
    }

    /**
     * Check if user is a priest at their current location.
     */
    protected function isPriestAtLocation(User $user): bool
    {
        if (! $user->current_location_type || ! $user->current_location_id) {
            return false;
        }

        try {
            $priestRole = Role::where('slug', 'priest')->first();
            if (! $priestRole) {
                return false;
            }

            return PlayerRole::where('user_id', $user->id)
                ->where('role_id', $priestRole->id)
                ->where('location_type', $user->current_location_type)
                ->where('location_id', $user->current_location_id)
                ->active()
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get count of blessings given today by priest.
     */
    protected function getBlessingsGivenToday(User $user): int
    {
        return PlayerBlessing::where('granted_by', $user->id)
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * Format a blessing for the frontend.
     */
    protected function formatBlessing(PlayerBlessing $blessing): array
    {
        return [
            'id' => $blessing->id,
            'name' => $blessing->blessingType->name,
            'slug' => $blessing->blessingType->slug,
            'icon' => $blessing->blessingType->icon,
            'description' => $blessing->blessingType->description,
            'category' => $blessing->blessingType->category,
            'effects' => $blessing->blessingType->effects,
            'expires_at' => $blessing->expires_at->toIso8601String(),
            'time_remaining' => $blessing->time_remaining,
            'minutes_remaining' => $blessing->minutes_remaining,
            'granted_by' => $blessing->grantedBy?->username ?? 'Divine Prayer',
            'is_active' => $blessing->isActive(),
        ];
    }

    /**
     * Determine location type from model.
     */
    protected function getLocationType($location): ?string
    {
        return match (true) {
            $location instanceof Village => 'village',
            $location instanceof Town => 'town',
            $location instanceof Barony => 'barony',
            $location instanceof Duchy => 'duchy',
            $location instanceof Kingdom => 'kingdom',
            default => null,
        };
    }

    /**
     * Format duration in minutes to human readable string.
     */
    protected function formatDuration(int $minutes): string
    {
        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;

            return $mins > 0 ? "{$hours}h {$mins}m" : "{$hours}h";
        }

        return "{$minutes}m";
    }

    /**
     * Get religions with HQ at this location.
     */
    protected function getLocalReligions(User $user, ?string $locationType, ?int $locationId): array
    {
        if (! $locationType || ! $locationId) {
            return [];
        }

        // Get player's kingdom for ban checking
        $playerKingdomId = $this->getPlayerKingdomId($user);

        // Get religions with HQ at this location
        $religions = Religion::query()
            ->where('is_active', true)
            ->whereHas('headquarters', function ($q) use ($locationType, $locationId) {
                $q->where('location_type', $locationType)
                    ->where('location_id', $locationId)
                    ->whereNotNull('location_type'); // Only built HQs
            })
            ->with(['headquarters', 'beliefs', 'founder'])
            ->withCount('members')
            ->get();

        // Check if player is already a member of any religion
        $playerMembership = ReligionMember::where('user_id', $user->id)->first();

        return $religions->map(function ($religion) use ($playerKingdomId, $playerMembership) {
            // Check kingdom ban status
            $kingdomStatus = null;
            $isBanned = false;
            if ($playerKingdomId) {
                $status = KingdomReligion::where('kingdom_id', $playerKingdomId)
                    ->where('religion_id', $religion->id)
                    ->first();
                $kingdomStatus = $status?->status;
                $isBanned = $status && $status->isBanned();
            }

            // Check if player is a member of this religion
            $isMember = $playerMembership && $playerMembership->religion_id === $religion->id;

            // Can join: public, not banned, not already a member of any religion, can accept members
            $canJoin = $religion->is_public
                && ! $isBanned
                && ! $playerMembership
                && $religion->canAcceptMembers();

            return [
                'id' => $religion->id,
                'name' => $religion->name,
                'description' => $religion->description,
                'icon' => $religion->icon,
                'color' => $religion->color,
                'type' => $religion->type,
                'is_cult' => $religion->isCult(),
                'is_public' => $religion->is_public,
                'member_count' => $religion->members_count,
                'founder' => $religion->founder ? [
                    'id' => $religion->founder->id,
                    'username' => $religion->founder->username,
                ] : null,
                'beliefs' => $religion->beliefs->map(fn ($b) => [
                    'id' => $b->id,
                    'name' => $b->name,
                    'description' => $b->description,
                    'icon' => $b->icon,
                ])->toArray(),
                'hq_tier' => $religion->headquarters?->tier ?? 1,
                'kingdom_status' => $kingdomStatus,
                'is_banned' => $isBanned,
                'is_member' => $isMember,
                'can_join' => $canJoin,
            ];
        })->toArray();
    }

    /**
     * Get player's kingdom ID.
     */
    protected function getPlayerKingdomId(User $user): ?int
    {
        return match ($user->current_location_type) {
            'village' => Village::find($user->current_location_id)?->barony?->kingdom_id,
            'barony' => Barony::find($user->current_location_id)?->kingdom_id,
            'town' => Town::find($user->current_location_id)?->barony?->kingdom_id,
            'kingdom' => $user->current_location_id,
            default => null,
        };
    }

    /**
     * Activate a religion's beliefs for temporary buffs.
     */
    public function activateBeliefs(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'religion_id' => 'required|integer|exists:religions,id',
            'devotion' => 'required|integer|min:'.PlayerActiveBelief::MIN_DEVOTION.'|max:'.PlayerActiveBelief::MAX_DEVOTION,
        ]);

        $religion = Religion::with('beliefs')->findOrFail($validated['religion_id']);
        $devotionToSpend = $validated['devotion'];

        // Check user is a member of this religion
        $membership = ReligionMember::where('user_id', $user->id)
            ->where('religion_id', $religion->id)
            ->first();

        if (! $membership) {
            return back()->withErrors(['error' => 'You must be a member of this religion to activate its beliefs.']);
        }

        // Check user has enough devotion
        if ($membership->devotion < $devotionToSpend) {
            return back()->withErrors(['error' => 'You do not have enough devotion.']);
        }

        // Check religion has beliefs
        if ($religion->beliefs->isEmpty()) {
            return back()->withErrors(['error' => 'This religion has no beliefs to activate.']);
        }

        // Check if any of these beliefs are already active for the user
        $activeBeliefIds = PlayerActiveBelief::where('user_id', $user->id)
            ->active()
            ->pluck('belief_id')
            ->toArray();

        $religionBeliefIds = $religion->beliefs->pluck('id')->toArray();
        $alreadyActive = array_intersect($activeBeliefIds, $religionBeliefIds);

        if (! empty($alreadyActive)) {
            return back()->withErrors(['error' => 'You already have beliefs from this religion active.']);
        }

        // Calculate duration
        $durationMinutes = PlayerActiveBelief::calculateDurationMinutes($devotionToSpend);
        $expiresAt = now()->addMinutes($durationMinutes);

        // Deduct devotion from membership
        $membership->decrement('devotion', $devotionToSpend);

        // Create active belief records for all religion's beliefs
        foreach ($religion->beliefs as $belief) {
            PlayerActiveBelief::create([
                'user_id' => $user->id,
                'belief_id' => $belief->id,
                'religion_id' => $religion->id,
                'devotion_spent' => $devotionToSpend,
                'expires_at' => $expiresAt,
            ]);
        }

        $beliefNames = $religion->beliefs->pluck('name')->join(', ');

        return back()->with('success', "Activated beliefs ({$beliefNames}) for {$durationMinutes} minutes.");
    }

    /**
     * Activate a cult's forbidden beliefs (with HP cost).
     */
    public function activateCultBeliefs(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'religion_id' => 'required|integer|exists:religions,id',
            'belief_id' => 'required|integer|exists:beliefs,id',
            'devotion' => 'required|integer|min:'.PlayerActiveBelief::MIN_DEVOTION.'|max:'.PlayerActiveBelief::MAX_DEVOTION,
        ]);

        $religion = Religion::findOrFail($validated['religion_id']);
        $belief = Belief::findOrFail($validated['belief_id']);
        $devotionToSpend = $validated['devotion'];

        // Verify this is a cult
        if (! $religion->isCult()) {
            return back()->withErrors(['error' => 'This is not a cult.']);
        }

        // Verify this is a cult-only belief
        if (! $belief->isCultOnly()) {
            return back()->withErrors(['error' => 'This is not a Forbidden Art.']);
        }

        // Check user is a member of this cult
        $membership = ReligionMember::where('user_id', $user->id)
            ->where('religion_id', $religion->id)
            ->first();

        if (! $membership) {
            return back()->withErrors(['error' => 'You must be a member of this cult to activate its Forbidden Arts.']);
        }

        // Check hideout tier requirement
        if (! $religion->hasHideout()) {
            return back()->withErrors(['error' => 'Your cult must have a hideout to use Forbidden Arts.']);
        }

        if ($religion->hideout_tier < $belief->required_hideout_tier) {
            $requiredTierName = Religion::HIDEOUT_TIERS[$belief->required_hideout_tier]['name'] ?? 'Unknown';

            return back()->withErrors(['error' => "This Forbidden Art requires a {$requiredTierName} hideout."]);
        }

        // Check user has enough devotion
        if ($membership->devotion < $devotionToSpend) {
            return back()->withErrors(['error' => 'You do not have enough devotion.']);
        }

        // Check HP cost (can't go below 1 HP)
        $hpCost = $belief->getHpCost();
        if ($user->hp < $hpCost + 1) {
            return back()->withErrors(['error' => 'You need at least '.($hpCost + 1).' HP for the blood sacrifice.']);
        }

        // Check energy cost
        $energyCost = $belief->getEnergyCost();
        if ($user->energy < $energyCost) {
            return back()->withErrors(['error' => "You need {$energyCost} energy to perform this ritual."]);
        }

        // Check if this belief is already active
        $existingBelief = PlayerActiveBelief::where('user_id', $user->id)
            ->where('belief_id', $belief->id)
            ->active()
            ->first();

        if ($existingBelief) {
            return back()->withErrors(['error' => 'This Forbidden Art is already active.']);
        }

        // Calculate duration
        $durationMinutes = PlayerActiveBelief::calculateDurationMinutes($devotionToSpend);
        $expiresAt = now()->addMinutes($durationMinutes);

        // Deduct resources
        $membership->decrement('devotion', $devotionToSpend);
        $user->decrement('hp', $hpCost);
        $user->decrement('energy', $energyCost);

        // Create active belief record
        PlayerActiveBelief::create([
            'user_id' => $user->id,
            'belief_id' => $belief->id,
            'religion_id' => $religion->id,
            'devotion_spent' => $devotionToSpend,
            'expires_at' => $expiresAt,
        ]);

        return back()->with('success', "The blood sacrifice is complete. {$belief->name} activated for {$durationMinutes} minutes. (-{$hpCost} HP, -{$energyCost} energy)");
    }
}

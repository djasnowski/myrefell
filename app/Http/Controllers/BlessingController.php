<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\BlessingType;
use App\Models\Duchy;
use App\Models\Kingdom;
use App\Models\LocationActivityLog;
use App\Models\PlayerBlessing;
use App\Models\PlayerRole;
use App\Models\PlayerSkill;
use App\Models\Role;
use App\Models\Town;
use App\Models\User;
use App\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlessingController extends Controller
{
    /**
     * Show the blessing shrine/church page (location-scoped).
     */
    public function index(Request $request, Village|Town|Barony|Duchy|Kingdom $village = null, Town $town = null, Barony $barony = null, Duchy $duchy = null, Kingdom $kingdom = null): Response
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

            $priestData = [
                'prayer_level' => $prayerLevel,
                'prayer_xp' => $prayerSkill?->xp ?? 0,
                'prayer_xp_to_next' => $prayerSkill?->xpToNextLevel() ?? 83,
                'available_blessings' => $availableBlessings,
                'nearby_players' => $nearbyPlayers,
                'blessings_given_today' => $this->getBlessingsGivenToday($user),
            ];
        }

        $data = [
            'active_blessings' => $activeBlessings,
            'is_priest' => $isPriest,
            'priest_data' => $priestData,
            'energy' => $user->energy,
            'gold' => $user->gold,
            'current_user_id' => $user->id,
            'location' => [
                'type' => $user->current_location_type,
                'id' => $user->current_location_id,
            ],
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
    public function bless(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Verify user is a priest at this location
        if (!$this->isPriestAtLocation($user)) {
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

        // Check target has enough gold for donation
        if ($targetUser->gold < $blessingType->gold_cost) {
            return back()->withErrors([
                'error' => "{$targetUser->username} cannot afford the {$blessingType->gold_cost}g donation.",
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

        // Deduct gold from target (donation)
        if ($blessingType->gold_cost > 0) {
            $targetUser->decrement('gold', $blessingType->gold_cost);
            // Give portion to priest as offering
            $priestShare = (int) floor($blessingType->gold_cost * 0.5);
            $user->increment('gold', $priestShare);
        }

        // Create the blessing
        $blessing = PlayerBlessing::create([
            'user_id' => $targetUser->id,
            'blessing_type_id' => $blessingType->id,
            'granted_by' => $user->id,
            'location_type' => $user->current_location_type,
            'location_id' => $user->current_location_id,
            'expires_at' => now()->addMinutes($blessingType->duration_minutes),
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
    public function pray(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'blessing_type_id' => 'required|exists:blessing_types,id',
        ]);

        $blessingType = BlessingType::findOrFail($validated['blessing_type_id']);

        // Self-service blessings cost more and are weaker (no priest bonus)
        $goldCost = (int) ceil($blessingType->gold_cost * 1.5);
        $duration = (int) floor($blessingType->duration_minutes * 0.75); // 75% duration without priest

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
        if (!$user->current_location_type || !$user->current_location_id) {
            return false;
        }

        try {
            $priestRole = Role::where('slug', 'priest')->first();
            if (!$priestRole) {
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
}

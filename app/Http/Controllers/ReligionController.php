<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Kingdom;
use App\Models\PlayerInventory;
use App\Models\Religion;
use App\Models\Town;
use App\Models\Village;
use App\Services\ReligionInviteService;
use App\Services\ReligionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReligionController extends Controller
{
    public function __construct(
        protected ReligionService $religionService,
        protected ReligionInviteService $inviteService
    ) {}

    /**
     * Redirect religions index to dashboard.
     * Religion discovery now happens at shrines.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('dashboard')->with('info', 'Visit a shrine to discover and join religions.');
    }

    /**
     * Show a specific religion's details (global route - redirects to location-scoped).
     */
    public function show(Request $request, Religion $religion): RedirectResponse
    {
        $user = $request->user();

        // Redirect to location-scoped route
        $locationType = $user->current_location_type;
        $locationId = $user->current_location_id;

        // Handle irregular pluralization
        $locationPlural = match ($locationType) {
            'barony' => 'baronies',
            'duchy' => 'duchies',
            default => $locationType.'s',
        };

        return redirect()->route("{$locationPlural}.religions.show", [
            $locationType => $locationId,
            'religion' => $religion->id,
        ]);
    }

    /**
     * Show a specific religion's details (location-scoped).
     */
    public function showAtLocation(Request $request): Response
    {
        $user = $request->user();

        // Get religion from route parameter
        $religionParam = $request->route('religion');
        $religion = $religionParam instanceof Religion
            ? $religionParam
            : Religion::findOrFail($religionParam);

        // Determine location from route parameters
        $location = null;
        $locationType = null;

        if ($village = $request->route('village')) {
            $location = $village instanceof Village ? $village : Village::findOrFail($village);
            $locationType = 'village';
        } elseif ($town = $request->route('town')) {
            $location = $town instanceof Town ? $town : Town::findOrFail($town);
            $locationType = 'town';
        } elseif ($barony = $request->route('barony')) {
            $location = $barony instanceof Barony ? $barony : Barony::findOrFail($barony);
            $locationType = 'barony';
        } elseif ($duchy = $request->route('duchy')) {
            $location = $duchy instanceof Duchy ? $duchy : Duchy::findOrFail($duchy);
            $locationType = 'duchy';
        } elseif ($kingdom = $request->route('kingdom')) {
            $location = $kingdom instanceof Kingdom ? $kingdom : Kingdom::findOrFail($kingdom);
            $locationType = 'kingdom';
        }

        $details = $this->religionService->getReligionDetails($religion, $user);

        // Get bones from player inventory for sacrifice
        $sacrificeBones = PlayerInventory::where('player_id', $user->id)
            ->whereHas('item', fn ($q) => $q->where('subtype', 'remains'))
            ->with('item:id,name,prayer_bonus')
            ->get()
            ->map(fn ($inv) => [
                'item_id' => $inv->item_id,
                'name' => $inv->item->name,
                'quantity' => $inv->quantity,
                'prayer_xp' => $inv->item->prayer_bonus,
            ]);

        return Inertia::render('Religions/Show', [
            'religion' => $details['religion'],
            'membership' => $details['membership'],
            'is_member' => $details['is_member'],
            'can_join' => $details['can_join'],
            'kingdom_status' => $details['kingdom_status'],
            'members' => $details['members'],
            'structures' => $details['structures'],
            'history' => $details['history'],
            'sacrifice_bones' => $sacrificeBones,
            'energy' => [
                'current' => $user->energy,
            ],
            'gold' => $user->gold,
            'location' => [
                'type' => $locationType,
                'id' => $location->id,
                'name' => $location->name,
            ],
        ]);
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
     * Create a new cult.
     */
    public function createCult(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|min:3|max:50',
            'description' => 'nullable|string|max:500',
            'belief_ids' => 'required|array|min:1|max:2',
            'belief_ids.*' => 'integer|exists:beliefs,id',
        ]);

        $user = $request->user();
        $result = $this->religionService->createCult(
            $user,
            $request->input('name'),
            $request->input('description', ''),
            $request->input('belief_ids')
        );

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Join a religion.
     */
    public function join(Request $request): JsonResponse
    {
        $request->validate([
            'religion_id' => 'required|integer|exists:religions,id',
        ]);

        $user = $request->user();
        $result = $this->religionService->joinReligion($user, $request->input('religion_id'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Leave a religion.
     */
    public function leave(Request $request): JsonResponse
    {
        $request->validate([
            'religion_id' => 'required|integer|exists:religions,id',
        ]);

        $user = $request->user();
        $result = $this->religionService->leaveReligion($user, $request->input('religion_id'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Perform a religious action.
     */
    public function performAction(Request $request): JsonResponse
    {
        $request->validate([
            'religion_id' => 'required|integer|exists:religions,id',
            'action_type' => 'required|string|in:prayer,donation,ritual,sacrifice,pilgrimage',
            'structure_id' => 'nullable|integer|exists:religious_structures,id',
            'donation_amount' => 'nullable|integer|min:10',
            'sacrifice_item_id' => 'nullable|integer|exists:items,id',
        ]);

        $user = $request->user();
        $result = $this->religionService->performAction(
            $user,
            $request->input('religion_id'),
            $request->input('action_type'),
            $request->input('structure_id'),
            $request->input('donation_amount', 0),
            $request->input('sacrifice_item_id')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Promote a member to the next rank.
     */
    public function promote(Request $request): JsonResponse
    {
        $request->validate([
            'member_id' => 'required|integer|exists:religion_members,id',
        ]);

        $user = $request->user();
        $result = $this->religionService->promoteMember($user, $request->input('member_id'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Demote a member one rank.
     */
    public function demote(Request $request): JsonResponse
    {
        $request->validate([
            'member_id' => 'required|integer|exists:religion_members,id',
        ]);

        $user = $request->user();
        $result = $this->religionService->demoteMember($user, $request->input('member_id'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Convert cult to full religion.
     */
    public function convertToReligion(Request $request): JsonResponse
    {
        $request->validate([
            'religion_id' => 'required|integer|exists:religions,id',
        ]);

        $user = $request->user();
        $result = $this->religionService->convertToReligion($user, $request->input('religion_id'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Build a religious structure.
     */
    public function buildStructure(Request $request): JsonResponse
    {
        $request->validate([
            'religion_id' => 'required|integer|exists:religions,id',
            'structure_type' => 'required|string|in:shrine,temple,cathedral',
            'location_type' => 'required|string|in:village,barony,kingdom',
            'location_id' => 'required|integer',
        ]);

        $user = $request->user();
        $result = $this->religionService->buildStructure(
            $user,
            $request->input('religion_id'),
            $request->input('structure_type'),
            $request->input('location_type'),
            $request->input('location_id')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Make a religion public.
     */
    public function makePublic(Request $request): JsonResponse
    {
        $request->validate([
            'religion_id' => 'required|integer|exists:religions,id',
        ]);

        $user = $request->user();
        $result = $this->religionService->makePublic($user, $request->input('religion_id'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Set kingdom religion status.
     */
    public function setKingdomStatus(Request $request): JsonResponse
    {
        $request->validate([
            'kingdom_id' => 'required|integer|exists:kingdoms,id',
            'religion_id' => 'required|integer|exists:religions,id',
            'status' => 'required|string|in:state,tolerated,banned',
        ]);

        $user = $request->user();
        $result = $this->religionService->setKingdomReligionStatus(
            $user,
            $request->input('kingdom_id'),
            $request->input('religion_id'),
            $request->input('status')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get structures at current location.
     */
    public function structures(Request $request): JsonResponse
    {
        $user = $request->user();
        $structures = $this->religionService->getStructuresAtLocation(
            $user->current_location_type,
            $user->current_location_id
        );

        return response()->json([
            'success' => true,
            'data' => ['structures' => $structures],
        ]);
    }

    /**
     * Dissolve a religion (prophet only).
     */
    public function dissolve(Request $request): JsonResponse
    {
        $request->validate([
            'religion_id' => 'required|integer|exists:religions,id',
            'successor_user_id' => 'nullable|integer|exists:users,id',
        ]);

        $user = $request->user();
        $result = $this->religionService->dissolveReligion(
            $user,
            $request->input('religion_id'),
            $request->input('successor_user_id')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get potential successors for a religion.
     */
    public function successors(Request $request): JsonResponse
    {
        $request->validate([
            'religion_id' => 'required|integer|exists:religions,id',
        ]);

        $user = $request->user();
        $successors = $this->religionService->getPotentialSuccessors(
            $user,
            $request->input('religion_id')
        );

        return response()->json([
            'success' => true,
            'data' => ['successors' => $successors],
        ]);
    }

    /**
     * Send an invite to join a religion.
     */
    public function invite(Request $request): RedirectResponse
    {
        $request->validate([
            'religion_id' => 'required|integer|exists:religions,id',
            'user_id' => 'required|integer|exists:users,id',
            'message' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $result = $this->inviteService->sendInvite(
            $user,
            $request->input('religion_id'),
            $request->input('user_id'),
            $request->input('message')
        );

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Accept a religion invite.
     */
    public function acceptInvite(Request $request): RedirectResponse
    {
        $request->validate([
            'invite_id' => 'required|integer|exists:religion_invites,id',
        ]);

        $user = $request->user();
        $result = $this->inviteService->acceptInvite($user, $request->input('invite_id'));

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Decline a religion invite.
     */
    public function declineInvite(Request $request): RedirectResponse
    {
        $request->validate([
            'invite_id' => 'required|integer|exists:religion_invites,id',
            'message' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $result = $this->inviteService->declineInvite(
            $user,
            $request->input('invite_id'),
            $request->input('message')
        );

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Cancel a religion invite.
     */
    public function cancelInvite(Request $request): RedirectResponse
    {
        $request->validate([
            'invite_id' => 'required|integer|exists:religion_invites,id',
        ]);

        $user = $request->user();
        $result = $this->inviteService->cancelInvite($user, $request->input('invite_id'));

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Get pending invites for the current user.
     */
    public function pendingInvites(Request $request): JsonResponse
    {
        $user = $request->user();
        $invites = $this->inviteService->getPendingInvitesForUser($user);

        return response()->json([
            'success' => true,
            'data' => ['invites' => $invites],
        ]);
    }
}

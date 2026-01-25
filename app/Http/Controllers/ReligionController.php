<?php

namespace App\Http\Controllers;

use App\Models\Religion;
use App\Models\ReligiousAction;
use App\Services\ReligionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReligionController extends Controller
{
    public function __construct(
        protected ReligionService $religionService
    ) {}

    /**
     * Show the religions index page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Check if player is traveling
        if ($user->isTraveling()) {
            return Inertia::render('Religions/NotAvailable', [
                'message' => 'You cannot access religious services while traveling.',
            ]);
        }

        $availableReligions = $this->religionService->getAvailableReligions($user);
        $myReligions = $this->religionService->getPlayerReligions($user);
        $beliefs = $this->religionService->getAllBeliefs();
        $structures = $this->religionService->getStructuresAtLocation(
            $user->current_location_type,
            $user->current_location_id
        );

        return Inertia::render('Religions/Index', [
            'available_religions' => $availableReligions,
            'my_religions' => $myReligions,
            'beliefs' => $beliefs,
            'structures' => $structures,
            'energy' => [
                'current' => $user->energy,
            ],
            'gold' => $user->gold,
            'action_costs' => [
                'prayer' => ReligiousAction::getEnergyCost(ReligiousAction::ACTION_PRAYER),
                'ritual' => ReligiousAction::getEnergyCost(ReligiousAction::ACTION_RITUAL),
                'sacrifice' => ReligiousAction::getEnergyCost(ReligiousAction::ACTION_SACRIFICE),
                'pilgrimage' => ReligiousAction::getEnergyCost(ReligiousAction::ACTION_PILGRIMAGE),
            ],
        ]);
    }

    /**
     * Show a specific religion's details.
     */
    public function show(Request $request, Religion $religion): Response
    {
        $user = $request->user();

        $details = $this->religionService->getReligionDetails($religion, $user);

        return Inertia::render('Religions/Show', [
            'religion' => $details['religion'],
            'membership' => $details['membership'],
            'is_member' => $details['is_member'],
            'can_join' => $details['can_join'],
            'kingdom_status' => $details['kingdom_status'],
            'members' => $details['members'],
            'structures' => $details['structures'],
            'energy' => [
                'current' => $user->energy,
            ],
            'gold' => $user->gold,
        ]);
    }

    /**
     * Create a new cult.
     */
    public function createCult(Request $request): JsonResponse
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

        return response()->json($result, $result['success'] ? 200 : 422);
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
        ]);

        $user = $request->user();
        $result = $this->religionService->performAction(
            $user,
            $request->input('religion_id'),
            $request->input('action_type'),
            $request->input('structure_id'),
            $request->input('donation_amount', 0)
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Promote a member to priest.
     */
    public function promote(Request $request): JsonResponse
    {
        $request->validate([
            'member_id' => 'required|integer|exists:religion_members,id',
        ]);

        $user = $request->user();
        $result = $this->religionService->promoteToPriest($user, $request->input('member_id'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Demote a priest to follower.
     */
    public function demote(Request $request): JsonResponse
    {
        $request->validate([
            'member_id' => 'required|integer|exists:religion_members,id',
        ]);

        $user = $request->user();
        $result = $this->religionService->demoteToFollower($user, $request->input('member_id'));

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
}

<?php

namespace App\Http\Controllers;

use App\Models\Charter;
use App\Models\Kingdom;
use App\Models\SettlementRuin;
use App\Services\CharterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CharterController extends Controller
{
    public function __construct(
        protected CharterService $charterService
    ) {}

    /**
     * Display a listing of charters.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get user's own charters
        $myCharters = $this->charterService->getUserCharters($user);

        // Get kingdoms for the dropdown
        $kingdoms = Kingdom::orderBy('name')->get()->map(fn ($k) => [
            'id' => $k->id,
            'name' => $k->name,
            'biome' => $k->biome,
        ]);

        // Get charter costs
        $costs = [
            'village' => Charter::DEFAULT_COST,
            'town' => Charter::TOWN_COST,
            'barony' => Charter::BARONY_COST,
        ];

        $signatoryRequirements = [
            'village' => Charter::DEFAULT_SIGNATORIES_REQUIRED,
            'town' => Charter::TOWN_SIGNATORIES_REQUIRED,
            'barony' => Charter::BARONY_SIGNATORIES_REQUIRED,
        ];

        return Inertia::render('charters/index', [
            'myCharters' => $myCharters,
            'kingdoms' => $kingdoms,
            'costs' => $costs,
            'signatoryRequirements' => $signatoryRequirements,
            'userGold' => $user->gold,
        ]);
    }

    /**
     * Display charters for a specific kingdom.
     */
    public function kingdomCharters(Kingdom $kingdom): Response
    {
        $charters = $this->charterService->getKingdomCharters($kingdom);
        $ruins = $this->charterService->getKingdomRuins($kingdom);

        return Inertia::render('charters/kingdom', [
            'kingdom' => [
                'id' => $kingdom->id,
                'name' => $kingdom->name,
                'biome' => $kingdom->biome,
                'king' => $kingdom->king ? [
                    'id' => $kingdom->king->id,
                    'username' => $kingdom->king->username,
                ] : null,
            ],
            'charters' => $charters,
            'ruins' => $ruins,
        ]);
    }

    /**
     * Display a specific charter.
     */
    public function show(Charter $charter): Response
    {
        $charterData = $this->charterService->getCharterDetails($charter);

        return Inertia::render('charters/show', [
            'charter' => $charterData,
        ]);
    }

    /**
     * Create a new charter request.
     */
    public function store(Request $request): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Charter founding is temporarily disabled.'], 403);

        $validated = $request->validate([
            'settlement_name' => 'required|string|max:100',
            'settlement_type' => 'required|in:village,town,barony',
            'kingdom_id' => 'required|exists:kingdoms,id',
            'description' => 'nullable|string|max:500',
            'tax_terms' => 'nullable|array',
            'coordinates_x' => 'nullable|integer|min:0|max:1000',
            'coordinates_y' => 'nullable|integer|min:0|max:1000',
            'biome' => 'nullable|string|max:50',
        ]);

        $user = $request->user();
        $kingdom = Kingdom::findOrFail($validated['kingdom_id']);

        $result = $this->charterService->createCharter(
            founder: $user,
            kingdom: $kingdom,
            settlementName: $validated['settlement_name'],
            settlementType: $validated['settlement_type'],
            description: $validated['description'] ?? null,
            taxTerms: $validated['tax_terms'] ?? null,
            coordinatesX: $validated['coordinates_x'] ?? null,
            coordinatesY: $validated['coordinates_y'] ?? null,
            biome: $validated['biome'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Sign a charter as a supporter.
     */
    public function sign(Request $request, Charter $charter): JsonResponse
    {
        $validated = $request->validate([
            'comment' => 'nullable|string|max:255',
        ]);

        $result = $this->charterService->signCharter(
            user: $request->user(),
            charter: $charter,
            comment: $validated['comment'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Approve a charter (King only).
     */
    public function approve(Request $request, Charter $charter): JsonResponse
    {
        $result = $this->charterService->approveCharter(
            approver: $request->user(),
            charter: $charter
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Reject a charter (King only).
     */
    public function reject(Request $request, Charter $charter): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $result = $this->charterService->rejectCharter(
            rejector: $request->user(),
            charter: $charter,
            reason: $validated['reason']
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Found the settlement from an approved charter.
     */
    public function found(Request $request, Charter $charter): JsonResponse
    {
        $validated = $request->validate([
            'coordinates_x' => 'nullable|integer|min:0|max:1000',
            'coordinates_y' => 'nullable|integer|min:0|max:1000',
        ]);

        $result = $this->charterService->foundSettlement(
            founder: $request->user(),
            charter: $charter,
            coordinatesX: $validated['coordinates_x'] ?? null,
            coordinatesY: $validated['coordinates_y'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Cancel a charter request.
     */
    public function cancel(Request $request, Charter $charter): JsonResponse
    {
        $result = $this->charterService->cancelCharter(
            user: $request->user(),
            charter: $charter
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Reclaim a ruined settlement.
     */
    public function reclaim(Request $request, SettlementRuin $ruin): JsonResponse
    {
        $result = $this->charterService->reclaimRuin(
            founder: $request->user(),
            ruin: $ruin
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get charter status (for polling).
     */
    public function status(Charter $charter): JsonResponse
    {
        $charterData = $this->charterService->getCharterDetails($charter);

        return response()->json([
            'charter' => $charterData,
        ]);
    }
}

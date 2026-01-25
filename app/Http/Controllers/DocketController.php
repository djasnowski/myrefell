<?php

namespace App\Http\Controllers;

use App\Models\CraftingOrder;
use App\Services\DocketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocketController extends Controller
{
    public function __construct(
        protected DocketService $docketService
    ) {}

    /**
     * Show the docket page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if (! $this->docketService->canAccessDocket($user)) {
            return Inertia::render('Crafting/DocketNotAvailable', [
                'message' => 'You cannot access the crafting docket at your current location.',
            ]);
        }

        $info = $this->docketService->getDocketInfo($user);

        return Inertia::render('Crafting/Docket', [
            'docket_info' => $info,
        ]);
    }

    /**
     * Place an NPC instant crafting order.
     */
    public function npcOrder(Request $request): JsonResponse
    {
        $request->validate([
            'recipe' => 'required|string',
            'quantity' => 'integer|min:1|max:10',
        ]);

        $user = $request->user();
        $result = $this->docketService->placeNpcOrder(
            $user,
            $request->input('recipe'),
            $request->input('quantity', 1)
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Place a player crafting order (goes to docket).
     */
    public function placeOrder(Request $request): JsonResponse
    {
        $request->validate([
            'recipe' => 'required|string',
            'quantity' => 'integer|min:1|max:10',
        ]);

        $user = $request->user();
        $result = $this->docketService->placePlayerOrder(
            $user,
            $request->input('recipe'),
            $request->input('quantity', 1)
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Accept a crafting order (as a crafter).
     */
    public function acceptOrder(Request $request, CraftingOrder $order): JsonResponse
    {
        $user = $request->user();
        $result = $this->docketService->acceptOrder($user, $order);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Complete a crafting order (as a crafter).
     */
    public function completeOrder(Request $request, CraftingOrder $order): JsonResponse
    {
        $user = $request->user();
        $result = $this->docketService->completeOrder($user, $order);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Cancel a crafting order (as the customer).
     */
    public function cancelOrder(Request $request, CraftingOrder $order): JsonResponse
    {
        $user = $request->user();
        $result = $this->docketService->cancelOrder($user, $order);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Abandon an accepted order (as the crafter).
     */
    public function abandonOrder(Request $request, CraftingOrder $order): JsonResponse
    {
        $user = $request->user();
        $result = $this->docketService->abandonOrder($user, $order);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get docket status (for polling).
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->docketService->canAccessDocket($user)) {
            return response()->json(['can_access' => false], 200);
        }

        $info = $this->docketService->getDocketInfo($user);

        return response()->json($info);
    }
}

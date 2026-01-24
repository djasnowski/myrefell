<?php

namespace App\Http\Controllers;

use App\Services\HealerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HealerController extends Controller
{
    public function __construct(
        protected HealerService $healerService
    ) {}

    /**
     * Show the healer page for a village.
     */
    public function villageHealer(Request $request, int $villageId): Response
    {
        return $this->showHealer($request, 'village', $villageId);
    }

    /**
     * Show the infirmary page for a castle.
     */
    public function castleInfirmary(Request $request, int $castleId): Response
    {
        return $this->showHealer($request, 'castle', $castleId);
    }

    /**
     * Show the infirmary page for a town.
     */
    public function townInfirmary(Request $request, int $townId): Response
    {
        return $this->showHealer($request, 'town', $townId);
    }

    /**
     * Show the healer page.
     */
    protected function showHealer(Request $request, string $locationType, int $locationId): Response
    {
        $user = $request->user();

        // Check if player is at this location
        if ($user->current_location_type !== $locationType || $user->current_location_id !== $locationId) {
            return Inertia::render('Healer/NotHere', [
                'message' => 'You must be at this location to visit the healer.',
            ]);
        }

        if (! $this->healerService->canAccessHealer($user)) {
            return Inertia::render('Healer/NotHere', [
                'message' => 'You cannot access a healer while traveling.',
            ]);
        }

        $healerInfo = $this->healerService->getHealerInfo($user);

        return Inertia::render('Healer/Index', [
            'healer_info' => $healerInfo,
        ]);
    }

    /**
     * Heal using a predefined option.
     */
    public function heal(Request $request): JsonResponse
    {
        $request->validate([
            'option' => 'required|string|in:heal_25,heal_50,heal_full',
        ]);

        $user = $request->user();
        $result = $this->healerService->healByOption($user, $request->input('option'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Heal a custom amount.
     */
    public function healAmount(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $result = $this->healerService->heal($user, $request->input('amount'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}

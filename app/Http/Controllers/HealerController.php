<?php

namespace App\Http\Controllers;

use App\Services\HealerService;
use App\Services\InfirmaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HealerController extends Controller
{
    public function __construct(
        protected HealerService $healerService,
        protected InfirmaryService $infirmaryService
    ) {}

    /**
     * Show the healer page for a village.
     */
    public function villageHealer(Request $request, int $villageId): Response|RedirectResponse
    {
        return $this->showHealer($request, 'village', $villageId);
    }

    /**
     * Show the infirmary page for a barony.
     */
    public function baronyInfirmary(Request $request, int $baronyId): Response|RedirectResponse
    {
        return $this->showHealer($request, 'barony', $baronyId);
    }

    /**
     * Show the infirmary page for a town.
     */
    public function townInfirmary(Request $request, int $townId): Response|RedirectResponse
    {
        return $this->showHealer($request, 'town', $townId);
    }

    /**
     * Show the infirmary page for a kingdom.
     */
    public function kingdomInfirmary(Request $request, int $kingdomId): Response|RedirectResponse
    {
        return $this->showHealer($request, 'kingdom', $kingdomId);
    }

    /**
     * Show the healer page.
     */
    protected function showHealer(Request $request, string $locationType, int $locationId): Response|RedirectResponse
    {
        $user = $request->user();

        // Check if player is at this location (or within its hierarchy for infirmary access)
        $isAtLocation = $user->current_location_type === $locationType && $user->current_location_id === $locationId;

        // If player is in infirmary, allow access to kingdom infirmary if they're within that kingdom
        if (! $isAtLocation && $user->isInInfirmary() && $locationType === 'kingdom') {
            $isAtLocation = $this->isPlayerInKingdom($user, $locationId);
        }

        if (! $isAtLocation) {
            return Inertia::render('Healer/NotHere', [
                'message' => 'You must be at this location to visit the healer.',
            ]);
        }

        // If in infirmary, show the infirmary recovery view
        if ($user->isInInfirmary()) {
            $infirmaryStatus = $this->infirmaryService->getInfirmaryStatus($user);

            // Resolve location name for the infirmary view
            $locationModel = match ($locationType) {
                'village' => \App\Models\Village::find($locationId),
                'barony' => \App\Models\Barony::find($locationId),
                'town' => \App\Models\Town::find($locationId),
                'kingdom' => \App\Models\Kingdom::find($locationId),
                default => null,
            };

            return Inertia::render('Healer/Index', [
                'healer_info' => [
                    'location_type' => $locationType,
                    'location_id' => $locationId,
                    'location_name' => $locationModel?->name ?? 'Unknown',
                    'hp' => $user->hp,
                    'max_hp' => $user->max_hp,
                ],
                'disease_info' => null,
                'infirmary' => $infirmaryStatus,
            ]);
        }

        // Locations without a healer (e.g. kingdoms) only have an infirmary page
        if (! in_array($locationType, ['village', 'barony', 'town'])) {
            $locationPath = match ($locationType) {
                'kingdom' => "/kingdoms/{$locationId}",
                default => '/dashboard',
            };

            return redirect($locationPath);
        }

        if (! $this->healerService->canAccessHealer($user)) {
            return Inertia::render('Healer/NotHere', [
                'message' => 'You cannot access a healer while traveling.',
            ]);
        }

        $healerInfo = $this->healerService->getHealerInfo($user);
        $diseaseInfo = $this->healerService->getDiseaseInfo($user);

        return Inertia::render('Healer/Index', [
            'healer_info' => $healerInfo,
            'disease_info' => $diseaseInfo,
            'infirmary' => null,
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

    /**
     * Treat a disease infection.
     */
    public function treatDisease(Request $request): JsonResponse
    {
        $user = $request->user();
        $infection = $this->healerService->getActiveInfection($user);

        if (! $infection) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have an active disease infection.',
            ], 422);
        }

        $result = $this->healerService->treatDisease($user, $infection);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Check if a player is within a kingdom (at a village/town/barony in that kingdom).
     */
    protected function isPlayerInKingdom($user, int $kingdomId): bool
    {
        $locationType = $user->current_location_type;
        $locationId = $user->current_location_id;

        return match ($locationType) {
            'kingdom' => $locationId === $kingdomId,
            'barony' => \App\Models\Barony::where('id', $locationId)
                ->where('kingdom_id', $kingdomId)->exists(),
            'town' => \App\Models\Town::whereHas('barony', fn ($q) => $q->where('kingdom_id', $kingdomId))
                ->where('id', $locationId)->exists(),
            'village' => \App\Models\Village::whereHas('barony', fn ($q) => $q->where('kingdom_id', $kingdomId))
                ->where('id', $locationId)->exists(),
            default => false,
        };
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\GatheringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GatheringController extends Controller
{
    public function __construct(
        protected GatheringService $gatheringService
    ) {}

    /**
     * Show the gathering hub.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $activities = $this->gatheringService->getAvailableActivities($user);

        if (empty($activities)) {
            return Inertia::render('Gathering/NotAvailable', [
                'message' => 'There are no gathering activities available at your current location.',
            ]);
        }

        $seasonalData = $this->gatheringService->getSeasonalData();

        return Inertia::render('Gathering/Index', [
            'activities' => $activities,
            'player_energy' => $user->energy,
            'max_energy' => $user->max_energy,
            'seasonal' => $seasonalData,
        ]);
    }

    /**
     * Show a specific gathering activity.
     */
    public function show(Request $request, string $activity): Response
    {
        $user = $request->user();
        $info = $this->gatheringService->getActivityInfo($user, $activity);

        if (! $info) {
            return Inertia::render('Gathering/NotAvailable', [
                'message' => 'Invalid gathering activity.',
            ]);
        }

        if (! $this->gatheringService->canGather($user, $activity)) {
            return Inertia::render('Gathering/NotAvailable', [
                'message' => 'You cannot do this activity at your current location.',
            ]);
        }

        return Inertia::render('Gathering/Activity', [
            'activity' => $info,
            'player_energy' => $user->energy,
            'max_energy' => $user->max_energy,
        ]);
    }

    /**
     * Perform a gathering action.
     */
    public function gather(Request $request): JsonResponse
    {
        $request->validate([
            'activity' => 'required|string|in:mining,fishing,woodcutting',
        ]);

        $user = $request->user();
        $result = $this->gatheringService->gather($user, $request->input('activity'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}

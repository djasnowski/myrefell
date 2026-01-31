<?php

namespace App\Http\Controllers;

use App\Models\LocationActivityLog;
use App\Models\Village;
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
    public function index(Request $request, Village $village): Response
    {
        $user = $request->user();
        $activities = $this->gatheringService->getAvailableActivities($user);

        if (empty($activities)) {
            return Inertia::render('Gathering/NotAvailable', [
                'message' => 'There are no gathering activities available at your current location.',
            ]);
        }

        $seasonalData = $this->gatheringService->getSeasonalData();

        $data = [
            'activities' => $activities,
            'player_energy' => $user->energy,
            'max_energy' => $user->max_energy,
            'seasonal' => $seasonalData,
            'location' => [
                'type' => 'village',
                'id' => $village->id,
                'name' => $village->name,
            ],
        ];

        // Get recent gathering activity at this location
        try {
            $data['recent_activity'] = LocationActivityLog::atLocation('village', $village->id)
                ->ofType(LocationActivityLog::TYPE_GATHERING)
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

        return Inertia::render('Gathering/Index', $data);
    }

    /**
     * Show a specific gathering activity.
     */
    public function show(Request $request, Village $village, string $activity): Response
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
            'location' => [
                'type' => 'village',
                'id' => $village->id,
                'name' => $village->name,
            ],
        ]);
    }

    /**
     * Perform a gathering action.
     */
    public function gather(Request $request, Village $village): JsonResponse
    {
        $request->validate([
            'activity' => 'required|string|in:mining,fishing,woodcutting',
        ]);

        $user = $request->user();
        $result = $this->gatheringService->gather(
            $user,
            $request->input('activity'),
            'village',
            $village->id
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}

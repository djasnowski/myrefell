<?php

namespace App\Http\Controllers;

use App\Config\LocationServices;
use App\Models\LocationActivityLog;
use App\Models\Village;
use App\Services\GatheringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GatheringController extends Controller
{
    public function __construct(
        protected GatheringService $gatheringService
    ) {}

    /**
     * Legacy index - redirects to location-scoped route.
     */
    public function legacyIndex(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Redirect to location-scoped route if possible (gathering only in villages/wilderness)
        if ($user->current_location_type === 'village' && $user->current_location_id) {
            return redirect()->route('villages.gathering', ['village' => $user->current_location_id]);
        }

        // Fall back to original behavior
        return $this->renderIndex($request->user(), null, null);
    }

    /**
     * Show the gathering hub (location-scoped).
     */
    public function index(Request $request, Village $village): Response
    {
        return $this->renderIndex($request->user(), $village, 'village');
    }

    /**
     * Render the gathering index page.
     */
    protected function renderIndex($user, $location, ?string $locationType): Response
    {
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
        ];

        // Add location context if available
        if ($location && $locationType) {
            $data['location'] = [
                'type' => $locationType,
                'id' => $location->id,
                'name' => $location->name,
            ];

            // Get recent gathering activity at this location
            try {
                $data['recent_activity'] = LocationActivityLog::atLocation($locationType, $location->id)
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
        }

        return Inertia::render('Gathering/Index', $data);
    }

    /**
     * Show a specific gathering activity.
     */
    public function show(Request $request, Village $village = null, string $activity = null): Response
    {
        // Handle both legacy /gathering/{activity} and new /villages/{village}/gathering/{activity} routes
        if ($village === null && $activity === null) {
            // This shouldn't happen, but handle gracefully
            return Inertia::render('Gathering/NotAvailable', [
                'message' => 'Invalid gathering activity.',
            ]);
        }

        // If $village is actually the activity string (legacy route)
        if (is_string($village)) {
            $activity = $village;
            $village = null;
        }

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

        $data = [
            'activity' => $info,
            'player_energy' => $user->energy,
            'max_energy' => $user->max_energy,
        ];

        if ($village) {
            $data['location'] = [
                'type' => 'village',
                'id' => $village->id,
                'name' => $village->name,
            ];
        }

        return Inertia::render('Gathering/Activity', $data);
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
        $result = $this->gatheringService->gather(
            $user,
            $request->input('activity'),
            $user->current_location_type,
            $user->current_location_id
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}

<?php

namespace App\Http\Controllers;

use App\Config\LocationServices;
use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Kingdom;
use App\Models\LocationActivityLog;
use App\Models\Town;
use App\Models\Village;
use App\Services\TrainingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainingController extends Controller
{
    public function __construct(
        protected TrainingService $trainingService
    ) {}

    /**
     * Legacy index - redirects to location-scoped route.
     */
    public function legacyIndex(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Redirect to location-scoped route if possible
        if ($user->current_location_type && $user->current_location_id) {
            $routeName = LocationServices::getServiceRoute($user->current_location_type, 'training');
            if ($routeName && \Route::has($routeName)) {
                return redirect()->route($routeName, [$user->current_location_type => $user->current_location_id]);
            }
        }

        // Fall back to original behavior
        return $this->renderIndex($request->user(), null, null);
    }

    /**
     * Show the training grounds (location-scoped).
     */
    public function index(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null): Response
    {
        // Get location from route binding
        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);

        return $this->renderIndex($request->user(), $location, $locationType);
    }

    /**
     * Render the training index page.
     */
    protected function renderIndex($user, $location, ?string $locationType): Response
    {
        if (!$this->trainingService->canTrain($user)) {
            return Inertia::render('Training/NotAvailable', [
                'message' => 'There are no training grounds at your current location. Travel to a village, town, or barony to train.',
            ]);
        }

        $exercises = $this->trainingService->getAvailableExercises($user);
        $combatStats = $this->trainingService->getCombatStats($user);

        $data = [
            'exercises' => $exercises,
            'combat_stats' => $combatStats,
            'player_energy' => $user->energy,
            'max_energy' => $user->max_energy,
            'player_hp' => $user->hp,
            'max_hp' => $user->max_hp,
        ];

        // Add location context if available
        if ($location && $locationType) {
            $data['location'] = [
                'type' => $locationType,
                'id' => $location->id,
                'name' => $location->name,
            ];

            // Get recent training activity at this location
            try {
                $data['recent_activity'] = LocationActivityLog::atLocation($locationType, $location->id)
                    ->ofType(LocationActivityLog::TYPE_TRAINING)
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

        return Inertia::render('Training/Index', $data);
    }

    /**
     * Perform a training exercise.
     */
    public function train(Request $request): JsonResponse
    {
        $request->validate([
            'exercise' => 'required|string|in:attack,strength,defense',
        ]);

        $user = $request->user();
        $result = $this->trainingService->train(
            $user,
            $request->input('exercise'),
            $user->current_location_type,
            $user->current_location_id
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get current training status.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'can_train' => $this->trainingService->canTrain($user),
            'combat_stats' => $this->trainingService->getCombatStats($user),
            'player_energy' => $user->energy,
            'max_energy' => $user->max_energy,
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
}

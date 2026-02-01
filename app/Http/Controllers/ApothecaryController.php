<?php

namespace App\Http\Controllers;

use App\Config\LocationServices;
use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Kingdom;
use App\Models\LocationActivityLog;
use App\Models\Town;
use App\Models\Village;
use App\Services\ApothecaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApothecaryController extends Controller
{
    public function __construct(
        protected ApothecaryService $apothecaryService
    ) {}

    /**
     * Legacy index - redirects to location-scoped route.
     */
    public function legacyIndex(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->current_location_type && $user->current_location_id) {
            $routeName = LocationServices::getServiceRoute($user->current_location_type, 'apothecary');
            if ($routeName && \Route::has($routeName)) {
                return redirect()->route($routeName, [$user->current_location_type => $user->current_location_id]);
            }
        }

        return $this->renderIndex($request->user(), null, null);
    }

    /**
     * Show the apothecary page (location-scoped).
     */
    public function index(
        Request $request,
        ?Village $village = null,
        ?Town $town = null,
        ?Barony $barony = null,
        ?Duchy $duchy = null,
        ?Kingdom $kingdom = null
    ): Response {
        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);

        return $this->renderIndex($request->user(), $location, $locationType);
    }

    /**
     * Render the apothecary index page.
     */
    protected function renderIndex($user, $location, ?string $locationType): Response
    {
        if (! $this->apothecaryService->canBrew($user)) {
            return Inertia::render('Apothecary/NotAvailable', [
                'message' => 'You cannot access the apothecary at your current location.',
            ]);
        }

        $info = $this->apothecaryService->getBrewingInfo($user);

        $data = [
            'brewing_info' => $info,
        ];

        if ($location && $locationType) {
            $data['location'] = [
                'type' => $locationType,
                'id' => $location->id,
                'name' => $location->name,
            ];

            try {
                $data['recent_activity'] = LocationActivityLog::atLocation($locationType, $location->id)
                    ->where('activity_subtype', 'apothecary')
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

        return Inertia::render('Apothecary/Index', $data);
    }

    /**
     * Brew a potion.
     */
    public function brew(
        Request $request,
        ?Village $village = null,
        ?Town $town = null,
        ?Barony $barony = null,
        ?Duchy $duchy = null,
        ?Kingdom $kingdom = null
    ): JsonResponse {
        $request->validate([
            'recipe' => 'required|string',
        ]);

        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);

        $user = $request->user();
        $result = $this->apothecaryService->brew(
            $user,
            $request->input('recipe'),
            $locationType ?? $user->current_location_type,
            $location?->id ?? $user->current_location_id
        );

        return response()->json($result, $result['success'] ? 200 : 422);
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

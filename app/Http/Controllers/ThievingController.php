<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Kingdom;
use App\Models\Town;
use App\Models\Village;
use App\Services\ThievingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ThievingController extends Controller
{
    public function __construct(
        protected ThievingService $thievingService
    ) {}

    /**
     * Show the thieving page.
     */
    public function index(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null): Response
    {
        $user = $request->user();
        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);

        if (! $this->thievingService->canThieve($user)) {
            return Inertia::render('Thieving/NotAvailable', [
                'message' => 'There are no suitable targets for thieving at your current location.',
            ]);
        }

        $info = $this->thievingService->getThievingInfo($user);

        $data = [
            'thieving_info' => $info,
        ];

        if ($location && $locationType) {
            $data['location'] = [
                'type' => $locationType,
                'id' => $location->id,
                'name' => $location->name,
            ];
        }

        return Inertia::render('Thieving/Index', $data);
    }

    /**
     * Attempt to thieve from a target.
     */
    public function thieve(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null): JsonResponse
    {
        $request->validate([
            'target' => 'required|string',
        ]);

        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);

        $user = $request->user();
        $result = $this->thievingService->thieve(
            $user,
            $request->input('target'),
            $locationType ?? $user->current_location_type,
            $location?->id ?? $user->current_location_id
        );

        return response()->json($result, $result['success'] || isset($result['caught']) ? 200 : 422);
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

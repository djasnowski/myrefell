<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Town;
use App\Models\Village;
use App\Services\AgilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AgilityController extends Controller
{
    public function __construct(
        protected AgilityService $agilityService
    ) {}

    /**
     * Show the agility training page.
     */
    public function index(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null): Response
    {
        $user = $request->user();
        $location = $village ?? $town ?? $barony ?? $duchy;
        $locationType = $this->getLocationType($location);

        if (! $this->agilityService->canTrain($user)) {
            return Inertia::render('Agility/NotAvailable', [
                'message' => 'There is no agility course at your current location.',
            ]);
        }

        $info = $this->agilityService->getAgilityInfo($user);

        $data = [
            'agility_info' => $info,
        ];

        if ($location && $locationType) {
            $data['location'] = [
                'type' => $locationType,
                'id' => $location->id,
                'name' => $location->name,
            ];
        }

        return Inertia::render('Agility/Index', $data);
    }

    /**
     * Attempt an agility obstacle.
     */
    public function train(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null): JsonResponse
    {
        $request->validate([
            'obstacle' => 'required|string',
        ]);

        $location = $village ?? $town ?? $barony ?? $duchy;
        $locationType = $this->getLocationType($location);

        $user = $request->user();
        $result = $this->agilityService->train(
            $user,
            $request->input('obstacle'),
            $locationType ?? $user->current_location_type,
            $location?->id ?? $user->current_location_id
        );

        return response()->json($result, $result['success'] || isset($result['failed']) ? 200 : 422);
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
            default => null,
        };
    }
}

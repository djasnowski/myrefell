<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\Town;
use App\Models\Village;
use App\Services\SawmillService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SawmillController extends Controller
{
    public function __construct(
        protected SawmillService $sawmillService
    ) {}

    /**
     * Show the sawmill page.
     */
    public function index(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null): Response
    {
        $user = $request->user();
        $location = $village ?? $town ?? $barony;

        $data = [
            'recipes' => $this->sawmillService->getAvailablePlanks($user),
            'playerGold' => $user->gold,
        ];

        if ($location) {
            $data['location'] = [
                'type' => $this->getLocationType($location),
                'id' => $location->id,
                'name' => $location->name,
            ];
        }

        return Inertia::render('Sawmill/Index', $data);
    }

    /**
     * Convert logs to planks.
     */
    public function convert(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null): RedirectResponse
    {
        $request->validate([
            'plank_name' => 'required|string',
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $result = $this->sawmillService->makePlanks(
            $request->user(),
            $request->input('plank_name'),
            $request->input('quantity'),
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    protected function getLocationType($location): ?string
    {
        return match (true) {
            $location instanceof Village => 'village',
            $location instanceof Town => 'town',
            $location instanceof Barony => 'barony',
            default => null,
        };
    }
}

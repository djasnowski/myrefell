<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Kingdom;
use App\Models\Town;
use App\Models\Village;
use App\Services\PlayerConstructionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlayerConstructionController extends Controller
{
    public function __construct(
        protected PlayerConstructionService $constructionService
    ) {}

    /**
     * Show the construction contracts page.
     */
    public function index(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null): Response
    {
        $user = $request->user();
        $user->load('skills');
        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;

        $data = [
            'contracts' => $this->constructionService->getAvailableContracts($user),
            'constructionLevel' => $this->constructionService->getConstructionLevel($user),
            'playerEnergy' => $user->energy,
            'maxEnergy' => $user->max_energy,
        ];

        if ($location) {
            $data['location'] = [
                'type' => $this->getLocationType($location),
                'id' => $location->id,
                'name' => $location->name,
            ];
        }

        return Inertia::render('Construction/Index', $data);
    }

    /**
     * Complete a construction contract.
     */
    public function doContract(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null): RedirectResponse
    {
        $request->validate([
            'tier' => 'required|string',
        ]);

        $user = $request->user();
        $user->load('skills');

        $result = $this->constructionService->doContract($user, $request->input('tier'));

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
            $location instanceof Duchy => 'duchy',
            $location instanceof Kingdom => 'kingdom',
            default => null,
        };
    }
}

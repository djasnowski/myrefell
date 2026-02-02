<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Kingdom;
use App\Models\Town;
use App\Models\Village;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArenaController extends Controller
{
    /**
     * Show the arena page.
     */
    public function index(
        Request $request,
        ?Village $village = null,
        ?Town $town = null,
        ?Barony $barony = null,
        ?Duchy $duchy = null,
        ?Kingdom $kingdom = null
    ): Response {
        $user = $request->user();
        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);

        return Inertia::render('Arena/Index', [
            'location' => [
                'id' => $location?->id,
                'name' => $location?->name,
                'type' => $locationType,
            ],
            'player' => [
                'id' => $user->id,
                'username' => $user->username,
                'gold' => $user->gold,
                'energy' => $user->energy,
                'max_energy' => $user->max_energy,
            ],
        ]);
    }

    /**
     * Get the location type from the model.
     */
    protected function getLocationType(mixed $location): ?string
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

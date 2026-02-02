<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Kingdom;
use App\Models\Town;
use App\Models\Village;
use App\Services\DiceGameService;
use Illuminate\Http\Request;

class DiceGameController extends Controller
{
    public function __construct(
        protected DiceGameService $diceGameService
    ) {}

    /**
     * Play a dice game at the tavern.
     */
    public function play(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null)
    {
        $request->validate([
            'game_type' => 'required|string|in:high_roll,hazard,doubles',
            'wager' => 'required|integer|min:'.DiceGameService::MIN_WAGER.'|max:'.DiceGameService::MAX_WAGER,
        ]);

        $user = $request->user();
        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location) ?? $user->current_location_type ?? 'village';
        $locationId = $location?->id ?? $user->current_location_id ?? 1;

        $result = $this->diceGameService->play(
            $user,
            $request->input('game_type'),
            $request->input('wager'),
            $locationType,
            $locationId
        );

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('dice_result', $result);
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

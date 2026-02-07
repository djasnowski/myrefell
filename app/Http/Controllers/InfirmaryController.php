<?php

namespace App\Http\Controllers;

use App\Services\InfirmaryService;
use Illuminate\Http\Request;

class InfirmaryController extends Controller
{
    public function __construct(
        protected InfirmaryService $infirmaryService
    ) {}

    /**
     * Discharge the player from the infirmary when the timer expires.
     */
    public function discharge(Request $request): \Illuminate\Http\RedirectResponse
    {
        $player = $request->user();

        if (! $player->is_in_infirmary) {
            return back()->with('error', 'You are not in the infirmary.');
        }

        $discharged = $this->infirmaryService->checkAndDischarge($player);

        if (! $discharged) {
            return back()->with('error', 'Your infirmary timer has not expired yet.');
        }

        return back()->with('success', 'You have been discharged from the infirmary. Your wounds have healed.');
    }
}

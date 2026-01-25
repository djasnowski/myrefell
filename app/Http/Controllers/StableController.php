<?php

namespace App\Http\Controllers;

use App\Models\Horse;
use App\Services\StableService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StableController extends Controller
{
    public function __construct(
        private StableService $stableService
    ) {}

    /**
     * Show the stable page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $locationType = $user->current_location_type;

        // Get available horses at this location
        $stock = $this->stableService->getStableStock($locationType);

        // Get user's current horse if any
        $userHorse = $this->stableService->getUserHorse($user);

        return Inertia::render('Stable/Index', [
            'stock' => $stock,
            'userHorse' => $userHorse,
            'locationType' => $locationType,
            'userGold' => $user->gold,
        ]);
    }

    /**
     * Purchase a horse.
     */
    public function buy(Request $request)
    {
        $request->validate([
            'horse_id' => 'required|exists:horses,id',
            'price' => 'required|integer|min:1',
            'custom_name' => 'nullable|string|max:50',
        ]);

        $user = $request->user();
        $horse = Horse::findOrFail($request->horse_id);

        $result = $this->stableService->buyHorse(
            $user,
            $horse,
            $request->price,
            $request->custom_name
        );

        if (!$result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Sell the user's horse.
     */
    public function sell(Request $request)
    {
        $user = $request->user();

        $result = $this->stableService->sellHorse($user);

        if (!$result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Rename the user's horse.
     */
    public function rename(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
        ]);

        $user = $request->user();

        $result = $this->stableService->renameHorse($user, $request->name);

        if (!$result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Stable the horse at current location.
     */
    public function stable(Request $request)
    {
        $user = $request->user();

        $result = $this->stableService->stableHorse($user);

        if (!$result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Retrieve horse from stable.
     */
    public function retrieve(Request $request)
    {
        $user = $request->user();

        $result = $this->stableService->retrieveHorse($user);

        if (!$result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Rest horse at stable (pay to restore stamina).
     */
    public function rest(Request $request)
    {
        $user = $request->user();

        $result = $this->stableService->restHorse($user);

        if (!$result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }
}

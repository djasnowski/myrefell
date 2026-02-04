<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\CultHideoutProject;
use App\Models\Duchy;
use App\Models\Kingdom;
use App\Models\Religion;
use App\Models\Town;
use App\Models\Village;
use App\Services\CultHideoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CultHideoutController extends Controller
{
    public function __construct(
        protected CultHideoutService $hideoutService
    ) {}

    /**
     * Show the cult hideout page (location-scoped).
     */
    public function showAtLocation(
        Request $request,
        ?Village $village = null,
        ?Town $town = null,
        ?Barony $barony = null,
        ?Duchy $duchy = null,
        ?Kingdom $kingdom = null,
        ?Religion $religion = null
    ): Response {
        $user = $request->user();
        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);

        if (! $religion || ! $religion->isCult()) {
            abort(404, 'Cult not found.');
        }

        // Ensure we have fresh data (in case of redirect after build/upgrade)
        $religion->refresh();

        $hideoutInfo = $this->hideoutService->getHideoutInfo($religion, $user);

        return Inertia::render('Cults/Hideout/Index', [
            'cult' => [
                'id' => $religion->id,
                'name' => $religion->name,
                'description' => $religion->description,
                'icon' => $religion->icon,
                'color' => $religion->color,
                'member_count' => $religion->member_count,
            ],
            'hideout' => $hideoutInfo,
            'player' => [
                'gold' => $user->gold,
                'energy' => $user->energy,
                'current_hp' => $user->hp,
                'max_hp' => $user->max_hp,
            ],
            'location' => [
                'type' => $locationType,
                'id' => $location?->id,
                'name' => $location?->name,
            ],
        ]);
    }

    /**
     * Build a hideout for the cult.
     */
    public function build(
        Request $request,
        ?Village $village = null,
        ?Town $town = null,
        ?Barony $barony = null,
        ?Duchy $duchy = null,
        ?Kingdom $kingdom = null,
        ?Religion $religion = null
    ): RedirectResponse {
        $user = $request->user();
        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);

        if (! $religion || ! $religion->isCult()) {
            return back()->withErrors(['error' => 'Cult not found.']);
        }

        $result = $this->hideoutService->buildHideout(
            $user,
            $religion,
            $locationType,
            $location->id
        );

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Start a hideout upgrade project.
     */
    public function startUpgrade(
        Request $request,
        ?Village $village = null,
        ?Town $town = null,
        ?Barony $barony = null,
        ?Duchy $duchy = null,
        ?Kingdom $kingdom = null,
        ?Religion $religion = null
    ): RedirectResponse {
        $user = $request->user();

        if (! $religion || ! $religion->isCult()) {
            return back()->withErrors(['error' => 'Cult not found.']);
        }

        $result = $this->hideoutService->startUpgrade($user, $religion);

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Contribute to a hideout project.
     */
    public function contribute(
        Request $request,
        ?Village $village = null,
        ?Town $town = null,
        ?Barony $barony = null,
        ?Duchy $duchy = null,
        ?Kingdom $kingdom = null,
        ?Religion $religion = null,
        ?CultHideoutProject $project = null
    ): RedirectResponse {
        $user = $request->user();

        if (! $religion || ! $religion->isCult()) {
            return back()->withErrors(['error' => 'Cult not found.']);
        }

        if (! $project || $project->religion_id !== $religion->id) {
            return back()->withErrors(['error' => 'Project not found.']);
        }

        $validated = $request->validate([
            'gold' => 'nullable|integer|min:0',
            'devotion' => 'nullable|integer|min:0',
        ]);

        $result = $this->hideoutService->contributeToProject($user, $project, $validated);

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Complete a hideout project (manual trigger when construction is done).
     */
    public function completeProject(
        Request $request,
        ?Village $village = null,
        ?Town $town = null,
        ?Barony $barony = null,
        ?Duchy $duchy = null,
        ?Kingdom $kingdom = null,
        ?Religion $religion = null,
        ?CultHideoutProject $project = null
    ): RedirectResponse {
        if (! $religion || ! $religion->isCult()) {
            return back()->withErrors(['error' => 'Cult not found.']);
        }

        if (! $project || $project->religion_id !== $religion->id) {
            return back()->withErrors(['error' => 'Project not found.']);
        }

        if (! $project->isConstructionComplete()) {
            return back()->withErrors(['error' => 'Construction is not yet complete.']);
        }

        $this->hideoutService->finalizeProject($project);

        $tierName = Religion::HIDEOUT_TIERS[$project->target_tier]['name'] ?? 'Unknown';

        return back()->with('success', "The hideout has been upgraded to {$tierName}!");
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

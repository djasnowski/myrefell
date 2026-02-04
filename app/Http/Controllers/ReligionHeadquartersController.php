<?php

namespace App\Http\Controllers;

use App\Models\HqConstructionProject;
use App\Models\Religion;
use App\Models\ReligionHqFeature;
use App\Services\ReligionHeadquartersService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReligionHeadquartersController extends Controller
{
    public function __construct(
        protected ReligionHeadquartersService $hqService
    ) {}

    /**
     * Resolve the religion from route parameter (handles both model binding and raw ID).
     */
    protected function resolveReligion(Request $request): Religion
    {
        $param = $request->route('religion');

        return $param instanceof Religion ? $param : Religion::findOrFail($param);
    }

    /**
     * Resolve the project from route parameter.
     */
    protected function resolveProject(Request $request): HqConstructionProject
    {
        $param = $request->route('project');

        return $param instanceof HqConstructionProject ? $param : HqConstructionProject::findOrFail($param);
    }

    /**
     * Resolve the feature from route parameter.
     */
    protected function resolveFeature(Request $request): ReligionHqFeature
    {
        $param = $request->route('feature');

        return $param instanceof ReligionHqFeature ? $param : ReligionHqFeature::findOrFail($param);
    }

    /**
     * Redirect from legacy /religions/{id}/headquarters to location-scoped URL.
     */
    public function redirectToLocationScoped(Request $request): RedirectResponse
    {
        $religion = $this->resolveReligion($request);
        $hq = $religion->headquarters;

        // If HQ exists and is built, redirect to its location
        if ($hq && $hq->isBuilt()) {
            $locationPlural = match ($hq->location_type) {
                'village' => 'villages',
                'barony' => 'baronies',
                'town' => 'towns',
                'duchy' => 'duchies',
                'kingdom' => 'kingdoms',
                default => $hq->location_type.'s',
            };

            return redirect("/{$locationPlural}/{$hq->location_id}/religions/{$religion->id}/headquarters");
        }

        // HQ not built - redirect to religion page
        return redirect()->route('religions.show', $religion->id);
    }

    /**
     * Display the headquarters page.
     */
    public function showAtLocation(Request $request): Response
    {
        return $this->renderHeadquarters($request, $this->resolveReligion($request));
    }

    /**
     * Render the headquarters page.
     */
    protected function renderHeadquarters(Request $request, Religion $religion): Response
    {
        $user = $request->user();

        $hqInfo = $this->hqService->getHeadquartersInfo($religion, $user);
        $treasuryInfo = $this->hqService->getTreasuryInfo($religion);

        // Get current location name
        $locationName = null;
        if ($user->current_location_type && $user->current_location_id) {
            $locationName = match ($user->current_location_type) {
                'village' => \App\Models\Village::find($user->current_location_id)?->name,
                'barony' => \App\Models\Barony::find($user->current_location_id)?->name,
                'town' => \App\Models\Town::find($user->current_location_id)?->name,
                'kingdom' => \App\Models\Kingdom::find($user->current_location_id)?->name,
                default => null,
            };
        }

        return Inertia::render('Religions/Headquarters/Index', [
            'religion' => [
                'id' => $religion->id,
                'name' => $religion->name,
                'icon' => $religion->icon,
                'color' => $religion->color,
                'type' => $religion->type,
            ],
            'headquarters' => $hqInfo,
            'treasury' => $treasuryInfo,
            'gold' => $user->gold,
            'energy' => $user->energy,
            'current_location' => [
                'type' => $user->current_location_type,
                'id' => $user->current_location_id,
                'name' => $locationName,
            ],
        ]);
    }

    /**
     * Build the headquarters at a location.
     */
    public function build(Request $request): RedirectResponse
    {
        $religion = $this->resolveReligion($request);

        $validated = $request->validate([
            'location_type' => 'required|string|in:village,barony,town,kingdom',
            'location_id' => 'required|integer',
        ]);

        $result = $this->hqService->buildHeadquarters(
            $request->user(),
            $religion,
            $validated['location_type'],
            $validated['location_id']
        );

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Donate gold to the treasury.
     */
    public function donate(Request $request): RedirectResponse
    {
        $religion = $this->resolveReligion($request);

        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $result = $this->hqService->donateToTreasury(
            $request->user(),
            $religion,
            $validated['amount']
        );

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * View treasury transaction history.
     */
    public function treasuryHistory(Request $request): Response
    {
        $religion = $this->resolveReligion($request);

        $treasuryInfo = $this->hqService->getTreasuryInfo($religion);

        return Inertia::render('Religions/Headquarters/Treasury', [
            'religion' => [
                'id' => $religion->id,
                'name' => $religion->name,
                'icon' => $religion->icon,
                'color' => $religion->color,
            ],
            'treasury' => $treasuryInfo,
        ]);
    }

    /**
     * Start an HQ upgrade.
     */
    public function startUpgrade(Request $request): RedirectResponse
    {
        $religion = $this->resolveReligion($request);

        $result = $this->hqService->startHqUpgrade($request->user(), $religion);

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Start building a new feature.
     */
    public function buildFeature(Request $request): RedirectResponse
    {
        $religion = $this->resolveReligion($request);

        $validated = $request->validate([
            'feature_type_id' => 'required|integer|exists:hq_feature_types,id',
        ]);

        $result = $this->hqService->startFeatureBuild(
            $request->user(),
            $religion,
            $validated['feature_type_id']
        );

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Start upgrading a feature.
     */
    public function upgradeFeature(Request $request, int $featureId): RedirectResponse
    {
        $religion = $this->resolveReligion($request);

        $result = $this->hqService->startFeatureUpgrade(
            $request->user(),
            $religion,
            $featureId
        );

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Contribute to an active project.
     */
    public function contribute(Request $request): RedirectResponse
    {
        $religion = $this->resolveReligion($request);
        $project = $this->resolveProject($request);

        $validated = $request->validate([
            'gold' => 'nullable|integer|min:0',
            'devotion' => 'nullable|integer|min:0',
            'items' => 'nullable|array',
            'items.*' => 'integer|min:1',
        ]);

        // Verify project belongs to this religion's HQ
        if ($project->headquarters->religion_id !== $religion->id) {
            return back()->withErrors(['error' => 'Invalid project.']);
        }

        $result = $this->hqService->contributeToProject(
            $request->user(),
            $project,
            [
                'gold' => $validated['gold'] ?? 0,
                'devotion' => $validated['devotion'] ?? 0,
                'items' => $validated['items'] ?? [],
            ]
        );

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Pray at a feature to receive a temporary buff.
     */
    public function pray(Request $request): RedirectResponse
    {
        $religion = $this->resolveReligion($request);
        $feature = $this->resolveFeature($request);

        // Verify feature belongs to this religion's HQ
        if ($feature->headquarters->religion_id !== $religion->id) {
            return back()->withErrors(['error' => 'Invalid feature.']);
        }

        $result = $this->hqService->prayAtFeature($request->user(), $feature);

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Complete a construction project that is ready.
     */
    public function completeProject(Request $request): RedirectResponse
    {
        $religion = $this->resolveReligion($request);
        $project = $this->resolveProject($request);

        // Verify project belongs to this religion's HQ
        if ($project->headquarters->religion_id !== $religion->id) {
            return back()->withErrors(['error' => 'Invalid project.']);
        }

        // Check if project is ready to complete
        if (! $project->isConstructionComplete()) {
            return back()->withErrors(['error' => 'This project is not ready for completion.']);
        }

        // Complete the project
        $this->hqService->finalizeProject($project);

        $description = $project->project_type === HqConstructionProject::TYPE_HQ_UPGRADE
            ? 'Headquarters upgraded!'
            : "{$project->featureType->name} construction complete!";

        return back()->with('success', $description);
    }
}

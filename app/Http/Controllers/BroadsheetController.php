<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBroadsheetCommentRequest;
use App\Http\Requests\StoreBroadsheetRequest;
use App\Models\Barony;
use App\Models\Broadsheet;
use App\Models\BroadsheetComment;
use App\Models\Duchy;
use App\Models\Kingdom;
use App\Models\Town;
use App\Models\Village;
use App\Services\BroadsheetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BroadsheetController extends Controller
{
    public function __construct(
        protected BroadsheetService $broadsheetService
    ) {}

    /**
     * Display the broadsheets listing page (Notice Board).
     */
    public function index(
        Request $request,
        ?Village $village = null,
        ?Town $town = null,
        ?Barony $barony = null,
        ?Duchy $duchy = null,
        ?Kingdom $kingdom = null,
    ): Response {
        $user = $request->user();
        $tab = $request->get('tab', 'local');

        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);

        $locationData = $this->buildLocationData($location, $locationType);

        $local = $location
            ? $this->broadsheetService->getLocalBroadsheets($locationType, $location->id)
            : null;

        $baronyData = $locationData
            ? $this->broadsheetService->getBaronyBroadsheets($locationData['barony_id'])
            : null;

        $kingdomData = $locationData
            ? $this->broadsheetService->getKingdomBroadsheets($locationData['kingdom_id'])
            : null;

        $hasPublishedToday = Broadsheet::where('author_id', $user->id)
            ->whereDate('published_at', today())
            ->exists();

        $publishCost = $this->broadsheetService->getPublishCost($locationType);

        return Inertia::render('Broadsheets/Index', [
            'local' => $local,
            'barony' => $baronyData,
            'kingdom' => $kingdomData,
            'tab' => $tab,
            'player_gold' => $user->gold,
            'publish_cost' => $publishCost,
            'has_published_today' => $hasPublishedToday,
            'location' => $location ? [
                'type' => $locationType,
                'id' => $location->id,
                'name' => $location->name,
            ] : null,
            'barony_name' => $locationData['barony_name'] ?? null,
            'kingdom_name' => $locationData['kingdom_name'] ?? null,
        ]);
    }

    /**
     * Display a single broadsheet (location-scoped).
     */
    public function show(
        Request $request,
        ?Village $village = null,
        ?Town $town = null,
        ?Barony $barony = null,
        ?Duchy $duchy = null,
        ?Kingdom $kingdom = null,
        ?Broadsheet $broadsheet = null,
    ): Response {
        $user = $request->user();

        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);

        $details = $this->broadsheetService->getBroadsheetWithDetails($broadsheet, $user);

        return Inertia::render('Broadsheets/Show', [
            'broadsheet' => $details['broadsheet'],
            'comments' => $details['comments'],
            'current_user_id' => $user->id,
            'location' => $location ? [
                'type' => $locationType,
                'id' => $location->id,
                'name' => $location->name,
            ] : null,
        ]);
    }

    /**
     * Display a broadsheet via global read-only route (for shared links).
     */
    public function showGlobal(Request $request, Broadsheet $broadsheet): Response
    {
        $user = $request->user();
        $details = $this->broadsheetService->getBroadsheetWithDetails($broadsheet, $user);

        return Inertia::render('Broadsheets/Show', [
            'broadsheet' => $details['broadsheet'],
            'comments' => $details['comments'],
            'current_user_id' => $user->id,
            'location' => null,
        ]);
    }

    /**
     * Store a new broadsheet.
     */
    public function store(
        StoreBroadsheetRequest $request,
        ?Village $village = null,
        ?Town $town = null,
        ?Barony $barony = null,
        ?Duchy $duchy = null,
        ?Kingdom $kingdom = null,
    ): RedirectResponse {
        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);

        $locationData = $this->buildLocationData($location, $locationType);

        if (! $locationData) {
            return back()->with('error', 'Invalid location.');
        }

        $result = $this->broadsheetService->publish($request->user(), $request->validated(), $locationData);

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Delete a broadsheet.
     */
    public function destroy(
        Request $request,
        ?Village $village = null,
        ?Town $town = null,
        ?Barony $barony = null,
        ?Duchy $duchy = null,
        ?Kingdom $kingdom = null,
        ?Broadsheet $broadsheet = null,
    ): RedirectResponse {
        $result = $this->broadsheetService->delete($request->user(), $broadsheet);

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);
        $prefix = match ($locationType) {
            'village' => "/villages/{$location->id}",
            'town' => "/towns/{$location->id}",
            'barony' => "/baronies/{$location->id}",
            'duchy' => "/duchies/{$location->id}",
            'kingdom' => "/kingdoms/{$location->id}",
            default => '',
        };

        return redirect("{$prefix}/notice-board")->with('success', $result['message']);
    }

    /**
     * Toggle a reaction on a broadsheet.
     */
    public function react(
        Request $request,
        ?Village $village = null,
        ?Town $town = null,
        ?Barony $barony = null,
        ?Duchy $duchy = null,
        ?Kingdom $kingdom = null,
        ?Broadsheet $broadsheet = null,
    ): RedirectResponse {
        $request->validate(['type' => ['required', 'string', 'in:endorse,denounce']]);

        $result = $this->broadsheetService->react($request->user(), $broadsheet, $request->type);

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Post a comment on a broadsheet.
     */
    public function comment(
        StoreBroadsheetCommentRequest $request,
        ?Village $village = null,
        ?Town $town = null,
        ?Barony $barony = null,
        ?Duchy $duchy = null,
        ?Kingdom $kingdom = null,
        ?Broadsheet $broadsheet = null,
    ): RedirectResponse {
        $result = $this->broadsheetService->comment(
            $request->user(),
            $broadsheet,
            $request->body,
            $request->parent_id
        );

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Delete a comment.
     */
    public function deleteComment(
        Request $request,
        ?Village $village = null,
        ?Town $town = null,
        ?Barony $barony = null,
        ?Duchy $duchy = null,
        ?Kingdom $kingdom = null,
        ?BroadsheetComment $broadsheetComment = null,
    ): RedirectResponse {
        $result = $this->broadsheetService->deleteComment($request->user(), $broadsheetComment);

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Build location data with barony and kingdom info from the resolved location model.
     *
     * @return array{type: string, id: int, name: string, barony_id: int, barony_name: string, kingdom_id: int, kingdom_name: string}|null
     */
    protected function buildLocationData($location, ?string $locationType): ?array
    {
        if (! $location || ! $locationType) {
            return null;
        }

        if ($location instanceof Village) {
            $location->loadMissing('barony.kingdom');

            if (! $location->barony?->kingdom) {
                return null;
            }

            return [
                'type' => 'village',
                'id' => $location->id,
                'name' => $location->name,
                'barony_id' => $location->barony_id,
                'barony_name' => $location->barony->name,
                'kingdom_id' => $location->barony->kingdom_id,
                'kingdom_name' => $location->barony->kingdom->name,
            ];
        }

        if ($location instanceof Town) {
            $location->loadMissing('barony.kingdom');

            if (! $location->barony?->kingdom) {
                return null;
            }

            return [
                'type' => 'town',
                'id' => $location->id,
                'name' => $location->name,
                'barony_id' => $location->barony_id,
                'barony_name' => $location->barony->name,
                'kingdom_id' => $location->barony->kingdom_id,
                'kingdom_name' => $location->barony->kingdom->name,
            ];
        }

        if ($location instanceof Barony) {
            $location->loadMissing('kingdom');

            if (! $location->kingdom) {
                return null;
            }

            return [
                'type' => 'barony',
                'id' => $location->id,
                'name' => $location->name,
                'barony_id' => $location->id,
                'barony_name' => $location->name,
                'kingdom_id' => $location->kingdom_id,
                'kingdom_name' => $location->kingdom->name,
            ];
        }

        if ($location instanceof Duchy) {
            $location->loadMissing('kingdom');

            if (! $location->kingdom) {
                return null;
            }

            return [
                'type' => 'duchy',
                'id' => $location->id,
                'name' => $location->name,
                'barony_id' => null,
                'barony_name' => null,
                'kingdom_id' => $location->kingdom_id,
                'kingdom_name' => $location->kingdom->name,
            ];
        }

        if ($location instanceof Kingdom) {
            return [
                'type' => 'kingdom',
                'id' => $location->id,
                'name' => $location->name,
                'barony_id' => null,
                'barony_name' => null,
                'kingdom_id' => $location->id,
                'kingdom_name' => $location->name,
            ];
        }

        return null;
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

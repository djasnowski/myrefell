<?php

namespace App\Http\Controllers;

use App\Config\LocationServices;
use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Horse;
use App\Models\Kingdom;
use App\Models\Town;
use App\Services\StableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StableController extends Controller
{
    public function __construct(
        private StableService $stableService
    ) {}

    /**
     * Legacy index - redirects to location-scoped route.
     */
    public function legacyIndex(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Redirect to location-scoped route if possible
        if ($user->current_location_type && $user->current_location_id) {
            $routeName = LocationServices::getServiceRoute($user->current_location_type, 'stables');
            if ($routeName && \Route::has($routeName)) {
                return redirect()->route($routeName, [$user->current_location_type => $user->current_location_id]);
            }
        }

        // Fall back to showing not available
        return $this->renderNotAvailable();
    }

    /**
     * Show the stable page (location-scoped).
     */
    public function index(Request $request, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null): Response
    {
        $user = $request->user();
        $location = $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);

        // Check if stables are available at this location type
        if (! LocationServices::isServiceAvailable($locationType, 'stables')) {
            return $this->renderNotAvailable();
        }

        return $this->renderIndex($user, $location, $locationType);
    }

    /**
     * Render the stable index page.
     */
    protected function renderIndex($user, $location, string $locationType): Response
    {
        // Get available horses at this location
        $rawStock = $this->stableService->getStableStock($locationType);

        // Transform stock data for frontend
        $stock = collect($rawStock)
            ->filter(fn ($item) => $item['in_stock'])
            ->map(fn ($item) => [
                'id' => $item['horse']->id,
                'name' => $item['horse']->name,
                'description' => $item['horse']->description,
                'breed' => ucfirst($item['horse']->min_location_type).' Horse',
                'speed_multiplier' => (float) $item['horse']->speed_multiplier,
                'stamina' => $item['horse']->base_stamina,
                'max_stamina' => $item['horse']->base_stamina,
                'price' => $item['price'],
                'rarity' => $this->getRarityName($item['horse']->rarity),
            ])
            ->values()
            ->all();

        // Get user's current horse if any
        $userHorse = $this->stableService->getUserHorse($user);

        // Transform user horse data for frontend
        $userHorseData = null;
        if ($userHorse) {
            $userHorseData = [
                'id' => $userHorse['id'],
                'custom_name' => $userHorse['name'] !== $userHorse['type'] ? $userHorse['name'] : null,
                'horse' => [
                    'name' => $userHorse['type'],
                    'breed' => 'Horse',
                    'speed_multiplier' => $userHorse['speed_multiplier'],
                ],
                'stamina' => $userHorse['stamina'],
                'max_stamina' => $userHorse['max_stamina'],
                'is_stabled' => $userHorse['is_stabled'],
                'sell_value' => $userHorse['sell_price'],
            ];
        }

        $data = [
            'stock' => $stock,
            'userHorse' => $userHorseData,
            'locationType' => $locationType,
            'userGold' => $user->gold,
        ];

        // Add location context
        if ($location) {
            $data['location'] = [
                'type' => $locationType,
                'id' => $location->id,
                'name' => $location->name,
            ];
        }

        return Inertia::render('Stable/Index', $data);
    }

    /**
     * Convert rarity percentage to name.
     */
    protected function getRarityName(int $rarity): string
    {
        return match (true) {
            $rarity >= 80 => 'common',
            $rarity >= 50 => 'uncommon',
            $rarity >= 25 => 'rare',
            $rarity >= 10 => 'epic',
            default => 'legendary',
        };
    }

    /**
     * Render the not available page.
     */
    protected function renderNotAvailable(): Response
    {
        return Inertia::render('Stable/NotAvailable', [
            'message' => 'There are no stables at your current location. Travel to a town, barony, duchy, or kingdom to access the stables.',
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

        if (! $result['success']) {
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

        if (! $result['success']) {
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

        if (! $result['success']) {
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

        if (! $result['success']) {
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

        if (! $result['success']) {
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

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Determine location type from model.
     */
    protected function getLocationType($location): ?string
    {
        return match (true) {
            $location instanceof Town => 'town',
            $location instanceof Barony => 'barony',
            $location instanceof Duchy => 'duchy',
            $location instanceof Kingdom => 'kingdom',
            default => null,
        };
    }
}

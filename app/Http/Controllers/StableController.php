<?php

namespace App\Http\Controllers;

use App\Config\LocationServices;
use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Horse;
use App\Models\Kingdom;
use App\Models\PlayerHorse;
use App\Models\Town;
use App\Models\Village;
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
    public function index(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null): Response
    {
        $user = $request->user();
        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
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

        // Get user's active horse (for backwards compatibility with current UI)
        $userHorse = $this->stableService->getUserHorse($user);
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
                'is_active' => $userHorse['is_active'],
                'is_stabled' => $userHorse['is_stabled'],
                'stabled_location_type' => $userHorse['stabled_location_type'],
                'stabled_location_id' => $userHorse['stabled_location_id'],
                'sell_value' => $userHorse['sell_price'],
            ];
        }

        // Get all of user's horses
        $userHorses = $this->stableService->getUserHorses($user)->toArray();

        // Get all horses stabled at this location (with owners)
        $stabledHorses = $location
            ? $this->stableService->getHorsesStabledAt($locationType, $location->id)->toArray()
            : [];

        // Check if user is stablemaster at this location
        $isStablemaster = $location
            ? $this->stableService->isStablemaster($user, $locationType, $location->id)
            : false;

        $data = [
            'stock' => $stock,
            'userHorse' => $userHorseData,
            'userHorses' => $userHorses,
            'stabledHorses' => $stabledHorses,
            'isStablemaster' => $isStablemaster,
            'maxHorses' => PlayerHorse::MAX_HORSES_PER_USER,
            'horseCount' => $user->horseCount(),
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
            'message' => 'There are no stables at your current location.',
        ]);
    }

    /**
     * Purchase a horse.
     */
    public function buy(Request $request): RedirectResponse
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
     * Sell a horse.
     */
    public function sell(Request $request): RedirectResponse
    {
        $request->validate([
            'player_horse_id' => 'nullable|integer|exists:player_horses,id',
        ]);

        $user = $request->user();

        $result = $this->stableService->sellHorse($user, $request->player_horse_id);

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Rename a horse.
     */
    public function rename(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'player_horse_id' => 'nullable|integer|exists:player_horses,id',
        ]);

        $user = $request->user();

        $result = $this->stableService->renameHorse($user, $request->name, $request->player_horse_id);

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Stable a horse at current location.
     */
    public function stable(Request $request): RedirectResponse
    {
        $request->validate([
            'player_horse_id' => 'nullable|integer|exists:player_horses,id',
        ]);

        $user = $request->user();

        $result = $this->stableService->stableHorse($user, $request->player_horse_id);

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Retrieve horse from stable.
     */
    public function retrieve(Request $request): RedirectResponse
    {
        $request->validate([
            'player_horse_id' => 'nullable|integer|exists:player_horses,id',
        ]);

        $user = $request->user();

        $result = $this->stableService->retrieveHorse($user, $request->player_horse_id);

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Switch active horse.
     */
    public function switchActive(Request $request): RedirectResponse
    {
        $request->validate([
            'player_horse_id' => 'required|integer|exists:player_horses,id',
        ]);

        $user = $request->user();

        $result = $this->stableService->switchActiveHorse($user, $request->player_horse_id);

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Rest horse at stable (pay to restore stamina).
     */
    public function rest(Request $request): RedirectResponse
    {
        $request->validate([
            'player_horse_id' => 'nullable|integer|exists:player_horses,id',
        ]);

        $user = $request->user();

        $result = $this->stableService->restHorse($user, $request->player_horse_id);

        if (! $result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Stablemaster feeds all horses at the stable.
     */
    public function feedHorses(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->current_location_type || ! $user->current_location_id) {
            return back()->withErrors(['error' => 'You must be at a location to feed horses.']);
        }

        $result = $this->stableService->stablemasterFeedHorses(
            $user,
            $user->current_location_type,
            $user->current_location_id
        );

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
            $location instanceof Village => 'village',
            $location instanceof Town => 'town',
            $location instanceof Barony => 'barony',
            $location instanceof Duchy => 'duchy',
            $location instanceof Kingdom => 'kingdom',
            default => null,
        };
    }
}

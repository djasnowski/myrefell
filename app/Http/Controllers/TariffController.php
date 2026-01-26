<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\Item;
use App\Models\Kingdom;
use App\Models\TariffCollection;
use App\Models\TradeTariff;
use App\Models\TradeRoute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TariffController extends Controller
{
    /**
     * Minimum tariff rate.
     */
    public const MIN_TARIFF_RATE = 0;

    /**
     * Maximum tariff rate.
     */
    public const MAX_TARIFF_RATE = 50;

    /**
     * Display tariff management page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get the user's ruled territory (barony or kingdom)
        $ruledBarony = Barony::where('baron_user_id', $user->id)->first();
        $ruledKingdom = Kingdom::where('king_user_id', $user->id)->first();

        // If no ruled territory, show empty state
        if (!$ruledBarony && !$ruledKingdom) {
            return Inertia::render('Trade/Tariffs', [
                'can_manage' => false,
                'territory' => null,
                'routes' => [],
                'tariffs' => [],
                'revenue' => [
                    'this_week' => 0,
                    'this_month' => 0,
                    'total' => 0,
                ],
                'min_rate' => self::MIN_TARIFF_RATE,
                'max_rate' => self::MAX_TARIFF_RATE,
                'items' => [],
            ]);
        }

        // Prefer barony if user has both
        $locationType = $ruledBarony ? 'barony' : 'kingdom';
        $locationId = $ruledBarony ? $ruledBarony->id : $ruledKingdom->id;
        $territory = $ruledBarony ?? $ruledKingdom;

        // Get tariffs for this location
        $tariffs = TradeTariff::atLocation($locationType, $locationId)
            ->with(['item', 'setBy', 'collections'])
            ->get()
            ->map(fn ($tariff) => $this->mapTariff($tariff));

        // Get routes passing through this territory
        $routes = $this->getRoutesThrough($locationType, $locationId, $territory);

        // Calculate revenue
        $revenue = $this->calculateRevenue($locationType, $locationId);

        // Get all tradeable items for the dropdown
        $items = Item::whereIn('type', ['resource', 'consumable', 'misc'])
            ->orderBy('name')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'type' => $item->type,
                'base_value' => $item->base_value,
            ]);

        return Inertia::render('Trade/Tariffs', [
            'can_manage' => true,
            'territory' => [
                'type' => $locationType,
                'id' => $locationId,
                'name' => $territory->name,
            ],
            'routes' => $routes,
            'tariffs' => $tariffs->toArray(),
            'revenue' => $revenue,
            'min_rate' => self::MIN_TARIFF_RATE,
            'max_rate' => self::MAX_TARIFF_RATE,
            'items' => $items->toArray(),
        ]);
    }

    /**
     * Store a new tariff.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'location_type' => 'required|in:barony,kingdom',
            'location_id' => 'required|integer',
            'item_id' => 'nullable|integer|exists:items,id',
            'tariff_rate' => 'required|integer|min:' . self::MIN_TARIFF_RATE . '|max:' . self::MAX_TARIFF_RATE,
        ]);

        // Check permission
        if (!$this->canManageTariffs($user, $validated['location_type'], $validated['location_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage tariffs here.',
            ], 403);
        }

        // Check if tariff already exists for this item/location
        $existing = TradeTariff::atLocation($validated['location_type'], $validated['location_id'])
            ->where('item_id', $validated['item_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => $validated['item_id']
                    ? 'A tariff already exists for this item. Use update instead.'
                    : 'A general tariff already exists. Use update instead.',
            ], 422);
        }

        $tariff = TradeTariff::create([
            'location_type' => $validated['location_type'],
            'location_id' => $validated['location_id'],
            'item_id' => $validated['item_id'],
            'tariff_rate' => $validated['tariff_rate'],
            'is_active' => true,
            'set_by_user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tariff created successfully.',
            'tariff' => $this->mapTariff($tariff->load(['item', 'setBy'])),
        ]);
    }

    /**
     * Update an existing tariff.
     */
    public function update(Request $request, TradeTariff $tariff): JsonResponse
    {
        $user = $request->user();

        // Check permission
        if (!$this->canManageTariffs($user, $tariff->location_type, $tariff->location_id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage tariffs here.',
            ], 403);
        }

        $validated = $request->validate([
            'tariff_rate' => 'required|integer|min:' . self::MIN_TARIFF_RATE . '|max:' . self::MAX_TARIFF_RATE,
            'is_active' => 'sometimes|boolean',
        ]);

        $tariff->update([
            'tariff_rate' => $validated['tariff_rate'],
            'is_active' => $validated['is_active'] ?? $tariff->is_active,
            'set_by_user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tariff updated successfully.',
            'tariff' => $this->mapTariff($tariff->fresh(['item', 'setBy'])),
        ]);
    }

    /**
     * Check if user can manage tariffs at a location.
     */
    private function canManageTariffs($user, string $locationType, int $locationId): bool
    {
        if ($locationType === 'barony') {
            return Barony::where('id', $locationId)
                ->where('baron_user_id', $user->id)
                ->exists();
        }

        if ($locationType === 'kingdom') {
            return Kingdom::where('id', $locationId)
                ->where('king_user_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Get routes passing through a territory.
     */
    private function getRoutesThrough(string $locationType, int $locationId, $territory): array
    {
        // For baronies, get routes where either endpoint is in the barony
        // For kingdoms, get routes where either endpoint is in the kingdom
        $routes = TradeRoute::active()->get();

        $throughRoutes = [];

        foreach ($routes as $route) {
            $passesThrough = false;
            $origin = $route->origin;
            $destination = $route->destination;

            if ($locationType === 'barony') {
                // Check if origin or destination is in this barony
                if ($origin && method_exists($origin, 'barony_id') && $origin->barony_id === $locationId) {
                    $passesThrough = true;
                }
                if ($destination && method_exists($destination, 'barony_id') && $destination->barony_id === $locationId) {
                    $passesThrough = true;
                }
                // For villages, check barony
                if ($route->origin_type === 'village' && $origin?->barony_id === $locationId) {
                    $passesThrough = true;
                }
                if ($route->destination_type === 'village' && $destination?->barony_id === $locationId) {
                    $passesThrough = true;
                }
            } elseif ($locationType === 'kingdom') {
                // Check if origin or destination is in this kingdom
                if ($origin) {
                    $originBarony = $origin->barony ?? null;
                    if ($originBarony?->kingdom_id === $locationId) {
                        $passesThrough = true;
                    }
                }
                if ($destination) {
                    $destBarony = $destination->barony ?? null;
                    if ($destBarony?->kingdom_id === $locationId) {
                        $passesThrough = true;
                    }
                }
            }

            if ($passesThrough) {
                // Get tariff info for this route
                $tariff = TradeTariff::atLocation($locationType, $locationId)
                    ->whereNull('item_id')
                    ->active()
                    ->first();

                // Count caravans this week on this route
                $caravansThisWeek = $route->caravans()
                    ->where('created_at', '>=', now()->subWeek())
                    ->count();

                // Calculate revenue for this route
                $routeRevenue = TariffCollection::where('location_type', $locationType)
                    ->where('location_id', $locationId)
                    ->whereHas('caravan', fn ($q) => $q->where('trade_route_id', $route->id))
                    ->sum('amount_collected');

                $throughRoutes[] = [
                    'id' => $route->id,
                    'name' => $route->name,
                    'origin' => [
                        'name' => $origin?->name ?? 'Unknown',
                        'type' => $route->origin_type,
                    ],
                    'destination' => [
                        'name' => $destination?->name ?? 'Unknown',
                        'type' => $route->destination_type,
                    ],
                    'tariff_rate' => $tariff?->tariff_rate ?? 0,
                    'caravans_this_week' => $caravansThisWeek,
                    'revenue' => $routeRevenue,
                ];
            }
        }

        return $throughRoutes;
    }

    /**
     * Calculate revenue statistics.
     */
    private function calculateRevenue(string $locationType, int $locationId): array
    {
        $query = TariffCollection::where('location_type', $locationType)
            ->where('location_id', $locationId);

        $thisWeek = (clone $query)->where('created_at', '>=', now()->subWeek())->sum('amount_collected');
        $thisMonth = (clone $query)->where('created_at', '>=', now()->subMonth())->sum('amount_collected');
        $total = $query->sum('amount_collected');

        return [
            'this_week' => (int) $thisWeek,
            'this_month' => (int) $thisMonth,
            'total' => (int) $total,
        ];
    }

    /**
     * Map a tariff to array format.
     */
    private function mapTariff(TradeTariff $tariff): array
    {
        return [
            'id' => $tariff->id,
            'item_id' => $tariff->item_id,
            'item_name' => $tariff->item?->name ?? 'All Goods',
            'tariff_rate' => $tariff->tariff_rate,
            'is_active' => $tariff->is_active,
            'set_by' => $tariff->setBy ? [
                'id' => $tariff->setBy->id,
                'username' => $tariff->setBy->username,
            ] : null,
            'total_collected' => $tariff->collections()->sum('amount_collected'),
            'created_at' => $tariff->created_at?->toISOString(),
            'updated_at' => $tariff->updated_at?->toISOString(),
        ];
    }
}

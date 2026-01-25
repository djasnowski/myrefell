<?php

namespace App\Http\Controllers;

use App\Models\Caravan;
use App\Models\Item;
use App\Models\TradeRoute;
use App\Services\CaravanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CaravanController extends Controller
{
    public function __construct(
        protected CaravanService $caravanService
    ) {}

    /**
     * Display a listing of the user's caravans.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get active caravans (preparing, traveling, returning)
        $activeCaravans = Caravan::where('owner_id', $user->id)
            ->whereIn('status', [
                Caravan::STATUS_PREPARING,
                Caravan::STATUS_TRAVELING,
                Caravan::STATUS_RETURNING,
            ])
            ->with(['tradeRoute', 'goods.item'])
            ->get()
            ->map(fn ($caravan) => $this->mapCaravan($caravan));

        // Get arrived caravans (for unloading)
        $arrivedCaravans = Caravan::where('owner_id', $user->id)
            ->where('status', Caravan::STATUS_ARRIVED)
            ->with(['tradeRoute', 'goods.item'])
            ->get()
            ->map(fn ($caravan) => $this->mapCaravan($caravan));

        // Get completed/historical caravans
        $completedCaravans = Caravan::where('owner_id', $user->id)
            ->where('status', Caravan::STATUS_DISBANDED)
            ->with('tradeRoute')
            ->latest('updated_at')
            ->limit(10)
            ->get()
            ->map(fn ($caravan) => $this->mapCaravan($caravan, false));

        // Get available trade routes for creating new caravans
        $availableRoutes = TradeRoute::active()
            ->get()
            ->map(fn ($route) => [
                'id' => $route->id,
                'name' => $route->name,
                'origin' => [
                    'type' => $route->origin_type,
                    'id' => $route->origin_id,
                    'name' => $route->origin?->name ?? 'Unknown',
                ],
                'destination' => [
                    'type' => $route->destination_type,
                    'id' => $route->destination_id,
                    'name' => $route->destination?->name ?? 'Unknown',
                ],
                'base_travel_days' => $route->base_travel_days,
                'danger_level' => $route->danger_level,
            ]);

        // Get player's current location for creating caravans
        $currentLocation = [
            'type' => $user->location_type,
            'id' => $user->location_id,
            'name' => $user->location?->name ?? 'Unknown',
        ];

        return Inertia::render('Trade/Caravans', [
            'active_caravans' => $activeCaravans->toArray(),
            'arrived_caravans' => $arrivedCaravans->toArray(),
            'completed_caravans' => $completedCaravans->toArray(),
            'available_routes' => $availableRoutes->toArray(),
            'current_location' => $currentLocation,
            'caravan_cost' => CaravanService::CARAVAN_CREATION_COST,
            'guard_cost' => CaravanService::GUARD_HIRE_COST,
            'base_capacity' => CaravanService::BASE_CAPACITY,
        ]);
    }

    /**
     * Map caravan to array for frontend.
     */
    private function mapCaravan(Caravan $caravan, bool $includeGoods = true): array
    {
        $data = [
            'id' => $caravan->id,
            'name' => $caravan->name,
            'status' => $caravan->status,
            'capacity' => $caravan->capacity,
            'guards' => $caravan->guards,
            'gold_carried' => $caravan->gold_carried,
            'travel_progress' => $caravan->travel_progress,
            'travel_total' => $caravan->travel_total,
            'travel_progress_percent' => $caravan->travel_progress_percent,
            'departed_at' => $caravan->departed_at?->toISOString(),
            'arrived_at' => $caravan->arrived_at?->toISOString(),
            'current_location' => [
                'type' => $caravan->current_location_type,
                'id' => $caravan->current_location_id,
                'name' => $caravan->current_location?->name ?? 'Unknown',
            ],
            'destination' => [
                'type' => $caravan->destination_type,
                'id' => $caravan->destination_id,
                'name' => $caravan->destination?->name ?? 'Unknown',
            ],
            'route' => $caravan->tradeRoute ? [
                'id' => $caravan->tradeRoute->id,
                'name' => $caravan->tradeRoute->name,
                'danger_level' => $caravan->tradeRoute->danger_level,
            ] : null,
        ];

        if ($includeGoods) {
            $data['goods'] = $caravan->goods->map(fn ($goods) => [
                'id' => $goods->id,
                'item_id' => $goods->item_id,
                'item_name' => $goods->item->name ?? 'Unknown',
                'quantity' => $goods->quantity,
                'purchase_price' => $goods->purchase_price,
                'total_value' => $goods->total_value,
            ])->toArray();
            $data['total_goods'] = $caravan->total_goods;
            $data['remaining_capacity'] = $caravan->remaining_capacity;
            $data['goods_value'] = $caravan->goods->sum('total_value');
        }

        return $data;
    }

    /**
     * Store a newly created caravan.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'guards' => 'integer|min:0|max:20',
        ]);

        $result = $this->caravanService->createCaravan(
            owner: $user,
            name: $validated['name'],
            locationType: $user->location_type,
            locationId: $user->location_id,
            guards: $validated['guards'] ?? 0
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Load goods onto a caravan.
     */
    public function loadGoods(Request $request, Caravan $caravan): JsonResponse
    {
        $user = $request->user();

        // Check ownership
        if ($caravan->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this caravan.',
            ], 403);
        }

        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $item = Item::findOrFail($validated['item_id']);

        // Get current market price or inventory purchase price as baseline
        $inventoryItem = $user->inventory()->where('item_id', $item->id)->first();
        $purchasePrice = $inventoryItem?->purchase_price ?? $item->base_price ?? 1;

        $result = $this->caravanService->loadGoods(
            caravan: $caravan,
            item: $item,
            quantity: $validated['quantity'],
            purchasePrice: $purchasePrice,
            owner: $user
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Dispatch a caravan to travel.
     */
    public function dispatch(Request $request, Caravan $caravan): JsonResponse
    {
        $user = $request->user();

        // Check ownership
        if ($caravan->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this caravan.',
            ], 403);
        }

        $validated = $request->validate([
            'route_id' => 'nullable|exists:trade_routes,id',
            'destination_type' => 'required_without:route_id|in:village,town',
            'destination_id' => 'required_without:route_id|integer',
        ]);

        $route = null;
        if (isset($validated['route_id'])) {
            $route = TradeRoute::find($validated['route_id']);
            $destType = $route->destination_type;
            $destId = $route->destination_id;
        } else {
            $destType = $validated['destination_type'];
            $destId = $validated['destination_id'];
        }

        $result = $this->caravanService->depart(
            caravan: $caravan,
            destinationType: $destType,
            destinationId: $destId,
            route: $route
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Unload/sell goods from an arrived caravan.
     */
    public function unload(Request $request, Caravan $caravan): JsonResponse
    {
        $user = $request->user();

        // Check ownership
        if ($caravan->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this caravan.',
            ], 403);
        }

        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
            'sale_price' => 'required|integer|min:1',
        ]);

        $result = $this->caravanService->sellGoods(
            caravan: $caravan,
            itemId: $validated['item_id'],
            quantity: $validated['quantity'],
            salePrice: $validated['sale_price']
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Disband a caravan and return goods to owner.
     */
    public function disband(Request $request, Caravan $caravan): JsonResponse
    {
        $user = $request->user();

        // Check ownership
        if ($caravan->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this caravan.',
            ], 403);
        }

        $result = $this->caravanService->disbandCaravan($caravan);

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}

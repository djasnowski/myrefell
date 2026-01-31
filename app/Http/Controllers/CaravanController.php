<?php

namespace App\Http\Controllers;

use App\Models\Caravan;
use App\Models\Item;
use App\Models\TradeRoute;
use App\Services\CaravanService;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CaravanController extends Controller
{
    public function __construct(
        protected CaravanService $caravanService,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Display a single caravan detail page.
     */
    public function show(Request $request, Caravan $caravan): Response
    {
        $user = $request->user();

        // Check ownership
        if ($caravan->owner_id !== $user->id) {
            abort(403, 'You do not own this caravan.');
        }

        // Load relations
        $caravan->load(['tradeRoute.originSettlement', 'tradeRoute.destinationSettlement', 'goods.item', 'events']);

        // Get available routes from current location
        $availableRoutes = TradeRoute::active()
            ->where(function ($query) use ($caravan) {
                $query->where('origin_type', $caravan->current_location_type)
                    ->where('origin_id', $caravan->current_location_id);
            })
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

        // Get player's tradeable inventory items
        $inventory = $user->inventory()
            ->with('item')
            ->whereHas('item', fn ($q) => $q->where('is_tradeable', true))
            ->get()
            ->map(fn ($inv) => [
                'id' => $inv->item_id,
                'name' => $inv->item->name ?? 'Unknown',
                'quantity' => $inv->quantity,
                'base_price' => $inv->item->base_price ?? 1,
            ]);

        return Inertia::render('Trade/CaravanShow', [
            'caravan' => $this->mapCaravanDetail($caravan),
            'available_routes' => $availableRoutes->toArray(),
            'inventory' => $inventory->toArray(),
        ]);
    }

    /**
     * Map caravan to detailed array for frontend.
     */
    private function mapCaravanDetail(Caravan $caravan): array
    {
        $route = $caravan->tradeRoute;

        return [
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
            'route' => $route ? [
                'id' => $route->id,
                'name' => $route->name,
                'danger_level' => $route->danger_level,
                'base_travel_days' => $route->base_travel_days,
                'origin_name' => $route->origin?->name ?? 'Unknown',
                'destination_name' => $route->destination?->name ?? 'Unknown',
            ] : null,
            'goods' => $caravan->goods->map(fn ($goods) => [
                'id' => $goods->id,
                'item_id' => $goods->item_id,
                'item_name' => $goods->item->name ?? 'Unknown',
                'quantity' => $goods->quantity,
                'purchase_price' => $goods->purchase_price,
                'total_value' => $goods->total_value,
            ])->toArray(),
            'total_goods' => $caravan->total_goods,
            'remaining_capacity' => $caravan->remaining_capacity,
            'goods_value' => $caravan->goods->sum('total_value'),
            'events' => $caravan->events->map(fn ($event) => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'event_type_name' => $event->event_type_name,
                'description' => $event->description,
                'gold_lost' => $event->gold_lost,
                'gold_gained' => $event->gold_gained,
                'goods_lost' => $event->goods_lost,
                'guards_lost' => $event->guards_lost,
                'days_delayed' => $event->days_delayed,
                'is_negative' => $event->isNegative(),
                'is_positive' => $event->isPositive(),
                'created_at' => $event->created_at->toISOString(),
            ])->sortByDesc('created_at')->values()->toArray(),
            'can_depart' => $caravan->canDepart(),
            'is_traveling' => $caravan->isTraveling(),
            'has_arrived' => $caravan->status === Caravan::STATUS_ARRIVED,
        ];
    }

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
        $locationName = 'Unknown';
        if ($user->current_location_type && $user->current_location_id) {
            $locationModel = match ($user->current_location_type) {
                'village' => \App\Models\Village::find($user->current_location_id),
                'town' => \App\Models\Town::find($user->current_location_id),
                'barony' => \App\Models\Barony::find($user->current_location_id),
                default => null,
            };
            $locationName = $locationModel?->name ?? 'Unknown';
        }
        $currentLocation = [
            'type' => $user->current_location_type,
            'id' => $user->current_location_id,
            'name' => $locationName,
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
            locationType: $user->current_location_type,
            locationId: $user->current_location_id,
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

    /**
     * Remove goods from caravan (return to inventory).
     */
    public function removeGoods(Request $request, Caravan $caravan): JsonResponse
    {
        $user = $request->user();

        // Check ownership
        if ($caravan->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this caravan.',
            ], 403);
        }

        // Can only remove goods while preparing
        if ($caravan->status !== Caravan::STATUS_PREPARING) {
            return response()->json([
                'success' => false,
                'message' => 'Can only remove goods while caravan is loading.',
            ], 400);
        }

        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $goods = $caravan->goods()->where('item_id', $validated['item_id'])->first();

        if (! $goods || $goods->quantity < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient goods in caravan.',
            ], 400);
        }

        // Return to player inventory
        $this->inventoryService->addItem($user, $validated['item_id'], $validated['quantity']);

        // Remove from caravan
        if ($goods->quantity === $validated['quantity']) {
            $goods->delete();
        } else {
            $goods->decrement('quantity', $validated['quantity']);
        }

        $item = Item::find($validated['item_id']);

        return response()->json([
            'success' => true,
            'message' => "Removed {$validated['quantity']} {$item->name} from caravan.",
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Kingdom;
use App\Models\Shop;
use App\Models\ShopItem;
use App\Models\Town;
use App\Models\Village;
use App\Services\ShopService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function __construct(
        protected ShopService $shopService
    ) {}

    /**
     * Show the shops index page (location-scoped).
     */
    public function index(
        Request $request,
        ?Village $village = null,
        ?Town $town = null,
        ?Barony $barony = null,
        ?Duchy $duchy = null,
        ?Kingdom $kingdom = null
    ): Response {
        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);
        $user = $request->user();

        $shops = $this->shopService->getShopsAtLocation(
            $locationType ?? $user->current_location_type,
            $location?->id ?? $user->current_location_id
        );

        $data = [
            'shops' => $shops->map(fn (Shop $shop) => [
                'id' => $shop->id,
                'name' => $shop->name,
                'slug' => $shop->slug,
                'npc_name' => $shop->npc_name,
                'description' => $shop->description,
                'icon' => $shop->icon,
                'item_count' => $shop->items()->active()->count(),
            ]),
        ];

        if ($location && $locationType) {
            $data['location'] = [
                'type' => $locationType,
                'id' => $location->id,
                'name' => $location->name,
            ];
        }

        return Inertia::render('Shops/Index', $data);
    }

    /**
     * Show a specific shop.
     */
    public function show(
        Request $request,
        ?Village $village = null,
        ?Town $town = null,
        ?Barony $barony = null,
        ?Duchy $duchy = null,
        ?Kingdom $kingdom = null,
        ?Shop $shop = null
    ): Response {
        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);
        $user = $request->user();

        $shopData = $this->shopService->getShopWithItems($shop, $user);

        $data = $shopData;

        if ($location && $locationType) {
            $data['location'] = [
                'type' => $locationType,
                'id' => $location->id,
                'name' => $location->name,
            ];
        }

        return Inertia::render('Shops/Show', $data);
    }

    /**
     * Buy an item from a shop.
     */
    public function buy(
        Request $request,
        ?Village $village = null,
        ?Town $town = null,
        ?Barony $barony = null,
        ?Duchy $duchy = null,
        ?Kingdom $kingdom = null,
        ?Shop $shop = null
    ): JsonResponse {
        $request->validate([
            'shop_item_id' => 'required|integer|exists:shop_items,id',
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $shopItem = ShopItem::where('id', $request->input('shop_item_id'))
            ->where('shop_id', $shop->id)
            ->firstOrFail();

        $result = $this->shopService->buyItem(
            $request->user(),
            $shopItem,
            $request->input('quantity')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
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

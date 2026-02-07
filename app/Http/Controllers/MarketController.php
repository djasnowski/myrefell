<?php

namespace App\Http\Controllers;

use App\Models\Village;
use App\Services\MarketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MarketController extends Controller
{
    public function __construct(
        protected MarketService $marketService
    ) {}

    /**
     * Show the market page for a village.
     */
    public function villageMarket(Request $request, int $villageId): Response
    {
        return $this->showMarket($request, 'village', $villageId);
    }

    /**
     * Show the market page for a barony.
     */
    public function baronyMarket(Request $request, int $baronyId): Response
    {
        return $this->showMarket($request, 'barony', $baronyId);
    }

    /**
     * Show the market page for a town.
     */
    public function townMarket(Request $request, int $townId): Response
    {
        return $this->showMarket($request, 'town', $townId);
    }

    /**
     * Show the market page for a kingdom.
     */
    public function kingdomMarket(Request $request, int $kingdomId): Response
    {
        return $this->showMarket($request, 'kingdom', $kingdomId);
    }

    /**
     * Show the market page.
     */
    protected function showMarket(Request $request, string $locationType, int $locationId): Response
    {
        $user = $request->user();

        // Check if player is at this location
        if ($user->current_location_type !== $locationType || $user->current_location_id !== $locationId) {
            return Inertia::render('Market/NotHere', [
                'message' => 'You must be at this location to access its market.',
            ]);
        }

        if (! $this->marketService->canAccessMarket($user)) {
            return Inertia::render('Market/NotHere', [
                'message' => 'You cannot access a market while traveling.',
            ]);
        }

        // Handle hamlet -> parent village
        $effectiveLocationId = $locationId;
        if ($locationType === 'village') {
            $village = Village::find($locationId);
            if ($village && $village->isHamlet()) {
                $serviceProvider = $village->getServiceProvider();
                $effectiveLocationId = $serviceProvider->id;
            }
        }

        $marketInfo = $this->marketService->getMarketInfo($user);
        $marketPrices = $this->marketService->getMarketPrices($locationType, $effectiveLocationId);
        $sellableItems = $this->marketService->getSellableItems($user, $locationType, $effectiveLocationId);
        $recentTransactions = $this->marketService->getRecentTransactions($user);

        return Inertia::render('Market/Index', [
            'market_info' => $marketInfo,
            'market_prices' => $marketPrices->values()->toArray(),
            'sellable_items' => $sellableItems->toArray(),
            'recent_transactions' => $recentTransactions->toArray(),
        ]);
    }

    /**
     * Buy an item from the market.
     */
    public function buy(Request $request): JsonResponse
    {
        $request->validate([
            'item_id' => 'required|integer|exists:items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $result = $this->marketService->buyItem(
            $user,
            $request->input('item_id'),
            $request->input('quantity')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Sell an item to the market.
     */
    public function sell(Request $request): JsonResponse
    {
        $request->validate([
            'item_id' => 'required|integer|exists:items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $result = $this->marketService->sellItem(
            $user,
            $request->input('item_id'),
            $request->input('quantity')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get a sell quote for an item at a specific quantity.
     */
    public function sellQuote(Request $request): JsonResponse
    {
        $request->validate([
            'item_id' => 'required|integer|exists:items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $result = $this->marketService->getSellQuote(
            $user,
            $request->input('item_id'),
            $request->input('quantity')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get current market prices (for polling/refresh).
     */
    public function prices(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->marketService->canAccessMarket($user)) {
            return response()->json(['error' => 'Cannot access market'], 422);
        }

        $locationType = $user->current_location_type;
        $locationId = $user->current_location_id;

        // Handle hamlet -> parent village
        if ($locationType === 'village') {
            $village = Village::find($locationId);
            if ($village && $village->isHamlet()) {
                $serviceProvider = $village->getServiceProvider();
                $locationId = $serviceProvider->id;
            }
        }

        $marketPrices = $this->marketService->getMarketPrices($locationType, $locationId);

        return response()->json([
            'market_prices' => $marketPrices->values()->toArray(),
        ]);
    }
}

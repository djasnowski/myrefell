<?php

namespace App\Http\Controllers;

use App\Services\RoleStockingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleStockingController extends Controller
{
    public function __construct(
        protected RoleStockingService $roleStockingService
    ) {}

    /**
     * Show the role stocking page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $stockableItems = $this->roleStockingService->getStockableItems($user);
        $managedStockpile = $this->roleStockingService->getManagedStockpile($user);

        // Resolve location name
        $locationName = 'Unknown';
        if ($user->current_location_type && $user->current_location_id) {
            $location = match ($user->current_location_type) {
                'village' => \App\Models\Village::find($user->current_location_id),
                'barony' => \App\Models\Barony::find($user->current_location_id),
                'town' => \App\Models\Town::find($user->current_location_id),
                default => null,
            };
            $locationName = $location?->name ?? 'Unknown';
        }

        return Inertia::render('Market/Stock', [
            'stockable_items' => $stockableItems,
            'managed_stockpile' => $managedStockpile,
            'location_name' => $locationName,
        ]);
    }

    /**
     * Stock an item from inventory to the market.
     */
    public function stock(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $result = $this->roleStockingService->stockItem(
            $user,
            $validated['item_id'],
            $validated['quantity']
        );

        if (!$result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\BusinessEmployee;
use App\Models\BusinessType;
use App\Models\LocationNpc;
use App\Models\PlayerBusiness;
use App\Models\Town;
use App\Models\Village;
use App\Services\BusinessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    public function __construct(
        protected BusinessService $businessService
    ) {}

    /**
     * Display businesses at a village.
     */
    public function villageBusinesses(Request $request, Village $village): Response
    {
        $user = $request->user();

        if ($user->current_location_type !== 'village' || $user->current_location_id !== $village->id) {
            return Inertia::render('Business/NotHere', [
                'location' => $village->name,
            ]);
        }

        return $this->renderBusinessesPage($user, 'village', $village->id, $village->name);
    }

    /**
     * Display businesses at a town.
     */
    public function townBusinesses(Request $request, Town $town): Response
    {
        $user = $request->user();

        if ($user->current_location_type !== 'town' || $user->current_location_id !== $town->id) {
            return Inertia::render('Business/NotHere', [
                'location' => $town->name,
            ]);
        }

        return $this->renderBusinessesPage($user, 'town', $town->id, $town->name);
    }

    /**
     * Display businesses at a barony.
     */
    public function baronyBusinesses(Request $request, Barony $barony): Response
    {
        $user = $request->user();

        if ($user->current_location_type !== 'barony' || $user->current_location_id !== $barony->id) {
            return Inertia::render('Business/NotHere', [
                'location' => $barony->name,
            ]);
        }

        return $this->renderBusinessesPage($user, 'barony', $barony->id, $barony->name);
    }

    /**
     * Render the businesses page for any location type.
     */
    protected function renderBusinessesPage($user, string $locationType, int $locationId, string $locationName): Response
    {
        $availableTypes = $this->businessService->getAvailableBusinessTypes($user, $locationType, $locationId);
        $localBusinesses = $this->businessService->getBusinessesAtLocation($locationType, $locationId);
        $myBusinesses = $this->businessService->getPlayerBusinesses($user);

        // Get available NPCs for hiring
        $availableNpcs = LocationNpc::where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->whereDoesntHave('businessEmployment', function ($q) {
                $q->where('status', 'employed');
            })
            ->get()
            ->map(fn ($npc) => [
                'id' => $npc->id,
                'name' => $npc->name,
            ]);

        return Inertia::render('Business/Index', [
            'location_type' => $locationType,
            'location_id' => $locationId,
            'location_name' => $locationName,
            'available_types' => $availableTypes,
            'local_businesses' => $localBusinesses,
            'my_businesses' => $myBusinesses,
            'available_npcs' => $availableNpcs,
            'max_businesses' => BusinessService::MAX_BUSINESSES_PER_PLAYER,
            'player' => [
                'gold' => $user->gold,
            ],
        ]);
    }

    /**
     * Display player's own businesses overview.
     */
    public function myBusinesses(Request $request): Response
    {
        $user = $request->user();
        $businesses = $this->businessService->getPlayerBusinesses($user);

        return Inertia::render('Business/MyBusinesses', [
            'businesses' => $businesses,
            'max_businesses' => BusinessService::MAX_BUSINESSES_PER_PLAYER,
            'player' => [
                'gold' => $user->gold,
            ],
        ]);
    }

    /**
     * Display a single business details.
     */
    public function show(Request $request, PlayerBusiness $business): Response
    {
        $user = $request->user();

        // Only owner can see detailed view
        if ($business->user_id !== $user->id) {
            return Inertia::render('Business/NotOwner', [
                'business_name' => $business->name,
            ]);
        }

        $details = $this->businessService->getBusinessDetails($business);

        // Get available NPCs for hiring at business location
        $availableNpcs = LocationNpc::where('location_type', $business->location_type)
            ->where('location_id', $business->location_id)
            ->whereDoesntHave('businessEmployment', function ($q) {
                $q->where('status', 'employed');
            })
            ->get()
            ->map(fn ($npc) => [
                'id' => $npc->id,
                'name' => $npc->name,
            ]);

        return Inertia::render('Business/Show', [
            'business' => $details,
            'available_npcs' => $availableNpcs,
            'player' => [
                'gold' => $user->gold,
            ],
        ]);
    }

    /**
     * Establish a new business.
     */
    public function establish(Request $request): RedirectResponse
    {
        $request->validate([
            'business_type_id' => 'required|exists:business_types,id',
            'name' => 'required|string|min:2|max:50',
            'location_type' => 'required|in:village,town,barony',
            'location_id' => 'required|integer',
        ]);

        $user = $request->user();
        $businessType = BusinessType::findOrFail($request->business_type_id);

        $result = $this->businessService->establishBusiness(
            $user,
            $businessType,
            $request->name,
            $request->location_type,
            $request->location_id
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Close a business.
     */
    public function close(Request $request, PlayerBusiness $business): RedirectResponse
    {
        $user = $request->user();
        $result = $this->businessService->closeBusiness($user, $business);

        if ($result['success']) {
            return redirect()->route('businesses.index')->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Deposit gold into business.
     */
    public function deposit(Request $request, PlayerBusiness $business): RedirectResponse|JsonResponse
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $result = $this->businessService->depositGold($user, $business, $request->amount);

        if ($request->wantsJson()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Withdraw gold from business.
     */
    public function withdraw(Request $request, PlayerBusiness $business): RedirectResponse|JsonResponse
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $result = $this->businessService->withdrawGold($user, $business, $request->amount);

        if ($request->wantsJson()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Hire an NPC employee.
     */
    public function hire(Request $request, PlayerBusiness $business): RedirectResponse
    {
        $request->validate([
            'npc_id' => 'required|exists:location_npcs,id',
            'daily_wage' => 'required|integer|min:1|max:100',
        ]);

        $user = $request->user();
        $npc = LocationNpc::findOrFail($request->npc_id);

        $result = $this->businessService->hireNpc($user, $business, $npc, $request->daily_wage);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Fire an employee.
     */
    public function fire(Request $request, PlayerBusiness $business, BusinessEmployee $employee): RedirectResponse
    {
        $user = $request->user();
        $result = $this->businessService->fireEmployee($user, $business, $employee);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Add stock to business inventory.
     */
    public function addStock(Request $request, PlayerBusiness $business): RedirectResponse
    {
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $result = $this->businessService->addStock($user, $business, $request->item_id, $request->quantity);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Remove stock from business inventory.
     */
    public function removeStock(Request $request, PlayerBusiness $business): RedirectResponse
    {
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $result = $this->businessService->removeStock($user, $business, $request->item_id, $request->quantity);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Get business status (for API/sidebar updates).
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $businesses = $this->businessService->getPlayerBusinesses($user);

        return response()->json([
            'businesses' => $businesses,
            'count' => $businesses->count(),
            'max' => BusinessService::MAX_BUSINESSES_PER_PLAYER,
        ]);
    }
}

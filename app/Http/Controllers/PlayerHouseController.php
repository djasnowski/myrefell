<?php

namespace App\Http\Controllers;

use App\Config\ConstructionConfig;
use App\Services\HouseBuffService;
use App\Services\HouseService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlayerHouseController extends Controller
{
    public function __construct(
        protected HouseService $houseService,
        protected InventoryService $inventoryService,
        protected HouseBuffService $houseBuffService
    ) {}

    /**
     * Show the player's house page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $user->load('skills');
        $house = $this->houseService->getHouse($user);

        $purchaseCheck = $house ? null : $this->houseService->canPurchaseHouse($user);

        $upgradeInfo = null;
        if ($house) {
            $tierOrder = array_keys(ConstructionConfig::HOUSE_TIERS);
            $currentIndex = array_search($house->tier, $tierOrder);
            $nextTier = $tierOrder[$currentIndex + 1] ?? null;

            if ($nextTier) {
                $upgradeInfo = [
                    'target_tier' => $nextTier,
                    'target_name' => ConstructionConfig::HOUSE_TIERS[$nextTier]['name'],
                    'target_config' => ConstructionConfig::HOUSE_TIERS[$nextTier],
                    'check' => $this->houseService->canUpgradeHouse($user, $nextTier),
                ];
            }
        }

        $houseBuffs = $house ? $this->houseBuffService->getHouseEffects($user) : [];

        // Adjacency bonus data
        $adjacencyBonuses = [];
        if ($house) {
            $house->load('rooms');
            $adjacencyBonuses = $this->houseBuffService->getAdjacencyBonuses($house);
        }

        // Portal data
        $portals = $house ? $this->houseService->getPortals($user) : [];
        $hasPortalChamber = $house && $house->rooms->where('room_type', 'portal_chamber')->isNotEmpty();
        $availableDestinations = $hasPortalChamber ? $this->houseService->getAvailableDestinations() : [];

        $data = [
            'house' => $house ? $this->formatHouse($house) : null,
            'canPurchase' => $purchaseCheck,
            'purchaseCost' => ConstructionConfig::HOUSE_TIERS['cottage']['cost'],
            'roomTypes' => $this->getAvailableRoomTypes($user),
            'constructionLevel' => $user->skills->where('skill_name', 'construction')->first()?->level ?? 1,
            'playerGold' => $user->gold,
            'upgradeInfo' => $upgradeInfo,
            'houseBuffs' => $houseBuffs,
            'adjacencyBonuses' => $adjacencyBonuses,
            'adjacencyDefinitions' => ConstructionConfig::ADJACENCY_BONUSES,
            'portals' => $portals,
            'availableDestinations' => $availableDestinations,
        ];

        return Inertia::render('House/Index', $data);
    }

    /**
     * Purchase a house.
     */
    public function purchase(Request $request): RedirectResponse
    {
        $result = $this->houseService->purchaseHouse($request->user());

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Build a room.
     */
    public function buildRoom(Request $request): RedirectResponse
    {
        $request->validate([
            'room_type' => 'required|string',
            'grid_x' => 'required|integer|min:0',
            'grid_y' => 'required|integer|min:0',
        ]);

        $user = $request->user();
        $user->load('skills');

        $result = $this->houseService->buildRoom(
            $user,
            $request->input('room_type'),
            $request->input('grid_x'),
            $request->input('grid_y'),
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Build furniture at a hotspot.
     */
    public function buildFurniture(Request $request): RedirectResponse
    {
        $request->validate([
            'room_id' => 'required|integer',
            'hotspot_slug' => 'required|string',
            'furniture_key' => 'required|string',
        ]);

        $user = $request->user();
        $user->load('skills');

        $result = $this->houseService->buildFurniture(
            $user,
            $request->input('room_id'),
            $request->input('hotspot_slug'),
            $request->input('furniture_key'),
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Demolish furniture.
     */
    public function demolishFurniture(Request $request): RedirectResponse
    {
        $request->validate([
            'room_id' => 'required|integer',
            'hotspot_slug' => 'required|string',
        ]);

        $result = $this->houseService->demolishFurniture(
            $request->user(),
            $request->input('room_id'),
            $request->input('hotspot_slug'),
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Upgrade the house to the next tier.
     */
    public function upgrade(Request $request): RedirectResponse
    {
        $request->validate([
            'target_tier' => 'required|string',
        ]);

        $user = $request->user();
        $user->load('skills');

        $result = $this->houseService->upgradeHouse(
            $user,
            $request->input('target_tier'),
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Deposit item into house storage.
     */
    public function deposit(Request $request): RedirectResponse
    {
        $request->validate([
            'item_name' => 'required|string',
            'quantity' => 'required|integer|min:1',
        ]);

        $result = $this->houseService->depositItem(
            $request->user(),
            $request->input('item_name'),
            $request->input('quantity'),
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Withdraw item from house storage.
     */
    public function withdraw(Request $request): RedirectResponse
    {
        $request->validate([
            'item_name' => 'required|string',
            'quantity' => 'required|integer|min:1',
        ]);

        $result = $this->houseService->withdrawItem(
            $request->user(),
            $request->input('item_name'),
            $request->input('quantity'),
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Set a portal destination.
     */
    public function setPortal(Request $request): RedirectResponse
    {
        $request->validate([
            'slot' => 'required|integer|min:1|max:3',
            'destination_type' => 'required|string|in:village,barony,town,kingdom',
            'destination_id' => 'required|integer',
        ]);

        $result = $this->houseService->setPortalDestination(
            $request->user(),
            $request->input('slot'),
            $request->input('destination_type'),
            $request->input('destination_id'),
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Teleport via a portal.
     */
    public function teleport(Request $request): RedirectResponse
    {
        $request->validate([
            'slot' => 'required|integer|min:1|max:3',
        ]);

        $result = $this->houseService->teleportFromPortal(
            $request->user(),
            $request->input('slot'),
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Format house data for the frontend.
     */
    protected function formatHouse($house): array
    {
        $tierConfig = ConstructionConfig::HOUSE_TIERS[$house->tier] ?? [];

        return [
            'id' => $house->id,
            'name' => $house->name,
            'tier' => $house->tier,
            'tier_name' => $tierConfig['name'] ?? ucfirst($house->tier),
            'condition' => $house->condition,
            'grid_size' => $house->getGridSize(),
            'max_rooms' => $house->getMaxRooms(),
            'storage_capacity' => $house->getStorageCapacity(),
            'storage_used' => $house->getStorageUsed(),
            'kingdom' => $house->kingdom ? [
                'id' => $house->kingdom->id,
                'name' => $house->kingdom->name,
            ] : null,
            'rooms' => $house->rooms->map(fn ($room) => [
                'id' => $room->id,
                'room_type' => $room->room_type,
                'room_name' => ConstructionConfig::ROOMS[$room->room_type]['name'] ?? ucfirst($room->room_type),
                'grid_x' => $room->grid_x,
                'grid_y' => $room->grid_y,
                'hotspots' => $this->getHotspotData($room),
            ])->toArray(),
            'storage' => $house->storage->map(fn ($s) => [
                'item_name' => $s->item->name,
                'item_type' => $s->item->type,
                'quantity' => $s->quantity,
            ])->toArray(),
        ];
    }

    /**
     * Get hotspot data for a room (with current furniture and available options).
     */
    protected function getHotspotData($room): array
    {
        $roomConfig = ConstructionConfig::ROOMS[$room->room_type] ?? null;
        if (! $roomConfig) {
            return [];
        }

        $hotspots = [];
        foreach ($roomConfig['hotspots'] as $slug => $hotspot) {
            $currentFurniture = $room->furniture->where('hotspot_slug', $slug)->first();

            $hotspots[$slug] = [
                'name' => $hotspot['name'],
                'current' => $currentFurniture ? [
                    'key' => $currentFurniture->furniture_key,
                    'name' => $hotspot['options'][$currentFurniture->furniture_key]['name'] ?? 'Unknown',
                ] : null,
                'options' => collect($hotspot['options'])->map(fn ($opt, $key) => [
                    'key' => $key,
                    'name' => $opt['name'],
                    'level' => $opt['level'],
                    'materials' => $opt['materials'],
                    'xp' => $opt['xp'],
                    'effect' => $opt['effect'] ?? null,
                ])->values()->toArray(),
            ];
        }

        return $hotspots;
    }

    /**
     * Get available room types the player can build.
     */
    protected function getAvailableRoomTypes($user): array
    {
        $level = $user->skills->where('skill_name', 'construction')->first()?->level ?? 1;
        $rooms = [];

        foreach (ConstructionConfig::ROOMS as $key => $config) {
            $rooms[] = [
                'key' => $key,
                'name' => $config['name'],
                'description' => $config['description'],
                'level' => $config['level'],
                'cost' => $config['cost'],
                'is_unlocked' => $level >= $config['level'],
                'hotspot_count' => count($config['hotspots']),
            ];
        }

        return $rooms;
    }
}

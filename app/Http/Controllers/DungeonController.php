<?php

namespace App\Http\Controllers;

use App\Models\Dungeon;
use App\Models\Kingdom;
use App\Services\CombatService;
use App\Services\DungeonLootService;
use App\Services\DungeonService;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DungeonController extends Controller
{
    public function __construct(
        protected DungeonService $dungeonService,
        protected DungeonLootService $dungeonLootService,
        protected InventoryService $inventoryService,
        protected CombatService $combatService
    ) {}

    /**
     * Legacy route - redirect to kingdom-scoped dungeons.
     */
    public function legacyIndex(Request $request): RedirectResponse|Response
    {
        $user = $request->user();
        $kingdom = $this->dungeonService->getPlayerKingdom($user);

        if (! $kingdom) {
            return Inertia::render('Dungeons/NotAvailable', [
                'message' => 'Dungeons are only available within kingdoms. Travel to a kingdom to access dungeons.',
            ]);
        }

        return redirect()->route('kingdoms.dungeons.index', $kingdom);
    }

    /**
     * Show the dungeon hub page.
     */
    public function index(Request $request, Kingdom $kingdom): Response
    {
        $user = $request->user();

        // Check if player is traveling or in infirmary
        if ($user->isTraveling()) {
            return Inertia::render('Dungeons/NotAvailable', [
                'message' => 'You cannot access dungeons while traveling.',
            ]);
        }

        if ($user->isInInfirmary()) {
            return Inertia::render('Dungeons/NotAvailable', [
                'message' => 'You cannot access dungeons while recovering in the infirmary.',
            ]);
        }

        // Check if player is in this kingdom
        $playerKingdom = $this->dungeonService->getPlayerKingdom($user);

        if (! $playerKingdom || $playerKingdom->id !== $kingdom->id) {
            return Inertia::render('Dungeons/NotAvailable', [
                'message' => "You must be in {$kingdom->name} to access its dungeons.",
            ]);
        }

        $dungeonInfo = $this->dungeonService->getDungeonInfo($user);

        // If in active dungeon session, show the dungeon exploration page
        if ($dungeonInfo['in_dungeon']) {
            return Inertia::render('Dungeons/Explore', [
                'kingdom' => $kingdom,
                'session' => $dungeonInfo['session'],
                'player_stats' => $dungeonInfo['player_stats'],
                'equipment' => $dungeonInfo['equipment'],
                'food' => $this->dungeonService->getAvailableFood($user),
            ]);
        }

        // Otherwise show dungeon selection
        $dungeons = $this->dungeonService->getAvailableDungeons($user);
        $lootCount = $this->dungeonLootService->getTotalLootCount($user);

        // Check for dungeon completion flash data
        $dungeonCompletion = session('dungeon_completion');

        $weaponSubtype = $this->combatService->getPlayerWeaponSubtype($user);
        $attackStyles = CombatService::WEAPON_ATTACK_STYLES[$weaponSubtype] ?? CombatService::WEAPON_ATTACK_STYLES['unarmed'];
        $weaponSpeed = $this->combatService->getWeaponSpeed($weaponSubtype);

        $equippedSlots = $this->getEquippedSlots($user);

        return Inertia::render('Dungeons/Index', [
            'dungeons' => $dungeons,
            'kingdom' => $kingdom,
            'player_stats' => $dungeonInfo['player_stats'],
            'equipment' => $dungeonInfo['equipment'],
            'equipped_slots' => $equippedSlots,
            'energy' => $dungeonInfo['energy'],
            'loot_count' => $lootCount,
            'dungeon_completion' => $dungeonCompletion,
            'weapon_subtype' => $weaponSubtype,
            'weapon_speed' => $weaponSpeed,
            'available_attack_styles' => $attackStyles,
        ]);
    }

    /**
     * Show a specific dungeon's details.
     */
    public function show(Request $request, Kingdom $kingdom, Dungeon $dungeon): Response
    {
        $user = $request->user();

        $dungeon->load(['floors', 'bossMonster']);

        return Inertia::render('Dungeons/Show', [
            'kingdom' => $kingdom,
            'dungeon' => $dungeon,
            'can_enter' => $dungeon->canBeEnteredBy($user),
            'player_stats' => [
                'combat_level' => $user->combat_level,
                'hp' => $user->hp,
                'max_hp' => $user->max_hp,
            ],
            'energy' => [
                'current' => $user->energy,
                'cost' => $dungeon->energy_cost,
            ],
        ]);
    }

    /**
     * Enter a dungeon.
     */
    public function enter(Request $request, Kingdom $kingdom): JsonResponse
    {
        $request->validate([
            'dungeon_id' => 'required|integer|exists:dungeons,id',
            'attack_style_index' => 'nullable|integer|min:0|max:3',
        ]);

        $user = $request->user();
        $result = $this->dungeonService->enterDungeon(
            $user,
            $request->input('dungeon_id'),
            $request->input('attack_style_index', 0)
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Fight the next monster.
     */
    public function fight(Request $request, Kingdom $kingdom): JsonResponse
    {
        $user = $request->user();
        $result = $this->dungeonService->fightMonster($user);

        // Flash completion data to session for the victory modal
        if ($result['success'] && ($result['data']['status'] ?? null) === 'completed') {
            session()->flash('dungeon_completion', [
                'dungeon_name' => $result['data']['session']['dungeon']['name'] ?? 'Unknown Dungeon',
                'total_rewards' => $result['data']['total_rewards'] ?? [],
                'loot_items' => $result['data']['loot_items'] ?? [],
            ]);
        }

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Proceed to the next floor.
     */
    public function nextFloor(Request $request, Kingdom $kingdom): JsonResponse
    {
        $user = $request->user();
        $result = $this->dungeonService->nextFloor($user);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Eat food during dungeon exploration.
     */
    public function eat(Request $request, Kingdom $kingdom): JsonResponse
    {
        $request->validate([
            'inventory_slot_id' => 'required|integer',
        ]);

        $user = $request->user();
        $result = $this->dungeonService->eatFood($user, $request->input('inventory_slot_id'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Abandon the dungeon.
     */
    public function abandon(Request $request, Kingdom $kingdom): JsonResponse
    {
        $user = $request->user();
        $result = $this->dungeonService->abandonDungeon($user);

        return response()->json($result);
    }

    /**
     * Get current dungeon status.
     */
    public function status(Request $request, Kingdom $kingdom): JsonResponse
    {
        $user = $request->user();
        $dungeonInfo = $this->dungeonService->getDungeonInfo($user);

        return response()->json([
            'success' => true,
            'data' => $dungeonInfo,
        ]);
    }

    /**
     * Show the loot storage page.
     */
    public function lootStorage(Request $request, Kingdom $kingdom): Response
    {
        $user = $request->user();
        $loot = $this->dungeonLootService->getPlayerLoot($user, $kingdom);

        // Group loot by kingdom (in case we want to show other kingdoms too)
        $lootByKingdom = $loot->groupBy('kingdom_id')->map(function ($items, $kingdomId) {
            return [
                'kingdom' => $items->first()->kingdom,
                'items' => $items->map(function ($storage) {
                    return [
                        'id' => $storage->id,
                        'item' => $storage->item,
                        'quantity' => $storage->quantity,
                        'expires_at' => $storage->expires_at->toISOString(),
                        'days_until_expiry' => $storage->daysUntilExpiry(),
                    ];
                })->values(),
            ];
        })->values();

        return Inertia::render('Dungeons/LootStorage', [
            'kingdom' => $kingdom,
            'loot_by_kingdom' => $lootByKingdom,
            'total_items' => $loot->sum('quantity'),
            'inventory_free_slots' => $this->inventoryService->freeSlots($user),
        ]);
    }

    /**
     * Claim loot from storage.
     */
    public function claimLoot(Request $request, Kingdom $kingdom): JsonResponse
    {
        $request->validate([
            'storage_id' => 'required|integer',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $result = $this->dungeonLootService->claimLoot(
            $request->user(),
            $request->input('storage_id'),
            $request->input('quantity')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Claim all loot from a specific kingdom.
     */
    public function claimAllLoot(Request $request, Kingdom $kingdom): JsonResponse
    {
        $result = $this->dungeonLootService->claimAllLoot(
            $request->user(),
            $kingdom->id
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get equipped items organized by slot type for display.
     */
    protected function getEquippedSlots(\App\Models\User $user): array
    {
        $inventory = $user->inventory()->where('is_equipped', true)->with('item')->get();
        $slots = [];

        foreach (['head', 'amulet', 'chest', 'legs', 'weapon', 'shield', 'ring', 'necklace', 'bracelet'] as $slotType) {
            $equipped = $inventory->first(fn ($inv) => $inv->item->equipment_slot === $slotType);
            $slots[$slotType] = $equipped ? [
                'item' => [
                    'id' => $equipped->item->id,
                    'name' => $equipped->item->name,
                    'type' => $equipped->item->type,
                    'subtype' => $equipped->item->subtype,
                    'rarity' => $equipped->item->rarity,
                    'atk_bonus' => $equipped->item->atk_bonus,
                    'str_bonus' => $equipped->item->str_bonus,
                    'def_bonus' => $equipped->item->def_bonus,
                    'hp_bonus' => $equipped->item->hp_bonus,
                    'energy_bonus' => $equipped->item->energy_bonus,
                ],
            ] : null;
        }

        return $slots;
    }
}

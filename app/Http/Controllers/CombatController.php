<?php

namespace App\Http\Controllers;

use App\Services\CombatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CombatController extends Controller
{
    public function __construct(
        protected CombatService $combatService
    ) {}

    /**
     * Show the combat page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Check if player is traveling or in infirmary
        if ($user->isTraveling()) {
            return Inertia::render('Combat/NotAvailable', [
                'message' => 'You cannot access combat while traveling.',
            ]);
        }

        if ($user->isInInfirmary()) {
            return Inertia::render('Combat/NotAvailable', [
                'message' => 'You cannot access combat while recovering in the infirmary.',
            ]);
        }

        $combatInfo = $this->combatService->getCombatInfo($user);
        $equippedSlots = $this->getEquippedSlots($user);

        // If in active combat, show the combat arena
        if ($combatInfo['in_combat']) {
            return Inertia::render('Combat/Arena', [
                'session' => $combatInfo['session'],
                'player_stats' => $combatInfo['player_stats'],
                'equipment' => $combatInfo['equipment'],
                'equipped_slots' => $equippedSlots,
                'food' => $this->combatService->getAvailableFood($user),
                'weapon_subtype' => $combatInfo['weapon_subtype'],
                'available_attack_styles' => $combatInfo['available_attack_styles'],
            ]);
        }

        // Otherwise show monster selection
        $monsters = $this->combatService->getAvailableMonsters($user);

        return Inertia::render('Combat/Index', [
            'monsters' => $monsters,
            'player_stats' => $combatInfo['player_stats'],
            'equipment' => $combatInfo['equipment'],
            'equipped_slots' => $equippedSlots,
            'energy' => $combatInfo['energy'],
            'weapon_subtype' => $combatInfo['weapon_subtype'],
            'weapon_speed' => $combatInfo['weapon_speed'],
            'available_attack_styles' => $combatInfo['available_attack_styles'],
        ]);
    }

    /**
     * Get equipped items organized by slot type for display.
     */
    protected function getEquippedSlots(\App\Models\User $user): array
    {
        $inventory = $user->inventory()->where('is_equipped', true)->with('item')->get();
        $slots = [];

        foreach (['head', 'amulet', 'chest', 'legs', 'weapon', 'shield', 'ring'] as $slotType) {
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

    /**
     * Start a combat session.
     */
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'monster_id' => 'required|integer|exists:monsters,id',
            'attack_style_index' => 'nullable|integer|min:0|max:3',
        ]);

        $user = $request->user();
        $result = $this->combatService->startCombat(
            $user,
            $request->input('monster_id'),
            $request->input('attack_style_index', 0)
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Perform an attack action.
     */
    public function attack(Request $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->combatService->attack($user);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Eat food during combat.
     */
    public function eat(Request $request): JsonResponse
    {
        $request->validate([
            'inventory_slot_id' => 'required|integer',
        ]);

        $user = $request->user();
        $result = $this->combatService->eat($user, $request->input('inventory_slot_id'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Attempt to flee from combat.
     */
    public function flee(Request $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->combatService->flee($user);

        // For flee, success means escaped; failure means still in combat
        return response()->json($result);
    }

    /**
     * Get current combat status.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $combatInfo = $this->combatService->getCombatInfo($user);

        return response()->json([
            'success' => true,
            'data' => $combatInfo,
        ]);
    }
}

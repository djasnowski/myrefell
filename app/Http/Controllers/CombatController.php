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

        // Check if player is traveling
        if ($user->isTraveling()) {
            return Inertia::render('Combat/NotAvailable', [
                'message' => 'You cannot access combat while traveling.',
            ]);
        }

        $combatInfo = $this->combatService->getCombatInfo($user);

        // If in active combat, show the combat arena
        if ($combatInfo['in_combat']) {
            return Inertia::render('Combat/Arena', [
                'session' => $combatInfo['session'],
                'player_stats' => $combatInfo['player_stats'],
                'equipment' => $combatInfo['equipment'],
                'food' => $this->combatService->getAvailableFood($user),
            ]);
        }

        // Otherwise show monster selection
        $monsters = $this->combatService->getAvailableMonsters($user);

        return Inertia::render('Combat/Index', [
            'monsters' => $monsters,
            'player_stats' => $combatInfo['player_stats'],
            'equipment' => $combatInfo['equipment'],
            'energy' => $combatInfo['energy'],
        ]);
    }

    /**
     * Start a combat session.
     */
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'monster_id' => 'required|integer|exists:monsters,id',
            'training_style' => 'nullable|string|in:attack,strength,defense',
        ]);

        $user = $request->user();
        $result = $this->combatService->startCombat(
            $user,
            $request->input('monster_id'),
            $request->input('training_style', 'attack')
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

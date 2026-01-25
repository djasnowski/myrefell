<?php

namespace App\Http\Controllers;

use App\Models\Dungeon;
use App\Services\DungeonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DungeonController extends Controller
{
    public function __construct(
        protected DungeonService $dungeonService
    ) {}

    /**
     * Show the dungeon hub page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Check if player is traveling
        if ($user->isTraveling()) {
            return Inertia::render('Dungeons/NotAvailable', [
                'message' => 'You cannot access dungeons while traveling.',
            ]);
        }

        $dungeonInfo = $this->dungeonService->getDungeonInfo($user);

        // If in active dungeon session, show the dungeon exploration page
        if ($dungeonInfo['in_dungeon']) {
            return Inertia::render('Dungeons/Explore', [
                'session' => $dungeonInfo['session'],
                'player_stats' => $dungeonInfo['player_stats'],
                'equipment' => $dungeonInfo['equipment'],
                'food' => $this->dungeonService->getAvailableFood($user),
            ]);
        }

        // Otherwise show dungeon selection
        $dungeons = $this->dungeonService->getAvailableDungeons($user);

        return Inertia::render('Dungeons/Index', [
            'dungeons' => $dungeons,
            'player_stats' => $dungeonInfo['player_stats'],
            'equipment' => $dungeonInfo['equipment'],
            'energy' => $dungeonInfo['energy'],
        ]);
    }

    /**
     * Show a specific dungeon's details.
     */
    public function show(Request $request, Dungeon $dungeon): Response
    {
        $user = $request->user();

        $dungeon->load(['floors', 'bossMonster']);

        return Inertia::render('Dungeons/Show', [
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
    public function enter(Request $request): JsonResponse
    {
        $request->validate([
            'dungeon_id' => 'required|integer|exists:dungeons,id',
            'training_style' => 'nullable|string|in:attack,strength,defense',
        ]);

        $user = $request->user();
        $result = $this->dungeonService->enterDungeon(
            $user,
            $request->input('dungeon_id'),
            $request->input('training_style', 'attack')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Fight the next monster.
     */
    public function fight(Request $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->dungeonService->fightMonster($user);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Proceed to the next floor.
     */
    public function nextFloor(Request $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->dungeonService->nextFloor($user);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Eat food during dungeon exploration.
     */
    public function eat(Request $request): JsonResponse
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
    public function abandon(Request $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->dungeonService->abandonDungeon($user);

        return response()->json($result);
    }

    /**
     * Get current dungeon status.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $dungeonInfo = $this->dungeonService->getDungeonInfo($user);

        return response()->json([
            'success' => true,
            'data' => $dungeonInfo,
        ]);
    }
}

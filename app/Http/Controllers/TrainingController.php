<?php

namespace App\Http\Controllers;

use App\Services\TrainingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainingController extends Controller
{
    public function __construct(
        protected TrainingService $trainingService
    ) {}

    /**
     * Show the training grounds.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if (!$this->trainingService->canTrain($user)) {
            return Inertia::render('Training/NotAvailable', [
                'message' => 'There are no training grounds at your current location. Travel to a village, town, or barony to train.',
            ]);
        }

        $exercises = $this->trainingService->getAvailableExercises($user);
        $combatStats = $this->trainingService->getCombatStats($user);

        return Inertia::render('Training/Index', [
            'exercises' => $exercises,
            'combat_stats' => $combatStats,
            'player_energy' => $user->energy,
            'max_energy' => $user->max_energy,
        ]);
    }

    /**
     * Perform a training exercise.
     */
    public function train(Request $request): JsonResponse
    {
        $request->validate([
            'exercise' => 'required|string|in:attack,strength,defense',
        ]);

        $user = $request->user();
        $result = $this->trainingService->train($user, $request->input('exercise'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get current training status.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'can_train' => $this->trainingService->canTrain($user),
            'combat_stats' => $this->trainingService->getCombatStats($user),
            'player_energy' => $user->energy,
            'max_energy' => $user->max_energy,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\PlayerQuest;
use App\Models\Quest;
use App\Services\QuestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestController extends Controller
{
    public function __construct(
        protected QuestService $questService
    ) {}

    /**
     * Show the notice board (available quests).
     */
    public function noticeBoard(Request $request, int $villageId): Response
    {
        $user = $request->user();

        // Check if player is at this village
        if ($user->current_location_type !== 'village' || $user->current_location_id !== $villageId) {
            return Inertia::render('Quests/NotHere', [
                'message' => 'You must be at this village to view the notice board.',
            ]);
        }

        $availableQuests = $this->questService->getAvailableQuests($user);
        $activeQuests = $this->questService->getActiveQuests($user);
        $completedQuests = $this->questService->getCompletedQuests($user);

        return Inertia::render('Quests/NoticeBoard', [
            'available_quests' => $availableQuests,
            'active_quests' => $activeQuests,
            'completed_quests' => $completedQuests,
            'max_active_quests' => QuestService::MAX_ACTIVE_QUESTS,
            'village_id' => $villageId,
        ]);
    }

    /**
     * Show the player's quest log.
     */
    public function questLog(Request $request): Response
    {
        $user = $request->user();

        $activeQuests = $this->questService->getActiveQuests($user);
        $completedQuests = $this->questService->getCompletedQuests($user);

        return Inertia::render('Quests/QuestLog', [
            'active_quests' => $activeQuests,
            'completed_quests' => $completedQuests,
            'max_active_quests' => QuestService::MAX_ACTIVE_QUESTS,
        ]);
    }

    /**
     * Accept a quest.
     */
    public function accept(Request $request): JsonResponse
    {
        $request->validate([
            'quest_id' => 'required|exists:quests,id',
        ]);

        $user = $request->user();
        $quest = Quest::findOrFail($request->input('quest_id'));
        $result = $this->questService->acceptQuest($user, $quest);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Abandon a quest.
     */
    public function abandon(Request $request, PlayerQuest $playerQuest): JsonResponse
    {
        $user = $request->user();
        $result = $this->questService->abandonQuest($user, $playerQuest);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Claim quest rewards.
     */
    public function claim(Request $request, PlayerQuest $playerQuest): JsonResponse
    {
        $user = $request->user();
        $result = $this->questService->claimReward($user, $playerQuest);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\PlayerSkill;
use Illuminate\Http\JsonResponse;

class LeaderboardController extends Controller
{
    /**
     * Get leaderboard data for all skills.
     */
    public function index(): JsonResponse
    {
        $leaderboards = [];

        foreach (PlayerSkill::SKILLS as $skillName) {
            $topPlayers = PlayerSkill::where('skill_name', $skillName)
                ->with('player:id,username')
                ->orderByDesc('xp')
                ->limit(15)
                ->get()
                ->map(function ($skill, $index) {
                    return [
                        'rank' => $index + 1,
                        'username' => $skill->player->username ?? 'Unknown',
                        'level' => $skill->level,
                        'xp' => $skill->xp,
                    ];
                });

            $leaderboards[$skillName] = $topPlayers;
        }

        return response()->json([
            'leaderboards' => $leaderboards,
            'skills' => PlayerSkill::SKILLS,
        ]);
    }
}

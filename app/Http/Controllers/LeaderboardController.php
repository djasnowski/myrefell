<?php

namespace App\Http\Controllers;

use App\Models\PlayerSkill;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    /**
     * Display the leaderboard page.
     */
    public function index(): Response
    {
        $leaderboards = [];

        foreach (PlayerSkill::SKILLS as $skillName) {
            $topPlayers = PlayerSkill::where('skill_name', $skillName)
                ->where('xp', '>=', 10)
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

        return Inertia::render('Leaderboard/Index', [
            'leaderboards' => $leaderboards,
            'skills' => PlayerSkill::SKILLS,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\PlayerSkill;
use Illuminate\Support\Facades\DB;
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

        // Calculate total level leaderboard
        $totalLeaderboard = PlayerSkill::query()
            ->select('player_id', DB::raw('SUM(level) as total_level'), DB::raw('SUM(xp) as total_xp'))
            ->groupBy('player_id')
            ->having(DB::raw('SUM(xp)'), '>', 0) // Has actually gained XP
            ->orderByDesc('total_level')
            ->orderByDesc('total_xp')
            ->limit(15)
            ->with('player:id,username')
            ->get()
            ->map(function ($skill, $index) {
                return [
                    'rank' => $index + 1,
                    'username' => $skill->player->username ?? 'Unknown',
                    'level' => (int) $skill->total_level,
                    'xp' => (int) $skill->total_xp,
                ];
            });

        $leaderboards['total'] = $totalLeaderboard;

        return Inertia::render('Leaderboard/Index', [
            'leaderboards' => $leaderboards,
            'skills' => array_merge(['total'], PlayerSkill::SKILLS),
        ]);
    }
}

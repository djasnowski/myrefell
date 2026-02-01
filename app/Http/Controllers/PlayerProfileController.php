<?php

namespace App\Http\Controllers;

use App\Models\PlayerSkill;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PlayerProfileController extends Controller
{
    /**
     * Display a player's public profile.
     */
    public function show(string $username): Response|HttpResponse
    {
        $player = User::whereRaw('LOWER(username) = ?', [strtolower($username)])
            ->first();

        if (! $player) {
            abort(404);
        }

        $player->load('skills');

        $skills = collect(PlayerSkill::SKILLS)->map(function ($skillName) use ($player) {
            $skill = $player->skills->firstWhere('skill_name', $skillName);

            $defaultLevel = in_array($skillName, PlayerSkill::COMBAT_SKILLS) ? 5 : 1;
            $level = $skill?->level ?? $defaultLevel;
            $xp = $skill?->xp ?? 0;

            $xpProgress = 0;
            if ($skill) {
                $xpProgress = round($skill->getXpProgress(), 1);
            }

            $rank = null;
            if ($xp >= 10) {
                $rank = PlayerSkill::where('skill_name', $skillName)
                    ->where('xp', '>=', 10)
                    ->where('xp', '>', $xp)
                    ->count() + 1;
            }

            return [
                'name' => $skillName,
                'level' => $level,
                'xp' => $xp,
                'xp_progress' => $xpProgress,
                'rank' => $rank,
            ];
        });

        $totalXp = $player->skills->sum('xp');
        $totalLevel = $skills->sum('level');

        $totalRank = null;
        if ($totalXp > 0) {
            $totalRank = PlayerSkill::query()
                ->select('player_id', DB::raw('SUM(xp) as total_xp'))
                ->groupBy('player_id')
                ->having(DB::raw('SUM(xp)'), '>', $totalXp)
                ->get()
                ->count() + 1;
        }

        return Inertia::render('Players/Show', [
            'player' => [
                'username' => $player->username,
                'combat_level' => $player->combat_level,
                'total_level' => $totalLevel,
                'total_xp' => $totalXp,
                'total_rank' => $totalRank,
            ],
            'skills' => $skills,
        ]);
    }
}

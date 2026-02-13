<?php

namespace App\Http\Controllers;

use App\Config\ConstructionConfig;
use App\Models\PlayerHouse;
use App\Models\PlayerSkill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    private const PER_PAGE = 25;

    private const MAX_ENTRIES = 100;

    /**
     * Display the leaderboard page.
     */
    public function index(Request $request): Response
    {
        $tab = $request->input('tab', 'skills');
        if (! in_array($tab, ['skills', 'houses'])) {
            $tab = 'skills';
        }

        $page = max(1, (int) $request->input('page', 1));

        if ($tab === 'houses') {
            return Inertia::render('Leaderboard/Index', [
                'tab' => 'houses',
                'leaderboard' => $this->getHouseLeaderboard($page),
                'selectedSkill' => 'total',
                'skills' => array_merge(['total'], PlayerSkill::SKILLS),
            ]);
        }

        $selectedSkill = $request->input('skill', 'total');
        $validSkills = array_merge(['total'], PlayerSkill::SKILLS);

        if (! in_array($selectedSkill, $validSkills)) {
            $selectedSkill = 'total';
        }

        if ($selectedSkill === 'total') {
            $leaderboard = $this->getTotalLeaderboard($page);
        } else {
            $leaderboard = $this->getSkillLeaderboard($selectedSkill, $page);
        }

        return Inertia::render('Leaderboard/Index', [
            'tab' => 'skills',
            'leaderboard' => $leaderboard,
            'selectedSkill' => $selectedSkill,
            'skills' => $validSkills,
        ]);
    }

    /**
     * Get leaderboard for a specific skill.
     */
    private function getSkillLeaderboard(string $skillName, int $page): array
    {
        $isCombat = in_array($skillName, PlayerSkill::COMBAT_SKILLS);

        $query = PlayerSkill::where('skill_name', $skillName)
            ->whereHas('player', fn ($q) => $q->whereNull('banned_at'))
            ->with('player:id,username');

        // Combat skills: level 6+ (default is 5)
        // Non-combat skills: 250+ XP
        if ($isCombat) {
            $query->where('level', '>=', 6);
        } else {
            $query->where('xp', '>=', 250);
        }

        $query->orderByDesc('xp');

        // Cap at MAX_ENTRIES
        $totalRaw = (clone $query)->count();
        $total = min($totalRaw, self::MAX_ENTRIES);
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $lastPage);

        $offset = ($page - 1) * self::PER_PAGE;
        $entries = $query->skip($offset)->take(self::PER_PAGE)->get();

        return [
            'entries' => $entries->map(function ($skill, $index) use ($offset) {
                return [
                    'rank' => $offset + $index + 1,
                    'username' => $skill->player->username ?? 'Unknown',
                    'level' => $skill->level,
                    'xp' => $skill->xp,
                ];
            }),
            'current_page' => $page,
            'last_page' => $lastPage,
            'total' => $total,
        ];
    }

    /**
     * Get total level leaderboard.
     */
    private function getTotalLeaderboard(int $page): array
    {
        $query = PlayerSkill::query()
            ->select('player_id', DB::raw('SUM(level) as total_level'), DB::raw('SUM(xp) as total_xp'))
            ->whereHas('player', fn ($q) => $q->whereNull('banned_at'))
            ->groupBy('player_id')
            ->having(DB::raw('SUM(xp)'), '>=', 250)
            ->orderByDesc('total_level')
            ->orderByDesc('total_xp')
            ->with('player:id,username');

        // Manual pagination for grouped query, capped at MAX_ENTRIES
        $totalRaw = (clone $query)->get()->count();
        $total = min($totalRaw, self::MAX_ENTRIES);
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $lastPage);

        $offset = ($page - 1) * self::PER_PAGE;
        $entries = $query->skip($offset)->take(self::PER_PAGE)->get();

        return [
            'entries' => $entries->map(function ($skill, $index) use ($offset) {
                return [
                    'rank' => $offset + $index + 1,
                    'username' => $skill->player->username ?? 'Unknown',
                    'level' => (int) $skill->total_level,
                    'xp' => (int) $skill->total_xp,
                ];
            }),
            'current_page' => $page,
            'last_page' => $lastPage,
            'total' => $total,
        ];
    }

    /**
     * Get house leaderboard ranked by house value.
     */
    private function getHouseLeaderboard(int $page): array
    {
        $houses = PlayerHouse::with(['rooms.furniture', 'player:id,username'])
            ->whereHas('player', fn ($q) => $q->whereNull('banned_at'))
            ->get();

        // Compute value for each house
        $ranked = $houses->map(function ($house) {
            $tierCost = ConstructionConfig::HOUSE_TIERS[$house->tier]['cost'] ?? 0;

            $roomCost = 0;
            $furnitureXp = 0;
            foreach ($house->rooms as $room) {
                $roomConfig = ConstructionConfig::ROOMS[$room->room_type] ?? null;
                if ($roomConfig) {
                    $roomCost += $roomConfig['cost'] ?? 0;
                }

                foreach ($room->furniture as $furniture) {
                    if ($roomConfig) {
                        $hotspot = $roomConfig['hotspots'][$furniture->hotspot_slug] ?? null;
                        $furnitureConfig = $hotspot['options'][$furniture->furniture_key] ?? null;
                        if ($furnitureConfig) {
                            $furnitureXp += $furnitureConfig['xp'] ?? 0;
                        }
                    }
                }
            }

            $value = $tierCost + $roomCost + $furnitureXp;

            return [
                'username' => $house->player->username ?? 'Unknown',
                'tier_name' => ConstructionConfig::HOUSE_TIERS[$house->tier]['name'] ?? ucfirst($house->tier),
                'room_count' => $house->rooms->count(),
                'house_value' => $value,
            ];
        })
            ->sortByDesc('house_value')
            ->values()
            ->take(self::MAX_ENTRIES);

        $total = $ranked->count();
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $lastPage);
        $offset = ($page - 1) * self::PER_PAGE;

        $entries = $ranked->slice($offset, self::PER_PAGE)->values()->map(function ($entry, $index) use ($offset) {
            return array_merge($entry, ['rank' => $offset + $index + 1]);
        });

        return [
            'entries' => $entries,
            'current_page' => $page,
            'last_page' => $lastPage,
            'total' => $total,
        ];
    }
}

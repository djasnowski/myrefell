<?php

namespace App\Http\Controllers;

use App\Models\PlayerSkill;
use Inertia\Inertia;
use Inertia\Response;

class SkillsController extends Controller
{
    /**
     * Display the player's skills page.
     */
    public function index(): Response
    {
        $player = auth()->user();
        $skills = $player->skills;

        // Group skills by category
        $combatSkills = $skills->filter(fn ($skill) => in_array($skill->skill_name, PlayerSkill::COMBAT_SKILLS))
            ->map(fn ($skill) => $this->formatSkill($skill))
            ->values();

        $gatheringSkills = $skills->filter(fn ($skill) => in_array($skill->skill_name, ['farming', 'mining', 'fishing', 'woodcutting']))
            ->map(fn ($skill) => $this->formatSkill($skill))
            ->values();

        $craftingSkills = $skills->filter(fn ($skill) => in_array($skill->skill_name, ['cooking', 'smithing', 'crafting']))
            ->map(fn ($skill) => $this->formatSkill($skill))
            ->values();

        $supportSkills = $skills->filter(fn ($skill) => in_array($skill->skill_name, ['thieving']))
            ->map(fn ($skill) => $this->formatSkill($skill))
            ->values();

        // Calculate totals
        $totalLevel = $skills->sum('level');
        $totalXp = $skills->sum('xp');
        $combatLevel = $player->combat_level;

        return Inertia::render('Skills/Index', [
            'skills' => [
                'combat' => $combatSkills,
                'gathering' => $gatheringSkills,
                'crafting' => $craftingSkills,
                'support' => $supportSkills,
            ],
            'stats' => [
                'total_level' => $totalLevel,
                'total_xp' => $totalXp,
                'combat_level' => $combatLevel,
                'max_total_level' => count(PlayerSkill::SKILLS) * PlayerSkill::MAX_LEVEL,
            ],
        ]);
    }

    /**
     * Format a skill for the frontend.
     */
    private function formatSkill(PlayerSkill $skill): array
    {
        return [
            'name' => $skill->skill_name,
            'level' => $skill->level,
            'xp' => $skill->xp,
            'xp_to_next' => $skill->xpToNextLevel(),
            'xp_progress' => round($skill->getXpProgress(), 1),
            'xp_for_current_level' => PlayerSkill::xpForLevel($skill->level),
            'xp_for_next_level' => PlayerSkill::xpForLevel($skill->level + 1),
            'is_max' => $skill->level >= PlayerSkill::MAX_LEVEL,
        ];
    }
}

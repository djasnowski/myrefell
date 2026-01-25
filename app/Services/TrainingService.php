<?php

namespace App\Services;

use App\Models\PlayerSkill;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TrainingService
{
    /**
     * Training exercises configuration.
     * Based on PRD: Visit training grounds daily (costs energy), each session grants stat XP.
     */
    public const EXERCISES = [
        'attack' => [
            'name' => 'Combat Drills',
            'description' => 'Practice sword techniques and fighting stances to improve your attack power.',
            'skill' => 'attack',
            'energy_cost' => 10,
            'base_xp' => 25,
            'location_types' => ['village', 'town', 'barony'],
        ],
        'strength' => [
            'name' => 'Heavy Labor',
            'description' => 'Lift weights and perform manual labor to build raw strength.',
            'skill' => 'strength',
            'energy_cost' => 10,
            'base_xp' => 25,
            'location_types' => ['village', 'town', 'barony'],
        ],
        'defense' => [
            'name' => 'Sparring Practice',
            'description' => 'Train with shields and learn to block attacks to improve your defense.',
            'skill' => 'defense',
            'energy_cost' => 10,
            'base_xp' => 25,
            'location_types' => ['village', 'town', 'barony'],
        ],
    ];

    public function __construct(
        protected DailyTaskService $dailyTaskService
    ) {}

    /**
     * Check if user can train at their current location.
     */
    public function canTrain(User $user): bool
    {
        if ($user->isTraveling()) {
            return false;
        }

        // Training grounds are available in villages, towns, and baronies
        return in_array($user->current_location_type, ['village', 'town', 'barony']);
    }

    /**
     * Get available training exercises for the user.
     */
    public function getAvailableExercises(User $user): array
    {
        if (!$this->canTrain($user)) {
            return [];
        }

        $exercises = [];

        foreach (self::EXERCISES as $key => $config) {
            $skill = $user->skills()->where('skill_name', $config['skill'])->first();
            $level = $skill?->level ?? 5; // Combat skills start at level 5
            $xp = $skill?->xp ?? 0;
            $progress = $skill ? $skill->getXpProgress() : 0;
            $xpToNext = $skill ? $skill->xpToNextLevel() : PlayerSkill::xpForLevel(6) - PlayerSkill::xpForLevel(5);

            $exercises[] = [
                'id' => $key,
                'name' => $config['name'],
                'description' => $config['description'],
                'skill' => $config['skill'],
                'skill_level' => $level,
                'skill_xp' => $xp,
                'skill_progress' => $progress,
                'xp_to_next_level' => $xpToNext,
                'energy_cost' => $config['energy_cost'],
                'base_xp' => $config['base_xp'],
                'can_train' => $user->hasEnergy($config['energy_cost']),
            ];
        }

        return $exercises;
    }

    /**
     * Perform a training exercise.
     */
    public function train(User $user, string $exercise): array
    {
        $config = self::EXERCISES[$exercise] ?? null;

        if (!$config) {
            return [
                'success' => false,
                'message' => 'Invalid exercise.',
            ];
        }

        if (!$this->canTrain($user)) {
            return [
                'success' => false,
                'message' => 'You cannot train here. Find a training ground in a village, town, or barony.',
            ];
        }

        if (!$user->hasEnergy($config['energy_cost'])) {
            return [
                'success' => false,
                'message' => "Not enough energy. Need {$config['energy_cost']} energy.",
            ];
        }

        return DB::transaction(function () use ($user, $config, $exercise) {
            // Consume energy
            $user->consumeEnergy($config['energy_cost']);

            // Get or create the skill
            $skill = $user->skills()->where('skill_name', $config['skill'])->first();

            if (!$skill) {
                $skill = $user->skills()->create([
                    'skill_name' => $config['skill'],
                    'level' => 5, // Combat skills start at level 5
                    'xp' => PlayerSkill::xpForLevel(5),
                ]);
            }

            // Calculate XP with diminishing returns at higher levels
            // Higher levels grant slightly less XP per training session
            $levelModifier = max(0.5, 1 - ($skill->level - 5) * 0.01);
            $xpAwarded = (int) round($config['base_xp'] * $levelModifier);

            $oldLevel = $skill->level;
            $skill->addXp($xpAwarded);
            $newLevel = $skill->fresh()->level;
            $leveledUp = $newLevel > $oldLevel;

            // Record daily task progress for training
            $this->dailyTaskService->recordProgress($user, 'train', $config['skill'], 1);

            return [
                'success' => true,
                'message' => "You completed {$config['name']}!",
                'exercise' => $exercise,
                'xp_awarded' => $xpAwarded,
                'skill' => $config['skill'],
                'new_level' => $newLevel,
                'leveled_up' => $leveledUp,
                'energy_remaining' => $user->fresh()->energy,
                'skill_progress' => $skill->fresh()->getXpProgress(),
                'xp_to_next_level' => $skill->fresh()->xpToNextLevel(),
            ];
        });
    }

    /**
     * Get training info for a specific exercise.
     */
    public function getExerciseInfo(User $user, string $exercise): ?array
    {
        $config = self::EXERCISES[$exercise] ?? null;
        if (!$config) {
            return null;
        }

        $skill = $user->skills()->where('skill_name', $config['skill'])->first();
        $level = $skill?->level ?? 5;
        $xp = $skill?->xp ?? 0;
        $progress = $skill ? $skill->getXpProgress() : 0;
        $xpToNext = $skill ? $skill->xpToNextLevel() : PlayerSkill::xpForLevel(6) - PlayerSkill::xpForLevel(5);

        return [
            'id' => $exercise,
            'name' => $config['name'],
            'description' => $config['description'],
            'skill' => $config['skill'],
            'skill_level' => $level,
            'skill_xp' => $xp,
            'skill_progress' => $progress,
            'xp_to_next_level' => $xpToNext,
            'energy_cost' => $config['energy_cost'],
            'base_xp' => $config['base_xp'],
            'player_energy' => $user->energy,
            'can_train' => $this->canTrain($user) && $user->hasEnergy($config['energy_cost']),
        ];
    }

    /**
     * Get the player's combat level (average of ATK, STR, DEF).
     */
    public function getCombatLevel(User $user): int
    {
        $attack = $user->getSkillLevel('attack');
        $strength = $user->getSkillLevel('strength');
        $defense = $user->getSkillLevel('defense');

        return (int) floor(($attack + $strength + $defense) / 3);
    }

    /**
     * Get a summary of all combat stats for the user.
     */
    public function getCombatStats(User $user): array
    {
        $stats = [];

        foreach (self::EXERCISES as $key => $config) {
            $skill = $user->skills()->where('skill_name', $config['skill'])->first();
            $level = $skill?->level ?? 5;
            $xp = $skill?->xp ?? 0;
            $progress = $skill ? $skill->getXpProgress() : 0;
            $xpToNext = $skill ? $skill->xpToNextLevel() : PlayerSkill::xpForLevel(6) - PlayerSkill::xpForLevel(5);

            $stats[$config['skill']] = [
                'level' => $level,
                'xp' => $xp,
                'progress' => $progress,
                'xp_to_next_level' => $xpToNext,
            ];
        }

        $stats['combat_level'] = $this->getCombatLevel($user);

        return $stats;
    }
}

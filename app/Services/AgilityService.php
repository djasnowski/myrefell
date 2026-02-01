<?php

namespace App\Services;

use App\Models\LocationActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AgilityService
{
    /**
     * Agility obstacles configuration.
     * Higher level obstacles give more XP but cost more energy.
     */
    public const OBSTACLES = [
        // Level 1 obstacles
        'log_balance' => [
            'name' => 'Log Balance',
            'description' => 'Walk across a fallen log without falling',
            'min_level' => 1,
            'energy_cost' => 2,
            'base_xp' => 8,
            'base_success_rate' => 95,
            'location_types' => ['village', 'town', 'barony', 'duchy'],
        ],
        'rope_swing' => [
            'name' => 'Rope Swing',
            'description' => 'Swing across a small gap using a rope',
            'min_level' => 1,
            'energy_cost' => 2,
            'base_xp' => 10,
            'base_success_rate' => 92,
            'location_types' => ['village', 'town', 'barony', 'duchy'],
        ],
        // Level 5 obstacles
        'hurdles' => [
            'name' => 'Hurdles',
            'description' => 'Jump over a series of low hurdles',
            'min_level' => 5,
            'energy_cost' => 3,
            'base_xp' => 15,
            'base_success_rate' => 90,
            'location_types' => ['village', 'town', 'barony', 'duchy'],
        ],
        'stepping_stones' => [
            'name' => 'Stepping Stones',
            'description' => 'Hop across slippery stepping stones',
            'min_level' => 5,
            'energy_cost' => 3,
            'base_xp' => 18,
            'base_success_rate' => 88,
            'location_types' => ['village', 'town', 'barony', 'duchy'],
        ],
        // Level 10 obstacles
        'wall_climb' => [
            'name' => 'Wall Climb',
            'description' => 'Scale a short climbing wall',
            'min_level' => 10,
            'energy_cost' => 4,
            'base_xp' => 22,
            'base_success_rate' => 88,
            'location_types' => ['village', 'town', 'barony', 'duchy'],
        ],
        'balance_beam' => [
            'name' => 'Balance Beam',
            'description' => 'Cross a narrow elevated beam',
            'min_level' => 10,
            'energy_cost' => 4,
            'base_xp' => 25,
            'base_success_rate' => 85,
            'location_types' => ['village', 'town', 'barony', 'duchy'],
        ],
        // Level 15 obstacles
        'monkey_bars' => [
            'name' => 'Monkey Bars',
            'description' => 'Traverse overhead bars with arm strength',
            'min_level' => 15,
            'energy_cost' => 4,
            'base_xp' => 30,
            'base_success_rate' => 85,
            'location_types' => ['village', 'town', 'barony', 'duchy'],
        ],
        'net_climb' => [
            'name' => 'Cargo Net',
            'description' => 'Climb over a large cargo net',
            'min_level' => 15,
            'energy_cost' => 4,
            'base_xp' => 32,
            'base_success_rate' => 85,
            'location_types' => ['village', 'town', 'barony', 'duchy'],
        ],
        // Level 20 obstacles
        'pipe_crawl' => [
            'name' => 'Pipe Crawl',
            'description' => 'Crawl through a narrow pipe tunnel',
            'min_level' => 20,
            'energy_cost' => 5,
            'base_xp' => 38,
            'base_success_rate' => 82,
            'location_types' => ['village', 'town', 'barony', 'duchy'],
        ],
        'tightrope' => [
            'name' => 'Tightrope',
            'description' => 'Walk across a taut rope between posts',
            'min_level' => 20,
            'energy_cost' => 5,
            'base_xp' => 42,
            'base_success_rate' => 80,
            'location_types' => ['town', 'barony', 'duchy'],
        ],
        // Level 25 obstacles
        'rope_ladder' => [
            'name' => 'Rope Ladder',
            'description' => 'Ascend a swinging rope ladder',
            'min_level' => 25,
            'energy_cost' => 5,
            'base_xp' => 48,
            'base_success_rate' => 80,
            'location_types' => ['town', 'barony', 'duchy'],
        ],
        'wall_run' => [
            'name' => 'Wall Run',
            'description' => 'Run up and over a curved wall',
            'min_level' => 25,
            'energy_cost' => 5,
            'base_xp' => 52,
            'base_success_rate' => 78,
            'location_types' => ['town', 'barony', 'duchy'],
        ],
        // Level 30 obstacles
        'spinning_logs' => [
            'name' => 'Spinning Logs',
            'description' => 'Cross logs that spin beneath your feet',
            'min_level' => 30,
            'energy_cost' => 6,
            'base_xp' => 58,
            'base_success_rate' => 78,
            'location_types' => ['town', 'barony', 'duchy'],
        ],
        'leap_of_faith' => [
            'name' => 'Leap of Faith',
            'description' => 'Jump between distant platforms',
            'min_level' => 30,
            'energy_cost' => 6,
            'base_xp' => 62,
            'base_success_rate' => 75,
            'location_types' => ['town', 'barony', 'duchy'],
        ],
        // Level 35 obstacles
        'hanging_rings' => [
            'name' => 'Hanging Rings',
            'description' => 'Swing from ring to ring',
            'min_level' => 35,
            'energy_cost' => 6,
            'base_xp' => 68,
            'base_success_rate' => 75,
            'location_types' => ['town', 'barony', 'duchy'],
        ],
        'salmon_ladder' => [
            'name' => 'Salmon Ladder',
            'description' => 'Climb by jumping a bar upward',
            'min_level' => 35,
            'energy_cost' => 6,
            'base_xp' => 72,
            'base_success_rate' => 72,
            'location_types' => ['town', 'barony', 'duchy'],
        ],
        // Level 40 obstacles
        'cliff_face' => [
            'name' => 'Cliff Face',
            'description' => 'Scale a challenging rock wall',
            'min_level' => 40,
            'energy_cost' => 7,
            'base_xp' => 80,
            'base_success_rate' => 72,
            'location_types' => ['barony', 'duchy'],
        ],
        'spider_web' => [
            'name' => 'Spider Web',
            'description' => 'Navigate through a complex rope web',
            'min_level' => 40,
            'energy_cost' => 7,
            'base_xp' => 85,
            'base_success_rate' => 70,
            'location_types' => ['barony', 'duchy'],
        ],
        // Level 45 obstacles
        'warped_wall' => [
            'name' => 'Warped Wall',
            'description' => 'Run up a tall curved wall',
            'min_level' => 45,
            'energy_cost' => 7,
            'base_xp' => 92,
            'base_success_rate' => 70,
            'location_types' => ['barony', 'duchy'],
        ],
        'floating_steps' => [
            'name' => 'Floating Steps',
            'description' => 'Jump across unstable floating platforms',
            'min_level' => 45,
            'energy_cost' => 7,
            'base_xp' => 98,
            'base_success_rate' => 68,
            'location_types' => ['barony', 'duchy'],
        ],
        // Level 50 obstacles
        'vertical_limit' => [
            'name' => 'Vertical Limit',
            'description' => 'Climb straight up using minimal holds',
            'min_level' => 50,
            'energy_cost' => 8,
            'base_xp' => 105,
            'base_success_rate' => 68,
            'location_types' => ['barony', 'duchy'],
        ],
        'swinging_axes' => [
            'name' => 'Swinging Axes',
            'description' => 'Navigate past swinging obstacles',
            'min_level' => 50,
            'energy_cost' => 8,
            'base_xp' => 112,
            'base_success_rate' => 65,
            'location_types' => ['barony', 'duchy'],
        ],
        // Level 55 obstacles
        'sky_bridge' => [
            'name' => 'Sky Bridge',
            'description' => 'Cross a narrow bridge at great height',
            'min_level' => 55,
            'energy_cost' => 8,
            'base_xp' => 120,
            'base_success_rate' => 65,
            'location_types' => ['barony', 'duchy'],
        ],
        'wind_tunnel' => [
            'name' => 'Wind Tunnel',
            'description' => 'Fight against powerful gusts',
            'min_level' => 55,
            'energy_cost' => 8,
            'base_xp' => 128,
            'base_success_rate' => 62,
            'location_types' => ['barony', 'duchy'],
        ],
        // Level 60 obstacles
        'tower_ascent' => [
            'name' => 'Tower Ascent',
            'description' => 'Climb the exterior of a tall tower',
            'min_level' => 60,
            'energy_cost' => 9,
            'base_xp' => 138,
            'base_success_rate' => 62,
            'location_types' => ['duchy'],
        ],
        'trapeze' => [
            'name' => 'Flying Trapeze',
            'description' => 'Perform aerial trapeze maneuvers',
            'min_level' => 60,
            'energy_cost' => 9,
            'base_xp' => 145,
            'base_success_rate' => 60,
            'location_types' => ['duchy'],
        ],
        // Level 65 obstacles
        'glass_bridge' => [
            'name' => 'Glass Bridge',
            'description' => 'Cross a bridge with treacherous footing',
            'min_level' => 65,
            'energy_cost' => 9,
            'base_xp' => 155,
            'base_success_rate' => 60,
            'location_types' => ['duchy'],
        ],
        'pendulum_jump' => [
            'name' => 'Pendulum Jump',
            'description' => 'Time your jumps with swinging platforms',
            'min_level' => 65,
            'energy_cost' => 9,
            'base_xp' => 165,
            'base_success_rate' => 58,
            'location_types' => ['duchy'],
        ],
        // Level 70 obstacles
        'castle_walls' => [
            'name' => 'Castle Walls',
            'description' => 'Scale ancient castle fortifications',
            'min_level' => 70,
            'energy_cost' => 10,
            'base_xp' => 178,
            'base_success_rate' => 58,
            'location_types' => ['duchy'],
        ],
        'lava_pit' => [
            'name' => 'Lava Pit Crossing',
            'description' => 'Cross above a simulated lava pit',
            'min_level' => 70,
            'energy_cost' => 10,
            'base_xp' => 190,
            'base_success_rate' => 55,
            'location_types' => ['duchy'],
        ],
        // Level 75 obstacles
        'gauntlet_run' => [
            'name' => 'Gauntlet Run',
            'description' => 'Sprint through multiple moving obstacles',
            'min_level' => 75,
            'energy_cost' => 10,
            'base_xp' => 205,
            'base_success_rate' => 55,
            'location_types' => ['duchy'],
        ],
        'ultimate_climb' => [
            'name' => 'Ultimate Climb',
            'description' => 'Conquer the most challenging ascent',
            'min_level' => 75,
            'energy_cost' => 10,
            'base_xp' => 220,
            'base_success_rate' => 52,
            'location_types' => ['duchy'],
        ],
        // Level 80 obstacles
        'ninja_course' => [
            'name' => 'Ninja Course',
            'description' => 'Complete a full ninja warrior course',
            'min_level' => 80,
            'energy_cost' => 12,
            'base_xp' => 250,
            'base_success_rate' => 50,
            'location_types' => ['duchy'],
        ],
        // Level 85 obstacles
        'masters_trial' => [
            'name' => "Master's Trial",
            'description' => 'The ultimate test of agility mastery',
            'min_level' => 85,
            'energy_cost' => 14,
            'base_xp' => 300,
            'base_success_rate' => 45,
            'location_types' => ['duchy'],
        ],
        // Level 90 obstacles
        'legendary_course' => [
            'name' => 'Legendary Course',
            'description' => 'A course completed by only the greatest',
            'min_level' => 90,
            'energy_cost' => 16,
            'base_xp' => 400,
            'base_success_rate' => 40,
            'is_legendary' => true,
            'location_types' => ['duchy'],
        ],
    ];

    public function __construct(
        protected DailyTaskService $dailyTaskService
    ) {}

    /**
     * Check if user can train agility at their current location.
     */
    public function canTrain(User $user): bool
    {
        if ($user->isTraveling()) {
            return false;
        }

        // Kingdom does not have agility training
        if ($user->current_location_type === 'kingdom') {
            return false;
        }

        // Check if any obstacles are available at this location
        foreach (self::OBSTACLES as $obstacle) {
            if (in_array($user->current_location_type, $obstacle['location_types'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get available obstacles at the current location.
     */
    public function getAvailableObstacles(User $user): array
    {
        $obstacles = [];
        $agilityLevel = $user->getSkillLevel('agility');

        foreach (self::OBSTACLES as $key => $config) {
            if (! in_array($user->current_location_type, $config['location_types'])) {
                continue;
            }

            $isUnlocked = $agilityLevel >= $config['min_level'];
            $successRate = $this->calculateSuccessRate($agilityLevel, $config);

            $obstacles[] = [
                'id' => $key,
                'name' => $config['name'],
                'description' => $config['description'],
                'min_level' => $config['min_level'],
                'energy_cost' => $config['energy_cost'],
                'base_xp' => $config['base_xp'],
                'success_rate' => $successRate,
                'is_unlocked' => $isUnlocked,
                'is_legendary' => $config['is_legendary'] ?? false,
                'can_attempt' => $isUnlocked && $user->hasEnergy($config['energy_cost']),
            ];
        }

        return $obstacles;
    }

    /**
     * Calculate success rate based on player level vs obstacle requirement.
     */
    protected function calculateSuccessRate(int $playerLevel, array $config): int
    {
        $baseRate = $config['base_success_rate'];
        $levelDiff = $playerLevel - $config['min_level'];

        // Each level above requirement adds 0.5% success rate (max +20%)
        $levelBonus = min(20, $levelDiff * 0.5);

        // For legendary obstacles, cap at 60%
        if ($config['is_legendary'] ?? false) {
            return max(5, min(60, $baseRate + $levelBonus));
        }

        return max(10, min(98, $baseRate + $levelBonus));
    }

    /**
     * Attempt an agility obstacle.
     */
    public function train(User $user, string $obstacleId, ?string $locationType = null, ?int $locationId = null): array
    {
        $config = self::OBSTACLES[$obstacleId] ?? null;

        if (! $config) {
            return [
                'success' => false,
                'message' => 'Invalid obstacle.',
            ];
        }

        if (! in_array($user->current_location_type, $config['location_types'])) {
            return [
                'success' => false,
                'message' => 'This obstacle is not available at your location.',
            ];
        }

        $agilityLevel = $user->getSkillLevel('agility');

        if ($agilityLevel < $config['min_level']) {
            return [
                'success' => false,
                'message' => "You need level {$config['min_level']} Agility to attempt this.",
            ];
        }

        if (! $user->hasEnergy($config['energy_cost'])) {
            return [
                'success' => false,
                'message' => "Not enough energy. Need {$config['energy_cost']} energy.",
            ];
        }

        $locationType = $locationType ?? $user->current_location_type;
        $locationId = $locationId ?? $user->current_location_id;

        $successRate = $this->calculateSuccessRate($agilityLevel, $config);
        $roll = mt_rand(1, 100);
        $isSuccess = $roll <= $successRate;

        return DB::transaction(function () use ($user, $config, $obstacleId, $isSuccess, $locationType, $locationId) {
            // Always consume energy
            $user->consumeEnergy($config['energy_cost']);

            if ($isSuccess) {
                return $this->handleSuccess($user, $config, $obstacleId, $locationType, $locationId);
            } else {
                return $this->handleFailure($user, $config, $obstacleId, $locationType, $locationId);
            }
        });
    }

    /**
     * Handle successful obstacle completion.
     */
    protected function handleSuccess(User $user, array $config, string $obstacleId, ?string $locationType, ?int $locationId): array
    {
        // Award XP
        $xpAwarded = $config['base_xp'];
        $skill = $user->skills()->where('skill_name', 'agility')->first();

        if (! $skill) {
            // Create the skill if it doesn't exist
            $skill = $user->skills()->create([
                'skill_name' => 'agility',
                'level' => 1,
                'xp' => 0,
            ]);
        }

        $oldLevel = $skill->level;
        $skill->addXp($xpAwarded);
        $leveledUp = $skill->fresh()->level > $oldLevel;
        $newLevel = $skill->fresh()->level;

        // Record daily task progress
        $this->dailyTaskService->recordProgress($user, 'agility', $config['name'], 1);

        // Log activity
        if ($locationType && $locationId) {
            try {
                LocationActivityLog::log(
                    userId: $user->id,
                    locationType: $locationType,
                    locationId: $locationId,
                    activityType: 'agility',
                    description: "{$user->username} completed the {$config['name']}",
                    activitySubtype: $obstacleId,
                    metadata: [
                        'obstacle' => $config['name'],
                        'xp_gained' => $xpAwarded,
                    ]
                );
            } catch (\Illuminate\Database\QueryException $e) {
                // Table may not exist
            }
        }

        return [
            'success' => true,
            'message' => "You successfully completed the {$config['name']}!",
            'xp_awarded' => $xpAwarded,
            'leveled_up' => $leveledUp,
            'new_level' => $newLevel,
            'energy_remaining' => $user->fresh()->energy,
        ];
    }

    /**
     * Handle failed obstacle attempt.
     */
    protected function handleFailure(User $user, array $config, string $obstacleId, ?string $locationType, ?int $locationId): array
    {
        // Award reduced XP (25% of base) for the attempt
        $xpAwarded = (int) ceil($config['base_xp'] * 0.25);
        $skill = $user->skills()->where('skill_name', 'agility')->first();

        if (! $skill) {
            $skill = $user->skills()->create([
                'skill_name' => 'agility',
                'level' => 1,
                'xp' => 0,
            ]);
        }

        $skill->addXp($xpAwarded);

        // Log activity
        if ($locationType && $locationId) {
            try {
                LocationActivityLog::log(
                    userId: $user->id,
                    locationType: $locationType,
                    locationId: $locationId,
                    activityType: 'agility',
                    description: "{$user->username} failed the {$config['name']}",
                    activitySubtype: $obstacleId,
                    metadata: [
                        'obstacle' => $config['name'],
                        'failed' => true,
                    ]
                );
            } catch (\Illuminate\Database\QueryException $e) {
                // Table may not exist
            }
        }

        return [
            'success' => false,
            'failed' => true,
            'message' => "You slipped and failed the {$config['name']}. Try again!",
            'xp_awarded' => $xpAwarded,
            'energy_remaining' => $user->fresh()->energy,
        ];
    }

    /**
     * Get agility info for the page.
     */
    public function getAgilityInfo(User $user): array
    {
        $skill = $user->skills()->where('skill_name', 'agility')->first();
        $agilityLevel = $skill?->level ?? 1;

        return [
            'can_train' => $this->canTrain($user),
            'obstacles' => $this->getAvailableObstacles($user),
            'player_energy' => $user->energy,
            'max_energy' => $user->max_energy,
            'agility_level' => $agilityLevel,
            'agility_xp' => $skill?->xp ?? 0,
            'agility_xp_progress' => $skill?->getXpProgress() ?? 0,
            'agility_xp_to_next' => $skill?->xpToNextLevel() ?? 60,
        ];
    }
}

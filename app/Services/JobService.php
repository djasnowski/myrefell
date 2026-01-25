<?php

namespace App\Services;

use App\Models\EmploymentJob;
use App\Models\PlayerEmployment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class JobService
{
    /**
     * Maximum concurrent jobs per player.
     */
    public const MAX_CONCURRENT_JOBS = 2;

    public function __construct(
        protected EnergyService $energyService
    ) {}

    /**
     * Get available jobs at a location.
     */
    public function getAvailableJobs(User $user, string $locationType, int $locationId): Collection
    {
        // Get jobs the player already has at this location
        $employedJobIds = PlayerEmployment::where('user_id', $user->id)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('status', PlayerEmployment::STATUS_EMPLOYED)
            ->pluck('employment_job_id');

        return EmploymentJob::where('is_active', true)
            ->where('location_type', $locationType)
            ->whereNotIn('id', $employedJobIds)
            ->get()
            ->filter(fn ($job) => $job->playerMeetsRequirements($user))
            ->filter(fn ($job) => $job->hasAvailableSlots($locationType, $locationId))
            ->map(fn ($job) => $this->formatJob($job, $locationType, $locationId))
            ->values();
    }

    /**
     * Get the player's current employment.
     */
    public function getCurrentEmployment(User $user): Collection
    {
        return PlayerEmployment::where('user_id', $user->id)
            ->where('status', PlayerEmployment::STATUS_EMPLOYED)
            ->with('job')
            ->get()
            ->map(fn ($pe) => $this->formatEmployment($pe));
    }

    /**
     * Get the player's employment at a specific location.
     */
    public function getEmploymentAtLocation(User $user, string $locationType, int $locationId): Collection
    {
        return PlayerEmployment::where('user_id', $user->id)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('status', PlayerEmployment::STATUS_EMPLOYED)
            ->with('job')
            ->get()
            ->map(fn ($pe) => $this->formatEmployment($pe));
    }

    /**
     * Apply for a job.
     */
    public function applyForJob(User $user, EmploymentJob $job, string $locationType, int $locationId): array
    {
        // Check if already employed at this job at this location
        $existing = PlayerEmployment::where('user_id', $user->id)
            ->where('employment_job_id', $job->id)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('status', PlayerEmployment::STATUS_EMPLOYED)
            ->exists();

        if ($existing) {
            return [
                'success' => false,
                'message' => 'You already have this job at this location.',
            ];
        }

        // Check concurrent job limit
        $currentJobCount = PlayerEmployment::where('user_id', $user->id)
            ->where('status', PlayerEmployment::STATUS_EMPLOYED)
            ->count();

        if ($currentJobCount >= self::MAX_CONCURRENT_JOBS) {
            return [
                'success' => false,
                'message' => 'You already have the maximum number of jobs. Quit one first.',
            ];
        }

        // Check requirements
        if (! $job->playerMeetsRequirements($user)) {
            return [
                'success' => false,
                'message' => 'You do not meet the requirements for this job.',
            ];
        }

        // Check if job is at the right location type
        if ($job->location_type !== $locationType) {
            return [
                'success' => false,
                'message' => 'This job is not available at this type of location.',
            ];
        }

        // Check available slots
        if (! $job->hasAvailableSlots($locationType, $locationId)) {
            return [
                'success' => false,
                'message' => 'No positions available for this job.',
            ];
        }

        PlayerEmployment::create([
            'user_id' => $user->id,
            'employment_job_id' => $job->id,
            'location_type' => $locationType,
            'location_id' => $locationId,
            'status' => PlayerEmployment::STATUS_EMPLOYED,
            'hired_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => "You have been hired as a {$job->name}!",
        ];
    }

    /**
     * Quit a job.
     */
    public function quitJob(User $user, PlayerEmployment $employment): array
    {
        if ($employment->user_id !== $user->id) {
            return [
                'success' => false,
                'message' => 'This job does not belong to you.',
            ];
        }

        if ($employment->status !== PlayerEmployment::STATUS_EMPLOYED) {
            return [
                'success' => false,
                'message' => 'You are not currently employed at this job.',
            ];
        }

        $employment->update([
            'status' => PlayerEmployment::STATUS_QUIT,
        ]);

        return [
            'success' => true,
            'message' => "You have quit your job as a {$employment->job->name}.",
        ];
    }

    /**
     * Work at a job.
     */
    public function work(User $user, PlayerEmployment $employment): array
    {
        if ($employment->user_id !== $user->id) {
            return [
                'success' => false,
                'message' => 'This job does not belong to you.',
            ];
        }

        if (! $employment->isEmployed()) {
            return [
                'success' => false,
                'message' => 'You are not currently employed at this job.',
            ];
        }

        // Check if player is at the correct location
        if ($user->current_location_type !== $employment->location_type ||
            $user->current_location_id !== $employment->location_id) {
            return [
                'success' => false,
                'message' => 'You must be at your workplace to work.',
            ];
        }

        // Check cooldown
        if (! $employment->canWork()) {
            $minutes = $employment->minutes_until_work;

            return [
                'success' => false,
                'message' => "You need to rest. You can work again in {$minutes} minutes.",
            ];
        }

        $job = $employment->job;

        // Check energy
        if (! $this->energyService->hasEnergy($user, $job->energy_cost)) {
            return [
                'success' => false,
                'message' => "You need {$job->energy_cost} energy to work. You have {$user->energy}.",
            ];
        }

        return DB::transaction(function () use ($user, $employment, $job) {
            // Consume energy
            $this->energyService->consumeEnergy($user, $job->energy_cost);

            // Pay wages
            $user->increment('gold', $job->base_wage);

            // Award XP
            $xpAwarded = null;
            if ($job->xp_reward > 0 && $job->xp_skill) {
                $skill = $user->skills()->where('skill_name', $job->xp_skill)->first();
                if ($skill) {
                    $skill->addXp($job->xp_reward);
                    $xpAwarded = [
                        'amount' => $job->xp_reward,
                        'skill' => $job->xp_skill,
                    ];
                }
            }

            // Update employment record
            $employment->update([
                'last_worked_at' => now(),
                'times_worked' => $employment->times_worked + 1,
                'total_earnings' => $employment->total_earnings + $job->base_wage,
            ]);

            return [
                'success' => true,
                'message' => "You worked as a {$job->name} and earned {$job->base_wage} gold!",
                'rewards' => [
                    'gold' => $job->base_wage,
                    'xp' => $xpAwarded,
                    'energy_used' => $job->energy_cost,
                ],
            ];
        });
    }

    /**
     * Format a job for display.
     */
    protected function formatJob(EmploymentJob $job, string $locationType, int $locationId): array
    {
        return [
            'id' => $job->id,
            'name' => $job->name,
            'icon' => $job->icon,
            'description' => $job->description,
            'category' => $job->category,
            'category_display' => $job->category_display,
            'location_type' => $job->location_type,
            'energy_cost' => $job->energy_cost,
            'base_wage' => $job->base_wage,
            'xp_reward' => $job->xp_reward,
            'xp_skill' => $job->xp_skill,
            'cooldown_minutes' => $job->cooldown_minutes,
            'required_skill' => $job->required_skill,
            'required_skill_level' => $job->required_skill_level,
            'required_level' => $job->required_level,
            'current_workers' => $job->countWorkersAtLocation($locationType, $locationId),
            'max_workers' => $job->max_workers,
        ];
    }

    /**
     * Format an employment record for display.
     */
    protected function formatEmployment(PlayerEmployment $employment): array
    {
        $job = $employment->job;

        return [
            'id' => $employment->id,
            'job_id' => $job->id,
            'name' => $job->name,
            'icon' => $job->icon,
            'description' => $job->description,
            'category' => $job->category,
            'location_type' => $employment->location_type,
            'location_id' => $employment->location_id,
            'location_name' => $employment->location_name,
            'energy_cost' => $job->energy_cost,
            'base_wage' => $job->base_wage,
            'xp_reward' => $job->xp_reward,
            'xp_skill' => $job->xp_skill,
            'cooldown_minutes' => $job->cooldown_minutes,
            'status' => $employment->status,
            'hired_at' => $employment->hired_at->toISOString(),
            'last_worked_at' => $employment->last_worked_at?->toISOString(),
            'times_worked' => $employment->times_worked,
            'total_earnings' => $employment->total_earnings,
            'can_work' => $employment->canWork(),
            'minutes_until_work' => $employment->minutes_until_work,
        ];
    }

    /**
     * Seed default jobs.
     */
    public static function seedDefaultJobs(): void
    {
        $jobs = [
            // Service jobs (available at villages, towns)
            [
                'name' => 'Cook',
                'icon' => 'utensils',
                'description' => 'Prepare meals for travelers and locals at the tavern.',
                'category' => 'service',
                'location_type' => 'village',
                'energy_cost' => 8,
                'base_wage' => 45,
                'xp_reward' => 15,
                'xp_skill' => 'cooking',
                'cooldown_minutes' => 30,
            ],
            [
                'name' => 'Cleaner',
                'icon' => 'sparkles',
                'description' => 'Keep the village buildings clean and tidy.',
                'category' => 'service',
                'location_type' => 'village',
                'energy_cost' => 6,
                'base_wage' => 30,
                'xp_reward' => 10,
                'xp_skill' => null,
                'cooldown_minutes' => 20,
            ],
            [
                'name' => 'Stable Hand',
                'icon' => 'horse',
                'description' => 'Care for horses and livestock at the stables.',
                'category' => 'labor',
                'location_type' => 'village',
                'energy_cost' => 10,
                'base_wage' => 40,
                'xp_reward' => 12,
                'xp_skill' => null,
                'cooldown_minutes' => 25,
            ],
            [
                'name' => 'Farmhand',
                'icon' => 'wheat',
                'description' => 'Help with planting, tending, and harvesting crops.',
                'category' => 'labor',
                'location_type' => 'village',
                'energy_cost' => 12,
                'base_wage' => 50,
                'xp_reward' => 15,
                'xp_skill' => 'foraging',
                'cooldown_minutes' => 30,
            ],

            // Skilled jobs (require skills)
            [
                'name' => 'Miner',
                'icon' => 'pickaxe',
                'description' => 'Extract ore from the village mines.',
                'category' => 'skilled',
                'location_type' => 'village',
                'energy_cost' => 15,
                'base_wage' => 70,
                'xp_reward' => 25,
                'xp_skill' => 'mining',
                'required_skill' => 'mining',
                'required_skill_level' => 5,
                'cooldown_minutes' => 35,
            ],
            [
                'name' => 'Lumberjack',
                'icon' => 'axe',
                'description' => 'Fell trees and process timber.',
                'category' => 'skilled',
                'location_type' => 'village',
                'energy_cost' => 14,
                'base_wage' => 65,
                'xp_reward' => 22,
                'xp_skill' => 'woodcutting',
                'required_skill' => 'woodcutting',
                'required_skill_level' => 5,
                'cooldown_minutes' => 35,
            ],

            // Barony jobs
            [
                'name' => 'Guard Duty',
                'icon' => 'shield',
                'description' => 'Stand watch at the barony gates.',
                'category' => 'service',
                'location_type' => 'barony',
                'energy_cost' => 10,
                'base_wage' => 60,
                'xp_reward' => 20,
                'xp_skill' => 'defense',
                'required_level' => 5,
                'cooldown_minutes' => 40,
            ],
            [
                'name' => 'Squire',
                'icon' => 'swords',
                'description' => 'Assist knights with their equipment and training.',
                'category' => 'service',
                'location_type' => 'barony',
                'energy_cost' => 12,
                'base_wage' => 55,
                'xp_reward' => 18,
                'xp_skill' => 'attack',
                'required_level' => 3,
                'cooldown_minutes' => 35,
            ],

            // Town jobs
            [
                'name' => 'Market Vendor',
                'icon' => 'store',
                'description' => 'Help manage a stall at the town market.',
                'category' => 'service',
                'location_type' => 'town',
                'energy_cost' => 8,
                'base_wage' => 55,
                'xp_reward' => 15,
                'xp_skill' => null,
                'cooldown_minutes' => 30,
            ],
            [
                'name' => 'Blacksmith Assistant',
                'icon' => 'hammer',
                'description' => 'Assist the blacksmith with forging and repairs.',
                'category' => 'skilled',
                'location_type' => 'town',
                'energy_cost' => 14,
                'base_wage' => 75,
                'xp_reward' => 25,
                'xp_skill' => 'smithing',
                'required_skill' => 'smithing',
                'required_skill_level' => 5,
                'cooldown_minutes' => 35,
            ],
        ];

        foreach ($jobs as $jobData) {
            EmploymentJob::updateOrCreate(
                ['name' => $jobData['name']],
                $jobData
            );
        }
    }
}

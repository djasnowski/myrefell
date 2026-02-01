<?php

namespace App\Services;

use App\Models\EmploymentJob;
use App\Models\LocationStockpile;
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

    /**
     * Default supervisor cut percentage if not specified.
     */
    public const DEFAULT_SUPERVISOR_CUT = 10;

    public function __construct(
        protected EnergyService $energyService,
        protected InventoryService $inventoryService
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
        // Check if user is physically at this location
        if ($user->current_location_type !== $locationType || $user->current_location_id !== $locationId) {
            return [
                'success' => false,
                'message' => 'You must travel to this location to apply for a job here.',
            ];
        }

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
     * Fire a worker (supervisor action).
     */
    public function fireWorker(User $supervisor, PlayerEmployment $employment, ?string $reason = null): array
    {
        $job = $employment->job;

        // Check if the job has a supervisor role
        if (! $job->supervisor_role_slug) {
            return [
                'success' => false,
                'message' => 'This job does not have a supervisor role.',
            ];
        }

        // Check if the supervisor holds the correct role at this location
        $expectedSupervisor = $job->getSupervisorAtLocation(
            $employment->location_type,
            $employment->location_id
        );

        if (! $expectedSupervisor || $expectedSupervisor->id !== $supervisor->id) {
            return [
                'success' => false,
                'message' => 'You are not the supervisor for this job at this location.',
            ];
        }

        // Can't fire yourself
        if ($employment->user_id === $supervisor->id) {
            return [
                'success' => false,
                'message' => 'You cannot fire yourself.',
            ];
        }

        if ($employment->status !== PlayerEmployment::STATUS_EMPLOYED) {
            return [
                'success' => false,
                'message' => 'This worker is not currently employed.',
            ];
        }

        $worker = $employment->user;
        $jobName = $job->name;

        $employment->update([
            'status' => PlayerEmployment::STATUS_FIRED,
            'fired_by' => $supervisor->id,
            'fired_at' => now(),
            'fired_reason' => $reason,
        ]);

        return [
            'success' => true,
            'message' => "You have fired {$worker->username} from their position as {$jobName}.",
            'worker' => [
                'id' => $worker->id,
                'username' => $worker->username,
            ],
        ];
    }

    /**
     * Get all workers at a location that the supervisor oversees.
     */
    public function getSupervisedWorkers(User $supervisor, string $locationType, int $locationId): Collection
    {
        // Get all jobs this supervisor oversees at this location
        $supervisorRoleSlugs = $supervisor->playerRoles()
            ->active()
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->with('role')
            ->get()
            ->pluck('role.slug')
            ->toArray();

        if (empty($supervisorRoleSlugs)) {
            return collect();
        }

        // Get jobs that this supervisor role oversees
        $jobIds = EmploymentJob::whereIn('supervisor_role_slug', $supervisorRoleSlugs)
            ->pluck('id');

        // Get all employed workers for those jobs at this location
        return PlayerEmployment::whereIn('employment_job_id', $jobIds)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('status', PlayerEmployment::STATUS_EMPLOYED)
            ->with(['user', 'job'])
            ->get()
            ->map(fn ($pe) => [
                'employment_id' => $pe->id,
                'user_id' => $pe->user_id,
                'username' => $pe->user->username,
                'job_name' => $pe->job->name,
                'hired_at' => $pe->hired_at->toISOString(),
                'times_worked' => $pe->times_worked,
                'total_earnings' => $pe->total_earnings,
            ]);
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

            // Calculate supervisor cut
            $supervisorCut = 0;
            $supervisor = null;
            $supervisorCutPercent = $job->supervisor_cut_percent ?? self::DEFAULT_SUPERVISOR_CUT;

            if ($job->supervisor_role_slug) {
                $supervisor = $job->getSupervisorAtLocation($employment->location_type, $employment->location_id);
                if ($supervisor && $supervisor->id !== $user->id) {
                    $supervisorCut = (int) floor($job->base_wage * $supervisorCutPercent / 100);
                }
            }

            // Pay wages (minus supervisor cut)
            $workerWage = $job->base_wage - $supervisorCut;
            $user->increment('gold', $workerWage);

            // Pay supervisor their cut
            if ($supervisor && $supervisorCut > 0) {
                $supervisor->increment('gold', $supervisorCut);
            }

            // Award XP
            $xpAwarded = null;
            if ($job->xp_reward > 0 && $job->xp_skill) {
                $skill = $user->skills()->where('skill_name', $job->xp_skill)->first();

                if (! $skill) {
                    $skill = $user->skills()->create([
                        'skill_name' => $job->xp_skill,
                        'level' => 1,
                        'xp' => 0,
                    ]);
                }

                $skill->addXp($job->xp_reward);
                $xpAwarded = [
                    'amount' => $job->xp_reward,
                    'skill' => $job->xp_skill,
                ];
            }

            // Check for production
            $produced = null;
            if ($job->produces_item && $job->production_chance > 0) {
                if (mt_rand(1, 100) <= $job->production_chance) {
                    $item = $job->getProducedItem();
                    if ($item) {
                        $quantity = $job->production_quantity ?? 1;

                        // Add to location stockpile
                        $stockpile = LocationStockpile::getOrCreate(
                            $employment->location_type,
                            $employment->location_id,
                            $item->id
                        );
                        $stockpile->addQuantity($quantity);

                        $produced = [
                            'item' => $item->name,
                            'quantity' => $quantity,
                        ];
                    }
                }
            }

            // Update employment record
            $employment->update([
                'last_worked_at' => now(),
                'times_worked' => $employment->times_worked + 1,
                'total_earnings' => $employment->total_earnings + $workerWage,
            ]);

            // Build message
            $message = "You worked as a {$job->name} and earned {$workerWage} gold!";
            if ($supervisorCut > 0 && $supervisor) {
                $message .= " ({$supervisorCut}g paid to your supervisor)";
            }
            if ($produced) {
                $message .= " You produced {$produced['quantity']}x {$produced['item']} for the stockpile.";
            }

            return [
                'success' => true,
                'message' => $message,
                'rewards' => [
                    'gold' => $workerWage,
                    'supervisor_cut' => $supervisorCut,
                    'supervisor' => $supervisor?->username,
                    'xp' => $xpAwarded,
                    'energy_used' => $job->energy_cost,
                    'produced' => $produced,
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
            // ===================
            // VILLAGE JOBS
            // ===================
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
                'supervisor_role_slug' => 'innkeeper',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Cooked Meat',
                'production_chance' => 25,
                'production_quantity' => 1,
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
                'xp_skill' => 'agility',
                'cooldown_minutes' => 20,
                'supervisor_role_slug' => 'elder',
                'supervisor_cut_percent' => 10,
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
                'xp_skill' => 'strength',
                'cooldown_minutes' => 25,
                'supervisor_role_slug' => 'elder',
                'supervisor_cut_percent' => 10,
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
                'xp_skill' => 'farming',
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'master_farmer',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Wheat',
                'production_chance' => 30,
                'production_quantity' => 2,
            ],
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
                'supervisor_role_slug' => 'miner',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Iron Ore',
                'production_chance' => 35,
                'production_quantity' => 1,
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
                'supervisor_role_slug' => 'forester',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Oak Logs',
                'production_chance' => 35,
                'production_quantity' => 2,
            ],

            // Blacksmith helpers
            [
                'name' => 'Forge Assistant',
                'icon' => 'flame',
                'description' => 'Maintain the forge fire and assist the blacksmith with metalwork.',
                'category' => 'skilled',
                'location_type' => 'village',
                'energy_cost' => 12,
                'base_wage' => 55,
                'xp_reward' => 18,
                'xp_skill' => 'smithing',
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'blacksmith',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Iron Bar',
                'production_chance' => 25,
                'production_quantity' => 1,
            ],

            // Merchant helpers
            [
                'name' => 'Stock Clerk',
                'icon' => 'package',
                'description' => 'Organize inventory and assist customers at the market.',
                'category' => 'service',
                'location_type' => 'village',
                'energy_cost' => 6,
                'base_wage' => 35,
                'xp_reward' => 10,
                'xp_skill' => 'crafting',
                'cooldown_minutes' => 20,
                'supervisor_role_slug' => 'merchant',
                'supervisor_cut_percent' => 10,
            ],

            // Guard Captain helpers
            [
                'name' => 'Village Guard',
                'icon' => 'shield',
                'description' => 'Patrol the village and keep watch for threats.',
                'category' => 'service',
                'location_type' => 'village',
                'energy_cost' => 10,
                'base_wage' => 50,
                'xp_reward' => 15,
                'xp_skill' => 'defense',
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'guard_captain',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Night Watch',
                'icon' => 'moon',
                'description' => 'Guard the village gates during the night hours.',
                'category' => 'service',
                'location_type' => 'village',
                'energy_cost' => 8,
                'base_wage' => 45,
                'xp_reward' => 12,
                'xp_skill' => 'defense',
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'guard_captain',
                'supervisor_cut_percent' => 10,
            ],

            // Healer helpers
            [
                'name' => "Healer's Apprentice",
                'icon' => 'heart-pulse',
                'description' => 'Assist the healer with patients and prepare medicines.',
                'category' => 'skilled',
                'location_type' => 'village',
                'energy_cost' => 8,
                'base_wage' => 40,
                'xp_reward' => 15,
                'xp_skill' => 'prayer',
                'cooldown_minutes' => 25,
                'supervisor_role_slug' => 'healer',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Healing Herb',
                'production_chance' => 20,
                'production_quantity' => 1,
            ],

            // Fisherman helpers
            [
                'name' => 'Fishing Helper',
                'icon' => 'anchor',
                'description' => 'Cast nets and help haul in the daily catch.',
                'category' => 'labor',
                'location_type' => 'village',
                'energy_cost' => 10,
                'base_wage' => 40,
                'xp_reward' => 15,
                'xp_skill' => 'fishing',
                'cooldown_minutes' => 25,
                'supervisor_role_slug' => 'fisherman',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Raw Trout',
                'production_chance' => 30,
                'production_quantity' => 2,
            ],
            [
                'name' => 'Net Mender',
                'icon' => 'grid-3x3',
                'description' => 'Repair fishing nets and maintain fishing equipment.',
                'category' => 'labor',
                'location_type' => 'village',
                'energy_cost' => 6,
                'base_wage' => 30,
                'xp_reward' => 10,
                'xp_skill' => 'crafting',
                'cooldown_minutes' => 20,
                'supervisor_role_slug' => 'fisherman',
                'supervisor_cut_percent' => 10,
            ],

            // Priest helpers
            [
                'name' => 'Altar Server',
                'icon' => 'candle',
                'description' => 'Assist with religious ceremonies and maintain the shrine.',
                'category' => 'service',
                'location_type' => 'village',
                'energy_cost' => 6,
                'base_wage' => 30,
                'xp_reward' => 12,
                'xp_skill' => 'prayer',
                'cooldown_minutes' => 20,
                'supervisor_role_slug' => 'priest',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Temple Keeper',
                'icon' => 'church',
                'description' => 'Clean and maintain the village shrine or temple.',
                'category' => 'service',
                'location_type' => 'village',
                'energy_cost' => 8,
                'base_wage' => 35,
                'xp_reward' => 10,
                'xp_skill' => 'prayer',
                'cooldown_minutes' => 25,
                'supervisor_role_slug' => 'priest',
                'supervisor_cut_percent' => 10,
            ],

            // Baker helpers
            [
                'name' => "Baker's Assistant",
                'icon' => 'croissant',
                'description' => 'Knead dough and help bake bread for the village.',
                'category' => 'labor',
                'location_type' => 'village',
                'energy_cost' => 8,
                'base_wage' => 35,
                'xp_reward' => 12,
                'xp_skill' => 'cooking',
                'cooldown_minutes' => 25,
                'supervisor_role_slug' => 'baker',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Bread',
                'production_chance' => 35,
                'production_quantity' => 2,
            ],

            // Butcher helpers
            [
                'name' => "Butcher's Assistant",
                'icon' => 'beef',
                'description' => 'Help process meat and prepare cuts for sale.',
                'category' => 'labor',
                'location_type' => 'village',
                'energy_cost' => 10,
                'base_wage' => 40,
                'xp_reward' => 12,
                'xp_skill' => 'cooking',
                'cooldown_minutes' => 25,
                'supervisor_role_slug' => 'butcher',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Raw Meat',
                'production_chance' => 30,
                'production_quantity' => 1,
            ],

            // Hunter helpers
            [
                'name' => 'Hunting Assistant',
                'icon' => 'crosshair',
                'description' => 'Track game and assist with hunts in the surrounding wilderness.',
                'category' => 'skilled',
                'location_type' => 'village',
                'energy_cost' => 12,
                'base_wage' => 45,
                'xp_reward' => 18,
                'xp_skill' => 'range',
                'required_skill' => 'range',
                'required_skill_level' => 3,
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'hunter',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Raw Meat',
                'production_chance' => 25,
                'production_quantity' => 1,
            ],
            [
                'name' => 'Trapper',
                'icon' => 'box',
                'description' => 'Set and check traps for small game around the village.',
                'category' => 'skilled',
                'location_type' => 'village',
                'energy_cost' => 8,
                'base_wage' => 35,
                'xp_reward' => 14,
                'xp_skill' => 'range',
                'cooldown_minutes' => 25,
                'supervisor_role_slug' => 'hunter',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Leather',
                'production_chance' => 20,
                'production_quantity' => 1,
            ],

            // Brewer helpers
            [
                'name' => "Brewer's Assistant",
                'icon' => 'beer',
                'description' => 'Help brew ale and maintain the brewing equipment.',
                'category' => 'labor',
                'location_type' => 'village',
                'energy_cost' => 8,
                'base_wage' => 38,
                'xp_reward' => 12,
                'xp_skill' => 'cooking',
                'cooldown_minutes' => 25,
                'supervisor_role_slug' => 'brewer',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Ale',
                'production_chance' => 30,
                'production_quantity' => 1,
            ],

            // Additional woodcutting jobs
            [
                'name' => 'Timber Hauler',
                'icon' => 'truck',
                'description' => 'Carry heavy logs from the forest to the village lumber yard.',
                'category' => 'labor',
                'location_type' => 'village',
                'energy_cost' => 12,
                'base_wage' => 45,
                'xp_reward' => 18,
                'xp_skill' => 'strength',
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'forester',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Wood',
                'production_chance' => 40,
                'production_quantity' => 2,
            ],
            [
                'name' => 'Sawmill Worker',
                'icon' => 'axe',
                'description' => 'Process logs into usable lumber at the sawmill.',
                'category' => 'labor',
                'location_type' => 'village',
                'energy_cost' => 10,
                'base_wage' => 42,
                'xp_reward' => 15,
                'xp_skill' => 'woodcutting',
                'cooldown_minutes' => 25,
                'supervisor_role_slug' => 'forester',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Oak Wood',
                'production_chance' => 30,
                'production_quantity' => 1,
            ],

            // Strength-based village jobs
            [
                'name' => 'Well Digger',
                'icon' => 'droplet',
                'description' => 'Dig and maintain wells throughout the village.',
                'category' => 'labor',
                'location_type' => 'village',
                'energy_cost' => 14,
                'base_wage' => 50,
                'xp_reward' => 20,
                'xp_skill' => 'strength',
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'elder',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Stone Carrier',
                'icon' => 'mountain',
                'description' => 'Haul stone and building materials for construction.',
                'category' => 'labor',
                'location_type' => 'village',
                'energy_cost' => 14,
                'base_wage' => 48,
                'xp_reward' => 20,
                'xp_skill' => 'strength',
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'blacksmith',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Stone',
                'production_chance' => 25,
                'production_quantity' => 1,
            ],

            // Hitpoints/endurance village jobs
            [
                'name' => 'Message Runner',
                'icon' => 'footprints',
                'description' => 'Run messages between nearby villages and farms.',
                'category' => 'service',
                'location_type' => 'village',
                'energy_cost' => 10,
                'base_wage' => 35,
                'xp_reward' => 18,
                'xp_skill' => 'hitpoints',
                'cooldown_minutes' => 25,
                'supervisor_role_slug' => 'elder',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Field Hand',
                'icon' => 'wheat',
                'description' => 'Work the fields from dawn to dusk, building stamina.',
                'category' => 'labor',
                'location_type' => 'village',
                'energy_cost' => 12,
                'base_wage' => 40,
                'xp_reward' => 15,
                'xp_skill' => 'hitpoints',
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'master_farmer',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Wheat',
                'production_chance' => 35,
                'production_quantity' => 2,
            ],

            // ===================
            // BARONY JOBS
            // ===================
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
                'supervisor_role_slug' => 'marshal',
                'supervisor_cut_percent' => 10,
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
                'supervisor_role_slug' => 'marshal',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Servant',
                'icon' => 'sparkles',
                'description' => 'Clean chambers and serve the nobility.',
                'category' => 'service',
                'location_type' => 'barony',
                'energy_cost' => 8,
                'base_wage' => 40,
                'xp_reward' => 10,
                'xp_skill' => null,
                'cooldown_minutes' => 25,
                'supervisor_role_slug' => 'steward',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Page',
                'icon' => 'scroll',
                'description' => 'Run messages and assist nobles with daily tasks.',
                'category' => 'service',
                'location_type' => 'barony',
                'energy_cost' => 10,
                'base_wage' => 45,
                'xp_reward' => 12,
                'xp_skill' => null,
                'cooldown_minutes' => 20,
                'supervisor_role_slug' => 'steward',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Herald',
                'icon' => 'megaphone',
                'description' => 'Make announcements and assist with ceremonies.',
                'category' => 'service',
                'location_type' => 'barony',
                'energy_cost' => 8,
                'base_wage' => 55,
                'xp_reward' => 14,
                'xp_skill' => null,
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'steward',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Barony Stable Hand',
                'icon' => 'horse',
                'description' => 'Care for the horses and maintain the stables.',
                'category' => 'service',
                'location_type' => 'barony',
                'energy_cost' => 12,
                'base_wage' => 50,
                'xp_reward' => 15,
                'xp_skill' => null,
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'castle_stablemaster',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Mason',
                'icon' => 'brick-wall',
                'description' => 'Repair and maintain the castle walls and structures.',
                'category' => 'labor',
                'location_type' => 'barony',
                'energy_cost' => 16,
                'base_wage' => 75,
                'xp_reward' => 22,
                'xp_skill' => 'crafting',
                'cooldown_minutes' => 40,
                'supervisor_role_slug' => 'castellan',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Stone Block',
                'production_chance' => 20,
                'production_quantity' => 1,
            ],
            [
                'name' => 'Groundskeeper',
                'icon' => 'tree-deciduous',
                'description' => 'Maintain the castle grounds and gardens.',
                'category' => 'labor',
                'location_type' => 'barony',
                'energy_cost' => 12,
                'base_wage' => 45,
                'xp_reward' => 14,
                'xp_skill' => null,
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'castellan',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Kitchen Hand',
                'icon' => 'chef-hat',
                'description' => 'Assist in the castle kitchen preparing meals.',
                'category' => 'labor',
                'location_type' => 'barony',
                'energy_cost' => 10,
                'base_wage' => 45,
                'xp_reward' => 15,
                'xp_skill' => 'cooking',
                'cooldown_minutes' => 25,
                'supervisor_role_slug' => 'master_cook',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Cooked Meat',
                'production_chance' => 20,
                'production_quantity' => 1,
            ],
            [
                'name' => 'Armorer',
                'icon' => 'shield-check',
                'description' => 'Maintain and repair weapons and armor for the garrison.',
                'category' => 'skilled',
                'location_type' => 'barony',
                'energy_cost' => 14,
                'base_wage' => 80,
                'xp_reward' => 28,
                'xp_skill' => 'smithing',
                'required_skill' => 'smithing',
                'required_skill_level' => 10,
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'marshal',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Iron Bar',
                'production_chance' => 25,
                'production_quantity' => 1,
            ],
            [
                'name' => "Falconer's Assistant",
                'icon' => 'bird',
                'description' => 'Care for and train hunting birds for the nobility.',
                'category' => 'skilled',
                'location_type' => 'barony',
                'energy_cost' => 10,
                'base_wage' => 60,
                'xp_reward' => 18,
                'xp_skill' => 'range',
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'marshal',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Huntsman',
                'icon' => 'crosshair',
                'description' => 'Assist with hunts and track game for the lord.',
                'category' => 'skilled',
                'location_type' => 'barony',
                'energy_cost' => 14,
                'base_wage' => 65,
                'xp_reward' => 22,
                'xp_skill' => 'range',
                'required_skill' => 'range',
                'required_skill_level' => 5,
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'marshal',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Raw Meat',
                'production_chance' => 30,
                'production_quantity' => 1,
            ],
            [
                'name' => 'Castle Cook',
                'icon' => 'soup',
                'description' => 'Prepare fine meals for the baron and his guests.',
                'category' => 'skilled',
                'location_type' => 'barony',
                'energy_cost' => 12,
                'base_wage' => 70,
                'xp_reward' => 25,
                'xp_skill' => 'cooking',
                'required_skill' => 'cooking',
                'required_skill_level' => 10,
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'master_cook',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Cooked Meat',
                'production_chance' => 30,
                'production_quantity' => 1,
            ],
            [
                'name' => "Chronicler's Assistant",
                'icon' => 'book-open',
                'description' => 'Record events and maintain the castle archives.',
                'category' => 'skilled',
                'location_type' => 'barony',
                'energy_cost' => 8,
                'base_wage' => 65,
                'xp_reward' => 16,
                'xp_skill' => null,
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'court_chaplain',
                'supervisor_cut_percent' => 10,
            ],

            // Barony woodcutting/strength/hitpoints jobs
            [
                'name' => 'Castle Woodcutter',
                'icon' => 'axe',
                'description' => 'Supply firewood for the castle and its many hearths.',
                'category' => 'labor',
                'location_type' => 'barony',
                'energy_cost' => 12,
                'base_wage' => 55,
                'xp_reward' => 18,
                'xp_skill' => 'woodcutting',
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'castellan',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Wood',
                'production_chance' => 40,
                'production_quantity' => 2,
            ],
            [
                'name' => 'Siege Engineer',
                'icon' => 'hammer',
                'description' => 'Build and maintain siege equipment and fortifications.',
                'category' => 'skilled',
                'location_type' => 'barony',
                'energy_cost' => 14,
                'base_wage' => 75,
                'xp_reward' => 22,
                'xp_skill' => 'woodcutting',
                'required_skill' => 'woodcutting',
                'required_skill_level' => 10,
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'marshal',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Oak Wood',
                'production_chance' => 30,
                'production_quantity' => 1,
            ],
            [
                'name' => 'Fortress Builder',
                'icon' => 'castle',
                'description' => 'Heavy labor constructing and repairing castle walls.',
                'category' => 'labor',
                'location_type' => 'barony',
                'energy_cost' => 16,
                'base_wage' => 65,
                'xp_reward' => 25,
                'xp_skill' => 'strength',
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'castellan',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Stone Block',
                'production_chance' => 20,
                'production_quantity' => 1,
            ],
            [
                'name' => 'Wagon Driver',
                'icon' => 'truck',
                'description' => 'Haul supplies and goods to and from the castle.',
                'category' => 'labor',
                'location_type' => 'barony',
                'energy_cost' => 12,
                'base_wage' => 55,
                'xp_reward' => 18,
                'xp_skill' => 'strength',
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'steward',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Scout',
                'icon' => 'eye',
                'description' => 'Patrol the surrounding lands and report on threats.',
                'category' => 'service',
                'location_type' => 'barony',
                'energy_cost' => 14,
                'base_wage' => 60,
                'xp_reward' => 20,
                'xp_skill' => 'hitpoints',
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'marshal',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Drill Instructor',
                'icon' => 'dumbbell',
                'description' => 'Train soldiers and keep them in fighting shape.',
                'category' => 'skilled',
                'location_type' => 'barony',
                'energy_cost' => 14,
                'base_wage' => 70,
                'xp_reward' => 22,
                'xp_skill' => 'hitpoints',
                'required_level' => 8,
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'marshal',
                'supervisor_cut_percent' => 10,
            ],

            // ===================
            // DUCHY JOBS
            // ===================
            [
                'name' => 'Ducal Guard',
                'icon' => 'shield',
                'description' => 'Elite guard protecting the Duke and his court.',
                'category' => 'service',
                'location_type' => 'duchy',
                'energy_cost' => 12,
                'base_wage' => 90,
                'xp_reward' => 28,
                'xp_skill' => 'defense',
                'required_level' => 10,
                'cooldown_minutes' => 40,
                'supervisor_role_slug' => 'duchy_marshal',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Ducal Servant',
                'icon' => 'sparkles',
                'description' => 'Serve in the Duke\'s personal household.',
                'category' => 'service',
                'location_type' => 'duchy',
                'energy_cost' => 10,
                'base_wage' => 60,
                'xp_reward' => 15,
                'xp_skill' => null,
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'duchy_chancellor',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Diplomatic Courier',
                'icon' => 'mail',
                'description' => 'Carry sensitive messages between noble courts.',
                'category' => 'service',
                'location_type' => 'duchy',
                'energy_cost' => 14,
                'base_wage' => 75,
                'xp_reward' => 20,
                'xp_skill' => null,
                'required_level' => 5,
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'duchy_chancellor',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Court Attendant',
                'icon' => 'crown',
                'description' => 'Assist with court functions and ceremonies.',
                'category' => 'service',
                'location_type' => 'duchy',
                'energy_cost' => 10,
                'base_wage' => 65,
                'xp_reward' => 16,
                'xp_skill' => null,
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'duchy_chancellor',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Palace Stable Master',
                'icon' => 'horse',
                'description' => 'Oversee the Duke\'s prized horses and stables.',
                'category' => 'service',
                'location_type' => 'duchy',
                'energy_cost' => 12,
                'base_wage' => 70,
                'xp_reward' => 18,
                'xp_skill' => null,
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'ducal_stablemaster',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Palace Groundskeeper',
                'icon' => 'tree-deciduous',
                'description' => 'Maintain the grand gardens and grounds of the ducal palace.',
                'category' => 'labor',
                'location_type' => 'duchy',
                'energy_cost' => 14,
                'base_wage' => 65,
                'xp_reward' => 18,
                'xp_skill' => null,
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'duchy_chancellor',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Construction Foreman',
                'icon' => 'hard-hat',
                'description' => 'Oversee building projects throughout the duchy.',
                'category' => 'labor',
                'location_type' => 'duchy',
                'energy_cost' => 16,
                'base_wage' => 95,
                'xp_reward' => 25,
                'xp_skill' => 'crafting',
                'required_skill' => 'crafting',
                'required_skill_level' => 10,
                'cooldown_minutes' => 40,
                'supervisor_role_slug' => 'duchy_chancellor',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Stone Block',
                'production_chance' => 25,
                'production_quantity' => 1,
            ],
            [
                'name' => 'Palace Kitchen Staff',
                'icon' => 'chef-hat',
                'description' => 'Work in the grand kitchens preparing feasts.',
                'category' => 'labor',
                'location_type' => 'duchy',
                'energy_cost' => 12,
                'base_wage' => 60,
                'xp_reward' => 18,
                'xp_skill' => 'cooking',
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'duchy_chef',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Cooked Meat',
                'production_chance' => 25,
                'production_quantity' => 1,
            ],
            [
                'name' => 'Ducal Armorer',
                'icon' => 'shield-check',
                'description' => 'Craft and maintain the finest arms and armor for the Duke\'s forces.',
                'category' => 'skilled',
                'location_type' => 'duchy',
                'energy_cost' => 16,
                'base_wage' => 110,
                'xp_reward' => 35,
                'xp_skill' => 'smithing',
                'required_skill' => 'smithing',
                'required_skill_level' => 20,
                'cooldown_minutes' => 40,
                'supervisor_role_slug' => 'duchy_marshal',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Steel Bar',
                'production_chance' => 30,
                'production_quantity' => 1,
            ],
            [
                'name' => 'Court Musician',
                'icon' => 'music',
                'description' => 'Entertain the court with music and song.',
                'category' => 'skilled',
                'location_type' => 'duchy',
                'energy_cost' => 10,
                'base_wage' => 80,
                'xp_reward' => 20,
                'xp_skill' => null,
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'duchy_chancellor',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Master Huntsman',
                'icon' => 'crosshair',
                'description' => 'Lead grand hunts for the Duke and his guests.',
                'category' => 'skilled',
                'location_type' => 'duchy',
                'energy_cost' => 16,
                'base_wage' => 90,
                'xp_reward' => 30,
                'xp_skill' => 'range',
                'required_skill' => 'range',
                'required_skill_level' => 15,
                'cooldown_minutes' => 40,
                'supervisor_role_slug' => 'master_of_hunts',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Raw Meat',
                'production_chance' => 40,
                'production_quantity' => 2,
            ],
            [
                'name' => 'Ducal Chef',
                'icon' => 'soup',
                'description' => 'Prepare elaborate feasts for the Duke\'s banquets.',
                'category' => 'skilled',
                'location_type' => 'duchy',
                'energy_cost' => 14,
                'base_wage' => 100,
                'xp_reward' => 32,
                'xp_skill' => 'cooking',
                'required_skill' => 'cooking',
                'required_skill_level' => 20,
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'duchy_chef',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Cooked Meat',
                'production_chance' => 40,
                'production_quantity' => 2,
            ],
            [
                'name' => "Cartographer's Assistant",
                'icon' => 'map',
                'description' => 'Help create and maintain maps of the duchy.',
                'category' => 'skilled',
                'location_type' => 'duchy',
                'energy_cost' => 10,
                'base_wage' => 85,
                'xp_reward' => 22,
                'xp_skill' => null,
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'duchy_chancellor',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Treasury Clerk',
                'icon' => 'coins',
                'description' => 'Manage the duchy\'s finances and tax records.',
                'category' => 'skilled',
                'location_type' => 'duchy',
                'energy_cost' => 10,
                'base_wage' => 95,
                'xp_reward' => 24,
                'xp_skill' => null,
                'required_level' => 8,
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'duchy_treasurer',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Court Physician Assistant',
                'icon' => 'heart-pulse',
                'description' => 'Assist the duchy physician with treatments and remedies.',
                'category' => 'skilled',
                'location_type' => 'duchy',
                'energy_cost' => 12,
                'base_wage' => 90,
                'xp_reward' => 26,
                'xp_skill' => 'crafting',
                'required_skill' => 'crafting',
                'required_skill_level' => 10,
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'duchy_physician',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Healing Potion',
                'production_chance' => 20,
                'production_quantity' => 1,
            ],

            // ===================
            // KINGDOM JOBS
            // ===================
            [
                'name' => 'Royal Guard',
                'icon' => 'shield',
                'description' => 'Elite protector of the King and the royal family.',
                'category' => 'service',
                'location_type' => 'kingdom',
                'energy_cost' => 14,
                'base_wage' => 130,
                'xp_reward' => 35,
                'xp_skill' => 'defense',
                'required_level' => 15,
                'cooldown_minutes' => 45,
                'supervisor_role_slug' => 'lord_marshal',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Royal Servant',
                'icon' => 'sparkles',
                'description' => 'Serve in the King\'s personal household with honor.',
                'category' => 'service',
                'location_type' => 'kingdom',
                'energy_cost' => 10,
                'base_wage' => 85,
                'xp_reward' => 20,
                'xp_skill' => null,
                'required_level' => 5,
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'royal_steward',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Royal Courier',
                'icon' => 'mail',
                'description' => 'Carry royal decrees and sensitive documents across the realm.',
                'category' => 'service',
                'location_type' => 'kingdom',
                'energy_cost' => 16,
                'base_wage' => 100,
                'xp_reward' => 25,
                'xp_skill' => null,
                'required_level' => 10,
                'cooldown_minutes' => 40,
                'supervisor_role_slug' => 'royal_steward',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Royal Herald',
                'icon' => 'megaphone',
                'description' => 'Announce royal proclamations and represent the crown.',
                'category' => 'service',
                'location_type' => 'kingdom',
                'energy_cost' => 10,
                'base_wage' => 90,
                'xp_reward' => 22,
                'xp_skill' => null,
                'required_level' => 8,
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'royal_herald',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Royal Stable Master',
                'icon' => 'horse',
                'description' => 'Oversee the King\'s prized warhorses and royal steeds.',
                'category' => 'service',
                'location_type' => 'kingdom',
                'energy_cost' => 12,
                'base_wage' => 95,
                'xp_reward' => 24,
                'xp_skill' => null,
                'required_level' => 5,
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'royal_stablemaster',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Throne Room Attendant',
                'icon' => 'crown',
                'description' => 'Attend to visitors and maintain order in the throne room.',
                'category' => 'service',
                'location_type' => 'kingdom',
                'energy_cost' => 10,
                'base_wage' => 80,
                'xp_reward' => 18,
                'xp_skill' => null,
                'required_level' => 5,
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'royal_steward',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Royal Groundskeeper',
                'icon' => 'tree-deciduous',
                'description' => 'Maintain the magnificent royal palace gardens.',
                'category' => 'labor',
                'location_type' => 'kingdom',
                'energy_cost' => 14,
                'base_wage' => 85,
                'xp_reward' => 22,
                'xp_skill' => null,
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'royal_steward',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Royal Kitchen Staff',
                'icon' => 'chef-hat',
                'description' => 'Work in the grand royal kitchens preparing feasts.',
                'category' => 'labor',
                'location_type' => 'kingdom',
                'energy_cost' => 12,
                'base_wage' => 80,
                'xp_reward' => 22,
                'xp_skill' => 'cooking',
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'royal_chef',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Cooked Meat',
                'production_chance' => 30,
                'production_quantity' => 2,
            ],
            [
                'name' => 'Royal Construction Overseer',
                'icon' => 'hard-hat',
                'description' => 'Supervise grand construction projects across the kingdom.',
                'category' => 'labor',
                'location_type' => 'kingdom',
                'energy_cost' => 18,
                'base_wage' => 130,
                'xp_reward' => 35,
                'xp_skill' => 'crafting',
                'required_skill' => 'crafting',
                'required_skill_level' => 20,
                'cooldown_minutes' => 45,
                'supervisor_role_slug' => 'royal_steward',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Stone Block',
                'production_chance' => 30,
                'production_quantity' => 2,
            ],
            [
                'name' => 'Royal Armorer',
                'icon' => 'shield-check',
                'description' => 'Forge legendary weapons and armor for the King\'s elite.',
                'category' => 'skilled',
                'location_type' => 'kingdom',
                'energy_cost' => 18,
                'base_wage' => 150,
                'xp_reward' => 45,
                'xp_skill' => 'smithing',
                'required_skill' => 'smithing',
                'required_skill_level' => 30,
                'cooldown_minutes' => 45,
                'supervisor_role_slug' => 'lord_marshal',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Steel Bar',
                'production_chance' => 35,
                'production_quantity' => 1,
            ],
            [
                'name' => 'Royal Chef',
                'icon' => 'soup',
                'description' => 'Prepare exquisite meals fit for royalty.',
                'category' => 'skilled',
                'location_type' => 'kingdom',
                'energy_cost' => 14,
                'base_wage' => 135,
                'xp_reward' => 40,
                'xp_skill' => 'cooking',
                'required_skill' => 'cooking',
                'required_skill_level' => 30,
                'cooldown_minutes' => 40,
                'supervisor_role_slug' => 'royal_chef',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Cooked Meat',
                'production_chance' => 45,
                'production_quantity' => 2,
            ],
            [
                'name' => 'Court Composer',
                'icon' => 'music',
                'description' => 'Create magnificent music for royal ceremonies and celebrations.',
                'category' => 'skilled',
                'location_type' => 'kingdom',
                'energy_cost' => 12,
                'base_wage' => 110,
                'xp_reward' => 28,
                'xp_skill' => null,
                'required_level' => 10,
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'royal_steward',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Royal Treasurer\'s Assistant',
                'icon' => 'coins',
                'description' => 'Help manage the kingdom\'s vast treasury and finances.',
                'category' => 'skilled',
                'location_type' => 'kingdom',
                'energy_cost' => 12,
                'base_wage' => 125,
                'xp_reward' => 30,
                'xp_skill' => null,
                'required_level' => 12,
                'cooldown_minutes' => 40,
                'supervisor_role_slug' => 'royal_treasurer',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Royal Physician\'s Assistant',
                'icon' => 'heart-pulse',
                'description' => 'Assist in caring for the royal family\'s health.',
                'category' => 'skilled',
                'location_type' => 'kingdom',
                'energy_cost' => 14,
                'base_wage' => 120,
                'xp_reward' => 32,
                'xp_skill' => 'crafting',
                'required_skill' => 'crafting',
                'required_skill_level' => 20,
                'cooldown_minutes' => 40,
                'supervisor_role_slug' => 'royal_physician',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Healing Potion',
                'production_chance' => 25,
                'production_quantity' => 1,
            ],
            [
                'name' => 'Royal Cartographer',
                'icon' => 'map',
                'description' => 'Map the entire kingdom and chart new territories.',
                'category' => 'skilled',
                'location_type' => 'kingdom',
                'energy_cost' => 12,
                'base_wage' => 115,
                'xp_reward' => 28,
                'xp_skill' => null,
                'required_level' => 10,
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'royal_steward',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Master of Ceremonies Assistant',
                'icon' => 'party-popper',
                'description' => 'Help organize grand royal events and celebrations.',
                'category' => 'skilled',
                'location_type' => 'kingdom',
                'energy_cost' => 12,
                'base_wage' => 105,
                'xp_reward' => 26,
                'xp_skill' => null,
                'required_level' => 8,
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'royal_steward',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Royal Huntmaster',
                'icon' => 'crosshair',
                'description' => 'Lead the King\'s legendary hunts across the realm.',
                'category' => 'skilled',
                'location_type' => 'kingdom',
                'energy_cost' => 18,
                'base_wage' => 125,
                'xp_reward' => 38,
                'xp_skill' => 'range',
                'required_skill' => 'range',
                'required_skill_level' => 25,
                'cooldown_minutes' => 45,
                'supervisor_role_slug' => 'royal_steward',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Raw Meat',
                'production_chance' => 50,
                'production_quantity' => 3,
            ],

            // ===================
            // TOWN JOBS
            // ===================
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
                'supervisor_role_slug' => 'market_warden',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Inn Server',
                'icon' => 'beer',
                'description' => 'Serve food and drinks to weary travelers at the inn.',
                'category' => 'service',
                'location_type' => 'town',
                'energy_cost' => 10,
                'base_wage' => 50,
                'xp_reward' => 12,
                'xp_skill' => null,
                'cooldown_minutes' => 25,
                'supervisor_role_slug' => 'head_chef',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Bank Clerk',
                'icon' => 'landmark',
                'description' => 'Handle deposits, withdrawals, and financial records.',
                'category' => 'service',
                'location_type' => 'town',
                'energy_cost' => 8,
                'base_wage' => 65,
                'xp_reward' => 15,
                'xp_skill' => null,
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'town_clerk',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Courier',
                'icon' => 'mail',
                'description' => 'Deliver messages and small packages throughout town.',
                'category' => 'service',
                'location_type' => 'town',
                'energy_cost' => 12,
                'base_wage' => 45,
                'xp_reward' => 18,
                'xp_skill' => null,
                'cooldown_minutes' => 20,
                'supervisor_role_slug' => 'mayor',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Town Stable Hand',
                'icon' => 'horse',
                'description' => 'Care for horses and maintain the town stables.',
                'category' => 'service',
                'location_type' => 'town',
                'energy_cost' => 10,
                'base_wage' => 45,
                'xp_reward' => 14,
                'xp_skill' => null,
                'cooldown_minutes' => 28,
                'supervisor_role_slug' => 'stablemaster',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Dockworker',
                'icon' => 'anchor',
                'description' => 'Load and unload cargo from ships at the harbor.',
                'category' => 'labor',
                'location_type' => 'town',
                'energy_cost' => 15,
                'base_wage' => 70,
                'xp_reward' => 20,
                'xp_skill' => 'strength',
                'cooldown_minutes' => 35,
                'supervisor_role_slug' => 'harbormaster',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Warehouse Worker',
                'icon' => 'package',
                'description' => 'Organize and move goods in the town warehouses.',
                'category' => 'labor',
                'location_type' => 'town',
                'energy_cost' => 14,
                'base_wage' => 60,
                'xp_reward' => 18,
                'xp_skill' => 'strength',
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'harbormaster',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Street Sweeper',
                'icon' => 'trash-2',
                'description' => 'Keep the town streets clean and tidy.',
                'category' => 'labor',
                'location_type' => 'town',
                'energy_cost' => 10,
                'base_wage' => 35,
                'xp_reward' => 10,
                'xp_skill' => null,
                'cooldown_minutes' => 25,
                'supervisor_role_slug' => 'mayor',
                'supervisor_cut_percent' => 10,
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
                'supervisor_role_slug' => 'master_blacksmith',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Iron Bar',
                'production_chance' => 25,
                'production_quantity' => 1,
            ],
            [
                'name' => 'Apothecary Assistant',
                'icon' => 'flask-conical',
                'description' => 'Help the alchemist prepare potions and remedies.',
                'category' => 'skilled',
                'location_type' => 'town',
                'energy_cost' => 10,
                'base_wage' => 70,
                'xp_reward' => 22,
                'xp_skill' => 'crafting',
                'required_skill' => 'crafting',
                'required_skill_level' => 3,
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'alchemist',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Healing Potion',
                'production_chance' => 15,
                'production_quantity' => 1,
            ],
            [
                'name' => "Jeweler's Apprentice",
                'icon' => 'gem',
                'description' => 'Learn the art of jewelry making under a master jeweler.',
                'category' => 'skilled',
                'location_type' => 'town',
                'energy_cost' => 10,
                'base_wage' => 65,
                'xp_reward' => 20,
                'xp_skill' => 'crafting',
                'required_skill' => 'crafting',
                'required_skill_level' => 5,
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'master_jeweler',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => "Tailor's Assistant",
                'icon' => 'scissors',
                'description' => 'Help create and mend clothing and fabric goods.',
                'category' => 'skilled',
                'location_type' => 'town',
                'energy_cost' => 8,
                'base_wage' => 55,
                'xp_reward' => 18,
                'xp_skill' => 'crafting',
                'required_skill' => 'crafting',
                'required_skill_level' => 3,
                'cooldown_minutes' => 25,
                'supervisor_role_slug' => 'master_tailor',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => "Scribe's Assistant",
                'icon' => 'pen-tool',
                'description' => 'Copy documents and assist with record keeping.',
                'category' => 'skilled',
                'location_type' => 'town',
                'energy_cost' => 8,
                'base_wage' => 60,
                'xp_reward' => 15,
                'xp_skill' => null,
                'cooldown_minutes' => 25,
                'supervisor_role_slug' => 'scribe',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Brewery Worker',
                'icon' => 'wine',
                'description' => 'Assist in brewing ales, meads, and other beverages.',
                'category' => 'skilled',
                'location_type' => 'town',
                'energy_cost' => 12,
                'base_wage' => 60,
                'xp_reward' => 20,
                'xp_skill' => 'cooking',
                'required_skill' => 'cooking',
                'required_skill_level' => 5,
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'brewmaster',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Ale',
                'production_chance' => 25,
                'production_quantity' => 1,
            ],

            // Town woodcutting/strength/hitpoints jobs
            [
                'name' => 'Lumber Merchant Assistant',
                'icon' => 'axe',
                'description' => 'Help manage the town lumber yard and process timber.',
                'category' => 'labor',
                'location_type' => 'town',
                'energy_cost' => 12,
                'base_wage' => 55,
                'xp_reward' => 18,
                'xp_skill' => 'woodcutting',
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'market_warden',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Oak Wood',
                'production_chance' => 30,
                'production_quantity' => 1,
            ],
            [
                'name' => 'Carpenter',
                'icon' => 'hammer',
                'description' => 'Build furniture, repair buildings, and craft wooden goods.',
                'category' => 'skilled',
                'location_type' => 'town',
                'energy_cost' => 12,
                'base_wage' => 65,
                'xp_reward' => 22,
                'xp_skill' => 'woodcutting',
                'required_skill' => 'woodcutting',
                'required_skill_level' => 8,
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'master_carpenter',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Oak Wood',
                'production_chance' => 25,
                'production_quantity' => 1,
            ],
            [
                'name' => 'Dock Worker',
                'icon' => 'anchor',
                'description' => 'Load and unload cargo at the town docks.',
                'category' => 'labor',
                'location_type' => 'town',
                'energy_cost' => 14,
                'base_wage' => 55,
                'xp_reward' => 20,
                'xp_skill' => 'strength',
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'harbormaster',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Construction Worker',
                'icon' => 'building',
                'description' => 'Help build and expand the town buildings.',
                'category' => 'labor',
                'location_type' => 'town',
                'energy_cost' => 14,
                'base_wage' => 58,
                'xp_reward' => 20,
                'xp_skill' => 'strength',
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'master_carpenter',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Stone',
                'production_chance' => 20,
                'production_quantity' => 1,
            ],
            [
                'name' => 'Barrel Maker',
                'icon' => 'cylinder',
                'description' => 'Craft barrels for the brewery and merchants.',
                'category' => 'skilled',
                'location_type' => 'town',
                'energy_cost' => 10,
                'base_wage' => 55,
                'xp_reward' => 18,
                'xp_skill' => 'woodcutting',
                'required_skill' => 'woodcutting',
                'required_skill_level' => 5,
                'cooldown_minutes' => 28,
                'supervisor_role_slug' => 'brewmaster',
                'supervisor_cut_percent' => 10,
                'produces_item' => 'Wood',
                'production_chance' => 20,
                'production_quantity' => 1,
            ],
            [
                'name' => 'Town Crier',
                'icon' => 'megaphone',
                'description' => 'Run through town announcing news and proclamations.',
                'category' => 'service',
                'location_type' => 'town',
                'energy_cost' => 10,
                'base_wage' => 45,
                'xp_reward' => 18,
                'xp_skill' => 'hitpoints',
                'cooldown_minutes' => 25,
                'supervisor_role_slug' => 'town_clerk',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Fire Brigade',
                'icon' => 'flame',
                'description' => 'Stand ready to fight fires and rescue citizens.',
                'category' => 'service',
                'location_type' => 'town',
                'energy_cost' => 12,
                'base_wage' => 55,
                'xp_reward' => 20,
                'xp_skill' => 'hitpoints',
                'cooldown_minutes' => 30,
                'supervisor_role_slug' => 'town_guard_captain',
                'supervisor_cut_percent' => 10,
            ],
            [
                'name' => 'Street Sweeper',
                'icon' => 'sparkles',
                'description' => 'Keep the town streets clean and orderly.',
                'category' => 'labor',
                'location_type' => 'town',
                'energy_cost' => 10,
                'base_wage' => 40,
                'xp_reward' => 15,
                'xp_skill' => 'hitpoints',
                'cooldown_minutes' => 25,
                'supervisor_role_slug' => 'town_clerk',
                'supervisor_cut_percent' => 10,
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

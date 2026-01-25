<?php

namespace App\Services;

use App\Models\DiseaseImmunity;
use App\Models\DiseaseInfection;
use App\Models\DiseaseOutbreak;
use App\Models\DiseaseType;
use App\Models\QuarantineOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DiseaseService
{
    /**
     * Start a new disease outbreak at a location.
     */
    public function startOutbreak(
        DiseaseType $diseaseType,
        string $locationType,
        int $locationId
    ): DiseaseOutbreak {
        return DiseaseOutbreak::create([
            'disease_type_id' => $diseaseType->id,
            'location_type' => $locationType,
            'location_id' => $locationId,
            'status' => DiseaseOutbreak::STATUS_EMERGING,
            'started_at' => now(),
        ]);
    }

    /**
     * Infect a user with a disease.
     */
    public function infectUser(
        User $user,
        DiseaseType $diseaseType,
        ?DiseaseOutbreak $outbreak = null
    ): array {
        // Check immunity
        if ($this->hasImmunity($user, $diseaseType)) {
            return ['success' => false, 'message' => 'User is immune to this disease.'];
        }

        // Check if already infected
        $existing = DiseaseInfection::where('user_id', $user->id)
            ->where('disease_type_id', $diseaseType->id)
            ->active()
            ->first();

        if ($existing) {
            return ['success' => false, 'message' => 'User is already infected.'];
        }

        $infection = DiseaseInfection::create([
            'disease_outbreak_id' => $outbreak?->id,
            'disease_type_id' => $diseaseType->id,
            'user_id' => $user->id,
            'status' => DiseaseInfection::STATUS_INCUBATING,
            'severity_modifier' => rand(-20, 20),
            'infected_at' => now(),
        ]);

        if ($outbreak) {
            $outbreak->increment('infected_count');
            if ($outbreak->infected_count > $outbreak->peak_infected) {
                $outbreak->update(['peak_infected' => $outbreak->infected_count]);
            }
        }

        return ['success' => true, 'infection' => $infection];
    }

    /**
     * Check if user has immunity to a disease.
     */
    public function hasImmunity(User $user, DiseaseType $diseaseType): bool
    {
        return DiseaseImmunity::where('user_id', $user->id)
            ->where('disease_type_id', $diseaseType->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Process daily disease tick for all active infections.
     */
    public function processDailyTick(): array
    {
        $results = ['processed' => 0, 'recovered' => 0, 'deceased' => 0, 'new_symptoms' => 0];

        DiseaseInfection::active()
            ->with(['diseaseType', 'user'])
            ->chunk(100, function ($infections) use (&$results) {
                foreach ($infections as $infection) {
                    $result = $this->processInfection($infection);
                    $results['processed']++;
                    if ($result['new_status'] === 'recovered') {
                        $results['recovered']++;
                    }
                    if ($result['new_status'] === 'deceased') {
                        $results['deceased']++;
                    }
                    if ($result['became_symptomatic'] ?? false) {
                        $results['new_symptoms']++;
                    }
                }
            });

        // Update outbreak statuses
        $this->updateOutbreakStatuses();

        // Process disease spread
        $this->processSpread();

        return $results;
    }

    /**
     * Process a single infection.
     */
    protected function processInfection(DiseaseInfection $infection): array
    {
        $type = $infection->diseaseType;
        $result = ['new_status' => $infection->status];

        $infection->increment('days_infected');

        // Handle incubation
        if ($infection->status === DiseaseInfection::STATUS_INCUBATING) {
            if ($infection->days_infected >= $type->incubation_days) {
                $infection->update([
                    'status' => DiseaseInfection::STATUS_SYMPTOMATIC,
                    'symptoms_started_at' => now(),
                ]);
                $result['became_symptomatic'] = true;
            }
            return $result;
        }

        $infection->increment('days_symptomatic');

        // Check for recovery
        $recoveryChance = $this->calculateRecoveryChance($infection);
        if (rand(1, 100) <= $recoveryChance) {
            return $this->recoverFromInfection($infection);
        }

        // Check for death (only if untreated and severe)
        if (!$infection->is_treated && $type->mortality_rate > 0) {
            $mortalityChance = $type->mortality_rate + $infection->severity_modifier;
            $mortalityChance = max(1, min(50, $mortalityChance)); // Cap at 1-50%

            if (rand(1, 100) <= $mortalityChance) {
                return $this->handleDeath($infection);
            }
        }

        // Check if naturally recovering (past duration)
        if ($infection->days_symptomatic >= $type->base_duration_days) {
            $infection->update(['status' => DiseaseInfection::STATUS_RECOVERING]);
            $result['new_status'] = 'recovering';
        }

        return $result;
    }

    /**
     * Calculate recovery chance.
     */
    protected function calculateRecoveryChance(DiseaseInfection $infection): int
    {
        $type = $infection->diseaseType;
        $baseChance = 100 / $type->base_duration_days;

        // Treatment improves recovery
        if ($infection->is_treated) {
            $baseChance *= 2;
        }

        // Recovering status has higher chance
        if ($infection->status === DiseaseInfection::STATUS_RECOVERING) {
            $baseChance *= 3;
        }

        return (int) min(80, $baseChance);
    }

    /**
     * Handle recovery from infection.
     */
    protected function recoverFromInfection(DiseaseInfection $infection): array
    {
        return DB::transaction(function () use ($infection) {
            $infection->update([
                'status' => DiseaseInfection::STATUS_RECOVERED,
                'recovered_at' => now(),
            ]);

            if ($infection->diseaseOutbreak) {
                $infection->diseaseOutbreak->increment('recovered_count');
            }

            // Grant immunity if disease allows
            if ($infection->diseaseType->grants_immunity && $infection->user_id) {
                DiseaseImmunity::create([
                    'disease_type_id' => $infection->disease_type_id,
                    'user_id' => $infection->user_id,
                    'immunity_type' => 'recovered',
                    'acquired_at' => now(),
                ]);
            }

            return ['new_status' => 'recovered'];
        });
    }

    /**
     * Handle death from infection.
     */
    protected function handleDeath(DiseaseInfection $infection): array
    {
        return DB::transaction(function () use ($infection) {
            $infection->update(['status' => DiseaseInfection::STATUS_DECEASED]);

            if ($infection->diseaseOutbreak) {
                $infection->diseaseOutbreak->increment('death_count');
            }

            // Handle player death would go here
            // For now, just mark the infection as deceased

            return ['new_status' => 'deceased'];
        });
    }

    /**
     * Process disease spread.
     */
    protected function processSpread(): void
    {
        // Get active symptomatic infections
        $infections = DiseaseInfection::where('status', DiseaseInfection::STATUS_SYMPTOMATIC)
            ->whereNotNull('user_id')
            ->with(['diseaseType', 'user.homeVillage'])
            ->get();

        foreach ($infections as $infection) {
            if (!$infection->diseaseType->is_contagious) {
                continue;
            }

            $user = $infection->user;
            $village = $user->homeVillage;

            if (!$village) {
                continue;
            }

            // Check if quarantined
            $quarantine = QuarantineOrder::active()
                ->where('location_type', 'village')
                ->where('location_id', $village->id)
                ->first();

            $spreadRate = $infection->diseaseType->base_spread_rate;
            if ($quarantine) {
                $spreadRate = (int) ($spreadRate * 0.3); // 70% reduction
            }

            // Try to spread to other residents
            $residents = $village->residents()
                ->where('id', '!=', $user->id)
                ->inRandomOrder()
                ->limit(5)
                ->get();

            foreach ($residents as $resident) {
                if (rand(1, 100) <= $spreadRate) {
                    $this->infectUser($resident, $infection->diseaseType, $infection->diseaseOutbreak);
                }
            }
        }
    }

    /**
     * Update outbreak statuses based on infection counts.
     */
    protected function updateOutbreakStatuses(): void
    {
        DiseaseOutbreak::active()->each(function ($outbreak) {
            $activeInfections = $outbreak->infections()->active()->count();

            if ($activeInfections === 0) {
                $outbreak->update([
                    'status' => DiseaseOutbreak::STATUS_ENDED,
                    'ended_at' => now(),
                ]);
            } elseif ($activeInfections > $outbreak->peak_infected * 0.8) {
                $outbreak->update(['status' => DiseaseOutbreak::STATUS_ACTIVE]);
            } elseif ($activeInfections < $outbreak->peak_infected * 0.3) {
                $outbreak->update(['status' => DiseaseOutbreak::STATUS_DECLINING]);
            }
        });
    }

    /**
     * Treat a user's infection.
     */
    public function treatInfection(DiseaseInfection $infection): array
    {
        if (!$infection->isActive()) {
            return ['success' => false, 'message' => 'Infection is not active.'];
        }

        if ($infection->is_treated) {
            return ['success' => false, 'message' => 'Already being treated.'];
        }

        $infection->update(['is_treated' => true]);

        return ['success' => true, 'message' => 'Treatment started.'];
    }

    /**
     * Issue a quarantine order.
     */
    public function issueQuarantine(
        DiseaseOutbreak $outbreak,
        string $locationType,
        int $locationId,
        User $orderedBy,
        ?string $reason = null
    ): QuarantineOrder {
        $outbreak->update(['is_quarantined' => true]);

        return QuarantineOrder::create([
            'disease_outbreak_id' => $outbreak->id,
            'location_type' => $locationType,
            'location_id' => $locationId,
            'ordered_by_user_id' => $orderedBy->id,
            'status' => 'active',
            'ordered_at' => now(),
            'reason' => $reason,
        ]);
    }

    /**
     * Lift a quarantine order.
     */
    public function liftQuarantine(QuarantineOrder $order): void
    {
        $order->update([
            'status' => 'lifted',
            'lifted_at' => now(),
        ]);

        // Check if any other quarantines exist for this outbreak
        $hasOtherQuarantines = QuarantineOrder::where('disease_outbreak_id', $order->disease_outbreak_id)
            ->where('id', '!=', $order->id)
            ->active()
            ->exists();

        if (!$hasOtherQuarantines) {
            $order->diseaseOutbreak->update(['is_quarantined' => false]);
        }
    }
}

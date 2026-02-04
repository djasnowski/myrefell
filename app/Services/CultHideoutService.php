<?php

namespace App\Services;

use App\Models\Belief;
use App\Models\CultHideoutProject;
use App\Models\Religion;
use App\Models\ReligionLog;
use App\Models\ReligionMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CultHideoutService
{
    /**
     * Build a new hideout for a cult.
     */
    public function buildHideout(User $player, Religion $cult, string $locationType, int $locationId): array
    {
        if (! $cult->isCult()) {
            return ['success' => false, 'message' => 'Only cults can have hideouts.'];
        }

        // Check membership and prophet status
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $cult->id)
            ->first();

        if (! $membership || ! $membership->isProphet()) {
            return ['success' => false, 'message' => 'Only the Prophet can establish the hideout.'];
        }

        if ($cult->hasHideout()) {
            return ['success' => false, 'message' => 'This cult already has a hideout.'];
        }

        // Validate location type
        if (! in_array($locationType, ['village', 'barony', 'town', 'kingdom'])) {
            return ['success' => false, 'message' => 'Invalid location type.'];
        }

        // Verify player is at this location
        if ($player->current_location_type !== $locationType || $player->current_location_id !== $locationId) {
            return ['success' => false, 'message' => 'You must be at the location to establish a hideout.'];
        }

        return DB::transaction(function () use ($cult, $locationType, $locationId, $player) {
            $cult->update([
                'hideout_tier' => Religion::HIDEOUT_TIER_HIDDEN_CELLAR,
                'hideout_location_type' => $locationType,
                'hideout_location_id' => $locationId,
            ]);

            // Log the action
            $this->logAction($cult, $player, 'hideout_built', 'Established a Hidden Cellar');

            return [
                'success' => true,
                'message' => 'A Hidden Cellar has been established for your cult!',
                'data' => [
                    'tier' => Religion::HIDEOUT_TIER_HIDDEN_CELLAR,
                    'tier_name' => Religion::HIDEOUT_TIERS[1]['name'],
                ],
            ];
        });
    }

    /**
     * Start an upgrade project for the hideout.
     */
    public function startUpgrade(User $player, Religion $cult): array
    {
        if (! $cult->isCult()) {
            return ['success' => false, 'message' => 'Only cults can have hideouts.'];
        }

        // Check membership and prophet status
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $cult->id)
            ->first();

        if (! $membership || ! $membership->isProphet()) {
            return ['success' => false, 'message' => 'Only the Prophet can start hideout upgrades.'];
        }

        if (! $cult->hasHideout()) {
            return ['success' => false, 'message' => 'You must build a hideout first.'];
        }

        if (! $cult->canUpgradeHideout()) {
            if ($cult->activeHideoutProject()->exists()) {
                return ['success' => false, 'message' => 'An upgrade is already in progress.'];
            }

            return ['success' => false, 'message' => 'The hideout is already at maximum tier.'];
        }

        $nextTier = $cult->hideout_tier + 1;
        $upgradeCost = Religion::HIDEOUT_TIERS[$nextTier];

        return DB::transaction(function () use ($cult, $player, $nextTier, $upgradeCost) {
            $project = CultHideoutProject::create([
                'religion_id' => $cult->id,
                'project_type' => CultHideoutProject::TYPE_UPGRADE,
                'target_tier' => $nextTier,
                'gold_required' => $upgradeCost['gold'],
                'devotion_required' => $upgradeCost['devotion'],
                'started_by' => $player->id,
            ]);

            $this->logAction($cult, $player, 'hideout_upgrade_started', "Started upgrade to {$upgradeCost['name']}");

            return [
                'success' => true,
                'message' => "Upgrade to {$upgradeCost['name']} has been started!",
                'data' => ['project' => $this->formatProject($project)],
            ];
        });
    }

    /**
     * Contribute to an active hideout project.
     */
    public function contributeToProject(User $player, CultHideoutProject $project, array $contributions): array
    {
        if (! $project->isActive()) {
            if ($project->isConstructing()) {
                return ['success' => false, 'message' => 'This project is under construction.'];
            }

            return ['success' => false, 'message' => 'This project is no longer active.'];
        }

        $cult = $project->religion;

        // Check membership
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $cult->id)
            ->first();

        if (! $membership) {
            return ['success' => false, 'message' => 'You are not a member of this cult.'];
        }

        $goldToContribute = $contributions['gold'] ?? 0;
        $devotionToContribute = $contributions['devotion'] ?? 0;

        // Calculate what's actually needed
        $goldNeeded = max(0, $project->gold_required - $project->gold_invested);
        $devotionNeeded = max(0, $project->devotion_required - $project->devotion_invested);

        // Cap at what's needed
        $goldActual = min($goldToContribute, $goldNeeded);
        $devotionActual = min($devotionToContribute, $devotionNeeded);

        // Validate player has the resources
        if ($goldActual > 0 && $player->gold < $goldActual) {
            return ['success' => false, 'message' => 'You do not have enough gold.'];
        }

        if ($devotionActual > 0 && $membership->devotion < $devotionActual) {
            return ['success' => false, 'message' => 'You do not have enough devotion.'];
        }

        if ($goldActual === 0 && $devotionActual === 0) {
            return ['success' => false, 'message' => 'This project has already received enough contributions.'];
        }

        return DB::transaction(function () use ($player, $membership, $project, $goldActual, $devotionActual, $goldToContribute, $devotionToContribute) {
            // Deduct resources
            if ($goldActual > 0) {
                $player->decrement('gold', $goldActual);
            }

            if ($devotionActual > 0) {
                $membership->decrement('devotion', $devotionActual);
            }

            // Add contribution to project
            $result = $project->contribute($goldActual, $devotionActual);

            // Check if requirements are met - start construction timer
            $constructionStarted = false;
            if ($project->requirementsMet()) {
                $project->startConstructionTimer();
                $constructionStarted = true;
            }

            // Build response message
            $message = 'Contribution added!';
            if ($result['gold_added'] > 0) {
                $message .= " {$result['gold_added']} gold.";
            }
            if ($result['devotion_added'] > 0) {
                $message .= " {$result['devotion_added']} devotion.";
            }

            // Notify about overage
            $goldOverage = $goldToContribute - $goldActual;
            $devotionOverage = $devotionToContribute - $devotionActual;
            if ($goldOverage > 0 || $devotionOverage > 0) {
                $overageParts = [];
                if ($goldOverage > 0) {
                    $overageParts[] = number_format($goldOverage).' gold';
                }
                if ($devotionOverage > 0) {
                    $overageParts[] = number_format($devotionOverage).' devotion';
                }
                $message .= ' Overage returned: '.implode(', ', $overageParts).'.';
            }

            if ($constructionStarted) {
                $hours = $project->getConstructionTimeHours();
                if ($hours > 0) {
                    $timeStr = $hours >= 24 ? ($hours / 24).' day(s)' : $hours.' hour(s)';
                    $message .= " Construction has begun! Completion in {$timeStr}.";
                } else {
                    $message .= ' Construction complete!';
                    // Instant completion for tier 1
                    $this->finalizeProject($project);
                }
            }

            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'gold_contributed' => $result['gold_added'],
                    'devotion_contributed' => $result['devotion_added'],
                    'construction_started' => $constructionStarted,
                    'construction_ends_at' => $project->construction_ends_at?->toIso8601String(),
                    'progress' => $project->progress,
                ],
            ];
        });
    }

    /**
     * Finalize a construction project.
     */
    public function finalizeProject(CultHideoutProject $project): void
    {
        if ($project->status === CultHideoutProject::STATUS_COMPLETED) {
            return;
        }

        $cult = $project->religion;

        DB::transaction(function () use ($project, $cult) {
            // Apply the upgrade
            $cult->update([
                'hideout_tier' => $project->target_tier,
            ]);

            $project->complete();

            $tierName = Religion::HIDEOUT_TIERS[$project->target_tier]['name'] ?? 'Unknown';
            $this->logAction($cult, null, 'hideout_upgraded', "Hideout upgraded to {$tierName}");
        });
    }

    /**
     * Get hideout information for a cult.
     */
    public function getHideoutInfo(Religion $cult, User $player): array
    {
        if (! $cult->isCult()) {
            return ['exists' => false, 'is_cult' => false];
        }

        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $cult->id)
            ->first();

        $isMember = $membership !== null;
        $isProphet = $membership?->isProphet() ?? false;
        $isOfficer = $membership?->isOfficer() ?? false;

        if (! $cult->hasHideout()) {
            return [
                'exists' => false,
                'is_cult' => true,
                'is_member' => $isMember,
                'is_prophet' => $isProphet,
                'is_officer' => $isOfficer,
                'player_devotion' => $membership?->devotion ?? 0,
                'can_build' => $isProphet,
            ];
        }

        // Get available cult beliefs based on hideout tier
        $availableBeliefs = Belief::availableForHideoutTier($cult->hideout_tier)
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'description' => $b->description,
                'icon' => $b->icon,
                'type' => $b->type,
                'effects' => $b->effects,
                'required_hideout_tier' => $b->required_hideout_tier,
                'hp_cost' => $b->getHpCost(),
                'energy_cost' => $b->getEnergyCost(),
            ]);

        // Get all cult beliefs with lock status
        $allCultBeliefs = Belief::cultOnly()
            ->orderBy('required_hideout_tier')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'description' => $b->description,
                'icon' => $b->icon,
                'type' => $b->type,
                'effects' => $b->effects,
                'required_hideout_tier' => $b->required_hideout_tier,
                'hp_cost' => $b->getHpCost(),
                'energy_cost' => $b->getEnergyCost(),
                'is_unlocked' => $b->required_hideout_tier <= $cult->hideout_tier,
                'tier_name' => Religion::HIDEOUT_TIERS[$b->required_hideout_tier]['name'] ?? 'Unknown',
            ]);

        // Get active project
        $activeProject = $cult->activeHideoutProject;

        return [
            'exists' => true,
            'is_cult' => true,
            'is_member' => $isMember,
            'is_prophet' => $isProphet,
            'is_officer' => $isOfficer,
            'player_devotion' => $membership?->devotion ?? 0,
            'tier' => $cult->hideout_tier,
            'tier_name' => $cult->getHideoutName(),
            'location_type' => $cult->hideout_location_type,
            'location_id' => $cult->hideout_location_id,
            'location_name' => $cult->hideout_location_name,
            'max_tier' => Religion::HIDEOUT_MAX_TIER,
            'can_upgrade' => $cult->canUpgradeHideout() && $isProphet,
            'upgrade_cost' => $cult->getHideoutUpgradeCost(),
            'next_tier_name' => Religion::HIDEOUT_TIERS[$cult->hideout_tier + 1]['name'] ?? null,
            'is_at_hideout' => $player->current_location_type === $cult->hideout_location_type
                && $player->current_location_id === $cult->hideout_location_id,
            'available_beliefs' => $availableBeliefs,
            'all_cult_beliefs' => $allCultBeliefs,
            'active_project' => $activeProject ? $this->formatProject($activeProject) : null,
            'tier_progression' => $this->getTierProgression($cult->hideout_tier),
        ];
    }

    /**
     * Get tier progression info.
     */
    protected function getTierProgression(int $currentTier): array
    {
        $progression = [];

        foreach (Religion::HIDEOUT_TIERS as $tier => $info) {
            $unlockedBeliefs = Belief::cultOnly()
                ->where('required_hideout_tier', $tier)
                ->pluck('name')
                ->toArray();

            $progression[] = [
                'tier' => $tier,
                'name' => $info['name'],
                'gold' => $info['gold'],
                'devotion' => $info['devotion'],
                'is_current' => $tier === $currentTier,
                'is_unlocked' => $tier <= $currentTier,
                'unlocked_beliefs' => $unlockedBeliefs,
            ];
        }

        return $progression;
    }

    /**
     * Format a project for API response.
     */
    protected function formatProject(CultHideoutProject $project): array
    {
        return [
            'id' => $project->id,
            'project_type' => $project->project_type,
            'project_type_display' => $project->project_type_display,
            'description' => $project->description,
            'target_tier' => $project->target_tier,
            'target_tier_name' => Religion::HIDEOUT_TIERS[$project->target_tier]['name'] ?? 'Unknown',
            'status' => $project->status,
            'progress' => $project->progress,
            'gold_required' => $project->gold_required,
            'gold_invested' => $project->gold_invested,
            'devotion_required' => $project->devotion_required,
            'devotion_invested' => $project->devotion_invested,
            'construction_ends_at' => $project->construction_ends_at?->toIso8601String(),
            'remaining_time' => $project->remaining_time,
            'remaining_seconds' => $project->remaining_seconds,
            'is_constructing' => $project->isConstructing(),
            'is_construction_complete' => $project->isConstructionComplete(),
        ];
    }

    /**
     * Log a hideout action.
     */
    protected function logAction(Religion $cult, ?User $player, string $eventType, string $description): void
    {
        ReligionLog::log(
            $cult->id,
            $eventType,
            $description,
            $player?->id
        );
    }
}

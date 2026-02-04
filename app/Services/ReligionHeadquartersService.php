<?php

namespace App\Services;

use App\Models\HqConstructionProject;
use App\Models\HqFeatureType;
use App\Models\PlayerFeatureBuff;
use App\Models\Religion;
use App\Models\ReligionHeadquarters;
use App\Models\ReligionHqFeature;
use App\Models\ReligionMember;
use App\Models\ReligionTreasury;
use App\Models\ReligionTreasuryTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReligionHeadquartersService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Donate gold to the religion treasury.
     */
    public function donateToTreasury(User $player, Religion $religion, int $amount): array
    {
        if ($amount < 1) {
            return ['success' => false, 'message' => 'Donation amount must be positive.'];
        }

        if ($player->gold < $amount) {
            return ['success' => false, 'message' => 'You do not have enough gold.'];
        }

        // Check membership
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religion->id)
            ->first();

        if (! $membership) {
            return ['success' => false, 'message' => 'You are not a member of this religion.'];
        }

        return DB::transaction(function () use ($player, $religion, $amount) {
            // Deduct gold from player
            $player->decrement('gold', $amount);

            // Get or create treasury
            $treasury = ReligionTreasury::getOrCreate($religion);

            // Check for offering box bonus (from prayer buff)
            $bonusPercent = $this->getPrayerBuffEffect($player, 'treasury_bonus');

            $bonusAmount = (int) floor($amount * $bonusPercent / 100);
            $totalDeposit = $amount + $bonusAmount;

            // Deposit into treasury
            $treasury->deposit(
                $totalDeposit,
                ReligionTreasuryTransaction::TYPE_DONATION,
                "Donation from {$player->username}",
                $player->id
            );

            $message = "You donated {$amount} gold to the treasury.";
            if ($bonusAmount > 0) {
                $message .= " (+{$bonusAmount} bonus from Offering Box)";
            }

            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'amount' => $amount,
                    'bonus' => $bonusAmount,
                    'total' => $totalDeposit,
                    'new_balance' => $treasury->balance,
                ],
            ];
        });
    }

    /**
     * Get treasury information for a religion.
     */
    public function getTreasuryInfo(Religion $religion): array
    {
        $treasury = ReligionTreasury::getOrCreate($religion);

        return [
            'balance' => $treasury->balance,
            'total_collected' => $treasury->total_collected,
            'total_distributed' => $treasury->total_distributed,
            'recent_transactions' => $treasury->recentTransactions(10)
                ->with('user:id,username')
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'type' => $t->type,
                    'amount' => $t->amount,
                    'balance_after' => $t->balance_after,
                    'description' => $t->description,
                    'user' => $t->user?->username,
                    'created_at' => $t->created_at->toIso8601String(),
                    'time_ago' => $t->created_at->diffForHumans(),
                ])->toArray(),
        ];
    }

    /**
     * Get headquarters information for a religion.
     */
    public function getHeadquartersInfo(Religion $religion, User $player): array
    {
        $hq = $religion->headquarters;
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religion->id)
            ->first();

        if (! $hq) {
            return [
                'exists' => false,
                'is_member' => $membership !== null,
                'is_prophet' => $membership?->isProphet() ?? false,
                'is_officer' => $membership?->isOfficer() ?? false,
                'player_devotion' => $membership?->devotion ?? 0,
            ];
        }

        $hq->load(['features.featureType', 'activeProjects.featureType']);

        // Get prophet's Prayer level for upgrade requirements
        $prophet = $religion->members()->where('rank', 'prophet')->first();
        $prophetPrayerLevel = 1;
        if ($prophet) {
            $prophetPrayerLevel = \App\Models\PlayerSkill::where('player_id', $prophet->user_id)
                ->where('skill_name', 'prayer')
                ->value('level') ?? 1;
        }

        return [
            'exists' => true,
            'is_member' => $membership !== null,
            'is_prophet' => $membership?->isProphet() ?? false,
            'is_officer' => $membership?->isOfficer() ?? false,
            'player_devotion' => $membership?->devotion ?? 0,
            'prophet_prayer_level' => $prophetPrayerLevel,
            'id' => $hq->id,
            'tier' => $hq->tier,
            'tier_name' => $hq->tier_name,
            'name' => $hq->name ?? $religion->name.' '.$hq->tier_name,
            'is_built' => $hq->isBuilt(),
            'location_type' => $hq->location_type,
            'location_id' => $hq->location_id,
            'location_name' => $hq->location_name,
            'total_devotion_invested' => $hq->total_devotion_invested,
            'total_gold_invested' => $hq->total_gold_invested,
            'can_upgrade' => $hq->canUpgrade(),
            'upgrade_cost' => $hq->getUpgradeCost(),
            'next_tier_name' => ReligionHeadquarters::TIER_NAMES[$hq->tier + 1] ?? null,
            'next_tier_prayer_requirement' => $hq->getNextTierPrayerRequirement(),
            'max_tier' => ReligionHeadquarters::MAX_TIER,
            'tier_bonuses' => $hq->getTierBonuses(),
            'is_at_hq' => $player->current_location_type === $hq->location_type && $player->current_location_id === $hq->location_id,
            'active_buffs' => $this->getActiveBuffsDetailed($player),
            'features' => $hq->features->map(fn ($f) => [
                'id' => $f->id,
                'type_id' => $f->hq_feature_type_id,
                'name' => $f->featureType->name,
                'slug' => $f->featureType->slug,
                'description' => $f->featureType->description,
                'icon' => $f->featureType->icon,
                'category' => $f->featureType->category,
                'level' => $f->level,
                'max_level' => $f->featureType->max_level,
                'effects' => $f->getEffects(),
                'can_upgrade' => $f->canUpgrade(),
                'upgrade_cost' => $f->getUpgradeCost(),
                'next_level_effects' => $f->getNextLevelEffects(),
                'prayer_energy_cost' => $f->getPrayerEnergyCost(),
                'prayer_devotion_cost' => $f->getPrayerDevotionCost(),
                'prayer_duration_minutes' => $f->getPrayerDurationMinutes(),
            ])->toArray(),
            'available_features' => $hq->getAvailableFeatures()->map(fn ($ft) => [
                'id' => $ft->id,
                'slug' => $ft->slug,
                'name' => $ft->name,
                'description' => $ft->description,
                'icon' => $ft->icon,
                'category' => $ft->category,
                'min_hq_tier' => $ft->min_hq_tier,
                'max_level' => $ft->max_level,
                'effects_at_level_1' => $ft->getEffectsForLevel(1),
                'build_cost' => $ft->getCostForLevel(1),
            ])->toArray(),
            'active_projects' => $hq->activeProjects->map(fn ($p) => $this->formatProject($p))->toArray(),
        ];
    }

    /**
     * Build the headquarters at a location.
     */
    public function buildHeadquarters(User $player, Religion $religion, string $locationType, int $locationId): array
    {
        // Check membership and prophet status
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religion->id)
            ->first();

        if (! $membership || ! $membership->isProphet()) {
            return ['success' => false, 'message' => 'Only the Prophet can build the headquarters.'];
        }

        $hq = $religion->headquarters;
        if (! $hq) {
            $hq = ReligionHeadquarters::create([
                'religion_id' => $religion->id,
                'tier' => 1,
            ]);
        }

        if ($hq->isBuilt()) {
            return ['success' => false, 'message' => 'The headquarters has already been built.'];
        }

        // Validate location type
        if (! in_array($locationType, ['village', 'barony', 'town', 'kingdom'])) {
            return ['success' => false, 'message' => 'Invalid location type.'];
        }

        return DB::transaction(function () use ($hq, $locationType, $locationId, $religion) {
            $hq->update([
                'location_type' => $locationType,
                'location_id' => $locationId,
                'name' => $religion->name.' Chapel',
            ]);

            return [
                'success' => true,
                'message' => "The {$religion->name} Chapel has been established!",
            ];
        });
    }

    /**
     * Start an HQ upgrade project.
     */
    public function startHqUpgrade(User $player, Religion $religion): array
    {
        // Check membership and prophet status
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religion->id)
            ->first();

        if (! $membership || ! $membership->isProphet()) {
            return ['success' => false, 'message' => 'Only the Prophet can start upgrades.'];
        }

        $hq = $religion->headquarters;
        if (! $hq || ! $hq->isBuilt()) {
            return ['success' => false, 'message' => 'You must build the headquarters first.'];
        }

        if (! $hq->canUpgrade()) {
            if ($hq->activeHqUpgrade()->exists()) {
                return ['success' => false, 'message' => 'An HQ upgrade is already in progress.'];
            }

            return ['success' => false, 'message' => 'The headquarters is already at maximum tier.'];
        }

        // Check if there's already an HQ upgrade in progress
        if ($hq->activeHqUpgrade()->exists()) {
            return ['success' => false, 'message' => 'An HQ upgrade is already in progress.'];
        }

        $nextTier = $hq->tier + 1;
        $requiredPrayerLevel = ReligionHeadquarters::TIER_PRAYER_REQUIREMENTS[$nextTier] ?? 1;

        // Check prophet's Prayer level
        $prophetPrayerLevel = $player->skills()->where('skill_name', 'prayer')->value('level') ?? 1;
        if ($prophetPrayerLevel < $requiredPrayerLevel) {
            return [
                'success' => false,
                'message' => "Your Prayer level is too low. You need level {$requiredPrayerLevel} Prayer to upgrade to ".ReligionHeadquarters::TIER_NAMES[$nextTier].'.',
            ];
        }

        $upgradeCost = $hq->getUpgradeCost();

        return DB::transaction(function () use ($hq, $player, $nextTier, $upgradeCost) {
            $project = HqConstructionProject::create([
                'religion_hq_id' => $hq->id,
                'project_type' => HqConstructionProject::TYPE_HQ_UPGRADE,
                'target_level' => $nextTier,
                'gold_required' => $upgradeCost['gold'],
                'devotion_required' => $upgradeCost['devotion'],
                'items_required' => $upgradeCost['items'] ?: null,
                'items_invested' => null,
                'started_by' => $player->id,
            ]);

            return [
                'success' => true,
                'message' => 'Upgrade to '.ReligionHeadquarters::TIER_NAMES[$nextTier].' has been started!',
                'data' => ['project' => $this->formatProject($project)],
            ];
        });
    }

    /**
     * Start building a new feature.
     */
    public function startFeatureBuild(User $player, Religion $religion, int $featureTypeId): array
    {
        // Check membership and prophet status
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religion->id)
            ->first();

        if (! $membership || ! $membership->isProphet()) {
            return ['success' => false, 'message' => 'Only the Prophet can start construction.'];
        }

        $hq = $religion->headquarters;
        if (! $hq || ! $hq->isBuilt()) {
            return ['success' => false, 'message' => 'You must build the headquarters first.'];
        }

        // Check if this specific feature is already being built
        if ($hq->hasActiveProjectForFeature($featureTypeId)) {
            return ['success' => false, 'message' => 'This feature is already being constructed.'];
        }

        $featureType = HqFeatureType::find($featureTypeId);
        if (! $featureType) {
            return ['success' => false, 'message' => 'Feature type not found.'];
        }

        if ($featureType->min_hq_tier > $hq->tier) {
            return ['success' => false, 'message' => 'Your headquarters tier is too low for this feature.'];
        }

        // Check if already built
        $existing = ReligionHqFeature::where('religion_hq_id', $hq->id)
            ->where('hq_feature_type_id', $featureTypeId)
            ->first();

        if ($existing) {
            return ['success' => false, 'message' => 'This feature has already been built.'];
        }

        $buildCost = $featureType->getCostForLevel(1);

        return DB::transaction(function () use ($hq, $player, $featureType, $buildCost) {
            $project = HqConstructionProject::create([
                'religion_hq_id' => $hq->id,
                'project_type' => HqConstructionProject::TYPE_FEATURE_BUILD,
                'hq_feature_type_id' => $featureType->id,
                'target_level' => 1,
                'gold_required' => $buildCost['gold'],
                'devotion_required' => $buildCost['devotion'],
                'items_required' => $buildCost['items'] ?: null,
                'items_invested' => null,
                'started_by' => $player->id,
            ]);

            return [
                'success' => true,
                'message' => "Construction of {$featureType->name} has begun!",
                'data' => ['project' => $this->formatProject($project)],
            ];
        });
    }

    /**
     * Start upgrading a feature.
     */
    public function startFeatureUpgrade(User $player, Religion $religion, int $featureId): array
    {
        // Check membership and prophet status
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religion->id)
            ->first();

        if (! $membership || ! $membership->isProphet()) {
            return ['success' => false, 'message' => 'Only the Prophet can start upgrades.'];
        }

        $hq = $religion->headquarters;
        if (! $hq || ! $hq->isBuilt()) {
            return ['success' => false, 'message' => 'Headquarters not found.'];
        }

        $feature = ReligionHqFeature::where('id', $featureId)
            ->where('religion_hq_id', $hq->id)
            ->with('featureType')
            ->first();

        if (! $feature) {
            return ['success' => false, 'message' => 'Feature not found.'];
        }

        if (! $feature->canUpgrade()) {
            return ['success' => false, 'message' => 'This feature is already at maximum level.'];
        }

        // Check if this feature is already being upgraded
        if ($hq->hasActiveProjectForFeature($feature->hq_feature_type_id)) {
            return ['success' => false, 'message' => 'This feature is already being upgraded.'];
        }

        $upgradeCost = $feature->getUpgradeCost();
        $nextLevel = $feature->level + 1;

        return DB::transaction(function () use ($hq, $player, $feature, $upgradeCost, $nextLevel) {
            $project = HqConstructionProject::create([
                'religion_hq_id' => $hq->id,
                'project_type' => HqConstructionProject::TYPE_FEATURE_UPGRADE,
                'hq_feature_type_id' => $feature->hq_feature_type_id,
                'target_level' => $nextLevel,
                'gold_required' => $upgradeCost['gold'],
                'devotion_required' => $upgradeCost['devotion'],
                'items_required' => $upgradeCost['items'] ?: null,
                'items_invested' => null,
                'started_by' => $player->id,
            ]);

            return [
                'success' => true,
                'message' => "Upgrade of {$feature->featureType->name} to Level {$nextLevel} has begun!",
                'data' => ['project' => $this->formatProject($project)],
            ];
        });
    }

    /**
     * Contribute to an active project.
     *
     * @param  array{gold?: int, devotion?: int, items?: array}  $contributions
     */
    public function contributeToProject(User $player, HqConstructionProject $project, array $contributions): array
    {
        if (! $project->isActive()) {
            if ($project->isConstructing()) {
                return ['success' => false, 'message' => 'This project is under construction and cannot accept more contributions.'];
            }

            return ['success' => false, 'message' => 'This project is no longer active.'];
        }

        $hq = $project->headquarters;
        $religion = $hq->religion;

        // Check membership
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religion->id)
            ->first();

        if (! $membership) {
            return ['success' => false, 'message' => 'You are not a member of this religion.'];
        }

        $goldToContribute = $contributions['gold'] ?? 0;
        $devotionToContribute = $contributions['devotion'] ?? 0;
        $itemsToContribute = $contributions['items'] ?? [];

        // Calculate what's actually needed (overage protection)
        $goldNeeded = max(0, $project->gold_required - $project->gold_invested);
        $devotionNeeded = max(0, $project->devotion_required - $project->devotion_invested);

        // Cap at what's needed
        $goldActual = min($goldToContribute, $goldNeeded);
        $devotionActual = min($devotionToContribute, $devotionNeeded);

        // Calculate overage
        $goldOverage = $goldToContribute - $goldActual;
        $devotionOverage = $devotionToContribute - $devotionActual;

        // Validate player has the actual amounts needed
        if ($goldActual > 0 && $player->gold < $goldActual) {
            return ['success' => false, 'message' => 'You do not have enough gold.'];
        }

        if ($devotionActual > 0 && $membership->devotion < $devotionActual) {
            return ['success' => false, 'message' => 'You do not have enough devotion.'];
        }

        // Validate items (cap at what's needed)
        $itemsActual = [];
        $itemsOverage = [];
        if (! empty($itemsToContribute) && ! empty($project->items_required)) {
            $invested = $project->items_invested ?? [];
            foreach ($itemsToContribute as $itemId => $quantity) {
                if (! isset($project->items_required[$itemId])) {
                    $itemsOverage[$itemId] = $quantity;

                    continue;
                }

                $needed = $project->items_required[$itemId] - ($invested[$itemId] ?? 0);
                $actual = min($quantity, max(0, $needed));
                $overage = $quantity - $actual;

                if ($actual > 0) {
                    if (! $this->inventoryService->hasItem($player, $itemId, $actual)) {
                        return ['success' => false, 'message' => 'You do not have the required items.'];
                    }
                    $itemsActual[$itemId] = $actual;
                }

                if ($overage > 0) {
                    $itemsOverage[$itemId] = $overage;
                }
            }
        }

        // Check if there's anything to contribute
        if ($goldActual === 0 && $devotionActual === 0 && empty($itemsActual)) {
            return ['success' => false, 'message' => 'This project has already received enough contributions.'];
        }

        return DB::transaction(function () use ($player, $membership, $project, $hq, $goldActual, $devotionActual, $itemsActual, $goldOverage, $devotionOverage) {
            // Only deduct what's actually being contributed
            if ($goldActual > 0) {
                $player->decrement('gold', $goldActual);
            }

            if ($devotionActual > 0) {
                $membership->decrement('devotion', $devotionActual);
            }

            foreach ($itemsActual as $itemId => $quantity) {
                $this->inventoryService->removeItem($player, $itemId, $quantity);
            }

            // Add contribution to project
            $result = $project->contribute($goldActual, $devotionActual, $itemsActual);

            // Update HQ investment totals
            if ($result['gold_added'] > 0) {
                $hq->increment('total_gold_invested', $result['gold_added']);
            }
            if ($result['devotion_added'] > 0) {
                $hq->increment('total_devotion_invested', $result['devotion_added']);
            }

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
            if (! empty($result['items_added'])) {
                $itemCount = array_sum($result['items_added']);
                $message .= " {$itemCount} items.";
            }

            // Notify about overage
            $hasOverage = $goldOverage > 0 || $devotionOverage > 0;
            if ($hasOverage) {
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
                $timeStr = $hours >= 24 ? ($hours / 24).' day(s)' : $hours.' hour(s)';
                $message .= " Construction has begun! Completion in {$timeStr}.";
            }

            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'gold_contributed' => $result['gold_added'],
                    'devotion_contributed' => $result['devotion_added'],
                    'items_contributed' => $result['items_added'],
                    'gold_overage' => $goldOverage,
                    'devotion_overage' => $devotionOverage,
                    'construction_started' => $constructionStarted,
                    'construction_ends_at' => $project->construction_ends_at?->toIso8601String(),
                    'progress' => $project->progress,
                ],
            ];
        });
    }

    /**
     * Finalize a construction project (called when timer expires).
     * This is the public method called by the scheduled command.
     */
    public function finalizeProject(HqConstructionProject $project): void
    {
        if (! $project->isConstructing()) {
            return;
        }

        $this->applyProjectCompletion($project);
    }

    /**
     * Apply project completion effects (upgrade HQ tier, build feature, etc.).
     */
    protected function applyProjectCompletion(HqConstructionProject $project): void
    {
        $hq = $project->headquarters;

        DB::transaction(function () use ($project, $hq) {
            switch ($project->project_type) {
                case HqConstructionProject::TYPE_HQ_UPGRADE:
                    $hq->update([
                        'tier' => $project->target_level,
                        'name' => $hq->religion->name.' '.ReligionHeadquarters::TIER_NAMES[$project->target_level],
                    ]);
                    break;

                case HqConstructionProject::TYPE_FEATURE_BUILD:
                    ReligionHqFeature::create([
                        'religion_hq_id' => $hq->id,
                        'hq_feature_type_id' => $project->hq_feature_type_id,
                        'level' => 1,
                    ]);
                    break;

                case HqConstructionProject::TYPE_FEATURE_UPGRADE:
                    $feature = ReligionHqFeature::where('religion_hq_id', $hq->id)
                        ->where('hq_feature_type_id', $project->hq_feature_type_id)
                        ->first();

                    if ($feature) {
                        $feature->update(['level' => $project->target_level]);
                    }
                    break;
            }

            $project->complete();
        });
    }

    /**
     * Get all HQ bonuses for a player.
     *
     * @return array<string, int|float>
     */
    public function getMemberBonuses(User $player): array
    {
        $membership = ReligionMember::where('user_id', $player->id)->first();
        if (! $membership) {
            return [];
        }

        $hq = ReligionHeadquarters::where('religion_id', $membership->religion_id)
            ->with('features.featureType')
            ->first();

        if (! $hq || ! $hq->isBuilt()) {
            return [];
        }

        return $hq->getCombinedEffects();
    }

    /**
     * Get blessing cost modifier for a player.
     * Returns a multiplier (e.g., 0.85 for 15% reduction).
     */
    public function getBlessingCostModifier(User $player): float
    {
        // Get passive HQ tier bonus
        $bonuses = $this->getMemberBonuses($player);
        $reduction = $bonuses['blessing_cost_reduction'] ?? 0;

        // Add prayer buff bonus
        $prayerBuffReduction = $this->getPrayerBuffEffect($player, 'blessing_cost_reduction');
        $reduction += $prayerBuffReduction;

        return 1 - ($reduction / 100);
    }

    /**
     * Get a specific effect value from active prayer buffs.
     */
    protected function getPrayerBuffEffect(User $player, string $effectKey): float
    {
        $buffs = PlayerFeatureBuff::where('user_id', $player->id)
            ->active()
            ->get();

        $total = 0;
        foreach ($buffs as $buff) {
            if ($buff->effects && isset($buff->effects[$effectKey])) {
                $total += $buff->effects[$effectKey];
            }
        }

        return $total;
    }

    /**
     * Get blessing duration modifier for a player.
     * Returns a multiplier (e.g., 1.20 for 20% increase).
     */
    public function getBlessingDurationModifier(User $player): float
    {
        $bonuses = $this->getMemberBonuses($player);
        $increase = $bonuses['blessing_duration_bonus'] ?? 0;

        return 1 + ($increase / 100);
    }

    /**
     * Get devotion gain modifier for a player.
     * Returns a multiplier (e.g., 1.15 for 15% increase).
     */
    public function getDevotionGainModifier(User $player): float
    {
        $bonuses = $this->getMemberBonuses($player);
        $increase = $bonuses['devotion_bonus'] ?? 0;

        return 1 + ($increase / 100);
    }

    /**
     * Pray at a feature to receive a temporary buff.
     */
    public function prayAtFeature(User $player, ReligionHqFeature $feature): array
    {
        $hq = $feature->headquarters;
        $religion = $hq->religion;

        // Check membership
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religion->id)
            ->first();

        if (! $membership) {
            return ['success' => false, 'message' => 'You are not a member of this religion.'];
        }

        // Check if player is at the HQ location
        if ($player->current_location_type !== $hq->location_type || $player->current_location_id !== $hq->location_id) {
            return ['success' => false, 'message' => 'You must be at the headquarters to pray at this feature.'];
        }

        $energyCost = $feature->getPrayerEnergyCost();
        $devotionCost = $feature->getPrayerDevotionCost();
        $durationMinutes = $feature->getPrayerDurationMinutes();

        // Check energy
        if ($player->energy < $energyCost) {
            return ['success' => false, 'message' => "You need {$energyCost} energy to pray at this feature."];
        }

        // Check devotion
        if ($membership->devotion < $devotionCost) {
            return ['success' => false, 'message' => "You need {$devotionCost} devotion to pray at this feature."];
        }

        return DB::transaction(function () use ($player, $membership, $feature, $energyCost, $devotionCost, $durationMinutes) {
            // Deduct costs
            $player->decrement('energy', $energyCost);
            $membership->decrement('devotion', $devotionCost);

            // Get the effects
            $effects = $feature->getEffects();

            // Create or update the buff
            PlayerFeatureBuff::updateOrCreate(
                [
                    'user_id' => $player->id,
                    'religion_hq_feature_id' => $feature->id,
                ],
                [
                    'effects' => $effects,
                    'expires_at' => now()->addMinutes($durationMinutes),
                ]
            );

            $featureName = $feature->featureType->name;

            // Check for immediate energy restore effect (Paradise Gardens)
            $energyRestored = 0;
            if (isset($effects['energy_restore']) && $effects['energy_restore'] > 0) {
                $restoreAmount = (int) $effects['energy_restore'];
                $actualRestore = min($restoreAmount, $player->max_energy - $player->energy);
                if ($actualRestore > 0) {
                    $player->increment('energy', $actualRestore);
                    $energyRestored = $actualRestore;
                }
            }

            $message = "You prayed at the {$featureName}. Buff active for {$durationMinutes} minutes.";
            if ($energyRestored > 0) {
                $message .= " Restored {$energyRestored} energy.";
            }

            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'feature_name' => $featureName,
                    'effects' => $effects,
                    'duration_minutes' => $durationMinutes,
                    'expires_at' => now()->addMinutes($durationMinutes)->toIso8601String(),
                    'energy_spent' => $energyCost,
                    'devotion_spent' => $devotionCost,
                    'energy_restored' => $energyRestored,
                ],
            ];
        });
    }

    /**
     * Get all active feature buffs for a player.
     *
     * @return array<string, int|float>
     */
    public function getActiveFeatureBuffs(User $player): array
    {
        $buffs = PlayerFeatureBuff::where('user_id', $player->id)
            ->active()
            ->with('feature.featureType')
            ->get();

        $combinedEffects = [];

        foreach ($buffs as $buff) {
            foreach ($buff->effects as $key => $value) {
                $combinedEffects[$key] = ($combinedEffects[$key] ?? 0) + $value;
            }
        }

        return $combinedEffects;
    }

    /**
     * Get detailed active buffs for display.
     */
    public function getActiveBuffsDetailed(User $player): array
    {
        return PlayerFeatureBuff::where('user_id', $player->id)
            ->active()
            ->with('feature.featureType')
            ->get()
            ->map(fn ($buff) => [
                'id' => $buff->id,
                'feature_id' => $buff->religion_hq_feature_id,
                'feature_name' => $buff->feature->featureType->name,
                'feature_icon' => $buff->feature->featureType->icon,
                'effects' => $buff->effects,
                'expires_at' => $buff->expires_at->toIso8601String(),
                'remaining_time' => $buff->remaining_time,
                'remaining_seconds' => $buff->remaining_seconds,
            ])
            ->toArray();
    }

    /**
     * Format a project for API response.
     */
    protected function formatProject(HqConstructionProject $project): array
    {
        return [
            'id' => $project->id,
            'project_type' => $project->project_type,
            'project_type_display' => $project->project_type_display,
            'description' => $project->description,
            'feature_type' => $project->featureType ? [
                'id' => $project->featureType->id,
                'name' => $project->featureType->name,
                'icon' => $project->featureType->icon,
            ] : null,
            'target_level' => $project->target_level,
            'status' => $project->status,
            'progress' => $project->progress,
            'gold_required' => $project->gold_required,
            'gold_invested' => $project->gold_invested,
            'devotion_required' => $project->devotion_required,
            'devotion_invested' => $project->devotion_invested,
            'items_required' => $project->items_required,
            'items_invested' => $project->items_invested,
            'started_at' => $project->started_at?->toIso8601String(),
            'construction_ends_at' => $project->construction_ends_at?->toIso8601String(),
            'remaining_time' => $project->remaining_time,
            'remaining_seconds' => $project->remaining_seconds,
            'total_construction_seconds' => $project->isConstructing() ? $project->getConstructionTimeHours() * 3600 : null,
            'is_constructing' => $project->isConstructing(),
            'is_construction_complete' => $project->isConstructionComplete(),
        ];
    }
}

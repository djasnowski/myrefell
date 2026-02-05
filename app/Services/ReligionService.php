<?php

namespace App\Services;

use App\Models\Belief;
use App\Models\Item;
use App\Models\Kingdom;
use App\Models\KingdomReligion;
use App\Models\PlayerSkill;
use App\Models\Religion;
use App\Models\ReligionHeadquarters;
use App\Models\ReligionLog;
use App\Models\ReligionMember;
use App\Models\ReligionTreasury;
use App\Models\ReligiousAction;
use App\Models\ReligiousStructure;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReligionService
{
    public function __construct(
        protected EnergyService $energyService,
        protected BankService $bankService,
        protected DailyTaskService $dailyTaskService,
        protected BlessingEffectService $blessingEffects,
        protected BeliefEffectService $beliefEffectService,
        protected InventoryService $inventoryService,
        protected ReligionHeadquartersService $hqService
    ) {}

    /**
     * Get available religions for a player to join.
     */
    public function getAvailableReligions(User $player): array
    {
        // Get religions that are public or where player has been invited
        // Also check kingdom status (banned religions can't be joined)
        $playerKingdomId = $this->getPlayerKingdomId($player);

        $religions = Religion::query()
            ->where('is_active', true)
            ->where('is_public', true)
            ->with(['founder', 'beliefs', 'members'])
            ->get()
            ->filter(function ($religion) use ($player, $playerKingdomId) {
                // Exclude religions player is already a member of
                if ($religion->members->contains('user_id', $player->id)) {
                    return false;
                }

                // Exclude banned religions in player's kingdom
                if ($playerKingdomId) {
                    $status = KingdomReligion::where('kingdom_id', $playerKingdomId)
                        ->where('religion_id', $religion->id)
                        ->first();
                    if ($status && $status->isBanned()) {
                        return false;
                    }
                }

                return true;
            })
            ->map(fn ($religion) => $this->formatReligion($religion))
            ->values()
            ->toArray();

        return $religions;
    }

    /**
     * Get player's religions.
     */
    public function getPlayerReligions(User $player): array
    {
        return ReligionMember::where('user_id', $player->id)
            ->with(['religion', 'religion.beliefs', 'religion.founder'])
            ->get()
            ->map(fn ($member) => $this->formatMembership($member))
            ->toArray();
    }

    /**
     * Get a religion's details.
     */
    public function getReligionDetails(Religion $religion, User $player): array
    {
        $religion->load(['founder', 'beliefs', 'members.user', 'structures']);

        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religion->id)
            ->first();

        $playerKingdomId = $this->getPlayerKingdomId($player);
        $kingdomStatus = null;
        if ($playerKingdomId) {
            $kr = KingdomReligion::where('kingdom_id', $playerKingdomId)
                ->where('religion_id', $religion->id)
                ->first();
            $kingdomStatus = $kr?->status;
        }

        return [
            'religion' => $this->formatReligion($religion),
            'membership' => $membership ? $this->formatMembership($membership) : null,
            'is_member' => $membership !== null,
            'can_join' => $membership === null && $religion->canAcceptMembers() && $kingdomStatus !== 'banned',
            'kingdom_status' => $kingdomStatus,
            'members' => $religion->members->map(fn ($m) => [
                'id' => $m->id,
                'user_id' => $m->user_id,
                'username' => $m->user->username,
                'rank' => $m->rank,
                'rank_display' => $m->rank_display,
                'devotion' => $m->devotion,
                'joined_at' => $m->joined_at->toIso8601String(),
                'can_be_promoted' => $m->canBePromoted(),
                'can_be_demoted' => $m->canBeDemoted(),
            ])->toArray(),
            'structures' => $religion->structures->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'structure_type' => $s->structure_type,
                'type_display' => $s->type_display,
                'location_type' => $s->location_type,
                'location_id' => $s->location_id,
            ])->toArray(),
            'history' => $membership ? $this->getReligionLogs($player, $religion->id, 20) : [],
        ];
    }

    /**
     * Create a new cult.
     */
    public function createCult(User $player, string $name, string $description, array $beliefIds): array
    {
        // Check if player is already a member of any religion
        $existingMembership = ReligionMember::where('user_id', $player->id)->first();
        if ($existingMembership) {
            if ($existingMembership->isProphet()) {
                return ['success' => false, 'message' => 'You are already the prophet of another religion.'];
            }

            return ['success' => false, 'message' => 'You must leave your current religion before founding a cult.'];
        }

        // Validate name
        if (Religion::where('name', $name)->exists()) {
            return ['success' => false, 'message' => 'A religion with that name already exists.'];
        }

        // Validate belief count
        if (count($beliefIds) > Religion::CULT_BELIEF_LIMIT) {
            return ['success' => false, 'message' => 'Cults can only have '.Religion::CULT_BELIEF_LIMIT.' beliefs.'];
        }

        // Validate beliefs exist
        $beliefs = Belief::whereIn('id', $beliefIds)->get();
        if ($beliefs->count() !== count($beliefIds)) {
            return ['success' => false, 'message' => 'Invalid beliefs selected.'];
        }

        return DB::transaction(function () use ($player, $name, $description, $beliefIds) {
            // Create the cult
            $religion = Religion::create([
                'name' => $name,
                'description' => $description,
                'type' => Religion::TYPE_CULT,
                'founder_id' => $player->id,
                'is_public' => false, // Cults are secret by default
                'member_limit' => 0, // No limit
                'founding_cost' => Religion::CULT_FOUNDING_COST,
            ]);

            // Attach beliefs
            $religion->beliefs()->attach($beliefIds);

            // Add founder as prophet
            ReligionMember::create([
                'user_id' => $player->id,
                'religion_id' => $religion->id,
                'rank' => ReligionMember::RANK_PROPHET,
                'devotion' => 0,
                'joined_at' => now(),
            ]);

            // Create treasury for the religion
            ReligionTreasury::create([
                'religion_id' => $religion->id,
                'balance' => 0,
                'total_collected' => 0,
                'total_distributed' => 0,
            ]);

            // Create headquarters record (location to be set by prophet later)
            ReligionHeadquarters::create([
                'religion_id' => $religion->id,
                'tier' => 1,
            ]);

            // Log the founding
            ReligionLog::log(
                $religion->id,
                ReligionLog::EVENT_FOUNDED,
                "{$player->username} founded the cult",
                $player->id
            );

            return [
                'success' => true,
                'message' => "You have founded the cult '{$name}'.",
                'data' => ['religion' => $this->formatReligion($religion->fresh(['beliefs', 'founder']))],
            ];
        });
    }

    /**
     * Join a religion.
     */
    public function joinReligion(User $player, int $religionId): array
    {
        $religion = Religion::find($religionId);
        if (! $religion) {
            return ['success' => false, 'message' => 'Religion not found.'];
        }

        // Check if already a member of any religion
        $existingMembership = ReligionMember::where('user_id', $player->id)->first();
        if ($existingMembership) {
            if ($existingMembership->religion_id === $religionId) {
                return ['success' => false, 'message' => 'You are already a member of this religion.'];
            }

            return ['success' => false, 'message' => 'You must leave your current religion before joining another.'];
        }

        // Check kingdom ban
        $playerKingdomId = $this->getPlayerKingdomId($player);
        if ($playerKingdomId) {
            $status = KingdomReligion::where('kingdom_id', $playerKingdomId)
                ->where('religion_id', $religionId)
                ->first();
            if ($status && $status->isBanned()) {
                return ['success' => false, 'message' => 'This religion is banned in your kingdom.'];
            }
        }

        return DB::transaction(function () use ($player, $religion) {
            ReligionMember::create([
                'user_id' => $player->id,
                'religion_id' => $religion->id,
                'rank' => ReligionMember::RANK_FOLLOWER,
                'devotion' => 0,
                'joined_at' => now(),
            ]);

            // Log the join
            ReligionLog::log(
                $religion->id,
                ReligionLog::EVENT_MEMBER_JOINED,
                "{$player->username} joined as a follower",
                $player->id
            );

            return [
                'success' => true,
                'message' => "You have joined {$religion->name}.",
            ];
        });
    }

    /**
     * Leave a religion.
     */
    public function leaveReligion(User $player, int $religionId): array
    {
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religionId)
            ->first();

        if (! $membership) {
            return ['success' => false, 'message' => 'You are not a member of this religion.'];
        }

        // Prophets cannot leave (must disband instead)
        if ($membership->isProphet()) {
            return ['success' => false, 'message' => 'As the prophet, you must disband the religion instead of leaving.'];
        }

        return DB::transaction(function () use ($membership) {
            $religionId = $membership->religion_id;
            $religionName = $membership->religion->name;
            $username = $membership->user->username;
            $membership->delete();

            // Log the leave
            ReligionLog::log(
                $religionId,
                ReligionLog::EVENT_MEMBER_LEFT,
                "{$username} left the religion",
                $membership->user_id
            );

            return [
                'success' => true,
                'message' => "You have left {$religionName}.",
            ];
        });
    }

    /**
     * Perform a religious action.
     */
    public function performAction(User $player, int $religionId, string $actionType, ?int $structureId = null, int $donationAmount = 0, ?int $sacrificeItemId = null): array
    {
        // Validate action type
        if (! in_array($actionType, ReligiousAction::ACTIONS)) {
            return ['success' => false, 'message' => 'Invalid action type.'];
        }

        // Check membership
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religionId)
            ->first();

        if (! $membership) {
            return ['success' => false, 'message' => 'You are not a member of this religion.'];
        }

        // Check energy
        $energyCost = ReligiousAction::getEnergyCost($actionType);
        if ($energyCost > 0 && ! $this->energyService->hasEnergy($player, $energyCost)) {
            return ['success' => false, 'message' => "You need {$energyCost} energy to perform this action."];
        }

        // Check cooldown (considering Blessing of Haste)
        $cooldown = ReligiousAction::getCooldown($actionType);
        if ($cooldown > 0) {
            $lastAction = ReligiousAction::where('user_id', $player->id)
                ->where('religion_id', $religionId)
                ->where('action_type', $actionType)
                ->latest()
                ->first();

            if ($lastAction) {
                // Check for Blessing of Haste
                $hasteCooldown = $this->blessingEffects->getActionCooldownSeconds($player);

                if ($hasteCooldown !== null) {
                    $availableAt = $lastAction->created_at->addSeconds($hasteCooldown);
                } else {
                    $availableAt = $lastAction->created_at->addMinutes($cooldown);
                }

                if ($availableAt->isFuture()) {
                    $remaining = $availableAt->diffForHumans();

                    return ['success' => false, 'message' => "You can perform this action again {$remaining}."];
                }
            }
        }

        // Handle donation
        $goldSpent = 0;
        if ($actionType === ReligiousAction::ACTION_DONATION) {
            if ($donationAmount < 10) {
                return ['success' => false, 'message' => 'Minimum donation is 10 gold.'];
            }
            if ($player->gold < $donationAmount) {
                return ['success' => false, 'message' => 'You do not have enough gold.'];
            }
            $goldSpent = $donationAmount;
        }

        // Handle sacrifice - requires bones
        $sacrificeItem = null;
        if ($actionType === ReligiousAction::ACTION_SACRIFICE) {
            if (! $sacrificeItemId) {
                return ['success' => false, 'message' => 'You must select bones to sacrifice.'];
            }

            // Get the item and verify it's bones (subtype = remains)
            $sacrificeItem = Item::find($sacrificeItemId);
            if (! $sacrificeItem || $sacrificeItem->subtype !== 'remains') {
                return ['success' => false, 'message' => 'You can only sacrifice bones.'];
            }

            // Check player has the item
            if (! $this->inventoryService->hasItem($player, $sacrificeItemId, 1)) {
                return ['success' => false, 'message' => "You don't have any {$sacrificeItem->name} to sacrifice."];
            }
        }

        // Get structure multiplier
        $multiplier = 1.0;
        $structure = null;
        if ($structureId) {
            $structure = ReligiousStructure::where('id', $structureId)
                ->where('religion_id', $religionId)
                ->where('is_active', true)
                ->first();

            if ($structure) {
                $multiplier = $structure->devotion_multiplier;
            }
        }

        return DB::transaction(function () use ($player, $membership, $actionType, $energyCost, $goldSpent, $multiplier, $structure, $sacrificeItem) {
            // Consume energy
            if ($energyCost > 0) {
                $this->energyService->consumeEnergy($player, $energyCost);
            }

            // Consume gold for donation
            if ($goldSpent > 0) {
                $player->decrement('gold', $goldSpent);
            }

            // Consume bones for sacrifice
            if ($sacrificeItem) {
                $this->inventoryService->removeItem($player, $sacrificeItem->id, 1);
            }

            // Calculate devotion gained
            $baseDevotion = ReligiousAction::getBaseDevotion($actionType);
            if ($actionType === ReligiousAction::ACTION_DONATION) {
                // 1 devotion per 10 gold donated
                $baseDevotion = (int) floor($goldSpent / 10);
            }
            if ($actionType === ReligiousAction::ACTION_SACRIFICE && $sacrificeItem) {
                // Devotion scales with bone value (1 devotion per 5 prayer XP from bone)
                $baseDevotion = max(5, (int) floor($sacrificeItem->prayer_bonus / 5));
            }
            $devotionGained = (int) floor($baseDevotion * $multiplier);

            // Apply belief devotion modifiers
            $devotionGained = $this->applyBeliefDevotionModifiers($player, $actionType, $devotionGained);

            // Apply HQ devotion modifiers (passive from HQ tier)
            $hqDevotionModifier = $this->hqService->getDevotionGainModifier($player);
            if ($hqDevotionModifier !== 1.0) {
                $devotionGained = (int) ceil($devotionGained * $hqDevotionModifier);
            }

            // Apply blessing/prayer buff devotion bonus
            $devotionBonusPercent = $this->blessingEffects->getEffect($player, 'devotion_bonus');
            if ($devotionBonusPercent > 0) {
                $devotionGained = (int) ceil($devotionGained * (1 + $devotionBonusPercent / 100));
            }

            // Apply double devotion chance (from HQ prayer buff)
            $doubleDevotionChance = (int) $this->blessingEffects->getEffect($player, 'double_devotion_chance');
            if ($doubleDevotionChance > 0 && rand(1, 100) <= $doubleDevotionChance) {
                $devotionGained *= 2;
            }

            // Create action log
            ReligiousAction::create([
                'user_id' => $player->id,
                'religion_id' => $membership->religion_id,
                'religious_structure_id' => $structure?->id,
                'action_type' => $actionType,
                'devotion_gained' => $devotionGained,
                'gold_spent' => $goldSpent,
            ]);

            // Add devotion to membership
            $membership->addDevotion($devotionGained);

            // Award Prayer XP
            $basePrayerXp = ReligiousAction::getPrayerXp($actionType);
            if ($actionType === ReligiousAction::ACTION_DONATION) {
                // 1 Prayer XP per 25 gold donated
                $basePrayerXp = (int) floor($goldSpent / 25);
            }
            if ($actionType === ReligiousAction::ACTION_SACRIFICE && $sacrificeItem) {
                // Prayer XP comes from the bone's prayer_bonus value
                $basePrayerXp = $sacrificeItem->prayer_bonus;

                // Apply Sacrificial Rites belief: +50% sacrifice XP bonus
                $sacrificeXpBonus = $this->beliefEffectService->getEffect($player, 'sacrifice_xp_bonus');
                if ($sacrificeXpBonus > 0) {
                    $basePrayerXp = (int) ceil($basePrayerXp * (1 + $sacrificeXpBonus / 100));
                }
            }
            $prayerXpGained = (int) floor($basePrayerXp * $multiplier);

            $prayerSkill = null;
            if ($prayerXpGained > 0) {
                $prayerSkill = PlayerSkill::firstOrCreate(
                    ['player_id' => $player->id, 'skill_name' => 'prayer'],
                    ['level' => 1, 'xp' => 0]
                );
                $prayerSkill->addXp($prayerXpGained);
            }

            $actionName = match ($actionType) {
                ReligiousAction::ACTION_PRAYER => 'prayed',
                ReligiousAction::ACTION_DONATION => 'donated',
                ReligiousAction::ACTION_RITUAL => 'performed a ritual',
                ReligiousAction::ACTION_SACRIFICE => "sacrificed {$sacrificeItem->name}",
                ReligiousAction::ACTION_PILGRIMAGE => 'completed a pilgrimage',
                default => 'performed an action',
            };

            $message = "You {$actionName} and gained {$devotionGained} devotion.";
            if ($prayerXpGained > 0) {
                $message .= " (+{$prayerXpGained} Prayer XP)";
            }

            // Record daily task progress for prayer-related actions
            if (in_array($actionType, [
                ReligiousAction::ACTION_PRAYER,
                ReligiousAction::ACTION_RITUAL,
                ReligiousAction::ACTION_SACRIFICE,
                ReligiousAction::ACTION_PILGRIMAGE,
            ])) {
                $this->dailyTaskService->recordProgress($player, 'pray');
            }

            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'devotion_gained' => $devotionGained,
                    'total_devotion' => $membership->fresh()->devotion,
                    'gold_spent' => $goldSpent,
                    'prayer_xp_gained' => $prayerXpGained,
                    'prayer_skill' => $prayerSkill ? [
                        'level' => $prayerSkill->level,
                        'xp' => $prayerSkill->xp,
                    ] : null,
                ],
            ];
        });
    }

    /**
     * Promote a member to priest.
     */
    public function promoteMember(User $player, int $memberId): array
    {
        $targetMember = ReligionMember::with(['religion', 'user'])->find($memberId);
        if (! $targetMember) {
            return ['success' => false, 'message' => 'Member not found.'];
        }

        // Check if player is the prophet
        $playerMembership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $targetMember->religion_id)
            ->first();

        if (! $playerMembership || ! $playerMembership->isProphet()) {
            return ['success' => false, 'message' => 'Only the prophet can promote members.'];
        }

        // Check if target can be promoted
        $nextRank = $targetMember->getNextRank();
        if (! $nextRank) {
            return ['success' => false, 'message' => 'This member cannot be promoted further.'];
        }

        if (! $targetMember->canBePromoted()) {
            $requirement = $targetMember->getDevotionRequirementForNextRank();

            return ['success' => false, 'message' => "This member needs {$requirement} devotion to be promoted."];
        }

        return DB::transaction(function () use ($player, $targetMember, $nextRank) {
            $oldRank = $targetMember->rank_display;
            $targetMember->update(['rank' => $nextRank]);
            $targetMember->refresh();
            $newRank = $targetMember->rank_display;

            // Log the promotion
            ReligionLog::log(
                $targetMember->religion_id,
                ReligionLog::EVENT_MEMBER_PROMOTED,
                "{$targetMember->user->username} was promoted to {$newRank} by {$player->username}",
                $player->id,
                $targetMember->user_id
            );

            return [
                'success' => true,
                'message' => "{$targetMember->user->username} has been promoted to {$newRank}.",
            ];
        });
    }

    /**
     * Demote a member one rank.
     */
    public function demoteMember(User $player, int $memberId): array
    {
        $targetMember = ReligionMember::with(['religion', 'user'])->find($memberId);
        if (! $targetMember) {
            return ['success' => false, 'message' => 'Member not found.'];
        }

        // Check if player is the prophet
        $playerMembership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $targetMember->religion_id)
            ->first();

        if (! $playerMembership || ! $playerMembership->isProphet()) {
            return ['success' => false, 'message' => 'Only the prophet can demote members.'];
        }

        $previousRank = $targetMember->getPreviousRank();
        if (! $previousRank) {
            return ['success' => false, 'message' => 'This member cannot be demoted further.'];
        }

        return DB::transaction(function () use ($player, $targetMember, $previousRank) {
            $oldRank = $targetMember->rank_display;
            $targetMember->update(['rank' => $previousRank]);
            $targetMember->refresh();
            $newRank = $targetMember->rank_display;

            // Log the demotion
            ReligionLog::log(
                $targetMember->religion_id,
                ReligionLog::EVENT_MEMBER_DEMOTED,
                "{$targetMember->user->username} was demoted to {$newRank} by {$player->username}",
                $player->id,
                $targetMember->user_id
            );

            return [
                'success' => true,
                'message' => "{$targetMember->user->username} has been demoted to {$newRank}.",
            ];
        });
    }

    /**
     * Convert cult to full religion.
     */
    public function convertToReligion(User $player, int $religionId): array
    {
        $religion = Religion::with('members')->find($religionId);
        if (! $religion) {
            return ['success' => false, 'message' => 'Religion not found.'];
        }

        // Check if player is the prophet
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religionId)
            ->first();

        if (! $membership || ! $membership->isProphet()) {
            return ['success' => false, 'message' => 'Only the prophet can convert the cult to a religion.'];
        }

        if ($religion->isReligion()) {
            return ['success' => false, 'message' => 'This is already a full religion.'];
        }

        if (! $religion->canConvertToReligion()) {
            return ['success' => false, 'message' => 'You need at least '.Religion::RELIGION_MIN_MEMBERS.' members to become a religion.'];
        }

        // Check gold
        if ($player->gold < Religion::RELIGION_FOUNDING_COST) {
            return ['success' => false, 'message' => 'You need '.number_format(Religion::RELIGION_FOUNDING_COST).' gold to convert to a religion.'];
        }

        return DB::transaction(function () use ($player, $religion) {
            // Deduct gold
            $player->decrement('gold', Religion::RELIGION_FOUNDING_COST);

            // Convert to religion
            $religion->update([
                'type' => Religion::TYPE_RELIGION,
                'is_public' => true,
                'member_limit' => 0, // No limit
            ]);

            // Log the conversion
            ReligionLog::log(
                $religion->id,
                ReligionLog::EVENT_CONVERTED_TO_RELIGION,
                "{$religion->name} was elevated from a cult to a full religion by {$player->username}",
                $player->id
            );

            return [
                'success' => true,
                'message' => "{$religion->name} has become a full religion!",
            ];
        });
    }

    /**
     * Build a religious structure.
     */
    public function buildStructure(User $player, int $religionId, string $structureType, string $locationType, int $locationId): array
    {
        $religion = Religion::find($religionId);
        if (! $religion) {
            return ['success' => false, 'message' => 'Religion not found.'];
        }

        // Check if player is a priest or prophet
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religionId)
            ->first();

        if (! $membership || $membership->isFollower()) {
            return ['success' => false, 'message' => 'Only priests and prophets can build structures.'];
        }

        // Validate structure type
        if (! in_array($structureType, ReligiousStructure::TYPES)) {
            return ['success' => false, 'message' => 'Invalid structure type.'];
        }

        // Get build cost
        $baseCost = ReligiousStructure::getBuildCost($structureType);

        // Apply Communion belief: structures cost 15% less
        $structureBonus = $this->beliefEffectService->getEffect($player, 'structure_bonus');
        $cost = $structureBonus > 0
            ? (int) ceil($baseCost * (1 - $structureBonus / 100))
            : $baseCost;

        if ($player->gold < $cost) {
            return ['success' => false, 'message' => 'You need '.number_format($cost).' gold to build a '.$structureType.'.'];
        }

        // Validate location
        if (! in_array($locationType, ['village', 'barony', 'kingdom'])) {
            return ['success' => false, 'message' => 'Invalid location type.'];
        }

        // Check if structure already exists at location
        $existingStructure = ReligiousStructure::where('religion_id', $religionId)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('is_active', true)
            ->first();

        if ($existingStructure) {
            return ['success' => false, 'message' => 'A structure already exists at this location for this religion.'];
        }

        return DB::transaction(function () use ($player, $religion, $structureType, $locationType, $locationId, $cost) {
            // Deduct gold
            $player->decrement('gold', $cost);

            // Create structure
            $structure = ReligiousStructure::create([
                'religion_id' => $religion->id,
                'location_type' => $locationType,
                'location_id' => $locationId,
                'structure_type' => $structureType,
                'name' => "{$religion->name} ".ucfirst($structureType),
                'build_cost' => $cost,
                'built_by_id' => $player->id,
            ]);

            return [
                'success' => true,
                'message' => "You have built a {$structureType} for {$religion->name}.",
                'data' => ['structure' => $structure],
            ];
        });
    }

    /**
     * Set kingdom religion status (king only).
     */
    public function setKingdomReligionStatus(User $player, int $kingdomId, int $religionId, string $status): array
    {
        // Validate status
        if (! in_array($status, KingdomReligion::STATUSES)) {
            return ['success' => false, 'message' => 'Invalid status.'];
        }

        // Check if player is the king
        $kingdom = Kingdom::find($kingdomId);
        if (! $kingdom) {
            return ['success' => false, 'message' => 'Kingdom not found.'];
        }

        // TODO: Check if player holds the King role for this kingdom
        // For now, we'll check if they have the king role through the role system

        $religion = Religion::find($religionId);
        if (! $religion) {
            return ['success' => false, 'message' => 'Religion not found.'];
        }

        // Only full religions can be state religions
        if ($status === KingdomReligion::STATUS_STATE && $religion->isCult()) {
            return ['success' => false, 'message' => 'Only full religions can be designated as state religions.'];
        }

        return DB::transaction(function () use ($player, $kingdom, $religion, $status) {
            // If setting as state religion, remove any existing state religion
            if ($status === KingdomReligion::STATUS_STATE) {
                KingdomReligion::where('kingdom_id', $kingdom->id)
                    ->where('status', KingdomReligion::STATUS_STATE)
                    ->update(['status' => KingdomReligion::STATUS_TOLERATED]);
            }

            // Update or create the status
            KingdomReligion::updateOrCreate(
                [
                    'kingdom_id' => $kingdom->id,
                    'religion_id' => $religion->id,
                ],
                [
                    'status' => $status,
                    'set_by_id' => $player->id,
                ]
            );

            $statusText = match ($status) {
                KingdomReligion::STATUS_STATE => 'the state religion',
                KingdomReligion::STATUS_TOLERATED => 'tolerated',
                KingdomReligion::STATUS_BANNED => 'banned',
                default => $status,
            };

            return [
                'success' => true,
                'message' => "{$religion->name} is now {$statusText} in {$kingdom->name}.",
            ];
        });
    }

    /**
     * Get structures at a location.
     */
    public function getStructuresAtLocation(string $locationType, int $locationId): array
    {
        return ReligiousStructure::where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('is_active', true)
            ->with('religion')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'structure_type' => $s->structure_type,
                'type_display' => $s->type_display,
                'religion' => [
                    'id' => $s->religion->id,
                    'name' => $s->religion->name,
                    'icon' => $s->religion->icon,
                    'color' => $s->religion->color,
                ],
                'devotion_multiplier' => $s->devotion_multiplier,
            ])
            ->toArray();
    }

    /**
     * Get all beliefs.
     */
    public function getAllBeliefs(): array
    {
        return Belief::all()
            ->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'description' => $b->description,
                'icon' => $b->icon,
                'type' => $b->type,
                'effects' => $b->effects,
            ])
            ->toArray();
    }

    /**
     * Make a religion public.
     */
    public function makePublic(User $player, int $religionId): array
    {
        $religion = Religion::find($religionId);
        if (! $religion) {
            return ['success' => false, 'message' => 'Religion not found.'];
        }

        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religionId)
            ->first();

        if (! $membership || ! $membership->isProphet()) {
            return ['success' => false, 'message' => 'Only the prophet can change visibility.'];
        }

        if ($religion->is_public) {
            return ['success' => false, 'message' => 'This religion is already public.'];
        }

        $religion->update(['is_public' => true]);

        return [
            'success' => true,
            'message' => "{$religion->name} is now public.",
        ];
    }

    /**
     * Format a religion for API response.
     */
    protected function formatReligion(Religion $religion): array
    {
        return [
            'id' => $religion->id,
            'name' => $religion->name,
            'description' => $religion->description,
            'icon' => $religion->icon,
            'color' => $religion->color,
            'type' => $religion->type,
            'is_public' => $religion->is_public,
            'is_cult' => $religion->isCult(),
            'is_religion' => $religion->isReligion(),
            'member_count' => $religion->member_count,
            'member_limit' => $religion->isCult() ? $religion->member_limit : null,
            'belief_limit' => $religion->belief_limit,
            'founder' => $religion->founder ? [
                'id' => $religion->founder->id,
                'username' => $religion->founder->username,
            ] : null,
            'beliefs' => $religion->beliefs->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'description' => $b->description,
                'icon' => $b->icon,
                'type' => $b->type,
                'effects' => $b->effects,
            ])->toArray(),
            'combined_effects' => $religion->getCombinedEffects(),
        ];
    }

    /**
     * Format a membership for API response.
     */
    protected function formatMembership(ReligionMember $membership): array
    {
        return [
            'id' => $membership->id,
            'religion_id' => $membership->religion_id,
            'religion_name' => $membership->religion->name,
            'religion_icon' => $membership->religion->icon,
            'religion_color' => $membership->religion->color,
            'religion_type' => $membership->religion->type,
            'is_cult' => $membership->religion->isCult(),
            'rank' => $membership->rank,
            'rank_display' => $membership->rank_display,
            'devotion' => $membership->devotion,
            'joined_at' => $membership->joined_at->toIso8601String(),
            'can_be_promoted' => $membership->canBePromoted(),
            'is_prophet' => $membership->isProphet(),
            'is_officer' => $membership->isOfficer(),
        ];
    }

    /**
     * Get player's kingdom ID.
     */
    protected function getPlayerKingdomId(User $player): ?int
    {
        // Get kingdom based on current location
        return match ($player->current_location_type) {
            'village' => \App\Models\Village::find($player->current_location_id)?->barony?->kingdom_id,
            'barony' => \App\Models\Barony::find($player->current_location_id)?->kingdom_id,
            'town' => \App\Models\Town::find($player->current_location_id)?->barony?->kingdom_id,
            'kingdom' => $player->current_location_id,
            default => null,
        };
    }

    /**
     * Apply belief modifiers to devotion gains.
     */
    protected function applyBeliefDevotionModifiers(User $player, string $actionType, int $devotionGained): int
    {
        // General devotion bonus (Asceticism belief)
        $devotionBonus = $this->beliefEffectService->getEffect($player, 'devotion_bonus');

        // General devotion penalty (Pride belief)
        $devotionBonus += $this->beliefEffectService->getEffect($player, 'devotion_penalty');

        // Action-specific bonuses
        if ($actionType === ReligiousAction::ACTION_DONATION) {
            // Charity belief: +25% donation devotion
            $devotionBonus += $this->beliefEffectService->getEffect($player, 'donation_devotion_bonus');
            // Greed belief: -25% donation efficiency (costs more gold per devotion)
            $donationPenalty = $this->beliefEffectService->getEffect($player, 'donation_cost_penalty');
            if ($donationPenalty > 0) {
                $devotionBonus -= $donationPenalty;
            }
        }

        if ($actionType === ReligiousAction::ACTION_RITUAL) {
            // Mysticism belief: +25% ritual devotion
            $devotionBonus += $this->beliefEffectService->getEffect($player, 'ritual_devotion_bonus');
        }

        if ($actionType === ReligiousAction::ACTION_SACRIFICE) {
            // Mysticism belief: +25% sacrifice devotion
            $devotionBonus += $this->beliefEffectService->getEffect($player, 'sacrifice_devotion_bonus');
        }

        if ($actionType === ReligiousAction::ACTION_PILGRIMAGE) {
            // Pilgrimage belief: +100% pilgrimage devotion
            $devotionBonus += $this->beliefEffectService->getEffect($player, 'pilgrimage_bonus');
        }

        if ($actionType === ReligiousAction::ACTION_PRAYER) {
            // Sacrificial Rites belief: -25% prayer devotion
            $devotionBonus += $this->beliefEffectService->getEffect($player, 'prayer_devotion_penalty');
        }

        // Blood Tithe belief: -10% devotion gain from all sources
        $devotionBonus += $this->beliefEffectService->getEffect($player, 'devotion_gain_penalty');

        // Apply the total modifier
        if ($devotionBonus != 0) {
            $devotionGained = (int) ceil($devotionGained * (1 + $devotionBonus / 100));
            $devotionGained = max(1, $devotionGained); // Minimum 1 devotion
        }

        return $devotionGained;
    }

    /**
     * Dissolve a religion (prophet only).
     * If there are other members, a successor must be chosen.
     * If no other members, the religion is deleted.
     */
    public function dissolveReligion(User $player, int $religionId, ?int $successorUserId = null): array
    {
        $religion = Religion::find($religionId);

        if (! $religion) {
            return ['success' => false, 'message' => 'Religion not found.'];
        }

        // Must be the prophet
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religionId)
            ->first();

        if (! $membership || ! $membership->isProphet()) {
            return ['success' => false, 'message' => 'Only the prophet can dissolve the religion.'];
        }

        // Check if there are other members
        $otherMembers = ReligionMember::where('religion_id', $religionId)
            ->where('user_id', '!=', $player->id)
            ->get();

        $memberCount = $otherMembers->count();

        // If there are other members, successor must be chosen
        if ($memberCount > 0) {
            if (! $successorUserId) {
                return [
                    'success' => false,
                    'message' => 'You must choose a successor before leaving.',
                    'requires_successor' => true,
                    'members' => $otherMembers->map(fn ($m) => [
                        'user_id' => $m->user_id,
                        'username' => $m->user->username,
                        'rank' => $m->rank,
                        'devotion' => $m->devotion,
                    ])->toArray(),
                ];
            }

            // Validate successor is a member
            $successorMembership = $otherMembers->firstWhere('user_id', $successorUserId);
            if (! $successorMembership) {
                return ['success' => false, 'message' => 'Successor must be a member of the religion.'];
            }

            return DB::transaction(function () use ($religion, $membership, $successorMembership) {
                $oldProphetUsername = $membership->user->username;
                $oldProphetId = $membership->user_id;
                $newProphetUsername = $successorMembership->user->username;
                $newProphetId = $successorMembership->user_id;

                // Transfer leadership to successor
                $successorMembership->rank = ReligionMember::RANK_PROPHET;
                $successorMembership->save();

                // Remove the old prophet
                $membership->delete();

                // Log the leadership transfer
                ReligionLog::log(
                    $religion->id,
                    ReligionLog::EVENT_LEADERSHIP_TRANSFERRED,
                    "{$oldProphetUsername} passed leadership to {$newProphetUsername}",
                    $oldProphetId,
                    $newProphetId
                );

                // Log the old prophet leaving
                ReligionLog::log(
                    $religion->id,
                    ReligionLog::EVENT_MEMBER_LEFT,
                    "{$oldProphetUsername} left the religion",
                    $oldProphetId
                );

                return [
                    'success' => true,
                    'message' => "You have passed leadership of {$religion->name} to {$newProphetUsername} and left the religion.",
                ];
            });
        }

        // No other members - fully dissolve the religion
        return DB::transaction(function () use ($religion, $membership) {
            $religionName = $religion->name;
            $prophetUsername = $membership->user->username;
            $prophetId = $membership->user_id;

            // Log the dissolution before deleting
            ReligionLog::log(
                $religion->id,
                ReligionLog::EVENT_DISSOLVED,
                "{$prophetUsername} dissolved the religion",
                $prophetId
            );

            // Delete the membership
            $membership->delete();

            // Deactivate the religion (cascade will handle related data)
            $religion->is_active = false;
            $religion->save();

            // Actually delete it since there are no members
            $religion->delete();

            return [
                'success' => true,
                'message' => "{$religionName} has been dissolved.",
            ];
        });
    }

    /**
     * Get potential successors for a religion.
     */
    public function getPotentialSuccessors(User $player, int $religionId): array
    {
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religionId)
            ->first();

        if (! $membership || ! $membership->isProphet()) {
            return [];
        }

        return ReligionMember::where('religion_id', $religionId)
            ->where('user_id', '!=', $player->id)
            ->with('user:id,username')
            ->orderByRaw("CASE WHEN rank = 'priest' THEN 0 ELSE 1 END")
            ->orderByDesc('devotion')
            ->get()
            ->map(fn ($m) => [
                'user_id' => $m->user_id,
                'username' => $m->user->username,
                'rank' => $m->rank,
                'rank_display' => ucfirst($m->rank),
                'devotion' => $m->devotion,
            ])
            ->toArray();
    }

    /**
     * Get religion history logs (members only).
     */
    public function getReligionLogs(User $player, int $religionId, int $limit = 50): array
    {
        // Check if player is a member
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religionId)
            ->first();

        if (! $membership) {
            return [];
        }

        return ReligionLog::where('religion_id', $religionId)
            ->with(['actor:id,username', 'target:id,username'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'event_type' => $log->event_type,
                'description' => $log->description,
                'actor' => $log->actor ? ['id' => $log->actor->id, 'username' => $log->actor->username] : null,
                'target' => $log->target ? ['id' => $log->target->id, 'username' => $log->target->username] : null,
                'created_at' => $log->created_at->toIso8601String(),
                'time_ago' => $log->created_at->diffForHumans(),
            ])
            ->toArray();
    }
}

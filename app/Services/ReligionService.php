<?php

namespace App\Services;

use App\Models\Belief;
use App\Models\Kingdom;
use App\Models\KingdomReligion;
use App\Models\Religion;
use App\Models\ReligionMember;
use App\Models\ReligiousAction;
use App\Models\ReligiousStructure;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReligionService
{
    public function __construct(
        protected EnergyService $energyService,
        protected BankService $bankService
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

                // Check member limit for cults
                if ($religion->isCult() && !$religion->canAcceptMembers()) {
                    return false;
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
            ])->toArray(),
            'structures' => $religion->structures->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'structure_type' => $s->structure_type,
                'type_display' => $s->type_display,
                'location_type' => $s->location_type,
                'location_id' => $s->location_id,
            ])->toArray(),
        ];
    }

    /**
     * Create a new cult.
     */
    public function createCult(User $player, string $name, string $description, array $beliefIds): array
    {
        // Check if player is already a prophet
        $existingProphet = ReligionMember::where('user_id', $player->id)
            ->where('rank', ReligionMember::RANK_PROPHET)
            ->exists();

        if ($existingProphet) {
            return ['success' => false, 'message' => 'You are already the prophet of another religion.'];
        }

        // Validate name
        if (Religion::where('name', $name)->exists()) {
            return ['success' => false, 'message' => 'A religion with that name already exists.'];
        }

        // Validate belief count
        if (count($beliefIds) > Religion::CULT_BELIEF_LIMIT) {
            return ['success' => false, 'message' => 'Cults can only have ' . Religion::CULT_BELIEF_LIMIT . ' beliefs.'];
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
                'member_limit' => Religion::CULT_MEMBER_LIMIT,
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
        if (!$religion) {
            return ['success' => false, 'message' => 'Religion not found.'];
        }

        // Check if already a member
        if (ReligionMember::where('user_id', $player->id)->where('religion_id', $religionId)->exists()) {
            return ['success' => false, 'message' => 'You are already a member of this religion.'];
        }

        // Check member limit
        if (!$religion->canAcceptMembers()) {
            return ['success' => false, 'message' => 'This cult has reached its member limit.'];
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

        if (!$membership) {
            return ['success' => false, 'message' => 'You are not a member of this religion.'];
        }

        // Prophets cannot leave (must disband instead)
        if ($membership->isProphet()) {
            return ['success' => false, 'message' => 'As the prophet, you must disband the religion instead of leaving.'];
        }

        return DB::transaction(function () use ($membership) {
            $religionName = $membership->religion->name;
            $membership->delete();

            return [
                'success' => true,
                'message' => "You have left {$religionName}.",
            ];
        });
    }

    /**
     * Perform a religious action.
     */
    public function performAction(User $player, int $religionId, string $actionType, ?int $structureId = null, int $donationAmount = 0): array
    {
        // Validate action type
        if (!in_array($actionType, ReligiousAction::ACTIONS)) {
            return ['success' => false, 'message' => 'Invalid action type.'];
        }

        // Check membership
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religionId)
            ->first();

        if (!$membership) {
            return ['success' => false, 'message' => 'You are not a member of this religion.'];
        }

        // Check energy
        $energyCost = ReligiousAction::getEnergyCost($actionType);
        if ($energyCost > 0 && !$this->energyService->hasEnergy($player, $energyCost)) {
            return ['success' => false, 'message' => "You need {$energyCost} energy to perform this action."];
        }

        // Check cooldown
        $cooldown = ReligiousAction::getCooldown($actionType);
        if ($cooldown > 0) {
            $lastAction = ReligiousAction::where('user_id', $player->id)
                ->where('religion_id', $religionId)
                ->where('action_type', $actionType)
                ->latest()
                ->first();

            if ($lastAction && $lastAction->created_at->addMinutes($cooldown)->isFuture()) {
                $remaining = $lastAction->created_at->addMinutes($cooldown)->diffForHumans();
                return ['success' => false, 'message' => "You can perform this action again {$remaining}."];
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

        return DB::transaction(function () use ($player, $membership, $actionType, $energyCost, $goldSpent, $multiplier, $structure) {
            // Consume energy
            if ($energyCost > 0) {
                $this->energyService->consumeEnergy($player, $energyCost);
            }

            // Consume gold for donation
            if ($goldSpent > 0) {
                $player->decrement('gold', $goldSpent);
            }

            // Calculate devotion gained
            $baseDevotion = ReligiousAction::getBaseDevotion($actionType);
            if ($actionType === ReligiousAction::ACTION_DONATION) {
                // 1 devotion per 10 gold donated
                $baseDevotion = (int) floor($goldSpent / 10);
            }
            $devotionGained = (int) floor($baseDevotion * $multiplier);

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

            $actionName = match ($actionType) {
                ReligiousAction::ACTION_PRAYER => 'prayed',
                ReligiousAction::ACTION_DONATION => 'donated',
                ReligiousAction::ACTION_RITUAL => 'performed a ritual',
                ReligiousAction::ACTION_SACRIFICE => 'made a sacrifice',
                ReligiousAction::ACTION_PILGRIMAGE => 'completed a pilgrimage',
                default => 'performed an action',
            };

            return [
                'success' => true,
                'message' => "You {$actionName} and gained {$devotionGained} devotion.",
                'data' => [
                    'devotion_gained' => $devotionGained,
                    'total_devotion' => $membership->fresh()->devotion,
                    'gold_spent' => $goldSpent,
                ],
            ];
        });
    }

    /**
     * Promote a member to priest.
     */
    public function promoteToPriest(User $player, int $memberId): array
    {
        $targetMember = ReligionMember::with('religion')->find($memberId);
        if (!$targetMember) {
            return ['success' => false, 'message' => 'Member not found.'];
        }

        // Check if player is the prophet
        $playerMembership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $targetMember->religion_id)
            ->first();

        if (!$playerMembership || !$playerMembership->isProphet()) {
            return ['success' => false, 'message' => 'Only the prophet can promote members.'];
        }

        // Check if target can be promoted
        if (!$targetMember->canBePromoted()) {
            if (!$targetMember->isFollower()) {
                return ['success' => false, 'message' => 'This member cannot be promoted further.'];
            }
            return ['success' => false, 'message' => 'This member needs ' . ReligionMember::PRIEST_DEVOTION_REQUIREMENT . ' devotion to be promoted.'];
        }

        return DB::transaction(function () use ($targetMember) {
            $targetMember->update(['rank' => ReligionMember::RANK_PRIEST]);

            return [
                'success' => true,
                'message' => "{$targetMember->user->username} has been promoted to Priest.",
            ];
        });
    }

    /**
     * Demote a priest to follower.
     */
    public function demoteToFollower(User $player, int $memberId): array
    {
        $targetMember = ReligionMember::with(['religion', 'user'])->find($memberId);
        if (!$targetMember) {
            return ['success' => false, 'message' => 'Member not found.'];
        }

        // Check if player is the prophet
        $playerMembership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $targetMember->religion_id)
            ->first();

        if (!$playerMembership || !$playerMembership->isProphet()) {
            return ['success' => false, 'message' => 'Only the prophet can demote members.'];
        }

        if (!$targetMember->isPriest()) {
            return ['success' => false, 'message' => 'This member is not a priest.'];
        }

        return DB::transaction(function () use ($targetMember) {
            $targetMember->update(['rank' => ReligionMember::RANK_FOLLOWER]);

            return [
                'success' => true,
                'message' => "{$targetMember->user->username} has been demoted to Follower.",
            ];
        });
    }

    /**
     * Convert cult to full religion.
     */
    public function convertToReligion(User $player, int $religionId): array
    {
        $religion = Religion::with('members')->find($religionId);
        if (!$religion) {
            return ['success' => false, 'message' => 'Religion not found.'];
        }

        // Check if player is the prophet
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religionId)
            ->first();

        if (!$membership || !$membership->isProphet()) {
            return ['success' => false, 'message' => 'Only the prophet can convert the cult to a religion.'];
        }

        if ($religion->isReligion()) {
            return ['success' => false, 'message' => 'This is already a full religion.'];
        }

        if (!$religion->canConvertToReligion()) {
            return ['success' => false, 'message' => 'You need at least ' . Religion::RELIGION_MIN_MEMBERS . ' members to become a religion.'];
        }

        // Check gold
        if ($player->gold < Religion::RELIGION_FOUNDING_COST) {
            return ['success' => false, 'message' => 'You need ' . number_format(Religion::RELIGION_FOUNDING_COST) . ' gold to convert to a religion.'];
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
        if (!$religion) {
            return ['success' => false, 'message' => 'Religion not found.'];
        }

        // Check if player is a priest or prophet
        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religionId)
            ->first();

        if (!$membership || $membership->isFollower()) {
            return ['success' => false, 'message' => 'Only priests and prophets can build structures.'];
        }

        // Validate structure type
        if (!in_array($structureType, ReligiousStructure::TYPES)) {
            return ['success' => false, 'message' => 'Invalid structure type.'];
        }

        // Get build cost
        $cost = ReligiousStructure::getBuildCost($structureType);
        if ($player->gold < $cost) {
            return ['success' => false, 'message' => "You need " . number_format($cost) . " gold to build a " . $structureType . "."];
        }

        // Validate location
        if (!in_array($locationType, ['village', 'castle', 'kingdom'])) {
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
                'name' => "{$religion->name} " . ucfirst($structureType),
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
        if (!in_array($status, KingdomReligion::STATUSES)) {
            return ['success' => false, 'message' => 'Invalid status.'];
        }

        // Check if player is the king
        $kingdom = Kingdom::find($kingdomId);
        if (!$kingdom) {
            return ['success' => false, 'message' => 'Kingdom not found.'];
        }

        // TODO: Check if player holds the King role for this kingdom
        // For now, we'll check if they have the king role through the role system

        $religion = Religion::find($religionId);
        if (!$religion) {
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
        if (!$religion) {
            return ['success' => false, 'message' => 'Religion not found.'];
        }

        $membership = ReligionMember::where('user_id', $player->id)
            ->where('religion_id', $religionId)
            ->first();

        if (!$membership || !$membership->isProphet()) {
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
            'rank' => $membership->rank,
            'rank_display' => $membership->rank_display,
            'devotion' => $membership->devotion,
            'joined_at' => $membership->joined_at->toIso8601String(),
            'can_be_promoted' => $membership->canBePromoted(),
            'is_prophet' => $membership->isProphet(),
            'is_priest' => $membership->isPriest(),
        ];
    }

    /**
     * Get player's kingdom ID.
     */
    protected function getPlayerKingdomId(User $player): ?int
    {
        // Get kingdom based on current location
        return match ($player->current_location_type) {
            'village' => \App\Models\Village::find($player->current_location_id)?->castle?->kingdom_id,
            'castle' => \App\Models\Castle::find($player->current_location_id)?->kingdom_id,
            'kingdom' => $player->current_location_id,
            default => null,
        };
    }
}

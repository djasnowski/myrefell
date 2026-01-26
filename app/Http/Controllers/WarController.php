<?php

namespace App\Http\Controllers;

use App\Models\Army;
use App\Models\Barony;
use App\Models\Battle;
use App\Models\Kingdom;
use App\Models\Siege;
use App\Models\War;
use App\Models\WarGoal;
use App\Models\WarParticipant;
use App\Services\WarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarController extends Controller
{
    /**
     * Display a specific war.
     */
    public function show(Request $request, War $war): Response
    {
        $user = $request->user();

        // Load all related data
        $war->load([
            'attackerKingdom',
            'defenderKingdom',
            'participants',
            'battles' => function ($query) {
                $query->latest('started_at');
            },
            'sieges',
            'goals',
            'peaceTreaty',
        ]);

        // Check if user can offer peace (is war leader)
        $userParticipant = $war->participants
            ->where('participant_type', 'player')
            ->where('participant_id', $user->id)
            ->first();
        $canOfferPeace = $userParticipant && $userParticipant->is_war_leader && $war->status === War::STATUS_ACTIVE;

        return Inertia::render('Warfare/WarShow', [
            'war' => $this->mapWar($war, $user, true),
            'all_battles' => $war->battles->map(fn ($b) => $this->mapBattle($b))->toArray(),
            'all_sieges' => $war->sieges->map(fn ($s) => $this->mapSiege($s))->toArray(),
            'can_offer_peace' => $canOfferPeace,
        ]);
    }

    /**
     * Display a listing of wars.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get active wars where user participates or their kingdom/barony is involved
        $activeWars = War::active()
            ->with(['attackerKingdom', 'defenderKingdom', 'participants', 'battles', 'sieges', 'goals'])
            ->latest('declared_at')
            ->get()
            ->map(fn ($war) => $this->mapWar($war, $user));

        // Get concluded wars (recent history)
        $concludedWars = War::ended()
            ->with(['attackerKingdom', 'defenderKingdom', 'participants', 'peaceTreaty'])
            ->latest('ended_at')
            ->limit(10)
            ->get()
            ->map(fn ($war) => $this->mapWar($war, $user, false));

        // Get user's participation info
        $userParticipation = WarParticipant::where('participant_type', 'player')
            ->where('participant_id', $user->id)
            ->active()
            ->with('war')
            ->get()
            ->map(fn ($p) => [
                'war_id' => $p->war_id,
                'side' => $p->side,
                'role' => $p->role,
                'is_war_leader' => $p->is_war_leader,
                'contribution_score' => $p->contribution_score,
            ]);

        return Inertia::render('Warfare/Wars', [
            'active_wars' => $activeWars->toArray(),
            'concluded_wars' => $concludedWars->toArray(),
            'user_participation' => $userParticipation->toArray(),
        ]);
    }

    /**
     * Map war to array for frontend.
     */
    private function mapWar(War $war, $user, bool $includeDetails = true): array
    {
        $data = [
            'id' => $war->id,
            'name' => $war->name,
            'casus_belli' => $war->casus_belli,
            'status' => $war->status,
            'attacker_war_score' => $war->attacker_war_score,
            'defender_war_score' => $war->defender_war_score,
            'declared_at' => $war->declared_at?->toISOString(),
            'ended_at' => $war->ended_at?->toISOString(),
            'days_active' => $war->declared_at ? $war->declared_at->diffInDays(now()) : 0,
            'attacker' => $this->mapWarSide($war, 'attacker'),
            'defender' => $this->mapWarSide($war, 'defender'),
            'participant_count' => $war->participants->count(),
            'battle_count' => $war->battles->count(),
            'siege_count' => $war->sieges->count(),
        ];

        if ($includeDetails) {
            // Map participants by side
            $data['attacker_participants'] = $war->participants
                ->where('side', 'attacker')
                ->map(fn ($p) => $this->mapParticipant($p))
                ->values()
                ->toArray();

            $data['defender_participants'] = $war->participants
                ->where('side', 'defender')
                ->map(fn ($p) => $this->mapParticipant($p))
                ->values()
                ->toArray();

            // Recent battles
            $data['recent_battles'] = $war->battles
                ->sortByDesc('started_at')
                ->take(3)
                ->map(fn ($b) => $this->mapBattle($b))
                ->values()
                ->toArray();

            // Active sieges
            $data['active_sieges'] = $war->sieges
                ->whereIn('status', [Siege::STATUS_ACTIVE, Siege::STATUS_ASSAULT, Siege::STATUS_BREACHED])
                ->map(fn ($s) => $this->mapSiege($s))
                ->values()
                ->toArray();

            // War goals
            $data['goals'] = $war->goals->map(fn ($g) => [
                'id' => $g->id,
                'goal_type' => $g->goal_type,
                'is_achieved' => $g->is_achieved,
                'war_score_value' => $g->war_score_value,
            ])->toArray();
        }

        // Check user's participation in this war
        $userParticipant = $war->participants
            ->where('participant_type', 'player')
            ->where('participant_id', $user->id)
            ->first();

        $data['user_participation'] = $userParticipant ? [
            'side' => $userParticipant->side,
            'role' => $userParticipant->role,
            'is_war_leader' => $userParticipant->is_war_leader,
            'contribution_score' => $userParticipant->contribution_score,
        ] : null;

        // Peace treaty info for concluded wars
        if ($war->peaceTreaty) {
            $data['peace_treaty'] = [
                'treaty_type' => $war->peaceTreaty->treaty_type,
                'winner_side' => $war->peaceTreaty->winner_side,
                'gold_payment' => $war->peaceTreaty->gold_payment,
                'truce_days' => $war->peaceTreaty->truce_days,
                'signed_at' => $war->peaceTreaty->signed_at?->toISOString(),
            ];
        }

        return $data;
    }

    /**
     * Map war side (attacker/defender) info.
     */
    private function mapWarSide(War $war, string $side): array
    {
        $type = $side === 'attacker' ? $war->attacker_type : $war->defender_type;
        $id = $side === 'attacker' ? $war->attacker_id : $war->defender_id;
        $kingdom = $side === 'attacker' ? $war->attackerKingdom : $war->defenderKingdom;

        // Get the primary entity name
        $name = 'Unknown';
        if ($type === 'kingdom' && $kingdom) {
            $name = $kingdom->name;
        } elseif ($type === 'barony') {
            $barony = \App\Models\Barony::find($id);
            $name = $barony?->name ?? 'Unknown Barony';
        } elseif ($type === 'player') {
            $player = \App\Models\User::find($id);
            $name = $player?->username ?? 'Unknown Player';
        }

        return [
            'type' => $type,
            'id' => $id,
            'name' => $name,
            'kingdom_id' => $side === 'attacker' ? $war->attacker_kingdom_id : $war->defender_kingdom_id,
            'kingdom_name' => $kingdom?->name,
        ];
    }

    /**
     * Map participant to array.
     */
    private function mapParticipant(WarParticipant $participant): array
    {
        $name = 'Unknown';
        if ($participant->participant_type === 'kingdom') {
            $kingdom = \App\Models\Kingdom::find($participant->participant_id);
            $name = $kingdom?->name ?? 'Unknown Kingdom';
        } elseif ($participant->participant_type === 'barony') {
            $barony = \App\Models\Barony::find($participant->participant_id);
            $name = $barony?->name ?? 'Unknown Barony';
        } elseif ($participant->participant_type === 'player') {
            $player = \App\Models\User::find($participant->participant_id);
            $name = $player?->username ?? 'Unknown Player';
        }

        return [
            'id' => $participant->id,
            'participant_type' => $participant->participant_type,
            'participant_id' => $participant->participant_id,
            'name' => $name,
            'side' => $participant->side,
            'role' => $participant->role,
            'is_war_leader' => $participant->is_war_leader,
            'contribution_score' => $participant->contribution_score,
            'joined_at' => $participant->joined_at?->toISOString(),
        ];
    }

    /**
     * Map battle to array.
     */
    private function mapBattle(Battle $battle): array
    {
        $locationName = 'Unknown';
        if ($battle->location_type && $battle->location_id) {
            $location = match ($battle->location_type) {
                'village' => \App\Models\Village::find($battle->location_id),
                'town' => \App\Models\Town::find($battle->location_id),
                'castle' => \App\Models\Castle::find($battle->location_id),
                default => null,
            };
            $locationName = $location?->name ?? 'Unknown';
        }

        return [
            'id' => $battle->id,
            'battle_type' => $battle->battle_type,
            'status' => $battle->status,
            'location_name' => $locationName,
            'attacker_troops_start' => $battle->attacker_troops_start,
            'defender_troops_start' => $battle->defender_troops_start,
            'attacker_casualties' => $battle->attacker_casualties,
            'defender_casualties' => $battle->defender_casualties,
            'started_at' => $battle->started_at?->toISOString(),
            'ended_at' => $battle->ended_at?->toISOString(),
        ];
    }

    /**
     * Map siege to array.
     */
    private function mapSiege(Siege $siege): array
    {
        $targetName = 'Unknown';
        if ($siege->target_type && $siege->target_id) {
            $target = match ($siege->target_type) {
                'castle' => \App\Models\Castle::find($siege->target_id),
                'town' => \App\Models\Town::find($siege->target_id),
                'village' => \App\Models\Village::find($siege->target_id),
                default => null,
            };
            $targetName = $target?->name ?? 'Unknown';
        }

        return [
            'id' => $siege->id,
            'target_name' => $targetName,
            'target_type' => $siege->target_type,
            'status' => $siege->status,
            'fortification_level' => $siege->fortification_level,
            'garrison_strength' => $siege->garrison_strength,
            'garrison_morale' => $siege->garrison_morale,
            'supplies_remaining' => $siege->supplies_remaining,
            'days_besieged' => $siege->days_besieged,
            'has_breach' => $siege->has_breach,
            'started_at' => $siege->started_at?->toISOString(),
        ];
    }

    /**
     * Show the declare war form.
     */
    public function declareForm(Request $request): Response
    {
        $user = $request->user();

        // Get potential targets (kingdoms and baronies the user is not part of)
        $userKingdom = Kingdom::where('king_user_id', $user->id)->first();
        $userBarony = Barony::where('baron_user_id', $user->id)->first();

        // Get all kingdoms except the user's own
        $kingdoms = Kingdom::with('king')
            ->when($userKingdom, fn ($q) => $q->where('id', '!=', $userKingdom->id))
            ->get()
            ->map(fn ($k) => $this->mapTarget($k, 'kingdom'));

        // Get all baronies except the user's own
        $baronies = Barony::with(['baron', 'kingdom'])
            ->when($userBarony, fn ($q) => $q->where('id', '!=', $userBarony->id))
            ->get()
            ->map(fn ($b) => $this->mapTarget($b, 'barony'));

        // Casus belli types with descriptions and legitimacy impact
        $casusBelliTypes = [
            [
                'value' => War::CASUS_BELLI_CONQUEST,
                'label' => 'Conquest',
                'description' => 'Take territory by force',
                'legitimacy_impact' => -20,
            ],
            [
                'value' => War::CASUS_BELLI_CLAIM,
                'label' => 'Pressing Claim',
                'description' => 'You have a legal claim to territory',
                'legitimacy_impact' => -5,
            ],
            [
                'value' => War::CASUS_BELLI_HOLY_WAR,
                'label' => 'Holy War',
                'description' => 'Religious differences justify conquest',
                'legitimacy_impact' => 10,
            ],
            [
                'value' => War::CASUS_BELLI_RAID,
                'label' => 'Raid',
                'description' => 'Plunder enemy territory for resources',
                'legitimacy_impact' => -10,
            ],
            [
                'value' => War::CASUS_BELLI_REBELLION,
                'label' => 'Rebellion',
                'description' => 'Rise against your liege',
                'legitimacy_impact' => -15,
            ],
        ];

        // War goal types
        $warGoalTypes = [
            [
                'value' => WarGoal::TYPE_CONQUER_TERRITORY,
                'label' => 'Conquer Territory',
                'description' => 'Take control of a barony or settlement',
            ],
            [
                'value' => WarGoal::TYPE_SUBJUGATION,
                'label' => 'Subjugation',
                'description' => 'Force the enemy to become your vassal',
            ],
            [
                'value' => WarGoal::TYPE_RAID,
                'label' => 'Raid',
                'description' => 'Plunder gold and resources',
            ],
            [
                'value' => WarGoal::TYPE_HUMILIATE,
                'label' => 'Humiliate',
                'description' => 'Damage enemy prestige and reputation',
            ],
        ];

        // Get player's armies
        $playerArmies = Army::where('commander_id', $user->id)
            ->operational()
            ->with('units')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'total_troops' => $a->total_troops,
                'total_attack' => $a->total_attack,
                'total_defense' => $a->total_defense,
                'status' => $a->status,
            ]);

        // Calculate total military strength
        $totalTroops = $playerArmies->sum('total_troops');
        $totalAttack = $playerArmies->sum('total_attack');

        // Get potential allies (kingdoms/baronies that might join based on relationships)
        // For simplicity, showing friendly kingdoms (not at war with user)
        $potentialAllies = Kingdom::whereNotIn('id', function ($query) use ($user) {
            $query->select('attacker_kingdom_id')
                ->from('wars')
                ->whereIn('status', [War::STATUS_ACTIVE, War::STATUS_ATTACKER_WINNING, War::STATUS_DEFENDER_WINNING])
                ->where('defender_type', 'player')
                ->where('defender_id', $user->id);
        })
            ->when($userKingdom, fn ($q) => $q->where('id', '!=', $userKingdom->id))
            ->limit(5)
            ->get()
            ->map(fn ($k) => [
                'id' => $k->id,
                'type' => 'kingdom',
                'name' => $k->name,
                'estimated_troops' => $this->estimateKingdomTroops($k),
                'likelihood' => 'may join',
            ]);

        return Inertia::render('Warfare/DeclareWar', [
            'potential_targets' => [
                'kingdoms' => $kingdoms->toArray(),
                'baronies' => $baronies->toArray(),
            ],
            'casus_belli_types' => $casusBelliTypes,
            'war_goal_types' => $warGoalTypes,
            'player_armies' => $playerArmies->toArray(),
            'player_strength' => [
                'total_troops' => $totalTroops,
                'total_attack' => $totalAttack,
            ],
            'potential_allies' => $potentialAllies->toArray(),
            'user_kingdom' => $userKingdom ? [
                'id' => $userKingdom->id,
                'name' => $userKingdom->name,
            ] : null,
            'user_barony' => $userBarony ? [
                'id' => $userBarony->id,
                'name' => $userBarony->name,
                'kingdom_id' => $userBarony->kingdom_id,
            ] : null,
        ]);
    }

    /**
     * Declare war.
     */
    public function declare(Request $request, WarService $warService): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'target_type' => 'required|in:kingdom,barony',
            'target_id' => 'required|integer',
            'casus_belli' => 'required|in:claim,conquest,rebellion,holy_war,raid',
            'war_goals' => 'required|array|min:1',
            'war_goals.*' => 'in:conquer_territory,subjugation,independence,raid,humiliate',
            'war_name' => 'nullable|string|max:255',
        ]);

        // Determine attacker type and ID
        $userKingdom = Kingdom::where('king_user_id', $user->id)->first();
        $userBarony = Barony::where('baron_user_id', $user->id)->first();

        // Validate user can declare war
        if (!$userKingdom && !$userBarony) {
            return response()->json([
                'success' => false,
                'message' => 'You must rule a kingdom or barony to declare war.',
            ], 403);
        }

        // Determine attacker info
        if ($userKingdom) {
            $attackerType = 'kingdom';
            $attackerId = $userKingdom->id;
            $attackerKingdomId = $userKingdom->id;
        } else {
            $attackerType = 'barony';
            $attackerId = $userBarony->id;
            $attackerKingdomId = $userBarony->kingdom_id;
        }

        // Get defender info
        $defenderType = $validated['target_type'];
        $defenderId = $validated['target_id'];
        $defenderKingdomId = null;

        if ($defenderType === 'kingdom') {
            $defender = Kingdom::find($defenderId);
            if (!$defender) {
                return response()->json([
                    'success' => false,
                    'message' => 'Target kingdom not found.',
                ], 404);
            }
            $defenderKingdomId = $defender->id;
            $defenderName = $defender->name;
        } else {
            $defender = Barony::find($defenderId);
            if (!$defender) {
                return response()->json([
                    'success' => false,
                    'message' => 'Target barony not found.',
                ], 404);
            }
            $defenderKingdomId = $defender->kingdom_id;
            $defenderName = $defender->name;
        }

        // Check for existing truce
        if ($warService->hasActiveTruce($attackerType, $attackerId, $defenderType, $defenderId)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot declare war: an active truce exists with this target.',
            ], 400);
        }

        // Check if already at war with target
        $existingWar = War::active()
            ->where(function ($q) use ($attackerType, $attackerId, $defenderType, $defenderId) {
                $q->where(function ($q) use ($attackerType, $attackerId, $defenderType, $defenderId) {
                    $q->where('attacker_type', $attackerType)
                        ->where('attacker_id', $attackerId)
                        ->where('defender_type', $defenderType)
                        ->where('defender_id', $defenderId);
                })->orWhere(function ($q) use ($attackerType, $attackerId, $defenderType, $defenderId) {
                    $q->where('attacker_type', $defenderType)
                        ->where('attacker_id', $defenderId)
                        ->where('defender_type', $attackerType)
                        ->where('defender_id', $attackerId);
                });
            })
            ->exists();

        if ($existingWar) {
            return response()->json([
                'success' => false,
                'message' => 'You are already at war with this target.',
            ], 400);
        }

        // Generate war name if not provided
        $warName = $validated['war_name'] ?? $this->generateWarName(
            $userKingdom?->name ?? $userBarony?->name ?? $user->username,
            $defenderName,
            $validated['casus_belli']
        );

        try {
            // Create the war
            $war = $warService->declareWar(
                $warName,
                $validated['casus_belli'],
                $attackerType,
                $attackerId,
                $defenderType,
                $defenderId,
                $attackerKingdomId,
                $defenderKingdomId
            );

            // Add war goals
            foreach ($validated['war_goals'] as $goalType) {
                $warService->addWarGoal(
                    $war,
                    $goalType,
                    $attackerType,
                    $attackerId,
                    $defenderType,
                    $defenderId,
                    100
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'War has been declared!',
                'war_id' => $war->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Map target (kingdom or barony) for frontend.
     */
    private function mapTarget($entity, string $type): array
    {
        if ($type === 'kingdom') {
            return [
                'id' => $entity->id,
                'type' => 'kingdom',
                'name' => $entity->name,
                'ruler_name' => $entity->king?->username ?? 'No Ruler',
                'estimated_troops' => $this->estimateKingdomTroops($entity),
                'allies' => $this->getKingdomAllies($entity),
            ];
        }

        return [
            'id' => $entity->id,
            'type' => 'barony',
            'name' => $entity->name,
            'ruler_name' => $entity->baron?->username ?? 'No Baron',
            'kingdom_name' => $entity->kingdom?->name ?? 'Independent',
            'kingdom_id' => $entity->kingdom_id,
            'estimated_troops' => $this->estimateBaronyTroops($entity),
            'allies' => [],
        ];
    }

    /**
     * Estimate kingdom military strength.
     */
    private function estimateKingdomTroops(Kingdom $kingdom): int
    {
        return Army::where('owner_type', 'kingdom')
            ->where('owner_id', $kingdom->id)
            ->operational()
            ->withSum('units', 'count')
            ->get()
            ->sum('units_sum_count') ?? 0;
    }

    /**
     * Estimate barony military strength.
     */
    private function estimateBaronyTroops(Barony $barony): int
    {
        return Army::where('owner_type', 'barony')
            ->where('owner_id', $barony->id)
            ->operational()
            ->withSum('units', 'count')
            ->get()
            ->sum('units_sum_count') ?? 0;
    }

    /**
     * Get kingdom allies (simplified).
     */
    private function getKingdomAllies(Kingdom $kingdom): array
    {
        // Get allied kingdoms through marriage alliances or pacts
        // Simplified for now - could be expanded with DynastyAlliance model
        return [];
    }

    /**
     * Generate a war name based on casus belli.
     */
    private function generateWarName(string $attackerName, string $defenderName, string $casusBelli): string
    {
        return match ($casusBelli) {
            War::CASUS_BELLI_CONQUEST => "{$attackerName}'s Conquest of {$defenderName}",
            War::CASUS_BELLI_CLAIM => "War for {$defenderName}",
            War::CASUS_BELLI_HOLY_WAR => "Holy War against {$defenderName}",
            War::CASUS_BELLI_REBELLION => "{$attackerName}'s Rebellion",
            War::CASUS_BELLI_RAID => "{$attackerName}'s Raid on {$defenderName}",
            default => "War between {$attackerName} and {$defenderName}",
        };
    }

    /**
     * Show the peace negotiation form.
     */
    public function peaceForm(Request $request, War $war): Response
    {
        $user = $request->user();

        // Load all related data
        $war->load([
            'attackerKingdom',
            'defenderKingdom',
            'participants',
            'goals',
        ]);

        // Check if user can offer peace (is war leader)
        $userParticipant = $war->participants
            ->where('participant_type', 'player')
            ->where('participant_id', $user->id)
            ->first();

        $isWarLeader = $userParticipant && $userParticipant->is_war_leader;
        $userSide = $userParticipant?->side;

        // Redirect if not a war leader or war is not active
        if (!$isWarLeader || !$war->isActive()) {
            return Inertia::render('Warfare/PeaceNegotiation', [
                'war' => $this->mapWar($war, $user, true),
                'can_negotiate' => false,
                'error' => !$isWarLeader
                    ? 'Only war leaders can negotiate peace.'
                    : 'This war has already ended.',
                'war_score' => [
                    'attacker' => $war->attacker_war_score,
                    'defender' => $war->defender_war_score,
                ],
                'user_side' => $userSide,
                'territories' => [],
                'player_gold' => $user->gold,
                'enemy_gold' => 0,
                'truce_options' => $this->getTruceOptions(),
            ]);
        }

        // Get transferable territories based on war goals and current occupation
        $territories = $this->getTransferableTerritories($war, $userSide);

        // Calculate enemy's approximate gold for payment limits
        $enemySide = $userSide === 'attacker' ? 'defender' : 'attacker';
        $enemyGold = $this->estimateEnemyGold($war, $enemySide);

        return Inertia::render('Warfare/PeaceNegotiation', [
            'war' => $this->mapWar($war, $user, true),
            'can_negotiate' => true,
            'war_score' => [
                'attacker' => $war->attacker_war_score,
                'defender' => $war->defender_war_score,
            ],
            'user_side' => $userSide,
            'territories' => $territories,
            'player_gold' => $user->gold,
            'enemy_gold' => $enemyGold,
            'truce_options' => $this->getTruceOptions(),
        ]);
    }

    /**
     * Offer peace terms.
     */
    public function offerPeace(Request $request, War $war, WarService $warService): JsonResponse
    {
        $user = $request->user();

        // Check if user can offer peace
        $userParticipant = $war->participants()
            ->where('participant_type', 'player')
            ->where('participant_id', $user->id)
            ->first();

        if (!$userParticipant || !$userParticipant->is_war_leader) {
            return response()->json([
                'success' => false,
                'message' => 'Only war leaders can offer peace.',
            ], 403);
        }

        if (!$war->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'This war has already ended.',
            ], 400);
        }

        $validated = $request->validate([
            'treaty_type' => 'required|in:white_peace,surrender,negotiated',
            'winner_side' => 'nullable|in:attacker,defender',
            'territory_changes' => 'nullable|array',
            'gold_payment' => 'nullable|integer|min:0|max:100000',
            'truce_days' => 'required|integer|min:30|max:3650',
        ]);

        $userSide = $userParticipant->side;

        // For white peace, no winner
        if ($validated['treaty_type'] === 'white_peace') {
            $validated['winner_side'] = null;
            $validated['territory_changes'] = [];
            $validated['gold_payment'] = 0;
        }

        // For surrender, the side offering surrender loses
        if ($validated['treaty_type'] === 'surrender') {
            $validated['winner_side'] = $userSide === 'attacker' ? 'defender' : 'attacker';
        }

        try {
            $treaty = $warService->offerPeace(
                $war,
                $validated['treaty_type'],
                $validated['winner_side'],
                $validated['territory_changes'] ?? [],
                $validated['gold_payment'] ?? 0,
                $validated['truce_days']
            );

            return response()->json([
                'success' => true,
                'message' => 'Peace treaty has been signed. The war is over.',
                'treaty_id' => $treaty->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Respond to a peace offer (accept/reject).
     */
    public function respondToPeace(Request $request, War $war, int $treaty): JsonResponse
    {
        // For the current simplified implementation, peace is directly signed
        // This endpoint is a placeholder for future functionality where peace offers
        // can be pending and require acceptance from the other side
        return response()->json([
            'success' => false,
            'message' => 'Peace response system not yet implemented.',
        ], 501);
    }

    /**
     * Get transferable territories for peace negotiations.
     */
    private function getTransferableTerritories(War $war, string $userSide): array
    {
        $territories = [];

        // Get baronies involved in the war
        $attackerKingdomId = $war->attacker_kingdom_id;
        $defenderKingdomId = $war->defender_kingdom_id;

        // If user is winning, they can demand enemy territories
        $isWinning = ($userSide === 'attacker' && $war->attacker_war_score > $war->defender_war_score)
            || ($userSide === 'defender' && $war->defender_war_score > $war->attacker_war_score);

        // Get enemy baronies that can be demanded
        $enemyKingdomId = $userSide === 'attacker' ? $defenderKingdomId : $attackerKingdomId;
        if ($enemyKingdomId) {
            $enemyBaronies = Barony::where('kingdom_id', $enemyKingdomId)
                ->get()
                ->map(fn ($b) => [
                    'id' => $b->id,
                    'type' => 'barony',
                    'name' => $b->name,
                    'can_demand' => $isWinning,
                    'direction' => 'to_you', // Enemy cedes to you
                ]);
            $territories = array_merge($territories, $enemyBaronies->toArray());
        }

        // Get your baronies that could be ceded (if losing)
        $yourKingdomId = $userSide === 'attacker' ? $attackerKingdomId : $defenderKingdomId;
        if ($yourKingdomId) {
            $yourBaronies = Barony::where('kingdom_id', $yourKingdomId)
                ->get()
                ->map(fn ($b) => [
                    'id' => $b->id,
                    'type' => 'barony',
                    'name' => $b->name,
                    'can_demand' => !$isWinning,
                    'direction' => 'from_you', // You cede to enemy
                ]);
            $territories = array_merge($territories, $yourBaronies->toArray());
        }

        return $territories;
    }

    /**
     * Estimate enemy's gold based on their kingdom/barony treasury.
     */
    private function estimateEnemyGold(War $war, string $enemySide): int
    {
        // Get the enemy's primary entity
        if ($enemySide === 'attacker') {
            if ($war->attacker_type === 'kingdom') {
                $kingdom = Kingdom::find($war->attacker_id);
                return $kingdom?->treasury ?? 0;
            } elseif ($war->attacker_type === 'barony') {
                $barony = Barony::find($war->attacker_id);
                return $barony?->treasury ?? 0;
            }
        } else {
            if ($war->defender_type === 'kingdom') {
                $kingdom = Kingdom::find($war->defender_id);
                return $kingdom?->treasury ?? 0;
            } elseif ($war->defender_type === 'barony') {
                $barony = Barony::find($war->defender_id);
                return $barony?->treasury ?? 0;
            }
        }

        return 0;
    }

    /**
     * Get available truce duration options.
     */
    private function getTruceOptions(): array
    {
        return [
            ['value' => 90, 'label' => '3 months'],
            ['value' => 180, 'label' => '6 months'],
            ['value' => 365, 'label' => '1 year'],
            ['value' => 730, 'label' => '2 years'],
            ['value' => 1825, 'label' => '5 years'],
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Battle;
use App\Models\Siege;
use App\Models\War;
use App\Models\WarParticipant;
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
}

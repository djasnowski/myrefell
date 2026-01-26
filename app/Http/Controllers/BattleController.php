<?php

namespace App\Http\Controllers;

use App\Models\Army;
use App\Models\Battle;
use App\Models\BattleParticipant;
use App\Models\Castle;
use App\Models\Town;
use App\Models\Village;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BattleController extends Controller
{
    /**
     * Display the specified battle.
     */
    public function show(Request $request, Battle $battle): Response
    {
        $battle->load(['war', 'participants.army.commander', 'participants.army.npcCommander']);

        // Get location details
        $location = $this->getLocationDetails($battle->location_type, $battle->location_id);

        // Get war info if part of war
        $warInfo = null;
        if ($battle->war) {
            $warInfo = [
                'id' => $battle->war->id,
                'name' => $battle->war->name,
                'status' => $battle->war->status,
            ];
        }

        // Map participants by side
        $attackers = $battle->participants
            ->where('side', 'attacker')
            ->map(fn($p) => $this->mapParticipant($p))
            ->values()
            ->all();

        $defenders = $battle->participants
            ->where('side', 'defender')
            ->map(fn($p) => $this->mapParticipant($p))
            ->values()
            ->all();

        // Get commanders
        $attackerCommander = $battle->participants
            ->where('side', 'attacker')
            ->where('is_commander', true)
            ->first();

        $defenderCommander = $battle->participants
            ->where('side', 'defender')
            ->where('is_commander', true)
            ->first();

        return Inertia::render('Warfare/BattleShow', [
            'battle' => [
                'id' => $battle->id,
                'name' => $battle->name,
                'battle_type' => $battle->battle_type,
                'status' => $battle->status,
                'phase' => $battle->phase,
                'day' => $battle->day,
                'location' => $location,
                'attacker_troops_start' => $battle->attacker_troops_start,
                'defender_troops_start' => $battle->defender_troops_start,
                'attacker_casualties' => $battle->attacker_casualties,
                'defender_casualties' => $battle->defender_casualties,
                'attacker_remaining' => $battle->attacker_troops_start - $battle->attacker_casualties,
                'defender_remaining' => $battle->defender_troops_start - $battle->defender_casualties,
                'terrain_modifiers' => $battle->terrain_modifiers ?? [],
                'weather_modifiers' => $battle->weather_modifiers ?? [],
                'battle_log' => $battle->battle_log ?? [],
                'started_at' => $battle->started_at?->toISOString(),
                'ended_at' => $battle->ended_at?->toISOString(),
                'is_ongoing' => $battle->isOngoing(),
                'is_ended' => $battle->isEnded(),
            ],
            'attackers' => $attackers,
            'defenders' => $defenders,
            'attacker_commander' => $attackerCommander ? $this->getCommanderName($attackerCommander) : null,
            'defender_commander' => $defenderCommander ? $this->getCommanderName($defenderCommander) : null,
            'war' => $warInfo,
        ]);
    }

    /**
     * Get location details by type and ID.
     */
    private function getLocationDetails(?string $locationType, ?int $locationId): array
    {
        if (!$locationType || !$locationId) {
            return [
                'name' => 'Unknown',
                'type' => 'unknown',
            ];
        }

        $location = match ($locationType) {
            'village' => Village::find($locationId),
            'town' => Town::find($locationId),
            'castle' => Castle::find($locationId),
            default => null,
        };

        if (!$location) {
            return [
                'name' => 'Unknown',
                'type' => $locationType,
            ];
        }

        return [
            'id' => $location->id,
            'name' => $location->name,
            'type' => $locationType,
        ];
    }

    /**
     * Map battle participant to array.
     */
    private function mapParticipant(BattleParticipant $participant): array
    {
        $army = $participant->army;
        $commanderName = 'Unknown';

        if ($army) {
            $commanderName = $army->commander?->username ?? $army->npcCommander?->name ?? 'Unknown';
        }

        return [
            'id' => $participant->id,
            'army_id' => $participant->army_id,
            'army_name' => $army?->name ?? 'Unknown Army',
            'commander_name' => $commanderName,
            'side' => $participant->side,
            'is_commander' => $participant->is_commander,
            'troops_committed' => $participant->troops_committed,
            'casualties' => $participant->casualties,
            'casualty_rate' => $participant->casualty_rate,
            'morale_at_start' => $participant->morale_at_start,
            'morale_at_end' => $participant->morale_at_end,
            'morale_loss' => $participant->morale_loss,
            'outcome' => $participant->outcome,
        ];
    }

    /**
     * Get commander name from participant.
     */
    private function getCommanderName(BattleParticipant $participant): string
    {
        $army = $participant->army;
        if (!$army) {
            return 'Unknown';
        }

        return $army->commander?->username ?? $army->npcCommander?->name ?? 'Unknown';
    }
}

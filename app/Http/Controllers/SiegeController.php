<?php

namespace App\Http\Controllers;

use App\Models\Army;
use App\Models\Castle;
use App\Models\Siege;
use App\Models\Town;
use App\Models\User;
use App\Models\Village;
use App\Services\SiegeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiegeController extends Controller
{
    public function __construct(protected SiegeService $siegeService) {}

    /**
     * Display the specified siege.
     */
    public function show(Request $request, Siege $siege): Response
    {
        $user = $request->user();

        // Load relationships
        $siege->load(['war', 'attackingArmy.commander', 'attackingArmy.units']);

        // Check if user can control this siege (is attacking army commander)
        $canControl = $siege->attackingArmy?->commander_id === $user->id;

        // Get target details
        $target = $this->getTargetDetails($siege->target_type, $siege->target_id);

        // Get war info if part of war
        $warInfo = null;
        if ($siege->war) {
            $warInfo = [
                'id' => $siege->war->id,
                'name' => $siege->war->name,
                'status' => $siege->war->status,
            ];
        }

        // Get attacking army details
        $attackingArmy = $siege->attackingArmy ? $this->mapArmy($siege->attackingArmy) : null;

        return Inertia::render('Warfare/SiegeShow', [
            'siege' => [
                'id' => $siege->id,
                'status' => $siege->status,
                'target' => $target,
                'fortification_level' => $siege->fortification_level,
                'garrison_strength' => $siege->garrison_strength,
                'garrison_morale' => $siege->garrison_morale,
                'supplies_remaining' => $siege->supplies_remaining,
                'days_besieged' => $siege->days_besieged,
                'has_breach' => $siege->has_breach,
                'siege_equipment' => $siege->siege_equipment ?? [],
                'siege_log' => $siege->siege_log ?? [],
                'assault_difficulty' => $siege->assault_difficulty,
                'can_assault' => $siege->canAssault(),
                'is_starving' => $siege->isStarving(),
                'is_active' => $siege->isActive(),
                'is_ended' => $siege->isEnded(),
                'started_at' => $siege->started_at?->toISOString(),
                'ended_at' => $siege->ended_at?->toISOString(),
            ],
            'attacking_army' => $attackingArmy,
            'war' => $warInfo,
            'can_control' => $canControl,
        ]);
    }

    /**
     * Attempt an assault on the siege target.
     */
    public function assault(Request $request, Siege $siege): RedirectResponse
    {
        $user = $request->user();

        // Check if user can control this siege
        if ($siege->attackingArmy?->commander_id !== $user->id) {
            return back()->with('error', 'You are not the commander of the attacking army.');
        }

        if (!$siege->isActive()) {
            return back()->with('error', 'This siege is no longer active.');
        }

        if (!$siege->canAssault()) {
            return back()->with('error', 'Cannot assault: fortifications are too strong and there is no breach.');
        }

        try {
            $results = $this->siegeService->attemptAssault($siege);

            if ($results['success']) {
                return back()->with('success', 'Assault successful! The target has been captured.');
            } else {
                return back()->with('error', "Assault failed. You lost {$results['attacker_casualties']} soldiers.");
            }
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Lift the siege (withdraw).
     */
    public function lift(Request $request, Siege $siege): RedirectResponse
    {
        $user = $request->user();

        // Check if user can control this siege
        if ($siege->attackingArmy?->commander_id !== $user->id) {
            return back()->with('error', 'You are not the commander of the attacking army.');
        }

        if (!$siege->isActive()) {
            return back()->with('error', 'This siege is no longer active.');
        }

        try {
            $this->siegeService->liftSiege($siege);
            return redirect()->route('warfare.armies')->with('success', 'The siege has been lifted.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Build siege equipment.
     */
    public function buildEquipment(Request $request, Siege $siege): RedirectResponse
    {
        $user = $request->user();

        // Check if user can control this siege
        if ($siege->attackingArmy?->commander_id !== $user->id) {
            return back()->with('error', 'You are not the commander of the attacking army.');
        }

        if (!$siege->isActive()) {
            return back()->with('error', 'This siege is no longer active.');
        }

        $validated = $request->validate([
            'equipment' => 'required|string|in:battering_ram,trebuchet,catapult,siege_tower,sappers',
            'count' => 'required|integer|min:1|max:5',
        ]);

        try {
            $this->siegeService->addSiegeEquipment($siege, $validated['equipment'], $validated['count']);
            return back()->with('success', "Built {$validated['count']} {$validated['equipment']}.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get target details by type and ID.
     */
    private function getTargetDetails(string $targetType, int $targetId): array
    {
        $target = match ($targetType) {
            'castle' => Castle::find($targetId),
            'town' => Town::find($targetId),
            'village' => Village::find($targetId),
            default => null,
        };

        if (!$target) {
            return [
                'name' => 'Unknown',
                'type' => $targetType,
            ];
        }

        return [
            'id' => $target->id,
            'name' => $target->name,
            'type' => $targetType,
        ];
    }

    /**
     * Map army to array.
     */
    private function mapArmy(Army $army): array
    {
        $units = [];
        foreach ($army->units as $unit) {
            $units[$unit->unit_type] = ($units[$unit->unit_type] ?? 0) + $unit->count;
        }

        return [
            'id' => $army->id,
            'name' => $army->name,
            'commander_name' => $army->commander?->username ?? $army->npcCommander?->name ?? 'Unknown',
            'status' => $army->status,
            'morale' => $army->morale,
            'supplies' => $army->supplies,
            'total_troops' => $army->total_troops,
            'total_attack' => $army->total_attack,
            'total_defense' => $army->total_defense,
            'units' => $units,
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Kingdom;
use App\Models\LocationActivityLog;
use App\Models\Town;
use App\Models\Village;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TavernController extends Controller
{
    /**
     * Show the tavern page.
     */
    public function index(Request $request, $village = null, $town = null): Response
    {
        $user = $request->user();

        // Resolve model from ID if needed
        $location = null;
        $locationType = null;

        if ($village) {
            $location = $village instanceof Village ? $village : Village::find($village);
            $locationType = 'village';
        } elseif ($town) {
            $location = $town instanceof Town ? $town : Town::find($town);
            $locationType = 'town';
        }

        // Get recent activity at this location (rumors/gossip)
        $recentActivity = [];
        if ($location && $locationType) {
            try {
                $recentActivity = LocationActivityLog::atLocation($locationType, $location->id)
                    ->recent(20)
                    ->with('user:id,username')
                    ->get()
                    ->map(fn ($log) => [
                        'id' => $log->id,
                        'username' => $log->user->username ?? 'Unknown',
                        'description' => $log->description,
                        'type' => $log->activity_type,
                        'subtype' => $log->activity_subtype,
                        'time_ago' => $log->created_at->diffForHumans(),
                    ]);
            } catch (\Illuminate\Database\QueryException $e) {
                $recentActivity = [];
            }
        }

        // Calculate rest cost and benefits
        $restCost = 10; // gold
        $energyRestored = min(50, $user->max_energy - $user->energy);

        return Inertia::render('Tavern/Index', [
            'location' => $location ? [
                'type' => $locationType,
                'id' => $location->id,
                'name' => $location->name,
            ] : null,
            'player' => [
                'energy' => $user->energy,
                'max_energy' => $user->max_energy,
                'gold' => $user->gold,
            ],
            'rest' => [
                'cost' => $restCost,
                'energy_restored' => $energyRestored,
                'can_rest' => $user->gold >= $restCost && $user->energy < $user->max_energy,
            ],
            'recent_activity' => $recentActivity,
        ]);
    }

    /**
     * Rest at the tavern to restore energy.
     */
    public function rest(Request $request)
    {
        $user = $request->user();
        $restCost = 10;
        $energyRestored = min(50, $user->max_energy - $user->energy);

        if ($user->gold < $restCost) {
            return back()->withErrors(['error' => "You need {$restCost}g to rest at the tavern."]);
        }

        if ($user->energy >= $user->max_energy) {
            return back()->withErrors(['error' => 'You are already fully rested.']);
        }

        $user->decrement('gold', $restCost);
        $user->increment('energy', $energyRestored);

        // Log activity
        if ($user->current_location_type && $user->current_location_id) {
            try {
                LocationActivityLog::log(
                    userId: $user->id,
                    locationType: $user->current_location_type,
                    locationId: $user->current_location_id,
                    activityType: LocationActivityLog::TYPE_REST,
                    description: "{$user->username} rested at the tavern",
                    metadata: ['energy_restored' => $energyRestored, 'gold_spent' => $restCost]
                );
            } catch (\Illuminate\Database\QueryException $e) {
                // Table may not exist
            }
        }

        return back()->with('success', "You rest at the tavern and recover {$energyRestored} energy.");
    }

    /**
     * Determine location type from model.
     */
    protected function getLocationType($location): ?string
    {
        return match (true) {
            $location instanceof Village => 'village',
            $location instanceof Town => 'town',
            $location instanceof Barony => 'barony',
            $location instanceof Duchy => 'duchy',
            $location instanceof Kingdom => 'kingdom',
            default => null,
        };
    }
}

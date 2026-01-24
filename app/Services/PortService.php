<?php

namespace App\Services;

use App\Models\User;
use App\Models\Village;
use Illuminate\Support\Facades\DB;

class PortService
{
    /**
     * Cost for ship travel between kingdoms.
     */
    public const SHIP_COST = 5000;

    /**
     * Travel time in minutes for ship travel.
     */
    public const SHIP_TRAVEL_TIME = 10;

    /**
     * Check if user can access the port at their current location.
     */
    public function canAccessPort(User $user): bool
    {
        if ($user->isTraveling()) {
            return false;
        }

        if ($user->current_location_type !== 'village') {
            return false;
        }

        $village = Village::find($user->current_location_id);

        return $village && $village->is_port;
    }

    /**
     * Get the current port village.
     */
    public function getCurrentPort(User $user): ?Village
    {
        if ($user->current_location_type !== 'village') {
            return null;
        }

        $village = Village::find($user->current_location_id);

        return $village && $village->is_port ? $village : null;
    }

    /**
     * Get available ship destinations from the current port.
     */
    public function getAvailableDestinations(User $user): array
    {
        $currentPort = $this->getCurrentPort($user);

        if (! $currentPort) {
            return [];
        }

        // Get current kingdom
        $currentKingdom = $currentPort->castle?->town?->kingdom ?? $currentPort->castle?->kingdom;

        if (! $currentKingdom) {
            return [];
        }

        // Get all ports except current kingdom's port
        return Village::where('is_port', true)
            ->whereHas('castle.town.kingdom', fn ($q) => $q->where('id', '!=', $currentKingdom->id))
            ->with('castle.town.kingdom')
            ->get()
            ->map(fn ($port) => [
                'id' => $port->id,
                'name' => $port->name,
                'kingdom_id' => $port->castle->town->kingdom->id,
                'kingdom_name' => $port->castle->town->kingdom->name,
                'biome' => $port->biome,
                'cost' => self::SHIP_COST,
                'travel_time' => self::SHIP_TRAVEL_TIME,
            ])
            ->toArray();
    }

    /**
     * Book passage to a destination port.
     */
    public function bookPassage(User $user, int $destinationPortId): array
    {
        // Validate at a port
        if (! $this->canAccessPort($user)) {
            return [
                'success' => false,
                'message' => 'You must be at a port to book ship passage.',
            ];
        }

        // Validate destination exists and is a port
        $destination = Village::where('id', $destinationPortId)
            ->where('is_port', true)
            ->first();

        if (! $destination) {
            return [
                'success' => false,
                'message' => 'Invalid destination port.',
            ];
        }

        // Validate not traveling to current location
        if ($user->current_location_type === 'village' && $user->current_location_id === $destinationPortId) {
            return [
                'success' => false,
                'message' => 'You are already at this port.',
            ];
        }

        // Validate destination is in different kingdom
        $currentPort = $this->getCurrentPort($user);
        $currentKingdom = $currentPort->castle?->town?->kingdom;
        $destKingdom = $destination->castle?->town?->kingdom;

        if ($currentKingdom && $destKingdom && $currentKingdom->id === $destKingdom->id) {
            return [
                'success' => false,
                'message' => 'Ship travel is only available between different kingdoms.',
            ];
        }

        // Validate has enough gold
        if ($user->gold < self::SHIP_COST) {
            return [
                'success' => false,
                'message' => 'Not enough gold. Ship passage costs ' . number_format(self::SHIP_COST) . ' gold.',
            ];
        }

        return DB::transaction(function () use ($user, $destination) {
            // Deduct gold
            $user->decrement('gold', self::SHIP_COST);

            // Set travel state (dev mode: 2 seconds for testing)
            if (app()->environment('local') && TravelService::DEV_TRAVEL_SECONDS !== null) {
                $arrivesAt = now()->addSeconds(TravelService::DEV_TRAVEL_SECONDS);
            } else {
                $arrivesAt = now()->addMinutes(self::SHIP_TRAVEL_TIME);
            }

            $user->is_traveling = true;
            $user->travel_destination_type = 'village';
            $user->travel_destination_id = $destination->id;
            $user->travel_started_at = now();
            $user->travel_arrives_at = $arrivesAt;
            $user->save();

            return [
                'success' => true,
                'message' => 'Bon voyage! Your ship departs for ' . $destination->name . '.',
                'destination' => [
                    'type' => 'village',
                    'id' => $destination->id,
                    'name' => $destination->name,
                    'kingdom' => $destination->castle?->town?->kingdom?->name ?? 'Unknown',
                ],
                'travel_time_minutes' => self::SHIP_TRAVEL_TIME,
                'arrives_at' => $arrivesAt->toIso8601String(),
                'cost' => self::SHIP_COST,
                'gold_remaining' => $user->fresh()->gold,
            ];
        });
    }

    /**
     * Get port info for the current location.
     */
    public function getPortInfo(User $user): ?array
    {
        $currentPort = $this->getCurrentPort($user);

        if (! $currentPort) {
            return null;
        }

        $currentPort->load('castle.town.kingdom');
        $kingdom = $currentPort->castle?->town?->kingdom;

        return [
            'port_id' => $currentPort->id,
            'port_name' => $currentPort->name,
            'port_biome' => $currentPort->biome,
            'kingdom_id' => $kingdom?->id,
            'kingdom_name' => $kingdom?->name ?? 'Unknown',
            'harbormaster_name' => $this->getHarbormasterName($kingdom?->name ?? ''),
            'harbormaster_title' => 'Harbormaster',
            'gold' => $user->gold,
            'ship_cost' => self::SHIP_COST,
            'destinations' => $this->getAvailableDestinations($user),
        ];
    }

    /**
     * Get harbormaster name based on kingdom.
     */
    public function getHarbormasterName(string $kingdomName): string
    {
        return match ($kingdomName) {
            'Valdoria' => 'Captain Aldric',
            'Sandmar' => 'First Mate Hassan',
            'Frostholm' => 'Skipper Bjorn',
            'Ashenfell' => 'Navigator Ember',
            default => 'The Harbormaster',
        };
    }
}

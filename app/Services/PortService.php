<?php

namespace App\Services;

use App\Models\User;
use App\Models\Village;
use Illuminate\Support\Facades\DB;

class PortService
{
    /**
     * Base cost for ship travel.
     */
    public const BASE_SHIP_COST = 4513;

    /**
     * Gold per minute of travel time.
     */
    public const GOLD_PER_MINUTE = 100;

    /**
     * Base travel time in minutes for ship travel.
     */
    public const BASE_SHIP_TRAVEL_TIME = 5;

    /**
     * Minutes per 100 units of distance.
     */
    public const MINUTES_PER_100_DISTANCE = 2;

    /**
     * Check if user can access the port at their current location.
     */
    public function canAccessPort(User $user): bool
    {
        if ($user->isTraveling() || $user->isInInfirmary()) {
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
     * Calculate travel time between two ports based on distance.
     */
    public function calculateTravelTime(Village $from, Village $to): int
    {
        $distance = sqrt(
            pow($to->coordinates_x - $from->coordinates_x, 2) +
            pow($to->coordinates_y - $from->coordinates_y, 2)
        );

        return (int) ceil(self::BASE_SHIP_TRAVEL_TIME + ($distance / 100) * self::MINUTES_PER_100_DISTANCE);
    }

    /**
     * Calculate cost based on travel time.
     */
    public function calculateCost(int $travelTime): int
    {
        return self::BASE_SHIP_COST + ($travelTime * self::GOLD_PER_MINUTE);
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
        $currentKingdom = $currentPort->barony?->kingdom;

        if (! $currentKingdom) {
            return [];
        }

        // Get all ports except current kingdom's port
        return Village::where('is_port', true)
            ->whereHas('barony.kingdom', fn ($q) => $q->where('id', '!=', $currentKingdom->id))
            ->with('barony.kingdom')
            ->get()
            ->map(function ($port) use ($currentPort) {
                $travelTime = $this->calculateTravelTime($currentPort, $port);

                return [
                    'id' => $port->id,
                    'name' => $port->name,
                    'kingdom_id' => $port->barony->kingdom->id,
                    'kingdom_name' => $port->barony->kingdom->name,
                    'biome' => $port->biome,
                    'cost' => $this->calculateCost($travelTime),
                    'travel_time' => $travelTime,
                ];
            })
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
        $currentKingdom = $currentPort->barony?->kingdom;
        $destKingdom = $destination->barony?->kingdom;

        if ($currentKingdom && $destKingdom && $currentKingdom->id === $destKingdom->id) {
            return [
                'success' => false,
                'message' => 'Ship travel is only available between different kingdoms.',
            ];
        }

        $currentPort = $this->getCurrentPort($user);
        $travelTime = $this->calculateTravelTime($currentPort, $destination);
        $cost = $this->calculateCost($travelTime);

        // Validate has enough gold
        if ($user->gold < $cost) {
            return [
                'success' => false,
                'message' => 'Not enough gold. Ship passage costs '.number_format($cost).' gold.',
            ];
        }

        return DB::transaction(function () use ($user, $destination, $travelTime, $cost) {
            // Deduct gold
            $user->decrement('gold', $cost);

            // Set travel state (dev mode: 2 seconds for testing)
            if (app()->environment('local') && TravelService::DEV_TRAVEL_SECONDS !== null) {
                $arrivesAt = now()->addSeconds(TravelService::DEV_TRAVEL_SECONDS);
            } else {
                $arrivesAt = now()->addMinutes($travelTime);
            }

            $user->is_traveling = true;
            $user->travel_destination_type = 'village';
            $user->travel_destination_id = $destination->id;
            $user->travel_started_at = now();
            $user->travel_arrives_at = $arrivesAt;
            $user->save();

            return [
                'success' => true,
                'message' => 'Bon voyage! Your ship departs for '.$destination->name.'.',
                'destination' => [
                    'type' => 'village',
                    'id' => $destination->id,
                    'name' => $destination->name,
                    'kingdom' => $destination->barony?->kingdom?->name ?? 'Unknown',
                ],
                'travel_time_minutes' => $travelTime,
                'arrives_at' => $arrivesAt->toIso8601String(),
                'cost' => $cost,
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

        $currentPort->load('barony.kingdom');
        $kingdom = $currentPort->barony?->kingdom;

        return [
            'port_id' => $currentPort->id,
            'port_name' => $currentPort->name,
            'port_biome' => $currentPort->biome,
            'kingdom_id' => $kingdom?->id,
            'kingdom_name' => $kingdom?->name ?? 'Unknown',
            'harbormaster_name' => $this->getHarbormasterName($kingdom?->name ?? ''),
            'harbormaster_title' => 'Harbormaster',
            'gold' => $user->gold,
            'base_ship_cost' => self::BASE_SHIP_COST,
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

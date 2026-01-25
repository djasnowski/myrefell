<?php

namespace App\Services;

use App\Models\Barony;
use App\Models\Kingdom;
use App\Models\Town;
use App\Models\User;
use App\Models\Village;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TravelService
{
    /**
     * Distance divisor for travel time calculation.
     * Travel time = ceil(distance / DISTANCE_DIVISOR) in minutes.
     */
    public const DISTANCE_DIVISOR = 10;

    /**
     * Energy cost for travel.
     */
    public const ENERGY_COST = 5;

    /**
     * Dev mode: all travel takes this many seconds (set to null to disable).
     */
    public const DEV_TRAVEL_SECONDS = 2;

    /**
     * Start traveling to a destination.
     */
    public function startTravel(User $user, string $destinationType, int $destinationId): array
    {
        // Validate not already traveling
        if ($user->isTraveling()) {
            throw new \InvalidArgumentException('You are already traveling.');
        }

        // Validate destination exists
        $destination = $this->getDestination($destinationType, $destinationId);
        if (! $destination) {
            throw new \InvalidArgumentException('Invalid destination.');
        }

        // Check if already at destination
        if ($user->current_location_type === $destinationType && $user->current_location_id === $destinationId) {
            throw new \InvalidArgumentException('You are already at this location.');
        }

        // Determine if using horse or walking
        $usingHorse = $user->hasHorseWithMe();
        $playerHorse = $usingHorse ? $user->horse : null;

        if ($usingHorse) {
            // Check horse stamina
            $staminaCost = $playerHorse->stamina_cost;
            if (!$playerHorse->hasStamina($staminaCost)) {
                throw new \InvalidArgumentException("Your horse is too tired to travel. Rest at a stable or travel on foot.");
            }
        } else {
            // Check player energy for walking
            if (! $user->hasEnergy(self::ENERGY_COST)) {
                throw new \InvalidArgumentException('Not enough energy to travel.');
            }
        }

        // Calculate travel time based on coordinate distance
        // In dev mode, use fast travel for testing
        if (app()->environment('local') && self::DEV_TRAVEL_SECONDS !== null) {
            $arrivesAt = now()->addSeconds(self::DEV_TRAVEL_SECONDS);
            $travelMinutes = 0;
        } else {
            $travelMinutes = $this->calculateTravelTime($user, $destinationType, $destinationId);
            $arrivesAt = now()->addMinutes($travelMinutes);
        }

        return DB::transaction(function () use ($user, $destinationType, $destinationId, $arrivesAt, $destination, $travelMinutes, $usingHorse, $playerHorse) {
            if ($usingHorse) {
                // Consume horse stamina
                $playerHorse->consumeStamina($playerHorse->stamina_cost);
            } else {
                // Consume player energy
                $user->consumeEnergy(self::ENERGY_COST);
            }

            // Set travel state
            $user->is_traveling = true;
            $user->travel_destination_type = $destinationType;
            $user->travel_destination_id = $destinationId;
            $user->travel_started_at = now();
            $user->travel_arrives_at = $arrivesAt;
            $user->save();

            return [
                'destination' => [
                    'type' => $destinationType,
                    'id' => $destinationId,
                    'name' => $destination->name,
                ],
                'travel_time_minutes' => $travelMinutes,
                'arrives_at' => $arrivesAt->toIso8601String(),
                'started_at' => now()->toIso8601String(),
                'used_horse' => $usingHorse,
            ];
        });
    }

    /**
     * Check and complete travel if arrived.
     */
    public function checkArrival(User $user): ?array
    {
        if (! $user->is_traveling) {
            return null;
        }

        if ($user->travel_arrives_at->isFuture()) {
            return null;
        }

        // Player has arrived
        return $this->completeTravel($user);
    }

    /**
     * Complete the travel and update location.
     */
    public function completeTravel(User $user): array
    {
        $destination = $this->getDestination(
            $user->travel_destination_type,
            $user->travel_destination_id
        );

        return DB::transaction(function () use ($user, $destination) {
            $previousLocation = [
                'type' => $user->current_location_type,
                'id' => $user->current_location_id,
            ];

            // Update location
            $user->current_location_type = $user->travel_destination_type;
            $user->current_location_id = $user->travel_destination_id;

            // Clear travel state
            $user->is_traveling = false;
            $user->travel_destination_type = null;
            $user->travel_destination_id = null;
            $user->travel_started_at = null;
            $user->travel_arrives_at = null;
            $user->save();

            return [
                'arrived' => true,
                'location' => [
                    'type' => $user->current_location_type,
                    'id' => $user->current_location_id,
                    'name' => $destination?->name ?? 'Unknown',
                ],
                'previous_location' => $previousLocation,
            ];
        });
    }

    /**
     * Cancel travel and return to origin.
     */
    public function cancelTravel(User $user): bool
    {
        if (! $user->is_traveling) {
            return false;
        }

        $user->is_traveling = false;
        $user->travel_destination_type = null;
        $user->travel_destination_id = null;
        $user->travel_started_at = null;
        $user->travel_arrives_at = null;

        return $user->save();
    }

    /**
     * Get travel status for a user.
     */
    public function getTravelStatus(User $user): ?array
    {
        if (! $user->is_traveling) {
            return null;
        }

        $destination = $this->getDestination(
            $user->travel_destination_type,
            $user->travel_destination_id
        );

        $totalSeconds = $user->travel_started_at->diffInSeconds($user->travel_arrives_at);
        $elapsedSeconds = $user->travel_started_at->diffInSeconds(now());
        $remainingSeconds = max(0, $user->travel_arrives_at->timestamp - now()->timestamp);

        return [
            'is_traveling' => true,
            'destination' => [
                'type' => $user->travel_destination_type,
                'id' => $user->travel_destination_id,
                'name' => $destination?->name ?? 'Unknown',
            ],
            'started_at' => $user->travel_started_at->toIso8601String(),
            'arrives_at' => $user->travel_arrives_at->toIso8601String(),
            'total_seconds' => $totalSeconds,
            'elapsed_seconds' => $elapsedSeconds,
            'remaining_seconds' => $remainingSeconds,
            'progress_percent' => $totalSeconds > 0 ? min(100, ($elapsedSeconds / $totalSeconds) * 100) : 100,
            'has_arrived' => $remainingSeconds <= 0,
        ];
    }

    /**
     * Maximum distance to show nearby destinations.
     */
    public const MAX_TRAVEL_DISTANCE = 100;

    /**
     * Get available destinations from current location based on proximity.
     */
    public function getAvailableDestinations(User $user): array
    {
        $currentCoords = $this->getCurrentCoordinates($user);
        $currentType = $user->current_location_type;
        $currentId = $user->current_location_id;
        $speedMultiplier = $user->getTravelSpeedMultiplier();

        $destinations = [];

        // Get all nearby villages (includes hamlets)
        $villages = Village::all();
        foreach ($villages as $village) {
            // Skip current location
            if ($currentType === 'village' && $currentId === $village->id) {
                continue;
            }

            $distance = $this->calculateDistance($currentCoords, $village->coordinates_x, $village->coordinates_y);
            if ($distance <= self::MAX_TRAVEL_DISTANCE) {
                $baseTime = $distance / self::DISTANCE_DIVISOR;
                $locationType = $village->isHamlet() ? 'hamlet' : 'village';
                $destinations[] = [
                    'type' => 'village', // Still use 'village' for DB lookup
                    'display_type' => $locationType,
                    'id' => $village->id,
                    'name' => $village->name,
                    'biome' => $village->biome,
                    'distance' => $distance,
                    'travel_time' => max(1, (int) ceil($baseTime / $speedMultiplier)),
                    'is_hamlet' => $village->isHamlet(),
                ];
            }
        }

        // Get all nearby baronies
        $baronies = Barony::all();
        foreach ($baronies as $barony) {
            if ($currentType === 'barony' && $currentId === $barony->id) {
                continue;
            }

            $distance = $this->calculateDistance($currentCoords, $barony->coordinates_x, $barony->coordinates_y);
            if ($distance <= self::MAX_TRAVEL_DISTANCE) {
                $baseTime = $distance / self::DISTANCE_DIVISOR;
                $destinations[] = [
                    'type' => 'barony',
                    'display_type' => 'barony',
                    'id' => $barony->id,
                    'name' => $barony->name,
                    'biome' => $barony->biome,
                    'distance' => $distance,
                    'travel_time' => max(1, (int) ceil($baseTime / $speedMultiplier)),
                ];
            }
        }

        // Get all nearby towns
        $towns = Town::all();
        foreach ($towns as $town) {
            if ($currentType === 'town' && $currentId === $town->id) {
                continue;
            }

            $distance = $this->calculateDistance($currentCoords, $town->coordinates_x, $town->coordinates_y);
            if ($distance <= self::MAX_TRAVEL_DISTANCE) {
                $baseTime = $distance / self::DISTANCE_DIVISOR;
                $destinations[] = [
                    'type' => 'town',
                    'display_type' => 'town',
                    'id' => $town->id,
                    'name' => $town->name,
                    'biome' => $town->biome,
                    'distance' => $distance,
                    'travel_time' => max(1, (int) ceil($baseTime / $speedMultiplier)),
                ];
            }
        }

        // Sort by distance (closest first)
        usort($destinations, fn ($a, $b) => $a['distance'] <=> $b['distance']);

        return $destinations;
    }

    /**
     * Calculate Euclidean distance between two points.
     */
    protected function calculateDistance(array $from, float $toX, float $toY): float
    {
        return sqrt(pow($toX - $from['x'], 2) + pow($toY - $from['y'], 2));
    }

    /**
     * Calculate travel time based on coordinate distance.
     * Returns time in minutes: 1 minute per 10 coordinate units (minimum 1 minute).
     * Applies horse speed multiplier if user has a horse.
     */
    protected function calculateTravelTime(User $user, string $destType, int $destId): int
    {
        // Get current location coordinates
        $currentCoords = $this->getCurrentCoordinates($user);

        // Get destination coordinates
        $destination = $this->getDestination($destType, $destId);
        $destX = $destination->coordinates_x ?? 0;
        $destY = $destination->coordinates_y ?? 0;

        // Euclidean distance, 1 minute per 10 units (min 1 minute)
        $distance = sqrt(pow($destX - $currentCoords['x'], 2) + pow($destY - $currentCoords['y'], 2));
        $baseTime = $distance / self::DISTANCE_DIVISOR;

        // Apply horse speed multiplier (faster = lower time)
        $speedMultiplier = $user->getTravelSpeedMultiplier();
        $adjustedTime = $baseTime / $speedMultiplier;

        return max(1, (int) ceil($adjustedTime));
    }

    /**
     * Get the current location's coordinates.
     */
    protected function getCurrentCoordinates(User $user): array
    {
        $locationType = $user->current_location_type ?? 'wilderness';
        $locationId = $user->current_location_id ?? 0;

        if ($locationType === 'wilderness') {
            // Wilderness defaults to center of map
            return ['x' => 0, 'y' => 0];
        }

        $location = $this->getDestination($locationType, $locationId);

        return [
            'x' => $location->coordinates_x ?? 0,
            'y' => $location->coordinates_y ?? 0,
        ];
    }

    /**
     * Get destination model.
     */
    protected function getDestination(string $type, int $id): ?object
    {
        if ($type === 'wilderness') {
            return (object) ['name' => 'The Wilderness', 'id' => 0];
        }

        return match ($type) {
            'village', 'hamlet' => Village::find($id),
            'barony' => Barony::find($id),
            'town' => Town::find($id),
            'kingdom' => Kingdom::find($id),
            default => null,
        };
    }
}

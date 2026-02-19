<?php

namespace App\Services;

use App\Models\Barony;
use App\Models\Kingdom;
use App\Models\LocationActivityLog;
use App\Models\Town;
use App\Models\User;
use App\Models\Village;
use App\Models\WorldState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TravelService
{
    public function __construct(
        protected BlessingEffectService $blessingEffectService,
        protected BeliefEffectService $beliefEffectService,
        protected BiomeService $biomeService
    ) {}

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
     * Use the Skip button on /travel instead for testing.
     */
    public const DEV_TRAVEL_SECONDS = null;

    /**
     * Start traveling to a destination.
     */
    public function startTravel(User $user, string $destinationType, int $destinationId): array
    {
        // Validate not already traveling
        if ($user->isTraveling()) {
            throw new \InvalidArgumentException('You are already traveling.');
        }

        // Validate not in infirmary
        if ($user->isInInfirmary()) {
            throw new \InvalidArgumentException('You cannot travel while recovering in the infirmary.');
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
            if (! $playerHorse->hasStamina($staminaCost)) {
                throw new \InvalidArgumentException('Your horse is too tired to travel. Rest at a stable or travel on foot.');
            }
        } else {
            // Calculate energy cost with belief penalty (Pilgrimage belief)
            $energyCost = $this->getTravelEnergyCost($user);
            if (! $user->hasEnergy($energyCost)) {
                throw new \InvalidArgumentException('Not enough energy to travel.');
            }
        }

        // Calculate travel time based on coordinate distance
        // In dev mode, use fast travel for testing
        if (app()->environment('local') && self::DEV_TRAVEL_SECONDS !== null) {
            $travelSeconds = self::DEV_TRAVEL_SECONDS;
        } else {
            $travelSeconds = $this->calculateTravelTimeSeconds($user, $destinationType, $destinationId);
        }
        $arrivesAt = now()->addSeconds($travelSeconds);

        return DB::transaction(function () use ($user, $destinationType, $destinationId, $arrivesAt, $destination, $travelSeconds, $usingHorse, $playerHorse) {
            if ($usingHorse) {
                // Consume horse stamina
                $playerHorse->consumeStamina($playerHorse->stamina_cost);
            } else {
                // Consume player energy (with belief penalty applied)
                $user->consumeEnergy($this->getTravelEnergyCost($user));
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
                'travel_time_seconds' => $travelSeconds,
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

            // Update kingdom tracking for biome attunement
            $this->biomeService->updatePlayerKingdom($user);

            // Log departure from old location
            $destinationName = $destination?->name ?? 'Unknown';
            if ($previousLocation['type'] && $previousLocation['id']) {
                try {
                    LocationActivityLog::log(
                        userId: $user->id,
                        locationType: $previousLocation['type'],
                        locationId: $previousLocation['id'],
                        activityType: LocationActivityLog::TYPE_TRAVEL,
                        description: "{$user->username} departed for {$destinationName}",
                        activitySubtype: 'departure',
                    );
                } catch (\Illuminate\Database\QueryException $e) {
                    // Table may not exist yet
                }
            }

            // Log arrival at new location
            try {
                $previousName = $this->getDestination($previousLocation['type'], $previousLocation['id'])?->name ?? 'Unknown';
                LocationActivityLog::log(
                    userId: $user->id,
                    locationType: $user->current_location_type,
                    locationId: $user->current_location_id,
                    activityType: LocationActivityLog::TYPE_TRAVEL,
                    description: "{$user->username} arrived from {$previousName}",
                    activitySubtype: 'arrival',
                );
            } catch (\Illuminate\Database\QueryException $e) {
                // Table may not exist yet
            }

            return [
                'arrived' => true,
                'location' => [
                    'type' => $user->current_location_type,
                    'id' => $user->current_location_id,
                    'name' => $destinationName,
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
        $seasonalModifier = WorldState::current()->getTravelModifier();

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
                $adjustedTime = ($baseTime / $speedMultiplier) * $seasonalModifier;
                $locationType = $village->isHamlet() ? 'hamlet' : 'village';
                $destinations[] = [
                    'type' => 'village', // Still use 'village' for DB lookup
                    'display_type' => $locationType,
                    'id' => $village->id,
                    'name' => $village->name,
                    'biome' => $village->biome,
                    'distance' => $distance,
                    'travel_time' => max(1, (int) round($adjustedTime)),
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
                $adjustedTime = ($baseTime / $speedMultiplier) * $seasonalModifier;
                $destinations[] = [
                    'type' => 'barony',
                    'display_type' => 'barony',
                    'id' => $barony->id,
                    'name' => $barony->name,
                    'biome' => $barony->biome,
                    'distance' => $distance,
                    'travel_time' => max(1, (int) round($adjustedTime)),
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
                $adjustedTime = ($baseTime / $speedMultiplier) * $seasonalModifier;
                $destinations[] = [
                    'type' => 'town',
                    'display_type' => 'town',
                    'id' => $town->id,
                    'name' => $town->name,
                    'biome' => $town->biome,
                    'distance' => $distance,
                    'travel_time' => max(1, (int) round($adjustedTime)),
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
     * Returns time in seconds (minimum 60 seconds).
     * Applies horse speed multiplier if user has a horse.
     * Applies seasonal travel modifier based on current world time.
     */
    protected function calculateTravelTimeSeconds(User $user, string $destType, int $destId): int
    {
        // Get current location coordinates
        $currentCoords = $this->getCurrentCoordinates($user);

        // Get destination coordinates
        $destination = $this->getDestination($destType, $destId);
        $destX = $destination->coordinates_x ?? 0;
        $destY = $destination->coordinates_y ?? 0;

        // Euclidean distance, 1 minute per 10 units
        $distance = sqrt(pow($destX - $currentCoords['x'], 2) + pow($destY - $currentCoords['y'], 2));
        $baseTimeMinutes = $distance / self::DISTANCE_DIVISOR;

        // Apply horse speed multiplier (faster = lower time)
        $speedMultiplier = $user->getTravelSpeedMultiplier();
        $adjustedTime = $baseTimeMinutes / $speedMultiplier;

        // Apply seasonal travel modifier (>1 = slower, <1 = faster)
        $seasonalModifier = WorldState::current()->getTravelModifier();
        $adjustedTime = $adjustedTime * $seasonalModifier;

        // Apply agility bonus: 0.5% faster travel per agility level (max 25% at level 50+)
        $agilityLevel = $user->getSkillLevel('agility');
        $agilityBonus = min(0.25, $agilityLevel * 0.005); // Cap at 25% reduction
        $adjustedTime = $adjustedTime * (1 - $agilityBonus);

        // Apply blessing travel speed bonus (e.g., 25 = 25% faster travel)
        $travelSpeedBonus = $this->blessingEffectService->getEffect($user, 'travel_speed_bonus');
        if ($travelSpeedBonus > 0) {
            $adjustedTime = $adjustedTime * (1 - $travelSpeedBonus / 100);
        }

        // Convert to seconds, minimum 60 seconds
        return max(60, (int) round($adjustedTime * 60));
    }

    /**
     * Get travel energy cost with belief modifiers.
     */
    public function getTravelEnergyCost(User $user): int
    {
        $baseCost = self::ENERGY_COST;

        // Apply belief travel energy penalty (Pilgrimage belief: +10% energy cost)
        $travelEnergyPenalty = $this->beliefEffectService->getEffect($user, 'travel_energy_penalty');
        if ($travelEnergyPenalty > 0) {
            $baseCost = (int) ceil($baseCost * (1 + $travelEnergyPenalty / 100));
        }

        return $baseCost;
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

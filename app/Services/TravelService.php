<?php

namespace App\Services;

use App\Models\Castle;
use App\Models\Kingdom;
use App\Models\Town;
use App\Models\User;
use App\Models\Village;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TravelService
{
    /**
     * Travel times in minutes between location types.
     */
    public const TRAVEL_TIMES = [
        'village_to_castle' => 5,
        'village_to_town' => 15,
        'village_to_wilderness' => 3,
        'village_to_village' => 10,
        'castle_to_village' => 5,
        'castle_to_town' => 10,
        'castle_to_castle' => 20,
        'town_to_village' => 15,
        'town_to_castle' => 10,
        'town_to_town' => 30,
        'wilderness_to_village' => 3,
        'wilderness_to_castle' => 8,
    ];

    /**
     * Energy cost for travel.
     */
    public const ENERGY_COST = 5;

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

        // Check energy
        if (! $user->hasEnergy(self::ENERGY_COST)) {
            throw new \InvalidArgumentException('Not enough energy to travel.');
        }

        // Calculate travel time
        $travelMinutes = $this->calculateTravelTime($user, $destinationType);
        $arrivesAt = now()->addMinutes($travelMinutes);

        return DB::transaction(function () use ($user, $destinationType, $destinationId, $arrivesAt, $destination, $travelMinutes) {
            // Consume energy
            $user->consumeEnergy(self::ENERGY_COST);

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
        $remainingSeconds = max(0, $user->travel_arrives_at->diffInSeconds(now(), false));

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
     * Get available destinations from current location.
     */
    public function getAvailableDestinations(User $user): array
    {
        $destinations = [];
        $homeVillage = $user->homeVillage;

        if (! $homeVillage) {
            return $destinations;
        }

        $homeVillage->load('castle.town.kingdom');

        $currentType = $user->current_location_type;
        $currentId = $user->current_location_id;

        // From village
        if ($currentType === 'village') {
            // Can go to castle
            if ($homeVillage->castle) {
                $destinations[] = [
                    'type' => 'castle',
                    'id' => $homeVillage->castle->id,
                    'name' => $homeVillage->castle->name,
                    'travel_time' => self::TRAVEL_TIMES['village_to_castle'],
                ];
            }
            // Can go to town
            if ($homeVillage->castle?->town) {
                $destinations[] = [
                    'type' => 'town',
                    'id' => $homeVillage->castle->town->id,
                    'name' => $homeVillage->castle->town->name,
                    'travel_time' => self::TRAVEL_TIMES['village_to_town'],
                ];
            }
            // Can go to wilderness
            $destinations[] = [
                'type' => 'wilderness',
                'id' => 0,
                'name' => 'The Wilderness',
                'travel_time' => self::TRAVEL_TIMES['village_to_wilderness'],
            ];
        }

        // From castle
        if ($currentType === 'castle') {
            // Can go to home village
            $destinations[] = [
                'type' => 'village',
                'id' => $homeVillage->id,
                'name' => $homeVillage->name,
                'travel_time' => self::TRAVEL_TIMES['castle_to_village'],
            ];
            // Can go to town
            if ($homeVillage->castle?->town) {
                $destinations[] = [
                    'type' => 'town',
                    'id' => $homeVillage->castle->town->id,
                    'name' => $homeVillage->castle->town->name,
                    'travel_time' => self::TRAVEL_TIMES['castle_to_town'],
                ];
            }
        }

        // From town
        if ($currentType === 'town') {
            // Can go to home village
            $destinations[] = [
                'type' => 'village',
                'id' => $homeVillage->id,
                'name' => $homeVillage->name,
                'travel_time' => self::TRAVEL_TIMES['town_to_village'],
            ];
            // Can go to castle
            if ($homeVillage->castle) {
                $destinations[] = [
                    'type' => 'castle',
                    'id' => $homeVillage->castle->id,
                    'name' => $homeVillage->castle->name,
                    'travel_time' => self::TRAVEL_TIMES['town_to_castle'],
                ];
            }
        }

        // From wilderness
        if ($currentType === 'wilderness' || ! $currentType) {
            // Can go to home village
            $destinations[] = [
                'type' => 'village',
                'id' => $homeVillage->id,
                'name' => $homeVillage->name,
                'travel_time' => self::TRAVEL_TIMES['wilderness_to_village'],
            ];
        }

        return $destinations;
    }

    /**
     * Calculate travel time between locations.
     */
    protected function calculateTravelTime(User $user, string $destinationType): int
    {
        $fromType = $user->current_location_type ?? 'wilderness';
        $key = "{$fromType}_to_{$destinationType}";

        return self::TRAVEL_TIMES[$key] ?? 10;
    }

    /**
     * Get destination model.
     */
    protected function getDestination(string $type, int $id): ?Model
    {
        if ($type === 'wilderness') {
            return new class {
                public string $name = 'The Wilderness';
            };
        }

        return match ($type) {
            'village' => Village::find($id),
            'castle' => Castle::find($id),
            'town' => Town::find($id),
            'kingdom' => Kingdom::find($id),
            default => null,
        };
    }
}

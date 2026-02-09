<?php

namespace App\Services;

use App\Config\ConstructionConfig;
use App\Models\PlayerHouse;
use App\Models\User;

class HouseBuffService
{
    /**
     * Cache of house effects per user ID for the request lifecycle.
     *
     * @var array<int, array<string, int|float>>
     */
    protected array $cache = [];

    /**
     * Get all active house buffs for a player.
     * Returns aggregated effects from all furniture.
     *
     * @return array<string, int|float>
     */
    public function getHouseEffects(User $user): array
    {
        if (isset($this->cache[$user->id])) {
            return $this->cache[$user->id];
        }

        $house = PlayerHouse::where('player_id', $user->id)
            ->with('rooms.furniture')
            ->first();

        if (! $house) {
            $this->cache[$user->id] = [];

            return [];
        }

        $effects = [];

        foreach ($house->rooms as $room) {
            $roomConfig = ConstructionConfig::ROOMS[$room->room_type] ?? null;
            if (! $roomConfig) {
                continue;
            }

            foreach ($room->furniture as $furniture) {
                $hotspot = $roomConfig['hotspots'][$furniture->hotspot_slug] ?? null;
                if (! $hotspot) {
                    continue;
                }

                $furnitureConfig = $hotspot['options'][$furniture->furniture_key] ?? null;
                if (! $furnitureConfig || ! isset($furnitureConfig['effect'])) {
                    continue;
                }

                foreach ($furnitureConfig['effect'] as $key => $value) {
                    $effects[$key] = ($effects[$key] ?? 0) + $value;
                }
            }
        }

        // Merge adjacency bonuses
        foreach ($this->getAdjacencyBonuses($house) as $key => $value) {
            $effects[$key] = ($effects[$key] ?? 0) + $value;
        }

        $this->cache[$user->id] = $effects;

        return $effects;
    }

    /**
     * Get house buffs formatted for SkillBonusService integration.
     *
     * @return array<array{source: string, effect_key: string, value: int|float}>
     */
    public function getHouseBuffSources(User $user): array
    {
        $house = PlayerHouse::where('player_id', $user->id)
            ->with('rooms.furniture')
            ->first();

        if (! $house) {
            return [];
        }

        $sources = [];

        foreach ($house->rooms as $room) {
            $roomConfig = ConstructionConfig::ROOMS[$room->room_type] ?? null;
            if (! $roomConfig) {
                continue;
            }

            foreach ($room->furniture as $furniture) {
                $hotspot = $roomConfig['hotspots'][$furniture->hotspot_slug] ?? null;
                if (! $hotspot) {
                    continue;
                }

                $furnitureConfig = $hotspot['options'][$furniture->furniture_key] ?? null;
                if (! $furnitureConfig || ! isset($furnitureConfig['effect'])) {
                    continue;
                }

                $roomName = $roomConfig['name'];
                $furnitureName = $furnitureConfig['name'];

                foreach ($furnitureConfig['effect'] as $effectKey => $value) {
                    $sources[] = [
                        'source' => $roomName.' - '.$furnitureName,
                        'effect_key' => $effectKey,
                        'value' => $value,
                    ];
                }
            }
        }

        // Add adjacency bonus sources
        $adjacencyBonuses = $this->getActiveAdjacencyPairs($house);
        foreach ($adjacencyBonuses as $bonus) {
            $sources[] = [
                'source' => 'Adjacency: '.$bonus['description'],
                'effect_key' => $bonus['effect_key'],
                'value' => $bonus['value'],
            ];
        }

        return $sources;
    }

    /**
     * Get aggregated adjacency bonuses for a house.
     *
     * @return array<string, int|float>
     */
    public function getAdjacencyBonuses(PlayerHouse $house): array
    {
        $pairs = $this->getActiveAdjacencyPairs($house);
        $effects = [];

        foreach ($pairs as $pair) {
            $effects[$pair['effect_key']] = ($effects[$pair['effect_key']] ?? 0) + $pair['value'];
        }

        return $effects;
    }

    /**
     * Get active adjacency pairs (deduplicated).
     *
     * @return array<array{effect_key: string, value: int, description: string}>
     */
    protected function getActiveAdjacencyPairs(PlayerHouse $house): array
    {
        $rooms = $house->rooms;
        if ($rooms->isEmpty()) {
            return [];
        }

        // Build a grid map: "x,y" => room_type
        $grid = [];
        foreach ($rooms as $room) {
            $grid[$room->grid_x.','.$room->grid_y] = $room->room_type;
        }

        // Check each adjacency definition
        $activePairs = [];
        $foundPairs = [];

        foreach (ConstructionConfig::ADJACENCY_BONUSES as $bonus) {
            [$roomA, $roomB, $effectKey, $value, $description] = $bonus;
            $pairKey = $roomA < $roomB ? $roomA.':'.$roomB : $roomB.':'.$roomA;

            // Skip if already found this pair
            if (isset($foundPairs[$pairKey])) {
                continue;
            }

            // Check if any room of type A is adjacent to any room of type B
            foreach ($rooms as $room) {
                if ($room->room_type !== $roomA && $room->room_type !== $roomB) {
                    continue;
                }

                $targetType = $room->room_type === $roomA ? $roomB : $roomA;
                $adjacentPositions = [
                    ($room->grid_x - 1).','.$room->grid_y,
                    ($room->grid_x + 1).','.$room->grid_y,
                    $room->grid_x.','.($room->grid_y - 1),
                    $room->grid_x.','.($room->grid_y + 1),
                ];

                foreach ($adjacentPositions as $pos) {
                    if (isset($grid[$pos]) && $grid[$pos] === $targetType) {
                        $foundPairs[$pairKey] = true;
                        $activePairs[] = [
                            'effect_key' => $effectKey,
                            'value' => $value,
                            'description' => $description,
                        ];
                        break 2;
                    }
                }
            }
        }

        return $activePairs;
    }
}

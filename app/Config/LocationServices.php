<?php

namespace App\Config;

class LocationServices
{
    /**
     * Services available at each location type.
     * Keys are service identifiers, values are display names and route names.
     */
    public const SERVICES = [
        'training' => [
            'name' => 'Training Grounds',
            'description' => 'Train your combat skills',
            'icon' => 'swords',
            'route' => 'training',
        ],
        'gathering' => [
            'name' => 'Gathering',
            'description' => 'Gather resources from the land',
            'icon' => 'pickaxe',
            'route' => 'gathering',
        ],
        'crafting' => [
            'name' => 'Workshop',
            'description' => 'Craft items and equipment',
            'icon' => 'hammer',
            'route' => 'crafting',
        ],
        'forge' => [
            'name' => 'Forge',
            'description' => 'Smelt ores into metal bars',
            'icon' => 'flame',
            'route' => 'forge',
        ],
        'anvil' => [
            'name' => 'Anvil',
            'description' => 'Smith weapons and armor from bars',
            'icon' => 'anvil',
            'route' => 'anvil',
        ],
        'market' => [
            'name' => 'Market',
            'description' => 'Buy and sell goods',
            'icon' => 'store',
            'route' => 'market',
        ],
        'bank' => [
            'name' => 'Bank',
            'description' => 'Store your gold safely',
            'icon' => 'landmark',
            'route' => 'bank',
        ],
        'healer' => [
            'name' => 'Healer',
            'description' => 'Restore health and cure diseases',
            'icon' => 'heart-pulse',
            'route' => 'healer',
        ],
        'shrine' => [
            'name' => 'Shrine',
            'description' => 'Pray for blessings',
            'icon' => 'sparkles',
            'route' => 'shrine',
        ],
        'jobs' => [
            'name' => 'Job Board',
            'description' => 'Find work opportunities',
            'icon' => 'briefcase',
            'route' => 'jobs',
        ],
        'port' => [
            'name' => 'Harbor',
            'description' => 'Board ships to distant lands',
            'icon' => 'ship',
            'route' => 'port',
            'requires_port' => true,
        ],
        'tavern' => [
            'name' => 'Tavern',
            'description' => 'Rest and hear rumors',
            'icon' => 'beer',
            'route' => 'tavern',
        ],
        'stables' => [
            'name' => 'Stables',
            'description' => 'Buy and manage horses',
            'icon' => 'warehouse',
            'route' => 'stables',
        ],
        'arena' => [
            'name' => 'Arena',
            'description' => 'Fight in gladiatorial combat',
            'icon' => 'shield',
            'route' => 'arena',
        ],
        'hall' => [
            'name' => 'Town Hall',
            'description' => 'Town governance and administration',
            'icon' => 'building-columns',
            'route' => 'hall',
        ],
        'infirmary' => [
            'name' => 'Infirmary',
            'description' => 'Restore health and cure diseases',
            'icon' => 'hospital',
            'route' => 'infirmary',
        ],
        'chat' => [
            'name' => 'Town Crier',
            'description' => 'Local news and discussions',
            'icon' => 'message-square',
            'route' => 'chat',
        ],
        'taxes' => [
            'name' => 'Tax Office',
            'description' => 'Manage local taxes',
            'icon' => 'receipt',
            'route' => 'taxes',
        ],
        'businesses' => [
            'name' => 'Businesses',
            'description' => 'Local shops and enterprises',
            'icon' => 'store',
            'route' => 'businesses',
        ],
        'thieving' => [
            'name' => 'Thieving',
            'description' => 'Pickpocket targets for gold and items',
            'icon' => 'hand',
            'route' => 'thieving',
        ],
        'apothecary' => [
            'name' => 'Apothecary',
            'description' => 'Brew potions and remedies',
            'icon' => 'flask-conical',
            'route' => 'apothecary',
        ],
        'farming' => [
            'name' => 'Farming',
            'description' => 'Grow crops and harvest your yield',
            'icon' => 'wheat',
            'route' => 'farming',
        ],
    ];

    /**
     * Services available at each location type.
     */
    public const LOCATION_SERVICES = [
        'village' => [
            'training',
            'gathering',
            'crafting',
            'forge',
            'anvil',
            'market',
            'bank',
            'healer',
            'shrine',
            'jobs',
            'port',      // Only if is_port = true
            'stables',
            'tavern',
            'thieving',
            'apothecary',
            'farming',
        ],
        'town' => [
            'training',
            'gathering',
            'crafting',
            'forge',
            'anvil',
            'market',
            'bank',
            'infirmary',
            'shrine',
            'jobs',
            'port',      // Only if is_port = true
            'hall',
            'stables',
            'tavern',
            'thieving',
            'apothecary',
            'farming',
        ],
        'barony' => [
            'training',
            'crafting',
            'forge',
            'anvil',
            'market',
            'bank',
            'infirmary',
            'shrine',
            'jobs',
            'stables',
            'tavern',
            'thieving',
            'businesses',
            'chat',
            'taxes',
            'apothecary',
        ],
        'duchy' => [
            'training',
            'crafting',
            'forge',
            'anvil',
            'shrine',
            'jobs',
            'stables',
            'tavern',
            'thieving',
            'apothecary',
        ],
        'kingdom' => [
            'training',
            'crafting',
            'forge',
            'anvil',
            'shrine',
            'jobs',
            'stables',
            'tavern',
            'thieving',
            'apothecary',
        ],
    ];

    /**
     * Get services available at a specific location.
     *
     * @param  bool  $isPort  Whether the location is a port
     */
    public static function getServicesForLocation(string $locationType, bool $isPort = false): array
    {
        $serviceIds = self::LOCATION_SERVICES[$locationType] ?? [];
        $services = [];

        foreach ($serviceIds as $serviceId) {
            $service = self::SERVICES[$serviceId] ?? null;
            if (! $service) {
                continue;
            }

            // Skip port if location is not a port
            if (($service['requires_port'] ?? false) && ! $isPort) {
                continue;
            }

            $services[$serviceId] = $service;
        }

        return $services;
    }

    /**
     * Check if a service is available at a location type.
     */
    public static function isServiceAvailable(string $locationType, string $serviceId, bool $isPort = false): bool
    {
        $services = self::getServicesForLocation($locationType, $isPort);

        return isset($services[$serviceId]);
    }

    /**
     * Get the route name for a service at a location.
     */
    public static function getServiceRoute(string $locationType, string $serviceId): string
    {
        $service = self::SERVICES[$serviceId] ?? null;
        if (! $service) {
            return '';
        }

        // Build route name like "villages.training", "towns.market"
        $locationPlural = match ($locationType) {
            'village' => 'villages',
            'town' => 'towns',
            'barony' => 'baronies',
            'duchy' => 'duchies',
            'kingdom' => 'kingdoms',
            default => $locationType.'s',
        };

        return "{$locationPlural}.{$service['route']}";
    }

    /**
     * Get the URL for a service at a specific location.
     */
    public static function getServiceUrl(string $locationType, int $locationId, string $serviceId): string
    {
        $routeName = self::getServiceRoute($locationType, $serviceId);
        if (! $routeName) {
            return '';
        }

        return route($routeName, [$locationType => $locationId]);
    }
}

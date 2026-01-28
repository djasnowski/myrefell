<?php

namespace App\Http\Middleware;

use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Kingdom;
use App\Models\Town;
use App\Models\Village;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlayerAtLocation
{
    /**
     * Handle an incoming request.
     *
     * Validates that the player is physically at the specified location before allowing access.
     * The location type is determined from the route parameter name (village, town, barony, etc.).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if player is traveling
        if ($user->isTraveling()) {
            return back()->with('error', 'You cannot access services while traveling.');
        }

        // Determine the location type from route parameters
        $locationType = $this->getLocationTypeFromRoute($request);
        $location = $this->getLocationFromRoute($request, $locationType);

        if (!$location) {
            return back()->with('error', 'Location not found.');
        }

        // Check if player is at this location
        if ($user->current_location_type !== $locationType ||
            $user->current_location_id !== $location->id) {
            return back()->with('error', 'You must be at this location to access its services.');
        }

        return $next($request);
    }

    /**
     * Get the location type from route parameters.
     */
    protected function getLocationTypeFromRoute(Request $request): ?string
    {
        $routeParameters = array_keys($request->route()->parameters());

        foreach ($routeParameters as $param) {
            if (in_array($param, ['village', 'town', 'barony', 'duchy', 'kingdom'])) {
                return $param;
            }
        }

        return null;
    }

    /**
     * Get the location model from route parameters.
     */
    protected function getLocationFromRoute(Request $request, ?string $locationType): ?Model
    {
        if (!$locationType) {
            return null;
        }

        $locationValue = $request->route($locationType);

        // If it's already a model, return it
        if ($locationValue instanceof Model) {
            return $locationValue;
        }

        // Otherwise, resolve the model from the ID
        $locationId = is_numeric($locationValue) ? (int) $locationValue : null;
        if (!$locationId) {
            return null;
        }

        return match ($locationType) {
            'village' => Village::find($locationId),
            'town' => Town::find($locationId),
            'barony' => Barony::find($locationId),
            'duchy' => Duchy::find($locationId),
            'kingdom' => Kingdom::find($locationId),
            default => null,
        };
    }
}

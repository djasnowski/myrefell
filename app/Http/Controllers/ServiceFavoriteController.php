<?php

namespace App\Http\Controllers;

use App\Config\LocationServices;
use App\Models\UserServiceFavorite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceFavoriteController extends Controller
{
    /**
     * Toggle a service favorite on/off.
     */
    public function toggle(Request $request): RedirectResponse
    {
        $request->validate([
            'service_id' => ['required', 'string', 'max:50'],
        ]);

        $user = $request->user();
        $serviceId = $request->input('service_id');

        // Verify the service exists
        if (! isset(LocationServices::SERVICES[$serviceId])) {
            return back()->with('error', 'Invalid service.');
        }

        // Check if already favorited
        $existing = UserServiceFavorite::where('user_id', $user->id)
            ->where('service_id', $serviceId)
            ->first();

        if ($existing) {
            // Remove favorite
            $existing->delete();

            return back()->with('success', 'Removed from favorites.');
        }

        // Add favorite
        $maxOrder = UserServiceFavorite::where('user_id', $user->id)->max('sort_order') ?? 0;

        UserServiceFavorite::create([
            'user_id' => $user->id,
            'service_id' => $serviceId,
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('success', 'Added to favorites!');
    }
}

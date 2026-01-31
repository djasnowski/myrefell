<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Set energy for admin user (dan only).
     */
    public function setEnergy(Request $request): JsonResponse
    {
        $user = $request->user();

        // Only allow dan to use this
        if ($user->username !== 'dan') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'energy' => 'required|integer|min:0',
        ]);

        $user->energy = min($validated['energy'], $user->max_energy);
        $user->save();

        return response()->json([
            'success' => true,
            'energy' => $user->energy,
            'max_energy' => $user->max_energy,
        ]);
    }
}

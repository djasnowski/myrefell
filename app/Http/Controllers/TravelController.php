<?php

namespace App\Http\Controllers;

use App\Services\TravelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TravelController extends Controller
{
    public function __construct(
        protected TravelService $travelService
    ) {}

    /**
     * Show travel page with current status and destinations.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Check if arrived
        $arrival = $this->travelService->checkArrival($user);

        return Inertia::render('Travel/Index', [
            'travel_status' => $this->travelService->getTravelStatus($user),
            'destinations' => $this->travelService->getAvailableDestinations($user),
            'energy_cost' => TravelService::ENERGY_COST,
            'just_arrived' => $arrival,
            'is_dev' => app()->environment('local'),
        ]);
    }

    /**
     * Get travel status (for polling).
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if arrived
        $arrival = $this->travelService->checkArrival($user);

        if ($arrival) {
            return response()->json([
                'arrived' => true,
                'location' => $arrival['location'],
            ]);
        }

        $status = $this->travelService->getTravelStatus($user);

        return response()->json([
            'arrived' => false,
            'status' => $status,
        ]);
    }

    /**
     * Start traveling to a destination.
     */
    public function start(Request $request)
    {
        $request->validate([
            'destination_type' => 'required|string|in:village,barony,town,wilderness',
            'destination_id' => 'required|integer',
        ]);

        $user = $request->user();

        try {
            $result = $this->travelService->startTravel(
                $user,
                $request->input('destination_type'),
                $request->input('destination_id')
            );

            // If Inertia request, redirect to travel page to show progress
            if ($request->header('X-Inertia')) {
                return redirect()->route('travel.index');
            }

            return response()->json([
                'success' => true,
                'travel' => $result,
            ]);
        } catch (\InvalidArgumentException $e) {
            if ($request->header('X-Inertia')) {
                return back()->withErrors(['travel' => $e->getMessage()]);
            }

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel current travel.
     */
    public function cancel(Request $request)
    {
        $user = $request->user();

        if ($this->travelService->cancelTravel($user)) {
            if ($request->header('X-Inertia')) {
                return back();
            }

            return response()->json([
                'success' => true,
                'message' => 'Travel cancelled.',
            ]);
        }

        if ($request->header('X-Inertia')) {
            return back()->withErrors(['travel' => 'You are not traveling.']);
        }

        return response()->json([
            'success' => false,
            'error' => 'You are not traveling.',
        ], 422);
    }

    /**
     * Skip travel (dev only) - instantly arrive at destination.
     */
    public function skip(Request $request)
    {
        if (! app()->environment('local')) {
            abort(403, 'This action is only available in development.');
        }

        $user = $request->user();

        if (! $user->is_traveling) {
            if ($request->header('X-Inertia')) {
                return back()->withErrors(['travel' => 'You are not traveling.']);
            }

            return response()->json(['success' => false, 'error' => 'You are not traveling.'], 422);
        }

        // Force arrival by setting arrives_at to now
        $user->update(['traveling_arrives_at' => now()->subSecond()]);

        // Check arrival to complete the travel
        $this->travelService->checkArrival($user);

        if ($request->header('X-Inertia')) {
            return redirect()->route('travel.index');
        }

        return response()->json(['success' => true, 'message' => 'Travel skipped.']);
    }

    /**
     * Arrive at destination (manual check).
     */
    public function arrive(Request $request)
    {
        $user = $request->user();

        $arrival = $this->travelService->checkArrival($user);

        if ($arrival) {
            if ($request->header('X-Inertia')) {
                return redirect()->route('travel.index');
            }

            return response()->json([
                'success' => true,
                'arrived' => true,
                'location' => $arrival['location'],
            ]);
        }

        $status = $this->travelService->getTravelStatus($user);

        if (! $status) {
            if ($request->header('X-Inertia')) {
                return back()->withErrors(['travel' => 'You are not traveling.']);
            }

            return response()->json([
                'success' => false,
                'error' => 'You are not traveling.',
            ], 422);
        }

        if ($request->header('X-Inertia')) {
            return back();
        }

        return response()->json([
            'success' => false,
            'arrived' => false,
            'remaining_seconds' => $status['remaining_seconds'],
        ]);
    }
}

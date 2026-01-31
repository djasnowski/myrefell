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
            'destination_type' => 'required|string|in:village,barony,town,kingdom,wilderness',
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
        \Log::info('Skip called', ['env' => app()->environment()]);

        if (! app()->environment('local')) {
            \Log::warning('Skip rejected - not local environment');
            abort(403, 'This action is only available in development.');
        }

        $user = $request->user();
        \Log::info('Skip user state', [
            'user_id' => $user->id,
            'is_traveling' => $user->is_traveling,
            'travel_arrives_at' => $user->travel_arrives_at,
        ]);

        if (! $user->is_traveling) {
            \Log::warning('Skip rejected - user not traveling');
            if ($request->header('X-Inertia')) {
                return back()->withErrors(['travel' => 'You are not traveling.']);
            }

            return response()->json(['success' => false, 'error' => 'You are not traveling.'], 422);
        }

        // Force arrival by setting arrives_at to now
        $user->update(['travel_arrives_at' => now()->subSecond()]);
        $user->refresh();
        \Log::info('Skip updated travel_arrives_at', ['new_value' => $user->travel_arrives_at]);

        // Check arrival to complete the travel
        $result = $this->travelService->checkArrival($user);
        \Log::info('Skip checkArrival result', ['result' => $result]);

        if ($request->header('X-Inertia')) {
            \Log::info('Skip returning Inertia redirect');

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

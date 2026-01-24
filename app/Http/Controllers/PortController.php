<?php

namespace App\Http\Controllers;

use App\Models\Village;
use App\Services\PortService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortController extends Controller
{
    public function __construct(
        protected PortService $portService
    ) {}

    /**
     * Show the port page for a village.
     */
    public function show(Request $request, Village $village): Response
    {
        $user = $request->user();

        // Check if village is a port
        if (! $village->is_port) {
            return Inertia::render('Port/NotHere', [
                'message' => 'This village does not have a harbor.',
            ]);
        }

        // Check if player is at this village
        if ($user->current_location_type !== 'village' || $user->current_location_id !== $village->id) {
            return Inertia::render('Port/NotHere', [
                'message' => 'You must be at this port to access the harbor.',
            ]);
        }

        if (! $this->portService->canAccessPort($user)) {
            return Inertia::render('Port/NotHere', [
                'message' => 'You cannot access the harbor while traveling.',
            ]);
        }

        $portInfo = $this->portService->getPortInfo($user);

        return Inertia::render('Port/Index', [
            'port_info' => $portInfo,
        ]);
    }

    /**
     * Book passage to a destination port.
     */
    public function book(Request $request): JsonResponse
    {
        $request->validate([
            'destination_id' => 'required|integer|exists:villages,id',
        ]);

        $user = $request->user();
        $result = $this->portService->bookPassage($user, $request->input('destination_id'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}

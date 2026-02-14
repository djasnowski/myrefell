<?php

namespace App\Http\Controllers;

use App\Models\PlayerRole;
use App\Models\RolePetition;
use App\Services\RolePetitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RolePetitionController extends Controller
{
    public function __construct(
        protected RolePetitionService $petitionService
    ) {}

    /**
     * Submit a petition against a role holder.
     */
    public function create(Request $request, PlayerRole $playerRole): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'request_appointment' => ['sometimes', 'boolean'],
        ]);

        $result = $this->petitionService->createPetition(
            $request->user(),
            $playerRole,
            $request->reason,
            $request->boolean('request_appointment', false)
        );

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * View pending petitions for the authority figure.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $petitions = RolePetition::where('authority_user_id', $user->id)
            ->pending()
            ->notExpired()
            ->with([
                'petitioner:id,username',
                'targetPlayerRole.role',
                'targetPlayerRole.user:id,username',
            ])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (RolePetition $petition) => [
                'id' => $petition->id,
                'petitioner' => [
                    'id' => $petition->petitioner->id,
                    'username' => $petition->petitioner->username,
                ],
                'target_role' => $petition->targetPlayerRole->role->name ?? 'Unknown',
                'target_holder' => [
                    'id' => $petition->targetPlayerRole->user->id ?? 0,
                    'username' => $petition->targetPlayerRole->user->username ?? 'Unknown',
                ],
                'location_type' => $petition->location_type,
                'location_id' => $petition->location_id,
                'petition_reason' => $petition->petition_reason,
                'request_appointment' => $petition->request_appointment,
                'created_at' => $petition->created_at->toISOString(),
                'expires_at' => $petition->expires_at?->toISOString(),
            ]);

        // Also get petitions the user has filed
        $myPetitions = RolePetition::where('petitioner_id', $user->id)
            ->pending()
            ->notExpired()
            ->with([
                'authority:id,username',
                'targetPlayerRole.role',
                'targetPlayerRole.user:id,username',
            ])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (RolePetition $petition) => [
                'id' => $petition->id,
                'authority' => [
                    'id' => $petition->authority->id,
                    'username' => $petition->authority->username,
                ],
                'target_role' => $petition->targetPlayerRole->role->name ?? 'Unknown',
                'target_holder' => [
                    'id' => $petition->targetPlayerRole->user->id ?? 0,
                    'username' => $petition->targetPlayerRole->user->username ?? 'Unknown',
                ],
                'petition_reason' => $petition->petition_reason,
                'request_appointment' => $petition->request_appointment,
                'status' => $petition->status,
                'created_at' => $petition->created_at->toISOString(),
                'expires_at' => $petition->expires_at?->toISOString(),
            ]);

        return Inertia::render('Roles/Petitions', [
            'pending_petitions' => $petitions,
            'my_petitions' => $myPetitions,
        ]);
    }

    /**
     * Approve a petition.
     */
    public function approve(Request $request, RolePetition $rolePetition): RedirectResponse
    {
        $request->validate([
            'response_message' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->petitionService->approvePetition(
            $request->user(),
            $rolePetition,
            $request->response_message
        );

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Deny a petition.
     */
    public function deny(Request $request, RolePetition $rolePetition): RedirectResponse
    {
        $request->validate([
            'response_message' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->petitionService->denyPetition(
            $request->user(),
            $rolePetition,
            $request->response_message
        );

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Withdraw a petition.
     */
    public function withdraw(Request $request, RolePetition $rolePetition): RedirectResponse
    {
        $result = $this->petitionService->withdrawPetition(
            $request->user(),
            $rolePetition
        );

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }
}

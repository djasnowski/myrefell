<?php

namespace App\Http\Controllers;

use App\Models\MigrationRequest;
use App\Models\Village;
use App\Services\MigrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MigrationController extends Controller
{
    public function __construct(
        protected MigrationService $migrationService
    ) {}

    /**
     * Show migration page - request to move to a new village.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $currentVillage = $user->homeVillage;

        // Get user's pending request if any
        $pendingRequest = MigrationRequest::where('user_id', $user->id)
            ->pending()
            ->with(['toVillage.castle.kingdom'])
            ->first();

        // Get requests user can approve (if they hold authority)
        $requestsToApprove = $this->migrationService->getPendingRequestsForApprover($user);

        // Get user's request history
        $requestHistory = $this->migrationService->getUserRequests($user)->take(10);

        return Inertia::render('Migration/Index', [
            'current_village' => $currentVillage ? [
                'id' => $currentVillage->id,
                'name' => $currentVillage->name,
                'castle' => $currentVillage->castle?->name,
                'kingdom' => $currentVillage->castle?->kingdom?->name,
            ] : null,
            'pending_request' => $pendingRequest ? $this->formatRequest($pendingRequest) : null,
            'requests_to_approve' => $requestsToApprove->map(fn ($r) => $this->formatRequest($r)),
            'request_history' => $requestHistory->map(fn ($r) => $this->formatRequest($r)),
            'can_migrate' => $this->migrationService->canMigrate($user),
            'cooldown_ends' => $user->last_migration_at
                ? $user->last_migration_at->addDays(MigrationRequest::MIGRATION_COOLDOWN_DAYS)->toISOString()
                : null,
        ]);
    }

    /**
     * Request migration to a village.
     */
    public function request(Request $request, Village $village): RedirectResponse
    {
        $user = $request->user();
        $result = $this->migrationService->requestMigration($user, $village);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Cancel a migration request.
     */
    public function cancel(Request $request, MigrationRequest $migrationRequest): RedirectResponse
    {
        $user = $request->user();
        $result = $this->migrationService->cancel($migrationRequest, $user);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Approve a migration request.
     */
    public function approve(Request $request, MigrationRequest $migrationRequest): RedirectResponse
    {
        $request->validate([
            'level' => 'required|in:elder,lord,king',
        ]);

        $user = $request->user();
        $result = $this->migrationService->approve($migrationRequest, $user, $request->level);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Deny a migration request.
     */
    public function deny(Request $request, MigrationRequest $migrationRequest): RedirectResponse
    {
        $request->validate([
            'level' => 'required|in:elder,lord,king',
            'reason' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $result = $this->migrationService->deny($migrationRequest, $user, $request->level, $request->reason);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Format a migration request for the frontend.
     */
    protected function formatRequest(MigrationRequest $request): array
    {
        return [
            'id' => $request->id,
            'user' => [
                'id' => $request->user->id,
                'username' => $request->user->username,
            ],
            'from_village' => [
                'id' => $request->fromVillage->id,
                'name' => $request->fromVillage->name,
            ],
            'to_village' => [
                'id' => $request->toVillage->id,
                'name' => $request->toVillage->name,
                'castle' => $request->toVillage->castle?->name,
                'kingdom' => $request->toVillage->castle?->kingdom?->name,
            ],
            'status' => $request->status,
            'elder_approved' => $request->elder_approved,
            'lord_approved' => $request->lord_approved,
            'king_approved' => $request->king_approved,
            'needs_elder' => $request->needsElderApproval(),
            'needs_lord' => $request->needsLordApproval(),
            'needs_king' => $request->needsKingApproval(),
            'denial_reason' => $request->denial_reason,
            'created_at' => $request->created_at->toISOString(),
            'completed_at' => $request->completed_at?->toISOString(),
        ];
    }
}

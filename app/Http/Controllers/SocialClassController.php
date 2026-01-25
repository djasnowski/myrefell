<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\EnnoblementRequest;
use App\Models\Kingdom;
use App\Models\ManumissionRequest;
use App\Models\User;
use App\Services\SocialClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SocialClassController extends Controller
{
    public function __construct(
        protected SocialClassService $socialClassService
    ) {}

    /**
     * Display the player's social class status.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $user->load(['boundBarony', 'manumissionRequests', 'ennoblementRequests', 'socialClassHistory']);

        return Inertia::render('SocialClass/Index', [
            'player' => [
                'id' => $user->id,
                'username' => $user->username,
                'social_class' => $user->social_class,
                'social_class_display' => $user->social_class_display,
                'bound_to_barony' => $user->boundBarony ? [
                    'id' => $user->boundBarony->id,
                    'name' => $user->boundBarony->name,
                ] : null,
                'labor_days_owed' => $user->labor_days_owed,
                'labor_days_completed' => $user->labor_days_completed,
                'remaining_labor_days' => $user->getRemainingLaborDays(),
                'gold' => $user->gold,
            ],
            'rights' => [
                'can_vote' => $user->canVote(),
                'can_join_guild' => $user->canJoinGuild(),
                'can_own_business' => $user->canOwnBusiness(),
                'can_own_property' => $user->canOwnProperty(),
                'can_hold_high_office' => $user->canHoldHighOffice(),
                'can_freely_travel' => $user->canFreelyTravel(),
            ],
            'manumission_requests' => $user->manumissionRequests()
                ->with(['baron', 'barony'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(fn($r) => [
                    'id' => $r->id,
                    'type' => $r->request_type,
                    'type_display' => $r->type_display,
                    'status' => $r->status,
                    'gold_offered' => $r->gold_offered,
                    'reason' => $r->reason,
                    'response_message' => $r->response_message,
                    'created_at' => $r->created_at->diffForHumans(),
                    'responded_at' => $r->responded_at?->diffForHumans(),
                ]),
            'ennoblement_requests' => $user->ennoblementRequests()
                ->with(['king', 'kingdom'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(fn($r) => [
                    'id' => $r->id,
                    'type' => $r->request_type,
                    'type_display' => $r->type_display,
                    'status' => $r->status,
                    'gold_offered' => $r->gold_offered,
                    'reason' => $r->reason,
                    'response_message' => $r->response_message,
                    'title_granted' => $r->title_granted,
                    'created_at' => $r->created_at->diffForHumans(),
                    'responded_at' => $r->responded_at?->diffForHumans(),
                ]),
            'class_history' => $user->socialClassHistory()
                ->with('grantedBy')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(fn($h) => [
                    'old_class' => $h->old_class_display,
                    'new_class' => $h->new_class_display,
                    'reason' => $h->reason,
                    'granted_by' => $h->grantedBy?->username,
                    'created_at' => $h->created_at->diffForHumans(),
                ]),
            'manumission_cost' => ManumissionRequest::PURCHASE_COST,
            'ennoblement_cost' => EnnoblementRequest::PURCHASE_COST,
        ]);
    }

    /**
     * Request manumission (freedom).
     */
    public function requestManumission(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'request_type' => 'required|in:decree,purchase,military_service,exceptional_service',
            'reason' => 'nullable|string|max:1000',
            'gold_offered' => 'nullable|integer|min:0',
        ]);

        $result = $this->socialClassService->requestManumission(
            $user,
            $validated['request_type'],
            $validated['reason'] ?? null,
            $validated['gold_offered'] ?? 0
        );

        if (is_string($result)) {
            return back()->with('error', $result);
        }

        return back()->with('success', 'Manumission request submitted successfully.');
    }

    /**
     * Cancel a manumission request.
     */
    public function cancelManumission(Request $request, ManumissionRequest $manumissionRequest): RedirectResponse
    {
        $user = $request->user();

        if ($manumissionRequest->serf_id !== $user->id) {
            return back()->with('error', 'This is not your request.');
        }

        if (!$manumissionRequest->isPending()) {
            return back()->with('error', 'This request is no longer pending.');
        }

        $manumissionRequest->update(['status' => ManumissionRequest::STATUS_CANCELLED]);

        return back()->with('success', 'Request cancelled.');
    }

    /**
     * Request ennoblement (nobility).
     */
    public function requestEnnoblement(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'kingdom_id' => 'required|exists:kingdoms,id',
            'request_type' => 'required|in:royal_decree,military_service,marriage,purchase',
            'reason' => 'nullable|string|max:1000',
            'gold_offered' => 'nullable|integer|min:0',
            'spouse_id' => 'nullable|exists:users,id',
        ]);

        $kingdom = Kingdom::findOrFail($validated['kingdom_id']);
        $spouse = isset($validated['spouse_id']) ? User::find($validated['spouse_id']) : null;

        $result = $this->socialClassService->requestEnnoblement(
            $user,
            $kingdom,
            $validated['request_type'],
            $validated['reason'] ?? null,
            $validated['gold_offered'] ?? 0,
            $spouse
        );

        if (is_string($result)) {
            return back()->with('error', $result);
        }

        return back()->with('success', 'Ennoblement request submitted successfully.');
    }

    /**
     * Cancel an ennoblement request.
     */
    public function cancelEnnoblement(Request $request, EnnoblementRequest $ennoblementRequest): RedirectResponse
    {
        $user = $request->user();

        if ($ennoblementRequest->requester_id !== $user->id) {
            return back()->with('error', 'This is not your request.');
        }

        if (!$ennoblementRequest->isPending()) {
            return back()->with('error', 'This request is no longer pending.');
        }

        $ennoblementRequest->update(['status' => EnnoblementRequest::STATUS_CANCELLED]);

        return back()->with('success', 'Request cancelled.');
    }

    /**
     * Become a burgher.
     */
    public function becomeBurgher(Request $request): RedirectResponse
    {
        $user = $request->user();

        $result = $this->socialClassService->becomeBurgher($user);

        if (is_string($result)) {
            return back()->with('error', $result);
        }

        return back()->with('success', 'You are now a Burgher!');
    }

    // ==================== BARON/KING ADMIN ACTIONS ====================

    /**
     * View pending manumission requests (for barons).
     */
    public function manumissionRequests(Request $request): Response
    {
        $user = $request->user();

        $requests = ManumissionRequest::where('baron_id', $user->id)
            ->pending()
            ->with(['serf', 'barony'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('SocialClass/ManumissionRequests', [
            'requests' => $requests,
        ]);
    }

    /**
     * Approve a manumission request.
     */
    public function approveManumission(Request $request, ManumissionRequest $manumissionRequest): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'response_message' => 'nullable|string|max:500',
        ]);

        $result = $this->socialClassService->approveManumission(
            $manumissionRequest,
            $user,
            $validated['response_message'] ?? null
        );

        if (!$result) {
            return back()->with('error', 'Failed to approve request.');
        }

        return back()->with('success', 'Manumission approved. The serf is now free.');
    }

    /**
     * Deny a manumission request.
     */
    public function denyManumission(Request $request, ManumissionRequest $manumissionRequest): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'response_message' => 'nullable|string|max:500',
        ]);

        $result = $this->socialClassService->denyManumission(
            $manumissionRequest,
            $user,
            $validated['response_message'] ?? null
        );

        if (!$result) {
            return back()->with('error', 'Failed to deny request.');
        }

        return back()->with('success', 'Manumission denied.');
    }

    /**
     * View pending ennoblement requests (for kings).
     */
    public function ennoblementRequests(Request $request): Response
    {
        $user = $request->user();

        $requests = EnnoblementRequest::where('king_id', $user->id)
            ->pending()
            ->with(['requester', 'kingdom', 'spouse'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('SocialClass/EnnoblementRequests', [
            'requests' => $requests,
        ]);
    }

    /**
     * Approve an ennoblement request.
     */
    public function approveEnnoblement(Request $request, EnnoblementRequest $ennoblementRequest): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'title_granted' => 'required|string|max:50',
            'response_message' => 'nullable|string|max:500',
        ]);

        $result = $this->socialClassService->approveEnnoblement(
            $ennoblementRequest,
            $user,
            $validated['title_granted'],
            $validated['response_message'] ?? null
        );

        if (!$result) {
            return back()->with('error', 'Failed to approve request.');
        }

        return back()->with('success', 'Ennoblement approved. The requester is now a noble.');
    }

    /**
     * Deny an ennoblement request.
     */
    public function denyEnnoblement(Request $request, EnnoblementRequest $ennoblementRequest): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'response_message' => 'nullable|string|max:500',
        ]);

        $result = $this->socialClassService->denyEnnoblement(
            $ennoblementRequest,
            $user,
            $validated['response_message'] ?? null
        );

        if (!$result) {
            return back()->with('error', 'Failed to deny request.');
        }

        return back()->with('success', 'Ennoblement denied.');
    }
}

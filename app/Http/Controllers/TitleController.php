<?php

namespace App\Http\Controllers;

use App\Models\TitlePetition;
use App\Models\TitleType;
use App\Models\User;
use App\Services\TitleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TitleController extends Controller
{
    public function __construct(
        protected TitleService $titleService
    ) {}

    /**
     * Display the titles page (my titles, petitions, available titles).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Titles/Index', [
            'my_titles' => $this->titleService->getUserTitles($user),
            'my_petitions' => $this->titleService->getUserPetitions($user),
            'available_titles' => $this->titleService->getTitlesAvailableForPetition($user),
            'pending_to_review' => $this->titleService->getPendingPetitionsToReview($user),
            'awaiting_ceremony' => $this->titleService->getPetitionsAwaitingCeremony($user),
            'styled_name' => $this->titleService->getStyledName($user),
        ]);
    }

    /**
     * Show the petition form for a specific title.
     */
    public function showPetitionForm(Request $request, TitleType $titleType): Response
    {
        $user = $request->user();

        $requirementCheck = $titleType->userMeetsRequirements($user);
        $potentialGrantors = $this->titleService->getPotentialGrantors($titleType);

        return Inertia::render('Titles/Petition', [
            'title_type' => [
                'id' => $titleType->id,
                'name' => $titleType->name,
                'slug' => $titleType->slug,
                'tier' => $titleType->tier,
                'category' => $titleType->category,
                'description' => $titleType->description,
                'style_of_address' => $titleType->style_of_address,
                'requirements' => $titleType->requirements,
                'can_purchase' => $titleType->can_purchase,
                'purchase_cost' => $titleType->purchase_cost,
                'requires_ceremony' => $titleType->requires_ceremony,
            ],
            'meets_requirements' => $requirementCheck['meets_all'],
            'met_requirements' => $requirementCheck['met'],
            'unmet_requirements' => $requirementCheck['unmet'],
            'potential_grantors' => $potentialGrantors,
            'user_gold' => $user->gold,
        ]);
    }

    /**
     * Submit a petition for a title.
     */
    public function submitPetition(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title_type_id' => 'required|exists:title_types,id',
            'petition_to_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:1000',
            'is_purchase' => 'boolean',
            'domain_type' => 'nullable|string',
            'domain_id' => 'nullable|integer',
        ]);

        $user = $request->user();
        $titleType = TitleType::findOrFail($validated['title_type_id']);
        $petitionTo = User::findOrFail($validated['petition_to_id']);

        $result = $this->titleService->submitPetition(
            $user,
            $titleType,
            $petitionTo,
            $validated['message'] ?? null,
            $validated['is_purchase'] ?? false,
            $validated['domain_type'] ?? null,
            $validated['domain_id'] ?? null
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->withErrors(['petition' => $result['message']]);
    }

    /**
     * Withdraw a petition.
     */
    public function withdrawPetition(Request $request, TitlePetition $petition): RedirectResponse
    {
        $user = $request->user();

        $result = $this->titleService->withdrawPetition($petition, $user);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->withErrors(['petition' => $result['message']]);
    }

    /**
     * Show petition review page (for grantors).
     */
    public function reviewPetition(Request $request, TitlePetition $petition): Response
    {
        $user = $request->user();

        if ($petition->petition_to_id !== $user->id) {
            abort(403, 'You are not authorized to review this petition.');
        }

        $petition->load(['petitioner', 'titleType', 'domain']);

        return Inertia::render('Titles/Review', [
            'petition' => [
                'id' => $petition->id,
                'petitioner' => [
                    'id' => $petition->petitioner->id,
                    'username' => $petition->petitioner->username,
                    'current_title' => $petition->petitioner->primary_title,
                    'gold' => $petition->petitioner->gold,
                ],
                'title_type' => [
                    'id' => $petition->titleType->id,
                    'name' => $petition->titleType->name,
                    'description' => $petition->titleType->description,
                    'style_of_address' => $petition->titleType->style_of_address,
                    'requires_ceremony' => $petition->titleType->requires_ceremony,
                ],
                'status' => $petition->status,
                'petition_message' => $petition->petition_message,
                'is_purchase' => $petition->is_purchase,
                'gold_offered' => $petition->gold_offered,
                'domain_type' => $petition->domain_type,
                'domain_name' => $petition->domain?->name,
                'created_at' => $petition->created_at->format('Y-m-d H:i'),
                'expires_at' => $petition->expires_at?->format('Y-m-d H:i'),
            ],
        ]);
    }

    /**
     * Approve a petition.
     */
    public function approvePetition(Request $request, TitlePetition $petition): RedirectResponse
    {
        $validated = $request->validate([
            'response_message' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        $result = $this->titleService->approvePetition(
            $petition,
            $user,
            $validated['response_message'] ?? null
        );

        if ($result['success']) {
            return redirect()->route('titles.index')->with('success', $result['message']);
        }

        return back()->withErrors(['petition' => $result['message']]);
    }

    /**
     * Deny a petition.
     */
    public function denyPetition(Request $request, TitlePetition $petition): RedirectResponse
    {
        $validated = $request->validate([
            'response_message' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        $result = $this->titleService->denyPetition(
            $petition,
            $user,
            $validated['response_message'] ?? null
        );

        if ($result['success']) {
            return redirect()->route('titles.index')->with('success', $result['message']);
        }

        return back()->withErrors(['petition' => $result['message']]);
    }

    /**
     * Show ceremony page.
     */
    public function showCeremony(Request $request, TitlePetition $petition): Response
    {
        $user = $request->user();

        if (! $petition->isAwaitingCeremony()) {
            abort(404, 'This petition is not awaiting a ceremony.');
        }

        $petition->load(['petitioner', 'titleType', 'petitionTo']);

        // Check if user can officiate
        $canOfficiate = $petition->petition_to_id === $user->id;
        if (! $canOfficiate) {
            $userTitle = $user->highestTitle();
            $approverTitle = $petition->petitionTo->highestTitle();
            $canOfficiate = $userTitle && (! $approverTitle || $userTitle->tier >= $approverTitle->tier);
        }

        return Inertia::render('Titles/Ceremony', [
            'petition' => [
                'id' => $petition->id,
                'petitioner' => [
                    'id' => $petition->petitioner->id,
                    'username' => $petition->petitioner->username,
                ],
                'title_type' => [
                    'id' => $petition->titleType->id,
                    'name' => $petition->titleType->name,
                    'style_of_address' => $petition->titleType->style_of_address,
                    'female_variant' => $petition->titleType->female_variant,
                ],
                'approved_by' => [
                    'id' => $petition->petitionTo->id,
                    'username' => $petition->petitionTo->username,
                    'styled_name' => $this->titleService->getStyledName($petition->petitionTo),
                ],
                'ceremony_scheduled_at' => $petition->ceremony_scheduled_at?->format('Y-m-d H:i'),
            ],
            'can_officiate' => $canOfficiate,
        ]);
    }

    /**
     * Complete a ceremony.
     */
    public function completeCeremony(Request $request, TitlePetition $petition): RedirectResponse
    {
        $user = $request->user();

        $result = $this->titleService->completeCeremony($petition, $user);

        if ($result['success']) {
            return redirect()->route('titles.index')->with('success', $result['message']);
        }

        return back()->withErrors(['ceremony' => $result['message']]);
    }

    /**
     * Grant a title directly (for appointment-type titles).
     */
    public function grantTitle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'title_type_id' => 'required|exists:title_types,id',
            'domain_type' => 'nullable|string',
            'domain_id' => 'nullable|integer',
        ]);

        $user = $request->user();
        $recipient = User::findOrFail($validated['recipient_id']);
        $titleType = TitleType::findOrFail($validated['title_type_id']);

        $result = $this->titleService->grantTitle(
            $recipient,
            $titleType,
            $user,
            $validated['domain_type'] ?? null,
            $validated['domain_id'] ?? null,
            'appointment'
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->withErrors(['grant' => $result['message']]);
    }
}

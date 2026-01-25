<?php

namespace App\Http\Controllers;

use App\Models\Accusation;
use App\Models\Bounty;
use App\Models\CrimeType;
use App\Models\Exile;
use App\Models\JailInmate;
use App\Models\Outlaw;
use App\Models\Punishment;
use App\Models\Trial;
use App\Models\User;
use App\Services\CrimeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CrimeController extends Controller
{
    public function __construct(
        protected CrimeService $crimeService
    ) {}

    /**
     * Display the player's criminal record and status.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get player's criminal status
        $isJailed = $this->crimeService->isJailed($user);
        $isOutlaw = $this->crimeService->isOutlaw($user);

        $jailInfo = null;
        if ($isJailed) {
            $jailInmate = JailInmate::where('prisoner_id', $user->id)
                ->currentlyServing()
                ->first();
            if ($jailInmate) {
                $jailInfo = [
                    'location' => $jailInmate->getJailLocation()?->name,
                    'remaining_days' => $jailInmate->getRemainingDays(),
                    'release_at' => $jailInmate->release_at->diffForHumans(),
                ];
            }
        }

        $outlawInfo = null;
        if ($isOutlaw) {
            $outlaw = Outlaw::where('user_id', $user->id)->active()->first();
            if ($outlaw) {
                $outlawInfo = [
                    'declared_by' => $outlaw->getDeclaredByLocation()?->name,
                    'reason' => $outlaw->reason,
                ];
            }
        }

        // Get active exiles
        $exiles = Exile::where('user_id', $user->id)
            ->active()
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'location' => $e->getExiledFromLocation()?->name,
                'location_type' => $e->exiled_from_type,
                'reason' => $e->reason,
                'expires_at' => $e->expires_at?->diffForHumans(),
                'is_permanent' => $e->isPermanent(),
            ]);

        // Get active bounties on the player
        $bounties = Bounty::forTarget($user->id)
            ->active()
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'reward' => $b->reward_amount,
                'capture_type' => $b->capture_type_display,
                'reason' => $b->reason,
                'posted_by' => $b->poster_type === 'player' ? $b->postedBy?->username : ucfirst($b->poster_type),
            ]);

        // Get past punishments
        $punishments = Punishment::forCriminal($user->id)
            ->with('trial.crime.crimeType')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'type' => $p->type_display,
                'description' => $p->description,
                'status' => $p->status_display,
                'crime' => $p->trial?->crime?->crimeType?->name,
                'created_at' => $p->created_at->diffForHumans(),
            ]);

        // Get pending trials
        $pendingTrials = Trial::where('defendant_id', $user->id)
            ->pending()
            ->with(['crime.crimeType', 'judge'])
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'crime' => $t->crime->crimeType->name,
                'court' => $t->court_display,
                'judge' => $t->judge?->username,
                'scheduled_at' => $t->scheduled_at?->diffForHumans(),
                'status' => $t->status_display,
            ]);

        return Inertia::render('Crime/Index', [
            'player' => [
                'id' => $user->id,
                'username' => $user->username,
            ],
            'status' => [
                'is_jailed' => $isJailed,
                'is_outlaw' => $isOutlaw,
                'jail_info' => $jailInfo,
                'outlaw_info' => $outlawInfo,
            ],
            'exiles' => $exiles,
            'bounties' => $bounties,
            'punishments' => $punishments,
            'pending_trials' => $pendingTrials,
        ]);
    }

    /**
     * Display the accusation form.
     */
    public function accuseForm(Request $request): Response
    {
        $user = $request->user();

        // Get crime types available for accusation (exclude internal system crimes)
        $crimeTypes = CrimeType::orderBy('severity')
            ->orderBy('name')
            ->get()
            ->map(fn($ct) => [
                'slug' => $ct->slug,
                'name' => $ct->name,
                'description' => $ct->description,
                'severity' => $ct->severity,
                'severity_display' => $ct->severity_display,
                'court_level' => $ct->court_level,
                'court_display' => $ct->court_display,
            ]);

        // Get players in the same location that can be accused
        $playersInLocation = User::where('id', '!=', $user->id)
            ->where('current_village_id', $user->current_village_id)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'username' => $p->username,
            ]);

        return Inertia::render('Crime/Accuse', [
            'crime_types' => $crimeTypes,
            'players_in_location' => $playersInLocation,
            'current_location' => $user->currentVillage?->name ?? 'Unknown',
        ]);
    }

    /**
     * File an accusation against another player.
     */
    public function accuse(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'accused_id' => 'required|exists:users,id',
            'crime_type_slug' => 'required|exists:crime_types,slug',
            'accusation_text' => 'required|string|max:2000',
            'evidence' => 'nullable|array',
        ]);

        $accused = User::findOrFail($validated['accused_id']);

        // Use player's current location
        $locationType = 'village';
        $locationId = $user->current_village_id;

        $result = $this->crimeService->fileAccusation(
            $user,
            $accused,
            $validated['crime_type_slug'],
            $locationType,
            $locationId,
            $validated['accusation_text'],
            $validated['evidence'] ?? []
        );

        if (is_string($result)) {
            return back()->with('error', $result);
        }

        return back()->with('success', 'Accusation filed successfully. It will be reviewed by the local authority.');
    }

    /**
     * Withdraw an accusation.
     */
    public function withdrawAccusation(Request $request, Accusation $accusation): RedirectResponse
    {
        $user = $request->user();

        if ($accusation->accuser_id !== $user->id) {
            return back()->with('error', 'This is not your accusation.');
        }

        if (!$accusation->isPending()) {
            return back()->with('error', 'This accusation can no longer be withdrawn.');
        }

        $accusation->update(['status' => Accusation::STATUS_WITHDRAWN]);

        return back()->with('success', 'Accusation withdrawn.');
    }

    /**
     * Post a bounty on another player.
     */
    public function postBounty(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'target_id' => 'required|exists:users,id',
            'reward_amount' => 'required|integer|min:100|max:1000000',
            'reason' => 'required|string|max:500',
            'capture_type' => 'required|in:alive,dead_or_alive,dead',
        ]);

        $target = User::findOrFail($validated['target_id']);

        if ($target->id === $user->id) {
            return back()->with('error', 'You cannot post a bounty on yourself.');
        }

        $result = $this->crimeService->postBounty(
            $target,
            $user,
            $validated['reward_amount'],
            $validated['reason'],
            $validated['capture_type']
        );

        if (is_string($result)) {
            return back()->with('error', $result);
        }

        return back()->with('success', 'Bounty posted successfully.');
    }

    /**
     * Cancel a bounty you posted.
     */
    public function cancelBounty(Request $request, Bounty $bounty): RedirectResponse
    {
        $user = $request->user();

        if ($bounty->posted_by !== $user->id) {
            return back()->with('error', 'This is not your bounty.');
        }

        if (!$bounty->isActive()) {
            return back()->with('error', 'This bounty is no longer active.');
        }

        $bounty->cancel();

        return back()->with('success', 'Bounty cancelled. Your gold has been refunded.');
    }

    /**
     * View bounty board (active bounties).
     */
    public function bountyBoard(Request $request): Response
    {
        $bounties = Bounty::active()
            ->with(['target', 'postedBy', 'crime.crimeType'])
            ->orderByDesc('reward_amount')
            ->paginate(20);

        return Inertia::render('Crime/BountyBoard', [
            'bounties' => $bounties,
        ]);
    }

    /**
     * View crime types reference.
     */
    public function crimeTypes(): Response
    {
        $crimeTypes = CrimeType::orderBy('severity')->get();

        return Inertia::render('Crime/CrimeTypes', [
            'crime_types' => $crimeTypes,
        ]);
    }

    // ==================== JUDGE ACTIONS ====================

    /**
     * View pending accusations (for judges).
     */
    public function pendingAccusations(Request $request): Response
    {
        $user = $request->user();

        // Determine which locations this user can judge
        $accusations = collect();

        // Check village authority
        if ($user->current_village_id && $this->crimeService->hasJudicialAuthority($user, 'village', $user->current_village_id)) {
            $accusations = $accusations->merge(
                $this->crimeService->getPendingAccusations('village', $user->current_village_id)
            );
        }

        // Check barony authority
        $homeVillage = $user->homeVillage;
        if ($homeVillage && $homeVillage->barony_id && $this->crimeService->hasJudicialAuthority($user, 'barony', $homeVillage->barony_id)) {
            $accusations = $accusations->merge(
                $this->crimeService->getPendingAccusations('barony', $homeVillage->barony_id)
            );
        }

        return Inertia::render('Crime/PendingAccusations', [
            'accusations' => $accusations->map(fn($a) => [
                'id' => $a->id,
                'accuser' => $a->accuser->username,
                'accused' => $a->accused->username,
                'crime_type' => $a->crimeType->name,
                'accusation_text' => $a->accusation_text,
                'evidence' => $a->evidence_provided,
                'created_at' => $a->created_at->diffForHumans(),
            ]),
        ]);
    }

    /**
     * Review an accusation (accept, reject, or mark as false).
     */
    public function reviewAccusation(Request $request, Accusation $accusation): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'decision' => 'required|in:accept,reject,false',
            'notes' => 'nullable|string|max:1000',
        ]);

        $result = $this->crimeService->reviewAccusation(
            $accusation,
            $user,
            $validated['decision'],
            $validated['notes']
        );

        if (is_string($result)) {
            return back()->with('error', $result);
        }

        $message = match ($validated['decision']) {
            'accept' => 'Accusation accepted. A trial has been scheduled.',
            'reject' => 'Accusation rejected.',
            'false' => 'Accusation marked as false. The accuser may face charges.',
        };

        return back()->with('success', $message);
    }

    /**
     * View pending trials (for judges).
     */
    public function pendingTrials(Request $request): Response
    {
        $user = $request->user();

        $trials = $this->crimeService->getPendingTrials($user);

        return Inertia::render('Crime/PendingTrials', [
            'trials' => $trials->map(fn($t) => [
                'id' => $t->id,
                'defendant' => $t->defendant->username,
                'crime' => $t->crime->crimeType->name,
                'crime_description' => $t->crime->description,
                'court' => $t->court_display,
                'status' => $t->status_display,
                'scheduled_at' => $t->scheduled_at?->format('M j, Y'),
                'prosecution_argument' => $t->prosecution_argument,
                'defense_argument' => $t->defense_argument,
            ]),
        ]);
    }

    /**
     * Render a verdict in a trial.
     */
    public function renderVerdict(Request $request, Trial $trial): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'verdict' => 'required|in:guilty,not_guilty,dismissed',
            'reasoning' => 'required|string|max:2000',
            'punishments' => 'nullable|array',
            'punishments.*.type' => 'required_with:punishments|in:fine,jail,exile,outlawry,execution,community_service',
            'punishments.*.fine_amount' => 'nullable|integer|min:0',
            'punishments.*.jail_days' => 'nullable|integer|min:1|max:365',
            'punishments.*.exile_from_type' => 'nullable|in:village,barony,kingdom',
            'punishments.*.exile_from_id' => 'nullable|integer',
        ]);

        $punishments = $validated['verdict'] === 'guilty' ? ($validated['punishments'] ?? []) : [];

        $result = $this->crimeService->renderVerdict(
            $trial,
            $user,
            $validated['verdict'],
            $validated['reasoning'],
            $punishments
        );

        if (is_string($result)) {
            return back()->with('error', $result);
        }

        return back()->with('success', 'Verdict rendered successfully.');
    }

    /**
     * Pardon a punishment.
     */
    public function pardon(Request $request, Punishment $punishment): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        // Check authority - must be higher than the original issuer
        // For now, only kings can pardon
        $hasAuthority = \App\Models\PlayerRole::where('user_id', $user->id)
            ->whereHas('role', fn($q) => $q->where('slug', 'king'))
            ->where('status', \App\Models\PlayerRole::STATUS_ACTIVE)
            ->exists();

        if (!$hasAuthority) {
            return back()->with('error', 'You do not have the authority to issue pardons.');
        }

        if ($punishment->isCompleted() || $punishment->isPardoned()) {
            return back()->with('error', 'This punishment has already been completed or pardoned.');
        }

        $punishment->pardon($user, $validated['notes']);

        // Also end any active exile/outlaw/jail
        if ($punishment->jailInmate) {
            $punishment->jailInmate->release();
        }
        if ($punishment->outlaw) {
            $punishment->outlaw->pardon();
        }
        if ($punishment->exile) {
            $punishment->exile->pardon();
        }

        return back()->with('success', 'Pardon granted.');
    }
}

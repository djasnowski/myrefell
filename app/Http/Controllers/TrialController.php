<?php

namespace App\Http\Controllers;

use App\Models\Trial;
use App\Services\CrimeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrialController extends Controller
{
    public function __construct(
        protected CrimeService $crimeService
    ) {}

    /**
     * Display a specific trial.
     */
    public function show(Request $request, Trial $trial): Response
    {
        $user = $request->user();

        // Load all necessary relationships
        $trial->load([
            'crime.crimeType',
            'crime.witnesses.witness',
            'accusation.accuser',
            'accusation.accused',
            'defendant',
            'judge',
            'punishments',
        ]);

        // Determine user's role in this trial
        $isDefendant = $trial->defendant_id === $user->id;
        $isAccuser = $trial->accusation?->accuser_id === $user->id;
        $isJudge = $trial->judge_id === $user->id;

        // Check if user can see the defense form (defendant in active trial)
        $canSubmitDefense = $isDefendant &&
            in_array($trial->status, [Trial::STATUS_SCHEDULED, Trial::STATUS_IN_PROGRESS]) &&
            empty($trial->defense_argument);

        // Check if judge can render verdict
        $canRenderVerdict = $isJudge &&
            in_array($trial->status, [Trial::STATUS_IN_PROGRESS, Trial::STATUS_AWAITING_VERDICT]);

        // Build witnesses data
        $witnesses = $trial->crime?->witnesses?->map(fn($w) => [
            'id' => $w->id,
            'name' => $w->getWitnessName(),
            'testimony' => $w->testimony,
            'has_testified' => $w->has_testified,
        ]) ?? collect();

        // Build punishments data
        $punishments = $trial->punishments->map(fn($p) => [
            'id' => $p->id,
            'type' => $p->type,
            'type_display' => $p->type_display,
            'description' => $p->description,
            'status' => $p->status,
            'status_display' => $p->status_display,
        ]);

        // Get the trial location
        $location = $trial->getLocation();

        return Inertia::render('Crime/TrialShow', [
            'trial' => [
                'id' => $trial->id,
                'status' => $trial->status,
                'status_display' => $trial->status_display,
                'court_level' => $trial->court_level,
                'court_display' => $trial->court_display,
                'location_name' => $location?->name ?? 'Unknown',
                'scheduled_at' => $trial->scheduled_at?->format('M j, Y'),
                'started_at' => $trial->started_at?->format('M j, Y'),
                'concluded_at' => $trial->concluded_at?->format('M j, Y'),
                'verdict' => $trial->verdict,
                'verdict_display' => $trial->verdict_display,
                'verdict_reasoning' => $trial->verdict_reasoning,
                'prosecution_argument' => $trial->prosecution_argument,
                'defense_argument' => $trial->defense_argument,
                'can_appeal' => $trial->canAppeal() && $isDefendant,
            ],
            'crime' => [
                'type' => $trial->crime?->crimeType?->name,
                'severity' => $trial->crime?->crimeType?->severity,
                'severity_display' => $trial->crime?->crimeType?->severity_display,
                'description' => $trial->crime?->description,
                'committed_at' => $trial->crime?->committed_at?->format('M j, Y'),
            ],
            'accusation' => [
                'text' => $trial->accusation?->accusation_text,
                'evidence' => $trial->accusation?->evidence_provided ?? [],
            ],
            'defendant' => [
                'id' => $trial->defendant?->id,
                'username' => $trial->defendant?->username,
            ],
            'accuser' => [
                'id' => $trial->accusation?->accuser?->id,
                'username' => $trial->accusation?->accuser?->username,
            ],
            'judge' => $trial->judge ? [
                'id' => $trial->judge->id,
                'username' => $trial->judge->username,
            ] : null,
            'witnesses' => $witnesses,
            'punishments' => $punishments,
            'user_role' => [
                'is_defendant' => $isDefendant,
                'is_accuser' => $isAccuser,
                'is_judge' => $isJudge,
                'can_submit_defense' => $canSubmitDefense,
                'can_render_verdict' => $canRenderVerdict,
            ],
        ]);
    }

    /**
     * Submit a defense argument for a trial.
     */
    public function submitDefense(Request $request, Trial $trial)
    {
        $user = $request->user();

        // Verify user is the defendant
        if ($trial->defendant_id !== $user->id) {
            return back()->with('error', 'You are not the defendant in this trial.');
        }

        // Verify trial is in the right state
        if (!in_array($trial->status, [Trial::STATUS_SCHEDULED, Trial::STATUS_IN_PROGRESS])) {
            return back()->with('error', 'This trial is no longer accepting defense arguments.');
        }

        $validated = $request->validate([
            'defense_argument' => 'required|string|max:2000',
        ]);

        // Update the trial with the defense argument
        $trial->update([
            'defense_argument' => $validated['defense_argument'],
            'status' => Trial::STATUS_AWAITING_VERDICT,
        ]);

        // If trial wasn't started yet, start it now
        if (!$trial->started_at) {
            $trial->update(['started_at' => now()]);
        }

        return back()->with('success', 'Your defense has been submitted.');
    }
}

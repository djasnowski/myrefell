<?php

namespace App\Http\Controllers;

use App\Models\Dynasty;
use App\Models\DynastyEvent;
use App\Models\DynastyMember;
use App\Models\SuccessionRule;
use App\Services\DynastyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SuccessionController extends Controller
{
    const CHANGE_RULES_COST = 200;
    const DISINHERIT_COST = 100;

    public function __construct(
        protected DynastyService $dynastyService
    ) {}

    /**
     * Display succession settings page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if (!$user->dynasty_id) {
            return redirect()->route('dynasty.index');
        }

        $dynasty = Dynasty::with(['successionRules'])
            ->find($user->dynasty_id);

        $rules = $dynasty->successionRules;
        $successionLine = $this->getSuccessionLine($dynasty);

        return Inertia::render('Dynasty/Succession', [
            'dynasty' => [
                'id' => $dynasty->id,
                'name' => $dynasty->name,
                'prestige' => $dynasty->prestige,
            ],
            'rules' => $rules ? [
                'succession_type' => $rules->succession_type,
                'gender_law' => $rules->gender_law,
                'allows_bastards' => $rules->allows_bastards,
                'allows_adoption' => $rules->allows_adoption,
                'minimum_age' => $rules->minimum_age,
            ] : null,
            'succession_line' => $successionLine,
            'is_head' => $dynasty->current_head_id === $user->id,
            'succession_types' => $this->getSuccessionTypes(),
            'gender_laws' => $this->getGenderLaws(),
            'change_cost' => self::CHANGE_RULES_COST,
            'disinherit_cost' => self::DISINHERIT_COST,
        ]);
    }

    /**
     * Update succession rules.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        if (!$user->dynasty_id) {
            return back()->with('error', 'You do not have a dynasty.');
        }

        $dynasty = Dynasty::find($user->dynasty_id);

        if ($dynasty->current_head_id !== $user->id) {
            return back()->with('error', 'Only the dynasty head can change succession rules.');
        }

        if ($dynasty->prestige < self::CHANGE_RULES_COST) {
            return back()->with('error', 'You need at least ' . self::CHANGE_RULES_COST . ' prestige to change succession rules.');
        }

        $validated = $request->validate([
            'succession_type' => 'required|in:primogeniture,ultimogeniture,seniority,elective,gavelkind',
            'gender_law' => 'required|in:agnatic,agnatic-cognatic,cognatic,enatic',
            'allows_bastards' => 'boolean',
            'allows_adoption' => 'boolean',
            'minimum_age' => 'required|integer|min:0|max:100',
        ]);

        $rules = $dynasty->successionRules;

        $rules->update([
            'succession_type' => $validated['succession_type'],
            'gender_law' => $validated['gender_law'],
            'allows_bastards' => $validated['allows_bastards'] ?? false,
            'allows_adoption' => $validated['allows_adoption'] ?? false,
            'minimum_age' => $validated['minimum_age'],
        ]);

        // Deduct prestige
        $dynasty->decrement('prestige', self::CHANGE_RULES_COST);

        // Log event
        DynastyEvent::create([
            'dynasty_id' => $dynasty->id,
            'event_type' => DynastyEvent::TYPE_SUCCESSION,
            'title' => 'Succession Rules Changed',
            'description' => "The succession rules of {$dynasty->name} have been modified.",
            'prestige_change' => -self::CHANGE_RULES_COST,
            'occurred_at' => now(),
        ]);

        // Recalculate heir based on new rules
        $this->dynastyService->recalculateHeir($dynasty);

        return redirect()->route('dynasty.succession')->with('success', 'Succession rules updated successfully.');
    }

    /**
     * Disinherit a member.
     */
    public function disinherit(Request $request, DynastyMember $member)
    {
        $user = $request->user();

        if (!$user->dynasty_id) {
            return back()->with('error', 'You do not have a dynasty.');
        }

        $dynasty = Dynasty::find($user->dynasty_id);

        if ($dynasty->current_head_id !== $user->id) {
            return back()->with('error', 'Only the dynasty head can disinherit members.');
        }

        if ($member->dynasty_id !== $dynasty->id) {
            return back()->with('error', 'This member is not part of your dynasty.');
        }

        if ($member->is_disinherited) {
            return back()->with('error', 'This member is already disinherited.');
        }

        if ($dynasty->prestige < self::DISINHERIT_COST) {
            return back()->with('error', 'You need at least ' . self::DISINHERIT_COST . ' prestige to disinherit a member.');
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $this->dynastyService->disinherit($member, $validated['reason'] ?? null);

        // Additional prestige cost on top of what disinherit() already deducts
        $dynasty->decrement('prestige', self::DISINHERIT_COST - 25); // disinherit() already deducts 25

        return redirect()->route('dynasty.succession')->with('success', "{$member->full_name} has been disinherited.");
    }

    /**
     * Get the succession line based on current rules.
     */
    private function getSuccessionLine(Dynasty $dynasty): array
    {
        $rules = $dynasty->successionRules;
        if (!$rules) {
            return [];
        }

        $heirs = $rules->determineHeirs();

        return collect($heirs)->take(10)->map(function ($heir) {
            if ($heir instanceof DynastyMember) {
                $member = $heir;
            } else {
                $member = DynastyMember::find($heir['id']);
            }

            if (!$member) {
                return null;
            }

            return [
                'id' => $member->id,
                'name' => $member->full_name,
                'first_name' => $member->first_name,
                'generation' => $member->generation,
                'gender' => $member->gender,
                'age' => $member->age,
                'is_legitimate' => $member->is_legitimate,
                'is_disinherited' => $member->is_disinherited,
                'is_heir' => $member->is_heir,
                'status' => $member->status,
            ];
        })->filter()->values()->toArray();
    }

    /**
     * Get succession types with descriptions.
     */
    private function getSuccessionTypes(): array
    {
        return [
            [
                'value' => SuccessionRule::TYPE_PRIMOGENITURE,
                'label' => 'Primogeniture',
                'description' => 'Eldest child inherits',
            ],
            [
                'value' => SuccessionRule::TYPE_ULTIMOGENITURE,
                'label' => 'Ultimogeniture',
                'description' => 'Youngest child inherits',
            ],
            [
                'value' => SuccessionRule::TYPE_SENIORITY,
                'label' => 'Seniority',
                'description' => 'Oldest living member inherits',
            ],
            [
                'value' => SuccessionRule::TYPE_ELECTIVE,
                'label' => 'Elective',
                'description' => 'Members vote to choose heir',
            ],
            [
                'value' => SuccessionRule::TYPE_GAVELKIND,
                'label' => 'Gavelkind',
                'description' => 'Titles split among children',
            ],
        ];
    }

    /**
     * Get gender laws with descriptions.
     */
    private function getGenderLaws(): array
    {
        return [
            [
                'value' => SuccessionRule::GENDER_AGNATIC,
                'label' => 'Agnatic',
                'description' => 'Males only',
            ],
            [
                'value' => SuccessionRule::GENDER_AGNATIC_COGNATIC,
                'label' => 'Agnatic-Cognatic',
                'description' => 'Males inherit first, females if no males',
            ],
            [
                'value' => SuccessionRule::GENDER_COGNATIC,
                'label' => 'Absolute Cognatic',
                'description' => 'Equal inheritance (by birth order)',
            ],
            [
                'value' => SuccessionRule::GENDER_ENATIC,
                'label' => 'Enatic',
                'description' => 'Females only',
            ],
        ];
    }
}

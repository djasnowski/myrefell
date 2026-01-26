<?php

namespace App\Http\Controllers;

use App\Models\Dynasty;
use App\Models\DynastyEvent;
use App\Models\DynastyMember;
use App\Models\Marriage;
use App\Services\DynastyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DynastyController extends Controller
{
    const FOUNDING_COST = 100;

    public function __construct(
        protected DynastyService $dynastyService
    ) {}

    /**
     * Display dynasty overview page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Check if user has a dynasty
        if ($user->dynasty_id) {
            $dynasty = Dynasty::with(['founder', 'currentHead', 'successionRules'])
                ->find($user->dynasty_id);

            $members = DynastyMember::where('dynasty_id', $dynasty->id)
                ->where('status', 'alive')
                ->with('user')
                ->orderBy('generation')
                ->orderBy('birth_order')
                ->get()
                ->map(fn ($member) => $this->mapMember($member));

            $heir = $dynasty->getHeir();

            $recentEvents = DynastyEvent::where('dynasty_id', $dynasty->id)
                ->with('member')
                ->orderBy('occurred_at', 'desc')
                ->limit(5)
                ->get()
                ->map(fn ($event) => $this->mapEvent($event));

            return Inertia::render('Dynasty/Index', [
                'dynasty' => [
                    'id' => $dynasty->id,
                    'name' => $dynasty->name,
                    'motto' => $dynasty->motto,
                    'coat_of_arms' => $dynasty->coat_of_arms,
                    'prestige' => $dynasty->prestige,
                    'prestige_rank' => $this->getPrestigeRank($dynasty->prestige),
                    'wealth_score' => $dynasty->wealth_score,
                    'members_count' => $dynasty->members_count,
                    'living_members' => $members->count(),
                    'generations' => $dynasty->generations,
                    'founded_at' => $dynasty->founded_at?->format('Y'),
                    'head' => $dynasty->currentHead ? [
                        'id' => $dynasty->currentHead->id,
                        'name' => $dynasty->currentHead->username,
                    ] : null,
                    'heir' => $heir ? [
                        'id' => $heir->id,
                        'name' => $heir->full_name,
                        'relation' => $this->getRelation($heir, $user),
                        'age' => $heir->age,
                    ] : null,
                ],
                'members' => $members->toArray(),
                'recent_events' => $recentEvents->toArray(),
                'is_head' => $dynasty->current_head_id === $user->id,
                'can_found' => false,
            ]);
        }

        // User doesn't have a dynasty - show founding form
        return Inertia::render('Dynasty/Index', [
            'dynasty' => null,
            'members' => [],
            'recent_events' => [],
            'is_head' => false,
            'can_found' => $this->canFoundDynasty($user),
            'founding_cost' => self::FOUNDING_COST,
            'founding_requirements' => $this->getFoundingRequirements($user),
        ]);
    }

    /**
     * Found a new dynasty.
     */
    public function found(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:dynasties,name',
            'motto' => 'nullable|string|max:100',
        ]);

        if (!$this->canFoundDynasty($user)) {
            return back()->with('error', 'You do not meet the requirements to found a dynasty.');
        }

        if ($user->gold < self::FOUNDING_COST) {
            return back()->with('error', 'You need at least ' . self::FOUNDING_COST . ' gold to found a dynasty.');
        }

        $user->decrement('gold', self::FOUNDING_COST);

        $this->dynastyService->foundDynasty(
            $user,
            $validated['name'],
            $validated['motto'] ?? null
        );

        return redirect()->route('dynasty.index')->with('success', 'Dynasty founded successfully!');
    }

    /**
     * Update dynasty details.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        if (!$user->dynasty_id) {
            return back()->with('error', 'You do not have a dynasty.');
        }

        $dynasty = Dynasty::find($user->dynasty_id);

        if ($dynasty->current_head_id !== $user->id) {
            return back()->with('error', 'Only the dynasty head can update dynasty details.');
        }

        $validated = $request->validate([
            'motto' => 'nullable|string|max:100',
        ]);

        $dynasty->update([
            'motto' => $validated['motto'],
        ]);

        return redirect()->route('dynasty.index')->with('success', 'Dynasty updated successfully!');
    }

    /**
     * Display dynasty family tree.
     */
    public function tree(Request $request): Response
    {
        $user = $request->user();

        if (!$user->dynasty_id) {
            return redirect()->route('dynasty.index');
        }

        $dynasty = Dynasty::with('currentHead')->find($user->dynasty_id);

        // Get all dynasty members with relationships
        $members = DynastyMember::where('dynasty_id', $dynasty->id)
            ->with(['father', 'mother', 'user'])
            ->orderBy('generation')
            ->orderBy('birth_order')
            ->get();

        // Get all marriages involving dynasty members
        $memberIds = $members->pluck('id')->toArray();
        $marriages = Marriage::where(function ($q) use ($memberIds) {
            $q->whereIn('spouse1_id', $memberIds)
                ->orWhereIn('spouse2_id', $memberIds);
        })
            ->with(['spouse1', 'spouse2'])
            ->get();

        // Get player's dynasty member record
        $playerMember = $members->firstWhere('user_id', $user->id);

        return Inertia::render('Dynasty/Tree', [
            'dynasty' => [
                'id' => $dynasty->id,
                'name' => $dynasty->name,
                'motto' => $dynasty->motto,
                'prestige' => $dynasty->prestige,
                'generations' => $dynasty->generations,
                'head_id' => $dynasty->current_head_id,
            ],
            'members' => $members->map(fn ($member) => $this->mapTreeMember($member, $dynasty)),
            'marriages' => $marriages->map(fn ($marriage) => [
                'id' => $marriage->id,
                'spouse1_id' => $marriage->spouse1_id,
                'spouse2_id' => $marriage->spouse2_id,
                'status' => $marriage->status,
                'wedding_date' => $marriage->wedding_date?->format('Y'),
            ]),
            'player_member_id' => $playerMember?->id,
        ]);
    }

    /**
     * Map dynasty member for tree view.
     */
    private function mapTreeMember(DynastyMember $member, Dynasty $dynasty): array
    {
        return [
            'id' => $member->id,
            'name' => $member->full_name,
            'first_name' => $member->first_name,
            'gender' => $member->gender,
            'generation' => $member->generation,
            'birth_order' => $member->birth_order,
            'father_id' => $member->father_id,
            'mother_id' => $member->mother_id,
            'status' => $member->status,
            'is_alive' => $member->isAlive(),
            'is_heir' => $member->is_heir,
            'is_head' => $member->user_id && $dynasty->current_head_id === $member->user_id,
            'is_player' => $member->user_id !== null,
            'is_legitimate' => $member->is_legitimate,
            'is_disinherited' => $member->is_disinherited,
            'birth_year' => $member->birth_date?->format('Y'),
            'death_year' => $member->death_date?->format('Y'),
            'age' => $member->age,
            'user' => $member->user ? [
                'id' => $member->user->id,
                'name' => $member->user->username,
            ] : null,
        ];
    }

    /**
     * Check if user can found a dynasty.
     */
    private function canFoundDynasty($user): bool
    {
        // Already has dynasty
        if ($user->dynasty_id) {
            return false;
        }

        // Must be freeman or higher
        if ($user->social_class === 'serf') {
            return false;
        }

        // Must have enough gold
        if ($user->gold < self::FOUNDING_COST) {
            return false;
        }

        return true;
    }

    /**
     * Get founding requirements with status.
     */
    private function getFoundingRequirements($user): array
    {
        return [
            [
                'label' => 'Be a Freeman or higher social class',
                'met' => $user->social_class !== 'serf',
            ],
            [
                'label' => 'Have ' . self::FOUNDING_COST . ' gold',
                'met' => $user->gold >= self::FOUNDING_COST,
            ],
        ];
    }

    /**
     * Get prestige rank based on prestige score.
     */
    private function getPrestigeRank(int $prestige): string
    {
        return match (true) {
            $prestige >= 5000 => 'Legendary',
            $prestige >= 2500 => 'Illustrious',
            $prestige >= 1000 => 'Notable',
            $prestige >= 500 => 'Established',
            $prestige >= 100 => 'Rising',
            default => 'Unknown',
        };
    }

    /**
     * Get relation description for heir.
     */
    private function getRelation(DynastyMember $member, $user): string
    {
        if ($member->user_id === $user->id) {
            return 'you';
        }

        // Simple relation based on generation difference
        $userMember = DynastyMember::where('user_id', $user->id)
            ->where('dynasty_id', $member->dynasty_id)
            ->first();

        if (!$userMember) {
            return 'member';
        }

        $genDiff = $member->generation - $userMember->generation;

        if ($genDiff === 1) {
            return $member->gender === 'male' ? 'son' : 'daughter';
        } elseif ($genDiff === -1) {
            return $member->gender === 'male' ? 'father' : 'mother';
        } elseif ($genDiff === 0) {
            return $member->gender === 'male' ? 'brother' : 'sister';
        } elseif ($genDiff === 2) {
            return $member->gender === 'male' ? 'grandson' : 'granddaughter';
        }

        return 'relative';
    }

    /**
     * Map dynasty member for frontend.
     */
    private function mapMember(DynastyMember $member): array
    {
        return [
            'id' => $member->id,
            'name' => $member->full_name,
            'first_name' => $member->first_name,
            'gender' => $member->gender,
            'age' => $member->age,
            'generation' => $member->generation,
            'is_heir' => $member->is_heir,
            'is_head' => $member->user_id && $member->dynasty->current_head_id === $member->user_id,
            'is_married' => $member->isMarried(),
            'is_player' => $member->user_id !== null,
            'status' => $member->status,
            'user' => $member->user ? [
                'id' => $member->user->id,
                'name' => $member->user->username,
            ] : null,
        ];
    }

    /**
     * Map dynasty event for frontend.
     */
    private function mapEvent(DynastyEvent $event): array
    {
        return [
            'id' => $event->id,
            'type' => $event->event_type,
            'title' => $event->title,
            'description' => $event->description,
            'prestige_change' => $event->prestige_change,
            'occurred_at' => $event->occurred_at?->format('M j, Y'),
            'member' => $event->member ? [
                'id' => $event->member->id,
                'name' => $event->member->full_name,
            ] : null,
        ];
    }
}

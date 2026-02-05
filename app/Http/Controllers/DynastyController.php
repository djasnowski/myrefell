<?php

namespace App\Http\Controllers;

use App\Models\Dynasty;
use App\Models\DynastyAlliance;
use App\Models\DynastyEvent;
use App\Models\DynastyMember;
use App\Models\Marriage;
use App\Models\User;
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

        if (! $this->canFoundDynasty($user)) {
            return back()->with('error', 'You do not meet the requirements to found a dynasty.');
        }

        if ($user->gold < self::FOUNDING_COST) {
            return back()->with('error', 'You need at least '.self::FOUNDING_COST.' gold to found a dynasty.');
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

        if (! $user->dynasty_id) {
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

        if (! $user->dynasty_id) {
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
                'label' => 'Have '.self::FOUNDING_COST.' gold',
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

        if (! $userMember) {
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

    /**
     * Display dynasty history page.
     */
    public function history(Request $request): Response
    {
        $user = $request->user();

        if (! $user->dynasty_id) {
            return redirect()->route('dynasty.index');
        }

        $dynasty = Dynasty::find($user->dynasty_id);
        $filter = $request->get('filter');

        $eventsQuery = DynastyEvent::where('dynasty_id', $dynasty->id)
            ->with('member')
            ->orderBy('occurred_at', 'desc');

        if ($filter) {
            $eventsQuery->where('event_type', $filter);
        }

        $events = $eventsQuery->paginate(20);

        // Calculate stats
        $stats = [
            'total_events' => DynastyEvent::where('dynasty_id', $dynasty->id)->count(),
            'births' => DynastyEvent::where('dynasty_id', $dynasty->id)->where('event_type', 'birth')->count(),
            'deaths' => DynastyEvent::where('dynasty_id', $dynasty->id)->where('event_type', 'death')->count(),
            'marriages' => DynastyEvent::where('dynasty_id', $dynasty->id)->where('event_type', 'marriage')->count(),
            'total_prestige_gained' => DynastyEvent::where('dynasty_id', $dynasty->id)
                ->where('prestige_change', '>', 0)
                ->sum('prestige_change'),
            'total_prestige_lost' => abs(DynastyEvent::where('dynasty_id', $dynasty->id)
                ->where('prestige_change', '<', 0)
                ->sum('prestige_change')),
        ];

        return Inertia::render('Dynasty/History', [
            'dynasty' => [
                'id' => $dynasty->id,
                'name' => $dynasty->name,
                'prestige' => $dynasty->prestige,
                'founded_at' => $dynasty->founded_at?->format('Y'),
            ],
            'events' => [
                'data' => $events->map(fn ($event) => [
                    'id' => $event->id,
                    'type' => $event->event_type,
                    'title' => $event->title,
                    'description' => $event->description,
                    'prestige_change' => $event->prestige_change,
                    'occurred_at' => $event->occurred_at?->format('M j, Y'),
                    'year' => $event->occurred_at?->year ?? 0,
                    'member' => $event->member ? [
                        'id' => $event->member->id,
                        'name' => $event->member->full_name,
                    ] : null,
                ]),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'total' => $events->total(),
            ],
            'stats' => $stats,
            'filter' => $filter,
        ]);
    }

    /**
     * Display dynasty alliances page.
     */
    public function alliances(Request $request): Response
    {
        $user = $request->user();

        if (! $user->dynasty_id) {
            return redirect()->route('dynasty.index');
        }

        $dynasty = Dynasty::find($user->dynasty_id);

        $activeAlliances = DynastyAlliance::involving($dynasty->id)
            ->active()
            ->with(['dynasty1', 'dynasty2', 'marriage.spouse1', 'marriage.spouse2'])
            ->get()
            ->map(fn ($alliance) => $this->mapAlliance($alliance, $dynasty));

        $pastAlliances = DynastyAlliance::involving($dynasty->id)
            ->where(function ($q) {
                $q->where('status', '!=', DynastyAlliance::STATUS_ACTIVE)
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('expires_at')
                            ->where('expires_at', '<=', now());
                    });
            })
            ->with(['dynasty1', 'dynasty2', 'marriage.spouse1', 'marriage.spouse2'])
            ->orderBy('ended_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($alliance) => $this->mapAlliance($alliance, $dynasty));

        return Inertia::render('Dynasty/Alliances', [
            'dynasty' => [
                'id' => $dynasty->id,
                'name' => $dynasty->name,
                'prestige' => $dynasty->prestige,
            ],
            'active_alliances' => $activeAlliances,
            'past_alliances' => $pastAlliances,
            'is_head' => $dynasty->current_head_id === $user->id,
        ]);
    }

    /**
     * Break an alliance.
     */
    public function breakAlliance(Request $request, DynastyAlliance $alliance)
    {
        $user = $request->user();

        if (! $user->dynasty_id) {
            return back()->with('error', 'You do not have a dynasty.');
        }

        $dynasty = Dynasty::find($user->dynasty_id);

        // Must be dynasty head
        if ($dynasty->current_head_id !== $user->id) {
            return back()->with('error', 'Only the dynasty head can break alliances.');
        }

        // Must be involved in the alliance
        if ($alliance->dynasty1_id !== $dynasty->id && $alliance->dynasty2_id !== $dynasty->id) {
            return back()->with('error', 'Your dynasty is not part of this alliance.');
        }

        // Can't break marriage alliances while marriage is active
        if ($alliance->alliance_type === DynastyAlliance::TYPE_MARRIAGE && $alliance->marriage?->isActive()) {
            return back()->with('error', 'Cannot break a marriage alliance while the marriage is active.');
        }

        // Calculate prestige penalty
        $prestigePenalty = match ($alliance->alliance_type) {
            DynastyAlliance::TYPE_BLOOD_OATH => 500,
            DynastyAlliance::TYPE_PACT => 100,
            default => 50,
        };

        $alliance->update([
            'status' => DynastyAlliance::STATUS_BROKEN,
            'ended_at' => now(),
        ]);

        $dynasty->decrement('prestige', $prestigePenalty);

        // Record event
        DynastyEvent::create([
            'dynasty_id' => $dynasty->id,
            'event_type' => 'alliance',
            'title' => 'Alliance Broken',
            'description' => "Broke {$alliance->alliance_type} alliance with House ".$alliance->getOtherDynasty($dynasty)?->name,
            'prestige_change' => -$prestigePenalty,
            'occurred_at' => now(),
        ]);

        return back()->with('success', 'Alliance broken. You lost '.$prestigePenalty.' prestige.');
    }

    /**
     * Leave the dynasty (for non-head members).
     */
    public function leave(Request $request)
    {
        $user = $request->user();

        if (! $user->dynasty_id) {
            return back()->with('error', 'You are not part of a dynasty.');
        }

        $dynasty = Dynasty::find($user->dynasty_id);

        if (! $dynasty->isActive()) {
            return back()->with('error', 'This dynasty has already been dissolved.');
        }

        // Head cannot leave - must dissolve or transfer headship first
        if ($dynasty->current_head_id === $user->id) {
            return back()->with('error', 'As head of the dynasty, you must either transfer headship or dissolve the dynasty.');
        }

        // Get user's dynasty member record
        $member = DynastyMember::where('dynasty_id', $dynasty->id)
            ->where('user_id', $user->id)
            ->first();

        // Record event before leaving
        DynastyEvent::create([
            'dynasty_id' => $dynasty->id,
            'dynasty_member_id' => $member?->id,
            'event_type' => 'departure',
            'title' => 'Member Departed',
            'description' => "{$user->username} hath forsaken House {$dynasty->name}",
            'prestige_change' => -25,
            'occurred_at' => now(),
        ]);

        // Update dynasty prestige
        $dynasty->decrement('prestige', 25);

        // Remove user from dynasty
        $user->update(['dynasty_id' => null]);

        // If member record exists, mark as departed (but keep for history)
        if ($member) {
            $member->update([
                'status' => 'departed',
                'user_id' => null, // Unlink from user but keep the historical record
            ]);
        }

        // Recalculate dynasty stats
        $dynasty->recalculateMembers();

        return redirect()->route('dynasty.index')->with('success', 'You have left House '.$dynasty->name.'.');
    }

    /**
     * Dissolve the dynasty (head only).
     */
    public function dissolve(Request $request)
    {
        $user = $request->user();

        if (! $user->dynasty_id) {
            return back()->with('error', 'You are not part of a dynasty.');
        }

        $dynasty = Dynasty::find($user->dynasty_id);

        if (! $dynasty->isActive()) {
            return back()->with('error', 'This dynasty has already been dissolved.');
        }

        // Only head can dissolve
        if ($dynasty->current_head_id !== $user->id) {
            return back()->with('error', 'Only the head of the dynasty can dissolve it.');
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        // Record final event
        DynastyEvent::create([
            'dynasty_id' => $dynasty->id,
            'event_type' => 'dissolution',
            'title' => 'Dynasty Dissolved',
            'description' => "House {$dynasty->name} hath been dissolved by {$user->username}. ".
                ($validated['reason'] ? "Reason: {$validated['reason']}" : 'The bloodline fades into history.'),
            'prestige_change' => -$dynasty->prestige, // Lose all prestige
            'occurred_at' => now(),
        ]);

        // Remove all users from dynasty
        User::where('dynasty_id', $dynasty->id)->update(['dynasty_id' => null]);

        // Mark all living members as departed
        DynastyMember::where('dynasty_id', $dynasty->id)
            ->where('status', 'alive')
            ->update([
                'status' => 'departed',
                'user_id' => null,
            ]);

        // Mark dynasty as dissolved
        $dynasty->update([
            'status' => Dynasty::STATUS_DISSOLVED,
            'dissolved_at' => now(),
            'dissolution_reason' => $validated['reason'] ?? 'The bloodline fades into history.',
            'current_head_id' => null,
            'prestige' => 0,
        ]);

        return redirect()->route('dynasty.index')->with('success', 'House '.$dynasty->name.' has been dissolved. Its history shall not be forgotten.');
    }

    /**
     * Map alliance for frontend.
     */
    private function mapAlliance(DynastyAlliance $alliance, Dynasty $myDynasty): array
    {
        $otherDynasty = $alliance->getOtherDynasty($myDynasty);

        return [
            'id' => $alliance->id,
            'type' => $alliance->alliance_type,
            'status' => $alliance->isActive() ? 'active' : $alliance->status,
            'other_dynasty' => [
                'id' => $otherDynasty?->id,
                'name' => $otherDynasty?->name ?? 'Unknown',
                'prestige' => $otherDynasty?->prestige ?? 0,
                'head' => $otherDynasty?->currentHead?->username,
            ],
            'marriage' => $alliance->marriage ? [
                'id' => $alliance->marriage->id,
                'spouse1' => $alliance->marriage->spouse1?->full_name ?? 'Unknown',
                'spouse2' => $alliance->marriage->spouse2?->full_name ?? 'Unknown',
            ] : null,
            'terms' => $alliance->terms,
            'formed_at' => $alliance->formed_at?->format('M j, Y'),
            'expires_at' => $alliance->expires_at?->format('M j, Y'),
            'ended_at' => $alliance->ended_at?->format('M j, Y'),
            'can_break' => $alliance->alliance_type !== DynastyAlliance::TYPE_MARRIAGE
                || ! $alliance->marriage?->isActive(),
        ];
    }
}

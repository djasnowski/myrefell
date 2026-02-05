<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dynasty;
use App\Models\DynastyMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DynastyController extends Controller
{
    /**
     * Display a listing of dynasties.
     */
    public function index(Request $request): Response
    {
        $query = Dynasty::query()
            ->with(['founder', 'currentHead'])
            ->withCount(['members', 'livingMembers']);

        // Search by name
        if ($search = $request->input('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        // Filter by minimum prestige
        if ($minPrestige = $request->input('min_prestige')) {
            $query->where('prestige', '>=', (int) $minPrestige);
        }

        $dynasties = $query->orderBy('prestige', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Dynasty $dynasty) => [
                'id' => $dynasty->id,
                'name' => $dynasty->name,
                'motto' => $dynasty->motto,
                'founder' => $dynasty->founder ? [
                    'id' => $dynasty->founder->id,
                    'username' => $dynasty->founder->username,
                ] : null,
                'current_head' => $dynasty->currentHead ? [
                    'id' => $dynasty->currentHead->id,
                    'username' => $dynasty->currentHead->username,
                ] : null,
                'members_count' => $dynasty->members_count,
                'living_members_count' => $dynasty->living_members_count,
                'prestige' => $dynasty->prestige,
                'wealth_score' => $dynasty->wealth_score,
                'generations' => $dynasty->generations,
                'founded_at' => $dynasty->founded_at?->toISOString(),
                'created_at' => $dynasty->created_at->toISOString(),
            ]);

        return Inertia::render('Admin/Dynasties/Index', [
            'dynasties' => $dynasties,
            'filters' => [
                'search' => $request->input('search', ''),
                'min_prestige' => $request->input('min_prestige', ''),
            ],
        ]);
    }

    /**
     * Display the specified dynasty.
     */
    public function show(Dynasty $dynasty): Response
    {
        $dynasty->load([
            'founder',
            'currentHead',
            'members' => fn ($q) => $q->with('user')->orderBy('generation')->orderBy('birth_order'),
            'events' => fn ($q) => $q->orderBy('occurred_at', 'desc')->limit(50),
            'successionRules',
        ]);

        return Inertia::render('Admin/Dynasties/Show', [
            'dynasty' => [
                'id' => $dynasty->id,
                'name' => $dynasty->name,
                'motto' => $dynasty->motto,
                'coat_of_arms' => $dynasty->coat_of_arms,
                'prestige' => $dynasty->prestige,
                'wealth_score' => $dynasty->wealth_score,
                'generations' => $dynasty->generations,
                'history' => $dynasty->history,
                'founded_at' => $dynasty->founded_at?->toISOString(),
                'created_at' => $dynasty->created_at->toISOString(),
                'founder' => $dynasty->founder ? [
                    'id' => $dynasty->founder->id,
                    'username' => $dynasty->founder->username,
                ] : null,
                'current_head' => $dynasty->currentHead ? [
                    'id' => $dynasty->currentHead->id,
                    'username' => $dynasty->currentHead->username,
                ] : null,
            ],
            'members' => $dynasty->members->map(fn (DynastyMember $member) => [
                'id' => $member->id,
                'first_name' => $member->first_name,
                'full_name' => $member->full_name,
                'gender' => $member->gender,
                'generation' => $member->generation,
                'birth_order' => $member->birth_order,
                'status' => $member->status,
                'is_heir' => $member->is_heir,
                'is_legitimate' => $member->is_legitimate,
                'is_disinherited' => $member->is_disinherited,
                'birth_date' => $member->birth_date?->toDateString(),
                'death_date' => $member->death_date?->toDateString(),
                'death_cause' => $member->death_cause,
                'user' => $member->user ? [
                    'id' => $member->user->id,
                    'username' => $member->user->username,
                ] : null,
            ]),
            'events' => $dynasty->events->map(fn ($event) => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'title' => $event->title,
                'description' => $event->description,
                'prestige_change' => $event->prestige_change,
                'occurred_at' => $event->occurred_at?->toISOString(),
            ]),
            'successionRules' => $dynasty->successionRules ? [
                'id' => $dynasty->successionRules->id,
                'succession_type' => $dynasty->successionRules->succession_type,
                'gender_preference' => $dynasty->successionRules->gender_preference,
                'legitimacy_required' => $dynasty->successionRules->legitimacy_required,
            ] : null,
        ]);
    }

    /**
     * Show the form for editing the specified dynasty.
     */
    public function edit(Dynasty $dynasty): Response
    {
        $dynasty->load(['currentHead', 'members' => fn ($q) => $q->alive()->with('user')]);

        return Inertia::render('Admin/Dynasties/Edit', [
            'dynasty' => [
                'id' => $dynasty->id,
                'name' => $dynasty->name,
                'motto' => $dynasty->motto,
                'coat_of_arms' => $dynasty->coat_of_arms,
                'prestige' => $dynasty->prestige,
                'current_head_id' => $dynasty->current_head_id,
            ],
            'members' => $dynasty->members->map(fn (DynastyMember $member) => [
                'id' => $member->id,
                'first_name' => $member->first_name,
                'full_name' => $member->full_name,
                'user' => $member->user ? [
                    'id' => $member->user->id,
                    'username' => $member->user->username,
                ] : null,
            ]),
        ]);
    }

    /**
     * Update the specified dynasty.
     */
    public function update(Request $request, Dynasty $dynasty): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'motto' => ['nullable', 'string', 'max:255'],
            'coat_of_arms' => ['nullable', 'string', 'max:500'],
            'prestige' => ['required', 'integer', 'min:0'],
            'current_head_id' => ['nullable', 'exists:users,id'],
        ]);

        $dynasty->update($validated);

        return back()->with('success', 'Dynasty updated successfully.');
    }
}

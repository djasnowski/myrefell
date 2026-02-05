<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Religion;
use App\Models\ReligionMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReligionController extends Controller
{
    /**
     * Display a listing of religions.
     */
    public function index(Request $request): Response
    {
        $query = Religion::query()
            ->with(['founder', 'treasury'])
            ->withCount('members');

        // Search by name
        if ($search = $request->input('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        // Filter by type (cult/religion)
        if ($type = $request->input('type')) {
            if ($type === 'cult') {
                $query->where('type', Religion::TYPE_CULT);
            } elseif ($type === 'religion') {
                $query->where('type', Religion::TYPE_RELIGION);
            }
        }

        // Filter by active status
        if ($request->input('active') === 'true') {
            $query->where('is_active', true);
        } elseif ($request->input('active') === 'false') {
            $query->where('is_active', false);
        }

        $religions = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Religion $religion) => [
                'id' => $religion->id,
                'name' => $religion->name,
                'type' => $religion->type,
                'icon' => $religion->icon,
                'color' => $religion->color,
                'is_public' => $religion->is_public,
                'is_active' => $religion->is_active,
                'members_count' => $religion->members_count,
                'member_limit' => $religion->member_limit,
                'hideout_tier' => $religion->hideout_tier,
                'treasury_balance' => $religion->treasury?->balance ?? 0,
                'founder' => $religion->founder ? [
                    'id' => $religion->founder->id,
                    'username' => $religion->founder->username,
                ] : null,
                'created_at' => $religion->created_at->toISOString(),
            ]);

        return Inertia::render('Admin/Religions/Index', [
            'religions' => $religions,
            'filters' => [
                'search' => $request->input('search', ''),
                'type' => $request->input('type', ''),
                'active' => $request->input('active', ''),
            ],
        ]);
    }

    /**
     * Display the specified religion.
     */
    public function show(Religion $religion): Response
    {
        $religion->load([
            'founder',
            'treasury',
            'beliefs',
            'members' => fn ($q) => $q->with('user')->orderByRaw("
                CASE rank
                    WHEN 'prophet' THEN 1
                    WHEN 'archbishop' THEN 2
                    WHEN 'apostle' THEN 2
                    WHEN 'priest' THEN 3
                    WHEN 'acolyte' THEN 3
                    WHEN 'deacon' THEN 4
                    WHEN 'disciple' THEN 4
                    WHEN 'follower' THEN 5
                    ELSE 6
                END
            ")->orderBy('devotion', 'desc'),
        ]);

        return Inertia::render('Admin/Religions/Show', [
            'religion' => [
                'id' => $religion->id,
                'name' => $religion->name,
                'description' => $religion->description,
                'type' => $religion->type,
                'icon' => $religion->icon,
                'color' => $religion->color,
                'is_public' => $religion->is_public,
                'is_active' => $religion->is_active,
                'member_limit' => $religion->member_limit,
                'belief_limit' => $religion->belief_limit,
                'founding_cost' => $religion->founding_cost,
                'hideout_tier' => $religion->hideout_tier,
                'hideout_name' => $religion->getHideoutName(),
                'hideout_location_type' => $religion->hideout_location_type,
                'hideout_location_name' => $religion->hideout_location_name,
                'created_at' => $religion->created_at->toISOString(),
                'founder' => $religion->founder ? [
                    'id' => $religion->founder->id,
                    'username' => $religion->founder->username,
                ] : null,
            ],
            'treasury' => $religion->treasury ? [
                'id' => $religion->treasury->id,
                'balance' => $religion->treasury->balance,
            ] : null,
            'beliefs' => $religion->beliefs->map(fn ($belief) => [
                'id' => $belief->id,
                'name' => $belief->name,
                'description' => $belief->description,
                'effects' => $belief->effects,
            ]),
            'members' => $religion->members->map(fn (ReligionMember $member) => [
                'id' => $member->id,
                'rank' => $member->rank,
                'rank_display' => $member->rank_display,
                'devotion' => $member->devotion,
                'joined_at' => $member->joined_at?->toISOString(),
                'user' => $member->user ? [
                    'id' => $member->user->id,
                    'username' => $member->user->username,
                ] : null,
            ]),
        ]);
    }

    /**
     * Show the form for editing the specified religion.
     */
    public function edit(Religion $religion): Response
    {
        return Inertia::render('Admin/Religions/Edit', [
            'religion' => [
                'id' => $religion->id,
                'name' => $religion->name,
                'description' => $religion->description,
                'type' => $religion->type,
                'icon' => $religion->icon,
                'color' => $religion->color,
                'is_public' => $religion->is_public,
                'is_active' => $religion->is_active,
                'member_limit' => $religion->member_limit,
            ],
        ]);
    }

    /**
     * Update the specified religion.
     */
    public function update(Request $request, Religion $religion): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_public' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'member_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        $religion->update($validated);

        return back()->with('success', 'Religion updated successfully.');
    }

}

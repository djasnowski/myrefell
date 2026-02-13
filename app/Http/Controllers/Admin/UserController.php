<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BanUserRequest;
use App\Http\Requests\Admin\SetUserPasswordRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\LocationActivityLog;
use App\Models\PlayerRole;
use App\Models\PlayerSkill;
use App\Models\ReligionMember;
use App\Models\TabActivityLog;
use App\Models\User;
use App\Models\UserBan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        $query = User::query()
            ->withCount('bans')
            ->with('latestBan');

        // Search by username or email (case-insensitive)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        // Filter by banned status
        if ($request->input('banned') === 'true') {
            $query->whereNotNull('banned_at');
        } elseif ($request->input('banned') === 'false') {
            $query->whereNull('banned_at');
        }

        // Filter by admin status
        if ($request->input('admin') === 'true') {
            $query->where('is_admin', true);
        } elseif ($request->input('admin') === 'false') {
            $query->where('is_admin', false);
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($user) => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'is_banned' => $user->isBanned(),
                'banned_at' => $user->banned_at?->toISOString(),
                'bans_count' => $user->bans_count,
                'created_at' => $user->created_at->toISOString(),
                'email_verified_at' => $user->email_verified_at?->toISOString(),
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $request->input('search', ''),
                'banned' => $request->input('banned', ''),
                'admin' => $request->input('admin', ''),
            ],
        ]);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): Response
    {
        $user->load([
            'bans' => fn ($q) => $q->with(['bannedByUser', 'unbannedByUser'])->orderBy('banned_at', 'desc'),
            'homeVillage',
            'skills',
            'inventory.item',
            'titles',
            'playerRoles.role',
            'employment',
            'horse.horse',
            'diseaseInfections.disease',
            'bankAccounts',
            'quests.quest',
        ]);

        // Get religion membership separately since it's not a direct relation
        $religionMember = ReligionMember::with('religion')
            ->where('user_id', $user->id)
            ->first();

        // Get dynasty member info
        $dynastyMember = $user->dynasty_member_id
            ? \App\Models\DynastyMember::with('dynasty')->find($user->dynasty_member_id)
            : null;

        // Get recent activity (location-based)
        $activities = LocationActivityLog::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        // Get recent tab activity (for detailed debugging with browser info)
        $tabActivities = TabActivityLog::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'is_banned' => $user->isBanned(),
                'banned_at' => $user->banned_at?->toISOString(),
                'created_at' => $user->created_at->toISOString(),
                'email_verified_at' => $user->email_verified_at?->toISOString(),
                'registration_ip' => $user->registration_ip,
                'last_login_ip' => $user->last_login_ip,
                'last_login_at' => $user->last_login_at?->toISOString(),
                'gender' => $user->gender,
                'social_class' => $user->social_class,
                'gold' => $user->gold,
                'hp' => $user->hp,
                'max_hp' => $user->max_hp,
                'energy' => $user->energy,
                'max_energy' => $user->max_energy,
                'primary_title' => $user->primary_title,
                'title_tier' => $user->title_tier,
                'combat_level' => $user->combat_level,
                'home_village' => $user->homeVillage ? [
                    'id' => $user->homeVillage->id,
                    'name' => $user->homeVillage->name,
                ] : null,
                'bans' => $user->bans->map(fn ($ban) => [
                    'id' => $ban->id,
                    'reason' => $ban->reason,
                    'banned_at' => $ban->banned_at->toISOString(),
                    'banned_by' => $ban->bannedByUser ? [
                        'id' => $ban->bannedByUser->id,
                        'username' => $ban->bannedByUser->username,
                    ] : null,
                    'unbanned_at' => $ban->unbanned_at?->toISOString(),
                    'unbanned_by' => $ban->unbannedByUser ? [
                        'id' => $ban->unbannedByUser->id,
                        'username' => $ban->unbannedByUser->username,
                    ] : null,
                    'unban_reason' => $ban->unban_reason,
                    'is_active' => $ban->isActive(),
                    'appeal_text' => $ban->appeal_text,
                    'appeal_submitted_at' => $ban->appeal_submitted_at?->toISOString(),
                ]),
            ],
            'skills' => $this->formatSkills($user),
            'inventory' => $user->inventory->map(fn ($inv) => [
                'id' => $inv->id,
                'slot_number' => $inv->slot_number,
                'quantity' => $inv->quantity,
                'is_equipped' => $inv->is_equipped,
                'item' => $inv->item ? [
                    'id' => $inv->item->id,
                    'name' => $inv->item->name,
                    'type' => $inv->item->type,
                    'rarity' => $inv->item->rarity,
                    'equipment_slot' => $inv->item->equipment_slot,
                    'atk_bonus' => $inv->item->atk_bonus,
                    'str_bonus' => $inv->item->str_bonus,
                    'def_bonus' => $inv->item->def_bonus,
                ] : null,
            ]),
            'titles' => $user->titles->map(fn ($title) => [
                'id' => $title->id,
                'title' => $title->title,
                'tier' => $title->tier,
                'is_active' => $title->is_active,
                'domain_type' => $title->domain_type,
                'legitimacy' => $title->legitimacy,
                'granted_at' => $title->granted_at?->toISOString(),
                'revoked_at' => $title->revoked_at?->toISOString(),
            ]),
            'roles' => $user->playerRoles->map(fn ($pr) => [
                'id' => $pr->id,
                'role_name' => $pr->role?->name,
                'location_type' => $pr->location_type,
                'location_name' => $pr->location_name,
                'status' => $pr->status,
                'legitimacy' => $pr->legitimacy,
                'appointed_at' => $pr->appointed_at?->toISOString(),
            ]),
            'dynasty' => $dynastyMember ? [
                'id' => $dynastyMember->id,
                'dynasty_name' => $dynastyMember->dynasty?->name,
                'dynasty_id' => $dynastyMember->dynasty_id,
                'first_name' => $dynastyMember->first_name,
                'generation' => $dynastyMember->generation,
                'is_heir' => $dynastyMember->is_heir,
                'is_legitimate' => $dynastyMember->is_legitimate,
                'status' => $dynastyMember->status,
            ] : null,
            'religion' => $religionMember ? [
                'id' => $religionMember->id,
                'religion_name' => $religionMember->religion?->name,
                'religion_id' => $religionMember->religion_id,
                'rank' => $religionMember->rank,
                'devotion' => $religionMember->devotion,
                'joined_at' => $religionMember->joined_at?->toISOString(),
            ] : null,
            'employment' => $user->employment->map(fn ($emp) => [
                'id' => $emp->id,
                'employer_type' => $emp->employer_type,
                'status' => $emp->status,
                'hired_at' => $emp->hired_at?->toISOString(),
                'total_earnings' => $emp->total_earnings,
            ]),
            'horse' => $user->horse ? [
                'id' => $user->horse->id,
                'name' => $user->horse->name,
                'horse_type' => $user->horse->horse?->name,
                'health' => $user->horse->health,
                'stamina' => $user->horse->stamina,
                'max_stamina' => $user->horse->max_stamina,
                'is_stabled' => $user->horse->is_stabled,
            ] : null,
            'diseases' => $user->diseaseInfections->map(fn ($infection) => [
                'id' => $infection->id,
                'disease_name' => $infection->disease?->name,
                'severity' => $infection->severity,
                'infected_at' => $infection->infected_at?->toISOString(),
                'cured_at' => $infection->cured_at?->toISOString(),
            ]),
            'activities' => $activities->map(fn ($log) => [
                'id' => $log->id,
                'activity_type' => $log->activity_type,
                'activity_subtype' => $log->activity_subtype,
                'description' => $log->description,
                'location_type' => $log->location_type,
                'created_at' => $log->created_at->toISOString(),
            ]),
            'tabActivities' => $tabActivities->map(fn ($log, $index) => [
                'id' => $log->id,
                'route' => $log->route,
                'method' => $log->method,
                'tab_id' => $log->tab_id,
                'is_new_tab' => $log->is_new_tab,
                'user_agent' => $log->user_agent,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->toISOString(),
                'created_at_formatted' => $log->created_at->format('Y-m-d H:i:s'),
            ]),
            'bankAccounts' => $user->bankAccounts->map(fn ($acc) => [
                'id' => $acc->id,
                'balance' => $acc->balance,
                'account_type' => $acc->account_type,
            ]),
            'quests' => $user->quests->map(fn ($pq) => [
                'id' => $pq->id,
                'quest_name' => $pq->quest?->name,
                'status' => $pq->status,
                'started_at' => $pq->started_at?->toISOString(),
                'completed_at' => $pq->completed_at?->toISOString(),
            ]),
            'suspiciousActivity' => [
                'flagged_at' => $user->suspicious_activity_flagged_at?->toISOString(),
                'stats_24h' => TabActivityLog::getSuspiciousActivity($user->id, now()->subDay()->toDateTimeString()),
                'stats_1h' => TabActivityLog::getSuspiciousActivity($user->id, now()->subHour()->toDateTimeString()),
            ],
        ]);
    }

    /**
     * Format skills data with all skills for display.
     *
     * @return array<int, array{
     *     skill_name: string,
     *     level: int,
     *     xp: int,
     *     progress: float,
     *     is_combat: bool
     * }>
     */
    private function formatSkills(User $user): array
    {
        $skills = $user->skills->keyBy('skill_name');
        $allSkills = [];

        foreach (PlayerSkill::SKILLS as $skillName) {
            $skill = $skills->get($skillName);
            $isCombat = in_array($skillName, PlayerSkill::COMBAT_SKILLS);
            $defaultLevel = $isCombat ? 5 : 1;

            $allSkills[] = [
                'skill_name' => $skillName,
                'level' => $skill?->level ?? $defaultLevel,
                'xp' => $skill?->xp ?? 0,
                'progress' => $skill?->progress ?? 0.0,
                'is_combat' => $isCombat,
            ];
        }

        return $allSkills;
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
            ],
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return back()->with('success', 'User updated successfully.');
    }

    /**
     * Set the password for the specified user.
     */
    public function setPassword(SetUserPasswordRequest $request, User $user): RedirectResponse
    {
        $user->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return back()->with('success', "Password updated for {$user->username}.");
    }

    /**
     * Ban the specified user.
     */
    public function ban(BanUserRequest $request, User $user): RedirectResponse
    {
        $admin = $request->user();

        // Prevent banning self
        if ($user->id === $admin->id) {
            return back()->with('error', 'You cannot ban yourself.');
        }

        // Prevent banning other admins
        if ($user->is_admin) {
            return back()->with('error', 'You cannot ban other administrators.');
        }

        // Already banned check
        if ($user->isBanned()) {
            return back()->with('error', 'This user is already banned.');
        }

        // Create ban record
        UserBan::create([
            'user_id' => $user->id,
            'banned_by' => $admin->id,
            'reason' => $request->validated('reason'),
            'banned_at' => now(),
        ]);

        // Update user's banned_at
        $user->update(['banned_at' => now()]);

        // Remove all roles from banned user
        PlayerRole::where('user_id', $user->id)->delete();

        return back()->with('success', "User {$user->username} has been banned.");
    }

    /**
     * Unban the specified user.
     */
    public function unban(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $admin = $request->user();

        // Not banned check
        if (! $user->isBanned()) {
            return back()->with('error', 'This user is not banned.');
        }

        // Update the latest active ban record
        $latestBan = $user->bans()->whereNull('unbanned_at')->latest('banned_at')->first();
        if ($latestBan) {
            $latestBan->update([
                'unbanned_at' => now(),
                'unbanned_by' => $admin->id,
                'unban_reason' => $request->input('reason'),
            ]);
        }

        // Clear user's banned_at
        $user->update(['banned_at' => null]);

        return back()->with('success', "User {$user->username} has been unbanned.");
    }
}

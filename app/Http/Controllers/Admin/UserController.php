<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BanUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Models\UserBan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        // Search by username or email
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
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
        ]);

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
                ]),
            ],
        ]);
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

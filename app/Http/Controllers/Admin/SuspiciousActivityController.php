<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TabActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SuspiciousActivityController extends Controller
{
    /**
     * Display list of flagged users.
     */
    public function index(): Response
    {
        $flaggedUsers = User::whereNotNull('suspicious_activity_flagged_at')
            ->orderBy('suspicious_activity_flagged_at', 'desc')
            ->get()
            ->map(function ($user) {
                $stats = TabActivityLog::getSuspiciousActivity($user->id, now()->subDay()->toDateTimeString());

                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'flagged_at' => $user->suspicious_activity_flagged_at->toISOString(),
                    'is_banned' => $user->isBanned(),
                    'stats' => $stats,
                ];
            });

        return Inertia::render('Admin/SuspiciousActivity/Index', [
            'flaggedUsers' => $flaggedUsers,
        ]);
    }

    /**
     * Show tab activity details for a user.
     */
    public function show(User $user): Response
    {
        $stats24h = TabActivityLog::getSuspiciousActivity($user->id, now()->subDay()->toDateTimeString());
        $stats1h = TabActivityLog::getSuspiciousActivity($user->id, now()->subHour()->toDateTimeString());
        $statsAllTime = TabActivityLog::getSuspiciousActivity($user->id);

        $recentActivity = TabActivityLog::getRecentActivity($user->id, 200)
            ->map(fn ($log) => [
                'id' => $log->id,
                'tab_id' => $log->tab_id,
                'route' => $log->route,
                'method' => $log->method,
                'is_new_tab' => $log->is_new_tab,
                'previous_tab_id' => $log->previous_tab_id,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->toISOString(),
            ]);

        return Inertia::render('Admin/SuspiciousActivity/Show', [
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'is_banned' => $user->isBanned(),
                'flagged_at' => $user->suspicious_activity_flagged_at?->toISOString(),
            ],
            'stats' => [
                'last_hour' => $stats1h,
                'last_24h' => $stats24h,
                'all_time' => $statsAllTime,
            ],
            'recentActivity' => $recentActivity,
        ]);
    }

    /**
     * Clear the suspicious activity flag for a user.
     */
    public function clearFlag(User $user): RedirectResponse
    {
        $user->suspicious_activity_flagged_at = null;
        $user->save();

        return back()->with('success', "Suspicious activity flag cleared for {$user->username}.");
    }
}

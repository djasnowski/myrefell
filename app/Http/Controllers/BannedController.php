<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BannedController extends Controller
{
    /**
     * Display the banned user page with ban details and appeal form.
     */
    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        // If user is no longer banned, redirect to dashboard
        if (! $user->isBanned()) {
            return redirect()->route('dashboard');
        }

        // Get the most recent active ban
        $ban = $user->bans()
            ->whereNull('unbanned_at')
            ->with('bannedByUser:id,username')
            ->latest('banned_at')
            ->first();

        return Inertia::render('Banned', [
            'ban' => $ban ? [
                'reason' => $ban->reason,
                'banned_at' => $ban->banned_at->toISOString(),
                'banned_by' => $ban->bannedByUser?->username ?? 'System',
            ] : [
                'reason' => 'Your account has been suspended.',
                'banned_at' => $user->banned_at?->toISOString(),
                'banned_by' => 'System',
            ],
            'username' => $user->username,
        ]);
    }

    /**
     * Submit a ban appeal.
     */
    public function appeal(Request $request)
    {
        $request->validate([
            'appeal' => 'required|string|min:20|max:2000',
        ]);

        $user = $request->user();

        // Get the most recent active ban
        $ban = $user->bans()
            ->whereNull('unbanned_at')
            ->latest('banned_at')
            ->first();

        if ($ban) {
            // Store the appeal - for now just update the ban record
            // You could create a separate appeals table if needed
            $ban->update([
                'appeal_text' => $request->input('appeal'),
                'appeal_submitted_at' => now(),
            ]);
        }

        return back()->with('success', 'Your appeal has been submitted and will be reviewed by our team.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserBan;
use Inertia\Inertia;
use Inertia\Response;

class AppealController extends Controller
{
    /**
     * Display a listing of ban appeals.
     */
    public function index(): Response
    {
        $appeals = UserBan::query()
            ->whereNotNull('appeal_text')
            ->with(['user:id,username', 'bannedByUser:id,username'])
            ->orderByDesc('appeal_submitted_at')
            ->get()
            ->map(fn ($ban) => [
                'id' => $ban->id,
                'user_id' => $ban->user_id,
                'username' => $ban->user?->username ?? 'Unknown',
                'reason' => $ban->reason,
                'appeal_text' => $ban->appeal_text,
                'appeal_submitted_at' => $ban->appeal_submitted_at?->toISOString(),
                'banned_at' => $ban->banned_at->toISOString(),
                'banned_by' => $ban->bannedByUser?->username ?? 'Unknown',
                'is_active' => $ban->isActive(),
                'unbanned_at' => $ban->unbanned_at?->toISOString(),
            ]);

        return Inertia::render('Admin/Appeals/Index', [
            'appeals' => $appeals,
        ]);
    }
}

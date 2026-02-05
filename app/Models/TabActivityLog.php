<?php

namespace App\Models;

use App\Mail\SuspiciousActivityDetected;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Mail;

class TabActivityLog extends Model
{
    public $timestamps = false;

    /**
     * Minimum seconds between XP actions before flagging as suspicious.
     * Actions faster than this indicate multi-tabbing or botting.
     */
    public const MIN_XP_ACTION_INTERVAL = 3;

    /**
     * Admin email to notify when suspicious activity is detected.
     */
    public const ADMIN_EMAIL = 'd.jasnowski@gmail.com';

    /**
     * Route patterns that grant XP (cheating if multi-tabbed).
     */
    public const XP_ROUTES = [
        'gathering/gather',
        'training/train',
        'thieving/attempt',
        'crafting/craft',
        'agility/train',
        'apothecary/brew',
        'combat/attack',
        'anvil/smith',
        'forge/',
        'fishing/',
    ];

    /**
     * Check if a route grants XP.
     */
    public static function isXpRoute(?string $route): bool
    {
        if (! $route) {
            return false;
        }

        foreach (self::XP_ROUTES as $pattern) {
            if (str_contains($route, $pattern)) {
                return true;
            }
        }

        return false;
    }

    protected $fillable = [
        'user_id',
        'tab_id',
        'ip_address',
        'user_agent',
        'route',
        'method',
        'is_new_tab',
        'previous_tab_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_new_tab' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a tab activity, detecting if this is a suspicious new tab.
     */
    public static function logActivity(
        int $userId,
        string $tabId,
        ?string $ipAddress,
        ?string $userAgent,
        ?string $route,
        ?string $method
    ): self {
        // Check for recent activity from a different tab (within 5 seconds)
        $recentActivity = self::where('user_id', $userId)
            ->where('tab_id', '!=', $tabId)
            ->where('created_at', '>=', now()->subSeconds(5))
            ->orderBy('created_at', 'desc')
            ->first();

        $isNewTab = $recentActivity !== null;
        $previousTabId = $recentActivity?->tab_id;

        $log = self::create([
            'user_id' => $userId,
            'tab_id' => $tabId,
            'ip_address' => $ipAddress,
            'user_agent' => substr($userAgent ?? '', 0, 500),
            'route' => $route,
            'method' => $method,
            'is_new_tab' => $isNewTab,
            'previous_tab_id' => $previousTabId,
            'created_at' => now(),
        ]);

        // Check if we should flag the user - XP actions too fast (within 3s)
        if (self::isXpRoute($route)) {
            self::checkAndFlagUser($userId, $route);
        }

        return $log;
    }

    /**
     * Check if user should be flagged for suspicious activity.
     */
    protected static function checkAndFlagUser(int $userId, ?string $currentRoute = null): void
    {
        // Check for XP action within the minimum interval (too fast = cheating)
        $recentXpAction = self::where('user_id', $userId)
            ->where('created_at', '>=', now()->subSeconds(self::MIN_XP_ACTION_INTERVAL))
            ->where('created_at', '<', now()) // Exclude the current action
            ->orderBy('created_at', 'desc')
            ->first();

        // If there's a recent XP action, check if it was also an XP route
        if (! $recentXpAction || ! self::isXpRoute($recentXpAction->route)) {
            return;
        }

        // XP action within 3s of another XP action - suspicious!
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        // Already flagged in the last 24 hours? Don't re-flag (but still log)
        if ($user->suspicious_activity_flagged_at && $user->suspicious_activity_flagged_at->gt(now()->subDay())) {
            return;
        }

        // Flag the user
        $user->suspicious_activity_flagged_at = now();
        $user->save();

        // Get 24-hour stats for the email
        $dayStats = self::getSuspiciousActivity($userId, now()->subDay()->toDateTimeString());

        // Send email notification
        try {
            Mail::to(self::ADMIN_EMAIL)->send(new SuspiciousActivityDetected($user, $dayStats));
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * Get suspicious activity summary for a user.
     */
    public static function getSuspiciousActivity(int $userId, ?string $since = null): array
    {
        $query = self::where('user_id', $userId);

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        $total = $query->count();
        $tabSwitchLogs = (clone $query)->where('is_new_tab', true)->get();
        $newTabCount = $tabSwitchLogs->count();
        $uniqueTabs = (clone $query)->distinct('tab_id')->count('tab_id');

        // Count rapid XP actions (within 3s of each other)
        $xpLogs = (clone $query)->get()->filter(fn ($log) => self::isXpRoute($log->route))->sortBy('created_at')->values();
        $rapidXpActions = 0;
        for ($i = 1; $i < $xpLogs->count(); $i++) {
            $timeDiff = $xpLogs[$i]->created_at->diffInSeconds($xpLogs[$i - 1]->created_at);
            if ($timeDiff <= self::MIN_XP_ACTION_INTERVAL) {
                $rapidXpActions++;
            }
        }

        // Calculate requests per hour
        $requestsLastHour = self::where('user_id', $userId)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        return [
            'total_requests' => $total,
            'new_tab_switches' => $newTabCount,
            'rapid_xp_actions' => $rapidXpActions,
            'unique_tabs' => $uniqueTabs,
            'suspicious_percentage' => $total > 0 ? round(($rapidXpActions / max(1, $xpLogs->count())) * 100, 2) : 0,
            'requests_per_hour' => $requestsLastHour,
        ];
    }

    /**
     * Get recent tab activity for a user (for admin display).
     */
    public static function getRecentActivity(int $userId, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}

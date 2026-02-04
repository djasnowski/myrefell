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
     * Number of XP-granting tab switches required to trigger a flag.
     * Multi-tab XP farming is cheating - being in two places at once.
     */
    public const TAB_SWITCHES_TO_FLAG = 1;

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

        // Check if we should flag the user - only for XP-granting activities
        if ($isNewTab && self::isXpRoute($route)) {
            self::checkAndFlagUser($userId, $route);
        }

        return $log;
    }

    /**
     * Check if user should be flagged for suspicious activity.
     */
    protected static function checkAndFlagUser(int $userId, ?string $currentRoute = null): void
    {
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        // Already flagged in the last 24 hours? Don't re-flag (but still log)
        if ($user->suspicious_activity_flagged_at && $user->suspicious_activity_flagged_at->gt(now()->subDay())) {
            return;
        }

        // Count XP-granting tab switches in last hour
        $xpTabSwitches = self::where('user_id', $userId)
            ->where('is_new_tab', true)
            ->where('created_at', '>=', now()->subHour())
            ->get()
            ->filter(fn ($log) => self::isXpRoute($log->route))
            ->count();

        // Flag immediately if any XP-granting tab switching detected
        if ($xpTabSwitches >= self::TAB_SWITCHES_TO_FLAG) {
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

        // Separate XP vs non-XP tab switches
        $xpTabSwitches = $tabSwitchLogs->filter(fn ($log) => self::isXpRoute($log->route))->count();
        $nonXpTabSwitches = $newTabCount - $xpTabSwitches;

        // Calculate requests per hour
        $requestsLastHour = self::where('user_id', $userId)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        return [
            'total_requests' => $total,
            'new_tab_switches' => $newTabCount,
            'xp_tab_switches' => $xpTabSwitches,
            'non_xp_tab_switches' => $nonXpTabSwitches,
            'unique_tabs' => $uniqueTabs,
            'suspicious_percentage' => $total > 0 ? round(($newTabCount / $total) * 100, 2) : 0,
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

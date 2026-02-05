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
     * Maximum XP actions allowed per second from the same tab.
     * More than this indicates botting (humans can't click faster than ~2-3/sec).
     */
    public const MAX_SAME_TAB_ACTIONS_PER_SECOND = 2;

    /**
     * Number of rapid same-tab violations needed to trigger a flag.
     * This prevents false positives from occasional lag/burst.
     */
    public const SAME_TAB_VIOLATION_THRESHOLD = 5;

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
     * Flags for: 1) XP actions across different tabs within interval, or
     *            2) Too many XP actions per second from same tab (botting)
     */
    protected static function checkAndFlagUser(int $userId, ?string $currentRoute = null): void
    {
        // Get the current tab from the most recent log entry
        $currentLog = self::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $currentLog) {
            return;
        }

        $shouldFlag = false;
        $flagReason = '';

        // CHECK 1: XP action from DIFFERENT tab within interval (multi-tabbing)
        $recentXpAction = self::where('user_id', $userId)
            ->where('tab_id', '!=', $currentLog->tab_id)
            ->where('created_at', '>=', now()->subSeconds(self::MIN_XP_ACTION_INTERVAL))
            ->where('created_at', '<', now())
            ->orderBy('created_at', 'desc')
            ->first();

        if ($recentXpAction && self::isXpRoute($recentXpAction->route)) {
            $shouldFlag = true;
            $flagReason = 'multi-tabbing';
        }

        // CHECK 2: Too many XP actions per second from SAME tab (botting)
        if (! $shouldFlag) {
            // Count XP actions in the same second from the same tab
            $actionsThisSecond = self::where('user_id', $userId)
                ->where('tab_id', $currentLog->tab_id)
                ->where('created_at', '>=', now()->startOfSecond())
                ->where('created_at', '<=', now())
                ->whereRaw("route LIKE ANY (ARRAY['%gathering/gather%', '%training/train%', '%thieving/attempt%', '%crafting/craft%', '%agility/train%', '%apothecary/brew%', '%combat/attack%', '%anvil/smith%', '%forge/%', '%fishing/%'])")
                ->count();

            if ($actionsThisSecond > self::MAX_SAME_TAB_ACTIONS_PER_SECOND) {
                // Check if this is a pattern (not just a one-time burst)
                $recentViolations = self::where('user_id', $userId)
                    ->where('tab_id', $currentLog->tab_id)
                    ->where('created_at', '>=', now()->subMinute())
                    ->selectRaw("DATE_TRUNC('second', created_at) as second, COUNT(*) as cnt")
                    ->groupBy('second')
                    ->havingRaw('COUNT(*) > ?', [self::MAX_SAME_TAB_ACTIONS_PER_SECOND])
                    ->count();

                if ($recentViolations >= self::SAME_TAB_VIOLATION_THRESHOLD) {
                    $shouldFlag = true;
                    $flagReason = 'rapid-botting';
                }
            }
        }

        if (! $shouldFlag) {
            return;
        }

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
        $dayStats['flag_reason'] = $flagReason;

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

        // Get XP logs for analysis
        $xpLogs = (clone $query)->get()->filter(fn ($log) => self::isXpRoute($log->route))->sortBy('created_at')->values();

        // Count rapid XP actions across DIFFERENT tabs (within 3s of each other) - multi-tabbing
        $multiTabRapidActions = 0;
        for ($i = 1; $i < $xpLogs->count(); $i++) {
            $timeDiff = $xpLogs[$i]->created_at->diffInSeconds($xpLogs[$i - 1]->created_at);
            $differentTab = $xpLogs[$i]->tab_id !== $xpLogs[$i - 1]->tab_id;
            if ($timeDiff <= self::MIN_XP_ACTION_INTERVAL && $differentTab) {
                $multiTabRapidActions++;
            }
        }

        // Count seconds with too many same-tab XP actions (botting)
        $sameTabBotSeconds = (clone $query)
            ->whereRaw("route LIKE ANY (ARRAY['%gathering/gather%', '%training/train%', '%thieving/attempt%', '%crafting/craft%', '%agility/train%', '%apothecary/brew%', '%combat/attack%', '%anvil/smith%', '%forge/%', '%fishing/%'])")
            ->selectRaw("DATE_TRUNC('second', created_at) as second, tab_id, COUNT(*) as cnt")
            ->groupBy('second', 'tab_id')
            ->havingRaw('COUNT(*) > ?', [self::MAX_SAME_TAB_ACTIONS_PER_SECOND])
            ->get()
            ->count();

        // Count total seconds with XP activity (to calculate what % are bot seconds)
        $totalXpSeconds = (clone $query)
            ->whereRaw("route LIKE ANY (ARRAY['%gathering/gather%', '%training/train%', '%thieving/attempt%', '%crafting/craft%', '%agility/train%', '%apothecary/brew%', '%combat/attack%', '%anvil/smith%', '%forge/%', '%fishing/%'])")
            ->selectRaw("DATE_TRUNC('second', created_at) as second")
            ->groupBy('second')
            ->get()
            ->count();

        // Calculate requests per hour
        $requestsLastHour = self::where('user_id', $userId)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        // Max actions in any single second (peak botting indicator)
        $maxActionsPerSecond = (clone $query)
            ->selectRaw("DATE_TRUNC('second', created_at) as second, COUNT(*) as cnt")
            ->groupBy('second')
            ->orderByDesc('cnt')
            ->first();

        // Suspicious percentage: combine both bot types
        // - Same-tab botting: % of XP seconds with >2 actions
        // - Multi-tab botting: % of XP actions that were cross-tab rapid
        $sameTabPercent = $totalXpSeconds > 0
            ? ($sameTabBotSeconds / $totalXpSeconds) * 100
            : 0;
        $multiTabPercent = $xpLogs->count() > 0
            ? ($multiTabRapidActions / $xpLogs->count()) * 100
            : 0;
        // Take the higher of the two (they're different cheat types)
        $suspiciousPercentage = round(max($sameTabPercent, $multiTabPercent), 2);

        return [
            'total_requests' => $total,
            'new_tab_switches' => $newTabCount,
            'rapid_xp_actions' => $multiTabRapidActions,
            'same_tab_bot_seconds' => $sameTabBotSeconds,
            'max_actions_per_second' => $maxActionsPerSecond?->cnt ?? 0,
            'unique_tabs' => $uniqueTabs,
            'suspicious_percentage' => min($suspiciousPercentage, 100),
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

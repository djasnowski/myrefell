<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TabActivityLog extends Model
{
    public $timestamps = false;

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

        return self::create([
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
        $newTabCount = (clone $query)->where('is_new_tab', true)->count();
        $uniqueTabs = (clone $query)->distinct('tab_id')->count('tab_id');

        return [
            'total_requests' => $total,
            'new_tab_switches' => $newTabCount,
            'unique_tabs' => $uniqueTabs,
            'suspicious_percentage' => $total > 0 ? round(($newTabCount / $total) * 100, 2) : 0,
        ];
    }
}

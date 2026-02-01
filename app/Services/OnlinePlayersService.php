<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class OnlinePlayersService
{
    private const REDIS_KEY = 'online_players';

    private const ONLINE_THRESHOLD_SECONDS = 300; // 5 minutes

    /**
     * Mark a user as online.
     */
    public function markOnline(int $userId): void
    {
        try {
            Redis::zadd(self::REDIS_KEY, now()->timestamp, $userId);
            $this->cleanupOldEntries();
        } catch (\Throwable $e) {
            // Redis may not be available, fail silently
        }
    }

    /**
     * Get count of online players.
     */
    public function getOnlineCount(): int
    {
        try {
            $threshold = now()->subSeconds(self::ONLINE_THRESHOLD_SECONDS)->timestamp;

            return Redis::zcount(self::REDIS_KEY, $threshold, '+inf');
        } catch (\Throwable $e) {
            // Redis may not be available
            return 0;
        }
    }

    /**
     * Remove entries older than threshold.
     */
    private function cleanupOldEntries(): void
    {
        $threshold = now()->subSeconds(self::ONLINE_THRESHOLD_SECONDS)->timestamp;
        Redis::zremrangebyscore(self::REDIS_KEY, '-inf', $threshold);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinigamePlay extends Model
{
    use HasFactory;

    /**
     * Reward type constants.
     */
    public const REWARD_COMMON = 'common';

    public const REWARD_UNCOMMON = 'uncommon';

    public const REWARD_RARE = 'rare';

    public const REWARD_EPIC = 'epic';

    public const REWARD_MYSTERY = 'mystery';

    /**
     * Maximum streak days for bonus rewards.
     */
    public const MAX_STREAK = 5;

    /**
     * Reward chances based on streak day (percentages).
     * Day 1: Common 57%, Uncommon 25%, Rare 10%, Epic 5%, Mystery 3%
     * Day 5: Common 25%, Uncommon 25%, Rare 18%, Epic 22%, Mystery 10%
     *
     * @var array<int, array<string, int>>
     */
    public const STREAK_REWARD_CHANCES = [
        1 => [self::REWARD_COMMON => 57, self::REWARD_UNCOMMON => 25, self::REWARD_RARE => 10, self::REWARD_EPIC => 5, self::REWARD_MYSTERY => 3],
        2 => [self::REWARD_COMMON => 49, self::REWARD_UNCOMMON => 25, self::REWARD_RARE => 12, self::REWARD_EPIC => 9, self::REWARD_MYSTERY => 5],
        3 => [self::REWARD_COMMON => 40, self::REWARD_UNCOMMON => 25, self::REWARD_RARE => 14, self::REWARD_EPIC => 14, self::REWARD_MYSTERY => 7],
        4 => [self::REWARD_COMMON => 32, self::REWARD_UNCOMMON => 25, self::REWARD_RARE => 16, self::REWARD_EPIC => 18, self::REWARD_MYSTERY => 9],
        5 => [self::REWARD_COMMON => 25, self::REWARD_UNCOMMON => 25, self::REWARD_RARE => 18, self::REWARD_EPIC => 22, self::REWARD_MYSTERY => 10],
    ];

    protected $fillable = [
        'user_id',
        'played_at',
        'streak_count',
        'reward_type',
        'reward_value',
        'reward_item_id',
    ];

    protected function casts(): array
    {
        return [
            'played_at' => 'date',
            'streak_count' => 'integer',
            'reward_value' => 'integer',
        ];
    }

    /**
     * Get the user who made this play.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the item reward (if any).
     */
    public function rewardItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'reward_item_id');
    }

    /**
     * Get reward chances for a given streak day.
     *
     * @return array<string, int>
     */
    public static function getRewardChances(int $streakDay): array
    {
        $day = min($streakDay, self::MAX_STREAK);

        return self::STREAK_REWARD_CHANCES[$day] ?? self::STREAK_REWARD_CHANCES[1];
    }

    /**
     * Roll for a reward type based on streak day.
     */
    public static function rollRewardType(int $streakDay): string
    {
        $chances = self::getRewardChances($streakDay);
        $roll = random_int(1, 100);
        $cumulative = 0;

        foreach ($chances as $type => $chance) {
            $cumulative += $chance;
            if ($roll <= $cumulative) {
                return $type;
            }
        }

        return self::REWARD_COMMON;
    }

    /**
     * Check if a user has played today.
     */
    public static function hasPlayedToday(int $userId): bool
    {
        return self::where('user_id', $userId)
            ->where('played_at', today())
            ->exists();
    }

    /**
     * Get the user's current streak count.
     * If played today, returns today's streak. Otherwise checks yesterday.
     * Returns 0 if streak is broken.
     */
    public static function getCurrentStreak(int $userId): int
    {
        // First check if played today - show today's streak
        $todayPlay = self::where('user_id', $userId)
            ->where('played_at', today())
            ->first();

        if ($todayPlay) {
            return $todayPlay->streak_count;
        }

        // Otherwise check yesterday for continuing streak
        $yesterdayPlay = self::where('user_id', $userId)
            ->where('played_at', today()->subDay())
            ->first();

        if (! $yesterdayPlay) {
            return 0;
        }

        return min($yesterdayPlay->streak_count, self::MAX_STREAK - 1);
    }

    /**
     * Calculate what the next streak count would be for a user.
     */
    public static function getNextStreakCount(int $userId): int
    {
        $currentStreak = self::getCurrentStreak($userId);

        return min($currentStreak + 1, self::MAX_STREAK);
    }

    /**
     * Check if user can play today.
     */
    public static function canPlayToday(int $userId): bool
    {
        return ! self::hasPlayedToday($userId);
    }

    /**
     * Get recent plays for a user.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('played_at', '>=', today()->subDays($days));
    }

    /**
     * Check if this reward is common.
     */
    public function isCommon(): bool
    {
        return $this->reward_type === self::REWARD_COMMON;
    }

    /**
     * Check if this reward is uncommon.
     */
    public function isUncommon(): bool
    {
        return $this->reward_type === self::REWARD_UNCOMMON;
    }

    /**
     * Check if this reward is rare.
     */
    public function isRare(): bool
    {
        return $this->reward_type === self::REWARD_RARE;
    }

    /**
     * Check if this reward is epic.
     */
    public function isEpic(): bool
    {
        return $this->reward_type === self::REWARD_EPIC;
    }

    /**
     * Check if this reward is mystery box.
     */
    public function isMystery(): bool
    {
        return $this->reward_type === self::REWARD_MYSTERY;
    }
}

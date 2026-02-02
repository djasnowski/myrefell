<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinigameScore extends Model
{
    /** @use HasFactory<\Database\Factories\MinigameScoreFactory> */
    use HasFactory;

    /**
     * Location type constants.
     */
    public const LOCATION_VILLAGE = 'village';

    public const LOCATION_TOWN = 'town';

    public const LOCATION_BARONY = 'barony';

    public const LOCATION_DUCHY = 'duchy';

    public const LOCATION_KINGDOM = 'kingdom';

    public const LOCATION_TYPES = [
        self::LOCATION_VILLAGE,
        self::LOCATION_TOWN,
        self::LOCATION_BARONY,
        self::LOCATION_DUCHY,
        self::LOCATION_KINGDOM,
    ];

    /**
     * Daily-limited minigames (can only play once per day).
     */
    public const DAILY_LIMITED_GAMES = ['archery'];

    protected $fillable = [
        'user_id',
        'minigame',
        'score',
        'location_type',
        'location_id',
        'played_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'location_id' => 'integer',
            'played_at' => 'datetime',
        ];
    }

    /**
     * Get the user who played this game.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by minigame.
     */
    public function scopeForMinigame(Builder $query, string $minigame): Builder
    {
        return $query->where('minigame', $minigame);
    }

    /**
     * Scope to filter by location.
     */
    public function scopeForLocation(Builder $query, string $locationType, int $locationId): Builder
    {
        return $query->where('location_type', $locationType)
            ->where('location_id', $locationId);
    }

    /**
     * Scope for daily leaderboard (today's scores).
     */
    public function scopeDaily(Builder $query): Builder
    {
        return $query->whereDate('played_at', today());
    }

    /**
     * Scope for weekly leaderboard (this week's scores, Monday to Sunday).
     */
    public function scopeWeekly(Builder $query): Builder
    {
        return $query->whereBetween('played_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    /**
     * Scope for monthly leaderboard (this month's scores).
     */
    public function scopeMonthly(Builder $query): Builder
    {
        return $query->whereMonth('played_at', now()->month)
            ->whereYear('played_at', now()->year);
    }

    /**
     * Scope to get leaderboard with aggregated best scores per user.
     */
    public function scopeLeaderboard(Builder $query): Builder
    {
        return $query->selectRaw('user_id, MAX(score) as best_score')
            ->groupBy('user_id')
            ->orderByDesc('best_score');
    }

    /**
     * Get the daily leaderboard for a specific minigame and location.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public static function getDailyLeaderboard(string $minigame, string $locationType, int $locationId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()
            ->forMinigame($minigame)
            ->forLocation($locationType, $locationId)
            ->daily()
            ->leaderboard()
            ->limit($limit)
            ->get();
    }

    /**
     * Get the weekly leaderboard for a specific minigame and location.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public static function getWeeklyLeaderboard(string $minigame, string $locationType, int $locationId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()
            ->forMinigame($minigame)
            ->forLocation($locationType, $locationId)
            ->weekly()
            ->leaderboard()
            ->limit($limit)
            ->get();
    }

    /**
     * Get the monthly leaderboard for a specific minigame and location.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public static function getMonthlyLeaderboard(string $minigame, string $locationType, int $locationId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()
            ->forMinigame($minigame)
            ->forLocation($locationType, $locationId)
            ->monthly()
            ->leaderboard()
            ->limit($limit)
            ->get();
    }

    /**
     * Get a user's rank in the daily leaderboard.
     */
    public static function getUserDailyRank(int $userId, string $minigame, string $locationType, int $locationId): ?int
    {
        $userBestScore = static::query()
            ->where('user_id', $userId)
            ->forMinigame($minigame)
            ->forLocation($locationType, $locationId)
            ->daily()
            ->max('score');

        if ($userBestScore === null) {
            return null;
        }

        return static::query()
            ->forMinigame($minigame)
            ->forLocation($locationType, $locationId)
            ->daily()
            ->selectRaw('user_id, MAX(score) as best_score')
            ->groupBy('user_id')
            ->having('best_score', '>', $userBestScore)
            ->count() + 1;
    }

    /**
     * Check if a user has already played a daily-limited game today.
     */
    public static function hasPlayedToday(int $userId, string $minigame): bool
    {
        return static::where('user_id', $userId)
            ->where('minigame', $minigame)
            ->whereDate('played_at', today())
            ->exists();
    }

    /**
     * Check if a minigame is daily-limited.
     */
    public static function isDailyLimited(string $minigame): bool
    {
        return in_array($minigame, self::DAILY_LIMITED_GAMES);
    }

    /**
     * Get a user's rank in the weekly leaderboard.
     */
    public static function getUserWeeklyRank(int $userId, string $minigame, string $locationType, int $locationId): ?int
    {
        $userBestScore = static::query()
            ->where('user_id', $userId)
            ->forMinigame($minigame)
            ->forLocation($locationType, $locationId)
            ->weekly()
            ->max('score');

        if ($userBestScore === null) {
            return null;
        }

        return static::query()
            ->forMinigame($minigame)
            ->forLocation($locationType, $locationId)
            ->weekly()
            ->selectRaw('user_id, MAX(score) as best_score')
            ->groupBy('user_id')
            ->having('best_score', '>', $userBestScore)
            ->count() + 1;
    }

    /**
     * Get a user's rank in the monthly leaderboard.
     */
    public static function getUserMonthlyRank(int $userId, string $minigame, string $locationType, int $locationId): ?int
    {
        $userBestScore = static::query()
            ->where('user_id', $userId)
            ->forMinigame($minigame)
            ->forLocation($locationType, $locationId)
            ->monthly()
            ->max('score');

        if ($userBestScore === null) {
            return null;
        }

        return static::query()
            ->forMinigame($minigame)
            ->forLocation($locationType, $locationId)
            ->monthly()
            ->selectRaw('user_id, MAX(score) as best_score')
            ->groupBy('user_id')
            ->having('best_score', '>', $userBestScore)
            ->count() + 1;
    }

    /**
     * Get global leaderboard (across all locations).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public static function getGlobalLeaderboard(string $minigame, string $period = 'daily', int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::query()
            ->forMinigame($minigame)
            ->leaderboard()
            ->limit($limit);

        return match ($period) {
            'daily' => $query->daily()->get(),
            'weekly' => $query->weekly()->get(),
            'monthly' => $query->monthly()->get(),
            default => $query->daily()->get(),
        };
    }

    /**
     * Get a user's rank in a global leaderboard.
     */
    public static function getUserGlobalRank(int $userId, string $minigame, string $period = 'daily'): ?int
    {
        $baseQuery = static::query()
            ->where('user_id', $userId)
            ->forMinigame($minigame);

        $userBestScore = match ($period) {
            'daily' => $baseQuery->daily()->max('score'),
            'weekly' => $baseQuery->weekly()->max('score'),
            'monthly' => $baseQuery->monthly()->max('score'),
            default => $baseQuery->daily()->max('score'),
        };

        if ($userBestScore === null) {
            return null;
        }

        $rankQuery = static::query()
            ->forMinigame($minigame)
            ->selectRaw('user_id, MAX(score) as best_score')
            ->groupBy('user_id')
            ->having('best_score', '>', $userBestScore);

        return match ($period) {
            'daily' => $rankQuery->daily()->count() + 1,
            'weekly' => $rankQuery->weekly()->count() + 1,
            'monthly' => $rankQuery->monthly()->count() + 1,
            default => $rankQuery->daily()->count() + 1,
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinigameReward extends Model
{
    /** @use HasFactory<\Database\Factories\MinigameRewardFactory> */
    use HasFactory;

    /**
     * Reward type constants.
     */
    public const TYPE_DAILY = 'daily';

    public const TYPE_WEEKLY = 'weekly';

    public const TYPE_MONTHLY = 'monthly';

    public const REWARD_TYPES = [
        self::TYPE_DAILY,
        self::TYPE_WEEKLY,
        self::TYPE_MONTHLY,
    ];

    /**
     * Item rarity constants.
     */
    public const RARITY_RARE = 'rare';

    public const RARITY_EPIC = 'epic';

    public const RARITY_LEGENDARY = 'legendary';

    public const ITEM_RARITIES = [
        self::RARITY_RARE,
        self::RARITY_EPIC,
        self::RARITY_LEGENDARY,
    ];

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

    protected $fillable = [
        'user_id',
        'minigame',
        'reward_type',
        'rank',
        'location_type',
        'location_id',
        'gold_amount',
        'item_id',
        'item_rarity',
        'period_start',
        'period_end',
        'collected_at',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'location_id' => 'integer',
            'gold_amount' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'collected_at' => 'datetime',
        ];
    }

    /**
     * Get the user who earned this reward.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the item reward (if any).
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Scope for uncollected rewards.
     */
    public function scopeUncollected(Builder $query): Builder
    {
        return $query->whereNull('collected_at');
    }

    /**
     * Scope for collected rewards.
     */
    public function scopeCollected(Builder $query): Builder
    {
        return $query->whereNotNull('collected_at');
    }

    /**
     * Scope for a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for a specific minigame.
     */
    public function scopeForMinigame(Builder $query, string $minigame): Builder
    {
        return $query->where('minigame', $minigame);
    }

    /**
     * Scope for a specific reward type.
     */
    public function scopeOfType(Builder $query, string $rewardType): Builder
    {
        return $query->where('reward_type', $rewardType);
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
     * Check if the reward has been collected.
     */
    public function isCollected(): bool
    {
        return $this->collected_at !== null;
    }

    /**
     * Check if the reward has an item.
     */
    public function hasItem(): bool
    {
        return $this->item_id !== null;
    }

    /**
     * Mark the reward as collected.
     */
    public function collect(): bool
    {
        if ($this->isCollected()) {
            return false;
        }

        $this->update(['collected_at' => now()]);

        return true;
    }

    /**
     * Get all uncollected rewards for a user.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public static function getUncollectedForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()
            ->forUser($userId)
            ->uncollected()
            ->with('item')
            ->orderBy('period_end', 'desc')
            ->get();
    }

    /**
     * Get uncollected rewards at a specific location for a user.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public static function getUncollectedAtLocation(int $userId, string $locationType, int $locationId): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()
            ->forUser($userId)
            ->forLocation($locationType, $locationId)
            ->uncollected()
            ->with('item')
            ->orderBy('period_end', 'desc')
            ->get();
    }

    /**
     * Get count of uncollected rewards for a user.
     */
    public static function getUncollectedCount(int $userId): int
    {
        return static::query()
            ->forUser($userId)
            ->uncollected()
            ->count();
    }
}

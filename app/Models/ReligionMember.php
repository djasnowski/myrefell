<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReligionMember extends Model
{
    use HasFactory;

    public const RANK_PROPHET = 'prophet';
    public const RANK_PRIEST = 'priest';
    public const RANK_FOLLOWER = 'follower';

    public const RANKS = [
        self::RANK_PROPHET,
        self::RANK_PRIEST,
        self::RANK_FOLLOWER,
    ];

    // Devotion thresholds for promotion
    public const PRIEST_DEVOTION_REQUIREMENT = 1000;

    protected $fillable = [
        'user_id',
        'religion_id',
        'rank',
        'devotion',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'devotion' => 'integer',
            'joined_at' => 'datetime',
        ];
    }

    /**
     * Get the user who is a member.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the religion.
     */
    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    /**
     * Check if member is the prophet.
     */
    public function isProphet(): bool
    {
        return $this->rank === self::RANK_PROPHET;
    }

    /**
     * Check if member is a priest.
     */
    public function isPriest(): bool
    {
        return $this->rank === self::RANK_PRIEST;
    }

    /**
     * Check if member is a follower.
     */
    public function isFollower(): bool
    {
        return $this->rank === self::RANK_FOLLOWER;
    }

    /**
     * Check if member can be promoted to priest.
     */
    public function canBePromoted(): bool
    {
        return $this->isFollower()
            && $this->devotion >= self::PRIEST_DEVOTION_REQUIREMENT;
    }

    /**
     * Add devotion points.
     */
    public function addDevotion(int $amount): void
    {
        $this->increment('devotion', $amount);
    }

    /**
     * Get rank display name.
     */
    public function getRankDisplayAttribute(): string
    {
        return match ($this->rank) {
            self::RANK_PROPHET => 'Prophet',
            self::RANK_PRIEST => 'Priest',
            self::RANK_FOLLOWER => 'Follower',
            default => 'Unknown',
        };
    }
}

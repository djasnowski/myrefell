<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReligionMember extends Model
{
    use HasFactory;

    // Shared ranks
    public const RANK_PROPHET = 'prophet';

    public const RANK_FOLLOWER = 'follower';

    // Religion-specific ranks
    public const RANK_ARCHBISHOP = 'archbishop';

    public const RANK_PRIEST = 'priest';

    public const RANK_DEACON = 'deacon';

    // Cult-specific ranks
    public const RANK_APOSTLE = 'apostle';

    public const RANK_ACOLYTE = 'acolyte';

    public const RANK_DISCIPLE = 'disciple';

    // Religion ranks (highest to lowest)
    public const RELIGION_RANKS = [
        self::RANK_PROPHET,
        self::RANK_ARCHBISHOP,
        self::RANK_PRIEST,
        self::RANK_DEACON,
        self::RANK_FOLLOWER,
    ];

    // Cult ranks (highest to lowest)
    public const CULT_RANKS = [
        self::RANK_PROPHET,
        self::RANK_APOSTLE,
        self::RANK_ACOLYTE,
        self::RANK_DISCIPLE,
        self::RANK_FOLLOWER,
    ];

    // All valid ranks
    public const ALL_RANKS = [
        self::RANK_PROPHET,
        self::RANK_ARCHBISHOP,
        self::RANK_PRIEST,
        self::RANK_DEACON,
        self::RANK_APOSTLE,
        self::RANK_ACOLYTE,
        self::RANK_DISCIPLE,
        self::RANK_FOLLOWER,
    ];

    // Devotion thresholds for promotion (same for both)
    public const TIER_4_DEVOTION = 5000;  // Archbishop/Apostle

    public const TIER_3_DEVOTION = 1500;  // Priest/Acolyte

    public const TIER_2_DEVOTION = 500;   // Deacon/Disciple

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
     * Check if this is a cult membership.
     */
    public function isCultMember(): bool
    {
        return $this->religion->isCult();
    }

    /**
     * Get the ranks for this member's religion type.
     */
    public function getAvailableRanks(): array
    {
        return $this->isCultMember() ? self::CULT_RANKS : self::RELIGION_RANKS;
    }

    /**
     * Check if member is the prophet.
     */
    public function isProphet(): bool
    {
        return $this->rank === self::RANK_PROPHET;
    }

    /**
     * Check if member is an officer (second or third tier).
     */
    public function isOfficer(): bool
    {
        return in_array($this->rank, [
            self::RANK_ARCHBISHOP,
            self::RANK_PRIEST,
            self::RANK_APOSTLE,
            self::RANK_ACOLYTE,
        ]);
    }

    /**
     * Check if member is a follower.
     */
    public function isFollower(): bool
    {
        return $this->rank === self::RANK_FOLLOWER;
    }

    /**
     * Get the next rank this member can be promoted to.
     */
    public function getNextRank(): ?string
    {
        if ($this->isCultMember()) {
            return match ($this->rank) {
                self::RANK_FOLLOWER => self::RANK_DISCIPLE,
                self::RANK_DISCIPLE => self::RANK_ACOLYTE,
                self::RANK_ACOLYTE => self::RANK_APOSTLE,
                default => null,
            };
        }

        return match ($this->rank) {
            self::RANK_FOLLOWER => self::RANK_DEACON,
            self::RANK_DEACON => self::RANK_PRIEST,
            self::RANK_PRIEST => self::RANK_ARCHBISHOP,
            default => null,
        };
    }

    /**
     * Get the previous rank this member can be demoted to.
     */
    public function getPreviousRank(): ?string
    {
        if ($this->isCultMember()) {
            return match ($this->rank) {
                self::RANK_APOSTLE => self::RANK_ACOLYTE,
                self::RANK_ACOLYTE => self::RANK_DISCIPLE,
                self::RANK_DISCIPLE => self::RANK_FOLLOWER,
                default => null,
            };
        }

        return match ($this->rank) {
            self::RANK_ARCHBISHOP => self::RANK_PRIEST,
            self::RANK_PRIEST => self::RANK_DEACON,
            self::RANK_DEACON => self::RANK_FOLLOWER,
            default => null,
        };
    }

    /**
     * Get devotion requirement for next rank.
     */
    public function getDevotionRequirementForNextRank(): ?int
    {
        $nextRank = $this->getNextRank();
        if (! $nextRank) {
            return null;
        }

        return match ($nextRank) {
            self::RANK_ARCHBISHOP, self::RANK_APOSTLE => self::TIER_4_DEVOTION,
            self::RANK_PRIEST, self::RANK_ACOLYTE => self::TIER_3_DEVOTION,
            self::RANK_DEACON, self::RANK_DISCIPLE => self::TIER_2_DEVOTION,
            default => null,
        };
    }

    /**
     * Check if member can be promoted to the next rank.
     */
    public function canBePromoted(): bool
    {
        $nextRank = $this->getNextRank();
        if (! $nextRank) {
            return false;
        }

        $requirement = $this->getDevotionRequirementForNextRank();

        return $requirement && $this->devotion >= $requirement;
    }

    /**
     * Check if member can be demoted.
     */
    public function canBeDemoted(): bool
    {
        return $this->getPreviousRank() !== null;
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
            self::RANK_ARCHBISHOP => 'Archbishop',
            self::RANK_PRIEST => 'Priest',
            self::RANK_DEACON => 'Deacon',
            self::RANK_APOSTLE => 'Apostle',
            self::RANK_ACOLYTE => 'Acolyte',
            self::RANK_DISCIPLE => 'Disciple',
            self::RANK_FOLLOWER => 'Follower',
            default => 'Unknown',
        };
    }

    /**
     * Get numeric rank level (higher = more authority).
     */
    public function getRankLevel(): int
    {
        if ($this->isCultMember()) {
            return match ($this->rank) {
                self::RANK_PROPHET => 5,
                self::RANK_APOSTLE => 4,
                self::RANK_ACOLYTE => 3,
                self::RANK_DISCIPLE => 2,
                self::RANK_FOLLOWER => 1,
                default => 0,
            };
        }

        return match ($this->rank) {
            self::RANK_PROPHET => 5,
            self::RANK_ARCHBISHOP => 4,
            self::RANK_PRIEST => 3,
            self::RANK_DEACON => 2,
            self::RANK_FOLLOWER => 1,
            default => 0,
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuildMember extends Model
{
    use HasFactory;

    public const RANK_GUILDMASTER = 'guildmaster';
    public const RANK_MASTER = 'master';
    public const RANK_JOURNEYMAN = 'journeyman';
    public const RANK_APPRENTICE = 'apprentice';

    public const RANKS = [
        self::RANK_GUILDMASTER,
        self::RANK_MASTER,
        self::RANK_JOURNEYMAN,
        self::RANK_APPRENTICE,
    ];

    // Years required for each rank
    public const APPRENTICE_YEARS = 0;
    public const JOURNEYMAN_YEARS = 2;
    public const MASTER_YEARS = 5;

    // Contribution thresholds for promotion eligibility
    public const JOURNEYMAN_CONTRIBUTION = 500;
    public const MASTER_CONTRIBUTION = 5000;

    protected $fillable = [
        'user_id',
        'guild_id',
        'rank',
        'contribution',
        'years_membership',
        'dues_paid',
        'dues_paid_until',
        'joined_at',
        'promoted_at',
    ];

    protected function casts(): array
    {
        return [
            'contribution' => 'integer',
            'years_membership' => 'integer',
            'dues_paid' => 'boolean',
            'joined_at' => 'datetime',
            'promoted_at' => 'datetime',
            'dues_paid_until' => 'datetime',
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
     * Get the guild.
     */
    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class);
    }

    /**
     * Check if member is the guildmaster.
     */
    public function isGuildmaster(): bool
    {
        return $this->rank === self::RANK_GUILDMASTER;
    }

    /**
     * Check if member is a master.
     */
    public function isMaster(): bool
    {
        return $this->rank === self::RANK_MASTER;
    }

    /**
     * Check if member is a journeyman.
     */
    public function isJourneyman(): bool
    {
        return $this->rank === self::RANK_JOURNEYMAN;
    }

    /**
     * Check if member is an apprentice.
     */
    public function isApprentice(): bool
    {
        return $this->rank === self::RANK_APPRENTICE;
    }

    /**
     * Check if member has voting rights (master or guildmaster).
     */
    public function hasVotingRights(): bool
    {
        return in_array($this->rank, [self::RANK_MASTER, self::RANK_GUILDMASTER]);
    }

    /**
     * Check if member can be promoted.
     */
    public function canBePromoted(): bool
    {
        if ($this->isGuildmaster() || $this->isMaster()) {
            return false;
        }

        if ($this->isJourneyman()) {
            return $this->years_membership >= self::MASTER_YEARS
                && $this->contribution >= self::MASTER_CONTRIBUTION;
        }

        if ($this->isApprentice()) {
            return $this->years_membership >= self::JOURNEYMAN_YEARS
                && $this->contribution >= self::JOURNEYMAN_CONTRIBUTION;
        }

        return false;
    }

    /**
     * Get the next rank for promotion.
     */
    public function getNextRank(): ?string
    {
        return match ($this->rank) {
            self::RANK_APPRENTICE => self::RANK_JOURNEYMAN,
            self::RANK_JOURNEYMAN => self::RANK_MASTER,
            default => null,
        };
    }

    /**
     * Add contribution points.
     */
    public function addContribution(int $amount): void
    {
        $this->increment('contribution', $amount);
    }

    /**
     * Get rank display name.
     */
    public function getRankDisplayAttribute(): string
    {
        return match ($this->rank) {
            self::RANK_GUILDMASTER => 'Guildmaster',
            self::RANK_MASTER => 'Master',
            self::RANK_JOURNEYMAN => 'Journeyman',
            self::RANK_APPRENTICE => 'Apprentice',
            default => 'Unknown',
        };
    }

    /**
     * Get promotion requirements as array.
     */
    public function getPromotionRequirementsAttribute(): array
    {
        if ($this->isApprentice()) {
            return [
                'years_required' => self::JOURNEYMAN_YEARS,
                'contribution_required' => self::JOURNEYMAN_CONTRIBUTION,
                'years_met' => $this->years_membership >= self::JOURNEYMAN_YEARS,
                'contribution_met' => $this->contribution >= self::JOURNEYMAN_CONTRIBUTION,
            ];
        }

        if ($this->isJourneyman()) {
            return [
                'years_required' => self::MASTER_YEARS,
                'contribution_required' => self::MASTER_CONTRIBUTION,
                'years_met' => $this->years_membership >= self::MASTER_YEARS,
                'contribution_met' => $this->contribution >= self::MASTER_CONTRIBUTION,
            ];
        }

        return [];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guild extends Model
{
    use HasFactory;

    // Founding requirements
    public const MIN_FOUNDING_MEMBERS = 5;
    public const FOUNDING_COST = 50000;
    public const DEFAULT_MEMBERSHIP_FEE = 1000;
    public const DEFAULT_WEEKLY_DUES = 100;

    // Contribution thresholds for guild level
    public const LEVEL_THRESHOLDS = [
        1 => 0,
        2 => 10000,
        3 => 50000,
        4 => 150000,
        5 => 500000,
        6 => 1500000,
        7 => 5000000,
        8 => 15000000,
        9 => 50000000,
        10 => 150000000,
    ];

    // Skills that can have guilds
    public const GUILD_SKILLS = [
        'smithing',
        'crafting',
        'cooking',
        'mining',
        'woodcutting',
        'fishing',
    ];

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color',
        'primary_skill',
        'location_type',
        'location_id',
        'founder_id',
        'guildmaster_id',
        'treasury',
        'level',
        'total_contribution',
        'founding_cost',
        'membership_fee',
        'weekly_dues',
        'is_public',
        'has_monopoly',
        'monopoly_granted_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'treasury' => 'integer',
            'level' => 'integer',
            'total_contribution' => 'integer',
            'founding_cost' => 'integer',
            'membership_fee' => 'integer',
            'weekly_dues' => 'integer',
            'is_public' => 'boolean',
            'has_monopoly' => 'boolean',
            'is_active' => 'boolean',
            'monopoly_granted_at' => 'datetime',
        ];
    }

    /**
     * Get the founder of the guild.
     */
    public function founder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'founder_id');
    }

    /**
     * Get the current guildmaster.
     */
    public function guildmaster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guildmaster_id');
    }

    /**
     * Get the members of this guild.
     */
    public function members(): HasMany
    {
        return $this->hasMany(GuildMember::class);
    }

    /**
     * Get the benefits this guild has unlocked.
     */
    public function benefits(): BelongsToMany
    {
        return $this->belongsToMany(GuildBenefit::class, 'guild_benefit_guild')
            ->withTimestamps();
    }

    /**
     * Get the activities log for this guild.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(GuildActivity::class);
    }

    /**
     * Get the elections for this guild.
     */
    public function elections(): HasMany
    {
        return $this->hasMany(GuildElection::class);
    }

    /**
     * Get the price controls for this guild.
     */
    public function priceControls(): HasMany
    {
        return $this->hasMany(GuildPriceControl::class);
    }

    /**
     * Get the location model.
     */
    public function location()
    {
        return match ($this->location_type) {
            'town' => Town::find($this->location_id),
            'barony' => Barony::find($this->location_id),
            default => null,
        };
    }

    /**
     * Get member count.
     */
    public function getMemberCountAttribute(): int
    {
        return $this->members()->count();
    }

    /**
     * Get master count (members with voting rights).
     */
    public function getMasterCountAttribute(): int
    {
        return $this->members()
            ->whereIn('rank', [GuildMember::RANK_MASTER, GuildMember::RANK_GUILDMASTER])
            ->count();
    }

    /**
     * Check if the guild can accept more members.
     */
    public function canAcceptMembers(): bool
    {
        return $this->is_active && $this->is_public;
    }

    /**
     * Add contribution to the guild and handle level ups.
     */
    public function addContribution(int $amount): bool
    {
        $this->increment('total_contribution', $amount);

        // Check for level up
        $newLevel = $this->calculateLevel();
        if ($newLevel > $this->level) {
            $this->update(['level' => $newLevel]);
            return true;
        }

        return false;
    }

    /**
     * Calculate guild level from total contribution.
     */
    public function calculateLevel(): int
    {
        $level = 1;
        foreach (self::LEVEL_THRESHOLDS as $lvl => $threshold) {
            if ($this->total_contribution >= $threshold) {
                $level = $lvl;
            }
        }
        return min($level, 10);
    }

    /**
     * Get progress to next level as percentage.
     */
    public function getLevelProgressAttribute(): float
    {
        if ($this->level >= 10) {
            return 100.0;
        }

        $currentThreshold = self::LEVEL_THRESHOLDS[$this->level];
        $nextThreshold = self::LEVEL_THRESHOLDS[$this->level + 1];
        $progress = $this->total_contribution - $currentThreshold;
        $needed = $nextThreshold - $currentThreshold;

        return ($progress / $needed) * 100;
    }

    /**
     * Get the guildmaster member record.
     */
    public function getGuildmasterMember(): ?GuildMember
    {
        return $this->members()->where('rank', GuildMember::RANK_GUILDMASTER)->first();
    }

    /**
     * Get masters (members with voting rights).
     */
    public function getMasters()
    {
        return $this->members()
            ->whereIn('rank', [GuildMember::RANK_MASTER, GuildMember::RANK_GUILDMASTER])
            ->get();
    }

    /**
     * Calculate combined benefit effects.
     */
    public function getCombinedEffects(): array
    {
        $effects = [];

        foreach ($this->benefits as $benefit) {
            if (!$benefit->effects) {
                continue;
            }

            foreach ($benefit->effects as $stat => $value) {
                $effects[$stat] = ($effects[$stat] ?? 0) + $value;
            }
        }

        return $effects;
    }

    /**
     * Get skill display name.
     */
    public function getSkillDisplayAttribute(): string
    {
        return ucfirst($this->primary_skill);
    }

    /**
     * Check if there's an active election.
     */
    public function hasActiveElection(): bool
    {
        return $this->elections()
            ->whereIn('status', ['nomination', 'voting'])
            ->exists();
    }

    /**
     * Get current active election.
     */
    public function getActiveElection(): ?GuildElection
    {
        return $this->elections()
            ->whereIn('status', ['nomination', 'voting'])
            ->first();
    }
}

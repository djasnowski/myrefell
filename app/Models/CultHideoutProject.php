<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CultHideoutProject extends Model
{
    public const TYPE_BUILD = 'build';

    public const TYPE_UPGRADE = 'upgrade';

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_CONSTRUCTING = 'constructing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Construction time in hours per tier.
     */
    public const CONSTRUCTION_HOURS = [
        1 => 0,    // Instant for first hideout
        2 => 4,    // 4 hours
        3 => 12,   // 12 hours
        4 => 24,   // 1 day
        5 => 48,   // 2 days
    ];

    protected $fillable = [
        'religion_id',
        'project_type',
        'target_tier',
        'gold_required',
        'gold_invested',
        'devotion_required',
        'devotion_invested',
        'status',
        'construction_ends_at',
        'started_by',
    ];

    protected function casts(): array
    {
        return [
            'gold_required' => 'integer',
            'gold_invested' => 'integer',
            'devotion_required' => 'integer',
            'devotion_invested' => 'integer',
            'construction_ends_at' => 'datetime',
        ];
    }

    /**
     * Get the religion this project belongs to.
     */
    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    /**
     * Get the user who started this project.
     */
    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    /**
     * Check if this project is still accepting contributions.
     */
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Check if this project is under construction.
     */
    public function isConstructing(): bool
    {
        return $this->status === self::STATUS_CONSTRUCTING;
    }

    /**
     * Check if construction timer has completed.
     */
    public function isConstructionComplete(): bool
    {
        return $this->isConstructing()
            && $this->construction_ends_at
            && $this->construction_ends_at->isPast();
    }

    /**
     * Check if all requirements have been met.
     */
    public function requirementsMet(): bool
    {
        return $this->gold_invested >= $this->gold_required
            && $this->devotion_invested >= $this->devotion_required;
    }

    /**
     * Get overall progress as a percentage.
     */
    public function getProgressAttribute(): int
    {
        $goldProgress = $this->gold_required > 0
            ? ($this->gold_invested / $this->gold_required) * 100
            : 100;

        $devotionProgress = $this->devotion_required > 0
            ? ($this->devotion_invested / $this->devotion_required) * 100
            : 100;

        return (int) min(100, ($goldProgress + $devotionProgress) / 2);
    }

    /**
     * Contribute gold and/or devotion to the project.
     */
    public function contribute(int $gold, int $devotion): array
    {
        $goldAdded = 0;
        $devotionAdded = 0;

        if ($gold > 0) {
            $goldNeeded = max(0, $this->gold_required - $this->gold_invested);
            $goldAdded = min($gold, $goldNeeded);
            $this->gold_invested += $goldAdded;
        }

        if ($devotion > 0) {
            $devotionNeeded = max(0, $this->devotion_required - $this->devotion_invested);
            $devotionAdded = min($devotion, $devotionNeeded);
            $this->devotion_invested += $devotionAdded;
        }

        // Update status if we've started contributing
        if ($this->status === self::STATUS_PENDING && ($goldAdded > 0 || $devotionAdded > 0)) {
            $this->status = self::STATUS_IN_PROGRESS;
        }

        $this->save();

        return [
            'gold_added' => $goldAdded,
            'devotion_added' => $devotionAdded,
        ];
    }

    /**
     * Start the construction timer.
     */
    public function startConstructionTimer(): void
    {
        $hours = self::CONSTRUCTION_HOURS[$this->target_tier] ?? 0;

        $this->status = self::STATUS_CONSTRUCTING;

        if ($hours > 0) {
            $this->construction_ends_at = now()->addHours($hours);
        } else {
            // Instant completion for tier 1
            $this->construction_ends_at = now();
        }

        $this->save();
    }

    /**
     * Get construction time in hours for this project.
     */
    public function getConstructionTimeHours(): int
    {
        return self::CONSTRUCTION_HOURS[$this->target_tier] ?? 0;
    }

    /**
     * Mark the project as completed.
     */
    public function complete(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->save();
    }

    /**
     * Get remaining construction time as a string.
     */
    public function getRemainingTimeAttribute(): ?string
    {
        if (! $this->isConstructing() || ! $this->construction_ends_at) {
            return null;
        }

        if ($this->construction_ends_at->isPast()) {
            return 'Ready';
        }

        return $this->construction_ends_at->diffForHumans(['parts' => 2]);
    }

    /**
     * Get remaining construction time in seconds.
     */
    public function getRemainingSecondsAttribute(): int
    {
        if (! $this->isConstructing() || ! $this->construction_ends_at) {
            return 0;
        }

        return max(0, now()->diffInSeconds($this->construction_ends_at, false));
    }

    /**
     * Get a display name for the project type.
     */
    public function getProjectTypeDisplayAttribute(): string
    {
        return match ($this->project_type) {
            self::TYPE_BUILD => 'Build Hideout',
            self::TYPE_UPGRADE => 'Upgrade Hideout',
            default => 'Unknown',
        };
    }

    /**
     * Get project description.
     */
    public function getDescriptionAttribute(): string
    {
        $tierName = Religion::HIDEOUT_TIERS[$this->target_tier]['name'] ?? 'Unknown';

        return match ($this->project_type) {
            self::TYPE_BUILD => "Establish a {$tierName}",
            self::TYPE_UPGRADE => "Upgrade to {$tierName}",
            default => 'Unknown project',
        };
    }
}

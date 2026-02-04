<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HqConstructionProject extends Model
{
    public const TYPE_HQ_UPGRADE = 'hq_upgrade';

    public const TYPE_FEATURE_BUILD = 'feature_build';

    public const TYPE_FEATURE_UPGRADE = 'feature_upgrade';

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_CONSTRUCTING = 'constructing'; // Requirements met, waiting on timer

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Construction times in hours for HQ upgrades by target tier.
     */
    public const HQ_UPGRADE_TIMES = [
        2 => 2,    // Chapel -> Church: 2 hours
        3 => 6,    // Church -> Temple: 6 hours
        4 => 12,   // Temple -> Cathedral: 12 hours
        5 => 24,   // Cathedral -> Grand Cathedral: 24 hours
        6 => 48,   // Grand Cathedral -> Holy Sanctum: 48 hours
    ];

    /**
     * Construction times in hours for features by feature level.
     */
    public const FEATURE_BUILD_TIMES = [
        1 => 1,    // Level 1: 1 hour
        2 => 2,    // Level 2: 2 hours
        3 => 4,    // Level 3: 4 hours
        4 => 8,    // Level 4: 8 hours
        5 => 12,   // Level 5: 12 hours
    ];

    protected $fillable = [
        'religion_hq_id',
        'project_type',
        'hq_feature_type_id',
        'target_level',
        'status',
        'progress',
        'gold_required',
        'gold_invested',
        'devotion_required',
        'devotion_invested',
        'items_required',
        'items_invested',
        'started_by',
        'started_at',
        'construction_ends_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'target_level' => 'integer',
            'progress' => 'integer',
            'gold_required' => 'integer',
            'gold_invested' => 'integer',
            'devotion_required' => 'integer',
            'devotion_invested' => 'integer',
            'items_required' => 'array',
            'items_invested' => 'array',
            'started_at' => 'datetime',
            'construction_ends_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the headquarters this project belongs to.
     */
    public function headquarters(): BelongsTo
    {
        return $this->belongsTo(ReligionHeadquarters::class, 'religion_hq_id');
    }

    /**
     * Get the feature type (if this is a feature project).
     */
    public function featureType(): BelongsTo
    {
        return $this->belongsTo(HqFeatureType::class, 'hq_feature_type_id');
    }

    /**
     * Get the user who started this project.
     */
    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    /**
     * Check if this project is complete.
     */
    public function isComplete(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if this project is active (can receive contributions).
     */
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Check if this project is under construction (waiting on timer).
     */
    public function isConstructing(): bool
    {
        return $this->status === self::STATUS_CONSTRUCTING;
    }

    /**
     * Check if the construction timer has expired.
     */
    public function isConstructionComplete(): bool
    {
        return $this->isConstructing() && $this->construction_ends_at && $this->construction_ends_at->isPast();
    }

    /**
     * Get the construction time in hours for this project.
     */
    public function getConstructionTimeHours(): int
    {
        if ($this->project_type === self::TYPE_HQ_UPGRADE) {
            return self::HQ_UPGRADE_TIMES[$this->target_level] ?? 2;
        }

        // For features, use the target level
        return self::FEATURE_BUILD_TIMES[$this->target_level] ?? 1;
    }

    /**
     * Start the construction timer (called when all requirements are met).
     */
    public function startConstructionTimer(): void
    {
        $hours = $this->getConstructionTimeHours();

        $this->status = self::STATUS_CONSTRUCTING;
        $this->progress = 100;
        $this->construction_ends_at = now()->addHours($hours);
        $this->save();
    }

    /**
     * Get remaining construction time as a human-readable string.
     */
    public function getRemainingTimeAttribute(): ?string
    {
        if (! $this->isConstructing() || ! $this->construction_ends_at) {
            return null;
        }

        if ($this->construction_ends_at->isPast()) {
            return 'Complete!';
        }

        return $this->construction_ends_at->diffForHumans(['parts' => 2, 'short' => true]);
    }

    /**
     * Get remaining seconds until construction completes.
     */
    public function getRemainingSecondsAttribute(): ?int
    {
        if (! $this->isConstructing() || ! $this->construction_ends_at) {
            return null;
        }

        if ($this->construction_ends_at->isPast()) {
            return 0;
        }

        return (int) now()->diffInSeconds($this->construction_ends_at);
    }

    /**
     * Calculate progress percentage based on contributions.
     */
    public function calculateProgress(): int
    {
        $goldProgress = $this->gold_required > 0
            ? ($this->gold_invested / $this->gold_required) * 100
            : 100;

        $devotionProgress = $this->devotion_required > 0
            ? ($this->devotion_invested / $this->devotion_required) * 100
            : 100;

        $itemProgress = $this->calculateItemProgress();

        // Average of all requirements
        return (int) floor(($goldProgress + $devotionProgress + $itemProgress) / 3);
    }

    /**
     * Calculate item contribution progress.
     */
    protected function calculateItemProgress(): float
    {
        if (empty($this->items_required)) {
            return 100;
        }

        $invested = $this->items_invested ?? [];
        $totalRequired = 0;
        $totalInvested = 0;

        foreach ($this->items_required as $itemId => $quantity) {
            $totalRequired += $quantity;
            $totalInvested += min($invested[$itemId] ?? 0, $quantity);
        }

        if ($totalRequired === 0) {
            return 100;
        }

        return ($totalInvested / $totalRequired) * 100;
    }

    /**
     * Check if all requirements are met.
     */
    public function requirementsMet(): bool
    {
        if ($this->gold_invested < $this->gold_required) {
            return false;
        }

        if ($this->devotion_invested < $this->devotion_required) {
            return false;
        }

        if (! empty($this->items_required)) {
            $invested = $this->items_invested ?? [];
            foreach ($this->items_required as $itemId => $quantity) {
                if (($invested[$itemId] ?? 0) < $quantity) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get project type display name.
     */
    public function getProjectTypeDisplayAttribute(): string
    {
        return match ($this->project_type) {
            self::TYPE_HQ_UPGRADE => 'HQ Upgrade',
            self::TYPE_FEATURE_BUILD => 'Build Feature',
            self::TYPE_FEATURE_UPGRADE => 'Upgrade Feature',
            default => 'Unknown',
        };
    }

    /**
     * Get a description of what this project is building/upgrading.
     */
    public function getDescriptionAttribute(): string
    {
        return match ($this->project_type) {
            self::TYPE_HQ_UPGRADE => 'Upgrading to '.ReligionHeadquarters::TIER_NAMES[$this->target_level],
            self::TYPE_FEATURE_BUILD => 'Building '.$this->featureType?->name,
            self::TYPE_FEATURE_UPGRADE => 'Upgrading '.$this->featureType?->name.' to Level '.$this->target_level,
            default => 'Unknown Project',
        };
    }

    /**
     * Add a contribution to this project.
     *
     * @return array{gold_added: int, devotion_added: int, items_added: array}
     */
    public function contribute(int $gold = 0, int $devotion = 0, array $items = []): array
    {
        $goldAdded = 0;
        $devotionAdded = 0;
        $itemsAdded = [];

        // Add gold (don't exceed required)
        if ($gold > 0) {
            $goldNeeded = $this->gold_required - $this->gold_invested;
            $goldAdded = min($gold, $goldNeeded);
            $this->gold_invested += $goldAdded;
        }

        // Add devotion (don't exceed required)
        if ($devotion > 0) {
            $devotionNeeded = $this->devotion_required - $this->devotion_invested;
            $devotionAdded = min($devotion, $devotionNeeded);
            $this->devotion_invested += $devotionAdded;
        }

        // Add items (don't exceed required)
        if (! empty($items) && ! empty($this->items_required)) {
            $invested = $this->items_invested ?? [];

            foreach ($items as $itemId => $quantity) {
                if (! isset($this->items_required[$itemId])) {
                    continue;
                }

                $needed = $this->items_required[$itemId] - ($invested[$itemId] ?? 0);
                $added = min($quantity, $needed);

                if ($added > 0) {
                    $invested[$itemId] = ($invested[$itemId] ?? 0) + $added;
                    $itemsAdded[$itemId] = $added;
                }
            }

            $this->items_invested = $invested;
        }

        // Update progress
        $this->progress = $this->calculateProgress();

        // Check if in progress
        if ($this->status === self::STATUS_PENDING && ($goldAdded > 0 || $devotionAdded > 0 || ! empty($itemsAdded))) {
            $this->status = self::STATUS_IN_PROGRESS;
            $this->started_at = $this->started_at ?? now();
        }

        $this->save();

        return [
            'gold_added' => $goldAdded,
            'devotion_added' => $devotionAdded,
            'items_added' => $itemsAdded,
        ];
    }

    /**
     * Mark the project as completed.
     */
    public function complete(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->progress = 100;
        $this->completed_at = now();
        $this->save();
    }

    /**
     * Cancel the project.
     */
    public function cancel(): void
    {
        $this->status = self::STATUS_CANCELLED;
        $this->save();
    }
}

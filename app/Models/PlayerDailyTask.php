<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerDailyTask extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CLAIMED = 'claimed';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'daily_task_id',
        'current_progress',
        'target_amount',
        'status',
        'assigned_date',
        'completed_at',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'current_progress' => 'integer',
            'target_amount' => 'integer',
            'assigned_date' => 'date',
            'completed_at' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }

    /**
     * Get the user this task is assigned to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the daily task template.
     */
    public function dailyTask(): BelongsTo
    {
        return $this->belongsTo(DailyTask::class);
    }

    /**
     * Check if this task is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if this task is completed but not claimed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if this task has been claimed.
     */
    public function isClaimed(): bool
    {
        return $this->status === self::STATUS_CLAIMED;
    }

    /**
     * Check if this task has expired.
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || $this->assigned_date->lt(today());
    }

    /**
     * Get progress percentage.
     */
    public function getProgressPercentAttribute(): int
    {
        if ($this->target_amount === 0) {
            return 100;
        }

        return min(100, (int) floor(($this->current_progress / $this->target_amount) * 100));
    }

    /**
     * Add progress to this task.
     */
    public function addProgress(int $amount = 1): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        $this->current_progress = min($this->current_progress + $amount, $this->target_amount);

        if ($this->current_progress >= $this->target_amount) {
            $this->status = self::STATUS_COMPLETED;
            $this->completed_at = now();
        }

        return $this->save();
    }

    /**
     * Claim rewards for this task.
     */
    public function claim(): bool
    {
        if (! $this->isCompleted()) {
            return false;
        }

        $this->status = self::STATUS_CLAIMED;
        $this->claimed_at = now();

        return $this->save();
    }
}

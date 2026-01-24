<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerQuest extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CLAIMED = 'claimed';
    public const STATUS_ABANDONED = 'abandoned';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'quest_id',
        'status',
        'current_progress',
        'accepted_at',
        'completed_at',
        'claimed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'current_progress' => 'integer',
            'accepted_at' => 'datetime',
            'completed_at' => 'datetime',
            'claimed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the user who owns this quest.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the quest.
     */
    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }

    /**
     * Check if quest is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if quest is completed but not claimed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if quest is ready to claim (progress met target).
     */
    public function canClaim(): bool
    {
        return $this->current_progress >= $this->quest->target_amount
            && $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Add progress to the quest.
     */
    public function addProgress(int $amount = 1): void
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return;
        }

        $this->increment('current_progress', $amount);

        // Check if completed
        if ($this->current_progress >= $this->quest->target_amount) {
            $this->update([
                'status' => self::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Claim the quest rewards.
     */
    public function claim(): void
    {
        $this->update([
            'status' => self::STATUS_CLAIMED,
            'claimed_at' => now(),
        ]);
    }

    /**
     * Abandon the quest.
     */
    public function abandon(): void
    {
        $this->update([
            'status' => self::STATUS_ABANDONED,
        ]);
    }

    /**
     * Get progress percentage.
     */
    public function getProgressPercentAttribute(): int
    {
        if ($this->quest->target_amount <= 0) {
            return 100;
        }

        return min(100, (int) floor(($this->current_progress / $this->quest->target_amount) * 100));
    }
}

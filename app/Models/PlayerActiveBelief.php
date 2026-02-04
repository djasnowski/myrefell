<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerActiveBelief extends Model
{
    protected $fillable = [
        'user_id',
        'belief_id',
        'religion_id',
        'devotion_spent',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    // Constants for duration calculation
    public const MIN_DEVOTION = 50;

    public const MAX_DEVOTION = 550;

    public const MIN_DURATION_MINUTES = 5;

    public const MAX_DURATION_MINUTES = 60;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function belief(): BelongsTo
    {
        return $this->belongsTo(Belief::class);
    }

    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    /**
     * Check if the belief buff is still active.
     */
    public function isActive(): bool
    {
        return $this->expires_at->isFuture();
    }

    /**
     * Get remaining seconds until expiration.
     */
    public function getRemainingSeconds(): int
    {
        return max(0, now()->diffInSeconds($this->expires_at, false));
    }

    /**
     * Calculate duration in minutes based on devotion spent.
     * Formula: 5 + (devotion / 10), capped at 60 minutes.
     */
    public static function calculateDurationMinutes(int $devotion): int
    {
        $duration = self::MIN_DURATION_MINUTES + (int) ($devotion / 10);

        return min($duration, self::MAX_DURATION_MINUTES);
    }

    /**
     * Scope to get only active beliefs.
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope to get beliefs for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}

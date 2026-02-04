<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerFeatureBuff extends Model
{
    protected $fillable = [
        'user_id',
        'religion_hq_feature_id',
        'effects',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'effects' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the player who has this buff.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the feature this buff came from.
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(ReligionHqFeature::class, 'religion_hq_feature_id');
    }

    /**
     * Scope to only active (non-expired) buffs.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope to expired buffs.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Check if this buff is still active.
     */
    public function isActive(): bool
    {
        return $this->expires_at->isFuture();
    }

    /**
     * Get remaining time as human-readable string.
     */
    public function getRemainingTimeAttribute(): ?string
    {
        if (! $this->isActive()) {
            return null;
        }

        return $this->expires_at->diffForHumans(['parts' => 2, 'short' => true]);
    }

    /**
     * Get remaining seconds.
     */
    public function getRemainingSecondsAttribute(): int
    {
        if (! $this->isActive()) {
            return 0;
        }

        return (int) now()->diffInSeconds($this->expires_at);
    }
}

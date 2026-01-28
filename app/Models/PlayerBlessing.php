<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerBlessing extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'blessing_type_id',
        'granted_by',
        'location_type',
        'location_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user who has this blessing.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the blessing type.
     */
    public function blessingType(): BelongsTo
    {
        return $this->belongsTo(BlessingType::class);
    }

    /**
     * Get the user who granted this blessing.
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * Scope to only active (non-expired) blessings.
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope to expired blessings.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Check if the blessing is still active.
     */
    public function isActive(): bool
    {
        return $this->expires_at->isFuture();
    }

    /**
     * Get time remaining in human readable format.
     */
    public function getTimeRemainingAttribute(): string
    {
        if (!$this->isActive()) {
            return 'Expired';
        }

        return $this->expires_at->diffForHumans(['parts' => 2, 'short' => true]);
    }

    /**
     * Get time remaining in minutes.
     */
    public function getMinutesRemainingAttribute(): int
    {
        if (!$this->isActive()) {
            return 0;
        }

        return (int) now()->diffInMinutes($this->expires_at);
    }

    /**
     * Get the effects from the blessing type.
     */
    public function getEffectsAttribute(): array
    {
        return $this->blessingType?->effects ?? [];
    }
}

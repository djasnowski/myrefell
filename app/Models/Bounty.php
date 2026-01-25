<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bounty extends Model
{
    public const CAPTURE_ALIVE = 'alive';
    public const CAPTURE_DEAD_OR_ALIVE = 'dead_or_alive';
    public const CAPTURE_DEAD = 'dead';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLAIMED = 'claimed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    public const POSTER_PLAYER = 'player';
    public const POSTER_VILLAGE = 'village';
    public const POSTER_BARONY = 'barony';
    public const POSTER_KINGDOM = 'kingdom';

    protected $fillable = [
        'target_id',
        'posted_by',
        'crime_id',
        'poster_type',
        'poster_location_id',
        'reward_amount',
        'capture_type',
        'reason',
        'status',
        'claimed_by',
        'claimed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'reward_amount' => 'integer',
            'claimed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function crime(): BelongsTo
    {
        return $this->belongsTo(Crime::class);
    }

    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    public function getPosterLocation(): Model|null
    {
        if ($this->poster_type === self::POSTER_PLAYER) {
            return null;
        }

        return match ($this->poster_type) {
            self::POSTER_VILLAGE => Village::find($this->poster_location_id),
            self::POSTER_BARONY => Barony::find($this->poster_location_id),
            self::POSTER_KINGDOM => Kingdom::find($this->poster_location_id),
            default => null,
        };
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isClaimed(): bool
    {
        return $this->status === self::STATUS_CLAIMED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED ||
               ($this->expires_at && $this->expires_at->isPast());
    }

    public function requiresAlive(): bool
    {
        return $this->capture_type === self::CAPTURE_ALIVE;
    }

    public function allowsDead(): bool
    {
        return in_array($this->capture_type, [self::CAPTURE_DEAD_OR_ALIVE, self::CAPTURE_DEAD]);
    }

    public function claim(User $hunter, bool $targetKilled = false): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($targetKilled && $this->requiresAlive()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_CLAIMED,
            'claimed_by' => $hunter->id,
            'claimed_at' => now(),
        ]);

        // Transfer reward
        if ($this->poster_type === self::POSTER_PLAYER && $this->postedBy) {
            // Already paid when posted, just give to hunter
            $hunter->increment('gold', $this->reward_amount);
        } else {
            // Pay from location treasury
            $location = $this->getPosterLocation();
            if ($location) {
                $treasury = LocationTreasury::getOrCreate($this->poster_type, $this->poster_location_id);
                if ($treasury->withdraw($this->reward_amount, 'bounty_payment', "Bounty on {$this->target->username}")) {
                    $hunter->increment('gold', $this->reward_amount);
                }
            }
        }

        return true;
    }

    public function cancel(): void
    {
        if ($this->isActive()) {
            $this->update(['status' => self::STATUS_CANCELLED]);

            // Refund if player posted
            if ($this->poster_type === self::POSTER_PLAYER && $this->postedBy) {
                $this->postedBy->increment('gold', $this->reward_amount);
            }
        }
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeForTarget($query, int $targetId)
    {
        return $query->where('target_id', $targetId);
    }

    public function getCaptureTypeDisplayAttribute(): string
    {
        return match ($this->capture_type) {
            self::CAPTURE_ALIVE => 'Wanted Alive',
            self::CAPTURE_DEAD_OR_ALIVE => 'Dead or Alive',
            self::CAPTURE_DEAD => 'Wanted Dead',
            default => 'Unknown',
        };
    }

    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_CLAIMED => 'Claimed',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown',
        };
    }
}

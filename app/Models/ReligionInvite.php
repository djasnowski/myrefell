<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReligionInvite extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACCEPTED,
        self::STATUS_DECLINED,
        self::STATUS_EXPIRED,
        self::STATUS_CANCELLED,
    ];

    public const INVITE_EXPIRY_DAYS = 7;

    protected $fillable = [
        'religion_id',
        'invited_by_user_id',
        'invited_user_id',
        'status',
        'message',
        'response_message',
        'expires_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    /**
     * Get the religion this invite is for.
     */
    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    /**
     * Get the user who sent the invite.
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    /**
     * Get the user who was invited.
     */
    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }

    /**
     * Scope to get pending invites.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get non-expired invites.
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope to get active invites (pending and not expired).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->pending()->notExpired();
    }

    /**
     * Scope to get invites for a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('invited_user_id', $userId);
    }

    /**
     * Scope to get invites for a specific religion.
     */
    public function scopeForReligion(Builder $query, int $religionId): Builder
    {
        return $query->where('religion_id', $religionId);
    }

    /**
     * Check if invite is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if invite is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED || $this->expires_at->isPast();
    }

    /**
     * Check if invite can be responded to.
     */
    public function canRespond(): bool
    {
        return $this->isPending() && ! $this->isExpired();
    }

    /**
     * Accept the invite.
     */
    public function accept(?string $responseMessage = null): void
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'response_message' => $responseMessage,
            'responded_at' => now(),
        ]);
    }

    /**
     * Decline the invite.
     */
    public function decline(?string $responseMessage = null): void
    {
        $this->update([
            'status' => self::STATUS_DECLINED,
            'response_message' => $responseMessage,
            'responded_at' => now(),
        ]);
    }

    /**
     * Cancel the invite.
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'responded_at' => now(),
        ]);
    }

    /**
     * Mark as expired.
     */
    public function markExpired(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    /**
     * Get status display name.
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePetition extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DENIED = 'denied';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUS_EXPIRED = 'expired';

    public const EXPIRATION_DAYS = 7;

    protected $fillable = [
        'petitioner_id',
        'target_player_role_id',
        'authority_user_id',
        'authority_role_slug',
        'location_type',
        'location_id',
        'status',
        'petition_reason',
        'request_appointment',
        'response_message',
        'responded_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'request_appointment' => 'boolean',
            'responded_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * The user who filed the petition.
     */
    public function petitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petitioner_id');
    }

    /**
     * The player role being challenged.
     */
    public function targetPlayerRole(): BelongsTo
    {
        return $this->belongsTo(PlayerRole::class, 'target_player_role_id');
    }

    /**
     * The authority figure who reviews the petition.
     */
    public function authority(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authority_user_id');
    }

    /**
     * Scope to pending petitions.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to non-expired petitions.
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope to petitions for a specific authority.
     */
    public function scopeForAuthority(Builder $query, int $userId): Builder
    {
        return $query->where('authority_user_id', $userId);
    }

    /**
     * Check if petition is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if petition has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Approve the petition.
     */
    public function approve(?string $responseMessage = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'response_message' => $responseMessage,
            'responded_at' => now(),
        ]);
    }

    /**
     * Deny the petition.
     */
    public function deny(?string $responseMessage = null): void
    {
        $this->update([
            'status' => self::STATUS_DENIED,
            'response_message' => $responseMessage,
            'responded_at' => now(),
        ]);
    }

    /**
     * Withdraw the petition.
     */
    public function withdraw(): void
    {
        $this->update([
            'status' => self::STATUS_WITHDRAWN,
        ]);
    }
}

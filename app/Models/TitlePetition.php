<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TitlePetition extends Model
{
    // Statuses
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DENIED = 'denied';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CEREMONY_PENDING = 'ceremony_pending';

    // Default expiration in days
    public const DEFAULT_EXPIRATION_DAYS = 30;

    protected $fillable = [
        'petitioner_id',
        'title_type_id',
        'petition_to_id',
        'domain_type',
        'domain_id',
        'status',
        'petition_message',
        'is_purchase',
        'gold_offered',
        'response_message',
        'responded_at',
        'ceremony_required',
        'ceremony_completed',
        'ceremony_scheduled_at',
        'ceremony_completed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_purchase' => 'boolean',
            'gold_offered' => 'integer',
            'responded_at' => 'datetime',
            'ceremony_required' => 'boolean',
            'ceremony_completed' => 'boolean',
            'ceremony_scheduled_at' => 'datetime',
            'ceremony_completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * The user petitioning for the title.
     */
    public function petitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petitioner_id');
    }

    /**
     * The title type being petitioned for.
     */
    public function titleType(): BelongsTo
    {
        return $this->belongsTo(TitleType::class);
    }

    /**
     * The user being petitioned (the potential grantor).
     */
    public function petitionTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petition_to_id');
    }

    /**
     * The domain this petition is for (polymorphic).
     */
    public function domain(): MorphTo
    {
        return $this->morphTo('domain', 'domain_type', 'domain_id');
    }

    /**
     * Scope to pending petitions.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to petitions awaiting ceremony.
     */
    public function scopeAwaitingCeremony(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CEREMONY_PENDING)
            ->where('ceremony_completed', false);
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
     * Scope to petitions for a specific user (as grantor).
     */
    public function scopeForGrantor(Builder $query, int $userId): Builder
    {
        return $query->where('petition_to_id', $userId);
    }

    /**
     * Scope to petitions by a specific user (as petitioner).
     */
    public function scopeByPetitioner(Builder $query, int $userId): Builder
    {
        return $query->where('petitioner_id', $userId);
    }

    /**
     * Check if petition is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if petition is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if petition is awaiting ceremony.
     */
    public function isAwaitingCeremony(): bool
    {
        return $this->status === self::STATUS_CEREMONY_PENDING && ! $this->ceremony_completed;
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
            'status' => $this->ceremony_required ? self::STATUS_CEREMONY_PENDING : self::STATUS_APPROVED,
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

    /**
     * Mark ceremony as completed.
     */
    public function completeCeremony(): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'ceremony_completed' => true,
            'ceremony_completed_at' => now(),
        ]);
    }

    /**
     * Schedule the ceremony.
     */
    public function scheduleCeremony(\DateTime $scheduledAt): void
    {
        $this->update([
            'ceremony_scheduled_at' => $scheduledAt,
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnnoblementRequest extends Model
{
    use HasFactory;

    /**
     * Request types.
     */
    public const TYPE_ROYAL_DECREE = 'royal_decree';
    public const TYPE_MILITARY_SERVICE = 'military_service';
    public const TYPE_MARRIAGE = 'marriage';
    public const TYPE_PURCHASE = 'purchase';

    /**
     * Statuses.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DENIED = 'denied';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Cost to purchase nobility (in gold).
     */
    public const PURCHASE_COST = 1000000;

    protected $fillable = [
        'requester_id',
        'king_id',
        'kingdom_id',
        'request_type',
        'gold_offered',
        'spouse_id',
        'reason',
        'status',
        'response_message',
        'responded_at',
        'title_granted',
    ];

    protected function casts(): array
    {
        return [
            'gold_offered' => 'integer',
            'responded_at' => 'datetime',
        ];
    }

    /**
     * Get the requester.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Get the king who can grant nobility.
     */
    public function king(): BelongsTo
    {
        return $this->belongsTo(User::class, 'king_id');
    }

    /**
     * Get the kingdom.
     */
    public function kingdom(): BelongsTo
    {
        return $this->belongsTo(Kingdom::class);
    }

    /**
     * Get the spouse (for marriage requests).
     */
    public function spouse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'spouse_id');
    }

    /**
     * Check if request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Get the request type display name.
     */
    public function getTypeDisplayAttribute(): string
    {
        return match ($this->request_type) {
            self::TYPE_ROYAL_DECREE => 'Royal Decree',
            self::TYPE_MILITARY_SERVICE => 'Military Service',
            self::TYPE_MARRIAGE => 'Marriage into Nobility',
            self::TYPE_PURCHASE => 'Purchase Title',
            default => 'Unknown',
        };
    }

    /**
     * Scope to pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to requests for a specific king.
     */
    public function scopeForKing($query, int $kingId)
    {
        return $query->where('king_id', $kingId);
    }
}

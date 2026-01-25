<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManumissionRequest extends Model
{
    use HasFactory;

    /**
     * Request types.
     */
    public const TYPE_DECREE = 'decree';
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_MILITARY_SERVICE = 'military_service';
    public const TYPE_EXCEPTIONAL_SERVICE = 'exceptional_service';

    /**
     * Statuses.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DENIED = 'denied';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Cost to purchase freedom (in gold).
     */
    public const PURCHASE_COST = 100000;

    protected $fillable = [
        'serf_id',
        'baron_id',
        'barony_id',
        'request_type',
        'gold_offered',
        'reason',
        'status',
        'response_message',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'gold_offered' => 'integer',
            'responded_at' => 'datetime',
        ];
    }

    /**
     * Get the serf requesting freedom.
     */
    public function serf(): BelongsTo
    {
        return $this->belongsTo(User::class, 'serf_id');
    }

    /**
     * Get the baron who can grant freedom.
     */
    public function baron(): BelongsTo
    {
        return $this->belongsTo(User::class, 'baron_id');
    }

    /**
     * Get the barony.
     */
    public function barony(): BelongsTo
    {
        return $this->belongsTo(Barony::class);
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
     * Check if request is denied.
     */
    public function isDenied(): bool
    {
        return $this->status === self::STATUS_DENIED;
    }

    /**
     * Get the request type display name.
     */
    public function getTypeDisplayAttribute(): string
    {
        return match ($this->request_type) {
            self::TYPE_DECREE => 'Baron\'s Decree',
            self::TYPE_PURCHASE => 'Purchase Freedom',
            self::TYPE_MILITARY_SERVICE => 'Military Service',
            self::TYPE_EXCEPTIONAL_SERVICE => 'Exceptional Service',
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
     * Scope to requests for a specific baron.
     */
    public function scopeForBaron($query, int $baronId)
    {
        return $query->where('baron_id', $baronId);
    }
}

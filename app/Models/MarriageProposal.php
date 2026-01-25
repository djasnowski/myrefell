<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarriageProposal extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_WITHDRAWN = 'withdrawn';
    const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'proposer_member_id', 'proposed_member_id', 'proposer_guardian_id',
        'proposed_guardian_id', 'status', 'offered_dowry', 'offered_items',
        'requested_terms', 'message', 'response_message', 'expires_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'offered_items' => 'array',
            'requested_terms' => 'array',
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(DynastyMember::class, 'proposer_member_id');
    }

    public function proposed(): BelongsTo
    {
        return $this->belongsTo(DynastyMember::class, 'proposed_member_id');
    }

    public function proposerGuardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposer_guardian_id');
    }

    public function proposedGuardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_guardian_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function canRespond(): bool
    {
        return $this->isPending() && !$this->isExpired();
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}

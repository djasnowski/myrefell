<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerMail extends Model
{
    use HasFactory;

    public const MAIL_COST = 5;

    public const MAX_SUBJECT = 100;

    public const MAX_BODY = 1000;

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'subject',
        'body',
        'is_read',
        'read_at',
        'is_deleted_by_sender',
        'is_deleted_by_recipient',
        'gold_cost',
        'is_carrier_pigeon',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'is_deleted_by_sender' => 'boolean',
            'is_deleted_by_recipient' => 'boolean',
            'gold_cost' => 'integer',
            'is_carrier_pigeon' => 'boolean',
        ];
    }

    /**
     * The user who sent this mail.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * The user who received this mail.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Scope to inbox for a user (received, not deleted by recipient).
     */
    public function scopeForInbox(Builder $query, int $userId): Builder
    {
        return $query->where('recipient_id', $userId)
            ->where('is_deleted_by_recipient', false);
    }

    /**
     * Scope to sent mail for a user (sent, not deleted by sender).
     */
    public function scopeForSent(Builder $query, int $userId): Builder
    {
        return $query->where('sender_id', $userId)
            ->where('is_deleted_by_sender', false);
    }

    /**
     * Scope to unread mail.
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * Mark this mail as read.
     */
    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Soft-delete from sender's view.
     */
    public function deleteForSender(): void
    {
        $this->update(['is_deleted_by_sender' => true]);
    }

    /**
     * Soft-delete from recipient's view.
     */
    public function deleteForRecipient(): void
    {
        $this->update(['is_deleted_by_recipient' => true]);
    }
}

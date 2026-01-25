<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    /**
     * Channel types.
     */
    public const CHANNEL_LOCATION = 'location';
    public const CHANNEL_PRIVATE = 'private';

    /**
     * Location types for location channels.
     */
    public const LOCATION_VILLAGE = 'village';
    public const LOCATION_CASTLE = 'castle';
    public const LOCATION_KINGDOM = 'kingdom';

    protected $fillable = [
        'sender_id',
        'channel_type',
        'channel_id',
        'channel_location_type',
        'content',
        'is_deleted',
        'deleted_by_user_id',
        'deleted_at',
        'deletion_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_deleted' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the message sender.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the user who deleted this message.
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by_user_id');
    }

    /**
     * Check if this is a location channel message.
     */
    public function isLocationMessage(): bool
    {
        return $this->channel_type === self::CHANNEL_LOCATION;
    }

    /**
     * Check if this is a private message.
     */
    public function isPrivateMessage(): bool
    {
        return $this->channel_type === self::CHANNEL_PRIVATE;
    }

    /**
     * Get the location model for location messages.
     */
    public function getLocationAttribute(): Model|null
    {
        if (!$this->isLocationMessage()) {
            return null;
        }

        return match ($this->channel_location_type) {
            self::LOCATION_VILLAGE => Village::find($this->channel_id),
            self::LOCATION_CASTLE => Castle::find($this->channel_id),
            self::LOCATION_KINGDOM => Kingdom::find($this->channel_id),
            default => null,
        };
    }

    /**
     * Get the recipient for private messages.
     */
    public function getRecipientAttribute(): ?User
    {
        if (!$this->isPrivateMessage()) {
            return null;
        }

        return User::find($this->channel_id);
    }

    /**
     * Soft delete the message (moderation).
     */
    public function moderatorDelete(User $moderator, string $reason = null): void
    {
        $this->update([
            'is_deleted' => true,
            'deleted_by_user_id' => $moderator->id,
            'deleted_at' => now(),
            'deletion_reason' => $reason,
        ]);
    }

    /**
     * Scope to visible messages (not deleted).
     */
    public function scopeVisible($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Scope to location channel messages.
     */
    public function scopeInLocationChannel($query, string $locationType, int $locationId)
    {
        return $query->where('channel_type', self::CHANNEL_LOCATION)
            ->where('channel_location_type', $locationType)
            ->where('channel_id', $locationId);
    }

    /**
     * Scope to private messages between two users.
     */
    public function scopePrivateBetween($query, int $userId1, int $userId2)
    {
        return $query->where('channel_type', self::CHANNEL_PRIVATE)
            ->where(function ($q) use ($userId1, $userId2) {
                $q->where(function ($q2) use ($userId1, $userId2) {
                    $q2->where('sender_id', $userId1)
                        ->where('channel_id', $userId2);
                })->orWhere(function ($q2) use ($userId1, $userId2) {
                    $q2->where('sender_id', $userId2)
                        ->where('channel_id', $userId1);
                });
            });
    }
}

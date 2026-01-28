<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlessingRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DENIED = 'denied';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'blessing_type_id',
        'location_type',
        'location_id',
        'status',
        'handled_by',
        'message',
        'denial_reason',
        'handled_at',
        'expires_at',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user who requested the blessing.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the blessing type requested.
     */
    public function blessingType(): BelongsTo
    {
        return $this->belongsTo(BlessingType::class);
    }

    /**
     * Get the priest/healer who handled this request.
     */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /**
     * Scope to pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to requests at a specific location.
     */
    public function scopeAtLocation($query, string $locationType, int $locationId)
    {
        return $query->where('location_type', $locationType)
            ->where('location_id', $locationId);
    }

    /**
     * Check if request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Approve the blessing request.
     */
    public function approve(User $handler): PlayerBlessing
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'handled_by' => $handler->id,
            'handled_at' => now(),
        ]);

        // Create the actual blessing
        return PlayerBlessing::create([
            'user_id' => $this->user_id,
            'blessing_type_id' => $this->blessing_type_id,
            'granted_by' => $handler->id,
            'location_type' => $this->location_type,
            'location_id' => $this->location_id,
            'expires_at' => now()->addMinutes($this->blessingType->duration_minutes),
        ]);
    }

    /**
     * Deny the blessing request.
     */
    public function deny(User $handler, ?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_DENIED,
            'handled_by' => $handler->id,
            'handled_at' => now(),
            'denial_reason' => $reason,
        ]);
    }
}

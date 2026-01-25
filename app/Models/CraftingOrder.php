<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CraftingOrder extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    public const FULFILLMENT_NPC = 'npc';
    public const FULFILLMENT_PLAYER = 'player';

    /**
     * Tardiness threshold in minutes for player crafters.
     */
    public const TARDINESS_THRESHOLD_MINUTES = 10;

    protected $fillable = [
        'customer_id',
        'crafter_id',
        'recipe_id',
        'quantity',
        'location_type',
        'location_id',
        'gold_cost',
        'crafter_payment',
        'status',
        'fulfillment_type',
        'accepted_at',
        'due_at',
        'completed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'gold_cost' => 'integer',
            'crafter_payment' => 'integer',
            'accepted_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the customer who placed the order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the player crafter (if any).
     */
    public function crafter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'crafter_id');
    }

    /**
     * Get the location where the order was placed.
     */
    public function getLocationAttribute(): Model|null
    {
        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'castle' => Castle::find($this->location_id),
            'town' => Town::find($this->location_id),
            default => null,
        };
    }

    /**
     * Check if order is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if order is accepted.
     */
    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Check if order is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if order is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if order is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    /**
     * Check if order is fulfilled by NPC.
     */
    public function isNpcOrder(): bool
    {
        return $this->fulfillment_type === self::FULFILLMENT_NPC;
    }

    /**
     * Check if order is fulfilled by player.
     */
    public function isPlayerOrder(): bool
    {
        return $this->fulfillment_type === self::FULFILLMENT_PLAYER;
    }

    /**
     * Check if a player order is tardy.
     */
    public function isTardy(): bool
    {
        if (! $this->isAccepted() || ! $this->isPlayerOrder() || ! $this->due_at) {
            return false;
        }

        return now()->gt($this->due_at);
    }

    /**
     * Get minutes until due.
     */
    public function getMinutesUntilDueAttribute(): ?int
    {
        if (! $this->due_at) {
            return null;
        }

        $diff = now()->diffInMinutes($this->due_at, false);

        return max(0, (int) $diff);
    }

    /**
     * Scope to get orders at a specific location.
     */
    public function scopeAtLocation($query, string $locationType, int $locationId)
    {
        return $query->where('location_type', $locationType)
            ->where('location_id', $locationId);
    }

    /**
     * Scope to get pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get accepted orders.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    /**
     * Scope to get orders for a specific crafter.
     */
    public function scopeForCrafter($query, int $crafterId)
    {
        return $query->where('crafter_id', $crafterId);
    }

    /**
     * Scope to get orders for a specific customer.
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}

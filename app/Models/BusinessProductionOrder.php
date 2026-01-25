<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessProductionOrder extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'player_business_id',
        'item_id',
        'quantity',
        'quantity_completed',
        'status',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'quantity_completed' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the business this order belongs to.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(PlayerBusiness::class, 'player_business_id');
    }

    /**
     * Get the item being produced.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Check if order is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if order is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if order is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Get completion percentage.
     */
    public function getCompletionPercentageAttribute(): int
    {
        if ($this->quantity === 0) {
            return 0;
        }

        return (int) round(($this->quantity_completed / $this->quantity) * 100);
    }

    /**
     * Get remaining quantity to produce.
     */
    public function getRemainingAttribute(): int
    {
        return max(0, $this->quantity - $this->quantity_completed);
    }
}

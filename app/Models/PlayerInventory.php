<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerInventory extends Model
{
    use HasFactory;

    public const MAX_SLOTS = 50;

    protected $table = 'player_inventory';

    protected $fillable = [
        'player_id',
        'item_id',
        'slot_number',
        'quantity',
        'is_equipped',
        'weeks_stored',
        'last_decay_at',
    ];

    protected function casts(): array
    {
        return [
            'slot_number' => 'integer',
            'quantity' => 'integer',
            'is_equipped' => 'boolean',
            'weeks_stored' => 'integer',
            'last_decay_at' => 'datetime',
        ];
    }

    /**
     * Get the player who owns this inventory slot.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    /**
     * Get the item in this slot.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Check if this slot can accept more of the same item.
     */
    public function canStack(int $amount = 1): bool
    {
        if (! $this->item->stackable) {
            return false;
        }

        return ($this->quantity + $amount) <= $this->item->max_stack;
    }

    /**
     * Add quantity to this stack.
     */
    public function addQuantity(int $amount): bool
    {
        if (! $this->canStack($amount)) {
            return false;
        }

        $this->increment('quantity', $amount);

        return true;
    }

    /**
     * Remove quantity from this stack.
     */
    public function removeQuantity(int $amount): bool
    {
        if ($this->quantity < $amount) {
            return false;
        }

        $this->decrement('quantity', $amount);

        // Delete the slot if empty
        if ($this->quantity <= 0) {
            $this->delete();
        }

        return true;
    }

    /**
     * Increment the weeks stored counter.
     */
    public function incrementWeeksStored(): bool
    {
        $this->weeks_stored++;

        return $this->save();
    }

    /**
     * Reset the weeks stored counter (when new items are added).
     */
    public function resetWeeksStored(): bool
    {
        $this->weeks_stored = 0;

        return $this->save();
    }

    /**
     * Check if the item has been stored long enough to spoil.
     */
    public function hasSpoiled(): bool
    {
        $item = $this->item;
        if (! $item || ! $item->isPerishable() || $item->spoil_after_weeks === null) {
            return false;
        }

        return $this->weeks_stored >= $item->spoil_after_weeks;
    }

    /**
     * Scope to get inventory slots with perishable items.
     */
    public function scopeWithPerishableItems($query)
    {
        return $query->whereHas('item', function ($q) {
            $q->where('is_perishable', true);
        });
    }
}

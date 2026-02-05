<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DungeonLootStorage extends Model
{
    protected $table = 'dungeon_loot_storage';

    protected $fillable = [
        'user_id',
        'kingdom_id',
        'item_id',
        'quantity',
        'stored_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'stored_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the user who owns this loot.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the kingdom where this loot was collected.
     */
    public function kingdom(): BelongsTo
    {
        return $this->belongsTo(Kingdom::class);
    }

    /**
     * Get the item stored.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by kingdom.
     */
    public function scopeInKingdom(Builder $query, int $kingdomId): Builder
    {
        return $query->where('kingdom_id', $kingdomId);
    }

    /**
     * Scope to filter out expired loot.
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Add loot to storage, merging with existing if same item exists.
     */
    public static function addLoot(int $userId, int $kingdomId, int $itemId, int $quantity): self
    {
        $existing = self::where('user_id', $userId)
            ->where('kingdom_id', $kingdomId)
            ->where('item_id', $itemId)
            ->first();

        if ($existing) {
            $existing->increment('quantity', $quantity);
            // Extend expiry when more loot is added
            $existing->update([
                'stored_at' => now(),
                'expires_at' => now()->addWeeks(2),
            ]);

            return $existing;
        }

        return self::create([
            'user_id' => $userId,
            'kingdom_id' => $kingdomId,
            'item_id' => $itemId,
            'quantity' => $quantity,
            'stored_at' => now(),
            'expires_at' => now()->addWeeks(2),
        ]);
    }

    /**
     * Check if this loot has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Get the number of days until expiry.
     */
    public function daysUntilExpiry(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return (int) now()->diffInDays($this->expires_at);
    }
}

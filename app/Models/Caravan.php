<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caravan extends Model
{
    use HasFactory;

    public const STATUS_PREPARING = 'preparing';
    public const STATUS_TRAVELING = 'traveling';
    public const STATUS_ARRIVED = 'arrived';
    public const STATUS_RETURNING = 'returning';
    public const STATUS_DISBANDED = 'disbanded';
    public const STATUS_DESTROYED = 'destroyed';

    protected $fillable = [
        'name',
        'owner_id',
        'trade_route_id',
        'current_location_type',
        'current_location_id',
        'destination_type',
        'destination_id',
        'status',
        'capacity',
        'guards',
        'gold_carried',
        'travel_progress',
        'travel_total',
        'departed_at',
        'arrived_at',
        'is_npc',
        'npc_merchant_name',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'guards' => 'integer',
            'gold_carried' => 'integer',
            'travel_progress' => 'integer',
            'travel_total' => 'integer',
            'departed_at' => 'datetime',
            'arrived_at' => 'datetime',
            'is_npc' => 'boolean',
        ];
    }

    /**
     * Get the owner (player).
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the trade route.
     */
    public function tradeRoute(): BelongsTo
    {
        return $this->belongsTo(TradeRoute::class);
    }

    /**
     * Get goods carried by this caravan.
     */
    public function goods(): HasMany
    {
        return $this->hasMany(CaravanGoods::class);
    }

    /**
     * Get events for this caravan.
     */
    public function events(): HasMany
    {
        return $this->hasMany(CaravanEvent::class);
    }

    /**
     * Get tariff collections for this caravan.
     */
    public function tariffCollections(): HasMany
    {
        return $this->hasMany(TariffCollection::class);
    }

    /**
     * Get the current location.
     */
    public function getCurrentLocationAttribute(): ?Model
    {
        return match ($this->current_location_type) {
            'village' => Village::find($this->current_location_id),
            'town' => Town::find($this->current_location_id),
            default => null,
        };
    }

    /**
     * Get the destination.
     */
    public function getDestinationAttribute(): ?Model
    {
        return match ($this->destination_type) {
            'village' => Village::find($this->destination_id),
            'town' => Town::find($this->destination_id),
            default => null,
        };
    }

    /**
     * Get total goods count.
     */
    public function getTotalGoodsAttribute(): int
    {
        return $this->goods()->sum('quantity');
    }

    /**
     * Get remaining capacity.
     */
    public function getRemainingCapacityAttribute(): int
    {
        return max(0, $this->capacity - $this->total_goods);
    }

    /**
     * Get travel progress percentage.
     */
    public function getTravelProgressPercentAttribute(): int
    {
        if ($this->travel_total <= 0) {
            return 0;
        }
        return (int) (($this->travel_progress / $this->travel_total) * 100);
    }

    /**
     * Check if caravan is traveling.
     */
    public function isTraveling(): bool
    {
        return in_array($this->status, [self::STATUS_TRAVELING, self::STATUS_RETURNING]);
    }

    /**
     * Check if caravan can depart.
     */
    public function canDepart(): bool
    {
        return $this->status === self::STATUS_PREPARING && $this->total_goods > 0;
    }

    /**
     * Check if caravan has arrived at destination.
     */
    public function hasArrived(): bool
    {
        return $this->travel_progress >= $this->travel_total;
    }

    /**
     * Get merchant name (owner username or NPC name).
     */
    public function getMerchantNameAttribute(): string
    {
        if ($this->is_npc) {
            return $this->npc_merchant_name ?? 'Unknown Merchant';
        }
        return $this->owner?->username ?? 'Unknown';
    }

    /**
     * Scope to active caravans.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PREPARING,
            self::STATUS_TRAVELING,
            self::STATUS_ARRIVED,
            self::STATUS_RETURNING,
        ]);
    }

    /**
     * Scope to traveling caravans.
     */
    public function scopeTraveling($query)
    {
        return $query->whereIn('status', [self::STATUS_TRAVELING, self::STATUS_RETURNING]);
    }

    /**
     * Scope to NPC caravans.
     */
    public function scopeNpc($query)
    {
        return $query->where('is_npc', true);
    }

    /**
     * Scope to player caravans.
     */
    public function scopePlayer($query)
    {
        return $query->where('is_npc', false);
    }

    /**
     * Scope to caravans at a location.
     */
    public function scopeAtLocation($query, string $type, int $id)
    {
        return $query->where('current_location_type', $type)
            ->where('current_location_id', $id);
    }
}

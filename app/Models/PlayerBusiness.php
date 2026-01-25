<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlayerBusiness extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'user_id',
        'business_type_id',
        'name',
        'location_type',
        'location_id',
        'status',
        'treasury',
        'total_revenue',
        'total_expenses',
        'reputation',
        'last_production_at',
        'last_upkeep_at',
        'established_at',
    ];

    protected function casts(): array
    {
        return [
            'treasury' => 'integer',
            'total_revenue' => 'integer',
            'total_expenses' => 'integer',
            'reputation' => 'integer',
            'last_production_at' => 'datetime',
            'last_upkeep_at' => 'datetime',
            'established_at' => 'datetime',
        ];
    }

    /**
     * Get the owner of this business.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the business type.
     */
    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    /**
     * Get all employees of this business.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(BusinessEmployee::class);
    }

    /**
     * Get active employees.
     */
    public function activeEmployees(): HasMany
    {
        return $this->employees()->where('status', 'employed');
    }

    /**
     * Get the business inventory.
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(BusinessInventory::class);
    }

    /**
     * Get all transactions for this business.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(BusinessTransaction::class);
    }

    /**
     * Get production orders.
     */
    public function productionOrders(): HasMany
    {
        return $this->hasMany(BusinessProductionOrder::class);
    }

    /**
     * Get the location model.
     */
    public function getLocationAttribute(): Model|null
    {
        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'town' => Town::find($this->location_id),
            'barony' => Barony::find($this->location_id),
            default => null,
        };
    }

    /**
     * Get the location name.
     */
    public function getLocationNameAttribute(): string
    {
        return $this->location?->name ?? 'Unknown Location';
    }

    /**
     * Check if business is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if business is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * Deposit gold into the business treasury.
     */
    public function deposit(int $amount, string $description, ?int $relatedUserId = null): BusinessTransaction
    {
        $this->increment('treasury', $amount);
        $this->increment('total_revenue', $amount);
        $this->refresh();

        return $this->transactions()->create([
            'type' => 'deposit',
            'amount' => $amount,
            'balance_after' => $this->treasury,
            'description' => $description,
            'related_user_id' => $relatedUserId,
        ]);
    }

    /**
     * Withdraw gold from the business treasury.
     */
    public function withdraw(int $amount, string $type, string $description, ?int $relatedUserId = null, ?int $relatedItemId = null): ?BusinessTransaction
    {
        if ($this->treasury < $amount) {
            return null;
        }

        $this->decrement('treasury', $amount);
        $this->increment('total_expenses', $amount);
        $this->refresh();

        return $this->transactions()->create([
            'type' => $type,
            'amount' => -$amount,
            'balance_after' => $this->treasury,
            'description' => $description,
            'related_user_id' => $relatedUserId,
            'related_item_id' => $relatedItemId,
        ]);
    }

    /**
     * Record a sale transaction.
     */
    public function recordSale(int $amount, string $description, ?int $relatedUserId = null, ?int $relatedItemId = null): BusinessTransaction
    {
        $this->increment('treasury', $amount);
        $this->increment('total_revenue', $amount);
        $this->refresh();

        return $this->transactions()->create([
            'type' => 'sale',
            'amount' => $amount,
            'balance_after' => $this->treasury,
            'description' => $description,
            'related_user_id' => $relatedUserId,
            'related_item_id' => $relatedItemId,
        ]);
    }

    /**
     * Get the employee count.
     */
    public function getEmployeeCountAttribute(): int
    {
        return $this->activeEmployees()->count();
    }

    /**
     * Check if business can hire more employees.
     */
    public function canHireMore(): bool
    {
        return $this->employee_count < $this->businessType->max_employees;
    }

    /**
     * Get an inventory item by item ID.
     */
    public function getInventoryItem(int $itemId): ?BusinessInventory
    {
        return $this->inventory()->where('item_id', $itemId)->first();
    }

    /**
     * Add items to inventory.
     */
    public function addInventory(int $itemId, int $quantity): BusinessInventory
    {
        $inv = $this->inventory()->firstOrCreate(
            ['item_id' => $itemId],
            ['quantity' => 0]
        );

        $inv->increment('quantity', $quantity);

        return $inv->fresh();
    }

    /**
     * Remove items from inventory.
     */
    public function removeInventory(int $itemId, int $quantity): bool
    {
        $inv = $this->getInventoryItem($itemId);

        if (! $inv || $inv->quantity < $quantity) {
            return false;
        }

        $inv->decrement('quantity', $quantity);

        if ($inv->quantity <= 0) {
            $inv->delete();
        }

        return true;
    }

    /**
     * Check if business has enough inventory.
     */
    public function hasInventory(int $itemId, int $quantity): bool
    {
        $inv = $this->getInventoryItem($itemId);

        return $inv && $inv->quantity >= $quantity;
    }
}

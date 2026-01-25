<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocationTreasury extends Model
{
    protected $fillable = [
        'location_type',
        'location_id',
        'balance',
        'total_collected',
        'total_distributed',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
            'total_collected' => 'integer',
            'total_distributed' => 'integer',
        ];
    }

    /**
     * Get all transactions for this treasury.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(TreasuryTransaction::class);
    }

    /**
     * Get recent transactions.
     */
    public function recentTransactions(int $limit = 10): HasMany
    {
        return $this->transactions()->orderByDesc('created_at')->limit($limit);
    }

    /**
     * Get the location model.
     */
    public function getLocationAttribute(): Model|null
    {
        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'castle' => Castle::find($this->location_id),
            'kingdom' => Kingdom::find($this->location_id),
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
     * Deposit gold into the treasury.
     */
    public function deposit(int $amount, string $type, string $description, ?int $relatedUserId = null, ?string $relatedLocationType = null, ?int $relatedLocationId = null): TreasuryTransaction
    {
        $this->increment('balance', $amount);
        $this->increment('total_collected', $amount);
        $this->refresh();

        return $this->transactions()->create([
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $this->balance,
            'description' => $description,
            'related_user_id' => $relatedUserId,
            'related_location_type' => $relatedLocationType,
            'related_location_id' => $relatedLocationId,
        ]);
    }

    /**
     * Withdraw gold from the treasury.
     */
    public function withdraw(int $amount, string $type, string $description, ?int $relatedUserId = null, ?string $relatedLocationType = null, ?int $relatedLocationId = null): ?TreasuryTransaction
    {
        if ($this->balance < $amount) {
            return null;
        }

        $this->decrement('balance', $amount);
        $this->increment('total_distributed', $amount);
        $this->refresh();

        return $this->transactions()->create([
            'type' => $type,
            'amount' => -$amount,
            'balance_after' => $this->balance,
            'description' => $description,
            'related_user_id' => $relatedUserId,
            'related_location_type' => $relatedLocationType,
            'related_location_id' => $relatedLocationId,
        ]);
    }

    /**
     * Get or create a treasury for a location.
     */
    public static function getOrCreate(string $locationType, int $locationId): self
    {
        return static::firstOrCreate(
            [
                'location_type' => $locationType,
                'location_id' => $locationId,
            ],
            [
                'balance' => 0,
                'total_collected' => 0,
                'total_distributed' => 0,
            ]
        );
    }
}

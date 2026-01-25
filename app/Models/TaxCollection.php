<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxCollection extends Model
{
    public const TYPE_INCOME = 'income';
    public const TYPE_PROPERTY = 'property';
    public const TYPE_TRADE = 'trade';
    public const TYPE_ROLE_SALARY = 'role_salary';
    public const TYPE_UPSTREAM = 'upstream'; // village -> castle -> kingdom

    protected $fillable = [
        'payer_user_id',
        'payer_location_type',
        'payer_location_id',
        'receiver_location_type',
        'receiver_location_id',
        'amount',
        'tax_type',
        'description',
        'tax_period',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'tax_period' => 'date',
        ];
    }

    /**
     * Get the user who paid this tax (if player tax).
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_user_id');
    }

    /**
     * Get the receiver location model.
     */
    public function getReceiverLocationAttribute(): Model|null
    {
        return match ($this->receiver_location_type) {
            'village' => Village::find($this->receiver_location_id),
            'castle' => Castle::find($this->receiver_location_id),
            'kingdom' => Kingdom::find($this->receiver_location_id),
            default => null,
        };
    }

    /**
     * Get the receiver location name.
     */
    public function getReceiverLocationNameAttribute(): string
    {
        return $this->receiver_location?->name ?? 'Unknown Location';
    }

    /**
     * Get the payer location model (for upstream taxes).
     */
    public function getPayerLocationAttribute(): Model|null
    {
        if (!$this->payer_location_type) {
            return null;
        }

        return match ($this->payer_location_type) {
            'village' => Village::find($this->payer_location_id),
            'castle' => Castle::find($this->payer_location_id),
            default => null,
        };
    }
}

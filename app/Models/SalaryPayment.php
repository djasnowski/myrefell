<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryPayment extends Model
{
    protected $fillable = [
        'user_id',
        'player_role_id',
        'amount',
        'source_location_type',
        'source_location_id',
        'pay_period',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'pay_period' => 'date',
        ];
    }

    /**
     * Get the user who received the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the player role this payment is for.
     */
    public function playerRole(): BelongsTo
    {
        return $this->belongsTo(PlayerRole::class);
    }

    /**
     * Get the source location model.
     */
    public function getSourceLocationAttribute(): Model|null
    {
        return match ($this->source_location_type) {
            'village' => Village::find($this->source_location_id),
            'castle' => Castle::find($this->source_location_id),
            'kingdom' => Kingdom::find($this->source_location_id),
            default => null,
        };
    }

    /**
     * Get the source location name.
     */
    public function getSourceLocationNameAttribute(): string
    {
        return $this->source_location?->name ?? 'Unknown Location';
    }
}

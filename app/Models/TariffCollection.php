<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TariffCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'caravan_id',
        'trade_tariff_id',
        'amount_collected',
        'location_type',
        'location_id',
    ];

    protected function casts(): array
    {
        return [
            'amount_collected' => 'integer',
        ];
    }

    /**
     * Get the caravan.
     */
    public function caravan(): BelongsTo
    {
        return $this->belongsTo(Caravan::class);
    }

    /**
     * Get the tariff.
     */
    public function tradeTariff(): BelongsTo
    {
        return $this->belongsTo(TradeTariff::class);
    }

    /**
     * Get the location where tariff was collected.
     */
    public function getLocationAttribute(): ?Model
    {
        return match ($this->location_type) {
            'barony' => Barony::find($this->location_id),
            'kingdom' => Kingdom::find($this->location_id),
            default => null,
        };
    }
}

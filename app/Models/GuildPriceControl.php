<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuildPriceControl extends Model
{
    use HasFactory;

    protected $fillable = [
        'guild_id',
        'item_name',
        'min_price',
        'max_price',
        'min_quality',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_price' => 'integer',
            'max_price' => 'integer',
            'min_quality' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the guild.
     */
    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class);
    }

    /**
     * Check if a price is within the allowed range.
     */
    public function isPriceAllowed(int $price): bool
    {
        if (!$this->is_active) {
            return true;
        }

        if ($price < $this->min_price) {
            return false;
        }

        if ($this->max_price !== null && $price > $this->max_price) {
            return false;
        }

        return true;
    }
}

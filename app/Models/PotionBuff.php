<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PotionBuff extends Model
{
    protected $fillable = [
        'user_id',
        'buff_type',
        'bonus_percent',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'bonus_percent' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the buff is still active.
     */
    public function isActive(): bool
    {
        return $this->expires_at->isFuture();
    }

    /**
     * Scope to get only active buffs.
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }
}

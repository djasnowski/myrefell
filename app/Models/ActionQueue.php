<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActionQueue extends Model
{
    protected $fillable = [
        'user_id',
        'action_type',
        'action_params',
        'status',
        'total',
        'completed',
        'total_xp',
        'total_quantity',
        'item_name',
        'last_level_up',
        'stop_reason',
        'dismissed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action_params' => 'array',
            'last_level_up' => 'array',
            'dismissed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereNull('dismissed_at');
    }

    public function isInfinite(): bool
    {
        return $this->total === 0;
    }
}

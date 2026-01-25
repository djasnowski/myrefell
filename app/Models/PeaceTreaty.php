<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeaceTreaty extends Model
{
    use HasFactory;

    const TYPE_WHITE_PEACE = 'white_peace';
    const TYPE_SURRENDER = 'surrender';
    const TYPE_NEGOTIATED = 'negotiated';

    protected $fillable = [
        'war_id', 'treaty_type', 'winner_side', 'territory_changes',
        'gold_payment', 'prisoner_exchange', 'other_terms', 'truce_days',
        'signed_at', 'truce_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'territory_changes' => 'array',
            'other_terms' => 'array',
            'signed_at' => 'datetime',
            'truce_expires_at' => 'datetime',
        ];
    }

    public function war(): BelongsTo
    {
        return $this->belongsTo(War::class);
    }

    public function isTruceActive(): bool
    {
        return $this->truce_expires_at->isFuture();
    }

    public function getTruceDaysRemainingAttribute(): int
    {
        if ($this->truce_expires_at->isPast()) {
            return 0;
        }
        return now()->diffInDays($this->truce_expires_at);
    }

    public function scopeActiveTruce($query)
    {
        return $query->where('truce_expires_at', '>', now());
    }
}

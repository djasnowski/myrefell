<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DynastyEvent extends Model
{
    use HasFactory;

    const TYPE_BIRTH = 'birth';
    const TYPE_DEATH = 'death';
    const TYPE_MARRIAGE = 'marriage';
    const TYPE_DIVORCE = 'divorce';
    const TYPE_SUCCESSION = 'succession';
    const TYPE_ACHIEVEMENT = 'achievement';
    const TYPE_SCANDAL = 'scandal';
    const TYPE_ALLIANCE = 'alliance';
    const TYPE_INHERITANCE = 'inheritance';

    protected $fillable = [
        'dynasty_id', 'member_id', 'event_type', 'title', 'description',
        'prestige_change', 'metadata', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function dynasty(): BelongsTo
    {
        return $this->belongsTo(Dynasty::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(DynastyMember::class, 'member_id');
    }

    public function isPositive(): bool
    {
        return $this->prestige_change > 0;
    }

    public function isNegative(): bool
    {
        return $this->prestige_change < 0;
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('occurred_at', '>=', now()->subDays($days));
    }
}

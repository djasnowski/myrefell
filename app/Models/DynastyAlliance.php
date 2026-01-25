<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DynastyAlliance extends Model
{
    use HasFactory;

    const TYPE_MARRIAGE = 'marriage';
    const TYPE_PACT = 'pact';
    const TYPE_BLOOD_OATH = 'blood_oath';

    const STATUS_ACTIVE = 'active';
    const STATUS_BROKEN = 'broken';
    const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'dynasty1_id', 'dynasty2_id', 'marriage_id', 'alliance_type', 'status',
        'terms', 'formed_at', 'expires_at', 'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'terms' => 'array',
            'formed_at' => 'date',
            'expires_at' => 'date',
            'ended_at' => 'date',
        ];
    }

    public function dynasty1(): BelongsTo
    {
        return $this->belongsTo(Dynasty::class, 'dynasty1_id');
    }

    public function dynasty2(): BelongsTo
    {
        return $this->belongsTo(Dynasty::class, 'dynasty2_id');
    }

    public function marriage(): BelongsTo
    {
        return $this->belongsTo(Marriage::class);
    }

    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function getOtherDynasty(Dynasty $dynasty): ?Dynasty
    {
        if ($this->dynasty1_id === $dynasty->id) {
            return $this->dynasty2;
        }
        if ($this->dynasty2_id === $dynasty->id) {
            return $this->dynasty1;
        }
        return null;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeInvolving($query, int $dynastyId)
    {
        return $query->where(function ($q) use ($dynastyId) {
            $q->where('dynasty1_id', $dynastyId)
                ->orWhere('dynasty2_id', $dynastyId);
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Outlaw extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CAPTURED = 'captured';
    public const STATUS_KILLED = 'killed';
    public const STATUS_PARDONED = 'pardoned';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'punishment_id',
        'declared_by_type',
        'declared_by_id',
        'reason',
        'status',
        'declared_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'declared_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function punishment(): BelongsTo
    {
        return $this->belongsTo(Punishment::class);
    }

    public function getDeclaredByLocation(): Model|null
    {
        return match ($this->declared_by_type) {
            'barony' => Barony::find($this->declared_by_id),
            'kingdom' => Kingdom::find($this->declared_by_id),
            default => null,
        };
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

    public function capture(): void
    {
        $this->update(['status' => self::STATUS_CAPTURED]);
        $this->punishment?->complete();
    }

    public function markKilled(): void
    {
        $this->update(['status' => self::STATUS_KILLED]);
        $this->punishment?->complete();
    }

    public function pardon(): void
    {
        $this->update(['status' => self::STATUS_PARDONED]);
        $this->punishment?->pardon($this->user);
    }

    public function expire(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
        $this->punishment?->complete();
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeInJurisdiction($query, string $type, int $id)
    {
        // Outlaw status in a barony also applies to its kingdom
        // Outlaw status in a kingdom applies everywhere in the kingdom
        return $query->where(function ($q) use ($type, $id) {
            $q->where('declared_by_type', $type)->where('declared_by_id', $id);

            if ($type === 'barony') {
                $barony = Barony::find($id);
                if ($barony) {
                    $q->orWhere(function ($q2) use ($barony) {
                        $q2->where('declared_by_type', 'kingdom')
                            ->where('declared_by_id', $barony->kingdom_id);
                    });
                }
            }
        });
    }

    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_CAPTURED => 'Captured',
            self::STATUS_KILLED => 'Killed',
            self::STATUS_PARDONED => 'Pardoned',
            self::STATUS_EXPIRED => 'Expired',
            default => 'Unknown',
        };
    }
}

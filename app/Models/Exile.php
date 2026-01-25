<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exile extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_PARDONED = 'pardoned';

    protected $fillable = [
        'user_id',
        'punishment_id',
        'exiled_from_type',
        'exiled_from_id',
        'reason',
        'status',
        'exiled_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'exiled_at' => 'datetime',
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

    public function getExiledFromLocation(): Model|null
    {
        return match ($this->exiled_from_type) {
            'village' => Village::find($this->exiled_from_id),
            'barony' => Barony::find($this->exiled_from_id),
            'kingdom' => Kingdom::find($this->exiled_from_id),
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

    public function isPermanent(): bool
    {
        return is_null($this->expires_at);
    }

    public function getRemainingDays(): ?int
    {
        if (!$this->isActive() || $this->isPermanent()) {
            return null;
        }

        return max(0, (int) now()->diffInDays($this->expires_at, false));
    }

    /**
     * Check if a user is banned from a specific location.
     */
    public function appliesToLocation(string $locationType, int $locationId): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        // Direct match
        if ($this->exiled_from_type === $locationType && $this->exiled_from_id === $locationId) {
            return true;
        }

        // Exile from kingdom applies to all baronies and villages in that kingdom
        if ($this->exiled_from_type === 'kingdom') {
            if ($locationType === 'barony') {
                $barony = Barony::find($locationId);
                return $barony && $barony->kingdom_id === $this->exiled_from_id;
            }
            if ($locationType === 'village') {
                $village = Village::find($locationId);
                if ($village && $village->barony) {
                    return $village->barony->kingdom_id === $this->exiled_from_id;
                }
            }
        }

        // Exile from barony applies to all villages in that barony
        if ($this->exiled_from_type === 'barony' && $locationType === 'village') {
            $village = Village::find($locationId);
            return $village && $village->barony_id === $this->exiled_from_id;
        }

        return false;
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

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_PARDONED => 'Pardoned',
            default => 'Unknown',
        };
    }

    public function getExileDescriptionAttribute(): string
    {
        $location = $this->getExiledFromLocation();
        $locationName = $location?->name ?? 'Unknown';

        $duration = $this->isPermanent() ? 'permanently' : "for {$this->getRemainingDays()} days";

        return "Exiled from {$locationName} ({$this->exiled_from_type}) {$duration}";
    }
}

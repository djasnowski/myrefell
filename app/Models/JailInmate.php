<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JailInmate extends Model
{
    protected $fillable = [
        'prisoner_id',
        'punishment_id',
        'jail_location_type',
        'jail_location_id',
        'jailed_at',
        'release_at',
        'released_at',
        'escaped',
    ];

    protected function casts(): array
    {
        return [
            'jailed_at' => 'datetime',
            'release_at' => 'datetime',
            'released_at' => 'datetime',
            'escaped' => 'boolean',
        ];
    }

    public function prisoner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prisoner_id');
    }

    public function punishment(): BelongsTo
    {
        return $this->belongsTo(Punishment::class);
    }

    public function getJailLocation(): Model|null
    {
        return match ($this->jail_location_type) {
            'village' => Village::find($this->jail_location_id),
            'barony' => Barony::find($this->jail_location_id),
            'kingdom' => Kingdom::find($this->jail_location_id),
            default => null,
        };
    }

    public function isServing(): bool
    {
        return is_null($this->released_at) && !$this->escaped;
    }

    public function isReleased(): bool
    {
        return !is_null($this->released_at);
    }

    public function hasEscaped(): bool
    {
        return $this->escaped;
    }

    public function shouldBeReleased(): bool
    {
        return $this->isServing() && $this->release_at->isPast();
    }

    public function getRemainingDays(): int
    {
        if (!$this->isServing()) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->release_at, false));
    }

    public function release(): void
    {
        $this->update(['released_at' => now()]);
        $this->punishment->complete();
    }

    public function escape(): void
    {
        $this->update(['escaped' => true]);
        $this->punishment->update(['status' => Punishment::STATUS_ESCAPED]);
    }

    public function scopeCurrentlyServing($query)
    {
        return $query->whereNull('released_at')->where('escaped', false);
    }

    public function scopeAtLocation($query, string $type, int $id)
    {
        return $query->where('jail_location_type', $type)->where('jail_location_id', $id);
    }

    public function scopeDueForRelease($query)
    {
        return $query->currentlyServing()->where('release_at', '<=', now());
    }
}

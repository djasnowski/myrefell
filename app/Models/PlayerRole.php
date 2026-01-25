<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerRole extends Model
{
    use HasFactory;

    /**
     * Role statuses.
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_RESIGNED = 'resigned';
    public const STATUS_REMOVED = 'removed';

    protected $fillable = [
        'user_id',
        'role_id',
        'location_type',
        'location_id',
        'status',
        'appointed_at',
        'expires_at',
        'removed_at',
        'appointed_by_user_id',
        'removed_by_user_id',
        'removal_reason',
        'total_salary_earned',
    ];

    protected function casts(): array
    {
        return [
            'appointed_at' => 'datetime',
            'expires_at' => 'datetime',
            'removed_at' => 'datetime',
            'total_salary_earned' => 'integer',
        ];
    }

    /**
     * Get the user who holds this role.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the role.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the user who appointed this role holder.
     */
    public function appointedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'appointed_by_user_id');
    }

    /**
     * Get the user who removed this role holder.
     */
    public function removedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by_user_id');
    }

    /**
     * Check if this role assignment is currently active.
     */
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

    /**
     * Check if this role has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get the location model.
     */
    public function getLocationAttribute(): Model|null
    {
        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'barony' => Barony::find($this->location_id),
            'kingdom' => Kingdom::find($this->location_id),
            default => null,
        };
    }

    /**
     * Get the location name.
     */
    public function getLocationNameAttribute(): string
    {
        return $this->location?->name ?? 'Unknown Location';
    }

    /**
     * Resign from this role.
     */
    public function resign(): void
    {
        $this->update([
            'status' => self::STATUS_RESIGNED,
            'removed_at' => now(),
        ]);
    }

    /**
     * Remove from this role.
     */
    public function remove(User $removedBy, string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_REMOVED,
            'removed_at' => now(),
            'removed_by_user_id' => $removedBy->id,
            'removal_reason' => $reason,
        ]);
    }

    /**
     * Suspend this role.
     */
    public function suspend(): void
    {
        $this->update([
            'status' => self::STATUS_SUSPENDED,
        ]);
    }

    /**
     * Reinstate this role.
     */
    public function reinstate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Pay salary to role holder.
     */
    public function paySalary(): int
    {
        if (!$this->isActive()) {
            return 0;
        }

        $salary = $this->role->salary;
        if ($salary > 0) {
            $this->user->increment('gold', $salary);
            $this->increment('total_salary_earned', $salary);
        }

        return $salary;
    }

    /**
     * Scope to active roles.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to roles at a specific location.
     */
    public function scopeAtLocation($query, string $locationType, int $locationId)
    {
        return $query->where('location_type', $locationType)
            ->where('location_id', $locationId);
    }
}

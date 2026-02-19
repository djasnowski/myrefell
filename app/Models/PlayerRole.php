<?php

namespace App\Models;

use App\Services\MigrationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'legitimacy',
        'months_in_office',
    ];

    protected function casts(): array
    {
        return [
            'appointed_at' => 'datetime',
            'expires_at' => 'datetime',
            'removed_at' => 'datetime',
            'total_salary_earned' => 'integer',
            'legitimacy' => 'integer',
            'months_in_office' => 'integer',
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
     * Get legitimacy events for this role.
     */
    public function legitimacyEvents(): HasMany
    {
        return $this->hasMany(LegitimacyEvent::class);
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
    public function getLocationAttribute(): ?Model
    {
        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'town' => Town::find($this->location_id),
            'barony' => Barony::find($this->location_id),
            'duchy' => Duchy::find($this->location_id),
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

        $this->clearLocationRuler();
        $this->autoApprovePendingMigrations();
    }

    /**
     * Remove from this role.
     */
    public function remove(User $removedBy, ?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_REMOVED,
            'removed_at' => now(),
            'removed_by_user_id' => $removedBy->id,
            'removal_reason' => $reason,
        ]);

        $this->clearLocationRuler();
        $this->autoApprovePendingMigrations();
    }

    /**
     * Clear the ruler user_id on the location model if this is a ruler role.
     */
    protected function clearLocationRuler(): void
    {
        $role = $this->role;
        if (! $role) {
            return;
        }

        $rulerColumn = match ($role->slug) {
            'king' => 'king_user_id',
            'baron' => 'baron_user_id',
            'mayor' => 'mayor_user_id',
            default => null,
        };

        if (! $rulerColumn) {
            return;
        }

        $model = match ($this->location_type) {
            'kingdom' => Kingdom::find($this->location_id),
            'barony' => Barony::find($this->location_id),
            'town' => Town::find($this->location_id),
            default => null,
        };

        if ($model) {
            $model->update([$rulerColumn => null]);
        }
    }

    /**
     * Auto-approve pending migration requests that were waiting on this authority role.
     * When an elder/mayor/baron/king leaves, their pending approvals should be cleared.
     */
    protected function autoApprovePendingMigrations(): void
    {
        $role = $this->role;
        if (! $role) {
            return;
        }

        $approvalLevel = match ($role->slug) {
            'elder' => 'elder',
            'mayor' => 'mayor',
            'baron' => 'baron',
            'king' => 'king',
            default => null,
        };

        if (! $approvalLevel) {
            return;
        }

        // Find pending migration requests that need this approval
        $pendingRequests = MigrationRequest::pending()
            ->whereNull("{$approvalLevel}_approved")
            ->get();

        foreach ($pendingRequests as $request) {
            // Verify this request actually targets a location under this role's jurisdiction
            $shouldApprove = match ($approvalLevel) {
                'elder' => $request->isToVillage()
                    && ($request->to_location_id === $this->location_id || $request->to_village_id === $this->location_id),
                'mayor' => $request->isToTown() && $request->to_location_id === $this->location_id,
                'baron' => $request->getDestinationBarony()?->id === $this->location_id,
                'king' => $request->getDestinationKingdom()?->id === $this->location_id,
                default => false,
            };

            if ($shouldApprove) {
                $request->update([
                    "{$approvalLevel}_approved" => true,
                    "{$approvalLevel}_decided_at" => now(),
                ]);

                // Check if fully approved now and complete if so
                $request->refresh();
                if ($request->checkAllApprovals()) {
                    app(MigrationService::class)->completeMigrationFromModel($request);
                }
            }
        }
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
        if (! $this->isActive()) {
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MigrationRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DENIED = 'denied';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    // Cooldown: 7 days between migrations
    public const MIGRATION_COOLDOWN_DAYS = 7;

    protected $fillable = [
        'user_id',
        'from_village_id',
        'to_village_id',
        'from_location_type',
        'from_location_id',
        'to_location_type',
        'to_location_id',
        'elder_approved',
        'mayor_approved',
        'baron_approved',
        'king_approved',
        'elder_decided_by',
        'mayor_decided_by',
        'baron_decided_by',
        'king_decided_by',
        'elder_decided_at',
        'mayor_decided_at',
        'baron_decided_at',
        'king_decided_at',
        'status',
        'denial_reason',
        'completed_at',
    ];

    protected $casts = [
        'elder_approved' => 'boolean',
        'mayor_approved' => 'boolean',
        'baron_approved' => 'boolean',
        'king_approved' => 'boolean',
        'elder_decided_at' => 'datetime',
        'mayor_decided_at' => 'datetime',
        'baron_decided_at' => 'datetime',
        'king_decided_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'from_village_id');
    }

    public function toVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'to_village_id');
    }

    public function elderDecidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'elder_decided_by');
    }

    public function baronDecidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'baron_decided_by');
    }

    public function kingDecidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'king_decided_by');
    }

    public function mayorDecidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mayor_decided_by');
    }

    /**
     * Get the destination location (polymorphic).
     */
    public function toLocation(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo('to_location', 'to_location_type', 'to_location_id');
    }

    /**
     * Get the origin location (polymorphic).
     */
    public function fromLocation(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo('from_location', 'from_location_type', 'from_location_id');
    }

    /**
     * Get the destination town (if migrating to a town).
     */
    public function toTown(): BelongsTo
    {
        return $this->belongsTo(Town::class, 'to_location_id');
    }

    /**
     * Check if this is a migration to a town.
     */
    public function isToTown(): bool
    {
        return $this->to_location_type === 'town';
    }

    /**
     * Check if this is a migration to a village.
     */
    public function isToVillage(): bool
    {
        return $this->to_location_type === 'village' || $this->to_village_id !== null;
    }

    /**
     * Get the destination name.
     */
    public function getDestinationName(): string
    {
        if ($this->isToTown()) {
            return Town::find($this->to_location_id)?->name ?? 'Unknown Town';
        }

        return $this->toVillage?->name ?? 'Unknown Village';
    }

    /**
     * Get the origin name.
     */
    public function getOriginName(): string
    {
        if ($this->from_location_type === 'town') {
            return Town::find($this->from_location_id)?->name ?? 'Unknown Town';
        }

        return $this->fromVillage?->name ?? 'Unknown Village';
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isDenied(): bool
    {
        return $this->status === self::STATUS_DENIED;
    }

    /**
     * Check if all required approvals are complete.
     */
    public function checkAllApprovals(): bool
    {
        // For towns: check mayor approval
        if ($this->isToTown()) {
            if ($this->needsMayorApproval() && $this->mayor_approved !== true) {
                return false;
            }
        } else {
            // For villages: check elder approval
            if ($this->needsElderApproval() && $this->elder_approved !== true) {
                return false;
            }
        }

        // Check baron approval (if destination barony has a baron)
        if ($this->needsBaronApproval() && $this->baron_approved !== true) {
            return false;
        }

        // Check king approval (if destination kingdom has a king)
        if ($this->needsKingApproval() && $this->king_approved !== true) {
            return false;
        }

        return true;
    }

    /**
     * Check if elder approval is needed (destination village has an elder).
     */
    public function needsElderApproval(): bool
    {
        if ($this->isToTown()) {
            return false; // Towns don't have elders
        }

        $villageId = $this->to_location_id ?? $this->to_village_id;

        return PlayerRole::where('location_type', 'village')
            ->where('location_id', $villageId)
            ->whereHas('role', fn ($q) => $q->where('slug', 'elder'))
            ->active()
            ->exists();
    }

    /**
     * Check if mayor approval is needed (destination town has a mayor).
     */
    public function needsMayorApproval(): bool
    {
        if (! $this->isToTown()) {
            return false; // Villages don't have mayors
        }

        return PlayerRole::where('location_type', 'town')
            ->where('location_id', $this->to_location_id)
            ->whereHas('role', fn ($q) => $q->where('slug', 'mayor'))
            ->active()
            ->exists();
    }

    /**
     * Check if baron approval is needed (destination barony has a baron).
     */
    public function needsBaronApproval(): bool
    {
        $barony = $this->getDestinationBarony();
        if (! $barony) {
            return false;
        }

        return PlayerRole::where('location_type', 'barony')
            ->where('location_id', $barony->id)
            ->whereHas('role', fn ($q) => $q->where('slug', 'baron'))
            ->active()
            ->exists();
    }

    /**
     * Check if king approval is needed (destination kingdom has a king).
     */
    public function needsKingApproval(): bool
    {
        $kingdom = $this->getDestinationKingdom();
        if (! $kingdom) {
            return false;
        }

        return PlayerRole::where('location_type', 'kingdom')
            ->where('location_id', $kingdom->id)
            ->whereHas('role', fn ($q) => $q->where('slug', 'king'))
            ->active()
            ->exists();
    }

    /**
     * Get the destination's barony.
     */
    public function getDestinationBarony(): ?Barony
    {
        if ($this->isToTown()) {
            return Town::find($this->to_location_id)?->barony;
        }

        return $this->toVillage?->barony;
    }

    /**
     * Get the destination's kingdom.
     */
    public function getDestinationKingdom(): ?Kingdom
    {
        return $this->getDestinationBarony()?->kingdom;
    }

    /**
     * Get the next required approval level.
     */
    public function getNextRequiredApproval(): ?string
    {
        // For towns, check mayor first
        if ($this->isToTown()) {
            if ($this->needsMayorApproval() && $this->mayor_approved === null) {
                return 'mayor';
            }
        } else {
            // For villages, check elder first
            if ($this->needsElderApproval() && $this->elder_approved === null) {
                return 'elder';
            }
        }

        if ($this->needsBaronApproval() && $this->baron_approved === null) {
            return 'baron';
        }

        if ($this->needsKingApproval() && $this->king_approved === null) {
            return 'king';
        }

        return null;
    }

    /**
     * Scope for pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for requests to a specific village.
     */
    public function scopeToVillage($query, int $villageId)
    {
        return $query->where('to_village_id', $villageId);
    }
}

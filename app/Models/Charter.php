<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Charter extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_FAILED = 'failed';

    public const TYPE_VILLAGE = 'village';
    public const TYPE_TOWN = 'town';
    public const TYPE_CASTLE = 'castle';

    public const DEFAULT_COST = 1000000;
    public const TOWN_COST = 2500000;
    public const CASTLE_COST = 5000000;

    public const DEFAULT_SIGNATORIES_REQUIRED = 10;
    public const TOWN_SIGNATORIES_REQUIRED = 25;
    public const CASTLE_SIGNATORIES_REQUIRED = 50;

    public const APPROVAL_EXPIRY_DAYS = 30;
    public const VULNERABILITY_DAYS = 14;

    protected $fillable = [
        'settlement_name',
        'description',
        'settlement_type',
        'kingdom_id',
        'issuer_id',
        'founder_id',
        'tax_terms',
        'gold_cost',
        'status',
        'required_signatories',
        'current_signatories',
        'submitted_at',
        'approved_at',
        'founded_at',
        'expires_at',
        'vulnerability_ends_at',
        'coordinates_x',
        'coordinates_y',
        'biome',
        'founded_village_id',
        'founded_castle_id',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'tax_terms' => 'array',
            'gold_cost' => 'integer',
            'required_signatories' => 'integer',
            'current_signatories' => 'integer',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'founded_at' => 'datetime',
            'expires_at' => 'datetime',
            'vulnerability_ends_at' => 'datetime',
            'coordinates_x' => 'integer',
            'coordinates_y' => 'integer',
        ];
    }

    /**
     * Get the kingdom this charter is issued for.
     */
    public function kingdom(): BelongsTo
    {
        return $this->belongsTo(Kingdom::class);
    }

    /**
     * Get the issuer (King or official) who can approve this charter.
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issuer_id');
    }

    /**
     * Get the founder who is creating this settlement.
     */
    public function founder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'founder_id');
    }

    /**
     * Get all signatories for this charter.
     */
    public function signatories(): HasMany
    {
        return $this->hasMany(CharterSignatory::class);
    }

    /**
     * Get the founded village if applicable.
     */
    public function foundedVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'founded_village_id');
    }

    /**
     * Get the founded castle if applicable.
     */
    public function foundedCastle(): BelongsTo
    {
        return $this->belongsTo(Castle::class, 'founded_castle_id');
    }

    /**
     * Check if charter is pending approval.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if charter is approved and ready to found.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if charter has been used to found a settlement.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if charter was rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if charter has expired.
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED ||
            ($this->expires_at && $this->expires_at->isPast());
    }

    /**
     * Check if the settlement is in its vulnerability window.
     */
    public function isVulnerable(): bool
    {
        return $this->vulnerability_ends_at && $this->vulnerability_ends_at->isFuture();
    }

    /**
     * Check if charter has enough signatories.
     */
    public function hasEnoughSignatories(): bool
    {
        return $this->current_signatories >= $this->required_signatories;
    }

    /**
     * Get the gold cost for a settlement type.
     */
    public static function getCostForType(string $type): int
    {
        return match ($type) {
            self::TYPE_TOWN => self::TOWN_COST,
            self::TYPE_CASTLE => self::CASTLE_COST,
            default => self::DEFAULT_COST,
        };
    }

    /**
     * Get required signatories for a settlement type.
     */
    public static function getRequiredSignatoriesForType(string $type): int
    {
        return match ($type) {
            self::TYPE_TOWN => self::TOWN_SIGNATORIES_REQUIRED,
            self::TYPE_CASTLE => self::CASTLE_SIGNATORIES_REQUIRED,
            default => self::DEFAULT_SIGNATORIES_REQUIRED,
        };
    }

    /**
     * Scope to get pending charters.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get approved charters.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope to get active charters (founded settlements).
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get charters for a specific kingdom.
     */
    public function scopeForKingdom($query, int $kingdomId)
    {
        return $query->where('kingdom_id', $kingdomId);
    }
}

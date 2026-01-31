<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PlayerTitle extends Model
{
    use HasFactory;

    /**
     * Title hierarchy with their tiers.
     */
    public const TITLES = [
        'peasant' => 1,
        'knight' => 2,
        'lord' => 3,
        'king' => 4,
    ];

    /**
     * Village roles (special titles for village positions).
     */
    public const VILLAGE_ROLES = [
        'elder' => 2,
        'blacksmith' => 2,
        'merchant' => 2,
        'guard_captain' => 2,
        'healer' => 2,
    ];

    /**
     * Mayor title tier.
     */
    public const MAYOR_TIER = 3;

    /**
     * King title tier.
     */
    public const KING_TIER = 4;

    /**
     * Valid methods of acquiring a title.
     */
    public const ACQUISITION_METHODS = [
        'signup',
        'appointment',
        'election',
        'inheritance',
        'conquest',
    ];

    /**
     * Valid domain types for titles.
     */
    public const DOMAIN_TYPES = [
        'village',
        'castle',
        'town',
        'kingdom',
    ];

    protected $fillable = [
        'user_id',
        'title',
        'tier',
        'domain_type',
        'domain_id',
        'acquisition_method',
        'granted_by_user_id',
        'is_active',
        'granted_at',
        'revoked_at',
        'legitimacy',
        'months_in_office',
    ];

    protected function casts(): array
    {
        return [
            'tier' => 'integer',
            'is_active' => 'boolean',
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'legitimacy' => 'integer',
            'months_in_office' => 'integer',
        ];
    }

    /**
     * Get the user who holds this title.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who granted this title.
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }

    /**
     * Get the title type definition.
     */
    public function titleType(): BelongsTo
    {
        return $this->belongsTo(TitleType::class);
    }

    /**
     * Get the domain (village, castle, town, or kingdom) this title is associated with.
     */
    public function domain(): MorphTo
    {
        return $this->morphTo('domain', 'domain_type', 'domain_id');
    }

    /**
     * Get legitimacy events for this title.
     */
    public function legitimacyEvents(): MorphMany
    {
        return $this->morphMany(LegitimacyEvent::class, 'holder');
    }

    /**
     * Revoke this title.
     */
    public function revoke(): bool
    {
        $this->is_active = false;
        $this->revoked_at = now();

        return $this->save();
    }

    /**
     * Check if this title is currently active.
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->revoked_at === null;
    }

    /**
     * Get the tier for a given title name.
     */
    public static function getTierForTitle(string $title): int
    {
        return self::TITLES[$title] ?? 1;
    }

    /**
     * Check if an acquisition method is valid.
     */
    public static function isValidAcquisitionMethod(string $method): bool
    {
        return in_array($method, self::ACQUISITION_METHODS);
    }
}

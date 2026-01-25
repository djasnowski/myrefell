<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KingdomReligion extends Model
{
    use HasFactory;

    public const STATUS_STATE = 'state';
    public const STATUS_TOLERATED = 'tolerated';
    public const STATUS_BANNED = 'banned';

    public const STATUSES = [
        self::STATUS_STATE,
        self::STATUS_TOLERATED,
        self::STATUS_BANNED,
    ];

    protected $fillable = [
        'kingdom_id',
        'religion_id',
        'status',
        'set_by_id',
    ];

    /**
     * Get the kingdom.
     */
    public function kingdom(): BelongsTo
    {
        return $this->belongsTo(Kingdom::class);
    }

    /**
     * Get the religion.
     */
    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    /**
     * Get the user who set this status.
     */
    public function setBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by_id');
    }

    /**
     * Check if this is the state religion.
     */
    public function isState(): bool
    {
        return $this->status === self::STATUS_STATE;
    }

    /**
     * Check if this religion is tolerated.
     */
    public function isTolerated(): bool
    {
        return $this->status === self::STATUS_TOLERATED;
    }

    /**
     * Check if this religion is banned.
     */
    public function isBanned(): bool
    {
        return $this->status === self::STATUS_BANNED;
    }

    /**
     * Get status display name.
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_STATE => 'State Religion',
            self::STATUS_TOLERATED => 'Tolerated',
            self::STATUS_BANNED => 'Banned',
            default => 'Unknown',
        };
    }
}

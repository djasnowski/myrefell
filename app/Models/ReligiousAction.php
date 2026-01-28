<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReligiousAction extends Model
{
    use HasFactory;

    public const ACTION_PRAYER = 'prayer';
    public const ACTION_DONATION = 'donation';
    public const ACTION_RITUAL = 'ritual';
    public const ACTION_SACRIFICE = 'sacrifice';
    public const ACTION_PILGRIMAGE = 'pilgrimage';

    public const ACTIONS = [
        self::ACTION_PRAYER,
        self::ACTION_DONATION,
        self::ACTION_RITUAL,
        self::ACTION_SACRIFICE,
        self::ACTION_PILGRIMAGE,
    ];

    // Base devotion rewards
    public const DEVOTION_REWARDS = [
        self::ACTION_PRAYER => 10,
        self::ACTION_DONATION => 0, // Based on gold amount
        self::ACTION_RITUAL => 25,
        self::ACTION_SACRIFICE => 50,
        self::ACTION_PILGRIMAGE => 100,
    ];

    // Energy costs
    public const ENERGY_COSTS = [
        self::ACTION_PRAYER => 5,
        self::ACTION_DONATION => 0,
        self::ACTION_RITUAL => 15,
        self::ACTION_SACRIFICE => 20,
        self::ACTION_PILGRIMAGE => 50,
    ];

    // Cooldowns in minutes
    public const COOLDOWNS = [
        self::ACTION_PRAYER => 5,
        self::ACTION_DONATION => 0,
        self::ACTION_RITUAL => 60,
        self::ACTION_SACRIFICE => 180,
        self::ACTION_PILGRIMAGE => 1440, // 24 hours
    ];

    // Prayer skill XP rewards
    public const PRAYER_XP = [
        self::ACTION_PRAYER => 5,
        self::ACTION_DONATION => 0, // Based on gold amount
        self::ACTION_RITUAL => 15,
        self::ACTION_SACRIFICE => 25,
        self::ACTION_PILGRIMAGE => 50,
    ];

    protected $fillable = [
        'user_id',
        'religion_id',
        'religious_structure_id',
        'action_type',
        'devotion_gained',
        'gold_spent',
    ];

    protected function casts(): array
    {
        return [
            'devotion_gained' => 'integer',
            'gold_spent' => 'integer',
        ];
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the religion.
     */
    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    /**
     * Get the structure where the action was performed.
     */
    public function structure(): BelongsTo
    {
        return $this->belongsTo(ReligiousStructure::class, 'religious_structure_id');
    }

    /**
     * Get action type display name.
     */
    public function getActionDisplayAttribute(): string
    {
        return match ($this->action_type) {
            self::ACTION_PRAYER => 'Prayer',
            self::ACTION_DONATION => 'Donation',
            self::ACTION_RITUAL => 'Ritual',
            self::ACTION_SACRIFICE => 'Sacrifice',
            self::ACTION_PILGRIMAGE => 'Pilgrimage',
            default => 'Unknown',
        };
    }

    /**
     * Get base devotion for an action type.
     */
    public static function getBaseDevotion(string $actionType): int
    {
        return self::DEVOTION_REWARDS[$actionType] ?? 0;
    }

    /**
     * Get energy cost for an action type.
     */
    public static function getEnergyCost(string $actionType): int
    {
        return self::ENERGY_COSTS[$actionType] ?? 0;
    }

    /**
     * Get cooldown for an action type in minutes.
     */
    public static function getCooldown(string $actionType): int
    {
        return self::COOLDOWNS[$actionType] ?? 0;
    }

    /**
     * Get prayer XP for an action type.
     */
    public static function getPrayerXp(string $actionType): int
    {
        return self::PRAYER_XP[$actionType] ?? 0;
    }
}

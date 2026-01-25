<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuildActivity extends Model
{
    use HasFactory;

    public const TYPE_CRAFT = 'craft';
    public const TYPE_DONATION = 'donation';
    public const TYPE_MEETING = 'meeting';
    public const TYPE_TRAINING = 'training';
    public const TYPE_PROMOTION = 'promotion';
    public const TYPE_ELECTION = 'election';
    public const TYPE_DUES = 'dues';

    public const TYPES = [
        self::TYPE_CRAFT,
        self::TYPE_DONATION,
        self::TYPE_MEETING,
        self::TYPE_TRAINING,
        self::TYPE_PROMOTION,
        self::TYPE_ELECTION,
        self::TYPE_DUES,
    ];

    // Base contribution for activities
    public const CONTRIBUTION_CRAFT = 10;
    public const CONTRIBUTION_MEETING = 25;
    public const CONTRIBUTION_TRAINING = 15;
    public const CONTRIBUTION_DONATION_PER_100_GOLD = 5;

    protected $fillable = [
        'user_id',
        'guild_id',
        'activity_type',
        'contribution_gained',
        'gold_amount',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'contribution_gained' => 'integer',
            'gold_amount' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user who performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the guild.
     */
    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class);
    }

    /**
     * Get activity type display name.
     */
    public function getTypeDisplayAttribute(): string
    {
        return match ($this->activity_type) {
            self::TYPE_CRAFT => 'Crafting',
            self::TYPE_DONATION => 'Donation',
            self::TYPE_MEETING => 'Meeting',
            self::TYPE_TRAINING => 'Training',
            self::TYPE_PROMOTION => 'Promotion',
            self::TYPE_ELECTION => 'Election',
            self::TYPE_DUES => 'Dues Payment',
            default => 'Unknown',
        };
    }

    /**
     * Calculate contribution for crafting.
     */
    public static function calculateCraftContribution(int $itemValue): int
    {
        // Base + percentage of item value
        return self::CONTRIBUTION_CRAFT + (int) floor($itemValue / 50);
    }

    /**
     * Calculate contribution for donation.
     */
    public static function calculateDonationContribution(int $goldAmount): int
    {
        return (int) floor($goldAmount / 100) * self::CONTRIBUTION_DONATION_PER_100_GOLD;
    }
}

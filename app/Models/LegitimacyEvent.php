<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LegitimacyEvent extends Model
{
    use HasFactory;

    // Event types
    public const TYPE_ELECTION_LANDSLIDE = 'election_landslide';
    public const TYPE_ELECTION_MAJORITY = 'election_majority';
    public const TYPE_ELECTION_NARROW = 'election_narrow';
    public const TYPE_ELECTION_CONTESTED = 'election_contested';
    public const TYPE_TIME_IN_OFFICE = 'time_in_office';
    public const TYPE_POLICY_SUCCESS = 'policy_success';
    public const TYPE_POLICY_FAILURE = 'policy_failure';
    public const TYPE_WAR_WON = 'war_won';
    public const TYPE_WAR_LOST = 'war_lost';
    public const TYPE_CHURCH_SUPPORT = 'church_support';
    public const TYPE_CHURCH_OPPOSITION = 'church_opposition';
    public const TYPE_EXCOMMUNICATION = 'excommunication';
    public const TYPE_SCANDAL = 'scandal';
    public const TYPE_NO_CONFIDENCE_SURVIVED = 'no_confidence_survived';
    public const TYPE_CRIME_CONVICTED = 'crime_convicted';
    public const TYPE_TAX_SUCCESS = 'tax_success';
    public const TYPE_TAX_FAILURE = 'tax_failure';
    public const TYPE_POPULAR_DECISION = 'popular_decision';
    public const TYPE_UNPOPULAR_DECISION = 'unpopular_decision';

    protected $fillable = [
        'player_role_id',
        'holder_type',
        'holder_id',
        'event_type',
        'legitimacy_change',
        'legitimacy_before',
        'legitimacy_after',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'legitimacy_change' => 'integer',
            'legitimacy_before' => 'integer',
            'legitimacy_after' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the player role this event belongs to (legacy).
     */
    public function playerRole(): BelongsTo
    {
        return $this->belongsTo(PlayerRole::class);
    }

    /**
     * Get the holder (PlayerRole or PlayerTitle) this event belongs to.
     */
    public function holder(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get human-readable event type name.
     */
    public function getEventTypeNameAttribute(): string
    {
        return match ($this->event_type) {
            self::TYPE_ELECTION_LANDSLIDE => 'Landslide Election Victory',
            self::TYPE_ELECTION_MAJORITY => 'Election Victory',
            self::TYPE_ELECTION_NARROW => 'Narrow Election Victory',
            self::TYPE_ELECTION_CONTESTED => 'Contested Election',
            self::TYPE_TIME_IN_OFFICE => 'Time in Office',
            self::TYPE_POLICY_SUCCESS => 'Successful Policy',
            self::TYPE_POLICY_FAILURE => 'Failed Policy',
            self::TYPE_WAR_WON => 'War Victory',
            self::TYPE_WAR_LOST => 'War Defeat',
            self::TYPE_CHURCH_SUPPORT => 'Church Support',
            self::TYPE_CHURCH_OPPOSITION => 'Church Opposition',
            self::TYPE_EXCOMMUNICATION => 'Excommunication',
            self::TYPE_SCANDAL => 'Scandal',
            self::TYPE_NO_CONFIDENCE_SURVIVED => 'Survived No Confidence Vote',
            self::TYPE_CRIME_CONVICTED => 'Criminal Conviction',
            self::TYPE_TAX_SUCCESS => 'Successful Tax Collection',
            self::TYPE_TAX_FAILURE => 'Failed Tax Collection',
            self::TYPE_POPULAR_DECISION => 'Popular Decision',
            self::TYPE_UNPOPULAR_DECISION => 'Unpopular Decision',
            default => ucwords(str_replace('_', ' ', $this->event_type)),
        };
    }

    /**
     * Check if this was a positive event.
     */
    public function isPositive(): bool
    {
        return $this->legitimacy_change > 0;
    }

    /**
     * Check if this was a negative event.
     */
    public function isNegative(): bool
    {
        return $this->legitimacy_change < 0;
    }
}

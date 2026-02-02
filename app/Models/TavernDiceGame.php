<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TavernDiceGame extends Model
{
    use HasFactory;

    public const GAME_HIGH_ROLL = 'high_roll';

    public const GAME_HAZARD = 'hazard';

    public const GAME_DOUBLES = 'doubles';

    public const GAMES = [
        self::GAME_HIGH_ROLL,
        self::GAME_HAZARD,
        self::GAME_DOUBLES,
    ];

    protected $fillable = [
        'user_id',
        'location_type',
        'location_id',
        'game_type',
        'wager',
        'rolls',
        'won',
        'payout',
        'energy_awarded',
    ];

    protected function casts(): array
    {
        return [
            'wager' => 'integer',
            'rolls' => 'array',
            'won' => 'boolean',
            'payout' => 'integer',
            'energy_awarded' => 'integer',
        ];
    }

    /**
     * Get the user who played this game.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the game was won.
     */
    public function isWon(): bool
    {
        return $this->won;
    }

    /**
     * Scope to filter by location.
     */
    public function scopeAtLocation($query, string $locationType, int $locationId)
    {
        return $query->where('location_type', $locationType)
            ->where('location_id', $locationId);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}

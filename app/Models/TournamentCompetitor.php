<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentCompetitor extends Model
{
    use HasFactory;

    public const STATUS_REGISTERED = 'registered';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ELIMINATED = 'eliminated';
    public const STATUS_WINNER = 'winner';
    public const STATUS_WITHDREW = 'withdrew';

    protected $fillable = [
        'tournament_id',
        'user_id',
        'seed',
        'status',
        'wins',
        'losses',
        'final_placement',
        'prize_won',
        'fame_earned',
    ];

    protected function casts(): array
    {
        return [
            'seed' => 'integer',
            'wins' => 'integer',
            'losses' => 'integer',
            'final_placement' => 'integer',
            'prize_won' => 'integer',
            'fame_earned' => 'integer',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matchesAsCompetitor1(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'competitor1_id');
    }

    public function matchesAsCompetitor2(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'competitor2_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_REGISTERED, self::STATUS_ACTIVE]);
    }
}

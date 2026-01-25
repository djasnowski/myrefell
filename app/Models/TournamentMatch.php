<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentMatch extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tournament_id',
        'round_number',
        'match_number',
        'competitor1_id',
        'competitor2_id',
        'winner_id',
        'status',
        'competitor1_score',
        'competitor2_score',
        'combat_log',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'round_number' => 'integer',
            'match_number' => 'integer',
            'competitor1_score' => 'integer',
            'competitor2_score' => 'integer',
            'combat_log' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function competitor1(): BelongsTo
    {
        return $this->belongsTo(TournamentCompetitor::class, 'competitor1_id');
    }

    public function competitor2(): BelongsTo
    {
        return $this->belongsTo(TournamentCompetitor::class, 'competitor2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(TournamentCompetitor::class, 'winner_id');
    }

    public function isBye(): bool
    {
        return $this->competitor2_id === null;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}

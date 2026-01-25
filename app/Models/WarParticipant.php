<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarParticipant extends Model
{
    use HasFactory;

    const SIDE_ATTACKER = 'attacker';
    const SIDE_DEFENDER = 'defender';

    const ROLE_PRIMARY = 'primary';
    const ROLE_ALLY = 'ally';
    const ROLE_VASSAL = 'vassal';

    protected $fillable = [
        'war_id', 'participant_type', 'participant_id', 'side', 'role',
        'is_war_leader', 'contribution_score', 'joined_at', 'left_at',
    ];

    protected function casts(): array
    {
        return [
            'is_war_leader' => 'boolean',
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function war(): BelongsTo
    {
        return $this->belongsTo(War::class);
    }

    public function getParticipantAttribute(): ?Model
    {
        return match ($this->participant_type) {
            'kingdom' => Kingdom::find($this->participant_id),
            'barony' => Barony::find($this->participant_id),
            'player' => User::find($this->participant_id),
            default => null,
        };
    }

    public function isActive(): bool
    {
        return is_null($this->left_at);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('left_at');
    }

    public function scopeOnSide($query, string $side)
    {
        return $query->where('side', $side);
    }
}

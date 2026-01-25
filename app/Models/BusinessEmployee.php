<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessEmployee extends Model
{
    use HasFactory;

    public const STATUS_EMPLOYED = 'employed';
    public const STATUS_QUIT = 'quit';
    public const STATUS_FIRED = 'fired';

    public const ROLE_WORKER = 'worker';
    public const ROLE_MANAGER = 'manager';

    protected $fillable = [
        'player_business_id',
        'user_id',
        'location_npc_id',
        'role',
        'daily_wage',
        'skill_level',
        'status',
        'hired_at',
        'last_paid_at',
    ];

    protected function casts(): array
    {
        return [
            'daily_wage' => 'integer',
            'skill_level' => 'integer',
            'hired_at' => 'datetime',
            'last_paid_at' => 'datetime',
        ];
    }

    /**
     * Get the business this employee works at.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(PlayerBusiness::class, 'player_business_id');
    }

    /**
     * Get the player employee (if any).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the NPC employee (if any).
     */
    public function npc(): BelongsTo
    {
        return $this->belongsTo(LocationNpc::class, 'location_npc_id');
    }

    /**
     * Check if this is a player employee.
     */
    public function isPlayer(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Check if this is an NPC employee.
     */
    public function isNpc(): bool
    {
        return $this->location_npc_id !== null;
    }

    /**
     * Get the employee name.
     */
    public function getNameAttribute(): string
    {
        if ($this->isPlayer()) {
            return $this->user?->name ?? 'Unknown Player';
        }

        return $this->npc?->name ?? 'Unknown NPC';
    }

    /**
     * Check if employee is currently employed.
     */
    public function isEmployed(): bool
    {
        return $this->status === self::STATUS_EMPLOYED;
    }

    /**
     * Calculate efficiency based on skill level.
     */
    public function getEfficiencyAttribute(): float
    {
        // Base efficiency is 50%, increases with skill level
        // Max skill level of 100 gives 100% efficiency
        return 0.5 + ($this->skill_level / 200);
    }
}

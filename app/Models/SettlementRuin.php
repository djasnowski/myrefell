<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementRuin extends Model
{
    use HasFactory;

    public const DEFAULT_RECLAIM_COST = 500000;

    protected $fillable = [
        'name',
        'description',
        'kingdom_id',
        'original_charter_id',
        'original_founder_id',
        'coordinates_x',
        'coordinates_y',
        'biome',
        'reclaim_cost',
        'is_reclaimable',
        'ruined_at',
    ];

    protected function casts(): array
    {
        return [
            'coordinates_x' => 'integer',
            'coordinates_y' => 'integer',
            'reclaim_cost' => 'integer',
            'is_reclaimable' => 'boolean',
            'ruined_at' => 'datetime',
        ];
    }

    /**
     * Get the kingdom this ruin is in.
     */
    public function kingdom(): BelongsTo
    {
        return $this->belongsTo(Kingdom::class);
    }

    /**
     * Get the original charter that created this settlement.
     */
    public function originalCharter(): BelongsTo
    {
        return $this->belongsTo(Charter::class, 'original_charter_id');
    }

    /**
     * Get the original founder who failed.
     */
    public function originalFounder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'original_founder_id');
    }

    /**
     * Scope to get reclaimable ruins.
     */
    public function scopeReclaimable($query)
    {
        return $query->where('is_reclaimable', true);
    }

    /**
     * Scope to get ruins in a kingdom.
     */
    public function scopeInKingdom($query, int $kingdomId)
    {
        return $query->where('kingdom_id', $kingdomId);
    }
}

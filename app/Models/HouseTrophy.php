<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseTrophy extends Model
{
    protected $fillable = [
        'player_house_id',
        'slot',
        'item_id',
        'monster_name',
        'monster_type',
        'monster_combat_level',
        'is_boss',
        'mounted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_boss' => 'boolean',
            'mounted_at' => 'datetime',
            'monster_combat_level' => 'integer',
        ];
    }

    /**
     * Monster type → stat bonuses mapping.
     * Display slots get these values directly.
     * Pedestal (boss) trophies get primary +3, secondary +1.
     */
    public const TROPHY_TYPE_BONUSES = [
        'humanoid' => ['attack_bonus' => 1],
        'beast' => ['strength_bonus' => 1],
        'undead' => ['defense_bonus' => 1],
        'dragon' => ['attack_bonus' => 1, 'strength_bonus' => 1],
        'demon' => ['strength_bonus' => 1, 'defense_bonus' => 1],
        'elemental' => ['defense_bonus' => 1],
        'giant' => ['strength_bonus' => 1],
        'goblinoid' => ['attack_bonus' => 1],
    ];

    public function house(): BelongsTo
    {
        return $this->belongsTo(PlayerHouse::class, 'player_house_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get stat bonuses for this mounted trophy.
     *
     * @return array<string, int>
     */
    public function getStatBonuses(): array
    {
        $baseBonuses = self::TROPHY_TYPE_BONUSES[$this->monster_type] ?? [];

        if (! $this->is_boss || $this->slot !== 'pedestal') {
            return $baseBonuses;
        }

        // Boss trophy on pedestal: primary +3, secondary +1
        $boosted = [];
        $first = true;
        foreach ($baseBonuses as $key => $value) {
            $boosted[$key] = $first ? 3 : 1;
            $first = false;
        }

        // If only one stat, just give +3
        if (count($baseBonuses) === 1) {
            $key = array_key_first($baseBonuses);
            $boosted[$key] = 3;
        }

        return $boosted;
    }
}

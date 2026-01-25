<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentType extends Model
{
    use HasFactory;

    public const COMBAT_MELEE = 'melee';
    public const COMBAT_JOUST = 'joust';
    public const COMBAT_ARCHERY = 'archery';
    public const COMBAT_WRESTLING = 'wrestling';
    public const COMBAT_MIXED = 'mixed';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'combat_type',
        'primary_stat',
        'secondary_stat',
        'entry_fee',
        'min_level',
        'max_participants',
        'prize_distribution',
        'is_lethal',
    ];

    protected function casts(): array
    {
        return [
            'entry_fee' => 'integer',
            'min_level' => 'integer',
            'max_participants' => 'integer',
            'prize_distribution' => 'array',
            'is_lethal' => 'boolean',
        ];
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }
}

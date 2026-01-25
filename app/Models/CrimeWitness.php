<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrimeWitness extends Model
{
    protected $fillable = [
        'crime_id',
        'witness_id',
        'is_npc',
        'npc_id',
        'testimony',
        'has_testified',
    ];

    protected function casts(): array
    {
        return [
            'is_npc' => 'boolean',
            'has_testified' => 'boolean',
        ];
    }

    public function crime(): BelongsTo
    {
        return $this->belongsTo(Crime::class);
    }

    public function witness(): BelongsTo
    {
        return $this->belongsTo(User::class, 'witness_id');
    }

    public function npc(): BelongsTo
    {
        return $this->belongsTo(LocationNpc::class, 'npc_id');
    }

    public function getWitnessName(): string
    {
        if ($this->is_npc && $this->npc) {
            return $this->npc->name;
        }
        return $this->witness?->username ?? 'Unknown';
    }
}

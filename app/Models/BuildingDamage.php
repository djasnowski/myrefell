<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuildingDamage extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id', 'disaster_id', 'damage_amount', 'condition_before',
        'condition_after', 'cause', 'occurred_at', 'is_repaired',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'is_repaired' => 'boolean',
        ];
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function disaster(): BelongsTo
    {
        return $this->belongsTo(Disaster::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuildingType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'category', 'construction_requirements',
        'construction_days', 'construction_labor', 'maintenance_cost',
        'capacity', 'bonuses', 'is_fortification',
    ];

    protected function casts(): array
    {
        return [
            'construction_requirements' => 'array',
            'bonuses' => 'array',
            'is_fortification' => 'boolean',
        ];
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }
}

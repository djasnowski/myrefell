<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisasterType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'category', 'affected_seasons',
        'base_chance', 'duration_days', 'building_damage', 'crop_damage',
        'casualty_rate', 'preventable_by',
    ];

    protected function casts(): array
    {
        return [
            'affected_seasons' => 'array',
            'preventable_by' => 'array',
        ];
    }

    public function disasters(): HasMany
    {
        return $this->hasMany(Disaster::class);
    }

    public function canOccurInSeason(string $season): bool
    {
        return empty($this->affected_seasons) || in_array($season, $this->affected_seasons);
    }
}

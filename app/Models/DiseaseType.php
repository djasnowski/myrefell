<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiseaseType extends Model
{
    use HasFactory;

    public const SEVERITY_MINOR = 'minor';
    public const SEVERITY_MODERATE = 'moderate';
    public const SEVERITY_SEVERE = 'severe';
    public const SEVERITY_PLAGUE = 'plague';

    protected $fillable = [
        'name', 'slug', 'description', 'severity', 'base_spread_rate',
        'mortality_rate', 'base_duration_days', 'incubation_days',
        'symptoms', 'stat_penalties', 'is_contagious', 'grants_immunity',
    ];

    protected function casts(): array
    {
        return [
            'symptoms' => 'array',
            'stat_penalties' => 'array',
            'is_contagious' => 'boolean',
            'grants_immunity' => 'boolean',
        ];
    }

    public function outbreaks(): HasMany
    {
        return $this->hasMany(DiseaseOutbreak::class);
    }

    public function infections(): HasMany
    {
        return $this->hasMany(DiseaseInfection::class);
    }
}

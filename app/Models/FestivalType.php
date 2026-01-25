<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FestivalType extends Model
{
    use HasFactory;

    public const CATEGORY_SEASONAL = 'seasonal';
    public const CATEGORY_RELIGIOUS = 'religious';
    public const CATEGORY_ROYAL = 'royal';
    public const CATEGORY_SPECIAL = 'special';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'season',
        'duration_days',
        'bonuses',
        'activities',
        'is_recurring',
    ];

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'bonuses' => 'array',
            'activities' => 'array',
            'is_recurring' => 'boolean',
        ];
    }

    public function festivals(): HasMany
    {
        return $this->hasMany(Festival::class);
    }
}

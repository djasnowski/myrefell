<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConstructionProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id', 'project_type', 'status', 'progress', 'labor_invested',
        'labor_required', 'materials_invested', 'materials_required',
        'gold_invested', 'gold_required', 'managed_by_user_id', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'materials_invested' => 'array',
            'materials_required' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'managed_by_user_id');
    }

    public function isComplete(): bool
    {
        return $this->progress >= 100;
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServantTask extends Model
{
    protected $fillable = [
        'house_servant_id',
        'task_type',
        'task_data',
        'status',
        'started_at',
        'estimated_completion',
        'completed_at',
        'result_message',
    ];

    protected function casts(): array
    {
        return [
            'task_data' => 'array',
            'started_at' => 'datetime',
            'estimated_completion' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function servant(): BelongsTo
    {
        return $this->belongsTo(HouseServant::class, 'house_servant_id');
    }

    public function isReadyToComplete(): bool
    {
        return $this->status === 'in_progress' && $this->estimated_completion?->isPast();
    }
}

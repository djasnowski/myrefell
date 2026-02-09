<?php

namespace App\Models;

use App\Config\ConstructionConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HouseServant extends Model
{
    protected $fillable = [
        'player_house_id',
        'servant_type',
        'name',
        'on_strike',
        'last_paid_at',
        'hired_at',
    ];

    protected function casts(): array
    {
        return [
            'on_strike' => 'boolean',
            'last_paid_at' => 'datetime',
            'hired_at' => 'datetime',
        ];
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(PlayerHouse::class, 'player_house_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ServantTask::class, 'house_servant_id');
    }

    public function currentTask(): ?ServantTask
    {
        return $this->tasks()->where('status', 'in_progress')->first();
    }

    public function nextQueuedTask(): ?ServantTask
    {
        return $this->tasks()->where('status', 'queued')->oldest()->first();
    }

    /**
     * @return array{name: string, level: int, hire_cost: int, weekly_wage: int, carry_capacity: int, base_speed: int}
     */
    public function getConfig(): array
    {
        return ConstructionConfig::SERVANT_TIERS[$this->servant_type];
    }
}

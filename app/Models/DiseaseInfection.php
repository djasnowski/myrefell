<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiseaseInfection extends Model
{
    use HasFactory;

    public const STATUS_INCUBATING = 'incubating';
    public const STATUS_SYMPTOMATIC = 'symptomatic';
    public const STATUS_RECOVERING = 'recovering';
    public const STATUS_RECOVERED = 'recovered';
    public const STATUS_DECEASED = 'deceased';

    protected $fillable = [
        'disease_outbreak_id', 'disease_type_id', 'user_id', 'location_npc_id',
        'status', 'severity_modifier', 'days_infected', 'days_symptomatic',
        'is_treated', 'infected_at', 'symptoms_started_at', 'recovered_at',
    ];

    protected function casts(): array
    {
        return [
            'infected_at' => 'datetime',
            'symptoms_started_at' => 'datetime',
            'recovered_at' => 'datetime',
            'is_treated' => 'boolean',
        ];
    }

    public function diseaseOutbreak(): BelongsTo
    {
        return $this->belongsTo(DiseaseOutbreak::class);
    }

    public function diseaseType(): BelongsTo
    {
        return $this->belongsTo(DiseaseType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function npc(): BelongsTo
    {
        return $this->belongsTo(LocationNpc::class, 'location_npc_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_INCUBATING, self::STATUS_SYMPTOMATIC, self::STATUS_RECOVERING]);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_INCUBATING, self::STATUS_SYMPTOMATIC, self::STATUS_RECOVERING]);
    }
}

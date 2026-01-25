<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiseaseImmunity extends Model
{
    use HasFactory;

    protected $fillable = [
        'disease_type_id', 'user_id', 'location_npc_id',
        'immunity_type', 'acquired_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'acquired_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function diseaseType(): BelongsTo
    {
        return $this->belongsTo(DiseaseType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at > now();
    }
}

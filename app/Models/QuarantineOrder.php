<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuarantineOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'disease_outbreak_id', 'location_type', 'location_id',
        'ordered_by_user_id', 'status', 'ordered_at', 'lifted_at', 'reason',
    ];

    protected function casts(): array
    {
        return [
            'ordered_at' => 'datetime',
            'lifted_at' => 'datetime',
        ];
    }

    public function diseaseOutbreak(): BelongsTo
    {
        return $this->belongsTo(DiseaseOutbreak::class);
    }

    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by_user_id');
    }

    public function getLocationAttribute(): ?Model
    {
        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'town' => Town::find($this->location_id),
            default => null,
        };
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

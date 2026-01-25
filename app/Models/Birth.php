<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Birth extends Model
{
    use HasFactory;

    protected $fillable = [
        'marriage_id', 'mother_id', 'father_id', 'child_id', 'is_legitimate',
        'is_stillborn', 'is_twins', 'birth_date', 'location_type', 'location_id',
    ];

    protected function casts(): array
    {
        return [
            'is_legitimate' => 'boolean',
            'is_stillborn' => 'boolean',
            'is_twins' => 'boolean',
            'birth_date' => 'date',
        ];
    }

    public function marriage(): BelongsTo
    {
        return $this->belongsTo(Marriage::class);
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(DynastyMember::class, 'mother_id');
    }

    public function father(): BelongsTo
    {
        return $this->belongsTo(DynastyMember::class, 'father_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(DynastyMember::class, 'child_id');
    }

    public function getLocationAttribute(): ?Model
    {
        if (!$this->location_type || !$this->location_id) {
            return null;
        }

        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'town' => Town::find($this->location_id),
            'castle' => Castle::find($this->location_id),
            default => null,
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Marriage extends Model
{
    use HasFactory;

    const STATUS_ACTIVE = 'active';
    const STATUS_DIVORCED = 'divorced';
    const STATUS_ANNULLED = 'annulled';
    const STATUS_WIDOWED = 'widowed';

    const TYPE_STANDARD = 'standard';
    const TYPE_POLITICAL = 'political';
    const TYPE_SECRET = 'secret';
    const TYPE_MORGANATIC = 'morganatic'; // Unequal marriage, children don't inherit

    protected $fillable = [
        'spouse1_id', 'spouse2_id', 'status', 'marriage_type', 'dowry_amount',
        'dowry_items', 'contract_terms', 'officiant_id', 'location_type',
        'location_id', 'wedding_date', 'end_date', 'end_reason',
    ];

    protected function casts(): array
    {
        return [
            'dowry_items' => 'array',
            'contract_terms' => 'array',
            'wedding_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function spouse1(): BelongsTo
    {
        return $this->belongsTo(DynastyMember::class, 'spouse1_id');
    }

    public function spouse2(): BelongsTo
    {
        return $this->belongsTo(DynastyMember::class, 'spouse2_id');
    }

    public function officiant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officiant_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Birth::class);
    }

    public function alliance()
    {
        return $this->hasOne(DynastyAlliance::class);
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

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getDurationAttribute(): ?int
    {
        $endDate = $this->end_date ?? now();
        return $this->wedding_date->diffInYears($endDate);
    }

    public function getSpouseOf(DynastyMember $member): ?DynastyMember
    {
        if ($this->spouse1_id === $member->id) {
            return $this->spouse2;
        }
        if ($this->spouse2_id === $member->id) {
            return $this->spouse1;
        }
        return null;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInvolving($query, int $memberId)
    {
        return $query->where(function ($q) use ($memberId) {
            $q->where('spouse1_id', $memberId)
                ->orWhere('spouse2_id', $memberId);
        });
    }
}

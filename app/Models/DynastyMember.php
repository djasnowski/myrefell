<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DynastyMember extends Model
{
    use HasFactory;

    const STATUS_ALIVE = 'alive';
    const STATUS_DEAD = 'dead';
    const STATUS_MISSING = 'missing';
    const STATUS_EXILED = 'exiled';

    const GENDER_MALE = 'male';
    const GENDER_FEMALE = 'female';

    protected $fillable = [
        'dynasty_id', 'user_id', 'npc_id', 'member_type', 'father_id', 'mother_id',
        'first_name', 'birth_name', 'gender', 'generation', 'birth_order',
        'is_legitimate', 'is_heir', 'is_disinherited', 'status',
        'birth_date', 'death_date', 'death_cause',
    ];

    protected function casts(): array
    {
        return [
            'is_legitimate' => 'boolean',
            'is_heir' => 'boolean',
            'is_disinherited' => 'boolean',
            'birth_date' => 'date',
            'death_date' => 'date',
        ];
    }

    public function dynasty(): BelongsTo
    {
        return $this->belongsTo(Dynasty::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function father(): BelongsTo
    {
        return $this->belongsTo(DynastyMember::class, 'father_id');
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(DynastyMember::class, 'mother_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(DynastyMember::class, 'father_id')
            ->orWhere('mother_id', $this->id);
    }

    public function childrenAsFather(): HasMany
    {
        return $this->hasMany(DynastyMember::class, 'father_id');
    }

    public function childrenAsMother(): HasMany
    {
        return $this->hasMany(DynastyMember::class, 'mother_id');
    }

    public function marriagesAsSpouse1(): HasMany
    {
        return $this->hasMany(Marriage::class, 'spouse1_id');
    }

    public function marriagesAsSpouse2(): HasMany
    {
        return $this->hasMany(Marriage::class, 'spouse2_id');
    }

    public function currentMarriage(): ?Marriage
    {
        return Marriage::where(function ($q) {
            $q->where('spouse1_id', $this->id)
                ->orWhere('spouse2_id', $this->id);
        })->where('status', 'active')->first();
    }

    public function inheritanceClaims(): HasMany
    {
        return $this->hasMany(InheritanceClaim::class, 'claimant_id');
    }

    public function getSpouseAttribute(): ?DynastyMember
    {
        $marriage = $this->currentMarriage();
        if (!$marriage) {
            return null;
        }

        return $marriage->spouse1_id === $this->id
            ? $marriage->spouse2
            : $marriage->spouse1;
    }

    public function getFullNameAttribute(): string
    {
        $dynastyName = $this->dynasty?->name ?? '';
        return "{$this->first_name} {$dynastyName}";
    }

    public function getAgeAttribute(): ?int
    {
        if (!$this->birth_date) {
            return null;
        }

        $endDate = $this->death_date ?? now();
        return $this->birth_date->diffInYears($endDate);
    }

    public function isAlive(): bool
    {
        return $this->status === self::STATUS_ALIVE;
    }

    public function isMarried(): bool
    {
        return $this->currentMarriage() !== null;
    }

    public function canMarry(): bool
    {
        return $this->isAlive() && !$this->isMarried() && ($this->age ?? 0) >= 16;
    }

    public function getSiblings()
    {
        return DynastyMember::where('dynasty_id', $this->dynasty_id)
            ->where('id', '!=', $this->id)
            ->where(function ($q) {
                $q->where('father_id', $this->father_id)
                    ->orWhere('mother_id', $this->mother_id);
            })
            ->get();
    }

    public function scopeAlive($query)
    {
        return $query->where('status', self::STATUS_ALIVE);
    }

    public function scopeMale($query)
    {
        return $query->where('gender', self::GENDER_MALE);
    }

    public function scopeFemale($query)
    {
        return $query->where('gender', self::GENDER_FEMALE);
    }

    public function scopeLegitimate($query)
    {
        return $query->where('is_legitimate', true);
    }

    public function scopeEligibleHeirs($query)
    {
        return $query->alive()
            ->legitimate()
            ->where('is_disinherited', false);
    }
}

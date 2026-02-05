<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Dynasty extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DISSOLVED = 'dissolved';

    protected $fillable = [
        'name', 'status', 'motto', 'founder_id', 'current_head_id', 'coat_of_arms',
        'prestige', 'wealth_score', 'members_count', 'generations', 'history',
        'founded_at', 'dissolved_at', 'dissolution_reason',
    ];

    protected function casts(): array
    {
        return [
            'history' => 'array',
            'founded_at' => 'datetime',
            'dissolved_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isDissolved(): bool
    {
        return $this->status === self::STATUS_DISSOLVED;
    }

    public function founder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'founder_id');
    }

    public function currentHead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_head_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(DynastyMember::class);
    }

    public function livingMembers(): HasMany
    {
        return $this->hasMany(DynastyMember::class)->where('status', 'alive');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function successionRules(): HasOne
    {
        return $this->hasOne(SuccessionRule::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(DynastyEvent::class);
    }

    public function alliances(): HasMany
    {
        return $this->hasMany(DynastyAlliance::class, 'dynasty1_id');
    }

    public function getHeir(): ?DynastyMember
    {
        return $this->members()
            ->where('is_heir', true)
            ->where('status', 'alive')
            ->first();
    }

    public function recalculateMembers(): void
    {
        $this->update([
            'members_count' => $this->members()->where('status', 'alive')->count(),
            'generations' => $this->members()->max('generation') ?? 1,
        ]);
    }

    public function addPrestige(int $amount, ?string $reason = null): void
    {
        $this->increment('prestige', $amount);

        if ($reason) {
            DynastyEvent::create([
                'dynasty_id' => $this->id,
                'event_type' => 'achievement',
                'title' => 'Prestige Changed',
                'description' => $reason,
                'prestige_change' => $amount,
                'occurred_at' => now(),
            ]);
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InheritanceClaim extends Model
{
    use HasFactory;

    const TYPE_THRONE = 'throne';
    const TYPE_TITLE = 'title';
    const TYPE_PROPERTY = 'property';
    const TYPE_WEALTH = 'wealth';

    const STRENGTH_WEAK = 'weak';
    const STRENGTH_STRONG = 'strong';
    const STRENGTH_PRESSED = 'pressed';

    const BASIS_BIRTH = 'birth';
    const BASIS_MARRIAGE = 'marriage';
    const BASIS_CONQUEST = 'conquest';
    const BASIS_GRANT = 'grant';

    const STATUS_ACTIVE = 'active';
    const STATUS_PRESSED = 'pressed';
    const STATUS_WON = 'won';
    const STATUS_LOST = 'lost';
    const STATUS_RENOUNCED = 'renounced';

    protected $fillable = [
        'claimant_id', 'claim_type', 'target_type', 'target_id', 'claim_strength',
        'claim_basis', 'status', 'supporting_evidence',
    ];

    protected function casts(): array
    {
        return [
            'supporting_evidence' => 'array',
        ];
    }

    public function claimant(): BelongsTo
    {
        return $this->belongsTo(DynastyMember::class, 'claimant_id');
    }

    public function getTargetAttribute(): ?Model
    {
        return match ($this->target_type) {
            'kingdom' => Kingdom::find($this->target_id),
            'barony' => Barony::find($this->target_id),
            'dynasty' => Dynasty::find($this->target_id),
            default => null,
        };
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_PRESSED]);
    }

    public function canPress(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->claimant->isAlive();
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_PRESSED]);
    }

    public function scopeStrong($query)
    {
        return $query->whereIn('claim_strength', [self::STRENGTH_STRONG, self::STRENGTH_PRESSED]);
    }
}

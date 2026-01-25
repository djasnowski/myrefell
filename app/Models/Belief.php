<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Belief extends Model
{
    use HasFactory;

    public const TYPE_VIRTUE = 'virtue';
    public const TYPE_VICE = 'vice';
    public const TYPE_NEUTRAL = 'neutral';

    public const TYPES = [
        self::TYPE_VIRTUE,
        self::TYPE_VICE,
        self::TYPE_NEUTRAL,
    ];

    protected $fillable = [
        'name',
        'description',
        'icon',
        'effects',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'effects' => 'array',
        ];
    }

    /**
     * Get religions that have adopted this belief.
     */
    public function religions(): BelongsToMany
    {
        return $this->belongsToMany(Religion::class, 'religion_beliefs')
            ->withTimestamps();
    }

    /**
     * Get the effect value for a specific stat.
     */
    public function getEffect(string $stat): int
    {
        return $this->effects[$stat] ?? 0;
    }

    /**
     * Check if this is a positive belief.
     */
    public function isVirtue(): bool
    {
        return $this->type === self::TYPE_VIRTUE;
    }

    /**
     * Check if this is a negative belief.
     */
    public function isVice(): bool
    {
        return $this->type === self::TYPE_VICE;
    }
}

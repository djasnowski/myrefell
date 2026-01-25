<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrimeType extends Model
{
    public const SEVERITY_MINOR = 'minor';
    public const SEVERITY_MODERATE = 'moderate';
    public const SEVERITY_MAJOR = 'major';
    public const SEVERITY_CAPITAL = 'capital';

    public const COURT_VILLAGE = 'village';
    public const COURT_BARONY = 'barony';
    public const COURT_KINGDOM = 'kingdom';
    public const COURT_CHURCH = 'church';

    // Standard crime slugs
    public const THEFT = 'theft';
    public const ASSAULT = 'assault';
    public const MURDER = 'murder';
    public const TREASON = 'treason';
    public const HERESY = 'heresy';
    public const DESERTION = 'desertion';
    public const FALSE_ACCUSATION = 'false_accusation';
    public const TRESPASSING = 'trespassing';
    public const FRAUD = 'fraud';
    public const SMUGGLING = 'smuggling';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'severity',
        'court_level',
        'base_fine',
        'base_jail_days',
        'can_be_outlawed',
        'can_be_executed',
        'is_religious',
    ];

    protected function casts(): array
    {
        return [
            'base_fine' => 'integer',
            'base_jail_days' => 'integer',
            'can_be_outlawed' => 'boolean',
            'can_be_executed' => 'boolean',
            'is_religious' => 'boolean',
        ];
    }

    public function crimes(): HasMany
    {
        return $this->hasMany(Crime::class);
    }

    public function accusations(): HasMany
    {
        return $this->hasMany(Accusation::class);
    }

    public function isMinor(): bool
    {
        return $this->severity === self::SEVERITY_MINOR;
    }

    public function isModerate(): bool
    {
        return $this->severity === self::SEVERITY_MODERATE;
    }

    public function isMajor(): bool
    {
        return $this->severity === self::SEVERITY_MAJOR;
    }

    public function isCapital(): bool
    {
        return $this->severity === self::SEVERITY_CAPITAL;
    }

    public function getSeverityDisplayAttribute(): string
    {
        return ucfirst($this->severity);
    }

    public function getCourtDisplayAttribute(): string
    {
        return match ($this->court_level) {
            self::COURT_VILLAGE => 'Village Court',
            self::COURT_BARONY => "Baron's Court",
            self::COURT_KINGDOM => 'Royal Court',
            self::COURT_CHURCH => 'Church Court',
            default => 'Unknown Court',
        };
    }
}

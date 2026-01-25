<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trial extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_AWAITING_VERDICT = 'awaiting_verdict';
    public const STATUS_CONCLUDED = 'concluded';
    public const STATUS_APPEALED = 'appealed';
    public const STATUS_DISMISSED = 'dismissed';

    public const VERDICT_GUILTY = 'guilty';
    public const VERDICT_NOT_GUILTY = 'not_guilty';
    public const VERDICT_DISMISSED = 'dismissed';

    public const COURT_VILLAGE = 'village';
    public const COURT_BARONY = 'barony';
    public const COURT_KINGDOM = 'kingdom';
    public const COURT_CHURCH = 'church';

    protected $fillable = [
        'crime_id',
        'accusation_id',
        'defendant_id',
        'judge_id',
        'court_level',
        'location_type',
        'location_id',
        'status',
        'prosecution_argument',
        'defense_argument',
        'verdict',
        'verdict_reasoning',
        'scheduled_at',
        'started_at',
        'concluded_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'concluded_at' => 'datetime',
        ];
    }

    public function crime(): BelongsTo
    {
        return $this->belongsTo(Crime::class);
    }

    public function accusation(): BelongsTo
    {
        return $this->belongsTo(Accusation::class);
    }

    public function defendant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'defendant_id');
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'judge_id');
    }

    public function punishments(): HasMany
    {
        return $this->hasMany(Punishment::class);
    }

    public function getLocation(): Model|null
    {
        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'barony' => Barony::find($this->location_id),
            'kingdom' => Kingdom::find($this->location_id),
            default => null,
        };
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isConcluded(): bool
    {
        return $this->status === self::STATUS_CONCLUDED;
    }

    public function isGuilty(): bool
    {
        return $this->verdict === self::VERDICT_GUILTY;
    }

    public function isNotGuilty(): bool
    {
        return $this->verdict === self::VERDICT_NOT_GUILTY;
    }

    public function canAppeal(): bool
    {
        if (!$this->isConcluded() || !$this->isGuilty()) {
            return false;
        }

        // Can appeal to higher court
        return match ($this->court_level) {
            self::COURT_VILLAGE => true, // Can appeal to barony
            self::COURT_BARONY => true, // Can appeal to kingdom
            self::COURT_KINGDOM => false, // Royal court is final
            self::COURT_CHURCH => false, // Church court is final for religious matters
            default => false,
        };
    }

    public function getAppealCourtLevel(): ?string
    {
        return match ($this->court_level) {
            self::COURT_VILLAGE => self::COURT_BARONY,
            self::COURT_BARONY => self::COURT_KINGDOM,
            default => null,
        };
    }

    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    public function renderVerdict(string $verdict, string $reasoning): void
    {
        $this->update([
            'status' => self::STATUS_CONCLUDED,
            'verdict' => $verdict,
            'verdict_reasoning' => $reasoning,
            'concluded_at' => now(),
        ]);
    }

    public function dismiss(string $reasoning): void
    {
        $this->update([
            'status' => self::STATUS_DISMISSED,
            'verdict' => self::VERDICT_DISMISSED,
            'verdict_reasoning' => $reasoning,
            'concluded_at' => now(),
        ]);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_SCHEDULED, self::STATUS_IN_PROGRESS, self::STATUS_AWAITING_VERDICT]);
    }

    public function scopeForJudge($query, int $judgeId)
    {
        return $query->where('judge_id', $judgeId);
    }

    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_AWAITING_VERDICT => 'Awaiting Verdict',
            self::STATUS_CONCLUDED => 'Concluded',
            self::STATUS_APPEALED => 'Appealed',
            self::STATUS_DISMISSED => 'Dismissed',
            default => 'Unknown',
        };
    }

    public function getVerdictDisplayAttribute(): string
    {
        return match ($this->verdict) {
            self::VERDICT_GUILTY => 'Guilty',
            self::VERDICT_NOT_GUILTY => 'Not Guilty',
            self::VERDICT_DISMISSED => 'Dismissed',
            default => 'Pending',
        };
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

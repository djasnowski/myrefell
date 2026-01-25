<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Punishment extends Model
{
    public const TYPE_FINE = 'fine';
    public const TYPE_JAIL = 'jail';
    public const TYPE_EXILE = 'exile';
    public const TYPE_OUTLAWRY = 'outlawry';
    public const TYPE_EXECUTION = 'execution';
    public const TYPE_EXCOMMUNICATION = 'excommunication';
    public const TYPE_COMMUNITY_SERVICE = 'community_service';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PARDONED = 'pardoned';
    public const STATUS_ESCAPED = 'escaped';

    protected $fillable = [
        'trial_id',
        'criminal_id',
        'issued_by',
        'type',
        'fine_amount',
        'jail_days',
        'exile_from_type',
        'exile_from_id',
        'community_service_hours',
        'status',
        'starts_at',
        'ends_at',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'fine_amount' => 'integer',
            'jail_days' => 'integer',
            'community_service_hours' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function trial(): BelongsTo
    {
        return $this->belongsTo(Trial::class);
    }

    public function criminal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criminal_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function jailInmate(): HasOne
    {
        return $this->hasOne(JailInmate::class);
    }

    public function outlaw(): HasOne
    {
        return $this->hasOne(Outlaw::class);
    }

    public function exile(): HasOne
    {
        return $this->hasOne(Exile::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPardoned(): bool
    {
        return $this->status === self::STATUS_PARDONED;
    }

    public function isFine(): bool
    {
        return $this->type === self::TYPE_FINE;
    }

    public function isJail(): bool
    {
        return $this->type === self::TYPE_JAIL;
    }

    public function isExile(): bool
    {
        return $this->type === self::TYPE_EXILE;
    }

    public function isOutlawry(): bool
    {
        return $this->type === self::TYPE_OUTLAWRY;
    }

    public function isExecution(): bool
    {
        return $this->type === self::TYPE_EXECUTION;
    }

    public function activate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'starts_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function pardon(User $pardonedBy, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_PARDONED,
            'completed_at' => now(),
            'notes' => $notes,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForCriminal($query, int $criminalId)
    {
        return $query->where('criminal_id', $criminalId);
    }

    public function getTypeDisplayAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_FINE => 'Fine',
            self::TYPE_JAIL => 'Imprisonment',
            self::TYPE_EXILE => 'Exile',
            self::TYPE_OUTLAWRY => 'Outlawry',
            self::TYPE_EXECUTION => 'Execution',
            self::TYPE_EXCOMMUNICATION => 'Excommunication',
            self::TYPE_COMMUNITY_SERVICE => 'Community Service',
            default => 'Unknown',
        };
    }

    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_PARDONED => 'Pardoned',
            self::STATUS_ESCAPED => 'Escaped',
            default => 'Unknown',
        };
    }

    public function getDescriptionAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_FINE => "Fine of {$this->fine_amount} gold",
            self::TYPE_JAIL => "{$this->jail_days} days imprisonment",
            self::TYPE_EXILE => "Exile from {$this->exile_from_type}",
            self::TYPE_OUTLAWRY => "Declared outlaw",
            self::TYPE_EXECUTION => "Sentenced to death",
            self::TYPE_EXCOMMUNICATION => "Excommunicated from the faith",
            self::TYPE_COMMUNITY_SERVICE => "{$this->community_service_hours} hours of community service",
            default => 'Unknown punishment',
        };
    }
}

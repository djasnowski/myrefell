<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class FarmPlot extends Model
{
    protected $fillable = [
        'user_id',
        'crop_type_id',
        'location_type',
        'location_id',
        'status',
        'planted_at',
        'ready_at',
        'withers_at',
        'quality',
        'times_tended',
        'is_watered',
        'last_watered_at',
    ];

    protected $casts = [
        'planted_at' => 'datetime',
        'ready_at' => 'datetime',
        'withers_at' => 'datetime',
        'last_watered_at' => 'datetime',
        'quality' => 'integer',
        'times_tended' => 'integer',
        'is_watered' => 'boolean',
    ];

    /**
     * The owner of this plot.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The crop type planted in this plot.
     */
    public function cropType(): BelongsTo
    {
        return $this->belongsTo(CropType::class);
    }

    /**
     * Scope for plots at a specific location.
     */
    public function scopeAtLocation(Builder $query, string $type, int $id): Builder
    {
        return $query->where('location_type', $type)->where('location_id', $id);
    }

    /**
     * Scope for plots owned by a user.
     */
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for plots that are ready to harvest.
     */
    public function scopeReady(Builder $query): Builder
    {
        return $query->where('status', 'ready');
    }

    /**
     * Scope for plots that need attention (not watered, ready, withered).
     */
    public function scopeNeedsAttention(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', 'ready')
                ->orWhere('status', 'withered')
                ->orWhere(function ($q2) {
                    $q2->whereIn('status', ['planted', 'growing'])
                        ->where('is_watered', false);
                });
        });
    }

    /**
     * Check if the crop is ready to harvest.
     */
    public function isReadyToHarvest(): bool
    {
        if ($this->status === 'ready') {
            return true;
        }

        // Check if growing crop has reached ready_at time
        if ($this->status === 'growing' && $this->ready_at && now()->gte($this->ready_at)) {
            // Update status to ready
            $this->update(['status' => 'ready']);
            return true;
        }

        return false;
    }

    /**
     * Check if the crop has withered.
     */
    public function hasWithered(): bool
    {
        if ($this->status === 'withered') {
            return true;
        }

        if ($this->status === 'ready' && $this->withers_at && now()->gte($this->withers_at)) {
            $this->update(['status' => 'withered']);
            return true;
        }

        return false;
    }

    /**
     * Plant a crop in this plot.
     */
    public function plant(CropType $cropType): bool
    {
        if ($this->status !== 'empty') {
            return false;
        }

        $readyAt = now()->addMinutes($cropType->grow_time_minutes);
        $withersAt = $readyAt->copy()->addHours(24); // 24 hours to harvest

        $this->update([
            'crop_type_id' => $cropType->id,
            'status' => 'planted',
            'planted_at' => now(),
            'ready_at' => $readyAt,
            'withers_at' => $withersAt,
            'quality' => 50,
            'times_tended' => 0,
            'is_watered' => false,
        ]);

        return true;
    }

    /**
     * Water the plot (improves quality).
     */
    public function water(): bool
    {
        if (!in_array($this->status, ['planted', 'growing'])) {
            return false;
        }

        if ($this->is_watered) {
            return false; // Already watered today
        }

        $qualityGain = rand(5, 10);
        $this->update([
            'is_watered' => true,
            'last_watered_at' => now(),
            'quality' => min(100, $this->quality + $qualityGain),
            'status' => 'growing', // Transition from planted to growing
        ]);

        return true;
    }

    /**
     * Tend the plot (improves quality significantly).
     */
    public function tend(): bool
    {
        if (!in_array($this->status, ['planted', 'growing'])) {
            return false;
        }

        $qualityGain = rand(10, 20);
        $this->update([
            'times_tended' => $this->times_tended + 1,
            'quality' => min(100, $this->quality + $qualityGain),
        ]);

        return true;
    }

    /**
     * Harvest the crop.
     */
    public function harvest(): ?array
    {
        if (!$this->isReadyToHarvest()) {
            return null;
        }

        if ($this->hasWithered()) {
            // Clear the plot, no yield
            $this->clear();
            return ['yield' => 0, 'withered' => true];
        }

        $cropType = $this->cropType;
        $yield = $cropType->rollYield($this->quality);
        $xp = $cropType->farming_xp;

        // Bonus XP for high quality
        if ($this->quality >= 80) {
            $xp = (int) ($xp * 1.5);
        }

        // Clear the plot for replanting
        $this->clear();

        return [
            'yield' => $yield,
            'xp' => $xp,
            'item_id' => $cropType->harvest_item_id,
            'item_name' => $cropType->harvestItem?->name ?? $cropType->name,
            'withered' => false,
        ];
    }

    /**
     * Clear the plot for replanting.
     */
    public function clear(): void
    {
        $this->update([
            'crop_type_id' => null,
            'status' => 'empty',
            'planted_at' => null,
            'ready_at' => null,
            'withers_at' => null,
            'quality' => 50,
            'times_tended' => 0,
            'is_watered' => false,
            'last_watered_at' => null,
        ]);
    }

    /**
     * Get the growth progress as a percentage.
     */
    public function getGrowthProgress(): int
    {
        if (!$this->planted_at || !$this->ready_at) {
            return 0;
        }

        if ($this->status === 'ready' || $this->status === 'withered') {
            return 100;
        }

        $totalSeconds = $this->ready_at->diffInSeconds($this->planted_at);
        $elapsedSeconds = now()->diffInSeconds($this->planted_at);

        if ($totalSeconds <= 0) {
            return 100;
        }

        return min(100, (int) (($elapsedSeconds / $totalSeconds) * 100));
    }

    /**
     * Get time remaining until ready.
     */
    public function getTimeRemaining(): ?string
    {
        if (!$this->ready_at || $this->status === 'ready') {
            return null;
        }

        if (now()->gte($this->ready_at)) {
            return 'Ready!';
        }

        return $this->ready_at->diffForHumans(['parts' => 2]);
    }
}

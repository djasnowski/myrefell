<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GardenPlot extends Model
{
    protected $fillable = [
        'player_house_id',
        'plot_slot',
        'crop_type_id',
        'status',
        'planted_at',
        'ready_at',
        'withers_at',
        'quality',
        'times_tended',
        'is_watered',
        'last_watered_at',
        'is_composted',
    ];

    protected function casts(): array
    {
        return [
            'planted_at' => 'datetime',
            'ready_at' => 'datetime',
            'withers_at' => 'datetime',
            'last_watered_at' => 'datetime',
            'quality' => 'integer',
            'times_tended' => 'integer',
            'is_watered' => 'boolean',
            'is_composted' => 'boolean',
        ];
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(PlayerHouse::class, 'player_house_id');
    }

    public function cropType(): BelongsTo
    {
        return $this->belongsTo(CropType::class);
    }

    /**
     * Plant a crop in this garden plot.
     * Quality starts at 60 (or 75 if composted). Grow time is 1.5x normal.
     */
    public function plant(CropType $cropType, bool $autoWater = false): bool
    {
        if ($this->status !== 'empty') {
            return false;
        }

        $growMinutes = (int) ceil($cropType->grow_time_minutes * 1.5);
        $readyAt = now()->addMinutes($growMinutes);
        $withersAt = $readyAt->copy()->addHours(24);

        $baseQuality = $this->is_composted ? 75 : 60;

        $this->update([
            'crop_type_id' => $cropType->id,
            'status' => $autoWater ? 'growing' : 'planted',
            'planted_at' => now(),
            'ready_at' => $readyAt,
            'withers_at' => $withersAt,
            'quality' => $baseQuality,
            'times_tended' => 0,
            'is_watered' => $autoWater,
            'last_watered_at' => $autoWater ? now() : null,
        ]);

        return true;
    }

    /**
     * Water the plot (improves quality).
     */
    public function water(): bool
    {
        if (! in_array($this->status, ['planted', 'growing'])) {
            return false;
        }

        if ($this->is_watered) {
            return false;
        }

        $qualityGain = rand(5, 10);
        $this->update([
            'is_watered' => true,
            'last_watered_at' => now(),
            'quality' => min(100, $this->quality + $qualityGain),
            'status' => 'growing',
        ]);

        return true;
    }

    /**
     * Tend the plot (improves quality significantly).
     */
    public function tend(): bool
    {
        if (! in_array($this->status, ['planted', 'growing'])) {
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
        if (! $this->isReadyToHarvest()) {
            return null;
        }

        if ($this->hasWithered()) {
            $this->clear();

            return ['yield' => 0, 'withered' => true];
        }

        $cropType = $this->cropType;
        $yield = $cropType->rollYield($this->quality);
        $xp = $cropType->farming_xp;

        if ($this->quality >= 80) {
            $xp = (int) ($xp * 1.5);
        }

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
            'quality' => 60,
            'times_tended' => 0,
            'is_watered' => false,
            'last_watered_at' => null,
            'is_composted' => false,
        ]);
    }

    /**
     * Check if the crop is ready to harvest.
     */
    public function isReadyToHarvest(): bool
    {
        if (in_array($this->status, ['ready', 'withered'])) {
            return true;
        }

        if ($this->status === 'growing' && $this->ready_at && now()->gte($this->ready_at)) {
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
     * Get the growth progress as a percentage.
     */
    public function getGrowthProgress(): int
    {
        if (! $this->planted_at || ! $this->ready_at) {
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
        if (! $this->ready_at || $this->status === 'ready') {
            return null;
        }

        if (now()->gte($this->ready_at)) {
            return 'Ready!';
        }

        return $this->ready_at->diffForHumans(['parts' => 2]);
    }
}

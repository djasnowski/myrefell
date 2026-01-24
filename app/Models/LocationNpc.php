<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationNpc extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id',
        'location_type',
        'location_id',
        'npc_name',
        'npc_description',
        'npc_icon',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the role this NPC represents.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the location model.
     */
    public function getLocationAttribute(): Model|null
    {
        return match ($this->location_type) {
            'village' => Village::find($this->location_id),
            'castle' => Castle::find($this->location_id),
            'kingdom' => Kingdom::find($this->location_id),
            default => null,
        };
    }

    /**
     * Get the location name.
     */
    public function getLocationNameAttribute(): string
    {
        return $this->location?->name ?? 'Unknown Location';
    }

    /**
     * Check if this NPC should be active (no player holds the role).
     */
    public function shouldBeActive(): bool
    {
        $playerHoldsRole = PlayerRole::where('role_id', $this->role_id)
            ->where('location_type', $this->location_type)
            ->where('location_id', $this->location_id)
            ->where('status', PlayerRole::STATUS_ACTIVE)
            ->exists();

        return !$playerHoldsRole;
    }

    /**
     * Activate this NPC.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate this NPC.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Scope to active NPCs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to NPCs at a specific location.
     */
    public function scopeAtLocation($query, string $locationType, int $locationId)
    {
        return $query->where('location_type', $locationType)
            ->where('location_id', $locationId);
    }

    /**
     * Get NPC names by role type for random generation.
     */
    public static function generateNpcName(string $roleSlug): string
    {
        $prefixes = [
            'elder' => ['Old', 'Wise', 'Elder'],
            'blacksmith' => ['Iron', 'Steel', 'Forge'],
            'merchant' => ['Rich', 'Trader', 'Merchant'],
            'guard_captain' => ['Captain', 'Sergeant', 'Guard'],
            'healer' => ['Gentle', 'Healing', 'Kind'],
            'lord' => ['Lord', 'Baron', 'Count'],
            'steward' => ['Steward', 'Keeper', 'Master'],
            'marshal' => ['Marshal', 'Commander', 'Warden'],
            'treasurer' => ['Treasurer', 'Keeper', 'Master'],
            'jailsman' => ['Jailer', 'Warden', 'Keeper'],
            'king' => ['King', 'Regent', 'Ruler'],
            'chancellor' => ['Chancellor', 'Advisor', 'Sage'],
            'general' => ['General', 'Commander', 'Marshal'],
            'royal_treasurer' => ['Royal', 'Grand', 'High'],
        ];

        $surnames = [
            'Ironforge', 'Goldsworth', 'Silverbane', 'Oakshield', 'Stoneheart',
            'Meadowbrook', 'Riverwind', 'Thornwood', 'Brightforge', 'Darkhollow',
            'Fairweather', 'Strongarm', 'Swiftfoot', 'Lightbringer', 'Shadowmend',
        ];

        $prefix = $prefixes[$roleSlug] ?? [''];
        $chosenPrefix = $prefix[array_rand($prefix)];
        $surname = $surnames[array_rand($surnames)];

        return trim("$chosenPrefix $surname");
    }
}

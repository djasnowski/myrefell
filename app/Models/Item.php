<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    public const TYPES = ['weapon', 'armor', 'resource', 'consumable', 'tool', 'misc'];

    public const RARITIES = ['common', 'uncommon', 'rare', 'epic', 'legendary'];

    public const EQUIPMENT_SLOTS = [
        'head',
        'chest',
        'legs',
        'feet',
        'hands',
        'weapon',
        'shield',
        'ring',
        'amulet',
    ];

    protected $fillable = [
        'name',
        'description',
        'type',
        'subtype',
        'rarity',
        'stackable',
        'max_stack',
        'atk_bonus',
        'str_bonus',
        'def_bonus',
        'hp_bonus',
        'energy_bonus',
        'effectiveness_type',
        'effective_against',
        'weak_against',
        'equipment_slot',
        'required_level',
        'required_skill',
        'required_skill_level',
        'base_value',
        'is_perishable',
        'decay_rate_per_week',
        'spoil_after_weeks',
        'decays_into',
    ];

    protected function casts(): array
    {
        return [
            'stackable' => 'boolean',
            'max_stack' => 'integer',
            'atk_bonus' => 'integer',
            'str_bonus' => 'integer',
            'def_bonus' => 'integer',
            'hp_bonus' => 'integer',
            'energy_bonus' => 'integer',
            'effective_against' => 'array',
            'weak_against' => 'array',
            'required_level' => 'integer',
            'required_skill_level' => 'integer',
            'base_value' => 'integer',
            'is_perishable' => 'boolean',
            'decay_rate_per_week' => 'integer',
            'spoil_after_weeks' => 'integer',
        ];
    }

    /**
     * Check if this item is equippable.
     */
    public function isEquippable(): bool
    {
        return $this->equipment_slot !== null;
    }

    /**
     * Check if this item is a weapon.
     */
    public function isWeapon(): bool
    {
        return $this->type === 'weapon';
    }

    /**
     * Check if this item is armor.
     */
    public function isArmor(): bool
    {
        return $this->type === 'armor';
    }

    /**
     * Check if this item is a resource.
     */
    public function isResource(): bool
    {
        return $this->type === 'resource';
    }

    /**
     * Check if this item is consumable.
     */
    public function isConsumable(): bool
    {
        return $this->type === 'consumable';
    }

    /**
     * Get total combat power of this item.
     */
    public function getCombatPowerAttribute(): int
    {
        return $this->atk_bonus + $this->str_bonus + $this->def_bonus;
    }

    /**
     * Check if this item is perishable.
     */
    public function isPerishable(): bool
    {
        return $this->is_perishable;
    }

    /**
     * Check if this item decays over time (loses quantity).
     */
    public function decaysOverTime(): bool
    {
        return $this->is_perishable && $this->decay_rate_per_week > 0;
    }

    /**
     * Check if this item spoils after a certain time (transforms into another item).
     */
    public function spoilsAfterTime(): bool
    {
        return $this->is_perishable && $this->spoil_after_weeks !== null;
    }

    /**
     * Get the item this transforms into when spoiled.
     */
    public function getSpoiledItem(): ?self
    {
        if (! $this->decays_into) {
            return null;
        }

        return self::where('name', $this->decays_into)->first();
    }

    /**
     * Scope to get perishable items.
     */
    public function scopePerishable($query)
    {
        return $query->where('is_perishable', true);
    }

    /**
     * Scope to get items that decay over time.
     */
    public function scopeDecaying($query)
    {
        return $query->where('is_perishable', true)
            ->where('decay_rate_per_week', '>', 0);
    }

    /**
     * Scope to get items that spoil after time.
     */
    public function scopeSpoiling($query)
    {
        return $query->where('is_perishable', true)
            ->whereNotNull('spoil_after_weeks');
    }
}

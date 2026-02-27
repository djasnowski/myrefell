<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'npc_name',
        'npc_description',
        'description',
        'location_type',
        'location_id',
        'icon',
        'map_position_x',
        'map_position_y',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'location_id' => 'integer',
            'map_position_x' => 'integer',
            'map_position_y' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the shop's items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShopItem::class)->orderBy('sort_order');
    }

    /**
     * Scope to active shops only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to shops at a specific location.
     */
    public function scopeAtLocation(Builder $query, string $locationType, int $locationId): Builder
    {
        return $query->where('location_type', $locationType)
            ->where('location_id', $locationId);
    }

    /**
     * Get the route key name for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

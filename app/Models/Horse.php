<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Horse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'speed_multiplier',
        'base_price',
        'min_location_type',
        'base_stamina',
        'stamina_cost_per_travel',
        'rarity',
    ];

    protected $casts = [
        'speed_multiplier' => 'decimal:1',
        'base_price' => 'integer',
        'base_stamina' => 'integer',
        'stamina_cost_per_travel' => 'integer',
        'rarity' => 'integer',
    ];

    /**
     * Location type hierarchy for availability
     */
    public const LOCATION_HIERARCHY = [
        'village' => 1,
        'town' => 2,
        'barony' => 3,
        'duchy' => 4,
        'kingdom' => 5,
    ];

    public function playerHorses(): HasMany
    {
        return $this->hasMany(PlayerHorse::class);
    }

    /**
     * Check if this horse is available at a given location type
     */
    public function isAvailableAt(string $locationType): bool
    {
        $minLevel = self::LOCATION_HIERARCHY[$this->min_location_type] ?? 1;
        $locationLevel = self::LOCATION_HIERARCHY[$locationType] ?? 1;

        return $locationLevel >= $minLevel;
    }

    /**
     * Get horses available at a specific location type
     */
    public static function availableAt(string $locationType): \Illuminate\Database\Eloquent\Collection
    {
        $locationLevel = self::LOCATION_HIERARCHY[$locationType] ?? 1;

        return self::query()
            ->whereIn('min_location_type', array_keys(
                array_filter(self::LOCATION_HIERARCHY, fn ($level) => $level <= $locationLevel)
            ))
            ->orderBy('speed_multiplier')
            ->get();
    }

    /**
     * Get the price with optional variance for this sale
     */
    public function getPriceWithVariance(int $variancePercent = 10): int
    {
        $variance = $this->base_price * ($variancePercent / 100);
        return $this->base_price + random_int((int) -$variance, (int) $variance);
    }
}

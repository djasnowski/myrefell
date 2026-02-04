<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HqFeatureType extends Model
{
    public const CATEGORY_ALTAR = 'altar';

    public const CATEGORY_LIBRARY = 'library';

    public const CATEGORY_VAULT = 'vault';

    public const CATEGORY_GARDEN = 'garden';

    public const CATEGORY_SANCTUM = 'sanctum';

    public const CATEGORY_RELIQUARY = 'reliquary';

    public const CATEGORY_TRAINING = 'training';

    /**
     * Base energy cost to pray at a feature, by HQ tier requirement.
     */
    public const PRAYER_ENERGY_COST = [
        1 => 25,
        2 => 30,
        3 => 35,
        4 => 40,
        5 => 45,
        6 => 50,
    ];

    /**
     * Base devotion cost to pray at a feature, by level.
     */
    public const PRAYER_DEVOTION_COST = [
        1 => 50,
        2 => 100,
        3 => 200,
        4 => 400,
        5 => 800,
    ];

    /**
     * Buff duration in minutes, by level.
     */
    public const PRAYER_DURATION_MINUTES = [
        1 => 5,
        2 => 10,
        3 => 15,
        4 => 20,
        5 => 30,
    ];

    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon',
        'category',
        'min_hq_tier',
        'max_level',
        'effects',
        'level_costs',
    ];

    protected function casts(): array
    {
        return [
            'min_hq_tier' => 'integer',
            'max_level' => 'integer',
            'effects' => 'array',
            'level_costs' => 'array',
        ];
    }

    /**
     * Get all built features of this type.
     */
    public function builtFeatures(): HasMany
    {
        return $this->hasMany(ReligionHqFeature::class);
    }

    /**
     * Get the cost for a specific level.
     *
     * @return array{gold: int, devotion: int, items: array}
     */
    public function getCostForLevel(int $level): array
    {
        return $this->level_costs[$level] ?? [
            'gold' => 0,
            'devotion' => 0,
            'items' => [],
        ];
    }

    /**
     * Get the effects for a specific level.
     *
     * @return array<string, int|float>
     */
    public function getEffectsForLevel(int $level): array
    {
        return $this->effects[$level] ?? [];
    }

    /**
     * Get total cost to build up to a level (sum of all levels).
     *
     * @return array{gold: int, devotion: int, items: array}
     */
    public function getTotalCostToLevel(int $level): array
    {
        $totalGold = 0;
        $totalDevotion = 0;
        $totalItems = [];

        for ($i = 1; $i <= $level; $i++) {
            $cost = $this->getCostForLevel($i);
            $totalGold += $cost['gold'];
            $totalDevotion += $cost['devotion'];

            foreach ($cost['items'] as $itemId => $quantity) {
                $totalItems[$itemId] = ($totalItems[$itemId] ?? 0) + $quantity;
            }
        }

        return [
            'gold' => $totalGold,
            'devotion' => $totalDevotion,
            'items' => $totalItems,
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\CropType;
use Illuminate\Database\Seeder;

class CropTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $crops = [
            // Basic crops (low level, fast growth)
            [
                'name' => 'Wheat',
                'slug' => 'wheat',
                'icon' => 'wheat',
                'description' => 'The staple grain of the realm. Quick to grow and essential for bread.',
                'grow_time_minutes' => 30,
                'farming_level_required' => 1,
                'farming_xp' => 8,
                'yield_min' => 2,
                'yield_max' => 5,
                'plant_cost' => 5,
                'seasons' => ['spring', 'summer', 'autumn'],
            ],
            [
                'name' => 'Potatoes',
                'slug' => 'potatoes',
                'icon' => 'potato',
                'description' => 'Hardy root vegetables that grow well in most conditions.',
                'grow_time_minutes' => 45,
                'farming_level_required' => 1,
                'farming_xp' => 10,
                'yield_min' => 3,
                'yield_max' => 6,
                'plant_cost' => 8,
                'seasons' => ['spring', 'summer'],
            ],
            [
                'name' => 'Carrots',
                'slug' => 'carrots',
                'icon' => 'carrot',
                'description' => 'Orange root vegetables, good for stews and horse feed.',
                'grow_time_minutes' => 40,
                'farming_level_required' => 5,
                'farming_xp' => 12,
                'yield_min' => 2,
                'yield_max' => 5,
                'plant_cost' => 10,
                'seasons' => ['spring', 'autumn'],
            ],
            [
                'name' => 'Cabbage',
                'slug' => 'cabbage',
                'icon' => 'salad',
                'description' => 'Leafy green vegetables perfect for soups and salads.',
                'grow_time_minutes' => 50,
                'farming_level_required' => 10,
                'farming_xp' => 15,
                'yield_min' => 1,
                'yield_max' => 3,
                'plant_cost' => 12,
                'seasons' => ['spring', 'autumn'],
            ],
            [
                'name' => 'Onions',
                'slug' => 'onions',
                'icon' => 'circle',
                'description' => 'Pungent bulbs used in countless recipes.',
                'grow_time_minutes' => 60,
                'farming_level_required' => 15,
                'farming_xp' => 18,
                'yield_min' => 2,
                'yield_max' => 4,
                'plant_cost' => 15,
                'seasons' => ['spring', 'summer'],
            ],
            // Intermediate crops
            [
                'name' => 'Corn',
                'slug' => 'corn',
                'icon' => 'wheat',
                'description' => 'Tall stalks bearing golden ears. Great for feed and flour.',
                'grow_time_minutes' => 90,
                'farming_level_required' => 20,
                'farming_xp' => 25,
                'yield_min' => 3,
                'yield_max' => 7,
                'plant_cost' => 20,
                'seasons' => ['summer'],
            ],
            [
                'name' => 'Tomatoes',
                'slug' => 'tomatoes',
                'icon' => 'apple',
                'description' => 'Red fruits that brighten any dish.',
                'grow_time_minutes' => 75,
                'farming_level_required' => 25,
                'farming_xp' => 22,
                'yield_min' => 2,
                'yield_max' => 6,
                'plant_cost' => 18,
                'seasons' => ['summer'],
            ],
            [
                'name' => 'Pumpkins',
                'slug' => 'pumpkins',
                'icon' => 'citrus',
                'description' => 'Large orange gourds, festive and nutritious.',
                'grow_time_minutes' => 120,
                'farming_level_required' => 30,
                'farming_xp' => 35,
                'yield_min' => 1,
                'yield_max' => 3,
                'plant_cost' => 25,
                'seasons' => ['autumn'],
            ],
            // Advanced crops
            [
                'name' => 'Grapes',
                'slug' => 'grapes',
                'icon' => 'grape',
                'description' => 'Sweet clusters perfect for wine making.',
                'grow_time_minutes' => 180,
                'farming_level_required' => 40,
                'farming_xp' => 50,
                'yield_min' => 3,
                'yield_max' => 8,
                'plant_cost' => 40,
                'seasons' => ['summer', 'autumn'],
            ],
            [
                'name' => 'Hops',
                'slug' => 'hops',
                'icon' => 'hop',
                'description' => 'Essential ingredient for brewing fine ales.',
                'grow_time_minutes' => 150,
                'farming_level_required' => 35,
                'farming_xp' => 45,
                'yield_min' => 2,
                'yield_max' => 5,
                'plant_cost' => 35,
                'seasons' => ['summer'],
            ],
            // Herbs
            [
                'name' => 'Herbs',
                'slug' => 'herbs',
                'icon' => 'leaf',
                'description' => 'Aromatic plants used in cooking and medicine.',
                'grow_time_minutes' => 25,
                'farming_level_required' => 10,
                'farming_xp' => 12,
                'yield_min' => 1,
                'yield_max' => 4,
                'plant_cost' => 15,
                'seasons' => null, // All seasons
            ],
            [
                'name' => 'Flax',
                'slug' => 'flax',
                'icon' => 'flower',
                'description' => 'Blue-flowered plant used for linen and oil.',
                'grow_time_minutes' => 100,
                'farming_level_required' => 45,
                'farming_xp' => 40,
                'yield_min' => 2,
                'yield_max' => 5,
                'plant_cost' => 30,
                'seasons' => ['spring', 'summer'],
            ],
        ];

        foreach ($crops as $crop) {
            CropType::updateOrCreate(
                ['slug' => $crop['slug']],
                $crop
            );
        }
    }
}

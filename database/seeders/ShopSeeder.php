<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Kingdom;
use App\Models\Shop;
use App\Models\ShopItem;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedAshenfell();
        $this->seedValdoria();
        $this->seedFrostholm();
        $this->seedSandmar();
    }

    protected function seedAshenfell(): void
    {
        $kingdom = Kingdom::where('name', 'Ashenfell')->first();
        if (! $kingdom) {
            return;
        }

        $shop = Shop::updateOrCreate(
            ['slug' => 'cindervault-stoneworks'],
            [
                'name' => 'Cindervault Stoneworks',
                'npc_name' => 'Bram Ashveil',
                'npc_description' => 'A grizzled stonemason who quarries from the volcanic cliffs. His hands are grey with stone dust and his left eyebrow is singed from years near the lava flows.',
                'description' => 'Volcanic stone and masonry supplies',
                'location_type' => 'kingdom',
                'location_id' => $kingdom->id,
                'icon' => 'store',
                'map_position_x' => 40,
                'map_position_y' => 50,
            ]
        );

        $this->addShopItem($shop, 'Marble Block', 325000, 0);
        $this->addShopItem($shop, 'Limestone Brick', 5000, 1);
        $this->addShopItem($shop, 'Limestone', 500, 2);
        $this->addShopItem($shop, 'Gold Leaf', 200000, 3);
        $this->addShopItem($shop, 'Magic Stone', 8000000, 4);
    }

    protected function seedValdoria(): void
    {
        $kingdom = Kingdom::where('name', 'Valdoria')->first();
        if (! $kingdom) {
            return;
        }

        $shop = Shop::updateOrCreate(
            ['slug' => 'the-golden-furrow'],
            [
                'name' => 'The Golden Furrow',
                'npc_name' => 'Elara Wheatley',
                'npc_description' => 'A cheerful supplier with sun-weathered skin and a crown of braided wheat in her hair.',
                'description' => 'Seeds and farming supplies',
                'location_type' => 'kingdom',
                'location_id' => $kingdom->id,
                'icon' => 'store',
                'map_position_x' => 60,
                'map_position_y' => 40,
            ]
        );

        $this->addShopItem($shop, 'Wheat Seeds', 50, 0);
        $this->addShopItem($shop, 'Potato Seeds', 75, 1);
        $this->addShopItem($shop, 'Carrot Seeds', 75, 2);
        $this->addShopItem($shop, 'Cabbage Seeds', 100, 3);
        $this->addShopItem($shop, 'Tomato Seeds', 125, 4);
        $this->addShopItem($shop, 'Corn Seeds', 125, 5);
        $this->addShopItem($shop, 'Pumpkin Seeds', 150, 6);
        $this->addShopItem($shop, 'Herb Seeds', 100, 7);
        $this->addShopItem($shop, 'Grape Seeds', 250, 8);
        $this->addShopItem($shop, 'Grain', 25, 9);
    }

    protected function seedFrostholm(): void
    {
        $kingdom = Kingdom::where('name', 'Frostholm')->first();
        if (! $kingdom) {
            return;
        }

        $shop = Shop::updateOrCreate(
            ['slug' => 'ironbark-provisions'],
            [
                'name' => 'Ironbark Provisions',
                'npc_name' => 'Gudrun Frostmantle',
                'npc_description' => 'A broad-shouldered woman wrapped in bear pelts who speaks in clipped sentences and never seems cold.',
                'description' => 'Cold-weather supplies and provisions',
                'location_type' => 'kingdom',
                'location_id' => $kingdom->id,
                'icon' => 'store',
                'map_position_x' => 50,
                'map_position_y' => 45,
            ]
        );

        $this->addShopItem($shop, 'Coal', 150, 0);
        $this->addShopItem($shop, 'Torch', 75, 1);
        $this->addShopItem($shop, 'Leather', 250, 2);
        $this->addShopItem($shop, 'Cloth', 500, 3);
        $this->addShopItem($shop, 'Flax', 100, 4);
        $this->addShopItem($shop, 'Rope', 200, 5);
        $this->addShopItem($shop, 'Needle', 150, 6);
    }

    protected function seedSandmar(): void
    {
        $kingdom = Kingdom::where('name', 'Sandmar')->first();
        if (! $kingdom) {
            return;
        }

        $shop = Shop::updateOrCreate(
            ['slug' => 'the-coral-bazaar'],
            [
                'name' => 'The Coral Bazaar',
                'npc_name' => 'Kadir al-Sayf',
                'npc_description' => 'A wiry merchant with a salt-and-pepper beard and a collection of coral jewelry he insists are merely decorative.',
                'description' => 'Exotic goods and maritime supplies',
                'location_type' => 'kingdom',
                'location_id' => $kingdom->id,
                'icon' => 'store',
                'map_position_x' => 45,
                'map_position_y' => 55,
            ]
        );

        $this->addShopItem($shop, 'Vial', 50, 0);
        $this->addShopItem($shop, 'Crystal Vial', 2500, 1);
        $this->addShopItem($shop, 'Fishing Rod', 500, 2);
        $this->addShopItem($shop, 'Fishing Net', 1500, 3);
        $this->addShopItem($shop, 'Chisel', 400, 4);
        $this->addShopItem($shop, 'Rope', 200, 5);
        $this->addShopItem($shop, 'Turtle Shell Powder', 1500, 6);
    }

    /**
     * Add an item to a shop by item name.
     */
    protected function addShopItem(Shop $shop, string $itemName, int $price, int $sortOrder): void
    {
        $item = Item::where('name', $itemName)->first();
        if (! $item) {
            return;
        }

        ShopItem::updateOrCreate(
            [
                'shop_id' => $shop->id,
                'item_id' => $item->id,
            ],
            [
                'price' => $price,
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]
        );
    }
}

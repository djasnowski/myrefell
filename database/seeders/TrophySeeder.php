<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class TrophySeeder extends Seeder
{
    public function run(): void
    {
        $trophies = [
            [
                'name' => 'Bandit Trophy',
                'description' => 'The severed insignia of a fearsome bandit. A grim reminder of victory.',
                'rarity' => 'rare',
                'base_value' => 500,
            ],
            [
                'name' => 'Hobgoblin Trophy',
                'description' => 'A hobgoblin war totem, claimed in battle. Proof of martial prowess.',
                'rarity' => 'rare',
                'base_value' => 650,
            ],
            [
                'name' => 'Bear Trophy',
                'description' => 'A massive bear claw mounted on a plaque. A testament to primal strength.',
                'rarity' => 'rare',
                'base_value' => 900,
            ],
            [
                'name' => 'Dark Mage Trophy',
                'description' => 'A shattered staff crystal from a dark mage. Its dark energy still lingers.',
                'rarity' => 'rare',
                'base_value' => 1200,
            ],
            [
                'name' => 'Troll Trophy',
                'description' => 'A petrified troll fang, impossibly dense. Few warriors claim such a prize.',
                'rarity' => 'rare',
                'base_value' => 1800,
            ],
            [
                'name' => 'Ice Elemental Trophy',
                'description' => 'A frozen elemental core that never melts. Radiates bitter cold.',
                'rarity' => 'rare',
                'base_value' => 2200,
            ],
            [
                'name' => 'Fire Elemental Trophy',
                'description' => 'A smoldering elemental core that never cools. Pulsates with inner flame.',
                'rarity' => 'rare',
                'base_value' => 2200,
            ],
            [
                'name' => 'Ogre Trophy',
                'description' => 'An ogre\'s iron-banded club, too heavy for most to lift. A symbol of brute conquest.',
                'rarity' => 'rare',
                'base_value' => 3000,
            ],
            [
                'name' => 'Demon Trophy',
                'description' => 'A demon horn wreathed in shadow. Exudes an aura of dread and power.',
                'rarity' => 'rare',
                'base_value' => 5000,
            ],
            [
                'name' => 'Wyvern Trophy',
                'description' => 'A wyvern scale plate, iridescent and impervious. A dragon-kin\'s legacy.',
                'rarity' => 'rare',
                'base_value' => 2500,
            ],
            [
                'name' => 'Goblin King Trophy',
                'description' => 'The Goblin King\'s crown, dented and bloodied. A legendary dungeon conquest.',
                'rarity' => 'legendary',
                'base_value' => 15000,
            ],
            [
                'name' => 'Lich Trophy',
                'description' => 'A lich\'s phylactery, cracked but still pulsing with undeath. Extremely rare.',
                'rarity' => 'legendary',
                'base_value' => 30000,
            ],
            [
                'name' => 'Elder Dragon Trophy',
                'description' => 'An elder dragon\'s heartscale, warm to the touch. The ultimate trophy.',
                'rarity' => 'legendary',
                'base_value' => 50000,
            ],
        ];

        foreach ($trophies as $trophy) {
            Item::updateOrCreate(
                ['name' => $trophy['name']],
                [
                    'description' => $trophy['description'],
                    'type' => 'misc',
                    'subtype' => 'trophy',
                    'rarity' => $trophy['rarity'],
                    'stackable' => false,
                    'max_stack' => 1,
                    'base_value' => $trophy['base_value'],
                    'is_tradeable' => true,
                ]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\TitleType;
use Illuminate\Database\Seeder;

class TitleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $titles = [
            // ============================================
            // COMMONER TITLES (Tier 1-2)
            // ============================================
            [
                'name' => 'Serf',
                'slug' => 'serf',
                'tier' => 1,
                'category' => TitleType::CATEGORY_COMMONER,
                'is_landed' => false,
                'domain_type' => null,
                'limit_per_domain' => null,
                'limit_per_superior' => null,
                'granted_by' => 'baron,king', // Can be enserfed by baron or king
                'style_of_address' => null,
                'female_variant' => null,
                'description' => 'Bound to the land, owes labor to their lord.',
                'prestige_bonus' => 0,
            ],
            [
                'name' => 'Peasant',
                'slug' => 'peasant',
                'tier' => 2,
                'category' => TitleType::CATEGORY_COMMONER,
                'is_landed' => false,
                'domain_type' => null,
                'limit_per_domain' => null,
                'limit_per_superior' => null,
                'granted_by' => null, // Default starting title
                'style_of_address' => null,
                'female_variant' => null,
                'description' => 'A free commoner working the land.',
                'prestige_bonus' => 0,
            ],
            [
                'name' => 'Freeman',
                'slug' => 'freeman',
                'tier' => 3,
                'category' => TitleType::CATEGORY_COMMONER,
                'is_landed' => false,
                'domain_type' => null,
                'limit_per_domain' => null,
                'limit_per_superior' => null,
                'granted_by' => 'baron,king',
                'style_of_address' => null,
                'female_variant' => 'Freewoman',
                'description' => 'A commoner with full rights of citizenship.',
                'prestige_bonus' => 5,
            ],
            [
                'name' => 'Yeoman',
                'slug' => 'yeoman',
                'tier' => 4,
                'category' => TitleType::CATEGORY_COMMONER,
                'is_landed' => false,
                'domain_type' => null,
                'limit_per_domain' => null,
                'limit_per_superior' => null,
                'granted_by' => 'baron,count,duke,king',
                'style_of_address' => 'Goodman',
                'female_variant' => 'Goodwife',
                'description' => 'A prosperous commoner who owns land and may bear arms.',
                'prestige_bonus' => 10,
            ],

            // ============================================
            // MINOR NOBILITY (Tier 5-7)
            // ============================================
            [
                'name' => 'Squire',
                'slug' => 'squire',
                'tier' => 5,
                'category' => TitleType::CATEGORY_MINOR_NOBILITY,
                'is_landed' => false,
                'domain_type' => 'barony',
                'limit_per_domain' => null, // No hard domain limit
                'limit_per_superior' => 2, // Each Knight can have 2 Squires
                'granted_by' => 'knight,baronet,baron,count,duke,king',
                'style_of_address' => 'Squire',
                'female_variant' => 'Squiress',
                'description' => 'A young noble in training to become a knight.',
                'prestige_bonus' => 15,
            ],
            [
                'name' => 'Knight',
                'slug' => 'knight',
                'tier' => 6,
                'category' => TitleType::CATEGORY_MINOR_NOBILITY,
                'is_landed' => false,
                'domain_type' => 'barony',
                'limit_per_domain' => 10, // Max 10 Knights per Barony
                'limit_per_superior' => null,
                'granted_by' => 'baronet,baron,viscount,count,marquess,duke,prince,king',
                'style_of_address' => 'Sir',
                'female_variant' => 'Dame',
                'description' => 'A warrior of noble rank who has sworn fealty.',
                'prestige_bonus' => 25,
            ],
            [
                'name' => 'Baronet',
                'slug' => 'baronet',
                'tier' => 7,
                'category' => TitleType::CATEGORY_MINOR_NOBILITY,
                'is_landed' => false,
                'domain_type' => 'barony',
                'limit_per_domain' => 5, // Max 5 Baronets per Barony
                'limit_per_superior' => null,
                'granted_by' => 'baron,count,duke,king',
                'style_of_address' => 'Sir',
                'female_variant' => 'Dame',
                'description' => 'A hereditary title ranking below Baron but above Knight.',
                'prestige_bonus' => 40,
            ],

            // ============================================
            // LANDED NOBILITY (Tier 8-12)
            // ============================================
            [
                'name' => 'Baron',
                'slug' => 'baron',
                'tier' => 8,
                'category' => TitleType::CATEGORY_LANDED_NOBILITY,
                'is_landed' => true,
                'domain_type' => 'barony',
                'limit_per_domain' => 1, // 1 Baron per Barony
                'limit_per_superior' => null,
                'granted_by' => 'duke,king',
                'style_of_address' => 'Lord',
                'female_variant' => 'Baroness',
                'description' => 'Ruler of a barony, the lowest rank of landed nobility.',
                'prestige_bonus' => 60,
            ],
            [
                'name' => 'Viscount',
                'slug' => 'viscount',
                'tier' => 9,
                'category' => TitleType::CATEGORY_LANDED_NOBILITY,
                'is_landed' => false, // Honorary, deputy to Count
                'domain_type' => 'duchy',
                'limit_per_domain' => 4, // Max 4 Viscounts per Duchy
                'limit_per_superior' => 2, // Each Count can have 2 Viscounts
                'granted_by' => 'count,duke,king',
                'style_of_address' => 'Lord',
                'female_variant' => 'Viscountess',
                'description' => 'Deputy to a Count, ranking between Baron and Count.',
                'prestige_bonus' => 80,
            ],
            [
                'name' => 'Count',
                'slug' => 'count',
                'tier' => 10,
                'category' => TitleType::CATEGORY_LANDED_NOBILITY,
                'is_landed' => false, // Honorary within duchy
                'domain_type' => 'duchy',
                'limit_per_domain' => 2, // Max 2 Counts per Duchy
                'limit_per_superior' => null,
                'granted_by' => 'duke,king',
                'style_of_address' => 'Lord',
                'female_variant' => 'Countess',
                'description' => 'A high noble ranking below Duke, also known as Earl.',
                'prestige_bonus' => 100,
            ],
            [
                'name' => 'Marquess',
                'slug' => 'marquess',
                'tier' => 11,
                'category' => TitleType::CATEGORY_LANDED_NOBILITY,
                'is_landed' => true,
                'domain_type' => 'duchy',
                'limit_per_domain' => 1, // 1 Marquess per border Duchy
                'limit_per_superior' => null,
                'granted_by' => 'king',
                'style_of_address' => 'Lord',
                'female_variant' => 'Marchioness',
                'description' => 'A noble who guards the borders of the realm.',
                'prestige_bonus' => 120,
            ],
            [
                'name' => 'Duke',
                'slug' => 'duke',
                'tier' => 12,
                'category' => TitleType::CATEGORY_LANDED_NOBILITY,
                'is_landed' => true,
                'domain_type' => 'duchy',
                'limit_per_domain' => 1, // 1 Duke per Duchy
                'limit_per_superior' => null,
                'granted_by' => 'king,emperor',
                'style_of_address' => 'Your Grace',
                'female_variant' => 'Duchess',
                'description' => 'Ruler of a duchy, the highest rank below royalty.',
                'prestige_bonus' => 150,
            ],

            // ============================================
            // ROYALTY (Tier 13-15)
            // ============================================
            [
                'name' => 'Prince',
                'slug' => 'prince',
                'tier' => 13,
                'category' => TitleType::CATEGORY_ROYALTY,
                'is_landed' => false,
                'domain_type' => 'kingdom',
                'limit_per_domain' => 3, // Max 3 Princes per Kingdom
                'limit_per_superior' => null,
                'granted_by' => 'king,emperor',
                'style_of_address' => 'Your Royal Highness',
                'female_variant' => 'Princess',
                'description' => 'Royal blood, heir to the throne or child of the King.',
                'prestige_bonus' => 200,
            ],
            [
                'name' => 'King',
                'slug' => 'king',
                'tier' => 14,
                'category' => TitleType::CATEGORY_ROYALTY,
                'is_landed' => true,
                'domain_type' => 'kingdom',
                'limit_per_domain' => 1, // 1 King per Kingdom
                'limit_per_superior' => null,
                'granted_by' => 'emperor', // Or by election/conquest
                'style_of_address' => 'Your Majesty',
                'female_variant' => 'Queen',
                'description' => 'Sovereign ruler of a kingdom.',
                'prestige_bonus' => 300,
            ],
            [
                'name' => 'Emperor',
                'slug' => 'emperor',
                'tier' => 15,
                'category' => TitleType::CATEGORY_ROYALTY,
                'is_landed' => true,
                'domain_type' => null, // Rules multiple kingdoms
                'limit_per_domain' => null,
                'limit_per_superior' => null,
                'granted_by' => null, // Only through conquest/unification
                'style_of_address' => 'Your Imperial Majesty',
                'female_variant' => 'Empress',
                'description' => 'Ruler of multiple kingdoms, the highest temporal authority.',
                'prestige_bonus' => 500,
            ],
        ];

        foreach ($titles as $title) {
            TitleType::updateOrCreate(
                ['slug' => $title['slug']],
                $title
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\TitleType;
use Illuminate\Database\Seeder;

class TitleTypeSeeder extends Seeder
{
    /**
     * Progression types:
     * - automatic: Granted when requirements met (check periodically)
     * - petition: Player requests, superior approves if requirements met
     * - appointment: Pure political - superior grants at will
     * - special: Unique rules (inheritance, conquest, election)
     */
    public function run(): void
    {
        $titles = [
            // ============================================
            // COMMONER TITLES (Automatic progression)
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
                'granted_by' => 'baron,king',
                'progression_type' => 'special', // Enserfment as punishment
                'requirements' => null,
                'can_purchase' => false,
                'purchase_cost' => null,
                'service_days_required' => null,
                'service_title_slug' => null,
                'requires_ceremony' => false,
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
                'granted_by' => null,
                'progression_type' => 'automatic', // Default starting title
                'requirements' => null,
                'can_purchase' => false,
                'purchase_cost' => null,
                'service_days_required' => null,
                'service_title_slug' => null,
                'requires_ceremony' => false,
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
                'progression_type' => 'automatic',
                'requirements' => json_encode([
                    'min_gold' => 10000,
                    'min_combat_level' => 10,
                    'or_conditions' => [
                        ['purchase' => true],
                        ['military_service_days' => 90],
                        ['baron_decree' => true],
                    ],
                ]),
                'can_purchase' => true,
                'purchase_cost' => 100000, // 100k gold to buy freedom
                'service_days_required' => null,
                'service_title_slug' => null,
                'requires_ceremony' => false,
                'style_of_address' => null,
                'female_variant' => 'Freewoman',
                'description' => 'A commoner with full rights of citizenship. Earned through wealth, military service, or lord\'s decree.',
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
                'progression_type' => 'automatic',
                'requirements' => json_encode([
                    'min_gold' => 50000,
                    'min_combat_level' => 20,
                    'owns_property' => true,
                    'militia_service_days' => 30,
                ]),
                'can_purchase' => false,
                'purchase_cost' => null,
                'service_days_required' => 30,
                'service_title_slug' => null,
                'requires_ceremony' => false,
                'style_of_address' => 'Goodman',
                'female_variant' => 'Goodwife',
                'description' => 'A prosperous commoner who owns land, bears arms, and has served in the militia.',
                'prestige_bonus' => 10,
            ],

            // ============================================
            // MINOR NOBILITY (Petition + Ceremony)
            // ============================================
            [
                'name' => 'Squire',
                'slug' => 'squire',
                'tier' => 5,
                'category' => TitleType::CATEGORY_MINOR_NOBILITY,
                'is_landed' => false,
                'domain_type' => 'barony',
                'limit_per_domain' => null,
                'limit_per_superior' => 2, // Each Knight can have 2 Squires
                'granted_by' => 'knight,baronet,baron,count,duke,king',
                'progression_type' => 'petition',
                'requirements' => json_encode([
                    'min_title_tier' => 4, // Must be Yeoman
                    'min_combat_level' => 15,
                    'min_age' => 14,
                    'max_age' => 25, // Historical: squires were young
                ]),
                'can_purchase' => false,
                'purchase_cost' => null,
                'service_days_required' => null,
                'service_title_slug' => null,
                'requires_ceremony' => true, // Oath of service
                'style_of_address' => 'Squire',
                'female_variant' => 'Squiress',
                'description' => 'A young noble in training, apprenticed to a Knight. Must serve faithfully to earn knighthood.',
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
                'progression_type' => 'petition',
                'requirements' => json_encode([
                    'current_title' => 'squire',
                    'min_combat_level' => 30,
                    'service_days_as_squire' => 30,
                    'or_conditions' => [
                        ['heroic_deed' => true], // Battlefield valor
                        ['tournament_winner' => true],
                        ['royal_favor' => true],
                    ],
                ]),
                'can_purchase' => true, // Historically possible but dishonorable
                'purchase_cost' => 500000, // 500k gold - "buying" knighthood
                'service_days_required' => 30,
                'service_title_slug' => 'squire',
                'requires_ceremony' => true, // Dubbing ceremony
                'style_of_address' => 'Sir',
                'female_variant' => 'Dame',
                'description' => 'A warrior of noble rank, dubbed after proving valor. The foundation of medieval military.',
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
                'progression_type' => 'petition',
                'requirements' => json_encode([
                    'current_title' => 'knight',
                    'min_combat_level' => 40,
                    'years_as_knight' => 1,
                    'or_conditions' => [
                        ['gold_donation' => 500000],
                        ['heroic_deed' => true],
                        ['exceptional_service' => true],
                    ],
                ]),
                'can_purchase' => true,
                'purchase_cost' => 1000000, // 1M gold
                'service_days_required' => 365, // 1 year as Knight
                'service_title_slug' => 'knight',
                'requires_ceremony' => true,
                'style_of_address' => 'Sir',
                'female_variant' => 'Dame',
                'description' => 'A hereditary title ranking below Baron. Often granted for exceptional service or substantial donations.',
                'prestige_bonus' => 40,
            ],

            // ============================================
            // LANDED NOBILITY (Pure Appointment)
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
                'progression_type' => 'appointment',
                'requirements' => json_encode([
                    'min_title_tier' => 6, // Must be at least Knight
                    'social_class' => 'noble',
                    'vacant_barony' => true,
                ]),
                'can_purchase' => false, // Cannot buy landed titles
                'purchase_cost' => null,
                'service_days_required' => null,
                'service_title_slug' => null,
                'requires_ceremony' => true, // Investiture
                'style_of_address' => 'Lord',
                'female_variant' => 'Baroness',
                'description' => 'Ruler of a barony. Appointed by Duke or King when a barony becomes vacant.',
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
                'progression_type' => 'appointment',
                'requirements' => json_encode([
                    'min_title_tier' => 7, // Must be at least Baronet
                    'social_class' => 'noble',
                ]),
                'can_purchase' => false,
                'purchase_cost' => null,
                'service_days_required' => null,
                'service_title_slug' => null,
                'requires_ceremony' => true,
                'style_of_address' => 'Lord',
                'female_variant' => 'Viscountess',
                'description' => 'Deputy to a Count, administering part of a county. An administrative title of trust.',
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
                'progression_type' => 'appointment',
                'requirements' => json_encode([
                    'min_title_tier' => 8, // Must be at least Baron
                    'social_class' => 'noble',
                ]),
                'can_purchase' => false,
                'purchase_cost' => null,
                'service_days_required' => null,
                'service_title_slug' => null,
                'requires_ceremony' => true,
                'style_of_address' => 'Lord',
                'female_variant' => 'Countess',
                'description' => 'A high noble ranking below Duke. Also known as Earl in some regions.',
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
                'progression_type' => 'appointment',
                'requirements' => json_encode([
                    'min_title_tier' => 10, // Must be at least Count
                    'social_class' => 'noble',
                    'border_duchy' => true, // Only for duchies on kingdom borders
                ]),
                'can_purchase' => false,
                'purchase_cost' => null,
                'service_days_required' => null,
                'service_title_slug' => null,
                'requires_ceremony' => true,
                'style_of_address' => 'Lord',
                'female_variant' => 'Marchioness',
                'description' => 'A noble who guards the borders of the realm. Only granted for border duchies.',
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
                'progression_type' => 'appointment',
                'requirements' => json_encode([
                    'min_title_tier' => 10, // Must be at least Count
                    'social_class' => 'noble',
                    'vacant_duchy' => true,
                ]),
                'can_purchase' => false,
                'purchase_cost' => null,
                'service_days_required' => null,
                'service_title_slug' => null,
                'requires_ceremony' => true, // Grand investiture
                'style_of_address' => 'Your Grace',
                'female_variant' => 'Duchess',
                'description' => 'Ruler of a duchy, the highest rank below royalty. Appointed by the King.',
                'prestige_bonus' => 150,
            ],

            // ============================================
            // ROYALTY (Special rules)
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
                'progression_type' => 'special', // Royal blood or named heir
                'requirements' => json_encode([
                    'or_conditions' => [
                        ['royal_blood' => true], // Child of King
                        ['named_heir' => true], // Formally adopted as heir
                        ['married_to_royalty' => true], // Prince/Princess consort
                    ],
                ]),
                'can_purchase' => false,
                'purchase_cost' => null,
                'service_days_required' => null,
                'service_title_slug' => null,
                'requires_ceremony' => true, // Investiture as Prince
                'style_of_address' => 'Your Royal Highness',
                'female_variant' => 'Princess',
                'description' => 'Royal blood or named heir to the throne. The King\'s children and designated successors.',
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
                'granted_by' => 'emperor',
                'progression_type' => 'special', // Inheritance, election, or conquest
                'requirements' => json_encode([
                    'or_conditions' => [
                        ['inheritance' => true], // Heir to previous King
                        ['election' => true], // Elected by nobles
                        ['conquest' => true], // Seized the throne
                    ],
                ]),
                'can_purchase' => false,
                'purchase_cost' => null,
                'service_days_required' => null,
                'service_title_slug' => null,
                'requires_ceremony' => true, // Coronation
                'style_of_address' => 'Your Majesty',
                'female_variant' => 'Queen',
                'description' => 'Sovereign ruler of a kingdom. Gained through inheritance, election, or conquest.',
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
                'granted_by' => null, // Only through conquest
                'progression_type' => 'special', // Conquest only
                'requirements' => json_encode([
                    'current_title' => 'king',
                    'kingdoms_controlled' => 2, // Must control 2+ kingdoms
                ]),
                'can_purchase' => false,
                'purchase_cost' => null,
                'service_days_required' => null,
                'service_title_slug' => null,
                'requires_ceremony' => true, // Imperial coronation
                'style_of_address' => 'Your Imperial Majesty',
                'female_variant' => 'Empress',
                'description' => 'Ruler of multiple kingdoms. Only achieved through conquest and unification.',
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

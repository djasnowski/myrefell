<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Village Roles
        $villageRoles = [
            [
                'name' => 'Village Elder',
                'slug' => 'elder',
                'icon' => 'crown',
                'description' => 'The elected leader of the village, responsible for governance and village welfare.',
                'location_type' => 'village',
                'permissions' => ['manage_village', 'appoint_roles', 'remove_roles', 'set_taxes', 'moderate_chat'],
                'bonuses' => ['xp_bonus' => 5, 'reputation_bonus' => 10],
                'salary' => 100,
                'tier' => 4,
                'is_elected' => true,
                'max_per_location' => 1,
            ],
            [
                'name' => 'Blacksmith',
                'slug' => 'blacksmith',
                'icon' => 'wrench',
                'description' => 'The village metalworker who maintains equipment and crafts tools.',
                'location_type' => 'village',
                'permissions' => ['access_forge', 'bulk_craft'],
                'bonuses' => ['smithing_xp_bonus' => 10, 'crafting_discount' => 15],
                'salary' => 50,
                'tier' => 2,
                'is_elected' => false,
                'max_per_location' => 1,
            ],
            [
                'name' => 'Merchant',
                'slug' => 'merchant',
                'icon' => 'briefcase',
                'description' => 'The village trader who manages the local market and trade agreements.',
                'location_type' => 'village',
                'permissions' => ['manage_market', 'set_prices', 'trade_bulk'],
                'bonuses' => ['trade_bonus' => 10, 'gold_find_bonus' => 5],
                'salary' => 60,
                'tier' => 2,
                'is_elected' => false,
                'max_per_location' => 1,
            ],
            [
                'name' => 'Guard Captain',
                'slug' => 'guard_captain',
                'icon' => 'shield',
                'description' => 'The head of village defense, responsible for protecting residents.',
                'location_type' => 'village',
                'permissions' => ['manage_guards', 'arrest_criminals', 'patrol'],
                'bonuses' => ['combat_xp_bonus' => 5, 'defense_bonus' => 10],
                'salary' => 75,
                'tier' => 3,
                'is_elected' => false,
                'max_per_location' => 1,
            ],
            [
                'name' => 'Healer',
                'slug' => 'healer',
                'icon' => 'heart',
                'description' => 'The village medic who tends to the sick and wounded.',
                'location_type' => 'village',
                'permissions' => ['free_healing', 'cure_diseases'],
                'bonuses' => ['healing_bonus' => 20, 'hp_regen_bonus' => 5],
                'salary' => 40,
                'tier' => 2,
                'is_elected' => false,
                'max_per_location' => 1,
            ],
        ];

        // Barony Roles
        $baronyRoles = [
            [
                'name' => 'Baron',
                'slug' => 'baron',
                'icon' => 'crown',
                'description' => 'The ruler of the barony and surrounding villages, a powerful noble.',
                'location_type' => 'barony',
                'permissions' => ['manage_barony', 'appoint_roles', 'remove_roles', 'set_taxes', 'declare_war', 'moderate_chat'],
                'bonuses' => ['xp_bonus' => 10, 'reputation_bonus' => 20, 'income_bonus' => 15],
                'salary' => 500,
                'tier' => 5,
                'is_elected' => false,
                'max_per_location' => 1,
            ],
            [
                'name' => 'Steward',
                'slug' => 'steward',
                'icon' => 'briefcase',
                'description' => 'The administrator who manages barony affairs and resources.',
                'location_type' => 'barony',
                'permissions' => ['manage_resources', 'manage_staff', 'access_treasury'],
                'bonuses' => ['resource_bonus' => 10, 'efficiency_bonus' => 5],
                'salary' => 200,
                'tier' => 4,
                'is_elected' => false,
                'max_per_location' => 1,
            ],
            [
                'name' => 'Marshal',
                'slug' => 'marshal',
                'icon' => 'swords',
                'description' => 'The military commander of the barony forces.',
                'location_type' => 'barony',
                'permissions' => ['command_troops', 'manage_defenses', 'lead_attacks'],
                'bonuses' => ['combat_xp_bonus' => 15, 'army_bonus' => 10],
                'salary' => 250,
                'tier' => 4,
                'is_elected' => false,
                'max_per_location' => 1,
            ],
            [
                'name' => 'Treasurer',
                'slug' => 'treasurer',
                'icon' => 'wallet',
                'description' => 'The keeper of the barony treasury and financial records.',
                'location_type' => 'barony',
                'permissions' => ['access_treasury', 'manage_finances', 'collect_taxes'],
                'bonuses' => ['gold_bonus' => 10, 'tax_efficiency' => 5],
                'salary' => 175,
                'tier' => 3,
                'is_elected' => false,
                'max_per_location' => 1,
            ],
            [
                'name' => 'Jailsman',
                'slug' => 'jailsman',
                'icon' => 'gavel',
                'description' => 'The warden of the barony dungeon, responsible for prisoners.',
                'location_type' => 'barony',
                'permissions' => ['manage_prison', 'interrogate', 'release_prisoners'],
                'bonuses' => ['intimidation_bonus' => 10],
                'salary' => 100,
                'tier' => 2,
                'is_elected' => false,
                'max_per_location' => 1,
            ],
        ];

        // Kingdom Roles
        $kingdomRoles = [
            [
                'name' => 'King',
                'slug' => 'king',
                'icon' => 'crown',
                'description' => 'The supreme ruler of the kingdom, elected by the lords.',
                'location_type' => 'kingdom',
                'permissions' => ['rule_kingdom', 'appoint_roles', 'remove_roles', 'set_kingdom_taxes', 'declare_war', 'make_laws', 'moderate_chat'],
                'bonuses' => ['xp_bonus' => 20, 'reputation_bonus' => 50, 'income_bonus' => 25],
                'salary' => 2000,
                'tier' => 5,
                'is_elected' => true,
                'max_per_location' => 1,
            ],
            [
                'name' => 'Chancellor',
                'slug' => 'chancellor',
                'icon' => 'scale',
                'description' => 'The chief advisor to the King, managing diplomacy and laws.',
                'location_type' => 'kingdom',
                'permissions' => ['manage_diplomacy', 'draft_laws', 'advise_king'],
                'bonuses' => ['reputation_bonus' => 15, 'diplomacy_bonus' => 20],
                'salary' => 800,
                'tier' => 4,
                'is_elected' => false,
                'max_per_location' => 1,
            ],
            [
                'name' => 'General',
                'slug' => 'general',
                'icon' => 'swords',
                'description' => 'The supreme commander of the kingdom\'s military forces.',
                'location_type' => 'kingdom',
                'permissions' => ['command_armies', 'manage_military', 'lead_campaigns'],
                'bonuses' => ['combat_xp_bonus' => 20, 'army_bonus' => 20],
                'salary' => 1000,
                'tier' => 4,
                'is_elected' => false,
                'max_per_location' => 1,
            ],
            [
                'name' => 'Royal Treasurer',
                'slug' => 'royal_treasurer',
                'icon' => 'wallet',
                'description' => 'The keeper of the royal treasury and kingdom finances.',
                'location_type' => 'kingdom',
                'permissions' => ['access_royal_treasury', 'manage_kingdom_finances', 'collect_kingdom_taxes'],
                'bonuses' => ['gold_bonus' => 20, 'tax_efficiency' => 15],
                'salary' => 600,
                'tier' => 4,
                'is_elected' => false,
                'max_per_location' => 1,
            ],
        ];

        $allRoles = array_merge($villageRoles, $baronyRoles, $kingdomRoles);

        foreach ($allRoles as $roleData) {
            Role::updateOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Charter;
use App\Models\CharterSignatory;
use App\Models\Kingdom;
use App\Models\SettlementRuin;
use App\Models\User;
use Illuminate\Database\Seeder;

class CharterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get kingdoms and users for seeding
        $valdoria = Kingdom::where('name', 'Valdoria')->first();
        $frostholm = Kingdom::where('name', 'Frostholm')->first();

        if (!$valdoria || !$frostholm) {
            return; // Skip if kingdoms don't exist
        }

        // Get or create a sample user for charter founder
        $founder = User::first();

        if (!$founder) {
            return; // Skip if no users exist
        }

        // Create a sample approved charter
        $approvedCharter = Charter::create([
            'settlement_name' => 'Haven\'s Rest',
            'description' => 'A peaceful settlement along the river bend, perfect for farming and trade.',
            'settlement_type' => Charter::TYPE_VILLAGE,
            'kingdom_id' => $valdoria->id,
            'founder_id' => $founder->id,
            'tax_terms' => [
                'village_rate' => 8,
                'kingdom_tribute' => 5,
                'years_tax_free' => 2,
            ],
            'gold_cost' => Charter::DEFAULT_COST,
            'status' => Charter::STATUS_APPROVED,
            'required_signatories' => Charter::DEFAULT_SIGNATORIES_REQUIRED,
            'current_signatories' => Charter::DEFAULT_SIGNATORIES_REQUIRED,
            'submitted_at' => now()->subDays(14),
            'approved_at' => now()->subDays(7),
            'expires_at' => now()->addDays(23),
            'coordinates_x' => 250,
            'coordinates_y' => 350,
            'biome' => 'plains',
        ]);

        // Add founder as signatory
        CharterSignatory::create([
            'charter_id' => $approvedCharter->id,
            'user_id' => $founder->id,
            'comment' => 'I shall build a prosperous village for all.',
        ]);

        // Create a sample pending charter
        $pendingCharter = Charter::create([
            'settlement_name' => 'Frostwatch Outpost',
            'description' => 'A hardy outpost to watch the northern borders and protect travelers.',
            'settlement_type' => Charter::TYPE_VILLAGE,
            'kingdom_id' => $frostholm->id,
            'founder_id' => $founder->id,
            'tax_terms' => [
                'village_rate' => 5,
                'kingdom_tribute' => 10,
                'years_tax_free' => 1,
            ],
            'gold_cost' => Charter::DEFAULT_COST,
            'status' => Charter::STATUS_PENDING,
            'required_signatories' => Charter::DEFAULT_SIGNATORIES_REQUIRED,
            'current_signatories' => 3,
            'submitted_at' => now()->subDays(3),
            'coordinates_x' => 750,
            'coordinates_y' => 850,
            'biome' => 'tundra',
        ]);

        // Add founder as signatory
        CharterSignatory::create([
            'charter_id' => $pendingCharter->id,
            'user_id' => $founder->id,
            'comment' => 'The north needs more settlements.',
        ]);

        // Create a sample ruin
        SettlementRuin::create([
            'name' => 'Old Millbrook',
            'description' => 'Once a thriving mill village, now abandoned after a harsh winter.',
            'kingdom_id' => $valdoria->id,
            'original_founder_id' => $founder->id,
            'coordinates_x' => 180,
            'coordinates_y' => 220,
            'biome' => 'plains',
            'reclaim_cost' => 300000,
            'is_reclaimable' => true,
            'ruined_at' => now()->subMonths(2),
        ]);

        // Create another ruin in Frostholm
        SettlementRuin::create([
            'name' => 'Frozen Hope',
            'description' => 'An ambitious settlement that could not withstand the endless blizzards.',
            'kingdom_id' => $frostholm->id,
            'coordinates_x' => 820,
            'coordinates_y' => 780,
            'biome' => 'tundra',
            'reclaim_cost' => 400000,
            'is_reclaimable' => true,
            'ruined_at' => now()->subMonths(6),
        ]);
    }
}

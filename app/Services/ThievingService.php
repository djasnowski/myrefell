<?php

namespace App\Services;

use App\Models\Item;
use App\Models\LocationActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ThievingService
{
    /**
     * Thieving targets configuration.
     * success_rate is base rate, modified by level difference
     */
    public const TARGETS = [
        // Level 1 targets
        'man' => [
            'name' => 'Man',
            'description' => 'A common villager going about their day',
            'min_level' => 1,
            'energy_cost' => 2,
            'base_xp' => 5,
            'base_success_rate' => 95,
            'gold_range' => [1, 5],
            'catch_gold_loss' => 5,
            'catch_energy_loss' => 4,
            'location_types' => ['village', 'town', 'barony', 'duchy', 'kingdom'],
            'loot_table' => [
                ['item' => 'Bread', 'weight' => 50, 'quantity' => [1, 1]],
                ['item' => 'Copper Ore', 'weight' => 30, 'quantity' => [1, 1]],
                ['item' => 'Wood', 'weight' => 20, 'quantity' => [1, 2]],
            ],
        ],
        'woman' => [
            'name' => 'Woman',
            'description' => 'A village woman carrying a small purse',
            'min_level' => 1,
            'energy_cost' => 2,
            'base_xp' => 5,
            'base_success_rate' => 95,
            'gold_range' => [1, 6],
            'catch_gold_loss' => 5,
            'catch_energy_loss' => 4,
            'location_types' => ['village', 'town', 'barony', 'duchy', 'kingdom'],
            'loot_table' => [
                ['item' => 'Apple', 'weight' => 40, 'quantity' => [1, 2]],
                ['item' => 'Bread', 'weight' => 35, 'quantity' => [1, 1]],
                ['item' => 'Wool', 'weight' => 25, 'quantity' => [1, 1]],
            ],
        ],
        'farmer' => [
            'name' => 'Farmer',
            'description' => 'A humble farmer tending their crops',
            'min_level' => 1,
            'energy_cost' => 3,
            'base_xp' => 8,
            'base_success_rate' => 92,
            'gold_range' => [3, 12],
            'catch_gold_loss' => 8,
            'catch_energy_loss' => 6,
            'location_types' => ['village', 'town'],
            'loot_table' => [
                ['item' => 'Wheat', 'weight' => 40, 'quantity' => [1, 3]],
                ['item' => 'Potato', 'weight' => 30, 'quantity' => [1, 2]],
                ['item' => 'Carrot', 'weight' => 20, 'quantity' => [1, 2]],
                ['item' => 'Seeds', 'weight' => 10, 'quantity' => [2, 5]],
            ],
        ],
        // Level 5 targets
        'fisherman' => [
            'name' => 'Fisherman',
            'description' => 'A weathered fisherman returning from the docks',
            'min_level' => 5,
            'energy_cost' => 3,
            'base_xp' => 10,
            'base_success_rate' => 90,
            'gold_range' => [5, 15],
            'catch_gold_loss' => 12,
            'catch_energy_loss' => 6,
            'location_types' => ['village', 'town'],
            'loot_table' => [
                ['item' => 'Raw Shrimp', 'weight' => 35, 'quantity' => [2, 4]],
                ['item' => 'Raw Sardine', 'weight' => 30, 'quantity' => [1, 3]],
                ['item' => 'Raw Trout', 'weight' => 20, 'quantity' => [1, 2]],
                ['item' => 'Fishing Bait', 'weight' => 15, 'quantity' => [3, 6]],
            ],
        ],
        'baker' => [
            'name' => 'Baker',
            'description' => 'A flour-dusted baker delivering fresh goods',
            'min_level' => 5,
            'energy_cost' => 3,
            'base_xp' => 10,
            'base_success_rate' => 90,
            'gold_range' => [6, 18],
            'catch_gold_loss' => 15,
            'catch_energy_loss' => 6,
            'location_types' => ['village', 'town', 'barony'],
            'loot_table' => [
                ['item' => 'Bread', 'weight' => 40, 'quantity' => [1, 3]],
                ['item' => 'Wheat', 'weight' => 30, 'quantity' => [2, 4]],
                ['item' => 'Apple Pie', 'weight' => 20, 'quantity' => [1, 1]],
                ['item' => 'Cake', 'weight' => 10, 'quantity' => [1, 1]],
            ],
        ],
        // Level 10 targets
        'market_stall' => [
            'name' => 'Market Stall',
            'description' => 'A busy market stall with various goods',
            'min_level' => 10,
            'energy_cost' => 4,
            'base_xp' => 15,
            'base_success_rate' => 88,
            'gold_range' => [10, 25],
            'catch_gold_loss' => 20,
            'catch_energy_loss' => 8,
            'location_types' => ['village', 'town', 'barony'],
            'loot_table' => [
                ['item' => 'Bread', 'weight' => 30, 'quantity' => [1, 2]],
                ['item' => 'Apple', 'weight' => 25, 'quantity' => [2, 4]],
                ['item' => 'Raw Trout', 'weight' => 20, 'quantity' => [1, 2]],
                ['item' => 'Ale', 'weight' => 15, 'quantity' => [1, 2]],
                ['item' => 'Lockpick', 'weight' => 10, 'quantity' => [1, 1]],
            ],
        ],
        'warrior' => [
            'name' => 'Warrior',
            'description' => 'An off-duty warrior with a coin pouch',
            'min_level' => 10,
            'energy_cost' => 4,
            'base_xp' => 18,
            'base_success_rate' => 85,
            'gold_range' => [12, 30],
            'catch_gold_loss' => 25,
            'catch_energy_loss' => 10,
            'location_types' => ['village', 'town', 'barony'],
            'loot_table' => [
                ['item' => 'Bronze Bar', 'weight' => 30, 'quantity' => [1, 1]],
                ['item' => 'Iron Ore', 'weight' => 25, 'quantity' => [1, 2]],
                ['item' => 'Ale', 'weight' => 25, 'quantity' => [1, 2]],
                ['item' => 'Lockpick', 'weight' => 20, 'quantity' => [1, 1]],
            ],
        ],
        // Level 15 targets
        'traveling_merchant' => [
            'name' => 'Traveling Merchant',
            'description' => 'A merchant passing through with trade goods',
            'min_level' => 15,
            'energy_cost' => 4,
            'base_xp' => 20,
            'base_success_rate' => 85,
            'gold_range' => [15, 35],
            'catch_gold_loss' => 30,
            'catch_energy_loss' => 8,
            'location_types' => ['village', 'town', 'barony'],
            'loot_table' => [
                ['item' => 'Silk', 'weight' => 25, 'quantity' => [1, 1]],
                ['item' => 'Spices', 'weight' => 25, 'quantity' => [1, 2]],
                ['item' => 'Silver Ore', 'weight' => 20, 'quantity' => [1, 1]],
                ['item' => 'Lockpick', 'weight' => 15, 'quantity' => [1, 2]],
                ['item' => 'Trade Contract', 'weight' => 15, 'quantity' => [1, 1]],
            ],
        ],
        'craftsman' => [
            'name' => 'Craftsman',
            'description' => 'A skilled artisan with valuable tools',
            'min_level' => 15,
            'energy_cost' => 4,
            'base_xp' => 22,
            'base_success_rate' => 85,
            'gold_range' => [18, 40],
            'catch_gold_loss' => 35,
            'catch_energy_loss' => 8,
            'location_types' => ['village', 'town', 'barony'],
            'loot_table' => [
                ['item' => 'Iron Bar', 'weight' => 30, 'quantity' => [1, 2]],
                ['item' => 'Steel Bar', 'weight' => 20, 'quantity' => [1, 1]],
                ['item' => 'Leather', 'weight' => 25, 'quantity' => [1, 2]],
                ['item' => 'Bronze Nails', 'weight' => 25, 'quantity' => [5, 10]],
            ],
        ],
        // Level 20 targets
        'priest' => [
            'name' => 'Priest',
            'description' => 'A holy man carrying temple donations',
            'min_level' => 20,
            'energy_cost' => 5,
            'base_xp' => 25,
            'base_success_rate' => 82,
            'gold_range' => [20, 50],
            'catch_gold_loss' => 40,
            'catch_energy_loss' => 10,
            'location_types' => ['village', 'town', 'barony', 'duchy'],
            'loot_table' => [
                ['item' => 'Holy Symbol', 'weight' => 30, 'quantity' => [1, 1]],
                ['item' => 'Prayer Beads', 'weight' => 25, 'quantity' => [1, 1]],
                ['item' => 'Incense', 'weight' => 25, 'quantity' => [2, 4]],
                ['item' => 'Silver Ore', 'weight' => 20, 'quantity' => [1, 2]],
            ],
        ],
        'guard' => [
            'name' => 'Guard',
            'description' => 'A town guard on patrol - risky but rewarding',
            'min_level' => 20,
            'energy_cost' => 5,
            'base_xp' => 28,
            'base_success_rate' => 78,
            'gold_range' => [25, 55],
            'catch_gold_loss' => 50,
            'catch_energy_loss' => 12,
            'location_types' => ['town', 'barony', 'duchy', 'kingdom'],
            'loot_table' => [
                ['item' => 'Iron Bar', 'weight' => 30, 'quantity' => [1, 2]],
                ['item' => 'Steel Bar', 'weight' => 20, 'quantity' => [1, 1]],
                ['item' => 'Guard Key', 'weight' => 15, 'quantity' => [1, 1]],
                ['item' => 'Ale', 'weight' => 20, 'quantity' => [1, 2]],
                ['item' => 'Lockpick', 'weight' => 15, 'quantity' => [1, 2]],
            ],
        ],
        // Level 25 targets
        'merchant' => [
            'name' => 'Wealthy Merchant',
            'description' => 'A prosperous trader with heavy coin purse',
            'min_level' => 25,
            'energy_cost' => 5,
            'base_xp' => 30,
            'base_success_rate' => 80,
            'gold_range' => [30, 70],
            'catch_gold_loss' => 60,
            'catch_energy_loss' => 10,
            'location_types' => ['town', 'barony', 'duchy'],
            'loot_table' => [
                ['item' => 'Gold Ring', 'weight' => 25, 'quantity' => [1, 1]],
                ['item' => 'Silver Ore', 'weight' => 20, 'quantity' => [1, 2]],
                ['item' => 'Silk', 'weight' => 20, 'quantity' => [1, 1]],
                ['item' => 'Sapphire', 'weight' => 15, 'quantity' => [1, 1]],
                ['item' => 'Trade Contract', 'weight' => 10, 'quantity' => [1, 1]],
                ['item' => 'Lockpick', 'weight' => 10, 'quantity' => [1, 2]],
            ],
        ],
        // Level 30 targets
        'tax_collector' => [
            'name' => 'Tax Collector',
            'description' => 'A government official carrying collected taxes',
            'min_level' => 30,
            'energy_cost' => 6,
            'base_xp' => 38,
            'base_success_rate' => 78,
            'gold_range' => [40, 90],
            'catch_gold_loss' => 80,
            'catch_energy_loss' => 12,
            'location_types' => ['town', 'barony', 'duchy'],
            'loot_table' => [
                ['item' => 'Gold Ore', 'weight' => 30, 'quantity' => [1, 2]],
                ['item' => 'Tax Ledger', 'weight' => 25, 'quantity' => [1, 1]],
                ['item' => 'Silver Ore', 'weight' => 25, 'quantity' => [2, 4]],
                ['item' => 'Official Seal', 'weight' => 20, 'quantity' => [1, 1]],
            ],
        ],
        'wealthy_woman' => [
            'name' => 'Wealthy Woman',
            'description' => 'A well-dressed lady with fine jewelry',
            'min_level' => 30,
            'energy_cost' => 5,
            'base_xp' => 35,
            'base_success_rate' => 80,
            'gold_range' => [35, 80],
            'catch_gold_loss' => 70,
            'catch_energy_loss' => 10,
            'location_types' => ['town', 'barony', 'duchy', 'kingdom'],
            'loot_table' => [
                ['item' => 'Gold Ring', 'weight' => 30, 'quantity' => [1, 1]],
                ['item' => 'Pearl Necklace', 'weight' => 25, 'quantity' => [1, 1]],
                ['item' => 'Silk', 'weight' => 25, 'quantity' => [1, 2]],
                ['item' => 'Perfume', 'weight' => 20, 'quantity' => [1, 1]],
            ],
        ],
        // Level 35 targets
        'knight' => [
            'name' => 'Knight',
            'description' => 'A noble knight carrying tournament winnings',
            'min_level' => 35,
            'energy_cost' => 6,
            'base_xp' => 42,
            'base_success_rate' => 75,
            'gold_range' => [45, 100],
            'catch_gold_loss' => 90,
            'catch_energy_loss' => 14,
            'location_types' => ['town', 'barony', 'duchy', 'kingdom'],
            'loot_table' => [
                ['item' => 'Steel Bar', 'weight' => 25, 'quantity' => [1, 2]],
                ['item' => 'Gold Ore', 'weight' => 25, 'quantity' => [1, 2]],
                ['item' => 'Knight Medal', 'weight' => 20, 'quantity' => [1, 1]],
                ['item' => 'Fine Wine', 'weight' => 15, 'quantity' => [1, 1]],
                ['item' => 'Mithril Ore', 'weight' => 15, 'quantity' => [1, 1]],
            ],
        ],
        // Level 40 targets
        'noble' => [
            'name' => 'Noble',
            'description' => 'A wealthy aristocrat adorned with jewelry',
            'min_level' => 40,
            'energy_cost' => 6,
            'base_xp' => 48,
            'base_success_rate' => 75,
            'gold_range' => [60, 130],
            'catch_gold_loss' => 110,
            'catch_energy_loss' => 14,
            'location_types' => ['town', 'barony', 'duchy', 'kingdom'],
            'loot_table' => [
                ['item' => 'Ruby', 'weight' => 20, 'quantity' => [1, 1]],
                ['item' => 'Gold Ore', 'weight' => 20, 'quantity' => [1, 2]],
                ['item' => 'Diamond', 'weight' => 15, 'quantity' => [1, 1]],
                ['item' => 'Noble Signet', 'weight' => 15, 'quantity' => [1, 1]],
                ['item' => 'Silk', 'weight' => 15, 'quantity' => [1, 2]],
                ['item' => 'Fine Wine', 'weight' => 15, 'quantity' => [1, 1]],
            ],
        ],
        // Level 45 targets
        'guild_master' => [
            'name' => 'Guild Master',
            'description' => 'Head of a powerful trade guild',
            'min_level' => 45,
            'energy_cost' => 7,
            'base_xp' => 55,
            'base_success_rate' => 72,
            'gold_range' => [70, 150],
            'catch_gold_loss' => 130,
            'catch_energy_loss' => 14,
            'location_types' => ['town', 'barony', 'duchy'],
            'loot_table' => [
                ['item' => 'Mithril Bar', 'weight' => 25, 'quantity' => [1, 2]],
                ['item' => 'Gold Bar', 'weight' => 20, 'quantity' => [1, 1]],
                ['item' => 'Guild Charter', 'weight' => 20, 'quantity' => [1, 1]],
                ['item' => 'Diamond', 'weight' => 20, 'quantity' => [1, 1]],
                ['item' => 'Trade Contract', 'weight' => 15, 'quantity' => [1, 2]],
            ],
        ],
        // Level 50 targets
        'bishop' => [
            'name' => 'Bishop',
            'description' => 'A high-ranking church official with temple gold',
            'min_level' => 50,
            'energy_cost' => 7,
            'base_xp' => 62,
            'base_success_rate' => 70,
            'gold_range' => [80, 180],
            'catch_gold_loss' => 150,
            'catch_energy_loss' => 16,
            'location_types' => ['town', 'barony', 'duchy', 'kingdom'],
            'loot_table' => [
                ['item' => 'Gold Bar', 'weight' => 25, 'quantity' => [1, 2]],
                ['item' => 'Holy Relic', 'weight' => 20, 'quantity' => [1, 1]],
                ['item' => 'Ruby', 'weight' => 20, 'quantity' => [1, 1]],
                ['item' => 'Blessed Amulet', 'weight' => 20, 'quantity' => [1, 1]],
                ['item' => 'Diamond', 'weight' => 15, 'quantity' => [1, 1]],
            ],
        ],
        'wealthy_banker' => [
            'name' => 'Wealthy Banker',
            'description' => 'A banker carrying important financial documents',
            'min_level' => 50,
            'energy_cost' => 7,
            'base_xp' => 65,
            'base_success_rate' => 68,
            'gold_range' => [90, 200],
            'catch_gold_loss' => 170,
            'catch_energy_loss' => 16,
            'location_types' => ['town', 'barony', 'duchy', 'kingdom'],
            'loot_table' => [
                ['item' => 'Gold Bar', 'weight' => 30, 'quantity' => [1, 3]],
                ['item' => 'Bank Note', 'weight' => 25, 'quantity' => [1, 1]],
                ['item' => 'Diamond', 'weight' => 20, 'quantity' => [1, 1]],
                ['item' => 'Vault Key', 'weight' => 15, 'quantity' => [1, 1]],
                ['item' => 'Emerald', 'weight' => 10, 'quantity' => [1, 1]],
            ],
        ],
        // Level 55 targets
        'palace_servant' => [
            'name' => 'Palace Servant',
            'description' => 'A servant with access to palace valuables',
            'min_level' => 55,
            'energy_cost' => 7,
            'base_xp' => 70,
            'base_success_rate' => 68,
            'gold_range' => [100, 220],
            'catch_gold_loss' => 180,
            'catch_energy_loss' => 16,
            'location_types' => ['barony', 'duchy', 'kingdom'],
            'loot_table' => [
                ['item' => 'Gold Bar', 'weight' => 25, 'quantity' => [1, 2]],
                ['item' => 'Ruby', 'weight' => 20, 'quantity' => [1, 2]],
                ['item' => 'Palace Key', 'weight' => 20, 'quantity' => [1, 1]],
                ['item' => 'Silk', 'weight' => 20, 'quantity' => [2, 3]],
                ['item' => 'Diamond', 'weight' => 15, 'quantity' => [1, 1]],
            ],
        ],
        // Level 60 targets
        'castle_treasury' => [
            'name' => 'Castle Treasury',
            'description' => 'The baron\'s well-guarded treasury room',
            'min_level' => 60,
            'energy_cost' => 8,
            'base_xp' => 80,
            'base_success_rate' => 65,
            'gold_range' => [120, 280],
            'catch_gold_loss' => 220,
            'catch_energy_loss' => 18,
            'location_types' => ['barony', 'duchy', 'kingdom'],
            'loot_table' => [
                ['item' => 'Gold Bar', 'weight' => 25, 'quantity' => [1, 3]],
                ['item' => 'Diamond', 'weight' => 20, 'quantity' => [1, 2]],
                ['item' => 'Ruby', 'weight' => 20, 'quantity' => [1, 2]],
                ['item' => 'Royal Seal', 'weight' => 15, 'quantity' => [1, 1]],
                ['item' => 'Ancient Coin', 'weight' => 10, 'quantity' => [2, 5]],
                ['item' => 'Treasure Map', 'weight' => 10, 'quantity' => [1, 1]],
            ],
        ],
        // Level 65 targets
        'foreign_ambassador' => [
            'name' => 'Foreign Ambassador',
            'description' => 'A diplomatic envoy with exotic treasures',
            'min_level' => 65,
            'energy_cost' => 8,
            'base_xp' => 88,
            'base_success_rate' => 62,
            'gold_range' => [140, 320],
            'catch_gold_loss' => 260,
            'catch_energy_loss' => 18,
            'location_types' => ['duchy', 'kingdom'],
            'loot_table' => [
                ['item' => 'Exotic Gem', 'weight' => 25, 'quantity' => [1, 2]],
                ['item' => 'Gold Bar', 'weight' => 20, 'quantity' => [2, 3]],
                ['item' => 'Diplomatic Seal', 'weight' => 20, 'quantity' => [1, 1]],
                ['item' => 'Celestial Ore', 'weight' => 15, 'quantity' => [1, 1]],
                ['item' => 'Rare Spices', 'weight' => 20, 'quantity' => [2, 4]],
            ],
        ],
        // Level 70 targets
        'duke' => [
            'name' => 'Duke',
            'description' => 'A powerful duke with immense wealth',
            'min_level' => 70,
            'energy_cost' => 9,
            'base_xp' => 95,
            'base_success_rate' => 60,
            'gold_range' => [160, 380],
            'catch_gold_loss' => 300,
            'catch_energy_loss' => 20,
            'location_types' => ['duchy', 'kingdom'],
            'loot_table' => [
                ['item' => 'Gold Bar', 'weight' => 25, 'quantity' => [2, 4]],
                ['item' => 'Diamond', 'weight' => 20, 'quantity' => [1, 2]],
                ['item' => 'Celestial Bar', 'weight' => 15, 'quantity' => [1, 1]],
                ['item' => 'Duke Signet', 'weight' => 15, 'quantity' => [1, 1]],
                ['item' => 'Ruby', 'weight' => 15, 'quantity' => [1, 2]],
                ['item' => 'Ancient Artifact', 'weight' => 10, 'quantity' => [1, 1]],
            ],
        ],
        // Level 75 targets
        'royal_advisor' => [
            'name' => 'Royal Advisor',
            'description' => 'A trusted advisor to the crown with state secrets',
            'min_level' => 75,
            'energy_cost' => 9,
            'base_xp' => 105,
            'base_success_rate' => 58,
            'gold_range' => [180, 420],
            'catch_gold_loss' => 350,
            'catch_energy_loss' => 20,
            'location_types' => ['kingdom'],
            'loot_table' => [
                ['item' => 'Gold Bar', 'weight' => 20, 'quantity' => [2, 4]],
                ['item' => 'Oria Ore', 'weight' => 15, 'quantity' => [1, 1]],
                ['item' => 'Royal Document', 'weight' => 20, 'quantity' => [1, 1]],
                ['item' => 'Diamond', 'weight' => 20, 'quantity' => [1, 2]],
                ['item' => 'Emerald', 'weight' => 15, 'quantity' => [1, 2]],
                ['item' => 'Ancient Artifact', 'weight' => 10, 'quantity' => [1, 1]],
            ],
        ],
        // Level 80 targets
        'royal_vault' => [
            'name' => 'Royal Vault',
            'description' => 'The king\'s legendary vault of treasures',
            'min_level' => 80,
            'energy_cost' => 10,
            'base_xp' => 125,
            'base_success_rate' => 55,
            'gold_range' => [220, 500],
            'catch_gold_loss' => 400,
            'catch_energy_loss' => 22,
            'location_types' => ['kingdom'],
            'loot_table' => [
                ['item' => 'Gold Bar', 'weight' => 20, 'quantity' => [3, 5]],
                ['item' => 'Diamond', 'weight' => 20, 'quantity' => [1, 3]],
                ['item' => 'Crown Jewel', 'weight' => 15, 'quantity' => [1, 1]],
                ['item' => 'Royal Scepter', 'weight' => 10, 'quantity' => [1, 1]],
                ['item' => 'Oria Bar', 'weight' => 10, 'quantity' => [1, 1]],
                ['item' => 'Treasure Map', 'weight' => 15, 'quantity' => [1, 2]],
                ['item' => 'Rare Gem', 'weight' => 10, 'quantity' => [1, 1]],
            ],
        ],
        // Level 85 targets
        'queen' => [
            'name' => 'Queen',
            'description' => 'The queen herself, adorned with priceless jewels',
            'min_level' => 85,
            'energy_cost' => 12,
            'base_xp' => 150,
            'base_success_rate' => 50,
            'gold_range' => [280, 600],
            'catch_gold_loss' => 500,
            'catch_energy_loss' => 25,
            'location_types' => ['kingdom'],
            'loot_table' => [
                ['item' => 'Queen\'s Crown', 'weight' => 15, 'quantity' => [1, 1]],
                ['item' => 'Diamond', 'weight' => 20, 'quantity' => [2, 3]],
                ['item' => 'Royal Necklace', 'weight' => 20, 'quantity' => [1, 1]],
                ['item' => 'Gold Bar', 'weight' => 20, 'quantity' => [3, 5]],
                ['item' => 'Ruby', 'weight' => 15, 'quantity' => [2, 3]],
                ['item' => 'Oria Bar', 'weight' => 10, 'quantity' => [1, 2]],
            ],
        ],
        // Level 90 targets
        'dragons_hoard' => [
            'name' => 'Dragon\'s Hoard',
            'description' => 'A legendary dragon\'s treasure hoard - extremely dangerous',
            'min_level' => 90,
            'energy_cost' => 15,
            'base_xp' => 500,
            'base_success_rate' => 5,
            'gold_range' => [1000, 5000],
            'catch_gold_loss' => 1000,
            'catch_energy_loss' => 50,
            'location_types' => ['kingdom'],
            'is_legendary' => true,
            'loot_table' => [
                ['item' => 'Dragon Scale', 'weight' => 25, 'quantity' => [1, 3]],
                ['item' => 'Legendary Gem', 'weight' => 20, 'quantity' => [1, 2]],
                ['item' => 'Ancient Dragon Coin', 'weight' => 20, 'quantity' => [5, 20]],
                ['item' => 'Dragon\'s Eye Ruby', 'weight' => 15, 'quantity' => [1, 1]],
                ['item' => 'Dragonbone', 'weight' => 10, 'quantity' => [1, 2]],
                ['item' => 'Enchanted Artifact', 'weight' => 10, 'quantity' => [1, 1]],
            ],
        ],
    ];

    public function __construct(
        protected InventoryService $inventoryService,
        protected DailyTaskService $dailyTaskService,
        protected BeliefEffectService $beliefEffectService
    ) {}

    /**
     * Check if user can thieve at their current location.
     */
    public function canThieve(User $user): bool
    {
        if ($user->isTraveling() || $user->isInInfirmary()) {
            return false;
        }

        // Check if any targets are available at this location
        foreach (self::TARGETS as $target) {
            if (in_array($user->current_location_type, $target['location_types'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get available targets at the current location.
     */
    public function getAvailableTargets(User $user): array
    {
        $targets = [];
        $thievingLevel = $user->getSkillLevel('thieving');

        // Get energy reduction from cult beliefs (Shadow Step)
        $energyReduction = $this->beliefEffectService->getEffect($user, 'thieving_energy_reduction');

        foreach (self::TARGETS as $key => $config) {
            if (! in_array($user->current_location_type, $config['location_types'])) {
                continue;
            }

            $isUnlocked = $thievingLevel >= $config['min_level'];
            $successRate = $this->calculateSuccessRate($thievingLevel, $config, $user);

            // Apply energy cost reduction
            $effectiveEnergyCost = $config['energy_cost'];
            if ($energyReduction > 0) {
                $effectiveEnergyCost = max(1, (int) floor($config['energy_cost'] * (1 - $energyReduction / 100)));
            }

            $targets[] = [
                'id' => $key,
                'name' => $config['name'],
                'description' => $config['description'],
                'min_level' => $config['min_level'],
                'energy_cost' => $effectiveEnergyCost,
                'base_energy_cost' => $config['energy_cost'],
                'base_xp' => $config['base_xp'],
                'gold_range' => $config['gold_range'],
                'success_rate' => $successRate,
                'catch_gold_loss' => $config['catch_gold_loss'],
                'catch_energy_loss' => $config['catch_energy_loss'],
                'is_unlocked' => $isUnlocked,
                'is_legendary' => $config['is_legendary'] ?? false,
                'can_attempt' => $isUnlocked && $user->hasEnergy($effectiveEnergyCost),
            ];
        }

        return $targets;
    }

    /**
     * Calculate success rate based on player level vs target requirement.
     * Applies cult belief bonuses for thieving success.
     */
    protected function calculateSuccessRate(int $playerLevel, array $config, ?User $user = null): int
    {
        $baseRate = $config['base_success_rate'];
        $levelDiff = $playerLevel - $config['min_level'];

        // Each level above requirement adds 0.5% success rate (max +15%)
        $levelBonus = min(15, $levelDiff * 0.5);

        // Apply cult belief bonuses
        $beliefBonus = 0;
        if ($user) {
            // Shadow's Embrace: +thieving_success_bonus
            $beliefBonus += $this->beliefEffectService->getEffect($user, 'thieving_success_bonus');

            // Night Stalker: high_level_thieving_bonus for level 40+ targets
            if ($config['min_level'] >= 40) {
                $beliefBonus += $this->beliefEffectService->getEffect($user, 'high_level_thieving_bonus');
            }
        }

        // For legendary targets, the rate is much lower
        if ($config['is_legendary'] ?? false) {
            return max(1, min(15, $baseRate + $levelBonus + $beliefBonus)); // Cap at 15% for legendary with beliefs
        }

        return max(5, min(98, $baseRate + $levelBonus + $beliefBonus));
    }

    /**
     * Attempt to thieve from a target.
     */
    public function thieve(User $user, string $targetId, ?string $locationType = null, ?int $locationId = null): array
    {
        $config = self::TARGETS[$targetId] ?? null;

        if (! $config) {
            return [
                'success' => false,
                'message' => 'Invalid target.',
            ];
        }

        if (! in_array($user->current_location_type, $config['location_types'])) {
            return [
                'success' => false,
                'message' => 'This target is not available at your location.',
            ];
        }

        $thievingLevel = $user->getSkillLevel('thieving');

        if ($thievingLevel < $config['min_level']) {
            return [
                'success' => false,
                'message' => "You need level {$config['min_level']} Thieving to attempt this.",
            ];
        }

        // Calculate effective energy cost with cult belief reduction
        $energyReduction = $this->beliefEffectService->getEffect($user, 'thieving_energy_reduction');
        $effectiveEnergyCost = $config['energy_cost'];
        if ($energyReduction > 0) {
            $effectiveEnergyCost = max(1, (int) floor($config['energy_cost'] * (1 - $energyReduction / 100)));
        }

        if (! $user->hasEnergy($effectiveEnergyCost)) {
            return [
                'success' => false,
                'message' => "Not enough energy. Need {$effectiveEnergyCost} energy.",
            ];
        }

        $locationType = $locationType ?? $user->current_location_type;
        $locationId = $locationId ?? $user->current_location_id;

        $successRate = $this->calculateSuccessRate($thievingLevel, $config, $user);
        $roll = mt_rand(1, 100);
        $isSuccess = $roll <= $successRate;

        return DB::transaction(function () use ($user, $config, $targetId, $isSuccess, $thievingLevel, $locationType, $locationId, $effectiveEnergyCost) {
            // Always consume reduced energy cost
            $user->consumeEnergy($effectiveEnergyCost);

            if ($isSuccess) {
                return $this->handleSuccess($user, $config, $targetId, $thievingLevel, $locationType, $locationId);
            } else {
                return $this->handleFailure($user, $config, $targetId, $locationType, $locationId);
            }
        });
    }

    /**
     * Handle successful thieving attempt.
     */
    protected function handleSuccess(User $user, array $config, string $targetId, int $thievingLevel, ?string $locationType, ?int $locationId): array
    {
        // Award gold
        $goldStolen = mt_rand($config['gold_range'][0], $config['gold_range'][1]);
        $user->increment('gold', $goldStolen);

        // Calculate XP with belief bonuses (Shadow's Embrace, Dark Whispers)
        $xpBonus = $this->beliefEffectService->getEffect($user, 'thieving_xp_bonus');
        $xpPenalty = $this->beliefEffectService->getEffect($user, 'all_xp_penalty'); // Forbidden Wealth
        $xpAwarded = $config['base_xp'];
        if ($xpBonus != 0 || $xpPenalty != 0) {
            $xpAwarded = (int) ceil($xpAwarded * (1 + ($xpBonus + $xpPenalty) / 100));
            $xpAwarded = max(1, $xpAwarded);
        }

        $skill = $user->skills()->where('skill_name', 'thieving')->first();
        $oldLevel = $skill->level;
        $skill->addXp($xpAwarded);
        $newLevel = $skill->fresh()->level;
        $leveledUp = $newLevel > $oldLevel;

        // Chance to get loot item (40% base chance + Night Stalker bonus)
        $lootItem = null;
        $lootQuantity = 0;
        $lootBonus = $this->beliefEffectService->getEffect($user, 'thieving_loot_bonus');
        $lootChance = 40 + $lootBonus;

        if (mt_rand(1, 100) <= $lootChance && ! empty($config['loot_table'])) {
            $loot = $this->selectWeightedLoot($config['loot_table']);
            if ($loot) {
                $item = Item::where('name', $loot['item'])->first();
                if ($item && $this->inventoryService->hasEmptySlot($user)) {
                    $lootQuantity = mt_rand($loot['quantity'][0], $loot['quantity'][1]);
                    $this->inventoryService->addItem($user, $item, $lootQuantity);
                    $lootItem = [
                        'name' => $item->name,
                        'quantity' => $lootQuantity,
                    ];
                }
            }
        }

        // Record daily task progress
        $this->dailyTaskService->recordProgress($user, 'thieve', $config['name'], 1);

        // Log activity
        if ($locationType && $locationId) {
            try {
                LocationActivityLog::log(
                    userId: $user->id,
                    locationType: $locationType,
                    locationId: $locationId,
                    activityType: 'thieving',
                    description: "{$user->username} successfully pickpocketed a {$config['name']}",
                    activitySubtype: $targetId,
                    metadata: [
                        'target' => $config['name'],
                        'gold_stolen' => $goldStolen,
                        'xp_gained' => $xpAwarded,
                        'loot' => $lootItem,
                    ]
                );
            } catch (\Illuminate\Database\QueryException $e) {
                // Table may not exist
            }
        }

        $message = "You successfully pickpocketed the {$config['name']} and stole {$goldStolen}g!";
        if ($lootItem) {
            $message .= " You also found {$lootQuantity}x {$lootItem['name']}!";
        }

        return [
            'success' => true,
            'caught' => false,
            'message' => $message,
            'gold_stolen' => $goldStolen,
            'xp_awarded' => $xpAwarded,
            'leveled_up' => $leveledUp,
            'new_level' => $leveledUp ? $newLevel : null,
            'loot' => $lootItem,
            'energy_remaining' => $user->fresh()->energy,
            'gold_remaining' => $user->fresh()->gold,
        ];
    }

    /**
     * Handle failed thieving attempt (caught).
     */
    protected function handleFailure(User $user, array $config, string $targetId, ?string $locationType, ?int $locationId): array
    {
        // Calculate catch penalty reduction from Dark Whispers
        $catchPenaltyReduction = $this->beliefEffectService->getEffect($user, 'catch_penalty_reduction');

        // Extra energy penalty (reduced by belief)
        $baseExtraEnergyLoss = $config['catch_energy_loss'] - $config['energy_cost'];
        $extraEnergyLoss = $baseExtraEnergyLoss;
        if ($catchPenaltyReduction > 0 && $extraEnergyLoss > 0) {
            $extraEnergyLoss = max(0, (int) ceil($baseExtraEnergyLoss * (1 - $catchPenaltyReduction / 100)));
        }
        if ($extraEnergyLoss > 0 && $user->energy >= $extraEnergyLoss) {
            $user->consumeEnergy($extraEnergyLoss);
        }

        // Gold fine (reduced by belief, only if player has enough)
        $baseGoldLoss = $config['catch_gold_loss'];
        $effectiveGoldLoss = $baseGoldLoss;
        if ($catchPenaltyReduction > 0) {
            $effectiveGoldLoss = max(0, (int) ceil($baseGoldLoss * (1 - $catchPenaltyReduction / 100)));
        }

        $goldLost = 0;
        if ($user->gold >= $effectiveGoldLoss) {
            $goldLost = $effectiveGoldLoss;
            $user->decrement('gold', $goldLost);
        } elseif ($user->gold > 0) {
            $goldLost = $user->gold;
            $user->decrement('gold', $goldLost);
        }

        // Still award some XP for the attempt (25% of base)
        $xpAwarded = (int) ceil($config['base_xp'] * 0.25);
        $skill = $user->skills()->where('skill_name', 'thieving')->first();
        $skill->addXp($xpAwarded);

        // Log activity
        if ($locationType && $locationId) {
            try {
                LocationActivityLog::log(
                    userId: $user->id,
                    locationType: $locationType,
                    locationId: $locationId,
                    activityType: 'thieving',
                    description: "{$user->username} was caught trying to pickpocket a {$config['name']}",
                    activitySubtype: $targetId,
                    metadata: [
                        'target' => $config['name'],
                        'gold_lost' => $goldLost,
                        'caught' => true,
                    ]
                );
            } catch (\Illuminate\Database\QueryException $e) {
                // Table may not exist
            }
        }

        $message = "You were caught trying to pickpocket the {$config['name']}!";
        if ($goldLost > 0) {
            $message .= " You paid a fine of {$goldLost}g.";
        }

        return [
            'success' => false,
            'caught' => true,
            'message' => $message,
            'gold_lost' => $goldLost,
            'xp_awarded' => $xpAwarded,
            'energy_remaining' => $user->fresh()->energy,
            'gold_remaining' => $user->fresh()->gold,
        ];
    }

    /**
     * Select loot from weighted table.
     */
    protected function selectWeightedLoot(array $lootTable): ?array
    {
        $totalWeight = array_sum(array_column($lootTable, 'weight'));
        $random = mt_rand(1, $totalWeight);
        $cumulative = 0;

        foreach ($lootTable as $loot) {
            $cumulative += $loot['weight'];
            if ($random <= $cumulative) {
                return $loot;
            }
        }

        return $lootTable[0] ?? null;
    }

    /**
     * Get thieving info for the page.
     */
    public function getThievingInfo(User $user): array
    {
        $skill = $user->skills()->where('skill_name', 'thieving')->first();
        $thievingLevel = $skill?->level ?? 1;

        return [
            'can_thieve' => $this->canThieve($user),
            'targets' => $this->getAvailableTargets($user),
            'player_energy' => $user->energy,
            'max_energy' => $user->max_energy,
            'player_gold' => $user->gold,
            'thieving_level' => $thievingLevel,
            'thieving_xp' => $skill?->xp ?? 0,
            'thieving_xp_progress' => $skill?->getXpProgress() ?? 0,
            'thieving_xp_to_next' => $skill?->xpToNextLevel() ?? 60,
            'free_slots' => $this->inventoryService->freeSlots($user),
        ];
    }
}

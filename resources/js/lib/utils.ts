import type { InertiaLinkProps } from "@inertiajs/react";
import { type ClassValue, clsx } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps["href"]>): string {
    return typeof url === "string" ? url : url.url;
}

const bonusLabels: Record<string, string> = {
    // General
    xp_bonus: "XP",
    reputation_bonus: "Reputation",
    income_bonus: "Income",
    gold_bonus: "Gold",
    gold_find_bonus: "Gold Find",
    efficiency_bonus: "Efficiency",
    tax_efficiency: "Tax Efficiency",
    legitimacy_bonus: "Legitimacy",

    // Combat
    combat_xp_bonus: "Combat XP",
    defense_bonus: "Defense",
    army_bonus: "Army",
    intimidation_bonus: "Intimidation",
    stealth_bonus: "Stealth",
    intrigue_bonus: "Intrigue",

    // Crafting
    smithing_xp_bonus: "Smithing XP",
    crafting_discount: "Craft Discount",
    crafting_xp_bonus: "Crafting XP",
    weapon_quality_bonus: "Weapon Quality",
    armor_quality_bonus: "Armor Quality",
    gem_quality_bonus: "Gem Quality",
    clothing_quality_bonus: "Clothing Quality",
    leather_quality_bonus: "Leather Quality",
    food_quality_bonus: "Food Quality",
    ale_quality_bonus: "Ale Quality",
    potion_quality_bonus: "Potion Quality",

    // Skills
    farming_xp_bonus: "Farming XP",
    woodcutting_xp_bonus: "Woodcut XP",
    fishing_xp_bonus: "Fishing XP",
    hunting_xp_bonus: "Hunting XP",
    mining_xp_bonus: "Mining XP",
    brewing_xp_bonus: "Brewing XP",
    cooking_xp_bonus: "Cooking XP",
    alchemy_xp_bonus: "Alchemy XP",
    magic_xp_bonus: "Magic XP",

    // Yields
    crop_yield_bonus: "Crop Yield",
    wood_yield_bonus: "Wood Yield",
    fish_yield_bonus: "Fish Yield",
    hunt_yield_bonus: "Hunt Yield",
    ore_yield_bonus: "Ore Yield",
    ale_yield_bonus: "Ale Yield",
    bread_yield_bonus: "Bread Yield",
    meat_yield_bonus: "Meat Yield",

    // Other
    trade_bonus: "Trade",
    diplomacy_bonus: "Diplomacy",
    healing_bonus: "Healing",
    hp_regen_bonus: "HP Regen",
    disease_cure_bonus: "Disease Cure",
    rest_bonus: "Rest",
    energy_regen_bonus: "Energy Regen",
    prayer_bonus: "Prayer",
    morale_bonus: "Morale",
    resource_bonus: "Resources",
    event_bonus: "Events",
    horse_care_bonus: "Horse Care",
    warhorse_training_bonus: "Warhorse Training",
    horse_discount: "Horse Discount",
};

export function formatBonusLabel(key: string): string {
    return bonusLabels[key] || key.replace(/_/g, " ");
}

export function formatBonus(key: string, value: number): string {
    return `+${value}% ${formatBonusLabel(key)}`;
}

import {
    Axe,
    Beef,
    Bone,
    CircleDot,
    Coins,
    Fish,
    FlaskConical,
    Gem,
    HardHat,
    Heart,
    HelpCircle,
    type LucideIcon,
    Package,
    Pickaxe,
    ScrollText,
    Shield,
    Shirt,
    Shovel,
    Sparkles,
    Sword,
    Swords,
    TreeDeciduous,
    Wheat,
    Wrench,
} from "lucide-react";

// Subtype-specific icons (most specific)
const subtypeIcons: Record<string, LucideIcon> = {
    // Weapons
    sword: Sword,
    axe: Axe,
    dagger: Sword,
    // Armor
    helmet: HardHat,
    chestplate: Shirt,
    shield: Shield,
    // Resources
    ore: Gem,
    wood: TreeDeciduous,
    fish: Fish,
    meat: Beef,
    grain: Wheat,
    bone: Bone,
    // Tools
    pickaxe: Pickaxe,
    fishing_rod: Fish,
    hatchet: Axe,
    shovel: Shovel,
    // Consumables
    potion: FlaskConical,
    food: Beef,
    scroll: ScrollText,
};

// Type-level icons (fallback)
const typeIcons: Record<string, LucideIcon> = {
    weapon: Swords,
    armor: Shield,
    resource: Package,
    consumable: FlaskConical,
    tool: Wrench,
    misc: Sparkles,
};

// Get the best icon for an item based on subtype, then type
export function getItemIcon(type: string, subtype: string | null): LucideIcon {
    if (subtype && subtypeIcons[subtype]) {
        return subtypeIcons[subtype];
    }
    return typeIcons[type] || HelpCircle;
}

// Icon components for common UI elements
export const GoldIcon = Coins;
export const HealthIcon = Heart;
export const EquipIcon = Sword;
export const UnequipIcon = Shield;
export const DropIcon = CircleDot;

// Re-export for convenience
export { HelpCircle };

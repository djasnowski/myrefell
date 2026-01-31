import { cn } from '@/lib/utils';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

interface InventoryItem {
    id: number;
    slot_number: number;
    quantity: number;
    is_equipped: boolean;
    item: {
        id: number;
        name: string;
        type: string;
        rarity: string;
        equipment_slot: string | null;
        atk_bonus: number | null;
        str_bonus: number | null;
        def_bonus: number | null;
    } | null;
}

interface Props {
    inventory: InventoryItem[];
}

const EQUIPMENT_SLOTS = [
    'head',
    'chest',
    'legs',
    'feet',
    'hands',
    'weapon',
    'shield',
    'ring',
    'amulet',
] as const;

const slotLabels: Record<string, string> = {
    head: 'Head',
    chest: 'Chest',
    legs: 'Legs',
    feet: 'Feet',
    hands: 'Hands',
    weapon: 'Weapon',
    shield: 'Shield',
    ring: 'Ring',
    amulet: 'Amulet',
};

const rarityColors: Record<string, string> = {
    common: 'border-stone-600 bg-stone-800/50',
    uncommon: 'border-green-600 bg-green-900/30',
    rare: 'border-blue-600 bg-blue-900/30',
    epic: 'border-purple-600 bg-purple-900/30',
    legendary: 'border-amber-500 bg-amber-900/30',
};

const rarityTextColors: Record<string, string> = {
    common: 'text-stone-300',
    uncommon: 'text-green-400',
    rare: 'text-blue-400',
    epic: 'text-purple-400',
    legendary: 'text-amber-400',
};

export function EquipmentSlots({ inventory }: Props) {
    // Get equipped items mapped by their equipment slot
    const equippedBySlot = new Map<string, InventoryItem>();
    inventory.forEach((inv) => {
        if (inv.is_equipped && inv.item?.equipment_slot) {
            equippedBySlot.set(inv.item.equipment_slot, inv);
        }
    });

    return (
        <TooltipProvider>
            <div className="space-y-4">
                <h4 className="text-sm font-medium text-stone-400">Equipped Items</h4>
                <div className="grid grid-cols-3 gap-2">
                    {EQUIPMENT_SLOTS.map((slot) => {
                        const invItem = equippedBySlot.get(slot);
                        const item = invItem?.item;

                        return (
                            <Tooltip key={slot}>
                                <TooltipTrigger asChild>
                                    <div
                                        className={cn(
                                            'flex flex-col items-center justify-center rounded-lg border p-2',
                                            item
                                                ? rarityColors[item.rarity] || rarityColors.common
                                                : 'border-stone-800 bg-stone-900/30'
                                        )}
                                    >
                                        <span className="text-xs text-stone-500">
                                            {slotLabels[slot]}
                                        </span>
                                        {item ? (
                                            <span
                                                className={cn(
                                                    'mt-1 truncate text-xs font-medium',
                                                    rarityTextColors[item.rarity] ||
                                                        rarityTextColors.common
                                                )}
                                            >
                                                {item.name}
                                            </span>
                                        ) : (
                                            <span className="mt-1 text-xs text-stone-600">
                                                Empty
                                            </span>
                                        )}
                                    </div>
                                </TooltipTrigger>
                                {item && (
                                    <TooltipContent
                                        side="top"
                                        className="border-stone-700 bg-stone-900 text-stone-100"
                                    >
                                        <div className="space-y-1">
                                            <p
                                                className={cn(
                                                    'font-medium',
                                                    rarityTextColors[item.rarity] ||
                                                        rarityTextColors.common
                                                )}
                                            >
                                                {item.name}
                                            </p>
                                            <p className="text-xs capitalize text-stone-400">
                                                {item.rarity} {item.type}
                                            </p>
                                            {(item.atk_bonus ||
                                                item.str_bonus ||
                                                item.def_bonus) && (
                                                <div className="flex gap-2 text-xs">
                                                    {item.atk_bonus ? (
                                                        <span className="text-red-400">
                                                            +{item.atk_bonus} ATK
                                                        </span>
                                                    ) : null}
                                                    {item.str_bonus ? (
                                                        <span className="text-orange-400">
                                                            +{item.str_bonus} STR
                                                        </span>
                                                    ) : null}
                                                    {item.def_bonus ? (
                                                        <span className="text-blue-400">
                                                            +{item.def_bonus} DEF
                                                        </span>
                                                    ) : null}
                                                </div>
                                            )}
                                        </div>
                                    </TooltipContent>
                                )}
                            </Tooltip>
                        );
                    })}
                </div>
            </div>
        </TooltipProvider>
    );
}

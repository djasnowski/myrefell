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
    maxSlots?: number;
}

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

export function InventoryGrid({ inventory, maxSlots = 28 }: Props) {
    // Create a map of slot_number to inventory item
    const slotMap = new Map<number, InventoryItem>();
    inventory.forEach((item) => {
        slotMap.set(item.slot_number, item);
    });

    // Create array of all slots
    const slots = Array.from({ length: maxSlots }, (_, i) => i + 1);

    return (
        <TooltipProvider>
            <div className="grid grid-cols-7 gap-2">
                {slots.map((slotNum) => {
                    const invItem = slotMap.get(slotNum);
                    const item = invItem?.item;

                    return (
                        <Tooltip key={slotNum}>
                            <TooltipTrigger asChild>
                                <div
                                    className={cn(
                                        'relative flex aspect-square items-center justify-center rounded-md border p-1 transition-colors',
                                        item
                                            ? rarityColors[item.rarity] || rarityColors.common
                                            : 'border-stone-800 bg-stone-900/30',
                                        invItem?.is_equipped && 'ring-2 ring-green-500'
                                    )}
                                >
                                    {item ? (
                                        <>
                                            <span
                                                className={cn(
                                                    'truncate text-xs font-medium',
                                                    rarityTextColors[item.rarity] || rarityTextColors.common
                                                )}
                                            >
                                                {item.name.slice(0, 3)}
                                            </span>
                                            {invItem && invItem.quantity > 1 && (
                                                <span className="absolute bottom-0 right-0.5 text-[10px] font-bold text-stone-300">
                                                    {invItem.quantity}
                                                </span>
                                            )}
                                            {invItem?.is_equipped && (
                                                <span className="absolute top-0 right-0.5 text-[10px] text-green-400">
                                                    E
                                                </span>
                                            )}
                                        </>
                                    ) : (
                                        <span className="text-xs text-stone-700">{slotNum}</span>
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
                                                rarityTextColors[item.rarity] || rarityTextColors.common
                                            )}
                                        >
                                            {item.name}
                                        </p>
                                        <p className="text-xs capitalize text-stone-400">
                                            {item.type}
                                            {item.equipment_slot && ` - ${item.equipment_slot}`}
                                        </p>
                                        {(item.atk_bonus || item.str_bonus || item.def_bonus) && (
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
                                        {invItem && invItem.quantity > 1 && (
                                            <p className="text-xs text-stone-500">
                                                Quantity: {invItem.quantity}
                                            </p>
                                        )}
                                    </div>
                                </TooltipContent>
                            )}
                        </Tooltip>
                    );
                })}
            </div>
        </TooltipProvider>
    );
}

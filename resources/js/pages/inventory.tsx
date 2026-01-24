import { Head, router, usePage } from '@inertiajs/react';
import { ShieldOff, Sword, Trash2 } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { getItemIcon, GoldIcon, HelpCircle } from '@/lib/item-icons';
import { inventory } from '@/routes';
import type { BreadcrumbItem } from '@/types';

interface Item {
    id: number;
    name: string;
    description: string;
    type: string;
    subtype: string | null;
    rarity: string;
    stackable: boolean;
    equipment_slot: string | null;
    atk_bonus: number;
    str_bonus: number;
    def_bonus: number;
    hp_bonus: number;
    base_value: number;
}

interface InventorySlot {
    id: number;
    item: Item;
    quantity: number;
    is_equipped: boolean;
}

interface PageProps {
    slots: (InventorySlot | null)[];
    max_slots: number;
    gold: number;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inventory',
        href: inventory().url,
    },
];

const rarityColors: Record<string, string> = {
    common: 'border-stone-500 bg-stone-800/50',
    uncommon: 'border-green-500 bg-green-900/30',
    rare: 'border-blue-500 bg-blue-900/30',
    epic: 'border-purple-500 bg-purple-900/30',
    legendary: 'border-amber-500 bg-amber-900/30',
};


function ItemTooltip({ item, quantity, isEquipped }: { item: Item; quantity: number; isEquipped: boolean }) {
    const hasStats = item.atk_bonus || item.str_bonus || item.def_bonus || item.hp_bonus;

    return (
        <div className="absolute bottom-full left-1/2 z-50 mb-2 w-48 -translate-x-1/2 rounded border-2 border-stone-600 bg-stone-900 p-2 shadow-lg">
            <div className="mb-1 font-pixel text-[8px] capitalize text-amber-400">{item.name}</div>
            <div className="mb-1 font-pixel text-[6px] capitalize text-stone-400">
                {item.rarity} {item.type}
                {item.subtype && ` - ${item.subtype}`}
            </div>
            {item.description && <div className="mb-2 text-[10px] text-stone-300">{item.description}</div>}
            {hasStats && (
                <div className="mb-2 space-y-0.5 border-t border-stone-700 pt-1">
                    {item.atk_bonus > 0 && (
                        <div className="font-pixel text-[6px] text-red-400">+{item.atk_bonus} Attack</div>
                    )}
                    {item.str_bonus > 0 && (
                        <div className="font-pixel text-[6px] text-orange-400">+{item.str_bonus} Strength</div>
                    )}
                    {item.def_bonus > 0 && (
                        <div className="font-pixel text-[6px] text-blue-400">+{item.def_bonus} Defense</div>
                    )}
                    {item.hp_bonus > 0 && (
                        <div className="font-pixel text-[6px] text-green-400">+{item.hp_bonus} HP</div>
                    )}
                </div>
            )}
            <div className="flex items-center justify-between border-t border-stone-700 pt-1">
                <span className="flex items-center gap-1 font-pixel text-[6px] text-amber-300">
                    <GoldIcon className="h-3 w-3" /> {item.base_value}
                </span>
                {quantity > 1 && <span className="font-pixel text-[6px] text-stone-400">x{quantity}</span>}
            </div>
            {isEquipped && (
                <div className="mt-1 font-pixel text-[6px] text-green-400">✓ Equipped</div>
            )}
            {/* Arrow */}
            <div className="absolute left-1/2 top-full -translate-x-1/2 border-8 border-transparent border-t-stone-600" />
        </div>
    );
}

function InventorySlotComponent({
    slot,
    slotIndex,
    isSelected,
    onSelect,
    onDrop,
}: {
    slot: InventorySlot | null;
    slotIndex: number;
    isSelected: boolean;
    onSelect: () => void;
    onDrop: (fromSlot: number) => void;
}) {
    const [showTooltip, setShowTooltip] = useState(false);

    const handleDragStart = (e: React.DragEvent) => {
        if (slot) {
            e.dataTransfer.setData('slotIndex', slotIndex.toString());
        }
    };

    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
    };

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        const fromSlot = parseInt(e.dataTransfer.getData('slotIndex'), 10);
        if (!isNaN(fromSlot) && fromSlot !== slotIndex) {
            onDrop(fromSlot);
        }
    };

    return (
        <div
            className={`relative aspect-square cursor-pointer rounded border-2 transition-all ${
                slot
                    ? `${rarityColors[slot.item.rarity]} hover:brightness-110`
                    : 'border-stone-700 bg-stone-800/30 hover:border-stone-600'
            } ${isSelected ? 'ring-2 ring-amber-400' : ''} ${slot?.is_equipped ? 'ring-2 ring-green-500' : ''}`}
            onClick={onSelect}
            onMouseEnter={() => setShowTooltip(true)}
            onMouseLeave={() => setShowTooltip(false)}
            draggable={!!slot}
            onDragStart={handleDragStart}
            onDragOver={handleDragOver}
            onDrop={handleDrop}
        >
            {slot && (
                <>
                    <div className="flex h-full items-center justify-center">
                        {(() => {
                            const Icon = getItemIcon(slot.item.type, slot.item.subtype);
                            return <Icon className="h-6 w-6 text-stone-300" />;
                        })()}
                    </div>
                    {slot.quantity > 1 && (
                        <div className="absolute bottom-0.5 right-0.5 font-pixel text-[8px] text-white drop-shadow-[0_1px_1px_rgba(0,0,0,0.8)]">
                            {slot.quantity}
                        </div>
                    )}
                    {slot.is_equipped && (
                        <div className="absolute left-0.5 top-0.5 font-pixel text-[8px] text-green-400">E</div>
                    )}
                    {showTooltip && (
                        <ItemTooltip item={slot.item} quantity={slot.quantity} isEquipped={slot.is_equipped} />
                    )}
                </>
            )}
        </div>
    );
}

export default function Inventory() {
    const { slots, max_slots, gold } = usePage<PageProps>().props;
    const [selectedSlot, setSelectedSlot] = useState<number | null>(null);

    const selectedItem = selectedSlot !== null ? slots[selectedSlot] : null;

    const handleSlotClick = (index: number) => {
        setSelectedSlot(selectedSlot === index ? null : index);
    };

    const handleMove = (fromSlot: number, toSlot: number) => {
        router.post(
            '/inventory/move',
            { from_slot: fromSlot, to_slot: toSlot },
            { preserveScroll: true }
        );
    };

    const handleDrop = () => {
        if (selectedSlot === null || !slots[selectedSlot]) return;

        if (confirm(`Drop ${slots[selectedSlot]!.item.name}?`)) {
            router.post('/inventory/drop', { slot: selectedSlot }, { preserveScroll: true });
            setSelectedSlot(null);
        }
    };

    const handleEquip = () => {
        if (selectedSlot === null || !slots[selectedSlot]) return;
        const item = slots[selectedSlot]!;

        if (item.is_equipped) {
            router.post('/inventory/unequip', { slot: selectedSlot }, { preserveScroll: true });
        } else if (item.item.equipment_slot) {
            router.post('/inventory/equip', { slot: selectedSlot }, { preserveScroll: true });
        }
    };

    const usedSlots = slots.filter(Boolean).length;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Inventory" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
                {/* Header */}
                <div className="flex flex-wrap items-center justify-between gap-4 rounded-lg border-2 border-amber-700 bg-gradient-to-b from-stone-800 to-stone-900 p-4 shadow-lg">
                    <div>
                        <h1 className="font-pixel text-lg text-amber-400">Inventory</h1>
                        <p className="font-pixel text-[10px] text-stone-400">
                            {usedSlots} / {max_slots} slots used
                        </p>
                    </div>
                    <div className="flex items-center gap-2 rounded border-2 border-amber-600 bg-amber-900/30 px-4 py-2">
                        <GoldIcon className="h-5 w-5 text-amber-400" />
                        <span className="font-pixel text-sm text-amber-300">{gold.toLocaleString()}</span>
                    </div>
                </div>

                <div className="flex flex-col gap-4 lg:flex-row">
                    {/* Inventory Grid */}
                    <div className="flex-1 rounded-lg border-2 border-stone-600 bg-stone-800/80 p-4">
                        <div className="grid grid-cols-7 gap-2">
                            {slots.map((slot, index) => (
                                <InventorySlotComponent
                                    key={index}
                                    slot={slot}
                                    slotIndex={index}
                                    isSelected={selectedSlot === index}
                                    onSelect={() => handleSlotClick(index)}
                                    onDrop={(fromSlot) => handleMove(fromSlot, index)}
                                />
                            ))}
                        </div>
                    </div>

                    {/* Item Details Panel */}
                    <div className="w-full rounded-lg border-2 border-stone-600 bg-stone-800/80 p-4 lg:w-64">
                        <h2 className="mb-4 font-pixel text-xs text-amber-400">Item Details</h2>

                        {selectedItem ? (
                            <div className="space-y-4">
                                <div className="flex items-center gap-3">
                                    <div
                                        className={`flex h-12 w-12 items-center justify-center rounded border-2 ${rarityColors[selectedItem.item.rarity]}`}
                                    >
                                        {(() => {
                                            const Icon = getItemIcon(selectedItem.item.type, selectedItem.item.subtype);
                                            return <Icon className="h-6 w-6 text-stone-300" />;
                                        })()}
                                    </div>
                                    <div>
                                        <div className="font-pixel text-[10px] text-amber-300">
                                            {selectedItem.item.name}
                                        </div>
                                        <div className="font-pixel text-[8px] capitalize text-stone-400">
                                            {selectedItem.item.rarity} {selectedItem.item.type}
                                        </div>
                                    </div>
                                </div>

                                {selectedItem.item.description && (
                                    <p className="text-xs text-stone-300">{selectedItem.item.description}</p>
                                )}

                                {(selectedItem.item.atk_bonus > 0 ||
                                    selectedItem.item.str_bonus > 0 ||
                                    selectedItem.item.def_bonus > 0 ||
                                    selectedItem.item.hp_bonus > 0) && (
                                    <div className="space-y-1 border-t border-stone-700 pt-2">
                                        {selectedItem.item.atk_bonus > 0 && (
                                            <div className="font-pixel text-[8px] text-red-400">
                                                +{selectedItem.item.atk_bonus} Attack
                                            </div>
                                        )}
                                        {selectedItem.item.str_bonus > 0 && (
                                            <div className="font-pixel text-[8px] text-orange-400">
                                                +{selectedItem.item.str_bonus} Strength
                                            </div>
                                        )}
                                        {selectedItem.item.def_bonus > 0 && (
                                            <div className="font-pixel text-[8px] text-blue-400">
                                                +{selectedItem.item.def_bonus} Defense
                                            </div>
                                        )}
                                        {selectedItem.item.hp_bonus > 0 && (
                                            <div className="font-pixel text-[8px] text-green-400">
                                                +{selectedItem.item.hp_bonus} HP
                                            </div>
                                        )}
                                    </div>
                                )}

                                <div className="flex items-center justify-between border-t border-stone-700 pt-2">
                                    <span className="flex items-center gap-1 font-pixel text-[8px] text-amber-300">
                                        <GoldIcon className="h-3 w-3" /> {selectedItem.item.base_value}
                                    </span>
                                    {selectedItem.quantity > 1 && (
                                        <span className="font-pixel text-[8px] text-stone-400">
                                            Qty: {selectedItem.quantity}
                                        </span>
                                    )}
                                </div>

                                {/* Action Buttons */}
                                <div className="flex flex-col gap-2 border-t border-stone-700 pt-2">
                                    {selectedItem.item.equipment_slot && (
                                        <button
                                            onClick={handleEquip}
                                            className="flex w-full items-center justify-center gap-1 rounded border-2 border-green-600 bg-green-900/30 px-3 py-1.5 font-pixel text-[8px] text-green-300 transition hover:bg-green-800/50"
                                        >
                                            {selectedItem.is_equipped ? (
                                                <>
                                                    <ShieldOff className="h-3 w-3" /> Unequip
                                                </>
                                            ) : (
                                                <>
                                                    <Sword className="h-3 w-3" /> Equip
                                                </>
                                            )}
                                        </button>
                                    )}
                                    <button
                                        onClick={handleDrop}
                                        className="flex w-full items-center justify-center gap-1 rounded border-2 border-red-600 bg-red-900/30 px-3 py-1.5 font-pixel text-[8px] text-red-300 transition hover:bg-red-800/50"
                                    >
                                        <Trash2 className="h-3 w-3" /> Drop
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <p className="font-pixel text-[8px] text-stone-500">Select an item to view details</p>
                        )}
                    </div>
                </div>

                {/* Legend */}
                <div className="rounded-lg border-2 border-stone-600 bg-stone-800/80 p-4">
                    <h3 className="mb-2 font-pixel text-[10px] text-amber-400">Item Rarity</h3>
                    <div className="flex flex-wrap gap-4">
                        {Object.entries(rarityColors).map(([rarity, classes]) => (
                            <div key={rarity} className="flex items-center gap-2">
                                <div className={`h-4 w-4 rounded border-2 ${classes}`} />
                                <span className="font-pixel text-[8px] capitalize text-stone-300">{rarity}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

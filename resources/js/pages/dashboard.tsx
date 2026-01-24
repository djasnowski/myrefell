import AppLayout from '@/layouts/app-layout';
import { getItemIcon, GoldIcon } from '@/lib/item-icons';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Backpack, Castle, ShieldOff, Sword, Trash2 } from 'lucide-react';
import { useState } from 'react';

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
    inventory: {
        slots: (InventorySlot | null)[];
        max_slots: number;
    };
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

// Rarity colors
const rarityColors: Record<string, string> = {
    common: 'border-stone-500 bg-stone-700/50',
    uncommon: 'border-green-500 bg-green-900/30',
    rare: 'border-blue-500 bg-blue-900/30',
    epic: 'border-purple-500 bg-purple-900/30',
    legendary: 'border-amber-500 bg-amber-900/30',
};

// Inventory slot component
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
            className={`relative aspect-square cursor-pointer rounded-lg border-2 transition-all ${
                slot ? `${rarityColors[slot.item.rarity]} hover:brightness-110` : 'border-stone-700 bg-stone-800/30 hover:border-stone-600'
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
                            return <Icon className="h-5 w-5 text-stone-300" />;
                        })()}
                    </div>
                    {slot.quantity > 1 && (
                        <div className="absolute bottom-0.5 right-1 font-pixel text-xs text-white drop-shadow-[0_1px_1px_rgba(0,0,0,0.8)]">
                            {slot.quantity}
                        </div>
                    )}
                    {slot.is_equipped && <div className="absolute left-1 top-0.5 font-pixel text-[10px] text-green-400">E</div>}
                    {showTooltip && (
                        <div className="absolute bottom-full left-1/2 z-50 mb-2 w-44 -translate-x-1/2 rounded-lg border-2 border-stone-600 bg-stone-900 p-2 shadow-lg">
                            <div className="font-pixel text-xs text-amber-400">{slot.item.name}</div>
                            <div className="font-pixel text-[10px] capitalize text-stone-400">
                                {slot.item.rarity} {slot.item.type}
                            </div>
                            {(slot.item.atk_bonus > 0 || slot.item.str_bonus > 0 || slot.item.def_bonus > 0) && (
                                <div className="mt-1 flex flex-wrap gap-2 text-[10px]">
                                    {slot.item.atk_bonus > 0 && <span className="text-red-400">+{slot.item.atk_bonus} ATK</span>}
                                    {slot.item.str_bonus > 0 && <span className="text-orange-400">+{slot.item.str_bonus} STR</span>}
                                    {slot.item.def_bonus > 0 && <span className="text-blue-400">+{slot.item.def_bonus} DEF</span>}
                                </div>
                            )}
                            <div className="absolute left-1/2 top-full -translate-x-1/2 border-8 border-transparent border-t-stone-600" />
                        </div>
                    )}
                </>
            )}
        </div>
    );
}

export default function Dashboard() {
    const { inventory } = usePage<PageProps>().props;
    const [selectedSlot, setSelectedSlot] = useState<number | null>(null);

    const selectedItem = selectedSlot !== null ? inventory.slots[selectedSlot] : null;
    const usedSlots = inventory.slots.filter(Boolean).length;

    const handleSlotClick = (index: number) => {
        setSelectedSlot(selectedSlot === index ? null : index);
    };

    const handleMove = (fromSlot: number, toSlot: number) => {
        router.post('/inventory/move', { from_slot: fromSlot, to_slot: toSlot }, { preserveScroll: true });
    };

    const handleDrop = () => {
        if (selectedSlot === null || !inventory.slots[selectedSlot]) return;
        if (confirm(`Drop ${inventory.slots[selectedSlot]!.item.name}?`)) {
            router.post('/inventory/drop', { slot: selectedSlot }, { preserveScroll: true });
            setSelectedSlot(null);
        }
    };

    const handleEquip = () => {
        if (selectedSlot === null || !inventory.slots[selectedSlot]) return;
        const item = inventory.slots[selectedSlot]!;
        if (item.is_equipped) {
            router.post('/inventory/unequip', { slot: selectedSlot }, { preserveScroll: true });
        } else if (item.item.equipment_slot) {
            router.post('/inventory/equip', { slot: selectedSlot }, { preserveScroll: true });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 gap-4 overflow-hidden p-4">
                {/* Main Game Area */}
                <div className="flex flex-1 flex-col gap-4">
                    <div className="flex-1 rounded-xl border-2 border-stone-600/50 bg-stone-800/50 p-6">
                        <div className="flex h-full items-center justify-center">
                            <div className="text-center">
                                <Castle className="mx-auto mb-3 h-16 w-16 text-stone-500" />
                                <p className="font-pixel text-base text-stone-500">Adventure awaits...</p>
                                <p className="font-pixel text-xs text-stone-600">Game content coming soon</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Right Column: Inventory */}
                <div className="flex w-80 flex-shrink-0 flex-col gap-4">
                    {/* Inventory Header */}
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/80 p-4">
                        <div className="flex items-center justify-between">
                            <h2 className="flex items-center gap-2 font-pixel text-sm text-amber-400">
                                <Backpack className="h-4 w-4" /> INVENTORY
                            </h2>
                            <span className="font-pixel text-xs text-stone-400">
                                {usedSlots}/{inventory.max_slots}
                            </span>
                        </div>
                    </div>

                    {/* Inventory Grid */}
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/80 p-3">
                        <div className="grid grid-cols-7 gap-1.5">
                            {inventory.slots.map((slot, index) => (
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

                    {/* Selected Item Details */}
                    <div className="flex-1 rounded-xl border-2 border-stone-600/50 bg-stone-800/80 p-4">
                        <h3 className="mb-3 font-pixel text-sm text-amber-400">ITEM DETAILS</h3>
                        {selectedItem ? (
                            <div className="space-y-3">
                                <div className="flex items-center gap-3">
                                    <div
                                        className={`flex h-14 w-14 items-center justify-center rounded-lg border-2 ${rarityColors[selectedItem.item.rarity]}`}
                                    >
                                        {(() => {
                                            const Icon = getItemIcon(selectedItem.item.type, selectedItem.item.subtype);
                                            return <Icon className="h-7 w-7 text-stone-300" />;
                                        })()}
                                    </div>
                                    <div>
                                        <div className="font-pixel text-sm text-amber-300">{selectedItem.item.name}</div>
                                        <div className="font-pixel text-xs capitalize text-stone-400">
                                            {selectedItem.item.rarity} {selectedItem.item.type}
                                        </div>
                                    </div>
                                </div>

                                {selectedItem.item.description && (
                                    <p className="text-sm leading-relaxed text-stone-300">{selectedItem.item.description}</p>
                                )}

                                {(selectedItem.item.atk_bonus > 0 ||
                                    selectedItem.item.str_bonus > 0 ||
                                    selectedItem.item.def_bonus > 0 ||
                                    selectedItem.item.hp_bonus > 0) && (
                                    <div className="space-y-1 border-t border-stone-700 pt-3">
                                        {selectedItem.item.atk_bonus > 0 && (
                                            <div className="font-pixel text-xs text-red-400">+{selectedItem.item.atk_bonus} Attack</div>
                                        )}
                                        {selectedItem.item.str_bonus > 0 && (
                                            <div className="font-pixel text-xs text-orange-400">+{selectedItem.item.str_bonus} Strength</div>
                                        )}
                                        {selectedItem.item.def_bonus > 0 && (
                                            <div className="font-pixel text-xs text-blue-400">+{selectedItem.item.def_bonus} Defense</div>
                                        )}
                                        {selectedItem.item.hp_bonus > 0 && (
                                            <div className="font-pixel text-xs text-green-400">+{selectedItem.item.hp_bonus} HP</div>
                                        )}
                                    </div>
                                )}

                                <div className="flex items-center justify-between border-t border-stone-700 pt-3">
                                    <span className="flex items-center gap-1 font-pixel text-xs text-amber-300">
                                        <GoldIcon className="h-3 w-3" /> {selectedItem.item.base_value}
                                    </span>
                                    {selectedItem.quantity > 1 && (
                                        <span className="font-pixel text-xs text-stone-400">x{selectedItem.quantity}</span>
                                    )}
                                </div>

                                {/* Action Buttons */}
                                <div className="flex gap-2 border-t border-stone-700 pt-3">
                                    {selectedItem.item.equipment_slot && (
                                        <button
                                            onClick={handleEquip}
                                            className="flex flex-1 items-center justify-center gap-1 rounded-lg border-2 border-green-600 bg-green-900/30 px-3 py-2 font-pixel text-xs text-green-300 transition hover:bg-green-800/50"
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
                                        className="flex flex-1 items-center justify-center gap-1 rounded-lg border-2 border-red-600 bg-red-900/30 px-3 py-2 font-pixel text-xs text-red-300 transition hover:bg-red-800/50"
                                    >
                                        <Trash2 className="h-3 w-3" /> Drop
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <p className="font-pixel text-xs text-stone-500">Select an item to view details</p>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

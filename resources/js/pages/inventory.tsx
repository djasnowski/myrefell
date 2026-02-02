import { Head, router, usePage } from "@inertiajs/react";
import {
    Apple,
    Droplets,
    Gift,
    Heart,
    Package,
    Shield,
    ShieldOff,
    Sword,
    Swords,
    Trash2,
    User,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { getItemIcon, GoldIcon, HelpCircle } from "@/lib/item-icons";
import { inventory } from "@/routes";
import type { BreadcrumbItem } from "@/types";

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
    energy_bonus: number;
    base_value: number;
    required_level: number | null;
    required_skill: string | null;
    required_skill_level: number | null;
}

interface InventorySlot {
    id: number;
    item: Item;
    quantity: number;
    is_equipped: boolean;
}

interface EquippedItem {
    slot_number: number;
    item: {
        id: number;
        name: string;
        type: string;
        subtype: string | null;
        rarity: string;
        atk_bonus: number;
        str_bonus: number;
        def_bonus: number;
        hp_bonus: number;
        energy_bonus: number;
    };
}

interface Equipment {
    head: EquippedItem | null;
    amulet: EquippedItem | null;
    chest: EquippedItem | null;
    legs: EquippedItem | null;
    weapon: EquippedItem | null;
    shield: EquippedItem | null;
    ring: EquippedItem | null;
}

interface CombatStats {
    attack_level: number;
    strength_level: number;
    defense_level: number;
    hitpoints_level: number;
    atk_bonus: number;
    str_bonus: number;
    def_bonus: number;
    hp_bonus: number;
}

interface PageProps {
    slots: (InventorySlot | null)[];
    max_slots: number;
    gold: number;
    can_donate: boolean;
    equipment: Equipment;
    combat_stats: CombatStats;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Inventory",
        href: inventory().url,
    },
];

const rarityColors: Record<string, string> = {
    common: "border-stone-500 bg-stone-800/50",
    uncommon: "border-green-500 bg-green-900/30",
    rare: "border-blue-500 bg-blue-900/30",
    epic: "border-purple-500 bg-purple-900/30",
    legendary: "border-amber-500 bg-amber-900/30",
};

const rarityTextColors: Record<string, string> = {
    common: "text-stone-300",
    uncommon: "text-green-400",
    rare: "text-blue-400",
    epic: "text-purple-400",
    legendary: "text-amber-400",
};

function ItemTooltip({
    item,
    quantity,
    isEquipped,
}: {
    item: Item;
    quantity: number;
    isEquipped: boolean;
}) {
    const hasStats =
        item.atk_bonus || item.str_bonus || item.def_bonus || item.hp_bonus || item.energy_bonus;

    return (
        <div className="absolute top-full left-1/2 z-[100] mt-2 w-56 -translate-x-1/2 rounded border-2 border-stone-600 bg-stone-900 p-3 shadow-lg">
            <div
                className={`mb-1 font-pixel text-sm capitalize ${rarityTextColors[item.rarity] || "text-stone-300"}`}
            >
                {item.name}
            </div>
            <div className="mb-1 font-pixel text-xs capitalize text-stone-500">{item.type}</div>
            {item.description && (
                <div className="mb-2 text-sm text-stone-300">{item.description}</div>
            )}
            {!!hasStats && (
                <div className="mb-2 space-y-1 border-t border-stone-700 pt-2">
                    {item.atk_bonus > 0 && (
                        <div className="font-pixel text-xs text-red-400">
                            +{item.atk_bonus} ATK{item.equipment_slot ? " BONUS" : ""}
                        </div>
                    )}
                    {item.str_bonus > 0 && (
                        <div className="font-pixel text-xs text-orange-400">
                            +{item.str_bonus} STR{item.equipment_slot ? " BONUS" : ""}
                        </div>
                    )}
                    {item.def_bonus > 0 && (
                        <div className="font-pixel text-xs text-blue-400">
                            +{item.def_bonus} DEF{item.equipment_slot ? " BONUS" : ""}
                        </div>
                    )}
                    {item.hp_bonus > 0 && (
                        <div className="font-pixel text-xs text-green-400">
                            +{item.hp_bonus} HP{item.equipment_slot ? " BONUS" : ""}
                        </div>
                    )}
                    {item.energy_bonus > 0 && (
                        <div className="font-pixel text-xs text-yellow-400">
                            +{item.energy_bonus} EN{item.equipment_slot ? " BONUS" : ""}
                        </div>
                    )}
                </div>
            )}
            {item.required_level != null && item.equipment_slot && (
                <div className="mb-2 border-t border-stone-700 pt-2">
                    <div className="font-pixel text-xs text-purple-400">
                        Requires: {item.required_level}{" "}
                        {item.required_skill || (item.equipment_slot === "weapon" ? "ATK" : "DEF")}
                    </div>
                </div>
            )}
            <div className="flex items-center justify-between border-t border-stone-700 pt-2">
                <span className="flex items-center gap-1 font-pixel text-xs text-amber-300">
                    <GoldIcon className="h-4 w-4" /> {item.base_value.toLocaleString()}
                </span>
                {quantity > 1 && (
                    <span className="font-pixel text-xs text-stone-400">x{quantity}</span>
                )}
            </div>
            {isEquipped && <div className="mt-1 font-pixel text-xs text-green-400">✓ Equipped</div>}
            {/* Arrow */}
            <div className="absolute left-1/2 bottom-full -translate-x-1/2 border-8 border-transparent border-b-stone-600" />
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
            e.dataTransfer.setData("slotIndex", slotIndex.toString());
        }
    };

    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
    };

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        const fromSlot = parseInt(e.dataTransfer.getData("slotIndex"), 10);
        if (!isNaN(fromSlot) && fromSlot !== slotIndex) {
            onDrop(fromSlot);
        }
    };

    return (
        <div
            className={`relative h-14 w-14 cursor-pointer rounded border-2 transition-all ${
                slot
                    ? `${rarityColors[slot.item.rarity]} hover:brightness-110`
                    : "border-stone-700 bg-stone-800/30 hover:border-stone-600"
            } ${isSelected ? "ring-2 ring-amber-400" : ""} ${slot?.is_equipped ? "ring-2 ring-green-500" : ""} ${showTooltip ? "z-50" : ""}`}
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
                            return <Icon className="h-7 w-7 text-stone-300" />;
                        })()}
                    </div>
                    {slot.quantity > 1 && (
                        <div className="absolute bottom-0.5 right-1 font-pixel text-[10px] text-white drop-shadow-[0_1px_1px_rgba(0,0,0,0.8)]">
                            {slot.quantity}
                        </div>
                    )}
                    {slot.is_equipped && (
                        <div className="absolute left-1 top-0.5 font-pixel text-[10px] text-green-400">
                            E
                        </div>
                    )}
                    {showTooltip && (
                        <ItemTooltip
                            item={slot.item}
                            quantity={slot.quantity}
                            isEquipped={slot.is_equipped}
                        />
                    )}
                </>
            )}
        </div>
    );
}

const equipmentSlotIcons: Record<string, React.ComponentType<{ className?: string }>> = {
    head: ({ className }) => (
        <svg
            className={className}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
        >
            <circle cx="12" cy="8" r="5" />
            <path d="M5 21v-2a7 7 0 0 1 14 0v2" />
        </svg>
    ),
    chest: ({ className }) => (
        <svg
            className={className}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
        >
            <path d="M12 2L4 6v6c0 5.5 3.5 10.7 8 12 4.5-1.3 8-6.5 8-12V6l-8-4z" />
        </svg>
    ),
    legs: ({ className }) => (
        <svg
            className={className}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
        >
            <path d="M6 4h4v8l-2 10h-2l2-10V4zM14 4h4v8l2 10h-2l-2-10V4z" />
        </svg>
    ),
    weapon: Sword,
    shield: Shield,
    ring: ({ className }) => (
        <svg
            className={className}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
        >
            <circle cx="12" cy="12" r="8" />
            <circle cx="12" cy="12" r="4" />
        </svg>
    ),
    amulet: ({ className }) => (
        <svg
            className={className}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
        >
            <path d="M12 2v4M8 6l4 4 4-4" />
            <path d="M12 22a8 8 0 1 0 0-16 8 8 0 0 0 0 16z" />
            <circle cx="12" cy="14" r="3" />
        </svg>
    ),
};

function EquipmentSlotDisplay({
    label,
    equipped,
    slotType,
}: {
    label: string;
    equipped: EquippedItem | null;
    slotType: string;
}) {
    const SlotIcon = equipmentSlotIcons[slotType] || Package;

    return (
        <div className="flex flex-col items-center">
            <div
                className={`flex h-12 w-12 items-center justify-center rounded border-2 ${
                    equipped
                        ? `${rarityColors[equipped.item.rarity]} ring-1 ring-green-500`
                        : "border-stone-700 bg-stone-900/50"
                }`}
                title={equipped ? equipped.item.name : `${label} (empty)`}
            >
                {equipped ? (
                    (() => {
                        const Icon = getItemIcon(equipped.item.type, equipped.item.subtype);
                        return <Icon className="h-6 w-6 text-stone-300" />;
                    })()
                ) : (
                    <SlotIcon className="h-5 w-5 text-stone-600" />
                )}
            </div>
            <span className="mt-1 font-pixel text-[8px] text-stone-500">{label}</span>
        </div>
    );
}

export default function Inventory() {
    const { slots, max_slots, gold, can_donate, equipment, combat_stats } =
        usePage<PageProps>().props;
    const [selectedSlot, setSelectedSlot] = useState<number | null>(null);

    const selectedItem = selectedSlot !== null ? slots[selectedSlot] : null;

    const handleSlotClick = (index: number) => {
        setSelectedSlot(selectedSlot === index ? null : index);
    };

    const handleMove = (fromSlot: number, toSlot: number) => {
        router.post(
            "/inventory/move",
            { from_slot: fromSlot, to_slot: toSlot },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
            },
        );
    };

    const handleDrop = () => {
        if (selectedSlot === null || !slots[selectedSlot]) return;

        if (confirm(`Drop ${slots[selectedSlot]!.item.name}?`)) {
            router.post(
                "/inventory/drop",
                { slot: selectedSlot },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        router.reload();
                    },
                },
            );
            setSelectedSlot(null);
        }
    };

    const handleEquip = () => {
        if (selectedSlot === null || !slots[selectedSlot]) return;
        const item = slots[selectedSlot]!;

        if (item.is_equipped) {
            router.post(
                "/inventory/unequip",
                { slot: selectedSlot },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        router.reload();
                    },
                },
            );
        } else if (item.item.equipment_slot) {
            router.post(
                "/inventory/equip",
                { slot: selectedSlot },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        router.reload();
                    },
                },
            );
        }
    };

    const handleConsume = () => {
        if (selectedSlot === null || !slots[selectedSlot]) return;

        router.post(
            "/inventory/consume",
            { slot: selectedSlot },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
            },
        );
    };

    const getConsumeLabel = (item: Item): { label: string; icon: typeof Apple } => {
        if (item.subtype === "food") {
            return { label: "Eat", icon: Apple };
        }
        if (item.subtype === "potion") {
            return { label: "Drink", icon: Droplets };
        }
        return { label: "Use", icon: Package };
    };

    const isConsumable = (item: Item): boolean => {
        return item.type === "consumable" && (item.hp_bonus > 0 || item.energy_bonus > 0);
    };

    const isDonatable = (item: Item): boolean => {
        return ["food", "crop", "grain"].includes(item.subtype || "");
    };

    const handleDonate = () => {
        if (selectedSlot === null || !slots[selectedSlot]) return;

        router.post(
            "/inventory/donate",
            { slot: selectedSlot },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
            },
        );
    };

    const usedSlots = slots.filter(Boolean).length;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Inventory" />
            <div className="flex h-full flex-1 flex-col gap-3 p-4">
                {/* Header */}
                <div className="flex items-center justify-between rounded-lg border-2 border-amber-700 bg-gradient-to-b from-stone-800 to-stone-900 px-4 py-2 shadow-lg">
                    <div>
                        <h1 className="font-pixel text-lg text-amber-400">Inventory</h1>
                        <p className="font-pixel text-[10px] text-stone-400">
                            {usedSlots} / {max_slots} slots used
                        </p>
                    </div>
                    <div className="flex items-center gap-2 rounded border-2 border-amber-600 bg-amber-900/30 px-4 py-2">
                        <GoldIcon className="h-5 w-5 text-amber-400" />
                        <span className="font-pixel text-sm text-amber-300">
                            {gold.toLocaleString()}
                        </span>
                    </div>
                </div>

                <div className="flex flex-1 flex-col gap-3 lg:flex-row">
                    {/* Inventory Grid */}
                    <div className="flex-1 rounded-lg border-2 border-stone-600 bg-stone-800/80 p-3">
                        <div className="flex flex-wrap gap-1">
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

                    {/* Equipment Panel */}
                    <div className="w-full rounded-lg border-2 border-stone-600 bg-stone-800/80 p-3 lg:w-64">
                        <h2 className="mb-3 flex items-center gap-1 font-pixel text-xs text-amber-400">
                            <User className="h-3 w-3" /> Equipment
                        </h2>

                        {/* Equipment Slots Grid */}
                        <div className="mb-4 grid grid-cols-3 gap-2">
                            {/* Top row: empty - head - empty */}
                            <div></div>
                            <EquipmentSlotDisplay
                                label="Head"
                                equipped={equipment.head}
                                slotType="head"
                            />
                            <div></div>

                            {/* Middle row: weapon - chest - shield */}
                            <EquipmentSlotDisplay
                                label="Weapon"
                                equipped={equipment.weapon}
                                slotType="weapon"
                            />
                            <EquipmentSlotDisplay
                                label="Chest"
                                equipped={equipment.chest}
                                slotType="chest"
                            />
                            <EquipmentSlotDisplay
                                label="Shield"
                                equipped={equipment.shield}
                                slotType="shield"
                            />

                            {/* Bottom row: ring - legs - amulet */}
                            <EquipmentSlotDisplay
                                label="Ring"
                                equipped={equipment.ring}
                                slotType="ring"
                            />
                            <EquipmentSlotDisplay
                                label="Legs"
                                equipped={equipment.legs}
                                slotType="legs"
                            />
                            <EquipmentSlotDisplay
                                label="Amulet"
                                equipped={equipment.amulet}
                                slotType="amulet"
                            />
                        </div>

                        {/* Equipped Items List */}
                        <div className="border-t border-stone-700 pt-3">
                            <h3 className="mb-2 font-pixel text-[10px] text-stone-400">Equipped</h3>
                            <div className="space-y-1">
                                {(
                                    [
                                        "head",
                                        "amulet",
                                        "chest",
                                        "legs",
                                        "weapon",
                                        "shield",
                                        "ring",
                                    ] as const
                                ).map((slot) => {
                                    const item = equipment[slot];
                                    if (!item) return null;
                                    return (
                                        <div key={slot} className="flex items-center gap-1.5">
                                            <span className="font-pixel text-[8px] text-stone-500 w-12">
                                                {slot}
                                            </span>
                                            <span
                                                className={`font-pixel text-[9px] ${rarityTextColors[item.item.rarity]}`}
                                            >
                                                {item.item.name}
                                            </span>
                                        </div>
                                    );
                                })}
                                {!Object.values(equipment).some(Boolean) && (
                                    <p className="font-pixel text-[8px] text-stone-600 italic">
                                        Nothing equipped
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Combat Stats */}
                        <div className="border-t border-stone-700 pt-3 mt-3">
                            <h3 className="mb-2 flex items-center gap-1 font-pixel text-[10px] text-stone-400">
                                <Swords className="h-3 w-3" /> Combat Stats
                            </h3>
                            <div className="space-y-1.5">
                                <div className="flex justify-between font-pixel text-[10px]">
                                    <span className="text-red-400">Attack</span>
                                    <span className="text-stone-300">
                                        {combat_stats.attack_level}
                                        {combat_stats.atk_bonus > 0 && (
                                            <span className="text-green-400">
                                                {" "}
                                                (+{combat_stats.atk_bonus})
                                            </span>
                                        )}
                                    </span>
                                </div>
                                <div className="flex justify-between font-pixel text-[10px]">
                                    <span className="text-orange-400">Strength</span>
                                    <span className="text-stone-300">
                                        {combat_stats.strength_level}
                                        {combat_stats.str_bonus > 0 && (
                                            <span className="text-green-400">
                                                {" "}
                                                (+{combat_stats.str_bonus})
                                            </span>
                                        )}
                                    </span>
                                </div>
                                <div className="flex justify-between font-pixel text-[10px]">
                                    <span className="text-blue-400">Defense</span>
                                    <span className="text-stone-300">
                                        {combat_stats.defense_level}
                                        {combat_stats.def_bonus > 0 && (
                                            <span className="text-green-400">
                                                {" "}
                                                (+{combat_stats.def_bonus})
                                            </span>
                                        )}
                                    </span>
                                </div>
                                <div className="flex justify-between font-pixel text-[10px]">
                                    <span className="text-green-400">Hitpoints</span>
                                    <span className="text-stone-300">
                                        {combat_stats.hitpoints_level}
                                        {combat_stats.hp_bonus > 0 && (
                                            <span className="text-green-400">
                                                {" "}
                                                (+{combat_stats.hp_bonus})
                                            </span>
                                        )}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Item Details Panel */}
                    <div className="w-full rounded-lg border-2 border-stone-600 bg-stone-800/80 p-3 lg:w-56">
                        <h2 className="mb-4 flex items-center gap-1 font-pixel text-xs text-amber-400">
                            Item Details
                            <HelpCircle className="h-3 w-3 text-stone-500" />
                        </h2>

                        {selectedItem ? (
                            <div className="space-y-4">
                                <div className="flex items-center gap-3">
                                    <div
                                        className={`flex h-12 w-12 items-center justify-center rounded border-2 ${rarityColors[selectedItem.item.rarity]}`}
                                    >
                                        {(() => {
                                            const Icon = getItemIcon(
                                                selectedItem.item.type,
                                                selectedItem.item.subtype,
                                            );
                                            return <Icon className="h-6 w-6 text-stone-300" />;
                                        })()}
                                    </div>
                                    <div>
                                        <div
                                            className={`font-pixel text-[10px] capitalize ${rarityTextColors[selectedItem.item.rarity] || "text-stone-300"}`}
                                        >
                                            {selectedItem.item.name}
                                        </div>
                                        <div className="font-pixel text-[8px] capitalize text-stone-500">
                                            {selectedItem.item.type}
                                        </div>
                                    </div>
                                </div>

                                {selectedItem.item.description && (
                                    <p className="text-xs text-stone-300">
                                        {selectedItem.item.description}
                                    </p>
                                )}

                                {(selectedItem.item.atk_bonus > 0 ||
                                    selectedItem.item.str_bonus > 0 ||
                                    selectedItem.item.def_bonus > 0 ||
                                    selectedItem.item.hp_bonus > 0 ||
                                    selectedItem.item.energy_bonus > 0) && (
                                    <div className="space-y-1 border-t border-stone-700 pt-2">
                                        {selectedItem.item.atk_bonus > 0 && (
                                            <div className="font-pixel text-[8px] text-red-400">
                                                +{selectedItem.item.atk_bonus} ATK
                                                {selectedItem.item.equipment_slot ? " BONUS" : ""}
                                            </div>
                                        )}
                                        {selectedItem.item.str_bonus > 0 && (
                                            <div className="font-pixel text-[8px] text-orange-400">
                                                +{selectedItem.item.str_bonus} STR
                                                {selectedItem.item.equipment_slot ? " BONUS" : ""}
                                            </div>
                                        )}
                                        {selectedItem.item.def_bonus > 0 && (
                                            <div className="font-pixel text-[8px] text-blue-400">
                                                +{selectedItem.item.def_bonus} DEF
                                                {selectedItem.item.equipment_slot ? " BONUS" : ""}
                                            </div>
                                        )}
                                        {selectedItem.item.hp_bonus > 0 && (
                                            <div className="font-pixel text-[8px] text-green-400">
                                                +{selectedItem.item.hp_bonus} HP
                                                {selectedItem.item.equipment_slot ? " BONUS" : ""}
                                            </div>
                                        )}
                                        {selectedItem.item.energy_bonus > 0 && (
                                            <div className="font-pixel text-[8px] text-yellow-400">
                                                +{selectedItem.item.energy_bonus} EN
                                                {selectedItem.item.equipment_slot ? " BONUS" : ""}
                                            </div>
                                        )}
                                    </div>
                                )}

                                {selectedItem.item.required_level != null &&
                                    selectedItem.item.equipment_slot && (
                                        <div className="border-t border-stone-700 pt-2">
                                            <div className="font-pixel text-[8px] text-purple-400">
                                                Requires: {selectedItem.item.required_level}{" "}
                                                {selectedItem.item.required_skill ||
                                                    (selectedItem.item.equipment_slot === "weapon"
                                                        ? "ATK"
                                                        : "DEF")}
                                            </div>
                                        </div>
                                    )}

                                <div className="flex items-center justify-between border-t border-stone-700 pt-2">
                                    <span className="flex items-center gap-1 font-pixel text-[8px] text-amber-300">
                                        <GoldIcon className="h-3 w-3" />{" "}
                                        {selectedItem.item.base_value.toLocaleString()}
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
                                    {isConsumable(selectedItem.item) &&
                                        (() => {
                                            const { label, icon: ConsumeIcon } = getConsumeLabel(
                                                selectedItem.item,
                                            );
                                            return (
                                                <button
                                                    onClick={handleConsume}
                                                    className="flex w-full items-center justify-center gap-1 rounded border-2 border-amber-600 bg-amber-900/30 px-3 py-1.5 font-pixel text-[8px] text-amber-300 transition hover:bg-amber-800/50"
                                                >
                                                    <ConsumeIcon className="h-3 w-3" /> {label}
                                                </button>
                                            );
                                        })()}
                                    {can_donate && isDonatable(selectedItem.item) && (
                                        <button
                                            onClick={handleDonate}
                                            className="flex w-full items-center justify-center gap-1 rounded border-2 border-blue-600 bg-blue-900/30 px-3 py-1.5 font-pixel text-[8px] text-blue-300 transition hover:bg-blue-800/50"
                                        >
                                            <Gift className="h-3 w-3" /> Donate to Granary
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
                            <p className="font-pixel text-[8px] text-stone-500">
                                Select an item to view details
                            </p>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

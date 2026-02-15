import { Head, router, usePage } from "@inertiajs/react";
import { gameToast } from "@/components/ui/game-toast";
import {
    Apple,
    ArrowUpDown,
    ChevronDown,
    Droplets,
    Eye,
    Gift,
    Package,
    Search,
    Shield,
    ShieldOff,
    Sword,
    Swords,
    Trash2,
    User,
    X,
} from "lucide-react";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { getItemIcon, GoldIcon } from "@/lib/item-icons";
import { inventory } from "@/routes";
import type { BreadcrumbItem } from "@/types";

// Long-press hook for mobile context menu support
function useLongPress(
    onLongPress: (e: React.TouchEvent | React.MouseEvent) => void,
    onClick?: () => void,
    { delay = 500 }: { delay?: number } = {},
) {
    const timeout = useRef<NodeJS.Timeout | null>(null);
    const target = useRef<EventTarget | null>(null);
    const moved = useRef(false);

    const start = useCallback(
        (e: React.TouchEvent | React.MouseEvent) => {
            target.current = e.target;
            moved.current = false;
            timeout.current = setTimeout(() => {
                if (!moved.current) {
                    onLongPress(e);
                }
            }, delay);
        },
        [onLongPress, delay],
    );

    const move = useCallback(() => {
        moved.current = true;
        if (timeout.current) {
            clearTimeout(timeout.current);
            timeout.current = null;
        }
    }, []);

    const end = useCallback(
        (e: React.TouchEvent | React.MouseEvent) => {
            if (timeout.current) {
                clearTimeout(timeout.current);
                timeout.current = null;
                // Only trigger click if we didn't long-press and didn't move
                if (!moved.current && onClick) {
                    onClick();
                }
            }
        },
        [onClick],
    );

    return {
        onTouchStart: start,
        onTouchMove: move,
        onTouchEnd: end,
    };
}

interface ContextMenuState {
    visible: boolean;
    x: number;
    y: number;
    slotIndex: number | null;
}

interface DropModalState {
    visible: boolean;
    slotIndex: number | null;
    itemName: string;
}

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
        description: string;
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

interface EquipmentContextMenuState {
    visible: boolean;
    x: number;
    y: number;
    slotName: keyof Equipment | null;
}

interface Equipment {
    head: EquippedItem | null;
    amulet: EquippedItem | null;
    chest: EquippedItem | null;
    legs: EquippedItem | null;
    weapon: EquippedItem | null;
    shield: EquippedItem | null;
    ring: EquippedItem | null;
    necklace: EquippedItem | null;
    bracelet: EquippedItem | null;
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
    position = "center",
}: {
    item: Item;
    quantity: number;
    isEquipped: boolean;
    position?: "left" | "center" | "right";
}) {
    const hasStats =
        item.atk_bonus || item.str_bonus || item.def_bonus || item.hp_bonus || item.energy_bonus;

    // Position classes based on where the slot is on screen
    const positionClasses = {
        left: "left-0", // Align to left edge of slot
        center: "left-1/2 -translate-x-1/2", // Center under slot
        right: "right-0", // Align to right edge of slot
    };

    return (
        <div
            className={`absolute top-full z-[100] mt-2 w-56 rounded border-2 border-stone-600 bg-stone-900 p-3 shadow-lg ${positionClasses[position]}`}
        >
            <div
                className={`font-pixel text-sm capitalize ${rarityTextColors[item.rarity] || "text-stone-300"}`}
            >
                {item.name}
            </div>
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
    onDrop,
    onContextMenu,
    onLongPress,
    contextMenuOpen,
    draggedItem,
    isBeingDragged,
    isDragTarget,
    onDragStart,
    onDragEnd,
    onDragEnter,
    onDragLeave,
}: {
    slot: InventorySlot | null;
    slotIndex: number;
    onDrop: (fromSlot: number) => void;
    onContextMenu: (e: React.MouseEvent) => void;
    onLongPress: (e: React.TouchEvent) => void;
    contextMenuOpen: boolean;
    draggedItem: InventorySlot | null;
    isBeingDragged: boolean;
    isDragTarget: boolean;
    onDragStart: (slotIndex: number) => void;
    onDragEnd: () => void;
    onDragEnter: (slotIndex: number) => void;
    onDragLeave: () => void;
}) {
    const [showTooltip, setShowTooltip] = useState(false);
    const [tooltipPosition, setTooltipPosition] = useState<"left" | "center" | "right">("center");
    const dragImageRef = useRef<HTMLDivElement>(null);
    const slotRef = useRef<HTMLDivElement>(null);

    // Calculate tooltip position based on slot position on screen
    const updateTooltipPosition = () => {
        if (slotRef.current) {
            const rect = slotRef.current.getBoundingClientRect();
            const screenWidth = window.innerWidth;
            const tooltipWidth = 224; // w-56 = 14rem = 224px

            // If slot is near left edge, align tooltip to left
            if (rect.left < tooltipWidth / 2) {
                setTooltipPosition("left");
            }
            // If slot is near right edge, align tooltip to right
            else if (rect.right > screenWidth - tooltipWidth / 2) {
                setTooltipPosition("right");
            }
            // Otherwise center it
            else {
                setTooltipPosition("center");
            }
        }
    };

    // Long-press for mobile context menu
    const longPressHandlers = useLongPress(
        (e) => {
            if (slot) {
                e.preventDefault();
                onLongPress(e as React.TouchEvent);
            }
        },
        undefined,
        { delay: 500 },
    );

    const handleDragStart = (e: React.DragEvent) => {
        if (slot) {
            e.dataTransfer.setData("slotIndex", slotIndex.toString());
            onDragStart(slotIndex);
            setShowTooltip(false);

            // Use the icon-only element as drag image
            if (dragImageRef.current) {
                e.dataTransfer.setDragImage(dragImageRef.current, 28, 28);
            }
        }
    };

    const handleDragEnd = () => {
        onDragEnd();
    };

    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
    };

    const handleDragEnter = (e: React.DragEvent) => {
        e.preventDefault();
        onDragEnter(slotIndex);
    };

    const handleDragLeave = () => {
        onDragLeave();
    };

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        const fromSlot = parseInt(e.dataTransfer.getData("slotIndex"), 10);
        if (!isNaN(fromSlot) && fromSlot !== slotIndex) {
            onDrop(fromSlot);
        }
    };

    // Show ghost of dragged item when this slot is the target
    const showGhost = isDragTarget && draggedItem && !isBeingDragged;

    return (
        <>
            {/* Hidden drag image - just the item square */}
            {slot && (
                <div
                    ref={dragImageRef}
                    className={`pointer-events-none fixed -left-[9999px] flex h-14 w-14 items-center justify-center rounded border-2 ${rarityColors[slot.item.rarity]}`}
                >
                    {(() => {
                        const Icon = getItemIcon(slot.item.type, slot.item.subtype);
                        return <Icon className="h-7 w-7 text-stone-300" />;
                    })()}
                    {slot.quantity > 1 && (
                        <div className="absolute bottom-0.5 right-1 font-pixel text-[10px] text-white drop-shadow-[0_1px_1px_rgba(0,0,0,0.8)]">
                            {slot.quantity}
                        </div>
                    )}
                </div>
            )}
            <div
                ref={slotRef}
                className={`relative h-14 w-14 cursor-pointer rounded border-2 transition-all ${
                    slot
                        ? `${rarityColors[slot.item.rarity]} hover:brightness-110`
                        : isDragTarget
                          ? "border-amber-500 bg-amber-900/30"
                          : "border-stone-700 bg-stone-800/30 hover:border-stone-600"
                } ${slot?.is_equipped ? "ring-2 ring-green-500" : ""} ${showTooltip && !isBeingDragged ? "z-50" : ""} ${isBeingDragged ? "opacity-50" : ""}`}
                onContextMenu={onContextMenu}
                onMouseEnter={() => {
                    if (!isBeingDragged) {
                        updateTooltipPosition();
                        setShowTooltip(true);
                    }
                }}
                onMouseLeave={() => setShowTooltip(false)}
                draggable={!!slot}
                onDragStart={handleDragStart}
                onDragEnd={handleDragEnd}
                onDragOver={handleDragOver}
                onDragEnter={handleDragEnter}
                onDragLeave={handleDragLeave}
                onDrop={handleDrop}
                {...longPressHandlers}
            >
                {/* Ghost preview of dragged item */}
                {showGhost && (
                    <div className="absolute inset-0 flex items-center justify-center opacity-50">
                        {(() => {
                            const Icon = getItemIcon(
                                draggedItem.item.type,
                                draggedItem.item.subtype,
                            );
                            return <Icon className="h-7 w-7 text-stone-300" />;
                        })()}
                    </div>
                )}
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
                        {showTooltip && !contextMenuOpen && !isBeingDragged && (
                            <ItemTooltip
                                item={slot.item}
                                quantity={slot.quantity}
                                isEquipped={slot.is_equipped}
                                position={tooltipPosition}
                            />
                        )}
                    </>
                )}
            </div>
        </>
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
    slotName,
    onContextMenu,
    onLongPress,
    contextMenuOpen,
}: {
    label: string;
    equipped: EquippedItem | null;
    slotType: string;
    slotName: keyof Equipment;
    onContextMenu: (e: React.MouseEvent, slotName: keyof Equipment) => void;
    onLongPress: (e: React.TouchEvent, slotName: keyof Equipment) => void;
    contextMenuOpen: boolean;
}) {
    const [showTooltip, setShowTooltip] = useState(false);
    const SlotIcon = equipmentSlotIcons[slotType] || Package;

    // Long-press for mobile context menu
    const longPressHandlers = useLongPress(
        (e) => {
            if (equipped) {
                e.preventDefault();
                onLongPress(e as React.TouchEvent, slotName);
            }
        },
        undefined,
        { delay: 500 },
    );

    return (
        <div className="relative flex flex-col items-center">
            <div
                className={`flex h-12 w-12 cursor-pointer items-center justify-center rounded border-2 transition-all ${
                    equipped
                        ? `${rarityColors[equipped.item.rarity]} ring-1 ring-green-500 hover:brightness-110`
                        : "border-stone-700 bg-stone-900/50"
                } ${showTooltip ? "z-50" : ""}`}
                onContextMenu={(e) => equipped && onContextMenu(e, slotName)}
                onMouseEnter={() => setShowTooltip(true)}
                onMouseLeave={() => setShowTooltip(false)}
                {...longPressHandlers}
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
            {/* Tooltip */}
            {equipped && showTooltip && !contextMenuOpen && (
                <div className="absolute top-full left-1/2 z-[100] mt-2 w-48 -translate-x-1/2 rounded border-2 border-stone-600 bg-stone-900 p-2 shadow-lg">
                    <div
                        className={`font-pixel text-xs capitalize ${rarityTextColors[equipped.item.rarity] || "text-stone-300"}`}
                    >
                        {equipped.item.name}
                    </div>
                    {(equipped.item.atk_bonus > 0 ||
                        equipped.item.str_bonus > 0 ||
                        equipped.item.def_bonus > 0 ||
                        equipped.item.hp_bonus > 0 ||
                        equipped.item.energy_bonus > 0) && (
                        <div className="space-y-0.5 border-t border-stone-700 pt-1">
                            {equipped.item.atk_bonus > 0 && (
                                <div className="font-pixel text-[10px] text-red-400">
                                    +{equipped.item.atk_bonus} ATK BONUS
                                </div>
                            )}
                            {equipped.item.str_bonus > 0 && (
                                <div className="font-pixel text-[10px] text-orange-400">
                                    +{equipped.item.str_bonus} STR BONUS
                                </div>
                            )}
                            {equipped.item.def_bonus > 0 && (
                                <div className="font-pixel text-[10px] text-blue-400">
                                    +{equipped.item.def_bonus} DEF BONUS
                                </div>
                            )}
                            {equipped.item.hp_bonus > 0 && (
                                <div className="font-pixel text-[10px] text-green-400">
                                    +{equipped.item.hp_bonus} HP BONUS
                                </div>
                            )}
                            {equipped.item.energy_bonus > 0 && (
                                <div className="font-pixel text-[10px] text-yellow-400">
                                    +{equipped.item.energy_bonus} EN BONUS
                                </div>
                            )}
                        </div>
                    )}
                    <div className="mt-1 font-pixel text-[10px] text-green-400">✓ Equipped</div>
                    {/* Arrow */}
                    <div className="absolute left-1/2 bottom-full -translate-x-1/2 border-8 border-transparent border-b-stone-600" />
                </div>
            )}
        </div>
    );
}

type SortOption = "default" | "name" | "type" | "rarity" | "value";
type FilterType = "all" | "weapon" | "armor" | "consumable" | "resource" | "tool" | "misc";

const rarityOrder: Record<string, number> = {
    common: 1,
    uncommon: 2,
    rare: 3,
    epic: 4,
    legendary: 5,
};

const typeLabels: Record<FilterType, string> = {
    all: "All",
    weapon: "Weapons",
    armor: "Armor",
    consumable: "Consumables",
    resource: "Resources",
    tool: "Tools",
    misc: "Misc",
};

export default function Inventory() {
    const { slots, max_slots, gold, can_donate, equipment, combat_stats } =
        usePage<PageProps>().props;
    const [searchQuery, setSearchQuery] = useState("");
    const [sortBy, setSortBy] = useState<SortOption>("default");
    const [filterType, setFilterType] = useState<FilterType>("all");
    const [showSortDropdown, setShowSortDropdown] = useState(false);
    const [showFilterDropdown, setShowFilterDropdown] = useState(false);
    const sortDropdownRef = useRef<HTMLDivElement>(null);
    const filterDropdownRef = useRef<HTMLDivElement>(null);
    const [contextMenu, setContextMenu] = useState<ContextMenuState>({
        visible: false,
        x: 0,
        y: 0,
        slotIndex: null,
    });
    const [dropModal, setDropModal] = useState<DropModalState>({
        visible: false,
        slotIndex: null,
        itemName: "",
    });
    const [equipmentContextMenu, setEquipmentContextMenu] = useState<EquipmentContextMenuState>({
        visible: false,
        x: 0,
        y: 0,
        slotName: null,
    });
    const [dragState, setDragState] = useState<{
        sourceSlot: number | null;
        targetSlot: number | null;
    }>({
        sourceSlot: null,
        targetSlot: null,
    });
    const contextMenuRef = useRef<HTMLDivElement>(null);
    const equipmentContextMenuRef = useRef<HTMLDivElement>(null);

    // Close context menus and dropdowns when clicking/tapping outside
    useEffect(() => {
        const handleClickOutside = (e: MouseEvent | TouchEvent) => {
            const target = e.target as Node;
            if (contextMenuRef.current && !contextMenuRef.current.contains(target)) {
                setContextMenu((prev) => ({ ...prev, visible: false }));
            }
            if (
                equipmentContextMenuRef.current &&
                !equipmentContextMenuRef.current.contains(target)
            ) {
                setEquipmentContextMenu((prev) => ({ ...prev, visible: false }));
            }
            if (sortDropdownRef.current && !sortDropdownRef.current.contains(target)) {
                setShowSortDropdown(false);
            }
            if (filterDropdownRef.current && !filterDropdownRef.current.contains(target)) {
                setShowFilterDropdown(false);
            }
        };
        const handleEscape = (e: KeyboardEvent) => {
            if (e.key === "Escape") {
                setContextMenu((prev) => ({ ...prev, visible: false }));
                setEquipmentContextMenu((prev) => ({ ...prev, visible: false }));
                setDropModal({ visible: false, slotIndex: null, itemName: "" });
                setShowSortDropdown(false);
                setShowFilterDropdown(false);
            }
        };
        document.addEventListener("mousedown", handleClickOutside);
        document.addEventListener("touchstart", handleClickOutside);
        document.addEventListener("keydown", handleEscape);
        return () => {
            document.removeEventListener("mousedown", handleClickOutside);
            document.removeEventListener("touchstart", handleClickOutside);
            document.removeEventListener("keydown", handleEscape);
        };
    }, []);

    const handleContextMenu = (e: React.MouseEvent, slotIndex: number) => {
        e.preventDefault();
        if (!slots[slotIndex]) return;
        setContextMenu({
            visible: true,
            x: e.clientX,
            y: e.clientY,
            slotIndex,
        });
    };

    // Long-press handler for mobile - uses touch coordinates
    const handleLongPress = (e: React.TouchEvent, slotIndex: number) => {
        if (!slots[slotIndex]) return;
        const touch = e.touches?.[0] || e.changedTouches?.[0];
        const x = touch?.clientX ?? 0;
        const y = touch?.clientY ?? 0;
        setContextMenu({
            visible: true,
            x,
            y,
            slotIndex,
        });
    };

    const closeContextMenu = () => {
        setContextMenu((prev) => ({ ...prev, visible: false }));
    };

    const handleEquipmentContextMenu = (e: React.MouseEvent, slotName: keyof Equipment) => {
        e.preventDefault();
        if (!equipment[slotName]) return;
        setEquipmentContextMenu({
            visible: true,
            x: e.clientX,
            y: e.clientY,
            slotName,
        });
    };

    // Long-press handler for equipment slots on mobile
    const handleEquipmentLongPress = (e: React.TouchEvent, slotName: keyof Equipment) => {
        if (!equipment[slotName]) return;
        const touch = e.touches?.[0] || e.changedTouches?.[0];
        const x = touch?.clientX ?? 0;
        const y = touch?.clientY ?? 0;
        setEquipmentContextMenu({
            visible: true,
            x,
            y,
            slotName,
        });
    };

    const closeEquipmentContextMenu = () => {
        setEquipmentContextMenu((prev) => ({ ...prev, visible: false }));
    };

    const handleUnequipFromEquipment = (slotName: keyof Equipment) => {
        const equipped = equipment[slotName];
        if (!equipped) return;

        router.post(
            "/inventory/unequip",
            { slot: equipped.slot_number },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
            },
        );
        closeEquipmentContextMenu();
    };

    const handleExamineEquipment = (slotName: keyof Equipment) => {
        const equipped = equipment[slotName];
        if (!equipped) return;
        closeEquipmentContextMenu();
        gameToast.info(equipped.item.name, {
            description: equipped.item.description || "Nothing interesting.",
            duration: 5000,
        });
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

    const handleExamineItem = (slotIndex: number) => {
        if (!slots[slotIndex]) return;
        const item = slots[slotIndex]!.item;
        closeContextMenu();
        gameToast.info(item.name, {
            description: item.description || "Nothing interesting.",
            duration: 5000,
        });
    };

    const handleDropItem = (slotIndex: number) => {
        if (!slots[slotIndex]) return;
        const itemName = slots[slotIndex]!.item.name;
        closeContextMenu();
        setDropModal({
            visible: true,
            slotIndex,
            itemName,
        });
    };

    const confirmDrop = () => {
        if (dropModal.slotIndex === null) return;
        router.post(
            "/inventory/drop",
            { slot: dropModal.slotIndex },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
            },
        );
        setDropModal({ visible: false, slotIndex: null, itemName: "" });
    };

    const cancelDrop = () => {
        setDropModal({ visible: false, slotIndex: null, itemName: "" });
    };

    const handleEquipItem = (slotIndex: number) => {
        if (!slots[slotIndex]) return;
        const item = slots[slotIndex]!;

        if (item.is_equipped) {
            router.post(
                "/inventory/unequip",
                { slot: slotIndex },
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
                { slot: slotIndex },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        router.reload();
                    },
                    onError: (errors) => {
                        const message =
                            errors.error ||
                            Object.values(errors).flat().join(", ") ||
                            "Cannot equip this item";
                        gameToast.error(message);
                    },
                },
            );
        }
        closeContextMenu();
    };

    const handleConsumeItem = (slotIndex: number) => {
        if (!slots[slotIndex]) return;

        router.post(
            "/inventory/consume",
            { slot: slotIndex },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
            },
        );
        closeContextMenu();
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
        // Consumable if it has hp/energy bonus OR if it's a potion (buff potions have no hp/energy but give stat buffs)
        return (
            item.type === "consumable" &&
            (item.hp_bonus > 0 || item.energy_bonus > 0 || item.subtype === "potion")
        );
    };

    const isDonatable = (item: Item): boolean => {
        return ["food", "crop", "grain"].includes(item.subtype || "");
    };

    const handleDonateItem = (slotIndex: number) => {
        if (!slots[slotIndex]) return;

        router.post(
            "/inventory/donate",
            { slot: slotIndex },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
            },
        );
        closeContextMenu();
    };

    const usedSlots = slots.filter(Boolean).length;

    // Filter and sort slots
    const processedSlots = useMemo(() => {
        // Create array with original indices preserved
        const indexedSlots = slots.map((slot, index) => ({ slot, originalIndex: index }));

        // Filter by search query and type
        let filtered = indexedSlots.filter(({ slot }) => {
            if (!slot) return true; // Keep empty slots

            // Search filter
            if (searchQuery) {
                const query = searchQuery.toLowerCase();
                if (!slot.item.name.toLowerCase().includes(query)) {
                    return false;
                }
            }

            // Type filter
            if (filterType !== "all") {
                if (filterType === "armor") {
                    // Armor includes head, chest, legs, shield
                    if (
                        slot.item.type !== "armor" &&
                        !["head", "chest", "legs", "shield"].includes(
                            slot.item.equipment_slot || "",
                        )
                    ) {
                        return false;
                    }
                } else if (slot.item.type !== filterType) {
                    return false;
                }
            }

            return true;
        });

        // Sort (only affects items, empty slots stay in place)
        if (sortBy !== "default") {
            const itemSlots = filtered.filter(({ slot }) => slot !== null);
            const emptySlots = filtered.filter(({ slot }) => slot === null);

            itemSlots.sort((a, b) => {
                const itemA = a.slot!.item;
                const itemB = b.slot!.item;

                switch (sortBy) {
                    case "name":
                        return itemA.name.localeCompare(itemB.name);
                    case "type":
                        return (
                            itemA.type.localeCompare(itemB.type) ||
                            itemA.name.localeCompare(itemB.name)
                        );
                    case "rarity":
                        return (
                            (rarityOrder[itemB.rarity] || 0) - (rarityOrder[itemA.rarity] || 0) ||
                            itemA.name.localeCompare(itemB.name)
                        );
                    case "value":
                        return (
                            itemB.base_value - itemA.base_value ||
                            itemA.name.localeCompare(itemB.name)
                        );
                    default:
                        return 0;
                }
            });

            filtered = [...itemSlots, ...emptySlots];
        }

        return filtered;
    }, [slots, searchQuery, sortBy, filterType]);

    const sortLabels: Record<SortOption, string> = {
        default: "Default",
        name: "Name",
        type: "Type",
        rarity: "Rarity",
        value: "Value",
    };

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
                        {/* Search and Filter Bar */}
                        <div className="mb-3 flex flex-wrap items-center gap-2">
                            {/* Search Input */}
                            <div className="relative flex-1 min-w-[140px]">
                                <Search className="absolute left-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-stone-500" />
                                <input
                                    type="text"
                                    placeholder="Search items..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="w-full rounded border border-stone-600 bg-stone-900 py-1.5 pl-7 pr-2 font-pixel text-xs text-stone-300 placeholder-stone-500 focus:border-amber-500 focus:outline-none"
                                />
                                {searchQuery && (
                                    <button
                                        onClick={() => setSearchQuery("")}
                                        className="absolute right-2 top-1/2 -translate-y-1/2 text-stone-500 hover:text-stone-300"
                                    >
                                        <X className="h-3 w-3" />
                                    </button>
                                )}
                            </div>

                            {/* Sort Dropdown */}
                            <div className="relative" ref={sortDropdownRef}>
                                <button
                                    onClick={() => {
                                        setShowSortDropdown(!showSortDropdown);
                                        setShowFilterDropdown(false);
                                    }}
                                    className="flex items-center gap-1 rounded border border-stone-600 bg-stone-900 px-2 py-1.5 font-pixel text-xs text-stone-300 hover:border-stone-500"
                                >
                                    <ArrowUpDown className="h-3 w-3" />
                                    <span className="hidden sm:inline">{sortLabels[sortBy]}</span>
                                    <ChevronDown className="h-3 w-3" />
                                </button>
                                {showSortDropdown && (
                                    <div className="absolute right-0 top-full z-50 mt-1 min-w-[100px] rounded border border-stone-600 bg-stone-900 py-1 shadow-lg">
                                        {(Object.keys(sortLabels) as SortOption[]).map((option) => (
                                            <button
                                                key={option}
                                                onClick={() => {
                                                    setSortBy(option);
                                                    setShowSortDropdown(false);
                                                }}
                                                className={`w-full px-3 py-1 text-left font-pixel text-xs hover:bg-stone-800 ${
                                                    sortBy === option
                                                        ? "text-amber-400"
                                                        : "text-stone-300"
                                                }`}
                                            >
                                                {sortLabels[option]}
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* Type Filter Dropdown */}
                            <div className="relative" ref={filterDropdownRef}>
                                <button
                                    onClick={() => {
                                        setShowFilterDropdown(!showFilterDropdown);
                                        setShowSortDropdown(false);
                                    }}
                                    className="flex items-center gap-1 rounded border border-stone-600 bg-stone-900 px-2 py-1.5 font-pixel text-xs text-stone-300 hover:border-stone-500"
                                >
                                    <Package className="h-3 w-3" />
                                    <span className="hidden sm:inline">
                                        {typeLabels[filterType]}
                                    </span>
                                    <ChevronDown className="h-3 w-3" />
                                </button>
                                {showFilterDropdown && (
                                    <div className="absolute right-0 top-full z-50 mt-1 min-w-[110px] rounded border border-stone-600 bg-stone-900 py-1 shadow-lg">
                                        {(Object.keys(typeLabels) as FilterType[]).map((option) => (
                                            <button
                                                key={option}
                                                onClick={() => {
                                                    setFilterType(option);
                                                    setShowFilterDropdown(false);
                                                }}
                                                className={`w-full px-3 py-1 text-left font-pixel text-xs hover:bg-stone-800 ${
                                                    filterType === option
                                                        ? "text-amber-400"
                                                        : "text-stone-300"
                                                }`}
                                            >
                                                {typeLabels[option]}
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Active filters indicator */}
                        {(searchQuery || filterType !== "all" || sortBy !== "default") && (
                            <div className="mb-2 flex flex-wrap items-center gap-1">
                                {searchQuery && (
                                    <span className="flex items-center gap-1 rounded bg-stone-700 px-1.5 py-0.5 font-pixel text-[10px] text-stone-300">
                                        "{searchQuery}"
                                        <button
                                            onClick={() => setSearchQuery("")}
                                            className="text-stone-400 hover:text-stone-200"
                                        >
                                            <X className="h-2.5 w-2.5" />
                                        </button>
                                    </span>
                                )}
                                {filterType !== "all" && (
                                    <span className="flex items-center gap-1 rounded bg-stone-700 px-1.5 py-0.5 font-pixel text-[10px] text-stone-300">
                                        {typeLabels[filterType]}
                                        <button
                                            onClick={() => setFilterType("all")}
                                            className="text-stone-400 hover:text-stone-200"
                                        >
                                            <X className="h-2.5 w-2.5" />
                                        </button>
                                    </span>
                                )}
                                {sortBy !== "default" && (
                                    <span className="flex items-center gap-1 rounded bg-stone-700 px-1.5 py-0.5 font-pixel text-[10px] text-stone-300">
                                        Sort: {sortLabels[sortBy]}
                                        <button
                                            onClick={() => setSortBy("default")}
                                            className="text-stone-400 hover:text-stone-200"
                                        >
                                            <X className="h-2.5 w-2.5" />
                                        </button>
                                    </span>
                                )}
                                <button
                                    onClick={() => {
                                        setSearchQuery("");
                                        setFilterType("all");
                                        setSortBy("default");
                                    }}
                                    className="font-pixel text-[10px] text-amber-400 hover:text-amber-300"
                                >
                                    Clear all
                                </button>
                            </div>
                        )}

                        <div className="flex flex-wrap gap-1">
                            {processedSlots.map(({ slot, originalIndex }) => (
                                <InventorySlotComponent
                                    key={originalIndex}
                                    slot={slot}
                                    slotIndex={originalIndex}
                                    onDrop={(fromSlot) => {
                                        handleMove(fromSlot, originalIndex);
                                        setDragState({ sourceSlot: null, targetSlot: null });
                                    }}
                                    onContextMenu={(e) => handleContextMenu(e, originalIndex)}
                                    onLongPress={(e) => handleLongPress(e, originalIndex)}
                                    contextMenuOpen={
                                        contextMenu.visible &&
                                        contextMenu.slotIndex === originalIndex
                                    }
                                    draggedItem={
                                        dragState.sourceSlot !== null
                                            ? slots[dragState.sourceSlot]
                                            : null
                                    }
                                    isBeingDragged={dragState.sourceSlot === originalIndex}
                                    isDragTarget={dragState.targetSlot === originalIndex}
                                    onDragStart={(slotIndex) =>
                                        setDragState({ sourceSlot: slotIndex, targetSlot: null })
                                    }
                                    onDragEnd={() =>
                                        setDragState({ sourceSlot: null, targetSlot: null })
                                    }
                                    onDragEnter={(slotIndex) =>
                                        setDragState((prev) => ({ ...prev, targetSlot: slotIndex }))
                                    }
                                    onDragLeave={() =>
                                        setDragState((prev) => ({ ...prev, targetSlot: null }))
                                    }
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
                                slotName="head"
                                onContextMenu={handleEquipmentContextMenu}
                                onLongPress={handleEquipmentLongPress}
                                contextMenuOpen={
                                    equipmentContextMenu.visible &&
                                    equipmentContextMenu.slotName === "head"
                                }
                            />
                            <div></div>

                            {/* Middle row: weapon - chest - shield */}
                            <EquipmentSlotDisplay
                                label="Weapon"
                                equipped={equipment.weapon}
                                slotType="weapon"
                                slotName="weapon"
                                onContextMenu={handleEquipmentContextMenu}
                                onLongPress={handleEquipmentLongPress}
                                contextMenuOpen={
                                    equipmentContextMenu.visible &&
                                    equipmentContextMenu.slotName === "weapon"
                                }
                            />
                            <EquipmentSlotDisplay
                                label="Chest"
                                equipped={equipment.chest}
                                slotType="chest"
                                slotName="chest"
                                onContextMenu={handleEquipmentContextMenu}
                                onLongPress={handleEquipmentLongPress}
                                contextMenuOpen={
                                    equipmentContextMenu.visible &&
                                    equipmentContextMenu.slotName === "chest"
                                }
                            />
                            <EquipmentSlotDisplay
                                label="Shield"
                                equipped={equipment.shield}
                                slotType="shield"
                                slotName="shield"
                                onContextMenu={handleEquipmentContextMenu}
                                onLongPress={handleEquipmentLongPress}
                                contextMenuOpen={
                                    equipmentContextMenu.visible &&
                                    equipmentContextMenu.slotName === "shield"
                                }
                            />

                            {/* Bottom row: ring - legs - amulet */}
                            <EquipmentSlotDisplay
                                label="Ring"
                                equipped={equipment.ring}
                                slotType="ring"
                                slotName="ring"
                                onContextMenu={handleEquipmentContextMenu}
                                onLongPress={handleEquipmentLongPress}
                                contextMenuOpen={
                                    equipmentContextMenu.visible &&
                                    equipmentContextMenu.slotName === "ring"
                                }
                            />
                            <EquipmentSlotDisplay
                                label="Legs"
                                equipped={equipment.legs}
                                slotType="legs"
                                slotName="legs"
                                onContextMenu={handleEquipmentContextMenu}
                                onLongPress={handleEquipmentLongPress}
                                contextMenuOpen={
                                    equipmentContextMenu.visible &&
                                    equipmentContextMenu.slotName === "legs"
                                }
                            />
                            <EquipmentSlotDisplay
                                label="Amulet"
                                equipped={equipment.amulet}
                                slotType="amulet"
                                slotName="amulet"
                                onContextMenu={handleEquipmentContextMenu}
                                onLongPress={handleEquipmentLongPress}
                                contextMenuOpen={
                                    equipmentContextMenu.visible &&
                                    equipmentContextMenu.slotName === "amulet"
                                }
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
                </div>
            </div>

            {/* Context Menu */}
            {contextMenu.visible &&
                contextMenu.slotIndex !== null &&
                slots[contextMenu.slotIndex] && (
                    <div
                        ref={contextMenuRef}
                        className="fixed z-[200] min-w-40 rounded-lg border-2 border-stone-600 bg-stone-900 p-1 shadow-xl"
                        style={{
                            left: contextMenu.x,
                            top: contextMenu.y,
                        }}
                    >
                        {(() => {
                            const slot = slots[contextMenu.slotIndex!]!;
                            const item = slot.item;
                            return (
                                <>
                                    {/* Equip/Unequip */}
                                    {item.equipment_slot && (
                                        <button
                                            onClick={() => handleEquipItem(contextMenu.slotIndex!)}
                                            className="flex w-full items-center gap-2 rounded px-3 py-1.5 font-pixel text-xs text-stone-300 hover:bg-stone-800"
                                        >
                                            {slot.is_equipped ? (
                                                <>
                                                    <ShieldOff className="h-3.5 w-3.5 text-orange-400" />
                                                    Unequip
                                                </>
                                            ) : (
                                                <>
                                                    <Sword className="h-3.5 w-3.5 text-green-400" />
                                                    Equip
                                                </>
                                            )}
                                        </button>
                                    )}

                                    {/* Consume */}
                                    {isConsumable(item) &&
                                        (() => {
                                            const { label, icon: ConsumeIcon } =
                                                getConsumeLabel(item);
                                            return (
                                                <button
                                                    onClick={() =>
                                                        handleConsumeItem(contextMenu.slotIndex!)
                                                    }
                                                    className="flex w-full items-center gap-2 rounded px-3 py-1.5 font-pixel text-xs text-stone-300 hover:bg-stone-800"
                                                >
                                                    <ConsumeIcon className="h-3.5 w-3.5 text-amber-400" />
                                                    {label}
                                                </button>
                                            );
                                        })()}

                                    {/* Donate */}
                                    {can_donate && isDonatable(item) && (
                                        <button
                                            onClick={() => handleDonateItem(contextMenu.slotIndex!)}
                                            className="flex w-full items-center gap-2 rounded px-3 py-1.5 font-pixel text-xs text-stone-300 hover:bg-stone-800"
                                        >
                                            <Gift className="h-3.5 w-3.5 text-blue-400" />
                                            Donate to Granary
                                        </button>
                                    )}

                                    {/* Separator only if there are actions above */}
                                    {(item.equipment_slot ||
                                        isConsumable(item) ||
                                        (can_donate && isDonatable(item))) && (
                                        <div className="my-1 h-px bg-stone-700" />
                                    )}

                                    {/* Examine */}
                                    <button
                                        onClick={() => handleExamineItem(contextMenu.slotIndex!)}
                                        className="flex w-full items-center gap-2 rounded px-3 py-1.5 font-pixel text-xs text-stone-300 hover:bg-stone-800"
                                    >
                                        <Eye className="h-3.5 w-3.5 text-blue-400" />
                                        Examine
                                    </button>

                                    <div className="my-1 h-px bg-stone-700" />

                                    {/* Drop */}
                                    <button
                                        onClick={() => handleDropItem(contextMenu.slotIndex!)}
                                        className="flex w-full items-center gap-2 rounded px-3 py-1.5 font-pixel text-xs text-red-400 hover:bg-red-900/30"
                                    >
                                        <Trash2 className="h-3.5 w-3.5" />
                                        Drop
                                    </button>

                                    <div className="my-1 h-px bg-stone-700" />

                                    {/* Cancel */}
                                    <button
                                        onClick={closeContextMenu}
                                        className="flex w-full items-center gap-2 rounded px-3 py-1.5 font-pixel text-xs text-stone-400 hover:bg-stone-800"
                                    >
                                        Cancel
                                    </button>
                                </>
                            );
                        })()}
                    </div>
                )}

            {/* Equipment Context Menu */}
            {equipmentContextMenu.visible &&
                equipmentContextMenu.slotName !== null &&
                equipment[equipmentContextMenu.slotName] && (
                    <div
                        ref={equipmentContextMenuRef}
                        className="fixed z-[200] min-w-40 rounded-lg border-2 border-stone-600 bg-stone-900 p-1 shadow-xl"
                        style={{
                            left: equipmentContextMenu.x,
                            top: equipmentContextMenu.y,
                        }}
                    >
                        {/* Unequip */}
                        <button
                            onClick={() =>
                                handleUnequipFromEquipment(equipmentContextMenu.slotName!)
                            }
                            className="flex w-full items-center gap-2 rounded px-3 py-1.5 font-pixel text-xs text-stone-300 hover:bg-stone-800"
                        >
                            <ShieldOff className="h-3.5 w-3.5 text-orange-400" />
                            Unequip
                        </button>

                        <div className="my-1 h-px bg-stone-700" />

                        {/* Examine */}
                        <button
                            onClick={() => handleExamineEquipment(equipmentContextMenu.slotName!)}
                            className="flex w-full items-center gap-2 rounded px-3 py-1.5 font-pixel text-xs text-stone-300 hover:bg-stone-800"
                        >
                            <Eye className="h-3.5 w-3.5 text-blue-400" />
                            Examine
                        </button>

                        <div className="my-1 h-px bg-stone-700" />

                        {/* Cancel */}
                        <button
                            onClick={closeEquipmentContextMenu}
                            className="flex w-full items-center gap-2 rounded px-3 py-1.5 font-pixel text-xs text-stone-400 hover:bg-stone-800"
                        >
                            Cancel
                        </button>
                    </div>
                )}

            {/* Drop Confirmation Modal */}
            {dropModal.visible && (
                <div className="fixed inset-0 z-[300] flex items-center justify-center bg-black/70">
                    <div className="relative w-80 rounded-lg border-2 border-stone-600 bg-stone-900 p-4 shadow-2xl">
                        {/* Close button */}
                        <button
                            onClick={cancelDrop}
                            className="absolute right-2 top-2 text-stone-500 hover:text-stone-300"
                        >
                            <X className="h-4 w-4" />
                        </button>

                        {/* Icon */}
                        <div className="mb-4 flex justify-center">
                            <div className="rounded-full bg-red-900/50 p-3">
                                <Trash2 className="h-8 w-8 text-red-400" />
                            </div>
                        </div>

                        {/* Title */}
                        <h3 className="mb-2 text-center font-pixel text-sm text-amber-400">
                            Drop Item
                        </h3>

                        {/* Message */}
                        <p className="mb-4 text-center font-pixel text-xs text-stone-300">
                            Are you sure you want to drop{" "}
                            <span className="text-amber-300">{dropModal.itemName}</span>?
                        </p>
                        <p className="mb-4 text-center font-pixel text-[10px] text-stone-500">
                            This action cannot be undone.
                        </p>

                        {/* Buttons */}
                        <div className="flex gap-2">
                            <button
                                onClick={cancelDrop}
                                className="flex-1 rounded border-2 border-stone-600 bg-stone-800 px-4 py-2 font-pixel text-xs text-stone-300 transition hover:bg-stone-700"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={confirmDrop}
                                className="flex-1 rounded border-2 border-red-600 bg-red-900/50 px-4 py-2 font-pixel text-xs text-red-300 transition hover:bg-red-800/50"
                            >
                                Drop
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}

import { Package, Shield, Sword, Swords } from "lucide-react";
import { useCallback, useRef, useState } from "react";
import { getItemIcon } from "@/lib/item-icons";

export interface EquippedItemData {
    slot_number?: number;
    item: {
        id: number;
        name: string;
        description?: string;
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

export interface EquippedSlots {
    head: EquippedItemData | null;
    amulet: EquippedItemData | null;
    chest: EquippedItemData | null;
    legs: EquippedItemData | null;
    weapon: EquippedItemData | null;
    shield: EquippedItemData | null;
    ring: EquippedItemData | null;
    necklace: EquippedItemData | null;
    bracelet: EquippedItemData | null;
    [key: string]: EquippedItemData | null;
}

export interface CombatStats {
    attack: number;
    strength: number;
    defense: number;
    hitpoints?: number;
    hp?: number;
    max_hp?: number;
    atk_bonus: number;
    str_bonus: number;
    def_bonus: number;
    hp_bonus: number;
}

interface EquipmentPanelProps {
    equippedSlots: EquippedSlots;
    combatStats: CombatStats;
    onUnequip?: (slotName: string) => void;
    onExamine?: (slotName: string) => void;
}

const SLOT_ORDER = [
    "head",
    "amulet",
    "necklace",
    "chest",
    "legs",
    "weapon",
    "shield",
    "ring",
    "bracelet",
] as const;

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
    necklace: ({ className }) => (
        <svg
            className={className}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
        >
            <path d="M6 3c0 0-2 4-2 8s4 8 8 8 8-4 8-8-2-8-2-8" />
            <circle cx="12" cy="17" r="2" />
        </svg>
    ),
    bracelet: ({ className }) => (
        <svg
            className={className}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
        >
            <ellipse cx="12" cy="12" rx="7" ry="4" />
            <ellipse cx="12" cy="12" rx="4" ry="2" />
        </svg>
    ),
};

function useLongPress(
    onLongPress: (e: React.TouchEvent | React.MouseEvent) => void,
    { delay = 500 }: { delay?: number } = {},
) {
    const timeout = useRef<NodeJS.Timeout | null>(null);
    const moved = useRef(false);

    const start = useCallback(
        (e: React.TouchEvent | React.MouseEvent) => {
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

    const end = useCallback(() => {
        if (timeout.current) {
            clearTimeout(timeout.current);
            timeout.current = null;
        }
    }, []);

    return {
        onTouchStart: start,
        onTouchMove: move,
        onTouchEnd: end,
    };
}

function EquipmentSlot({
    label,
    equipped,
    slotType,
    interactive,
    onContextMenu,
    onLongPress,
    contextMenuOpen,
}: {
    label: string;
    equipped: EquippedItemData | null;
    slotType: string;
    interactive?: boolean;
    onContextMenu?: (e: React.MouseEvent, slotName: string) => void;
    onLongPress?: (e: React.TouchEvent, slotName: string) => void;
    contextMenuOpen?: boolean;
}) {
    const [showTooltip, setShowTooltip] = useState(false);
    const [tooltipPos, setTooltipPos] = useState<{ x: number; y: number } | null>(null);
    const slotRef = useRef<HTMLDivElement>(null);
    const SlotIcon = equipmentSlotIcons[slotType] || Package;

    const longPressHandlers = useLongPress(
        (e) => {
            if (equipped && interactive && onLongPress) {
                e.preventDefault();
                onLongPress(e as React.TouchEvent, slotType);
            }
        },
        { delay: 500 },
    );

    const handleMouseEnter = () => {
        if (slotRef.current) {
            const rect = slotRef.current.getBoundingClientRect();
            setTooltipPos({ x: rect.right + 8, y: rect.top });
        }
        setShowTooltip(true);
    };

    return (
        <div className="relative flex flex-col items-center">
            <div
                ref={slotRef}
                className={`flex h-12 w-12 items-center justify-center rounded border-2 transition-all ${
                    equipped
                        ? `${rarityColors[equipped.item.rarity]} ring-1 ring-green-500${interactive ? " cursor-pointer hover:brightness-110" : ""}`
                        : "border-stone-700 bg-stone-900/50"
                }`}
                onContextMenu={
                    interactive && equipped && onContextMenu
                        ? (e) => {
                              e.preventDefault();
                              onContextMenu(e, slotType);
                          }
                        : undefined
                }
                onMouseEnter={handleMouseEnter}
                onMouseLeave={() => setShowTooltip(false)}
                {...(interactive ? longPressHandlers : {})}
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
            {equipped && showTooltip && !contextMenuOpen && tooltipPos && (
                <div
                    className="fixed z-[100] w-48 rounded border-2 border-stone-600 bg-stone-900 p-2 shadow-lg"
                    style={{ left: tooltipPos.x, top: tooltipPos.y }}
                >
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
                    {interactive && (
                        <div className="mt-1 font-pixel text-[10px] text-green-400">✓ Equipped</div>
                    )}
                </div>
            )}
        </div>
    );
}

export default function EquipmentPanel({
    equippedSlots,
    combatStats,
    onUnequip,
    onExamine,
}: EquipmentPanelProps) {
    const interactive = !!(onUnequip || onExamine);
    const [contextMenu, setContextMenu] = useState<{
        visible: boolean;
        x: number;
        y: number;
        slotName: string | null;
    }>({
        visible: false,
        x: 0,
        y: 0,
        slotName: null,
    });
    const contextMenuRef = useRef<HTMLDivElement>(null);

    const handleContextMenu = (e: React.MouseEvent, slotName: string) => {
        setContextMenu({ visible: true, x: e.clientX, y: e.clientY, slotName });
    };

    const handleLongPress = (e: React.TouchEvent, slotName: string) => {
        const touch = e.touches?.[0] || e.changedTouches?.[0];
        setContextMenu({ visible: true, x: touch?.clientX ?? 0, y: touch?.clientY ?? 0, slotName });
    };

    const closeContextMenu = () => setContextMenu((prev) => ({ ...prev, visible: false }));

    // Resolve hitpoints display
    const hpDisplay = combatStats.hitpoints ?? combatStats.hp ?? 0;
    const maxHpDisplay = combatStats.max_hp;

    return (
        <div className="rounded-lg border-2 border-stone-600 bg-stone-800/80 p-3">
            <h2 className="mb-3 flex items-center gap-1 font-pixel text-xs text-amber-400">
                <Swords className="h-3 w-3" /> Equipment
            </h2>

            {/* Equipment Slots Grid */}
            <div className="mb-4 grid grid-cols-3 gap-2">
                <div></div>
                <EquipmentSlot
                    label="Head"
                    equipped={equippedSlots.head}
                    slotType="head"
                    interactive={interactive}
                    onContextMenu={handleContextMenu}
                    onLongPress={handleLongPress}
                    contextMenuOpen={contextMenu.visible && contextMenu.slotName === "head"}
                />
                <div></div>

                <EquipmentSlot
                    label="Weapon"
                    equipped={equippedSlots.weapon}
                    slotType="weapon"
                    interactive={interactive}
                    onContextMenu={handleContextMenu}
                    onLongPress={handleLongPress}
                    contextMenuOpen={contextMenu.visible && contextMenu.slotName === "weapon"}
                />
                <EquipmentSlot
                    label="Chest"
                    equipped={equippedSlots.chest}
                    slotType="chest"
                    interactive={interactive}
                    onContextMenu={handleContextMenu}
                    onLongPress={handleLongPress}
                    contextMenuOpen={contextMenu.visible && contextMenu.slotName === "chest"}
                />
                <EquipmentSlot
                    label="Shield"
                    equipped={equippedSlots.shield}
                    slotType="shield"
                    interactive={interactive}
                    onContextMenu={handleContextMenu}
                    onLongPress={handleLongPress}
                    contextMenuOpen={contextMenu.visible && contextMenu.slotName === "shield"}
                />

                <EquipmentSlot
                    label="Ring"
                    equipped={equippedSlots.ring}
                    slotType="ring"
                    interactive={interactive}
                    onContextMenu={handleContextMenu}
                    onLongPress={handleLongPress}
                    contextMenuOpen={contextMenu.visible && contextMenu.slotName === "ring"}
                />
                <EquipmentSlot
                    label="Legs"
                    equipped={equippedSlots.legs}
                    slotType="legs"
                    interactive={interactive}
                    onContextMenu={handleContextMenu}
                    onLongPress={handleLongPress}
                    contextMenuOpen={contextMenu.visible && contextMenu.slotName === "legs"}
                />
                <EquipmentSlot
                    label="Amulet"
                    equipped={equippedSlots.amulet}
                    slotType="amulet"
                    interactive={interactive}
                    onContextMenu={handleContextMenu}
                    onLongPress={handleLongPress}
                    contextMenuOpen={contextMenu.visible && contextMenu.slotName === "amulet"}
                />

                <EquipmentSlot
                    label="Necklace"
                    equipped={equippedSlots.necklace}
                    slotType="necklace"
                    interactive={interactive}
                    onContextMenu={handleContextMenu}
                    onLongPress={handleLongPress}
                    contextMenuOpen={contextMenu.visible && contextMenu.slotName === "necklace"}
                />
                <div></div>
                <EquipmentSlot
                    label="Bracelet"
                    equipped={equippedSlots.bracelet}
                    slotType="bracelet"
                    interactive={interactive}
                    onContextMenu={handleContextMenu}
                    onLongPress={handleLongPress}
                    contextMenuOpen={contextMenu.visible && contextMenu.slotName === "bracelet"}
                />
            </div>

            {/* Equipped Items List */}
            <div className="border-t border-stone-700 pt-3">
                <h3 className="mb-2 font-pixel text-[10px] text-stone-400">Equipped</h3>
                <div className="space-y-1">
                    {SLOT_ORDER.map((slot) => {
                        const eq = equippedSlots[slot];
                        if (!eq) return null;
                        const bonuses = [
                            eq.item.atk_bonus > 0 && (
                                <span key="atk" className="text-red-400">
                                    +{eq.item.atk_bonus} atk
                                </span>
                            ),
                            eq.item.str_bonus > 0 && (
                                <span key="str" className="text-orange-400">
                                    +{eq.item.str_bonus} str
                                </span>
                            ),
                            eq.item.def_bonus > 0 && (
                                <span key="def" className="text-blue-400">
                                    +{eq.item.def_bonus} def
                                </span>
                            ),
                            eq.item.hp_bonus > 0 && (
                                <span key="hp" className="text-green-400">
                                    +{eq.item.hp_bonus} hp
                                </span>
                            ),
                        ].filter(Boolean);
                        return (
                            <div key={slot}>
                                <div
                                    className={`font-pixel text-xs ${rarityTextColors[eq.item.rarity]}`}
                                >
                                    {eq.item.name}
                                </div>
                                {bonuses.length > 0 && (
                                    <div className="flex gap-1.5 font-pixel text-[10px]">
                                        {bonuses}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                    {!Object.values(equippedSlots).some(Boolean) && (
                        <p className="font-pixel text-[8px] italic text-stone-600">
                            Nothing equipped
                        </p>
                    )}
                </div>
            </div>

            {/* Combat Stats */}
            <div className="mt-3 border-t border-stone-700 pt-3">
                <h3 className="mb-2 flex items-center gap-1 font-pixel text-[10px] text-stone-400">
                    <Swords className="h-3 w-3" /> Combat Stats
                </h3>
                <div className="space-y-1.5">
                    <div className="flex justify-between font-pixel text-[10px]">
                        <span className="text-red-400">Attack</span>
                        <span className="text-stone-300">
                            {combatStats.attack}
                            {combatStats.atk_bonus > 0 && (
                                <span className="text-green-400"> (+{combatStats.atk_bonus})</span>
                            )}
                        </span>
                    </div>
                    <div className="flex justify-between font-pixel text-[10px]">
                        <span className="text-orange-400">Strength</span>
                        <span className="text-stone-300">
                            {combatStats.strength}
                            {combatStats.str_bonus > 0 && (
                                <span className="text-green-400"> (+{combatStats.str_bonus})</span>
                            )}
                        </span>
                    </div>
                    <div className="flex justify-between font-pixel text-[10px]">
                        <span className="text-blue-400">Defense</span>
                        <span className="text-stone-300">
                            {combatStats.defense}
                            {combatStats.def_bonus > 0 && (
                                <span className="text-green-400"> (+{combatStats.def_bonus})</span>
                            )}
                        </span>
                    </div>
                    <div className="flex justify-between font-pixel text-[10px]">
                        <span className="text-green-400">Hitpoints</span>
                        <span className="text-stone-300">
                            {maxHpDisplay ? `${hpDisplay}/${maxHpDisplay}` : hpDisplay}
                            {combatStats.hp_bonus > 0 && (
                                <span className="text-green-400"> (+{combatStats.hp_bonus})</span>
                            )}
                        </span>
                    </div>
                </div>
            </div>

            {/* Interactive Context Menu */}
            {interactive &&
                contextMenu.visible &&
                contextMenu.slotName &&
                equippedSlots[contextMenu.slotName] && (
                    <div
                        ref={contextMenuRef}
                        className="fixed z-[200] min-w-40 rounded-lg border-2 border-stone-600 bg-stone-900 p-1 shadow-xl"
                        style={{ left: contextMenu.x, top: contextMenu.y }}
                    >
                        {onUnequip && (
                            <button
                                onClick={() => {
                                    onUnequip(contextMenu.slotName!);
                                    closeContextMenu();
                                }}
                                className="flex w-full items-center gap-2 rounded px-3 py-1.5 font-pixel text-xs text-stone-300 hover:bg-stone-800"
                            >
                                Unequip
                            </button>
                        )}
                        {onUnequip && onExamine && <div className="my-1 h-px bg-stone-700" />}
                        {onExamine && (
                            <button
                                onClick={() => {
                                    onExamine(contextMenu.slotName!);
                                    closeContextMenu();
                                }}
                                className="flex w-full items-center gap-2 rounded px-3 py-1.5 font-pixel text-xs text-stone-300 hover:bg-stone-800"
                            >
                                Examine
                            </button>
                        )}
                        <div className="my-1 h-px bg-stone-700" />
                        <button
                            onClick={closeContextMenu}
                            className="flex w-full items-center gap-2 rounded px-3 py-1.5 font-pixel text-xs text-stone-400 hover:bg-stone-800"
                        >
                            Cancel
                        </button>
                    </div>
                )}
        </div>
    );
}

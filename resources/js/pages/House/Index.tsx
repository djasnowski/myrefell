import { Head, router, usePage } from "@inertiajs/react";
import {
    ArrowDownToLine,
    ArrowUpFromLine,
    ArrowUpCircle,
    BookOpen,
    Church,
    Coins,
    Flame,
    Hammer,
    Home,
    Link2,
    Loader2,
    Lock,
    MapPin,
    Package,
    Plus,
    Search,
    Sparkles,
    Trash2,
    Wrench,
    X,
    Zap,
} from "lucide-react";
import { useEffect, useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { gameToast } from "@/components/ui/game-toast";
import type { BreadcrumbItem } from "@/types";

interface FurnitureOption {
    key: string;
    name: string;
    level: number;
    materials: Record<string, number>;
    xp: number;
    effect?: Record<string, number> | null;
}

interface Hotspot {
    name: string;
    current: { key: string; name: string } | null;
    options: FurnitureOption[];
}

interface Room {
    id: number;
    room_type: string;
    room_name: string;
    grid_x: number;
    grid_y: number;
    hotspots: Record<string, Hotspot>;
}

interface StorageItem {
    item_name: string;
    item_type: string;
    quantity: number;
}

interface House {
    id: number;
    name: string;
    tier: string;
    tier_name: string;
    condition: number;
    grid_size: number;
    max_rooms: number;
    storage_capacity: number;
    storage_used: number;
    kingdom: { id: number; name: string } | null;
    rooms: Room[];
    storage: StorageItem[];
}

interface RoomType {
    key: string;
    name: string;
    description: string;
    level: number;
    cost: number;
    is_unlocked: boolean;
    hotspot_count: number;
}

interface UpgradeInfo {
    target_tier: string;
    target_name: string;
    target_config: {
        name: string;
        level: number;
        title_level: number;
        cost: number;
        grid: number;
        max_rooms: number;
        storage: number;
        upkeep: number;
    };
    check: {
        can_upgrade: boolean;
        reason: string | null;
        cost?: number;
    };
}

interface PortalSlot {
    slot: number;
    furniture_key: string | null;
    furniture_name: string | null;
    destination: { type: string; id: number; name: string } | null;
    set_cost: number | null;
}

interface Destination {
    type: string;
    id: number;
    name: string;
}

interface PageProps {
    house: House | null;
    canPurchase: { can_purchase: boolean; reason: string | null } | null;
    purchaseCost: number;
    roomTypes: RoomType[];
    constructionLevel: number;
    playerGold: number;
    upgradeInfo: UpgradeInfo | null;
    houseBuffs: Record<string, number>;
    adjacencyBonuses: Record<string, number>;
    adjacencyDefinitions: [string, string, string, number, string][];
    portals: PortalSlot[];
    availableDestinations: Destination[];
    flash?: { success?: string; error?: string };
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "My House", href: "/house" },
];

const roomIcons: Record<string, string> = {
    parlour: "🪑",
    kitchen: "🍳",
    bedroom: "🛏️",
    workshop: "🔧",
    study: "📖",
    hearth_room: "🔥",
    forge: "⚒️",
    chapel: "⛪",
    portal_chamber: "🌀",
    dining_room: "🍽️",
    servant_quarters: "🛎️",
};

const buffLabels: Record<string, string> = {
    energy_regen_bonus: "Energy Regen",
    max_hp_bonus: "Max HP",
    crafting_xp_bonus: "Crafting XP",
    smithing_xp_bonus: "Smithing XP",
    gathering_xp_bonus: "Gathering XP",
    prayer_xp_bonus: "Prayer XP",
    prayer_bonus: "Prayer",
    attack_bonus: "Attack",
    farming_yield_bonus: "Farming Yield",
    smithing_speed_bonus: "Smithing Speed",
    cooking_xp_bonus: "Cooking XP",
    servant_speed_bonus: "Servant Speed",
};

export default function HouseIndex() {
    const {
        house,
        canPurchase,
        purchaseCost,
        roomTypes,
        constructionLevel,
        playerGold,
        upgradeInfo,
        houseBuffs,
        adjacencyBonuses,
        adjacencyDefinitions,
        portals,
        availableDestinations,
        flash,
    } = usePage<PageProps>().props;
    const [loading, setLoading] = useState(false);
    const [selectedRoom, setSelectedRoom] = useState<Room | null>(null);
    const [buildingAt, setBuildingAt] = useState<{ x: number; y: number } | null>(null);
    const [activeTab, setActiveTab] = useState<"rooms" | "storage">("rooms");
    const [storageAction, setStorageAction] = useState<{
        type: "deposit" | "withdraw";
        item?: string;
    } | null>(null);
    const [storageQty, setStorageQty] = useState(1);
    const [storageItemName, setStorageItemName] = useState("");

    useEffect(() => {
        if (flash?.success) {
            gameToast.success(flash.success);
        }
        if (flash?.error) {
            gameToast.error(flash.error);
        }
    }, [flash]);

    // Reset selected room when house data changes (after rebuild)
    useEffect(() => {
        if (selectedRoom && house) {
            const updated = house.rooms.find((r) => r.id === selectedRoom.id);
            setSelectedRoom(updated || null);
        }
    }, [house]);

    const handlePurchase = () => {
        setLoading(true);
        router.post(
            "/house/purchase",
            {},
            {
                preserveScroll: true,
                onSuccess: () => router.reload(),
                onFinish: () => setLoading(false),
            },
        );
    };

    const handleBuildRoom = (roomType: string) => {
        if (!buildingAt) return;
        setLoading(true);
        router.post(
            "/house/build-room",
            { room_type: roomType, grid_x: buildingAt.x, grid_y: buildingAt.y },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                    setBuildingAt(null);
                },
                onFinish: () => setLoading(false),
            },
        );
    };

    const handleBuildFurniture = (roomId: number, hotspotSlug: string, furnitureKey: string) => {
        setLoading(true);
        router.post(
            "/house/build-furniture",
            { room_id: roomId, hotspot_slug: hotspotSlug, furniture_key: furnitureKey },
            {
                preserveScroll: true,
                onSuccess: () => router.reload(),
                onFinish: () => setLoading(false),
            },
        );
    };

    const handleDemolish = (roomId: number, hotspotSlug: string) => {
        setLoading(true);
        router.post(
            "/house/demolish-furniture",
            { room_id: roomId, hotspot_slug: hotspotSlug },
            {
                preserveScroll: true,
                onSuccess: () => router.reload(),
                onFinish: () => setLoading(false),
            },
        );
    };

    const handleUpgrade = () => {
        if (!upgradeInfo) return;
        setLoading(true);
        router.post(
            "/house/upgrade",
            { target_tier: upgradeInfo.target_tier },
            {
                preserveScroll: true,
                onSuccess: () => router.reload(),
                onFinish: () => setLoading(false),
            },
        );
    };

    const handleDeposit = () => {
        if (!storageItemName) return;
        setLoading(true);
        router.post(
            "/house/deposit",
            { item_name: storageItemName, quantity: storageQty },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                    setStorageAction(null);
                    setStorageItemName("");
                    setStorageQty(1);
                },
                onFinish: () => setLoading(false),
            },
        );
    };

    const handleWithdraw = (itemName: string) => {
        setLoading(true);
        router.post(
            "/house/withdraw",
            { item_name: itemName, quantity: storageQty },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                    setStorageQty(1);
                },
                onFinish: () => setLoading(false),
            },
        );
    };

    const handleSetPortal = (slot: number, destType: string, destId: number) => {
        setLoading(true);
        router.post(
            "/house/set-portal",
            { slot, destination_type: destType, destination_id: destId },
            {
                preserveScroll: true,
                onSuccess: () => router.reload(),
                onFinish: () => setLoading(false),
            },
        );
    };

    const handleTeleport = (slot: number) => {
        setLoading(true);
        router.post(
            "/house/teleport",
            { slot },
            {
                preserveScroll: true,
                onSuccess: () => router.reload(),
                onFinish: () => setLoading(false),
            },
        );
    };

    // No house - show purchase UI
    if (!house) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="My House" />
                <div className="mx-auto max-w-2xl p-4">
                    <div className="rounded-lg border border-stone-700/50 bg-gradient-to-br from-stone-800/80 to-stone-900 p-8 text-center">
                        <Home className="mx-auto mb-4 h-12 w-12 text-amber-400/50" />
                        <h1 className="font-pixel text-xl text-amber-100">Purchase a House</h1>
                        <p className="mt-2 font-pixel text-xs text-stone-400">
                            A cottage in the kingdom. Build rooms, craft furniture, and store your
                            valuables.
                        </p>

                        <div className="mt-6 rounded-md border border-stone-600/50 bg-stone-800/50 p-4">
                            <div className="font-pixel text-sm text-stone-300">Cottage</div>
                            <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                3x3 grid &bull; 3 rooms &bull; 100 storage
                            </div>
                            <div className="mt-2 flex items-center justify-center gap-1.5">
                                <Coins className="h-4 w-4 text-yellow-400" />
                                <span className="font-pixel text-sm text-yellow-300">
                                    {purchaseCost.toLocaleString()} gold
                                </span>
                            </div>
                        </div>

                        {canPurchase && !canPurchase.can_purchase && (
                            <div className="mt-4 font-pixel text-xs text-red-400">
                                {canPurchase.reason}
                            </div>
                        )}

                        <button
                            onClick={handlePurchase}
                            disabled={loading || !canPurchase?.can_purchase}
                            className="mt-6 rounded-md border border-amber-600/50 bg-amber-900/50 px-6 py-2.5 font-pixel text-sm text-amber-300 transition-colors hover:bg-amber-800/50 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            {loading ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                "Purchase Cottage"
                            )}
                        </button>
                    </div>
                </div>
            </AppLayout>
        );
    }

    // Has house - show grid + management
    const gridSize = house.grid_size;
    const roomMap = new Map(house.rooms.map((r) => [`${r.grid_x},${r.grid_y}`, r]));

    // Check which rooms have adjacency bonuses
    const roomsWithAdjacency = new Set<string>();
    if (adjacencyDefinitions && adjacencyBonuses) {
        for (const [roomA, roomB, effectKey] of adjacencyDefinitions) {
            if (adjacencyBonuses[effectKey]) {
                // Find rooms of these types in the grid
                for (const room of house.rooms) {
                    if (room.room_type === roomA || room.room_type === roomB) {
                        roomsWithAdjacency.add(`${room.grid_x},${room.grid_y}`);
                    }
                }
            }
        }
    }

    const isPortalChamberSelected = selectedRoom?.room_type === "portal_chamber";

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My House" />
            <div className="mx-auto max-w-6xl p-4">
                {/* Header */}
                <div className="mb-4 flex flex-wrap items-center gap-3">
                    <div className="rounded-lg bg-amber-900/50 p-2">
                        <Home className="h-6 w-6 text-amber-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-lg text-amber-100">{house.name}</h1>
                        <p className="font-pixel text-[10px] text-stone-500">
                            {house.tier_name} &bull; {house.kingdom?.name} &bull; Construction Lv.{" "}
                            {constructionLevel}
                        </p>
                    </div>
                    <div className="ml-auto flex items-center gap-3">
                        <div className="flex items-center gap-1.5 rounded-md bg-stone-800/50 px-2 py-1">
                            <Package className="h-3.5 w-3.5 text-stone-400" />
                            <span className="font-pixel text-[10px] text-stone-400">
                                {house.storage_used}/{house.storage_capacity}
                            </span>
                        </div>
                        <div className="flex items-center gap-1.5 rounded-md bg-yellow-900/30 px-2 py-1">
                            <Coins className="h-3.5 w-3.5 text-yellow-400" />
                            <span className="font-pixel text-[10px] text-yellow-300">
                                {playerGold.toLocaleString()}
                            </span>
                        </div>
                    </div>
                </div>

                {/* House Buffs Summary */}
                {houseBuffs && Object.keys(houseBuffs).length > 0 && (
                    <div className="mb-4 rounded-lg border border-cyan-800/30 bg-cyan-900/10 p-3">
                        <div className="mb-1.5 flex items-center gap-1.5 font-pixel text-[10px] text-cyan-300">
                            <Sparkles className="h-3.5 w-3.5" />
                            Active House Buffs
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {Object.entries(houseBuffs).map(([key, value]) => {
                                const isPercent =
                                    key.includes("_xp_") ||
                                    key.includes("_yield_") ||
                                    key.includes("_regen_") ||
                                    key.includes("_speed_");
                                const isAdjacency = adjacencyBonuses && adjacencyBonuses[key];
                                return (
                                    <span
                                        key={key}
                                        className={`rounded-md border px-2 py-0.5 font-pixel text-[9px] ${
                                            isAdjacency
                                                ? "border-emerald-700/30 bg-emerald-900/20 text-emerald-200"
                                                : "border-cyan-700/30 bg-cyan-900/20 text-cyan-200"
                                        }`}
                                    >
                                        +{value}
                                        {isPercent ? "%" : ""}{" "}
                                        {buffLabels[key] || key.replace(/_/g, " ")}
                                        {isAdjacency && " ✦"}
                                    </span>
                                );
                            })}
                        </div>
                    </div>
                )}

                {/* Upgrade Panel */}
                {upgradeInfo && (
                    <div className="mb-4 rounded-lg border border-purple-700/30 bg-purple-900/10 p-3">
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="flex items-center gap-1.5 font-pixel text-xs text-purple-200">
                                    <ArrowUpCircle className="h-4 w-4 text-purple-400" />
                                    Upgrade to {upgradeInfo.target_name}
                                </div>
                                <div className="mt-1 font-pixel text-[9px] text-stone-500">
                                    {upgradeInfo.target_config.grid}x
                                    {upgradeInfo.target_config.grid} grid &bull;{" "}
                                    {upgradeInfo.target_config.max_rooms} rooms &bull;{" "}
                                    {upgradeInfo.target_config.storage} storage &bull; Lv{" "}
                                    {upgradeInfo.target_config.level} Construction
                                </div>
                            </div>
                            <div className="flex items-center gap-3">
                                <div className="flex items-center gap-1">
                                    <Coins className="h-3.5 w-3.5 text-yellow-400" />
                                    <span className="font-pixel text-[10px] text-yellow-300">
                                        {upgradeInfo.check.cost?.toLocaleString()}g
                                    </span>
                                </div>
                                <button
                                    onClick={handleUpgrade}
                                    disabled={loading || !upgradeInfo.check.can_upgrade}
                                    className="rounded-md border border-purple-600/50 bg-purple-900/40 px-3 py-1.5 font-pixel text-[10px] text-purple-200 transition-colors hover:bg-purple-800/40 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    {loading ? (
                                        <Loader2 className="h-3 w-3 animate-spin" />
                                    ) : (
                                        "Upgrade"
                                    )}
                                </button>
                            </div>
                        </div>
                        {!upgradeInfo.check.can_upgrade && upgradeInfo.check.reason && (
                            <div className="mt-1.5 font-pixel text-[9px] text-red-400">
                                {upgradeInfo.check.reason}
                            </div>
                        )}
                    </div>
                )}

                {/* Tabs */}
                <div className="mb-4 flex gap-2">
                    <button
                        onClick={() => {
                            setActiveTab("rooms");
                            setStorageAction(null);
                        }}
                        className={`rounded-md px-3 py-1.5 font-pixel text-xs transition-colors ${activeTab === "rooms" ? "bg-amber-900/50 text-amber-300 border border-amber-600/50" : "text-stone-400 hover:text-stone-300 border border-stone-700/50"}`}
                    >
                        Rooms
                    </button>
                    <button
                        onClick={() => {
                            setActiveTab("storage");
                            setSelectedRoom(null);
                            setBuildingAt(null);
                        }}
                        className={`rounded-md px-3 py-1.5 font-pixel text-xs transition-colors ${activeTab === "storage" ? "bg-amber-900/50 text-amber-300 border border-amber-600/50" : "text-stone-400 hover:text-stone-300 border border-stone-700/50"}`}
                    >
                        Storage
                    </button>
                </div>

                {activeTab === "rooms" && (
                    <div className="grid gap-4 lg:grid-cols-[1fr_320px]">
                        {/* Grid */}
                        <div className="rounded-lg border border-stone-700/50 bg-stone-900/50 p-4">
                            <div
                                className="mx-auto grid gap-2"
                                style={{
                                    gridTemplateColumns: `repeat(${gridSize}, minmax(0, 1fr))`,
                                    maxWidth: `${gridSize * 120}px`,
                                }}
                            >
                                {Array.from({ length: gridSize * gridSize }, (_, i) => {
                                    const x = i % gridSize;
                                    const y = Math.floor(i / gridSize);
                                    const room = roomMap.get(`${x},${y}`);
                                    const isSelected =
                                        selectedRoom?.grid_x === x && selectedRoom?.grid_y === y;
                                    const isBuildTarget =
                                        buildingAt?.x === x && buildingAt?.y === y;
                                    const hasAdjacency = roomsWithAdjacency.has(`${x},${y}`);

                                    if (room) {
                                        return (
                                            <button
                                                key={`${x},${y}`}
                                                onClick={() => {
                                                    setSelectedRoom(room);
                                                    setBuildingAt(null);
                                                }}
                                                className={`relative flex aspect-square flex-col items-center justify-center rounded-lg border-2 p-2 transition-all ${
                                                    isSelected
                                                        ? "border-amber-400 bg-amber-900/30 shadow-lg shadow-amber-900/20"
                                                        : "border-stone-600 bg-stone-800/80 hover:border-stone-500 hover:bg-stone-700/50"
                                                }`}
                                            >
                                                <span className="text-2xl">
                                                    {roomIcons[room.room_type] || "🏠"}
                                                </span>
                                                <span className="mt-1 font-pixel text-[9px] text-stone-300">
                                                    {room.room_name}
                                                </span>
                                                {hasAdjacency && (
                                                    <span
                                                        className="absolute top-1 right-1 text-[8px] text-emerald-400"
                                                        title="Adjacency bonus active"
                                                    >
                                                        ✦
                                                    </span>
                                                )}
                                            </button>
                                        );
                                    }

                                    return (
                                        <button
                                            key={`${x},${y}`}
                                            onClick={() => {
                                                if (house.rooms.length < house.max_rooms) {
                                                    setBuildingAt({ x, y });
                                                    setSelectedRoom(null);
                                                }
                                            }}
                                            disabled={house.rooms.length >= house.max_rooms}
                                            className={`flex aspect-square flex-col items-center justify-center rounded-lg border-2 border-dashed p-2 transition-all ${
                                                isBuildTarget
                                                    ? "border-green-400 bg-green-900/20"
                                                    : "border-stone-700/50 hover:border-stone-500 hover:bg-stone-800/30 disabled:cursor-not-allowed disabled:opacity-30"
                                            }`}
                                        >
                                            <Plus
                                                className={`h-5 w-5 ${isBuildTarget ? "text-green-400" : "text-stone-600"}`}
                                            />
                                            <span className="mt-1 font-pixel text-[8px] text-stone-600">
                                                Empty
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>
                            <div className="mt-3 text-center font-pixel text-[10px] text-stone-600">
                                {house.rooms.length}/{house.max_rooms} rooms built
                            </div>
                        </div>

                        {/* Side Panel */}
                        <div className="rounded-lg border border-stone-700/50 bg-stone-900/50 p-4">
                            {buildingAt && (
                                <BuildRoomPanel
                                    roomTypes={roomTypes}
                                    position={buildingAt}
                                    loading={loading}
                                    playerGold={playerGold}
                                    onBuild={handleBuildRoom}
                                    onCancel={() => setBuildingAt(null)}
                                />
                            )}

                            {selectedRoom && !isPortalChamberSelected && (
                                <RoomDetailPanel
                                    room={selectedRoom}
                                    constructionLevel={constructionLevel}
                                    loading={loading}
                                    adjacencyDefinitions={adjacencyDefinitions}
                                    adjacencyBonuses={adjacencyBonuses}
                                    onBuildFurniture={handleBuildFurniture}
                                    onDemolish={handleDemolish}
                                    onClose={() => setSelectedRoom(null)}
                                />
                            )}

                            {isPortalChamberSelected && (
                                <PortalChamberPanel
                                    room={selectedRoom!}
                                    portals={portals}
                                    availableDestinations={availableDestinations}
                                    constructionLevel={constructionLevel}
                                    loading={loading}
                                    onSetPortal={handleSetPortal}
                                    onTeleport={handleTeleport}
                                    onBuildFurniture={handleBuildFurniture}
                                    onDemolish={handleDemolish}
                                    onClose={() => setSelectedRoom(null)}
                                />
                            )}

                            {!buildingAt && !selectedRoom && (
                                <div className="py-8 text-center">
                                    <Home className="mx-auto h-8 w-8 text-stone-600" />
                                    <p className="mt-2 font-pixel text-xs text-stone-500">
                                        Click a room to view details, or click an empty cell to
                                        build.
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {activeTab === "storage" && (
                    <StoragePanel
                        storage={house.storage}
                        storageUsed={house.storage_used}
                        storageCapacity={house.storage_capacity}
                        loading={loading}
                        storageAction={storageAction}
                        storageQty={storageQty}
                        storageItemName={storageItemName}
                        onSetStorageAction={setStorageAction}
                        onSetStorageQty={setStorageQty}
                        onSetStorageItemName={setStorageItemName}
                        onDeposit={handleDeposit}
                        onWithdraw={handleWithdraw}
                    />
                )}
            </div>
        </AppLayout>
    );
}

function BuildRoomPanel({
    roomTypes,
    position,
    loading,
    playerGold,
    onBuild,
    onCancel,
}: {
    roomTypes: RoomType[];
    position: { x: number; y: number };
    loading: boolean;
    playerGold: number;
    onBuild: (roomType: string) => void;
    onCancel: () => void;
}) {
    return (
        <div>
            <div className="mb-3 flex items-center justify-between">
                <h3 className="font-pixel text-sm text-green-300">Build Room</h3>
                <button onClick={onCancel} className="text-stone-500 hover:text-stone-300">
                    <X className="h-4 w-4" />
                </button>
            </div>
            <p className="mb-3 font-pixel text-[10px] text-stone-500">
                Position ({position.x}, {position.y})
            </p>
            <div className="space-y-2">
                {roomTypes.map((rt) => (
                    <button
                        key={rt.key}
                        onClick={() => onBuild(rt.key)}
                        disabled={loading || !rt.is_unlocked || playerGold < rt.cost}
                        className="w-full rounded-md border border-stone-600/50 bg-stone-800/50 p-3 text-left transition-colors hover:bg-stone-700/50 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <div className="flex items-center justify-between">
                            <span className="font-pixel text-xs text-stone-200">
                                {roomIcons[rt.key] || "🏠"} {rt.name}
                            </span>
                            {!rt.is_unlocked && (
                                <span className="flex items-center gap-1 font-pixel text-[9px] text-stone-500">
                                    <Lock className="h-3 w-3" /> Lv {rt.level}
                                </span>
                            )}
                        </div>
                        <div className="mt-1 font-pixel text-[9px] text-stone-500">
                            {rt.description}
                        </div>
                        <div className="mt-1 flex items-center gap-2 font-pixel text-[9px]">
                            <span className="text-yellow-400">{rt.cost.toLocaleString()}g</span>
                            <span className="text-stone-500">{rt.hotspot_count} hotspots</span>
                        </div>
                    </button>
                ))}
            </div>
        </div>
    );
}

function RoomDetailPanel({
    room,
    constructionLevel,
    loading,
    adjacencyDefinitions,
    adjacencyBonuses,
    onBuildFurniture,
    onDemolish,
    onClose,
}: {
    room: Room;
    constructionLevel: number;
    loading: boolean;
    adjacencyDefinitions: [string, string, string, number, string][];
    adjacencyBonuses: Record<string, number>;
    onBuildFurniture: (roomId: number, hotspotSlug: string, furnitureKey: string) => void;
    onDemolish: (roomId: number, hotspotSlug: string) => void;
    onClose: () => void;
}) {
    const [expandedHotspot, setExpandedHotspot] = useState<string | null>(null);

    // Find active adjacency bonuses for this room
    const roomAdjacencies =
        adjacencyDefinitions?.filter(
            ([roomA, roomB, effectKey]) =>
                (room.room_type === roomA || room.room_type === roomB) &&
                adjacencyBonuses?.[effectKey],
        ) || [];

    return (
        <div>
            <div className="mb-3 flex items-center justify-between">
                <h3 className="font-pixel text-sm text-amber-300">
                    {roomIcons[room.room_type] || "🏠"} {room.room_name}
                </h3>
                <button onClick={onClose} className="text-stone-500 hover:text-stone-300">
                    <X className="h-4 w-4" />
                </button>
            </div>

            {roomAdjacencies.length > 0 && (
                <div className="mb-3 rounded-md border border-emerald-700/30 bg-emerald-900/10 p-2">
                    <div className="flex items-center gap-1 font-pixel text-[9px] text-emerald-300">
                        <Link2 className="h-3 w-3" /> Adjacency Bonuses
                    </div>
                    {roomAdjacencies.map(([, , , , desc], i) => (
                        <div key={i} className="mt-1 font-pixel text-[8px] text-emerald-400/80">
                            ✦ {desc}
                        </div>
                    ))}
                </div>
            )}

            <div className="space-y-2">
                {Object.entries(room.hotspots).map(([slug, hotspot]) => (
                    <div
                        key={slug}
                        className="rounded-md border border-stone-700/50 bg-stone-800/30 p-2.5"
                    >
                        <div className="flex items-center justify-between">
                            <span className="font-pixel text-[11px] text-stone-300">
                                {hotspot.name}
                            </span>
                            {hotspot.current ? (
                                <div className="flex items-center gap-1.5">
                                    <span className="font-pixel text-[10px] text-green-400">
                                        {hotspot.current.name}
                                    </span>
                                    <button
                                        onClick={() => onDemolish(room.id, slug)}
                                        disabled={loading}
                                        className="text-red-500/50 hover:text-red-400"
                                        title="Demolish"
                                    >
                                        <Trash2 className="h-3 w-3" />
                                    </button>
                                </div>
                            ) : (
                                <span className="font-pixel text-[10px] text-stone-600">Empty</span>
                            )}
                        </div>

                        <button
                            onClick={() =>
                                setExpandedHotspot(expandedHotspot === slug ? null : slug)
                            }
                            className="mt-1 font-pixel text-[9px] text-amber-400/70 hover:text-amber-400"
                        >
                            {expandedHotspot === slug ? "Hide options" : "Build options"}
                        </button>

                        {expandedHotspot === slug && (
                            <div className="mt-2 space-y-1.5">
                                {hotspot.options.map((opt) => {
                                    const isCurrentlyBuilt = hotspot.current?.key === opt.key;
                                    const meetsLevel = constructionLevel >= opt.level;

                                    return (
                                        <div
                                            key={opt.key}
                                            className={`rounded border p-2 ${isCurrentlyBuilt ? "border-green-600/50 bg-green-900/20" : "border-stone-700/30 bg-stone-900/30"}`}
                                        >
                                            <div className="flex items-center justify-between">
                                                <span
                                                    className={`font-pixel text-[10px] ${isCurrentlyBuilt ? "text-green-300" : "text-stone-300"}`}
                                                >
                                                    {opt.name}
                                                </span>
                                                {!meetsLevel && (
                                                    <span className="flex items-center gap-0.5 font-pixel text-[8px] text-stone-600">
                                                        <Lock className="h-2.5 w-2.5" /> Lv{" "}
                                                        {opt.level}
                                                    </span>
                                                )}
                                            </div>
                                            <div className="mt-0.5 font-pixel text-[8px] text-stone-500">
                                                {Object.entries(opt.materials)
                                                    .map(([m, q]) => `${q} ${m}`)
                                                    .join(", ")}{" "}
                                                &bull; +{opt.xp} XP
                                                {opt.effect &&
                                                    Object.entries(opt.effect).map(([k, v]) => (
                                                        <span key={k} className="text-cyan-400">
                                                            {" "}
                                                            &bull; +{v}% {k.replace(/_/g, " ")}
                                                        </span>
                                                    ))}
                                            </div>
                                            {!isCurrentlyBuilt && meetsLevel && (
                                                <button
                                                    onClick={() =>
                                                        onBuildFurniture(room.id, slug, opt.key)
                                                    }
                                                    disabled={loading}
                                                    className="mt-1.5 flex items-center gap-1 rounded border border-amber-600/40 bg-amber-900/30 px-2 py-0.5 font-pixel text-[9px] text-amber-300 transition-colors hover:bg-amber-800/30 disabled:opacity-40"
                                                >
                                                    <Hammer className="h-3 w-3" /> Build
                                                </button>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}

function PortalChamberPanel({
    room,
    portals,
    availableDestinations,
    constructionLevel,
    loading,
    onSetPortal,
    onTeleport,
    onBuildFurniture,
    onDemolish,
    onClose,
}: {
    room: Room;
    portals: PortalSlot[];
    availableDestinations: Destination[];
    constructionLevel: number;
    loading: boolean;
    onSetPortal: (slot: number, destType: string, destId: number) => void;
    onTeleport: (slot: number) => void;
    onBuildFurniture: (roomId: number, hotspotSlug: string, furnitureKey: string) => void;
    onDemolish: (roomId: number, hotspotSlug: string) => void;
    onClose: () => void;
}) {
    const [configuringSlot, setConfiguringSlot] = useState<number | null>(null);
    const [searchTerm, setSearchTerm] = useState("");
    const [expandedHotspot, setExpandedHotspot] = useState<string | null>(null);

    const filteredDestinations = availableDestinations.filter((d) =>
        d.name.toLowerCase().includes(searchTerm.toLowerCase()),
    );

    return (
        <div>
            <div className="mb-3 flex items-center justify-between">
                <h3 className="font-pixel text-sm text-purple-300">🌀 Portal Chamber</h3>
                <button onClick={onClose} className="text-stone-500 hover:text-stone-300">
                    <X className="h-4 w-4" />
                </button>
            </div>

            {/* Portal Slots */}
            <div className="space-y-2.5">
                {portals.map((portal) => {
                    const hotspotSlug = `portal_${portal.slot}`;
                    const hotspot = room.hotspots[hotspotSlug];

                    return (
                        <div
                            key={portal.slot}
                            className="rounded-md border border-purple-700/30 bg-purple-900/10 p-3"
                        >
                            <div className="flex items-center justify-between">
                                <span className="font-pixel text-[11px] text-purple-200">
                                    Portal {portal.slot}
                                </span>
                                {portal.furniture_key ? (
                                    <span className="font-pixel text-[9px] text-purple-400">
                                        {portal.furniture_name}
                                    </span>
                                ) : (
                                    <span className="font-pixel text-[9px] text-stone-600">
                                        No portal built
                                    </span>
                                )}
                            </div>

                            {portal.furniture_key && (
                                <>
                                    {portal.destination ? (
                                        <div className="mt-2">
                                            <div className="flex items-center gap-1.5">
                                                <MapPin className="h-3 w-3 text-purple-400" />
                                                <span className="font-pixel text-[10px] text-stone-300">
                                                    {portal.destination.name}
                                                </span>
                                                <span className="font-pixel text-[8px] text-stone-600">
                                                    ({portal.destination.type})
                                                </span>
                                            </div>
                                            <div className="mt-1.5 flex gap-1.5">
                                                <button
                                                    onClick={() => onTeleport(portal.slot)}
                                                    disabled={loading}
                                                    className="flex items-center gap-1 rounded border border-purple-600/40 bg-purple-900/30 px-2 py-0.5 font-pixel text-[9px] text-purple-200 transition-colors hover:bg-purple-800/30 disabled:opacity-40"
                                                >
                                                    <Zap className="h-3 w-3" /> Teleport
                                                </button>
                                                <button
                                                    onClick={() => {
                                                        setConfiguringSlot(portal.slot);
                                                        setSearchTerm("");
                                                    }}
                                                    disabled={loading}
                                                    className="rounded border border-stone-600/40 bg-stone-800/30 px-2 py-0.5 font-pixel text-[9px] text-stone-400 transition-colors hover:bg-stone-700/30 disabled:opacity-40"
                                                >
                                                    Change
                                                </button>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="mt-2">
                                            <button
                                                onClick={() => {
                                                    setConfiguringSlot(portal.slot);
                                                    setSearchTerm("");
                                                }}
                                                disabled={loading}
                                                className="flex items-center gap-1 rounded border border-purple-600/40 bg-purple-900/30 px-2 py-0.5 font-pixel text-[9px] text-purple-200 transition-colors hover:bg-purple-800/30 disabled:opacity-40"
                                            >
                                                <MapPin className="h-3 w-3" /> Configure Destination
                                            </button>
                                            {portal.set_cost && (
                                                <div className="mt-1 flex items-center gap-1 font-pixel text-[8px] text-stone-500">
                                                    <Coins className="h-2.5 w-2.5 text-yellow-500" />
                                                    {portal.set_cost.toLocaleString()}g to set
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </>
                            )}

                            {!portal.furniture_key && hotspot && (
                                <div className="mt-2">
                                    <button
                                        onClick={() =>
                                            setExpandedHotspot(
                                                expandedHotspot === hotspotSlug
                                                    ? null
                                                    : hotspotSlug,
                                            )
                                        }
                                        className="font-pixel text-[9px] text-amber-400/70 hover:text-amber-400"
                                    >
                                        {expandedHotspot === hotspotSlug
                                            ? "Hide options"
                                            : "Build options"}
                                    </button>
                                    {expandedHotspot === hotspotSlug && (
                                        <div className="mt-2 space-y-1.5">
                                            {hotspot.options.map((opt) => {
                                                const meetsLevel = constructionLevel >= opt.level;
                                                return (
                                                    <div
                                                        key={opt.key}
                                                        className="rounded border border-stone-700/30 bg-stone-900/30 p-2"
                                                    >
                                                        <div className="flex items-center justify-between">
                                                            <span className="font-pixel text-[10px] text-stone-300">
                                                                {opt.name}
                                                            </span>
                                                            {!meetsLevel && (
                                                                <span className="flex items-center gap-0.5 font-pixel text-[8px] text-stone-600">
                                                                    <Lock className="h-2.5 w-2.5" />{" "}
                                                                    Lv {opt.level}
                                                                </span>
                                                            )}
                                                        </div>
                                                        <div className="mt-0.5 font-pixel text-[8px] text-stone-500">
                                                            {Object.entries(opt.materials)
                                                                .map(([m, q]) => `${q} ${m}`)
                                                                .join(", ")}{" "}
                                                            &bull; +{opt.xp} XP
                                                        </div>
                                                        {meetsLevel && (
                                                            <button
                                                                onClick={() =>
                                                                    onBuildFurniture(
                                                                        room.id,
                                                                        hotspotSlug,
                                                                        opt.key,
                                                                    )
                                                                }
                                                                disabled={loading}
                                                                className="mt-1.5 flex items-center gap-1 rounded border border-amber-600/40 bg-amber-900/30 px-2 py-0.5 font-pixel text-[9px] text-amber-300 transition-colors hover:bg-amber-800/30 disabled:opacity-40"
                                                            >
                                                                <Hammer className="h-3 w-3" /> Build
                                                            </button>
                                                        )}
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Destination picker */}
                            {configuringSlot === portal.slot && (
                                <div className="mt-2 rounded-md border border-stone-600/50 bg-stone-800/50 p-2">
                                    <div className="mb-2 flex items-center gap-1.5">
                                        <Search className="h-3 w-3 text-stone-500" />
                                        <input
                                            type="text"
                                            placeholder="Search destinations..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            className="flex-1 bg-transparent font-pixel text-[10px] text-stone-200 placeholder-stone-600 focus:outline-none"
                                            autoFocus
                                        />
                                        <button
                                            onClick={() => setConfiguringSlot(null)}
                                            className="text-stone-500 hover:text-stone-300"
                                        >
                                            <X className="h-3 w-3" />
                                        </button>
                                    </div>
                                    <div className="max-h-40 space-y-0.5 overflow-y-auto">
                                        {filteredDestinations.slice(0, 20).map((dest) => (
                                            <button
                                                key={`${dest.type}-${dest.id}`}
                                                onClick={() => {
                                                    onSetPortal(portal.slot, dest.type, dest.id);
                                                    setConfiguringSlot(null);
                                                }}
                                                disabled={loading}
                                                className="flex w-full items-center justify-between rounded px-2 py-1 text-left transition-colors hover:bg-stone-700/50 disabled:opacity-40"
                                            >
                                                <span className="font-pixel text-[10px] text-stone-300">
                                                    {dest.name}
                                                </span>
                                                <span className="font-pixel text-[8px] text-stone-600">
                                                    {dest.type}
                                                </span>
                                            </button>
                                        ))}
                                        {filteredDestinations.length === 0 && (
                                            <div className="py-2 text-center font-pixel text-[9px] text-stone-600">
                                                No results
                                            </div>
                                        )}
                                    </div>
                                    {portal.set_cost && (
                                        <div className="mt-1.5 flex items-center gap-1 border-t border-stone-700/30 pt-1.5 font-pixel text-[8px] text-stone-500">
                                            <Coins className="h-2.5 w-2.5 text-yellow-500" />
                                            Setting costs {portal.set_cost.toLocaleString()}g
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

function StoragePanel({
    storage,
    storageUsed,
    storageCapacity,
    loading,
    storageAction,
    storageQty,
    storageItemName,
    onSetStorageAction,
    onSetStorageQty,
    onSetStorageItemName,
    onDeposit,
    onWithdraw,
}: {
    storage: StorageItem[];
    storageUsed: number;
    storageCapacity: number;
    loading: boolean;
    storageAction: { type: "deposit" | "withdraw"; item?: string } | null;
    storageQty: number;
    storageItemName: string;
    onSetStorageAction: (action: { type: "deposit" | "withdraw"; item?: string } | null) => void;
    onSetStorageQty: (qty: number) => void;
    onSetStorageItemName: (name: string) => void;
    onDeposit: () => void;
    onWithdraw: (itemName: string) => void;
}) {
    return (
        <div className="rounded-lg border border-stone-700/50 bg-stone-900/50 p-4">
            <div className="mb-4 flex items-center justify-between">
                <h2 className="font-pixel text-sm text-amber-200">
                    <Package className="mr-2 inline h-4 w-4" />
                    Home Storage
                </h2>
                <span className="font-pixel text-[10px] text-stone-500">
                    {storageUsed}/{storageCapacity} items
                </span>
            </div>

            {/* Deposit form */}
            <div className="mb-4 rounded-md border border-stone-700/30 bg-stone-800/30 p-3">
                <div className="font-pixel text-[10px] text-stone-400 mb-2">Deposit Item</div>
                <div className="flex gap-2">
                    <input
                        type="text"
                        placeholder="Item name"
                        value={storageItemName}
                        onChange={(e) => onSetStorageItemName(e.target.value)}
                        className="flex-1 rounded border border-stone-600 bg-stone-800 px-2 py-1 font-pixel text-xs text-stone-200 placeholder-stone-600 focus:border-amber-500 focus:outline-none"
                    />
                    <input
                        type="number"
                        min={1}
                        value={storageQty}
                        onChange={(e) =>
                            onSetStorageQty(Math.max(1, parseInt(e.target.value) || 1))
                        }
                        className="w-16 rounded border border-stone-600 bg-stone-800 px-2 py-1 font-pixel text-xs text-stone-200 focus:border-amber-500 focus:outline-none"
                    />
                    <button
                        onClick={onDeposit}
                        disabled={loading || !storageItemName}
                        className="flex items-center gap-1 rounded border border-green-600/40 bg-green-900/30 px-2 py-1 font-pixel text-[10px] text-green-300 hover:bg-green-800/30 disabled:opacity-40"
                    >
                        <ArrowDownToLine className="h-3 w-3" /> Store
                    </button>
                </div>
            </div>

            {/* Stored items */}
            {storage.length === 0 ? (
                <div className="py-8 text-center">
                    <Package className="mx-auto h-8 w-8 text-stone-700" />
                    <p className="mt-2 font-pixel text-xs text-stone-600">Storage is empty</p>
                </div>
            ) : (
                <div className="space-y-1">
                    {storage.map((item) => (
                        <div
                            key={item.item_name}
                            className="flex items-center justify-between rounded-md border border-stone-700/30 bg-stone-800/20 px-3 py-2"
                        >
                            <div>
                                <span className="font-pixel text-xs text-stone-300">
                                    {item.item_name}
                                </span>
                                <span className="ml-2 font-pixel text-[10px] text-stone-500">
                                    x{item.quantity}
                                </span>
                            </div>
                            <button
                                onClick={() => onWithdraw(item.item_name)}
                                disabled={loading}
                                className="flex items-center gap-1 rounded border border-blue-600/40 bg-blue-900/30 px-2 py-0.5 font-pixel text-[9px] text-blue-300 hover:bg-blue-800/30 disabled:opacity-40"
                            >
                                <ArrowUpFromLine className="h-3 w-3" /> Take
                            </button>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

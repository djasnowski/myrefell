import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    AlertTriangle,
    ArrowLeft,
    Calendar,
    Castle,
    Flag,
    Hammer,
    Heart,
    Package,
    Shield,
    Skull,
    Sword,
    Swords,
    Target,
    Users,
    XCircle,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface SiegeTarget {
    id: number;
    name: string;
    type: string;
}

interface Army {
    id: number;
    name: string;
    commander_name: string;
    status: string;
    morale: number;
    supplies: number;
    total_troops: number;
    total_attack: number;
    total_defense: number;
    units: Record<string, number>;
}

interface WarInfo {
    id: number;
    name: string;
    status: string;
}

interface SiegeLogEntry {
    day: number;
    events: string[];
    fortification_damage: number;
    supplies_consumed: number;
}

interface Siege {
    id: number;
    status: string;
    target: SiegeTarget;
    fortification_level: number;
    garrison_strength: number;
    garrison_morale: number;
    supplies_remaining: number;
    days_besieged: number;
    has_breach: boolean;
    siege_equipment: Record<string, number>;
    siege_log: SiegeLogEntry[];
    assault_difficulty: number;
    can_assault: boolean;
    is_starving: boolean;
    is_active: boolean;
    is_ended: boolean;
    started_at: string | null;
    ended_at: string | null;
}

interface PageProps {
    siege: Siege;
    attacking_army: Army | null;
    war: WarInfo | null;
    can_control: boolean;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Warfare", href: "#" },
    { title: "Wars", href: "/warfare/wars" },
    { title: "Siege", href: "#" },
];

const statusColors: Record<string, { bg: string; text: string; label: string }> = {
    active: { bg: "bg-amber-900/30", text: "text-amber-400", label: "Active" },
    assault: { bg: "bg-red-900/30", text: "text-red-400", label: "Assault in Progress" },
    breached: { bg: "bg-orange-900/30", text: "text-orange-400", label: "Walls Breached" },
    captured: { bg: "bg-green-900/30", text: "text-green-400", label: "Captured" },
    lifted: { bg: "bg-stone-900/30", text: "text-stone-400", label: "Siege Lifted" },
    abandoned: { bg: "bg-stone-900/30", text: "text-stone-500", label: "Abandoned" },
};

const targetTypeIcons: Record<string, typeof Castle> = {
    castle: Castle,
    town: Flag,
    village: Users,
};

const equipmentLabels: Record<string, { name: string; damage: number }> = {
    battering_ram: { name: "Battering Ram", damage: 2 },
    trebuchet: { name: "Trebuchet", damage: 5 },
    catapult: { name: "Catapult", damage: 3 },
    siege_tower: { name: "Siege Tower", damage: 1 },
    sappers: { name: "Sappers", damage: 4 },
};

const unitTypeLabels: Record<string, string> = {
    levy: "Levy",
    militia: "Militia",
    men_at_arms: "Men-at-Arms",
    knights: "Knights",
    archers: "Archers",
    crossbowmen: "Crossbowmen",
    cavalry: "Cavalry",
    siege_engineers: "Siege Eng.",
};

export default function SiegeShow() {
    const { siege, attacking_army, war, can_control } = usePage<PageProps>().props;

    const [isAssaulting, setIsAssaulting] = useState(false);
    const [isLifting, setIsLifting] = useState(false);
    const [isBuilding, setIsBuilding] = useState(false);
    const [selectedEquipment, setSelectedEquipment] = useState("battering_ram");
    const [equipmentCount, setEquipmentCount] = useState(1);
    const [showBuildForm, setShowBuildForm] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const status = statusColors[siege.status] || statusColors.active;
    const TargetIcon = targetTypeIcons[siege.target.type] || Castle;

    const launchAssault = async () => {
        if (!can_control || !siege.can_assault) return;

        setIsAssaulting(true);
        setError(null);

        try {
            const response = await fetch(`/warfare/sieges/${siege.id}/assault`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
            });

            const data = await response.json();
            if (response.ok) {
                setSuccess("Assault completed!");
                router.reload();
            } else {
                setError(data.message || "Failed to launch assault");
            }
        } catch {
            setError("Failed to launch assault");
        } finally {
            setIsAssaulting(false);
        }
    };

    const liftSiege = async () => {
        if (!can_control) return;

        setIsLifting(true);
        setError(null);

        try {
            const response = await fetch(`/warfare/sieges/${siege.id}/lift`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
            });

            const data = await response.json();
            if (response.ok) {
                router.visit("/warfare/armies");
            } else {
                setError(data.message || "Failed to lift siege");
            }
        } catch {
            setError("Failed to lift siege");
        } finally {
            setIsLifting(false);
        }
    };

    const buildEquipment = async () => {
        if (!can_control) return;

        setIsBuilding(true);
        setError(null);

        try {
            const response = await fetch(`/warfare/sieges/${siege.id}/build-equipment`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify({
                    equipment: selectedEquipment,
                    count: equipmentCount,
                }),
            });

            const data = await response.json();
            if (response.ok) {
                setSuccess(
                    `Built ${equipmentCount} ${equipmentLabels[selectedEquipment]?.name || selectedEquipment}`,
                );
                setShowBuildForm(false);
                router.reload();
            } else {
                setError(data.message || "Failed to build equipment");
            }
        } catch {
            setError("Failed to build equipment");
        } finally {
            setIsBuilding(false);
        }
    };

    const renderProgressBar = (value: number, maxValue: number, colorClass: string) => {
        const percentage = Math.min(100, Math.max(0, (value / maxValue) * 100));
        return (
            <div className="h-4 w-full overflow-hidden rounded bg-stone-800">
                <div
                    className={`h-full transition-all duration-300 ${colorClass}`}
                    style={{ width: `${percentage}%` }}
                />
            </div>
        );
    };

    const getProgressColor = (value: number) => {
        if (value >= 70) return "bg-green-500";
        if (value >= 40) return "bg-yellow-500";
        return "bg-red-500";
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Siege of ${siege.target.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-y-auto p-4">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div>
                        <div className="mb-2 flex items-center gap-2">
                            <Link
                                href="/warfare/wars"
                                className="flex items-center gap-1 font-pixel text-xs text-stone-400 transition hover:text-stone-300"
                            >
                                <ArrowLeft className="h-3 w-3" />
                                Back to Wars
                            </Link>
                        </div>
                        <div className="flex items-center gap-3">
                            <Target className="h-8 w-8 text-amber-400" />
                            <div>
                                <h1 className="font-pixel text-2xl text-amber-400">
                                    Siege of {siege.target.name}
                                </h1>
                                <p className="font-pixel text-sm text-stone-400">
                                    Day {siege.days_besieged} |{" "}
                                    {siege.target.type.charAt(0).toUpperCase() +
                                        siege.target.type.slice(1)}
                                </p>
                            </div>
                        </div>
                    </div>
                    <span
                        className={`rounded px-3 py-1.5 font-pixel text-xs ${status.bg} ${status.text}`}
                    >
                        {status.label}
                    </span>
                </div>

                {/* Messages */}
                {error && (
                    <div className="rounded-lg border border-red-500/50 bg-red-900/30 p-3 font-pixel text-sm text-red-300">
                        {error}
                    </div>
                )}
                {success && (
                    <div className="rounded-lg border border-green-500/50 bg-green-900/30 p-3 font-pixel text-sm text-green-300">
                        {success}
                    </div>
                )}

                {/* War Link */}
                {war && (
                    <div className="rounded-lg border border-stone-600/50 bg-stone-800/30 p-3">
                        <div className="flex items-center gap-2 font-pixel text-xs">
                            <Swords className="h-4 w-4 text-red-400" />
                            <span className="text-stone-400">Part of:</span>
                            <span className="text-white">{war.name}</span>
                            <span className="rounded bg-stone-700/50 px-2 py-0.5 text-[10px] capitalize text-stone-400">
                                {war.status.replace("_", " ")}
                            </span>
                        </div>
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Left Column - Siege Status */}
                    <div className="space-y-4">
                        {/* Target Status */}
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                            <div className="mb-4 flex items-center gap-2">
                                <TargetIcon className="h-5 w-5 text-amber-400" />
                                <h2 className="font-pixel text-lg text-amber-300">Target Status</h2>
                            </div>

                            {/* Fortification */}
                            <div className="mb-4">
                                <div className="mb-1 flex items-center justify-between font-pixel text-xs">
                                    <span className="flex items-center gap-1 text-stone-400">
                                        <Shield className="h-3 w-3" />
                                        Fortification
                                    </span>
                                    <span
                                        className={
                                            siege.fortification_level <= 30
                                                ? "text-red-400"
                                                : siege.fortification_level <= 60
                                                  ? "text-yellow-400"
                                                  : "text-green-400"
                                        }
                                    >
                                        {siege.fortification_level}%
                                    </span>
                                </div>
                                {renderProgressBar(
                                    siege.fortification_level,
                                    100,
                                    getProgressColor(siege.fortification_level),
                                )}
                                {siege.has_breach && (
                                    <div className="mt-1 flex items-center gap-1 font-pixel text-[10px] text-orange-400">
                                        <AlertTriangle className="h-3 w-3" />
                                        Walls Breached!
                                    </div>
                                )}
                            </div>

                            {/* Garrison Supplies */}
                            <div className="mb-4">
                                <div className="mb-1 flex items-center justify-between font-pixel text-xs">
                                    <span className="flex items-center gap-1 text-stone-400">
                                        <Package className="h-3 w-3" />
                                        Garrison Supplies
                                    </span>
                                    <span
                                        className={
                                            siege.supplies_remaining <= 20
                                                ? "text-red-400"
                                                : siege.supplies_remaining <= 50
                                                  ? "text-yellow-400"
                                                  : "text-green-400"
                                        }
                                    >
                                        {siege.supplies_remaining}%
                                    </span>
                                </div>
                                {renderProgressBar(
                                    siege.supplies_remaining,
                                    100,
                                    getProgressColor(siege.supplies_remaining),
                                )}
                                {siege.is_starving && (
                                    <div className="mt-1 flex items-center gap-1 font-pixel text-[10px] text-red-400">
                                        <Skull className="h-3 w-3" />
                                        Garrison is starving!
                                    </div>
                                )}
                            </div>

                            {/* Garrison Morale */}
                            <div className="mb-4">
                                <div className="mb-1 flex items-center justify-between font-pixel text-xs">
                                    <span className="flex items-center gap-1 text-stone-400">
                                        <Heart className="h-3 w-3" />
                                        Garrison Morale
                                    </span>
                                    <span
                                        className={
                                            siege.garrison_morale <= 30
                                                ? "text-red-400"
                                                : siege.garrison_morale <= 60
                                                  ? "text-yellow-400"
                                                  : "text-green-400"
                                        }
                                    >
                                        {siege.garrison_morale}%
                                    </span>
                                </div>
                                {renderProgressBar(
                                    siege.garrison_morale,
                                    100,
                                    getProgressColor(siege.garrison_morale),
                                )}
                            </div>

                            {/* Garrison Strength */}
                            <div className="rounded-lg bg-stone-900/50 p-3">
                                <div className="flex items-center justify-between font-pixel text-sm">
                                    <span className="flex items-center gap-2 text-stone-400">
                                        <Users className="h-4 w-4" />
                                        Garrison Strength
                                    </span>
                                    <span className="text-white">
                                        {siege.garrison_strength} defenders
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Siege Equipment */}
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                            <div className="mb-4 flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Hammer className="h-5 w-5 text-amber-400" />
                                    <h2 className="font-pixel text-lg text-amber-300">
                                        Siege Equipment
                                    </h2>
                                </div>
                                {can_control && siege.is_active && (
                                    <button
                                        onClick={() => setShowBuildForm(!showBuildForm)}
                                        className="rounded border border-amber-600/50 bg-amber-900/20 px-3 py-1 font-pixel text-xs text-amber-300 transition hover:bg-amber-900/40"
                                    >
                                        {showBuildForm ? "Cancel" : "Build More"}
                                    </button>
                                )}
                            </div>

                            {/* Current Equipment */}
                            {Object.keys(siege.siege_equipment).length > 0 ? (
                                <div className="mb-4 space-y-2">
                                    {Object.entries(siege.siege_equipment).map(([type, count]) => (
                                        <div
                                            key={type}
                                            className="flex items-center justify-between rounded-lg bg-stone-900/50 px-3 py-2"
                                        >
                                            <span className="font-pixel text-sm text-white">
                                                {equipmentLabels[type]?.name || type}
                                            </span>
                                            <div className="flex items-center gap-2">
                                                <span className="font-pixel text-sm text-amber-300">
                                                    x{count}
                                                </span>
                                                <span className="font-pixel text-[10px] text-stone-500">
                                                    ({equipmentLabels[type]?.damage * count}{" "}
                                                    dmg/day)
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="mb-4 rounded-lg bg-stone-900/50 p-3 text-center font-pixel text-xs text-stone-500">
                                    No siege equipment built yet
                                </div>
                            )}

                            {/* Build Form */}
                            {showBuildForm && (
                                <div className="rounded-lg border border-amber-500/30 bg-amber-900/20 p-3">
                                    <div className="mb-3">
                                        <label className="mb-1 block font-pixel text-xs text-stone-400">
                                            Equipment Type
                                        </label>
                                        <select
                                            value={selectedEquipment}
                                            onChange={(e) => setSelectedEquipment(e.target.value)}
                                            className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-amber-500 focus:outline-none"
                                        >
                                            {Object.entries(equipmentLabels).map(([key, info]) => (
                                                <option key={key} value={key}>
                                                    {info.name} (+{info.damage} dmg/day)
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div className="mb-3">
                                        <label className="mb-1 block font-pixel text-xs text-stone-400">
                                            Quantity
                                        </label>
                                        <input
                                            type="number"
                                            value={equipmentCount}
                                            onChange={(e) =>
                                                setEquipmentCount(
                                                    Math.max(
                                                        1,
                                                        Math.min(5, parseInt(e.target.value) || 1),
                                                    ),
                                                )
                                            }
                                            min={1}
                                            max={5}
                                            className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-amber-500 focus:outline-none"
                                        />
                                    </div>

                                    <button
                                        onClick={buildEquipment}
                                        disabled={isBuilding}
                                        className="w-full rounded bg-amber-600 py-2 font-pixel text-sm text-white transition hover:bg-amber-500 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {isBuilding ? "Building..." : "Build Equipment"}
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Right Column - Army & Actions */}
                    <div className="space-y-4">
                        {/* Attacking Army */}
                        {attacking_army && (
                            <div className="rounded-xl border-2 border-red-600/50 bg-red-900/20 p-4">
                                <div className="mb-4 flex items-center gap-2">
                                    <Swords className="h-5 w-5 text-red-400" />
                                    <h2 className="font-pixel text-lg text-red-300">
                                        Attacking Army
                                    </h2>
                                </div>

                                <div className="mb-3 rounded-lg bg-stone-900/50 p-3">
                                    <div className="mb-2 flex items-center justify-between">
                                        <span className="font-pixel text-base text-white">
                                            {attacking_army.name}
                                        </span>
                                        <span className="font-pixel text-xs capitalize text-stone-400">
                                            {attacking_army.status}
                                        </span>
                                    </div>
                                    <div className="font-pixel text-xs text-stone-400">
                                        Commander:{" "}
                                        <span className="text-white">
                                            {attacking_army.commander_name}
                                        </span>
                                    </div>
                                </div>

                                {/* Army Stats */}
                                <div className="mb-3 grid grid-cols-3 gap-2">
                                    <div className="rounded-lg bg-stone-900/30 p-2 text-center">
                                        <div className="font-pixel text-[10px] text-stone-400">
                                            Soldiers
                                        </div>
                                        <div className="font-pixel text-sm text-white">
                                            {attacking_army.total_troops}
                                        </div>
                                    </div>
                                    <div className="rounded-lg bg-stone-900/30 p-2 text-center">
                                        <div className="flex items-center justify-center gap-1">
                                            <Sword className="h-3 w-3 text-red-400" />
                                            <span className="font-pixel text-[10px] text-stone-400">
                                                Attack
                                            </span>
                                        </div>
                                        <div className="font-pixel text-sm text-red-300">
                                            {attacking_army.total_attack}
                                        </div>
                                    </div>
                                    <div className="rounded-lg bg-stone-900/30 p-2 text-center">
                                        <div className="flex items-center justify-center gap-1">
                                            <Shield className="h-3 w-3 text-blue-400" />
                                            <span className="font-pixel text-[10px] text-stone-400">
                                                Defense
                                            </span>
                                        </div>
                                        <div className="font-pixel text-sm text-blue-300">
                                            {attacking_army.total_defense}
                                        </div>
                                    </div>
                                </div>

                                {/* Unit Composition */}
                                {Object.keys(attacking_army.units).length > 0 && (
                                    <div className="rounded-lg bg-stone-900/30 p-2">
                                        <div className="mb-1 font-pixel text-[10px] text-stone-400">
                                            Unit Composition:
                                        </div>
                                        <div className="flex flex-wrap gap-1">
                                            {Object.entries(attacking_army.units).map(
                                                ([type, count]) => (
                                                    <span
                                                        key={type}
                                                        className="rounded bg-stone-700/50 px-2 py-0.5 font-pixel text-[10px] text-stone-300"
                                                    >
                                                        {unitTypeLabels[type] || type}: {count}
                                                    </span>
                                                ),
                                            )}
                                        </div>
                                    </div>
                                )}

                                {/* Army Resources */}
                                <div className="mt-3 grid grid-cols-2 gap-2 font-pixel text-xs">
                                    <div className="flex items-center gap-1">
                                        <Heart className="h-3 w-3 text-red-400" />
                                        <span className="text-stone-400">Morale:</span>
                                        <span
                                            className={
                                                attacking_army.morale >= 70
                                                    ? "text-green-400"
                                                    : attacking_army.morale >= 40
                                                      ? "text-yellow-400"
                                                      : "text-red-400"
                                            }
                                        >
                                            {attacking_army.morale}%
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <Package className="h-3 w-3 text-amber-400" />
                                        <span className="text-stone-400">Supplies:</span>
                                        <span
                                            className={
                                                attacking_army.supplies >= 20
                                                    ? "text-green-400"
                                                    : attacking_army.supplies >= 10
                                                      ? "text-yellow-400"
                                                      : "text-red-400"
                                            }
                                        >
                                            {attacking_army.supplies} days
                                        </span>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Action Buttons */}
                        {can_control && siege.is_active && (
                            <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                                <h2 className="mb-4 font-pixel text-lg text-stone-300">Actions</h2>

                                {/* Assault Button */}
                                <button
                                    onClick={launchAssault}
                                    disabled={!siege.can_assault || isAssaulting}
                                    className="mb-3 flex w-full items-center justify-center gap-2 rounded border-2 border-red-600/50 bg-red-900/30 px-4 py-3 font-pixel text-sm text-red-300 transition hover:bg-red-900/50 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <Sword className="h-5 w-5" />
                                    {isAssaulting
                                        ? "Launching Assault..."
                                        : `Launch Assault (Difficulty: ${siege.assault_difficulty}%)`}
                                </button>

                                {!siege.can_assault && (
                                    <p className="mb-3 font-pixel text-[10px] text-stone-500">
                                        Cannot assault: Fortifications are too strong. Wait for a
                                        breach or weaken the walls with siege equipment.
                                    </p>
                                )}

                                {/* Continue Siege Info */}
                                <div className="mb-3 rounded-lg bg-stone-900/50 p-3">
                                    <div className="flex items-center gap-2 font-pixel text-xs text-stone-400">
                                        <Calendar className="h-3 w-3" />
                                        Continue the siege to wear down the garrison
                                    </div>
                                    <p className="mt-1 font-pixel text-[10px] text-stone-500">
                                        Siege equipment damages fortifications daily. Low supplies
                                        and morale may force surrender.
                                    </p>
                                </div>

                                {/* Lift Siege Button */}
                                <button
                                    onClick={liftSiege}
                                    disabled={isLifting}
                                    className="flex w-full items-center justify-center gap-2 rounded border border-stone-600/50 bg-stone-800/30 px-4 py-2 font-pixel text-xs text-stone-400 transition hover:bg-stone-700/30 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <XCircle className="h-4 w-4" />
                                    {isLifting ? "Lifting Siege..." : "Lift Siege (Withdraw)"}
                                </button>
                            </div>
                        )}

                        {/* Siege Ended Notice */}
                        {siege.is_ended && (
                            <div
                                className={`rounded-xl border-2 p-4 ${
                                    siege.status === "captured"
                                        ? "border-green-600/50 bg-green-900/20"
                                        : "border-stone-600/50 bg-stone-800/30"
                                }`}
                            >
                                <div className="text-center">
                                    {siege.status === "captured" ? (
                                        <>
                                            <Target className="mx-auto mb-2 h-12 w-12 text-green-400" />
                                            <h3 className="font-pixel text-lg text-green-300">
                                                Victory!
                                            </h3>
                                            <p className="font-pixel text-sm text-stone-400">
                                                {siege.target.name} has been captured.
                                            </p>
                                        </>
                                    ) : (
                                        <>
                                            <XCircle className="mx-auto mb-2 h-12 w-12 text-stone-400" />
                                            <h3 className="font-pixel text-lg text-stone-300">
                                                Siege Ended
                                            </h3>
                                            <p className="font-pixel text-sm text-stone-400">
                                                The siege was{" "}
                                                {siege.status === "lifted" ? "lifted" : "abandoned"}
                                                .
                                            </p>
                                        </>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Siege Log */}
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                            <h2 className="mb-4 font-pixel text-lg text-stone-300">Siege Log</h2>

                            {siege.siege_log.length > 0 ? (
                                <div className="max-h-64 space-y-2 overflow-y-auto">
                                    {siege.siege_log
                                        .slice()
                                        .reverse()
                                        .map((entry, index) => (
                                            <div
                                                key={index}
                                                className="rounded-lg bg-stone-900/50 p-2"
                                            >
                                                <div className="mb-1 font-pixel text-xs text-amber-400">
                                                    Day {entry.day}
                                                </div>
                                                {entry.events.map((event, eventIndex) => (
                                                    <div
                                                        key={eventIndex}
                                                        className="font-pixel text-[10px] text-stone-300"
                                                    >
                                                        {event}
                                                    </div>
                                                ))}
                                                <div className="mt-1 flex gap-3 font-pixel text-[10px] text-stone-500">
                                                    {entry.fortification_damage > 0 && (
                                                        <span>
                                                            Fortification -
                                                            {entry.fortification_damage}%
                                                        </span>
                                                    )}
                                                    {entry.supplies_consumed > 0 && (
                                                        <span>
                                                            Supplies -{entry.supplies_consumed}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                </div>
                            ) : (
                                <div className="rounded-lg bg-stone-900/50 p-3 text-center font-pixel text-xs text-stone-500">
                                    Siege just began. No events recorded yet.
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

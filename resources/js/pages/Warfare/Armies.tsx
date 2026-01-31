import { Head, router, usePage } from "@inertiajs/react";
import {
    Coins,
    MapPin,
    Package,
    Plus,
    Shield,
    Sword,
    Swords,
    Users,
    XCircle,
    Flag,
    Heart,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Location {
    type: string;
    id: number;
    name: string;
}

interface Commander {
    id: number;
    name: string;
}

interface ArmyUnit {
    id: number;
    unit_type: string;
    count: number;
    max_count: number;
    attack: number;
    defense: number;
    status: string;
    total_attack: number;
    total_defense: number;
}

interface Army {
    id: number;
    name: string;
    status: string;
    morale: number;
    supplies: number;
    daily_supply_cost: number;
    gold_upkeep: number;
    total_troops: number;
    total_attack: number;
    total_defense: number;
    commander: Commander | null;
    location: Location;
    units: ArmyUnit[];
    composition: Record<string, number>;
    mustered_at: string | null;
}

interface MercenaryCompany {
    id: number;
    name: string;
    reputation: string;
    specialization: string;
    hire_cost: number;
    daily_cost: number;
    soldier_count: number;
    total_attack: number;
    total_defense: number;
}

interface UnitTypeInfo {
    name: string;
    description: string;
    stats: {
        attack: number;
        defense: number;
        upkeep: number;
        morale_bonus: number;
    };
}

interface PageProps {
    active_armies: Army[];
    disbanded_armies: Army[];
    mercenary_companies: MercenaryCompany[];
    current_location: Location;
    army_creation_cost: number;
    unit_types: Record<string, UnitTypeInfo>;
    recruitment_costs: Record<string, number>;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Warfare", href: "#" },
    { title: "Armies", href: "/warfare/armies" },
];

const statusColors: Record<string, { bg: string; text: string; label: string }> = {
    mustering: { bg: "bg-blue-900/30", text: "text-blue-400", label: "Mustering" },
    marching: { bg: "bg-amber-900/30", text: "text-amber-400", label: "Marching" },
    encamped: { bg: "bg-green-900/30", text: "text-green-400", label: "Encamped" },
    besieging: { bg: "bg-purple-900/30", text: "text-purple-400", label: "Besieging" },
    in_battle: { bg: "bg-red-900/30", text: "text-red-400", label: "In Battle" },
    disbanded: { bg: "bg-stone-900/30", text: "text-stone-400", label: "Disbanded" },
};

const reputationColors: Record<string, string> = {
    unknown: "text-stone-400",
    poor: "text-red-400",
    average: "text-yellow-400",
    good: "text-green-400",
    legendary: "text-purple-400",
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

export default function Armies() {
    const {
        active_armies,
        disbanded_armies,
        mercenary_companies,
        current_location,
        army_creation_cost,
    } = usePage<PageProps>().props;

    const [showCreateForm, setShowCreateForm] = useState(false);
    const [formData, setFormData] = useState({
        name: "",
    });
    const [isCreating, setIsCreating] = useState(false);
    const [isDisbanding, setIsDisbanding] = useState<number | null>(null);
    const [isHiring, setIsHiring] = useState<number | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const createArmy = async () => {
        if (!formData.name.trim()) {
            setError("Please enter an army name.");
            return;
        }

        setIsCreating(true);
        setError(null);

        try {
            const response = await fetch("/warfare/armies", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify(formData),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                setShowCreateForm(false);
                setFormData({ name: "" });
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError("Failed to raise army");
        } finally {
            setIsCreating(false);
        }
    };

    const disbandArmy = async (armyId: number) => {
        setIsDisbanding(armyId);
        setError(null);

        try {
            const response = await fetch(`/warfare/armies/${armyId}/disband`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError("Failed to disband army");
        } finally {
            setIsDisbanding(null);
        }
    };

    const hireMercenary = async (companyId: number) => {
        setIsHiring(companyId);
        setError(null);

        try {
            const response = await fetch(`/warfare/mercenaries/${companyId}/hire`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify({ contract_days: 30 }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError("Failed to hire mercenary company");
        } finally {
            setIsHiring(null);
        }
    };

    const renderArmyCard = (army: Army, showActions: boolean = true) => {
        const status = statusColors[army.status] || statusColors.encamped;
        const isActive = army.status !== "disbanded";
        const canDisband = isActive && army.status !== "in_battle";

        return (
            <div
                key={army.id}
                className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4"
            >
                {/* Header */}
                <div className="mb-3 flex items-start justify-between">
                    <div className="flex items-center gap-2">
                        <Swords className="h-5 w-5 text-red-400" />
                        <h3 className="font-pixel text-base text-white">{army.name}</h3>
                    </div>
                    <span
                        className={`rounded px-2 py-1 font-pixel text-[10px] ${status.bg} ${status.text}`}
                    >
                        {status.label}
                    </span>
                </div>

                {/* Commander & Location */}
                <div className="mb-3 rounded-lg bg-stone-900/50 p-2">
                    <div className="mb-1 flex items-center gap-2 font-pixel text-xs">
                        <Flag className="h-3 w-3 text-amber-400" />
                        <span className="text-stone-400">Commander:</span>
                        <span className="text-white">{army.commander?.name ?? "None"}</span>
                    </div>
                    <div className="flex items-center gap-2 font-pixel text-xs">
                        <MapPin className="h-3 w-3 text-green-400" />
                        <span className="text-stone-400">Location:</span>
                        <span className="text-white">{army.location.name}</span>
                    </div>
                </div>

                {/* Stats Row */}
                <div className="mb-3 grid grid-cols-3 gap-2 font-pixel text-xs">
                    <div className="flex items-center gap-1">
                        <Heart className="h-3 w-3 text-red-400" />
                        <span className="text-stone-400">Morale:</span>
                        <span
                            className={
                                army.morale >= 70
                                    ? "text-green-400"
                                    : army.morale >= 40
                                      ? "text-yellow-400"
                                      : "text-red-400"
                            }
                        >
                            {army.morale}%
                        </span>
                    </div>
                    <div className="flex items-center gap-1">
                        <Package className="h-3 w-3 text-amber-400" />
                        <span className="text-stone-400">Supplies:</span>
                        <span
                            className={
                                army.supplies >= 20
                                    ? "text-green-400"
                                    : army.supplies >= 10
                                      ? "text-yellow-400"
                                      : "text-red-400"
                            }
                        >
                            {army.supplies}d
                        </span>
                    </div>
                    <div className="flex items-center gap-1">
                        <Coins className="h-3 w-3 text-yellow-400" />
                        <span className="text-stone-400">Upkeep:</span>
                        <span className="text-yellow-300">{army.gold_upkeep}g/d</span>
                    </div>
                </div>

                {/* Unit Composition */}
                {army.units && army.units.length > 0 && (
                    <div className="mb-3 rounded-lg bg-stone-900/30 p-2">
                        <div className="mb-1 flex items-center gap-1 font-pixel text-[10px] text-stone-400">
                            <Users className="h-3 w-3" />
                            <span>Unit Composition:</span>
                        </div>
                        <div className="flex flex-wrap gap-1">
                            {army.units.map((unit) => (
                                <span
                                    key={unit.id}
                                    className="rounded bg-stone-700/50 px-2 py-0.5 font-pixel text-[10px] text-stone-300"
                                >
                                    {unitTypeLabels[unit.unit_type] || unit.unit_type}: {unit.count}
                                </span>
                            ))}
                        </div>
                    </div>
                )}

                {/* Combat Stats */}
                <div className="mb-3 grid grid-cols-3 gap-2 rounded-lg bg-stone-900/30 p-2">
                    <div className="text-center">
                        <div className="font-pixel text-[10px] text-stone-400">Soldiers</div>
                        <div className="font-pixel text-sm text-white">{army.total_troops}</div>
                    </div>
                    <div className="text-center">
                        <div className="flex items-center justify-center gap-1">
                            <Sword className="h-3 w-3 text-red-400" />
                            <span className="font-pixel text-[10px] text-stone-400">Attack</span>
                        </div>
                        <div className="font-pixel text-sm text-red-300">{army.total_attack}</div>
                    </div>
                    <div className="text-center">
                        <div className="flex items-center justify-center gap-1">
                            <Shield className="h-3 w-3 text-blue-400" />
                            <span className="font-pixel text-[10px] text-stone-400">Defense</span>
                        </div>
                        <div className="font-pixel text-sm text-blue-300">{army.total_defense}</div>
                    </div>
                </div>

                {/* Actions */}
                {showActions && canDisband && (
                    <div className="flex gap-2">
                        <button
                            onClick={() => disbandArmy(army.id)}
                            disabled={isDisbanding === army.id}
                            className="flex flex-1 items-center justify-center gap-1 rounded border border-red-600/50 bg-red-900/20 px-3 py-1.5 font-pixel text-xs text-red-300 transition hover:bg-red-900/40 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <XCircle className="h-3 w-3" />
                            {isDisbanding === army.id ? "Disbanding..." : "Disband"}
                        </button>
                    </div>
                )}
            </div>
        );
    };

    const renderMercenaryCard = (company: MercenaryCompany) => {
        const repColor = reputationColors[company.reputation] || "text-stone-400";

        return (
            <div
                key={company.id}
                className="rounded-xl border-2 border-amber-600/30 bg-amber-900/10 p-4"
            >
                {/* Header */}
                <div className="mb-3 flex items-start justify-between">
                    <div className="flex items-center gap-2">
                        <Flag className="h-5 w-5 text-amber-400" />
                        <h3 className="font-pixel text-base text-white">{company.name}</h3>
                    </div>
                    <span
                        className={`rounded px-2 py-1 font-pixel text-[10px] capitalize ${repColor}`}
                    >
                        {company.reputation}
                    </span>
                </div>

                {/* Info */}
                <div className="mb-3 rounded-lg bg-stone-900/50 p-2">
                    <div className="mb-1 font-pixel text-xs">
                        <span className="text-stone-400">Specialization: </span>
                        <span className="capitalize text-white">{company.specialization}</span>
                    </div>
                    <div className="font-pixel text-xs">
                        <span className="text-stone-400">Soldiers: </span>
                        <span className="text-white">{company.soldier_count}</span>
                    </div>
                </div>

                {/* Combat Stats */}
                <div className="mb-3 grid grid-cols-2 gap-2">
                    <div className="flex items-center gap-1 font-pixel text-xs">
                        <Sword className="h-3 w-3 text-red-400" />
                        <span className="text-stone-400">Attack:</span>
                        <span className="text-red-300">{company.total_attack}</span>
                    </div>
                    <div className="flex items-center gap-1 font-pixel text-xs">
                        <Shield className="h-3 w-3 text-blue-400" />
                        <span className="text-stone-400">Defense:</span>
                        <span className="text-blue-300">{company.total_defense}</span>
                    </div>
                </div>

                {/* Cost */}
                <div className="mb-3 rounded-lg bg-stone-800/50 p-2 font-pixel text-xs">
                    <div className="flex justify-between">
                        <span className="text-stone-400">Hire Cost:</span>
                        <span className="text-yellow-300">{company.hire_cost}g</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-stone-400">Daily Cost:</span>
                        <span className="text-yellow-300">{company.daily_cost}g/day</span>
                    </div>
                </div>

                {/* Hire Button */}
                <button
                    onClick={() => hireMercenary(company.id)}
                    disabled={isHiring === company.id}
                    className="flex w-full items-center justify-center gap-1 rounded border border-amber-600/50 bg-amber-900/20 px-3 py-1.5 font-pixel text-xs text-amber-300 transition hover:bg-amber-900/40 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <Coins className="h-3 w-3" />
                    {isHiring === company.id ? "Hiring..." : `Hire (${company.hire_cost}g)`}
                </button>
            </div>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Military Forces" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-red-400">Military Forces</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            Manage your armies and hire mercenaries
                        </p>
                    </div>
                    <button
                        onClick={() => setShowCreateForm(!showCreateForm)}
                        className="flex items-center gap-2 rounded border-2 border-red-600/50 bg-red-900/20 px-4 py-2 font-pixel text-xs text-red-300 transition hover:bg-red-900/40"
                    >
                        <Plus className="h-4 w-4" />
                        {showCreateForm ? "Cancel" : "Raise Army"}
                    </button>
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

                {/* Create Form */}
                {showCreateForm && (
                    <div className="rounded-xl border-2 border-red-500/30 bg-red-900/20 p-4">
                        <h3 className="mb-4 font-pixel text-base text-red-300">Raise New Army</h3>

                        <div className="mb-3 rounded-lg bg-stone-800/50 p-2 font-pixel text-xs text-stone-400">
                            <MapPin className="mr-1 inline h-3 w-3" />
                            Mustering at:{" "}
                            <span className="text-white">{current_location.name}</span>
                        </div>

                        <div className="mb-4">
                            <label className="mb-1 block font-pixel text-xs text-stone-400">
                                Army Name
                            </label>
                            <input
                                type="text"
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                maxLength={100}
                                className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-red-500 focus:outline-none"
                                placeholder="e.g., Northern Host"
                            />
                        </div>

                        <div className="mb-4 rounded-lg bg-stone-800/50 p-3">
                            <div className="grid grid-cols-2 gap-2 font-pixel text-xs">
                                <div className="text-stone-400">Raise Cost:</div>
                                <div className="text-right text-yellow-300">
                                    {army_creation_cost}g
                                </div>
                                <div className="text-stone-400">Starting Morale:</div>
                                <div className="text-right text-white">100%</div>
                                <div className="text-stone-400">Starting Supplies:</div>
                                <div className="text-right text-white">30 days</div>
                            </div>
                        </div>

                        <p className="mb-4 font-pixel text-[10px] text-stone-500">
                            You will command the army. Recruit soldiers after raising.
                        </p>

                        <button
                            onClick={createArmy}
                            disabled={!formData.name.trim() || isCreating}
                            className="w-full rounded bg-red-600 py-2 font-pixel text-sm text-white transition hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {isCreating ? "Raising..." : `Raise Army (${army_creation_cost}g)`}
                        </button>
                    </div>
                )}

                {/* Active Armies */}
                {active_armies.length > 0 && (
                    <div>
                        <h2 className="mb-3 font-pixel text-lg text-red-300">Your Armies</h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {active_armies.map((army) => renderArmyCard(army))}
                        </div>
                    </div>
                )}

                {/* Mercenary Companies */}
                {mercenary_companies.length > 0 && (
                    <div>
                        <h2 className="mb-3 font-pixel text-lg text-amber-300">
                            Mercenary Companies Available
                        </h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {mercenary_companies.map((company) => renderMercenaryCard(company))}
                        </div>
                    </div>
                )}

                {/* Disbanded Armies */}
                {disbanded_armies.length > 0 && (
                    <div>
                        <h2 className="mb-3 font-pixel text-lg text-stone-400">Disbanded Armies</h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {disbanded_armies.map((army) => renderArmyCard(army, false))}
                        </div>
                    </div>
                )}

                {/* Empty State */}
                {active_armies.length === 0 &&
                    disbanded_armies.length === 0 &&
                    mercenary_companies.length === 0 && (
                        <div className="flex flex-1 items-center justify-center">
                            <div className="text-center">
                                <Swords className="mx-auto mb-3 h-16 w-16 text-stone-600" />
                                <p className="font-pixel text-base text-stone-500">No armies yet</p>
                                <p className="font-pixel text-xs text-stone-600">
                                    Raise your first army to begin your military campaign!
                                </p>
                            </div>
                        </div>
                    )}
            </div>
        </AppLayout>
    );
}

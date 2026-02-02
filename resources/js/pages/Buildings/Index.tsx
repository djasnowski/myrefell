import { Head, router, usePage } from "@inertiajs/react";
import {
    Building,
    Building2,
    Castle,
    Church,
    Coins,
    Factory,
    Hammer,
    Home,
    Loader2,
    Shield,
    Wrench,
    X,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { formatBonusLabel } from "@/lib/utils";
import type { BreadcrumbItem } from "@/types";

interface BuildingType {
    id: number;
    name: string;
    slug: string;
    description: string;
    category: string;
    construction_requirements: Record<string, number>;
    construction_days: number;
    construction_labor: number;
    maintenance_cost: number;
    capacity: number;
    bonuses: Record<string, number>;
    is_fortification: boolean;
}

interface BuildingData {
    id: number;
    name: string;
    type: {
        id: number;
        name: string;
        category: string;
        description: string;
        is_fortification: boolean;
        bonuses: Record<string, number> | null;
        maintenance_cost: number;
    };
    status: string;
    condition: number;
    construction_progress: number;
    needs_repair: boolean;
    is_operational: boolean;
    completed_at: string | null;
}

interface ProjectData {
    id: number;
    building: {
        id: number;
        name: string;
        type_name: string;
    };
    project_type: string;
    status: string;
    progress: number;
    labor_invested: number;
    labor_required: number;
    materials_invested: Record<string, number>;
    materials_required: Record<string, number>;
    days_remaining: number | null;
    started_at: string | null;
    manager: {
        id: number;
        username: string;
    } | null;
}

interface PageProps {
    location: {
        type: string;
        id: number;
        name: string;
    } | null;
    buildings: BuildingData[];
    projects: ProjectData[];
    available_types: BuildingType[];
    resources: Record<string, number>;
    can_build: boolean;
    player: {
        gold: number;
    };
    [key: string]: unknown;
}

const categoryIcons: Record<string, typeof Building> = {
    housing: Home,
    economic: Factory,
    military: Shield,
    religious: Church,
    infrastructure: Building2,
};

const categoryColors: Record<string, string> = {
    housing: "border-blue-500/50 bg-blue-900/20",
    economic: "border-amber-500/50 bg-amber-900/20",
    military: "border-red-500/50 bg-red-900/20",
    religious: "border-purple-500/50 bg-purple-900/20",
    infrastructure: "border-stone-500/50 bg-stone-700/20",
};

const statusColors: Record<string, string> = {
    planned: "text-stone-400 bg-stone-800/50",
    under_construction: "text-amber-300 bg-amber-800/50",
    operational: "text-green-300 bg-green-800/50",
    damaged: "text-red-300 bg-red-800/50",
    destroyed: "text-red-500 bg-red-900/50",
    abandoned: "text-stone-500 bg-stone-800/50",
};

function ProgressBar({
    value,
    max = 100,
    color = "amber",
}: {
    value: number;
    max?: number;
    color?: string;
}) {
    const percent = Math.min(100, Math.max(0, (value / max) * 100));
    const colorClasses: Record<string, string> = {
        amber: "bg-amber-500",
        green: "bg-green-500",
        red: "bg-red-500",
        blue: "bg-blue-500",
    };

    return (
        <div className="h-2 w-full rounded-full bg-stone-700">
            <div
                className={`h-2 rounded-full ${colorClasses[color] || colorClasses.amber}`}
                style={{ width: `${percent}%` }}
            />
        </div>
    );
}

function ExistingBuildingCard({
    building,
    canBuild,
    onRepair,
    repairing,
}: {
    building: BuildingData;
    canBuild: boolean;
    onRepair: () => void;
    repairing: boolean;
}) {
    const Icon = categoryIcons[building.type.category] || Building;

    return (
        <div
            className={`rounded-xl border-2 ${categoryColors[building.type.category] || "border-stone-600/50 bg-stone-800/50"} p-4`}
        >
            <div className="mb-3 flex items-start justify-between">
                <div className="flex items-center gap-2">
                    <div className="rounded-lg bg-stone-800/50 p-2">
                        <Icon className="h-5 w-5 text-stone-300" />
                    </div>
                    <div>
                        <h3 className="font-pixel text-sm text-amber-300">{building.name}</h3>
                        <span className="font-pixel text-[10px] text-stone-400">
                            {building.type.category}
                        </span>
                    </div>
                </div>
                <div className={`rounded-lg px-2 py-1 ${statusColors[building.status]}`}>
                    <span className="font-pixel text-[10px]">
                        {building.status.replace("_", " ")}
                    </span>
                </div>
            </div>

            {/* Condition bar */}
            <div className="mb-3">
                <div className="mb-1 flex items-center justify-between">
                    <span className="font-pixel text-[10px] text-stone-400">Condition</span>
                    <span
                        className={`font-pixel text-xs ${building.condition >= 70 ? "text-green-300" : building.condition >= 40 ? "text-amber-300" : "text-red-300"}`}
                    >
                        {building.condition}%
                    </span>
                </div>
                <ProgressBar
                    value={building.condition}
                    color={
                        building.condition >= 70
                            ? "green"
                            : building.condition >= 40
                              ? "amber"
                              : "red"
                    }
                />
            </div>

            {/* Bonuses */}
            {building.type.bonuses && Object.keys(building.type.bonuses).length > 0 && (
                <div className="mb-3 rounded-lg bg-stone-800/50 p-2">
                    <span className="font-pixel text-[10px] text-stone-400">Bonuses</span>
                    <div className="mt-1 flex flex-wrap gap-2">
                        {Object.entries(building.type.bonuses).map(([key, value]) => (
                            <span key={key} className="font-pixel text-xs text-emerald-300">
                                +{value}% {formatBonusLabel(key)}
                            </span>
                        ))}
                    </div>
                </div>
            )}

            {/* Repair button */}
            {canBuild && building.needs_repair && (
                <button
                    onClick={onRepair}
                    disabled={repairing}
                    className="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-amber-600/50 bg-amber-900/30 px-4 py-2 font-pixel text-xs text-amber-300 transition hover:bg-amber-800/50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {repairing ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                        <>
                            <Wrench className="h-4 w-4" />
                            Repair
                        </>
                    )}
                </button>
            )}
        </div>
    );
}

function ProjectCard({
    project,
    canBuild,
    onCancel,
    cancelling,
}: {
    project: ProjectData;
    canBuild: boolean;
    onCancel: () => void;
    cancelling: boolean;
}) {
    return (
        <div className="rounded-xl border-2 border-amber-500/50 bg-amber-900/20 p-4">
            <div className="mb-3 flex items-start justify-between">
                <div className="flex items-center gap-2">
                    <div className="rounded-lg bg-stone-800/50 p-2">
                        <Hammer className="h-5 w-5 text-amber-300" />
                    </div>
                    <div>
                        <h3 className="font-pixel text-sm text-amber-300">
                            {project.building.name}
                        </h3>
                        <span className="font-pixel text-[10px] text-stone-400">
                            {project.project_type === "construction"
                                ? "New Construction"
                                : "Repair Work"}
                        </span>
                    </div>
                </div>
            </div>

            {/* Progress bar */}
            <div className="mb-3">
                <div className="mb-1 flex items-center justify-between">
                    <span className="font-pixel text-[10px] text-stone-400">Progress</span>
                    <span className="font-pixel text-xs text-amber-300">{project.progress}%</span>
                </div>
                <ProgressBar value={project.progress} color="amber" />
            </div>

            {/* Details */}
            <div className="mb-3 grid grid-cols-2 gap-2 rounded-lg bg-stone-800/50 p-2">
                <div>
                    <span className="font-pixel text-[10px] text-stone-400">Workers</span>
                    <p className="font-pixel text-xs text-stone-200">
                        {project.labor_invested}/{project.labor_required}
                    </p>
                </div>
                {project.days_remaining !== null && (
                    <div>
                        <span className="font-pixel text-[10px] text-stone-400">
                            Days Remaining
                        </span>
                        <p className="font-pixel text-xs text-stone-200">
                            ~{project.days_remaining}
                        </p>
                    </div>
                )}
            </div>

            {/* Cancel button */}
            {canBuild && (
                <button
                    onClick={onCancel}
                    disabled={cancelling}
                    className="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-red-600/50 bg-red-900/30 px-4 py-2 font-pixel text-xs text-red-300 transition hover:bg-red-800/50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {cancelling ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                        <>
                            <X className="h-4 w-4" />
                            Cancel Project
                        </>
                    )}
                </button>
            )}
        </div>
    );
}

function BuildingTypeCard({
    type,
    resources,
    canBuild,
    onBuild,
    building,
}: {
    type: BuildingType;
    resources: Record<string, number>;
    canBuild: boolean;
    onBuild: () => void;
    building: boolean;
}) {
    const Icon = categoryIcons[type.category] || Building;

    // Check if player has all required resources
    const canAfford = Object.entries(type.construction_requirements).every(
        ([resource, amount]) => (resources[resource] ?? 0) >= amount,
    );

    return (
        <div
            className={`rounded-xl border-2 ${categoryColors[type.category] || "border-stone-600/50 bg-stone-800/50"} p-4`}
        >
            <div className="mb-3 flex items-start justify-between">
                <div className="flex items-center gap-2">
                    <div className="rounded-lg bg-stone-800/50 p-2">
                        <Icon className="h-5 w-5 text-stone-300" />
                    </div>
                    <div>
                        <h3 className="font-pixel text-sm text-amber-300">{type.name}</h3>
                        <span className="font-pixel text-[10px] text-stone-400">
                            {type.category}
                        </span>
                    </div>
                </div>
                {type.is_fortification && (
                    <div className="rounded-lg bg-red-800/50 px-2 py-1">
                        <Castle className="h-4 w-4 text-red-300" />
                    </div>
                )}
            </div>

            <p className="mb-3 text-sm text-stone-300">{type.description}</p>

            {/* Requirements */}
            <div className="mb-3 rounded-lg bg-stone-800/50 p-2">
                <span className="font-pixel text-[10px] text-stone-400">Requirements</span>
                <div className="mt-1 grid grid-cols-2 gap-1">
                    {Object.entries(type.construction_requirements).map(([resource, amount]) => {
                        const hasEnough = (resources[resource] ?? 0) >= amount;
                        return (
                            <div key={resource} className="flex items-center gap-1">
                                <span
                                    className={`font-pixel text-xs ${hasEnough ? "text-stone-300" : "text-red-400"}`}
                                >
                                    {resource}: {amount}
                                </span>
                                {!hasEnough && (
                                    <span className="font-pixel text-[10px] text-red-400">
                                        ({resources[resource] ?? 0})
                                    </span>
                                )}
                            </div>
                        );
                    })}
                </div>
            </div>

            {/* Stats */}
            <div className="mb-3 grid grid-cols-2 gap-2 rounded-lg bg-stone-800/50 p-2">
                <div className="flex items-center gap-1">
                    <span className="font-pixel text-[10px] text-stone-400">Build Time:</span>
                    <span className="font-pixel text-xs text-stone-300">
                        {type.construction_days} days
                    </span>
                </div>
                <div className="flex items-center gap-1">
                    <span className="font-pixel text-[10px] text-stone-400">Upkeep:</span>
                    <span className="font-pixel text-xs text-amber-300">
                        {type.maintenance_cost}g/week
                    </span>
                </div>
            </div>

            {/* Bonuses */}
            {type.bonuses && Object.keys(type.bonuses).length > 0 && (
                <div className="mb-3 rounded-lg bg-emerald-900/30 p-2">
                    <span className="font-pixel text-[10px] text-emerald-400">Bonuses</span>
                    <div className="mt-1 flex flex-wrap gap-2">
                        {Object.entries(type.bonuses).map(([key, value]) => (
                            <span key={key} className="font-pixel text-xs text-emerald-300">
                                +{value}% {formatBonusLabel(key)}
                            </span>
                        ))}
                    </div>
                </div>
            )}

            <button
                onClick={onBuild}
                disabled={building || !canBuild || !canAfford}
                className="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-green-600 bg-green-900/30 px-4 py-2 font-pixel text-xs text-green-300 transition hover:bg-green-800/50 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {building ? <Loader2 className="h-4 w-4 animate-spin" /> : "Start Construction"}
            </button>
        </div>
    );
}

export default function BuildingsIndex() {
    const { location, buildings, projects, available_types, resources, can_build, player } =
        usePage<PageProps>().props;

    const [buildingLoading, setBuildingLoading] = useState<number | null>(null);
    const [repairingId, setRepairingId] = useState<number | null>(null);
    const [cancellingId, setCancellingId] = useState<number | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        ...(location ? [{ title: location.name, href: `/${location.type}s/${location.id}` }] : []),
        { title: "Buildings", href: "#" },
    ];

    const handleBuild = (typeId: number) => {
        if (!location) return;

        setBuildingLoading(typeId);
        router.post(
            "/buildings",
            {
                building_type_id: typeId,
                location_type: location.type,
                location_id: location.id,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setBuildingLoading(null),
            },
        );
    };

    const handleRepair = (buildingId: number) => {
        setRepairingId(buildingId);
        router.post(
            `/buildings/${buildingId}/repair`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setRepairingId(null),
            },
        );
    };

    const handleCancel = (projectId: number) => {
        setCancellingId(projectId);
        router.post(
            `/buildings/projects/${projectId}/cancel`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setCancellingId(null),
            },
        );
    };

    if (!location) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Buildings" />
                <div className="flex h-full flex-1 flex-col items-center justify-center p-4">
                    <Building className="h-16 w-16 text-stone-600" />
                    <p className="mt-3 font-pixel text-base text-stone-500">
                        You must be at a location to view buildings
                    </p>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Buildings - ${location.name}`} />
            <div className="flex h-full flex-1 flex-col overflow-hidden p-4">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Buildings</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            Manage infrastructure in {location.name}
                        </p>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2 rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2">
                            <Coins className="h-4 w-4 text-amber-400" />
                            <span className="font-pixel text-sm text-amber-300">
                                {player.gold}g
                            </span>
                        </div>
                    </div>
                </div>

                {/* Resources Summary */}
                {Object.keys(resources).length > 0 && (
                    <div className="mb-6 rounded-xl border-2 border-stone-600/50 bg-stone-800/50 p-4">
                        <h2 className="mb-2 font-pixel text-sm text-stone-300">Your Resources</h2>
                        <div className="flex flex-wrap gap-3">
                            {Object.entries(resources).map(([resource, amount]) => (
                                <div
                                    key={resource}
                                    className="flex items-center gap-1 rounded-lg bg-stone-700/50 px-2 py-1"
                                >
                                    <span className="font-pixel text-xs text-stone-400">
                                        {resource}:
                                    </span>
                                    <span className="font-pixel text-xs text-amber-300">
                                        {amount}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Existing Buildings */}
                {buildings.length > 0 && (
                    <div className="mb-6">
                        <h2 className="mb-3 font-pixel text-lg text-green-400">
                            Existing Buildings
                        </h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {buildings.map((building) => (
                                <ExistingBuildingCard
                                    key={building.id}
                                    building={building}
                                    canBuild={can_build}
                                    onRepair={() => handleRepair(building.id)}
                                    repairing={repairingId === building.id}
                                />
                            ))}
                        </div>
                    </div>
                )}

                {/* Under Construction */}
                {projects.length > 0 && (
                    <div className="mb-6">
                        <h2 className="mb-3 font-pixel text-lg text-amber-300">
                            Under Construction
                        </h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {projects.map((project) => (
                                <ProjectCard
                                    key={project.id}
                                    project={project}
                                    canBuild={can_build}
                                    onCancel={() => handleCancel(project.id)}
                                    cancelling={cancellingId === project.id}
                                />
                            ))}
                        </div>
                    </div>
                )}

                {/* Available to Build */}
                <div>
                    <h2 className="mb-3 font-pixel text-lg text-stone-300">Available to Build</h2>
                    {!can_build && (
                        <div className="mb-4 rounded-lg border-2 border-amber-600/50 bg-amber-900/20 p-3">
                            <p className="font-pixel text-xs text-amber-300">
                                Only the ruler of this location can start new construction projects.
                            </p>
                        </div>
                    )}
                    {available_types.length > 0 ? (
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {available_types.map((type) => (
                                <BuildingTypeCard
                                    key={type.id}
                                    type={type}
                                    resources={resources}
                                    canBuild={can_build}
                                    onBuild={() => handleBuild(type.id)}
                                    building={buildingLoading === type.id}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="flex flex-1 items-center justify-center py-12">
                            <div className="text-center">
                                <Building className="mx-auto h-16 w-16 text-stone-600" />
                                <p className="mt-3 font-pixel text-base text-stone-500">
                                    All building types have been constructed
                                </p>
                                <p className="font-pixel text-xs text-stone-600">
                                    This location has all available buildings.
                                </p>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

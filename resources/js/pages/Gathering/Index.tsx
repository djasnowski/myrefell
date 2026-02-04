import { Head, Link, usePage } from "@inertiajs/react";
import { Axe, Fish, Leaf, Pickaxe, Snowflake, Sun, TreeDeciduous, Zap } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import { locationPath } from "@/lib/utils";
import type { BreadcrumbItem } from "@/types";

interface Activity {
    id: string;
    name: string;
    skill: string;
    skill_level: number;
    energy_cost: number;
    base_xp: number;
    available_resources: number;
}

interface SeasonalData {
    season: "spring" | "summer" | "autumn" | "winter";
    modifier: number;
    description: string;
}

interface Location {
    type: string;
    id: number;
    name: string;
}

interface PageProps {
    activities: Activity[];
    player_energy: number;
    max_energy: number;
    seasonal: SeasonalData;
    location: Location;
    [key: string]: unknown;
}

const activityIcons: Record<string, typeof Pickaxe> = {
    mining: Pickaxe,
    fishing: Fish,
    woodcutting: Axe,
    herblore: Leaf,
};

const activityColors: Record<string, string> = {
    mining: "border-stone-500/50 bg-stone-700/30 hover:bg-stone-700/50",
    fishing: "border-blue-500/50 bg-blue-900/30 hover:bg-blue-900/50",
    woodcutting: "border-green-600/50 bg-green-900/30 hover:bg-green-900/50",
    herblore: "border-emerald-500/50 bg-emerald-900/30 hover:bg-emerald-900/50",
};

const activityDescriptions: Record<string, string> = {
    mining: "Extract ores and minerals from the earth",
    fishing: "Catch fish from nearby waters",
    woodcutting: "Chop trees for wood and lumber",
    herblore: "Gather herbs and plants for alchemy",
};

const seasonIcons: Record<string, typeof Sun> = {
    spring: Leaf,
    summer: Sun,
    autumn: TreeDeciduous,
    winter: Snowflake,
};

const seasonColors: Record<string, string> = {
    spring: "text-green-400 bg-green-900/30 border-green-600/50",
    summer: "text-yellow-400 bg-yellow-900/30 border-yellow-600/50",
    autumn: "text-orange-400 bg-orange-900/30 border-orange-600/50",
    winter: "text-blue-400 bg-blue-900/30 border-blue-600/50",
};

export default function GatheringIndex() {
    const { activities, player_energy, max_energy, seasonal, location } =
        usePage<PageProps>().props;
    const SeasonIcon = seasonIcons[seasonal.season] || Sun;
    const seasonColor = seasonColors[seasonal.season] || "text-stone-400";
    const modifierPercent = Math.round((seasonal.modifier - 1) * 100);
    const isBonus = modifierPercent > 0;
    const isPenalty = modifierPercent < 0;

    const baseLocationUrl = locationPath(location.type, location.id);
    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: location.name, href: baseLocationUrl },
        { title: "Gathering", href: `${baseLocationUrl}/gathering` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gathering" />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-6">
                    <h1 className="font-pixel text-2xl text-amber-400">Gathering</h1>
                    <p className="font-pixel text-sm text-stone-400">
                        Collect resources from the world around you
                    </p>
                </div>

                {/* Seasonal Effect Banner */}
                <div className={`mb-4 rounded-lg border p-3 ${seasonColor}`}>
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <SeasonIcon className="h-5 w-5" />
                            <span className="font-pixel text-sm capitalize">
                                {seasonal.season} Season
                            </span>
                        </div>
                        <div className="font-pixel text-sm">
                            {isBonus && (
                                <span className="text-green-400">
                                    +{modifierPercent}% bonus yield chance
                                </span>
                            )}
                            {isPenalty && (
                                <span className="text-red-400">
                                    {modifierPercent}% yield penalty
                                </span>
                            )}
                            {!isBonus && !isPenalty && (
                                <span className="text-stone-400">Normal yields</span>
                            )}
                        </div>
                    </div>
                    <p className="mt-1 font-pixel text-[10px] text-stone-400">
                        {seasonal.description}
                    </p>
                </div>

                {/* Energy Bar */}
                <div className="mb-6 rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                    <div className="mb-1 flex items-center justify-between font-pixel text-xs">
                        <span className="flex items-center gap-1 text-yellow-400">
                            <Zap className="h-3 w-3" />
                            Energy
                        </span>
                        <span className="text-stone-300">
                            {player_energy} / {max_energy}
                        </span>
                    </div>
                    <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                        <div
                            className="h-full bg-gradient-to-r from-yellow-600 to-yellow-400 transition-all"
                            style={{ width: `${(player_energy / max_energy) * 100}%` }}
                        />
                    </div>
                </div>

                {/* Activities Grid */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {activities.map((activity) => {
                        const Icon = activityIcons[activity.id] || Pickaxe;
                        const colorClass =
                            activityColors[activity.id] || "border-stone-600/50 bg-stone-800/50";
                        const description = activityDescriptions[activity.id] || "";
                        const canAfford = player_energy >= activity.energy_cost;

                        return (
                            <Link
                                key={activity.id}
                                href={`${baseLocationUrl}/gathering/${activity.id}`}
                                className={`rounded-xl border-2 p-5 transition ${colorClass}`}
                            >
                                <div className="mb-4 flex items-center gap-3">
                                    <div className="rounded-lg bg-stone-800/50 p-3">
                                        <Icon className="h-8 w-8 text-stone-300" />
                                    </div>
                                    <div>
                                        <h2 className="font-pixel text-lg text-amber-300">
                                            {activity.name}
                                        </h2>
                                        <p className="font-pixel text-[10px] capitalize text-stone-400">
                                            {activity.skill} Level {activity.skill_level}
                                        </p>
                                    </div>
                                </div>

                                <p className="mb-4 font-pixel text-xs text-stone-400">
                                    {description}
                                </p>

                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-1">
                                        <Zap
                                            className={`h-3 w-3 ${canAfford ? "text-yellow-400" : "text-red-400"}`}
                                        />
                                        <span
                                            className={`font-pixel text-xs ${canAfford ? "text-yellow-400" : "text-red-400"}`}
                                        >
                                            {activity.energy_cost} per action
                                        </span>
                                    </div>
                                    <span className="font-pixel text-[10px] text-stone-500">
                                        {activity.available_resources} resources
                                    </span>
                                </div>
                            </Link>
                        );
                    })}
                </div>

                {activities.length === 0 && (
                    <div className="flex flex-1 items-center justify-center">
                        <div className="text-center">
                            <div className="mb-3 text-6xl opacity-50">
                                <Pickaxe className="mx-auto h-16 w-16 text-stone-600" />
                            </div>
                            <p className="font-pixel text-base text-stone-500">
                                No gathering available here
                            </p>
                            <p className="font-pixel text-xs text-stone-600">
                                Travel to a different location
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

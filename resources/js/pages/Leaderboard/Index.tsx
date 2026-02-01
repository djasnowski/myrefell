import { Head, usePage } from "@inertiajs/react";
import type { LucideIcon } from "lucide-react";
import {
    Beef,
    BicepsFlexed,
    Crown,
    Crosshair,
    Fish,
    Footprints,
    Hammer,
    Hand,
    Heart,
    Leaf,
    Pickaxe,
    Scissors,
    Shield,
    Sparkles,
    Sword,
    TreeDeciduous,
    Trophy,
    Wheat,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface LeaderboardEntry {
    rank: number;
    username: string;
    level: number;
    xp: number;
}

interface PageProps {
    leaderboards: Record<string, LeaderboardEntry[]>;
    skills: string[];
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Leaderboard", href: "/leaderboard" },
];

const skillIcons: Record<string, LucideIcon> = {
    total: Crown,
    attack: Sword,
    strength: BicepsFlexed,
    defense: Shield,
    hitpoints: Heart,
    range: Crosshair,
    prayer: Sparkles,
    farming: Wheat,
    mining: Pickaxe,
    fishing: Fish,
    woodcutting: TreeDeciduous,
    cooking: Beef,
    smithing: Hammer,
    crafting: Scissors,
    thieving: Hand,
    herblore: Leaf,
    agility: Footprints,
};

const skillColors: Record<string, string> = {
    total: "text-amber-400",
    attack: "text-red-400",
    strength: "text-red-500",
    defense: "text-blue-400",
    hitpoints: "text-pink-400",
    range: "text-green-400",
    prayer: "text-yellow-300",
    farming: "text-lime-400",
    mining: "text-stone-400",
    fishing: "text-cyan-400",
    woodcutting: "text-emerald-500",
    cooking: "text-orange-400",
    smithing: "text-amber-500",
    crafting: "text-violet-400",
    thieving: "text-purple-400",
    herblore: "text-emerald-400",
    agility: "text-sky-400",
};

export default function LeaderboardIndex() {
    const { leaderboards, skills } = usePage<PageProps>().props;
    const [selectedSkill, setSelectedSkill] = useState<string>(skills[0] || "attack");

    const currentLeaderboard = leaderboards[selectedSkill] || [];
    const Icon = skillIcons[selectedSkill] || Sword;
    const iconColor = skillColors[selectedSkill] || "text-amber-400";

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Leaderboard" />

            <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                {/* Header */}
                <div className="mb-6">
                    <div className="flex items-center gap-3">
                        <Trophy className="h-8 w-8 text-amber-400" />
                        <div>
                            <h1 className="font-pixel text-2xl text-amber-400">Leaderboards</h1>
                            <p className="font-pixel text-sm text-stone-400">
                                Top 15 players for each skill
                            </p>
                        </div>
                    </div>
                </div>

                {/* Skill Tabs */}
                <div className="mb-6">
                    <div className="flex flex-wrap gap-2">
                        {skills.map((skill) => {
                            const SkillIcon = skillIcons[skill] || Sword;
                            const color = skillColors[skill] || "text-amber-400";
                            const isSelected = selectedSkill === skill;

                            return (
                                <button
                                    key={skill}
                                    onClick={() => setSelectedSkill(skill)}
                                    className={`flex items-center gap-2 px-4 py-2 rounded-lg font-pixel text-sm transition-colors capitalize ${
                                        isSelected
                                            ? "bg-amber-900/50 border-2 border-amber-500/50 text-amber-300"
                                            : "bg-stone-800/50 border-2 border-stone-700/50 text-stone-400 hover:text-stone-200 hover:bg-stone-800"
                                    }`}
                                >
                                    <SkillIcon className={`h-4 w-4 ${color}`} />
                                    {skill === "total" ? "Total" : skill}
                                </button>
                            );
                        })}
                    </div>
                </div>

                {/* Leaderboard Content */}
                <div className="rounded-xl border-2 border-stone-700/50 bg-stone-900/50 p-4">
                    <div className="flex items-center gap-2 mb-4 pb-3 border-b border-stone-700/50">
                        <Icon className={`h-6 w-6 ${iconColor}`} />
                        <h2 className="font-pixel text-lg capitalize text-stone-200">
                            {selectedSkill === "total" ? "Total Level" : selectedSkill} Rankings
                        </h2>
                    </div>

                    {currentLeaderboard.length === 0 ? (
                        <p className="text-center text-stone-500 py-12 font-pixel">
                            {selectedSkill === "total"
                                ? "No players have leveled up yet"
                                : "No players with 10+ XP yet"}
                        </p>
                    ) : (
                        <div className="space-y-2">
                            {currentLeaderboard.map((entry) => (
                                <div
                                    key={entry.rank}
                                    className={`flex items-center gap-4 px-4 py-3 rounded-lg border-2 transition-all ${
                                        entry.rank === 1
                                            ? "bg-amber-900/30 border-amber-500/50"
                                            : entry.rank === 2
                                              ? "bg-slate-700/30 border-slate-400/50"
                                              : entry.rank === 3
                                                ? "bg-orange-900/30 border-orange-600/50"
                                                : "bg-stone-800/30 border-stone-700/30"
                                    }`}
                                >
                                    {/* Rank */}
                                    <div className="w-10 text-center shrink-0">
                                        {entry.rank === 1 ? (
                                            <Trophy className="h-6 w-6 text-amber-400 mx-auto" />
                                        ) : entry.rank === 2 ? (
                                            <Trophy className="h-6 w-6 text-slate-300 mx-auto" />
                                        ) : entry.rank === 3 ? (
                                            <Trophy className="h-6 w-6 text-orange-500 mx-auto" />
                                        ) : (
                                            <span className="font-pixel text-sm text-stone-500">
                                                #{entry.rank}
                                            </span>
                                        )}
                                    </div>

                                    {/* Username */}
                                    <div className="flex-1 min-w-0">
                                        <p
                                            className={`font-pixel truncate ${
                                                entry.rank === 1
                                                    ? "text-amber-300"
                                                    : entry.rank === 2
                                                      ? "text-slate-200"
                                                      : entry.rank === 3
                                                        ? "text-orange-300"
                                                        : "text-stone-300"
                                            }`}
                                        >
                                            {entry.username}
                                        </p>
                                    </div>

                                    {/* Level & XP */}
                                    <div className="text-right shrink-0">
                                        <p
                                            className={`font-pixel text-sm ${
                                                entry.rank <= 3
                                                    ? "text-stone-100"
                                                    : "text-stone-300"
                                            }`}
                                        >
                                            {selectedSkill === "total" ? "Total Lv." : "Lv."}{" "}
                                            {entry.level}
                                        </p>
                                        <p className="font-pixel text-xs text-stone-500">
                                            {entry.xp.toLocaleString()} XP
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

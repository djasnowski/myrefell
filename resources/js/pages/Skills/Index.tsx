import { Head, usePage } from "@inertiajs/react";
import type { LucideIcon } from "lucide-react";
import {
    Beef,
    BicepsFlexed,
    Church,
    Crosshair,
    Fish,
    Hammer,
    Hand,
    Heart,
    Leaf,
    Pickaxe,
    Footprints,
    Scissors,
    Shield,
    Sparkles,
    Sword,
    TreeDeciduous,
    Trophy,
    Wheat,
} from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Skill {
    name: string;
    level: number;
    xp: number;
    xp_to_next: number;
    xp_progress: number;
    xp_for_current_level: number;
    xp_for_next_level: number;
    is_max: boolean;
}

interface SkillStats {
    total_level: number;
    total_xp: number;
    combat_level: number;
    max_total_level: number;
}

interface SkillGroups {
    combat: Skill[];
    gathering: Skill[];
    crafting: Skill[];
    support: Skill[];
}

interface PageProps {
    skills: SkillGroups;
    stats: SkillStats;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Skills", href: "/skills" },
];

const skillIcons: Record<string, LucideIcon> = {
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

const categoryConfig = {
    combat: {
        color: "border-red-500/50 bg-red-900/20",
        iconColor: "text-red-400",
        barColor: "from-red-600 to-red-400",
    },
    gathering: {
        color: "border-green-500/50 bg-green-900/20",
        iconColor: "text-green-400",
        barColor: "from-green-600 to-green-400",
    },
    crafting: {
        color: "border-blue-500/50 bg-blue-900/20",
        iconColor: "text-blue-400",
        barColor: "from-blue-600 to-blue-400",
    },
    support: {
        color: "border-purple-500/50 bg-purple-900/20",
        iconColor: "text-purple-400",
        barColor: "from-purple-600 to-purple-400",
    },
};

function SkillCard({ skill, category }: { skill: Skill; category: keyof typeof categoryConfig }) {
    const Icon = skillIcons[skill.name] || Sword;
    const config = categoryConfig[category];

    return (
        <div
            className={`flex items-center gap-3 rounded-lg border ${config.color} px-3 py-2 transition-all hover:brightness-110`}
        >
            {/* Icon */}
            <div className="rounded-md bg-stone-800/50 p-2">
                <Icon className={`h-5 w-5 ${config.iconColor}`} />
            </div>

            {/* Name & Progress */}
            <div className="min-w-0 flex-1">
                <div className="mb-1 flex items-center justify-between">
                    <h3 className="font-pixel text-xs capitalize text-amber-300">{skill.name}</h3>
                    <span className="font-pixel text-[10px] text-stone-500">
                        {skill.xp.toLocaleString()} xp
                    </span>
                </div>
                <div className="h-1.5 w-full overflow-hidden rounded-full bg-stone-700">
                    <div
                        className={`h-full bg-gradient-to-r ${config.barColor} transition-all duration-500`}
                        style={{ width: `${skill.xp_progress}%` }}
                    />
                </div>
            </div>

            {/* Level */}
            <div className="flex items-baseline gap-0.5 pl-2">
                {skill.is_max ? (
                    <div className="flex items-center gap-1">
                        <Trophy className="h-4 w-4 text-amber-400" />
                        <span className="font-pixel text-lg text-amber-400">99</span>
                    </div>
                ) : (
                    <>
                        <span className="font-pixel text-lg text-white">{skill.level}</span>
                        <span className="font-pixel text-[10px] text-stone-500">/99</span>
                    </>
                )}
            </div>
        </div>
    );
}

export default function SkillsIndex() {
    const { skills, stats } = usePage<PageProps>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Skills" />
            <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Skills</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            Track your progress and abilities
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2 text-center">
                            <div className="font-pixel text-[10px] text-stone-500">
                                Combat Level
                            </div>
                            <div className="font-pixel text-lg text-red-400">
                                {stats.combat_level}
                            </div>
                        </div>
                        <div className="rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2 text-center">
                            <div className="font-pixel text-[10px] text-stone-500">Total Level</div>
                            <div className="font-pixel text-lg text-amber-300">
                                {stats.total_level}
                                <span className="text-xs text-stone-500">
                                    /{stats.max_total_level}
                                </span>
                            </div>
                        </div>
                        <div className="rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2 text-center">
                            <div className="font-pixel text-[10px] text-stone-500">Total XP</div>
                            <div className="flex items-center gap-1 font-pixel text-lg text-emerald-300">
                                <Sparkles className="h-4 w-4" />
                                {stats.total_xp.toLocaleString()}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Skills Grid */}
                <div className="grid gap-2 md:grid-cols-3">
                    {skills.combat.map((skill) => (
                        <SkillCard key={skill.name} skill={skill} category="combat" />
                    ))}
                    {skills.gathering.map((skill) => (
                        <SkillCard key={skill.name} skill={skill} category="gathering" />
                    ))}
                    {skills.crafting.map((skill) => (
                        <SkillCard key={skill.name} skill={skill} category="crafting" />
                    ))}
                    {skills.support.map((skill) => (
                        <SkillCard key={skill.name} skill={skill} category="support" />
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}

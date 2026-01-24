import { Head, usePage } from '@inertiajs/react';
import type {
    LucideIcon} from 'lucide-react';
import {
    Beef,
    BicepsFlexed,
    Fish,
    Hammer,
    Pickaxe,
    Scissors,
    Shield,
    Sparkles,
    Sword,
    TreeDeciduous,
    Trophy,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

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
    highest_skill: { name: string; level: number } | null;
    max_total_level: number;
}

interface SkillGroups {
    combat: Skill[];
    gathering: Skill[];
    crafting: Skill[];
}

interface PageProps {
    skills: SkillGroups;
    stats: SkillStats;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Skills', href: '/skills' },
];

const skillIcons: Record<string, LucideIcon> = {
    attack: Sword,
    strength: BicepsFlexed,
    defense: Shield,
    mining: Pickaxe,
    fishing: Fish,
    woodcutting: TreeDeciduous,
    cooking: Beef,
    smithing: Hammer,
    crafting: Scissors,
};

const skillDescriptions: Record<string, string> = {
    attack: 'Determines accuracy in combat and unlocks better weapons',
    strength: 'Increases maximum damage dealt in combat',
    defense: 'Reduces damage taken and unlocks better armor',
    mining: 'Allows mining of better ores and increases yield',
    fishing: 'Catch rarer fish and find better fishing spots',
    woodcutting: 'Chop higher-tier trees and harvest faster',
    cooking: 'Prepare better food with higher healing',
    smithing: 'Forge better weapons and armor from ores',
    crafting: 'Create tools, jewelry, and other useful items',
};

const categoryConfig = {
    combat: {
        label: 'Combat Skills',
        color: 'border-red-500/50 bg-red-900/20',
        iconColor: 'text-red-400',
        barColor: 'from-red-600 to-red-400',
    },
    gathering: {
        label: 'Gathering Skills',
        color: 'border-green-500/50 bg-green-900/20',
        iconColor: 'text-green-400',
        barColor: 'from-green-600 to-green-400',
    },
    crafting: {
        label: 'Crafting Skills',
        color: 'border-blue-500/50 bg-blue-900/20',
        iconColor: 'text-blue-400',
        barColor: 'from-blue-600 to-blue-400',
    },
};

function SkillCard({ skill, category }: { skill: Skill; category: keyof typeof categoryConfig }) {
    const Icon = skillIcons[skill.name] || Sword;
    const config = categoryConfig[category];

    return (
        <div className={`rounded-xl border-2 ${config.color} p-4 transition-all hover:scale-[1.02]`}>
            {/* Header */}
            <div className="mb-3 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <div className="rounded-lg bg-stone-800/50 p-2.5">
                        <Icon className={`h-6 w-6 ${config.iconColor}`} />
                    </div>
                    <div>
                        <h3 className="font-pixel text-sm capitalize text-amber-300">{skill.name}</h3>
                        <p className="max-w-[180px] text-xs text-stone-400">{skillDescriptions[skill.name]}</p>
                    </div>
                </div>
                <div className="text-right">
                    <div className="font-pixel text-2xl text-white">{skill.level}</div>
                    <div className="font-pixel text-[10px] text-stone-500">/ 99</div>
                </div>
            </div>

            {/* XP Progress Bar */}
            <div className="mb-2">
                <div className="mb-1 flex items-center justify-between">
                    <span className="font-pixel text-[10px] text-stone-400">Experience</span>
                    <span className="font-pixel text-[10px] text-stone-400">{skill.xp_progress}%</span>
                </div>
                <div className="h-3 w-full overflow-hidden rounded-full bg-stone-700">
                    <div
                        className={`h-full bg-gradient-to-r ${config.barColor} transition-all duration-500`}
                        style={{ width: `${skill.xp_progress}%` }}
                    />
                </div>
            </div>

            {/* XP Stats */}
            <div className="flex items-center justify-between rounded-lg bg-stone-800/50 px-3 py-2">
                <div className="text-center">
                    <div className="font-pixel text-[10px] text-stone-500">Total XP</div>
                    <div className="font-pixel text-xs text-stone-300">{skill.xp.toLocaleString()}</div>
                </div>
                <div className="h-6 w-px bg-stone-600" />
                {skill.is_max ? (
                    <div className="flex items-center gap-1 text-center">
                        <Trophy className="h-4 w-4 text-amber-400" />
                        <span className="font-pixel text-xs text-amber-400">MAX</span>
                    </div>
                ) : (
                    <div className="text-center">
                        <div className="font-pixel text-[10px] text-stone-500">To Next Level</div>
                        <div className="font-pixel text-xs text-emerald-300">{skill.xp_to_next.toLocaleString()} XP</div>
                    </div>
                )}
            </div>
        </div>
    );
}

function SkillCategory({
    title,
    skills,
    category,
}: {
    title: string;
    skills: Skill[];
    category: keyof typeof categoryConfig;
}) {
    const config = categoryConfig[category];

    return (
        <div className="mb-6">
            <h2 className={`mb-3 font-pixel text-lg ${config.iconColor}`}>{title}</h2>
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {skills.map((skill) => (
                    <SkillCard key={skill.name} skill={skill} category={category} />
                ))}
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
                        <p className="font-pixel text-sm text-stone-400">Track your progress and abilities</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2 text-center">
                            <div className="font-pixel text-[10px] text-stone-500">Combat Level</div>
                            <div className="font-pixel text-lg text-red-400">{stats.combat_level}</div>
                        </div>
                        <div className="rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2 text-center">
                            <div className="font-pixel text-[10px] text-stone-500">Total Level</div>
                            <div className="font-pixel text-lg text-amber-300">
                                {stats.total_level}
                                <span className="text-xs text-stone-500">/{stats.max_total_level}</span>
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

                {/* Highest Skill Banner */}
                {stats.highest_skill && (
                    <div className="mb-6 flex items-center gap-3 rounded-lg border-2 border-amber-500/30 bg-amber-900/20 px-4 py-3">
                        <Trophy className="h-6 w-6 text-amber-400" />
                        <div>
                            <span className="font-pixel text-sm text-stone-400">Highest Skill: </span>
                            <span className="font-pixel text-sm capitalize text-amber-300">{stats.highest_skill.name}</span>
                            <span className="font-pixel text-sm text-stone-400"> at level </span>
                            <span className="font-pixel text-sm text-white">{stats.highest_skill.level}</span>
                        </div>
                    </div>
                )}

                {/* Skill Categories */}
                <SkillCategory title="Combat Skills" skills={skills.combat} category="combat" />
                <SkillCategory title="Gathering Skills" skills={skills.gathering} category="gathering" />
                <SkillCategory title="Crafting Skills" skills={skills.crafting} category="crafting" />
            </div>
        </AppLayout>
    );
}

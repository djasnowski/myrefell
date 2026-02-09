import { usePage } from "@inertiajs/react";
import type { LucideIcon } from "lucide-react";
import {
    Anvil,
    Beef,
    BicepsFlexed,
    Fish,
    Footprints,
    Hammer,
    Hand,
    Heart,
    Home,
    Leaf,
    Pickaxe,
    Scissors,
    Shield,
    Sparkles,
    Sword,
    Target,
    TreeDeciduous,
    Wheat,
    FlaskConical,
    Church,
    Crown,
    Scroll,
} from "lucide-react";
import { useSidebar } from "@/components/ui/sidebar";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/tooltip";

interface Skill {
    name: string;
    level: number;
    xp: number;
    xp_to_next: number;
    xp_progress: number;
}

interface BonusSource {
    source: string;
    type: "blessing" | "hq_prayer" | "belief" | "active_belief" | "potion" | "house";
    value: number;
    is_percent: boolean;
    effect: string;
    expires_at?: string;
    minutes_remaining?: number;
}

interface SkillBonus {
    flat_bonus: number;
    percent_bonus: number;
    sources: BonusSource[];
}

interface SidebarData {
    skills: Skill[];
    skill_bonuses: Record<string, SkillBonus>;
}

const skillOrder = [
    "attack",
    "strength",
    "defense",
    "hitpoints",
    "range",
    "prayer",
    "agility",
    "thieving",
    "farming",
    "herblore",
    "mining",
    "fishing",
    "woodcutting",
    "cooking",
    "smithing",
    "crafting",
    "construction",
];

const skillIcons: Record<string, LucideIcon> = {
    hitpoints: Heart,
    attack: Sword,
    strength: BicepsFlexed,
    defense: Shield,
    range: Target,
    prayer: Sparkles,
    agility: Footprints,
    thieving: Hand,
    farming: Wheat,
    herblore: Leaf,
    mining: Pickaxe,
    fishing: Fish,
    woodcutting: TreeDeciduous,
    cooking: Beef,
    smithing: Anvil,
    crafting: Scissors,
    construction: Hammer,
};

const sourceTypeIcons: Record<string, LucideIcon> = {
    blessing: Sparkles,
    hq_prayer: Church,
    belief: Scroll,
    active_belief: Scroll,
    potion: FlaskConical,
    house: Home,
};

const sourceTypeColors: Record<string, string> = {
    blessing: "text-yellow-400",
    hq_prayer: "text-purple-400",
    belief: "text-blue-400",
    active_belief: "text-orange-400",
    potion: "text-emerald-400",
    house: "text-amber-400",
};

function SkillBadge({ skill, bonus }: { skill: Skill; bonus?: SkillBonus }) {
    const Icon = skillIcons[skill.name] || Sword;
    const hasBonuses = bonus && bonus.sources.length > 0;
    const totalBonus = bonus ? bonus.flat_bonus + bonus.percent_bonus : 0;

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <div
                    className={`relative flex aspect-square cursor-default items-center justify-center overflow-hidden rounded-lg border bg-sidebar-accent/30 ${
                        hasBonuses
                            ? "border-amber-500/50 ring-1 ring-amber-500/30"
                            : "border-sidebar-border"
                    }`}
                >
                    <Icon className="h-6 w-6 text-sidebar-foreground/80" />
                    <span className="absolute right-1 top-0.5 font-pixel text-[10px] font-bold text-sidebar-primary">
                        {skill.level}
                    </span>
                    {/* Bonus indicator */}
                    {hasBonuses && (
                        <span className="absolute left-0.5 top-0.5 font-pixel text-[8px] text-amber-400">
                            +
                        </span>
                    )}
                    {/* XP Progress Bar */}
                    <div className="absolute inset-x-0 bottom-0 h-1 bg-sidebar-accent/50">
                        <div
                            className="h-full bg-gradient-to-r from-emerald-600 to-emerald-400"
                            style={{ width: `${skill.xp_progress}%` }}
                        />
                    </div>
                </div>
            </TooltipTrigger>
            <TooltipContent side="top" className="w-56 bg-stone-900 border-stone-700">
                <div className="font-pixel text-xs capitalize text-stone-100">{skill.name}</div>

                {/* XP Progress */}
                <div className="mt-1 h-1.5 w-full overflow-hidden rounded-sm bg-stone-700">
                    <div
                        className="h-full bg-gradient-to-r from-emerald-600 to-emerald-400"
                        style={{ width: `${skill.xp_progress}%` }}
                    />
                </div>
                <div className="mt-1 font-pixel text-[9px] text-stone-400">
                    {skill.xp_to_next.toLocaleString()} XP to {skill.level + 1}
                </div>

                {/* Active Bonuses */}
                {hasBonuses && bonus && (
                    <div className="mt-2 border-t border-stone-700 pt-2">
                        <div className="mb-1 flex items-center gap-1 font-pixel text-[9px] text-amber-400">
                            <FlaskConical className="h-3 w-3" />
                            Active Bonuses
                        </div>
                        <div className="space-y-1">
                            {bonus.sources.map((source, idx) => {
                                const SourceIcon = sourceTypeIcons[source.type] || Sparkles;
                                const colorClass =
                                    sourceTypeColors[source.type] || "text-stone-400";
                                const valuePrefix = source.value > 0 ? "+" : "";
                                const valueSuffix = source.is_percent ? "%" : "";

                                return (
                                    <div
                                        key={idx}
                                        className="flex items-start gap-1.5 font-pixel text-[9px]"
                                    >
                                        <SourceIcon className={`h-3 w-3 shrink-0 ${colorClass}`} />
                                        <div className="flex-1 min-w-0">
                                            <div className="text-stone-200 truncate">
                                                {source.source}
                                            </div>
                                            <div
                                                className={`${source.value > 0 ? "text-green-400" : "text-red-400"}`}
                                            >
                                                {valuePrefix}
                                                {source.value}
                                                {valueSuffix}{" "}
                                                {source.effect
                                                    .toLowerCase()
                                                    .replace(source.source.toLowerCase(), "")
                                                    .trim()}
                                            </div>
                                            {source.minutes_remaining !== undefined && (
                                                <div className="text-stone-500">
                                                    {source.minutes_remaining}m remaining
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        {/* Total summary */}
                        {(bonus.flat_bonus !== 0 || bonus.percent_bonus !== 0) && (
                            <div className="mt-1.5 border-t border-stone-700 pt-1.5 font-pixel text-[9px]">
                                <span className="text-stone-400">Total: </span>
                                {bonus.flat_bonus !== 0 && (
                                    <span
                                        className={
                                            bonus.flat_bonus > 0 ? "text-green-400" : "text-red-400"
                                        }
                                    >
                                        {bonus.flat_bonus > 0 ? "+" : ""}
                                        {bonus.flat_bonus} flat
                                    </span>
                                )}
                                {bonus.flat_bonus !== 0 && bonus.percent_bonus !== 0 && (
                                    <span className="text-stone-500">, </span>
                                )}
                                {bonus.percent_bonus !== 0 && (
                                    <span
                                        className={
                                            bonus.percent_bonus > 0
                                                ? "text-green-400"
                                                : "text-red-400"
                                        }
                                    >
                                        {bonus.percent_bonus > 0 ? "+" : ""}
                                        {bonus.percent_bonus}%
                                    </span>
                                )}
                            </div>
                        )}
                    </div>
                )}
            </TooltipContent>
        </Tooltip>
    );
}

export function NavSkills() {
    const { sidebar } = usePage<{ sidebar: SidebarData | null }>().props;
    const { state } = useSidebar();

    if (!sidebar || !sidebar.skills || sidebar.skills.length === 0) return null;

    // When collapsed, show nothing (skills are too complex for icon-only view)
    if (state === "collapsed") {
        return null;
    }

    // Sort skills by defined order (combat skills first)
    const sortedSkills = [...sidebar.skills].sort(
        (a, b) => skillOrder.indexOf(a.name) - skillOrder.indexOf(b.name),
    );

    const skillBonuses = sidebar.skill_bonuses || {};

    return (
        <div className="grid grid-cols-4 gap-1.5">
            {sortedSkills.map((skill) => (
                <SkillBadge key={skill.name} skill={skill} bonus={skillBonuses[skill.name]} />
            ))}
        </div>
    );
}

import { useSidebar } from '@/components/ui/sidebar';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { usePage } from '@inertiajs/react';
import {
    Beef,
    Dumbbell,
    Fish,
    Hammer,
    LucideIcon,
    Pickaxe,
    Scissors,
    Shield,
    Sword,
    TreeDeciduous,
} from 'lucide-react';

interface Skill {
    name: string;
    level: number;
    xp: number;
    xp_to_next: number;
    xp_progress: number;
}

interface SidebarData {
    skills: Skill[];
}

const skillOrder = ['attack', 'strength', 'defense', 'mining', 'fishing', 'woodcutting', 'cooking', 'smithing', 'crafting'];

const skillIcons: Record<string, LucideIcon> = {
    attack: Sword,
    strength: Dumbbell,
    defense: Shield,
    mining: Pickaxe,
    fishing: Fish,
    woodcutting: TreeDeciduous,
    cooking: Beef,
    smithing: Hammer,
    crafting: Scissors,
};

function SkillBadge({ skill }: { skill: Skill }) {
    const Icon = skillIcons[skill.name] || Sword;

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <div className="relative flex aspect-square cursor-default items-center justify-center overflow-hidden rounded-lg border border-sidebar-border bg-sidebar-accent/30">
                    <Icon className="h-6 w-6 text-sidebar-foreground/80" />
                    <span className="absolute right-1 top-0.5 font-pixel text-[10px] font-bold text-sidebar-primary">
                        {skill.level}
                    </span>
                    {/* XP Progress Bar */}
                    <div className="absolute inset-x-0 bottom-0 h-1 bg-sidebar-accent/50">
                        <div
                            className="h-full bg-gradient-to-r from-emerald-600 to-emerald-400"
                            style={{ width: `${skill.xp_progress}%` }}
                        />
                    </div>
                </div>
            </TooltipTrigger>
            <TooltipContent side="top" className="w-40">
                <div className="font-pixel text-xs capitalize">{skill.name}</div>
                <div className="mt-1 h-1.5 w-full overflow-hidden rounded-sm bg-primary-foreground/20">
                    <div
                        className="h-full bg-gradient-to-r from-emerald-600 to-emerald-400"
                        style={{ width: `${skill.xp_progress}%` }}
                    />
                </div>
                <div className="mt-1 font-pixel text-[9px] opacity-70">
                    {skill.xp_to_next.toLocaleString()} XP to {skill.level + 1}
                </div>
            </TooltipContent>
        </Tooltip>
    );
}

export function NavSkills() {
    const { sidebar } = usePage<{ sidebar: SidebarData | null }>().props;
    const { state } = useSidebar();

    if (!sidebar || !sidebar.skills || sidebar.skills.length === 0) return null;

    // When collapsed, show nothing (skills are too complex for icon-only view)
    if (state === 'collapsed') {
        return null;
    }

    // Sort skills by defined order (combat skills first)
    const sortedSkills = [...sidebar.skills].sort(
        (a, b) => skillOrder.indexOf(a.name) - skillOrder.indexOf(b.name)
    );

    return (
        <div className="grid grid-cols-3 gap-1.5">
            {sortedSkills.map((skill) => (
                <SkillBadge key={skill.name} skill={skill} />
            ))}
        </div>
    );
}

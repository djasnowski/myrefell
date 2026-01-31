import { cn } from '@/lib/utils';

interface SkillCardProps {
    name: string;
    level: number;
    xp: number;
    progress: number;
    isCombat: boolean;
}

const skillIcons: Record<string, string> = {
    attack: 'crossed-swords',
    strength: 'muscle',
    defense: 'shield',
    hitpoints: 'heart',
    range: 'bow-arrow',
    farming: 'wheat',
    mining: 'pickaxe',
    fishing: 'fish',
    woodcutting: 'axe',
    cooking: 'cooking-pot',
    smithing: 'anvil',
    crafting: 'hammer',
    prayer: 'pray',
};

const skillColors: Record<string, { text: string; bg: string; bar: string }> = {
    attack: { text: 'text-red-400', bg: 'bg-red-900/30', bar: 'bg-red-500' },
    strength: { text: 'text-orange-400', bg: 'bg-orange-900/30', bar: 'bg-orange-500' },
    defense: { text: 'text-blue-400', bg: 'bg-blue-900/30', bar: 'bg-blue-500' },
    hitpoints: { text: 'text-pink-400', bg: 'bg-pink-900/30', bar: 'bg-pink-500' },
    range: { text: 'text-green-400', bg: 'bg-green-900/30', bar: 'bg-green-500' },
    farming: { text: 'text-lime-400', bg: 'bg-lime-900/30', bar: 'bg-lime-500' },
    mining: { text: 'text-stone-400', bg: 'bg-stone-700/30', bar: 'bg-stone-400' },
    fishing: { text: 'text-cyan-400', bg: 'bg-cyan-900/30', bar: 'bg-cyan-500' },
    woodcutting: { text: 'text-amber-400', bg: 'bg-amber-900/30', bar: 'bg-amber-500' },
    cooking: { text: 'text-rose-400', bg: 'bg-rose-900/30', bar: 'bg-rose-500' },
    smithing: { text: 'text-slate-400', bg: 'bg-slate-700/30', bar: 'bg-slate-400' },
    crafting: { text: 'text-yellow-400', bg: 'bg-yellow-900/30', bar: 'bg-yellow-500' },
    prayer: { text: 'text-purple-400', bg: 'bg-purple-900/30', bar: 'bg-purple-500' },
};

export function SkillCard({ name, level, xp, progress, isCombat }: SkillCardProps) {
    const colors = skillColors[name] || {
        text: 'text-stone-400',
        bg: 'bg-stone-800/30',
        bar: 'bg-stone-500',
    };

    const displayName = name.charAt(0).toUpperCase() + name.slice(1);

    return (
        <div
            className={cn(
                'rounded-lg border border-stone-800 p-3',
                colors.bg
            )}
        >
            <div className="flex items-center justify-between">
                <span className={cn('font-medium capitalize', colors.text)}>
                    {displayName}
                </span>
                <span className="rounded-md bg-stone-800 px-2 py-0.5 text-sm font-bold text-stone-100">
                    {level}
                </span>
            </div>
            <div className="mt-2">
                <div className="h-1.5 w-full overflow-hidden rounded-full bg-stone-800">
                    <div
                        className={cn('h-full rounded-full transition-all', colors.bar)}
                        style={{ width: `${Math.min(progress, 100)}%` }}
                    />
                </div>
                <div className="mt-1 flex justify-between text-xs text-stone-500">
                    <span>{xp.toLocaleString()} XP</span>
                    <span>{progress.toFixed(1)}%</span>
                </div>
            </div>
        </div>
    );
}

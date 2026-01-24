import { useSidebar } from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface SidebarData {
    player: {
        id: number;
        username: string;
        gender: 'male' | 'female';
        hp: number;
        max_hp: number;
        energy: number;
        max_energy: number;
        gold: number;
        combat_level: number;
        primary_title: string | null;
        title_tier: number | null;
    };
    energy_info: {
        current: number;
        max: number;
        at_max: boolean;
        regen_rate: number;
        seconds_until_next: number | null;
    };
}

function StatBar({ current, max, color }: { current: number; max: number; color: string }) {
    const percentage = Math.min((current / max) * 100, 100);
    return (
        <div className="h-2 w-full overflow-hidden rounded-sm bg-stone-700">
            <div className={`h-full transition-all duration-300 ${color}`} style={{ width: `${percentage}%` }} />
        </div>
    );
}

function EnergyTimer({ secondsUntilNext }: { secondsUntilNext: number | null }) {
    const [seconds, setSeconds] = useState(secondsUntilNext ?? 0);

    useEffect(() => {
        if (secondsUntilNext === null) {
            setSeconds(0);
            return;
        }
        setSeconds(secondsUntilNext);
        const interval = setInterval(() => {
            setSeconds((prev) => {
                if (prev <= 1) {
                    window.location.reload();
                    return 0;
                }
                return prev - 1;
            });
        }, 1000);
        return () => clearInterval(interval);
    }, [secondsUntilNext]);

    if (secondsUntilNext === null) return null;
    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;

    return (
        <span className="font-pixel text-[8px] text-stone-400">
            +1 in {minutes}:{secs.toString().padStart(2, '0')}
        </span>
    );
}

export function NavPlayerInfo() {
    const { sidebar } = usePage<{ sidebar: SidebarData | null }>().props;
    const { state } = useSidebar();

    if (!sidebar) return null;

    const { player, energy_info } = sidebar;

    const titleDisplay = player.primary_title
        ? player.primary_title.charAt(0).toUpperCase() + player.primary_title.slice(1)
        : 'Peasant';

    // Collapsed view - just show avatar/icon
    if (state === 'collapsed') {
        return (
            <div className="flex flex-col items-center gap-1">
                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-sidebar-primary/20 font-pixel text-xs text-sidebar-primary">
                    {player.combat_level}
                </div>
                {/* Mini HP bar */}
                <div className="h-1 w-6 overflow-hidden rounded-full bg-stone-700">
                    <div
                        className="h-full bg-red-500"
                        style={{ width: `${(player.hp / player.max_hp) * 100}%` }}
                    />
                </div>
                {/* Mini Energy bar */}
                <div className="h-1 w-6 overflow-hidden rounded-full bg-stone-700">
                    <div
                        className="h-full bg-yellow-500"
                        style={{ width: `${(energy_info.current / energy_info.max) * 100}%` }}
                    />
                </div>
            </div>
        );
    }

    return (
        <div className="rounded-lg border border-sidebar-border bg-sidebar-accent/50 p-3">
            {/* Player Name & Level */}
            <div className="mb-2 flex items-center justify-between">
                <div className="min-w-0 flex-1">
                    <div className="truncate font-pixel text-sm text-sidebar-accent-foreground">{player.username}</div>
                    <div className="font-pixel text-[10px] text-sidebar-foreground/60">{titleDisplay}</div>
                </div>
                <div className="ml-2 flex-shrink-0 rounded bg-sidebar-primary/20 px-2 py-0.5 font-pixel text-xs text-sidebar-primary">
                    Lv.{player.combat_level}
                </div>
            </div>

            {/* HP */}
            <div className="mb-1">
                <div className="mb-0.5 flex items-center justify-between">
                    <span className="flex items-center gap-1">
                        <span className="text-xs">❤️</span>
                        <span className="font-pixel text-[10px] text-sidebar-foreground/80">HP</span>
                    </span>
                    <span className="font-pixel text-[10px] text-sidebar-foreground/60">
                        {player.hp}/{player.max_hp}
                    </span>
                </div>
                <StatBar current={player.hp} max={player.max_hp} color="bg-gradient-to-r from-red-700 to-red-500" />
            </div>

            {/* Energy */}
            <div className="mb-2">
                <div className="mb-0.5 flex items-center justify-between">
                    <span className="flex items-center gap-1">
                        <span className="text-xs">⚡</span>
                        <span className="font-pixel text-[10px] text-sidebar-foreground/80">Energy</span>
                    </span>
                    <span className="font-pixel text-[10px] text-sidebar-foreground/60">
                        {energy_info.current}/{energy_info.max}
                    </span>
                </div>
                <StatBar current={energy_info.current} max={energy_info.max} color="bg-gradient-to-r from-yellow-600 to-yellow-400" />
                {!energy_info.at_max && (
                    <div className="mt-0.5 text-right">
                        <EnergyTimer secondsUntilNext={energy_info.seconds_until_next} />
                    </div>
                )}
            </div>

            {/* Gold */}
            <div className="flex items-center justify-center gap-1.5 rounded border border-amber-600/30 bg-amber-900/20 py-1.5">
                <span className="text-sm">🪙</span>
                <span className="font-pixel text-sm text-amber-300">{player.gold.toLocaleString()}</span>
            </div>
        </div>
    );
}

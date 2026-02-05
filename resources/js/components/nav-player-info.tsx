import { router, usePage } from "@inertiajs/react";
import * as LucideIcons from "lucide-react";
import { useEffect, useState } from "react";
import { useSidebar } from "@/components/ui/sidebar";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";

interface HpBonus {
    source: string;
    amount: number;
}

interface SidebarData {
    player: {
        id: number;
        username: string;
        gender: "male" | "female";
        hp: number;
        max_hp: number;
        base_max_hp: number;
        hp_bonuses: HpBonus[];
        energy: number;
        max_energy: number;
        gold: number;
        combat_level: number;
        primary_title: string | null;
        title_tier: number | null;
        social_class: string;
        role: {
            name: string;
            slug: string;
            icon: string | null;
            location_name: string;
            location_type: string;
            location_id: number;
            pending_count: number;
        } | null;
        job: {
            name: string;
            icon: string | null;
            wage: number;
        } | null;
    };
    energy_info: {
        current: number;
        max: number;
        at_max: boolean;
        regen_rate: number;
        regen_amount: number;
        base_regen_amount: number;
        regen_bonuses: { source: string; amount: string }[];
        seconds_until_next: number | null;
    };
}

function getIconComponent(
    iconName: string | null,
): React.ComponentType<{ className?: string }> | null {
    if (!iconName) return null;
    // Convert kebab-case to PascalCase (e.g., "chef-hat" -> "ChefHat")
    const pascalCase = iconName
        .split("-")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join("");
    return (
        (LucideIcons as Record<string, React.ComponentType<{ className?: string }>>)[pascalCase] ||
        null
    );
}

function StatBar({ current, max, color }: { current: number; max: number; color: string }) {
    const percentage = Math.min((current / max) * 100, 100);
    return (
        <div className="h-2 w-full overflow-hidden rounded-sm bg-stone-700">
            <div
                className={`h-full transition-all duration-300 ${color}`}
                style={{ width: `${percentage}%` }}
            />
        </div>
    );
}

function EnergyTimer({
    secondsUntilNext,
    regenAmount,
}: {
    secondsUntilNext: number | null;
    regenAmount: number;
}) {
    const [seconds, setSeconds] = useState(secondsUntilNext ?? 0);

    useEffect(() => {
        if (secondsUntilNext === null) {
            return;
        }
        const interval = setInterval(() => {
            setSeconds((prev) => {
                if (prev <= 1) {
                    // Just reset the timer - don't reload the page
                    // Energy will update on next user action
                    return secondsUntilNext ?? 5;
                }
                return prev - 1;
            });
        }, 1000);
        return () => clearInterval(interval);
    }, [secondsUntilNext]);

    if (secondsUntilNext === null) return null;
    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;

    const hasBonus = regenAmount > 10;

    return (
        <span className="font-pixel text-[8px] text-stone-400">
            <span className={hasBonus ? "text-green-400" : ""}>+{regenAmount}</span> in {minutes}:
            {secs.toString().padStart(2, "0")}
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
        : player.social_class.charAt(0).toUpperCase() + player.social_class.slice(1);

    // Collapsed view - just show avatar/icon
    if (state === "collapsed") {
        return (
            <div className="flex flex-col items-center gap-1">
                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-sidebar-primary/20 font-pixel text-xs text-sidebar-primary">
                    {player.combat_level}
                </div>
                {/* Mini HP bar */}
                <div className="h-1 w-6 overflow-hidden rounded-full bg-stone-700">
                    <div
                        className="h-full bg-red-500"
                        style={{
                            width: `${(player.hp / player.max_hp) * 100}%`,
                        }}
                    />
                </div>
                {/* Mini Energy bar */}
                <div className="h-1 w-6 overflow-hidden rounded-full bg-stone-700">
                    <div
                        className="h-full bg-yellow-500"
                        style={{
                            width: `${(energy_info.current / energy_info.max) * 100}%`,
                        }}
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
                    <div className="truncate font-pixel text-sm text-sidebar-accent-foreground">
                        {player.username}
                    </div>
                    <div className="font-pixel text-[10px] text-sidebar-foreground/60">
                        {titleDisplay}
                    </div>
                </div>
                <div className="ml-2 flex-shrink-0 rounded bg-sidebar-primary/20 px-2 py-0.5 font-pixel text-xs text-sidebar-primary">
                    Lv.{player.combat_level}
                </div>
            </div>

            {/* HP */}
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <div className="mb-1 cursor-help">
                            <div className="mb-0.5 flex items-center justify-between">
                                <span className="flex items-center gap-1">
                                    <span className="text-xs">❤️</span>
                                    <span className="font-pixel text-[10px] text-sidebar-foreground/80">
                                        HP
                                    </span>
                                </span>
                                <span className="font-pixel text-[10px] text-sidebar-foreground/60">
                                    {player.hp}/
                                    {player.hp_bonuses.length > 0 ? (
                                        <span className="text-green-400">{player.max_hp}</span>
                                    ) : (
                                        player.max_hp
                                    )}
                                    {player.hp_bonuses.length > 0 && (
                                        <span className="text-green-400 ml-0.5">
                                            (+{player.max_hp - player.base_max_hp})
                                        </span>
                                    )}
                                </span>
                            </div>
                            <StatBar
                                current={player.hp}
                                max={player.max_hp}
                                color="bg-gradient-to-r from-red-700 to-red-500"
                            />
                        </div>
                    </TooltipTrigger>
                    <TooltipContent side="right" className="bg-stone-900 border-stone-700">
                        <div className="font-pixel text-xs">
                            <div className="text-stone-300 mb-1">Max HP Breakdown</div>
                            <div className="text-stone-400">
                                Base (Hitpoints Lv): {player.base_max_hp}
                            </div>
                            {player.hp_bonuses.map((bonus, i) => (
                                <div key={i} className="text-green-400">
                                    {bonus.source}: +{bonus.amount}
                                </div>
                            ))}
                            <div className="border-t border-stone-700 mt-1 pt-1 text-stone-200">
                                Total: {player.max_hp}
                            </div>
                        </div>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>

            {/* Energy */}
            {energy_info.regen_bonuses.length > 0 ? (
                <TooltipProvider>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <div className="mb-2 cursor-help">
                                <div className="mb-0.5 flex items-center justify-between">
                                    <span className="flex items-center gap-1">
                                        <span className="text-xs">⚡</span>
                                        <span className="font-pixel text-[10px] text-sidebar-foreground/80">
                                            Energy
                                        </span>
                                    </span>
                                    <span className="font-pixel text-[10px] text-sidebar-foreground/60">
                                        {energy_info.current}/{energy_info.max}
                                    </span>
                                </div>
                                <StatBar
                                    current={energy_info.current}
                                    max={energy_info.max}
                                    color="bg-gradient-to-r from-yellow-600 to-yellow-400"
                                />
                                {!energy_info.at_max && (
                                    <div className="mt-0.5 text-right">
                                        <EnergyTimer
                                            key={energy_info.seconds_until_next}
                                            secondsUntilNext={energy_info.seconds_until_next}
                                            regenAmount={energy_info.regen_amount}
                                        />
                                    </div>
                                )}
                            </div>
                        </TooltipTrigger>
                        <TooltipContent side="right" className="bg-stone-900 border-stone-700">
                            <div className="font-pixel text-xs">
                                <div className="text-stone-300 mb-1">Energy Regen Bonuses</div>
                                <div className="text-stone-400">
                                    Base: +{energy_info.base_regen_amount}
                                </div>
                                {energy_info.regen_bonuses.map((bonus, i) => (
                                    <div key={i} className="text-green-400">
                                        {bonus.source}: {bonus.amount}
                                    </div>
                                ))}
                                <div className="border-t border-stone-700 mt-1 pt-1 text-stone-200">
                                    Total: +{energy_info.regen_amount}
                                </div>
                            </div>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            ) : (
                <div className="mb-2">
                    <div className="mb-0.5 flex items-center justify-between">
                        <span className="flex items-center gap-1">
                            <span className="text-xs">⚡</span>
                            <span className="font-pixel text-[10px] text-sidebar-foreground/80">
                                Energy
                            </span>
                        </span>
                        <span className="font-pixel text-[10px] text-sidebar-foreground/60">
                            {energy_info.current}/{energy_info.max}
                        </span>
                    </div>
                    <StatBar
                        current={energy_info.current}
                        max={energy_info.max}
                        color="bg-gradient-to-r from-yellow-600 to-yellow-400"
                    />
                    {!energy_info.at_max && (
                        <div className="mt-0.5 text-right">
                            <EnergyTimer
                                key={energy_info.seconds_until_next}
                                secondsUntilNext={energy_info.seconds_until_next}
                                regenAmount={energy_info.regen_amount}
                            />
                        </div>
                    )}
                </div>
            )}

            {/* Gold */}
            <div className="flex items-center justify-center gap-1.5 rounded border border-amber-600/30 bg-amber-900/20 py-1.5">
                <span className="text-sm">🪙</span>
                <span className="font-pixel text-sm text-amber-300">
                    {player.gold.toLocaleString()}
                </span>
            </div>

            {/* Role & Job */}
            {(player.role || player.job) && (
                <div className="mt-2 space-y-1">
                    {player.role &&
                        (() => {
                            const RoleIcon =
                                getIconComponent(player.role.icon) || LucideIcons.Crown;
                            return (
                                <div className="relative flex items-center gap-1.5 rounded border border-purple-600/30 bg-purple-900/20 px-2 py-1">
                                    <RoleIcon className="mr-1 h-5 w-5 flex-shrink-0 text-purple-400" />
                                    <div className="min-w-0 flex-1">
                                        <div className="truncate font-pixel text-[10px] text-purple-300">
                                            {player.role.name}
                                        </div>
                                        <div className="truncate font-pixel text-[8px] text-purple-400/60">
                                            {player.role.location_name}
                                        </div>
                                    </div>
                                    {player.role.pending_count > 0 && (
                                        <div className="absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 font-pixel text-[9px] text-white">
                                            {player.role.pending_count > 9
                                                ? "9+"
                                                : player.role.pending_count}
                                        </div>
                                    )}
                                </div>
                            );
                        })()}
                    {player.job &&
                        (() => {
                            const JobIcon = getIconComponent(player.job.icon) || LucideIcons.Hammer;
                            return (
                                <div className="flex items-center gap-1.5 rounded border border-blue-600/30 bg-blue-900/20 px-2 py-1">
                                    <JobIcon className="mr-1 h-5 w-5 flex-shrink-0 text-blue-400" />
                                    <div className="min-w-0 flex-1">
                                        <div className="truncate font-pixel text-[10px] text-blue-300">
                                            {player.job.name}
                                        </div>
                                        <div className="truncate font-pixel text-[8px] text-blue-400/60">
                                            {player.job.wage}g/work
                                        </div>
                                    </div>
                                </div>
                            );
                        })()}
                </div>
            )}
        </div>
    );
}

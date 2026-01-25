import { Crown } from "lucide-react"

import { cn } from "@/lib/utils"

interface LegitimacyBadgeProps {
    legitimacy: number;
    showLabel?: boolean;
    size?: 'sm' | 'md' | 'lg';
    className?: string;
}

/**
 * Get the legitimacy status label based on the score.
 * Mirrors the backend LegitimacyService thresholds.
 */
function getLegitimacyStatus(legitimacy: number): string {
    if (legitimacy >= 80) return 'Beloved';
    if (legitimacy >= 65) return 'Respected';
    if (legitimacy >= 50) return 'Accepted';
    if (legitimacy >= 35) return 'Questioned';
    if (legitimacy >= 20) return 'Unpopular';
    return 'Despised';
}

/**
 * Get color classes based on legitimacy score.
 */
function getLegitimacyColor(legitimacy: number): string {
    if (legitimacy >= 80) return 'bg-green-900/50 text-green-300 border-green-500/50';
    if (legitimacy >= 65) return 'bg-emerald-900/50 text-emerald-300 border-emerald-500/50';
    if (legitimacy >= 50) return 'bg-amber-900/50 text-amber-300 border-amber-500/50';
    if (legitimacy >= 35) return 'bg-orange-900/50 text-orange-300 border-orange-500/50';
    if (legitimacy >= 20) return 'bg-red-900/50 text-red-300 border-red-500/50';
    return 'bg-red-900/70 text-red-200 border-red-400/50';
}

export function LegitimacyBadge({
    legitimacy,
    showLabel = true,
    size = 'md',
    className
}: LegitimacyBadgeProps) {
    const status = getLegitimacyStatus(legitimacy);
    const colorClasses = getLegitimacyColor(legitimacy);

    const sizeClasses = {
        sm: 'text-[10px] px-1.5 py-0.5',
        md: 'text-xs px-2 py-1',
        lg: 'text-sm px-2.5 py-1.5',
    };

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded border font-pixel',
                colorClasses,
                sizeClasses[size],
                className
            )}
            title={`Legitimacy: ${legitimacy}% - ${status}`}
        >
            {showLabel ? (
                <>
                    <span>{status}</span>
                    <span className="opacity-75">({legitimacy}%)</span>
                </>
            ) : (
                <span>{legitimacy}%</span>
            )}
        </span>
    );
}

interface RulerDisplayProps {
    ruler: {
        id: number;
        username: string;
        legitimacy?: number;
        primary_title?: string | null;
    } | null | undefined;
    title: string;
    isCurrentUser?: boolean;
    className?: string;
}

export function RulerDisplay({
    ruler,
    title,
    isCurrentUser = false,
    className
}: RulerDisplayProps) {
    if (!ruler) {
        return (
            <div className={cn("rounded-lg border-2 border-stone-600/50 bg-stone-800/30 p-3", className)}>
                <div className="flex items-center gap-2 text-stone-400">
                    <Crown className="h-4 w-4" />
                    <span className="font-pixel text-sm">{title}</span>
                </div>
                <p className="mt-1 text-sm text-stone-500">Position vacant</p>
            </div>
        );
    }

    return (
        <div className={cn("rounded-lg border-2 border-amber-600/50 bg-amber-900/20 p-3", className)}>
            <div className="flex items-center gap-2">
                <Crown className="h-4 w-4 text-amber-400" />
                <span className="font-pixel text-sm text-amber-300">{title}</span>
                {isCurrentUser && (
                    <span className="rounded bg-green-900/50 px-1.5 py-0.5 text-[10px] text-green-300">
                        This is you!
                    </span>
                )}
            </div>
            <div className="mt-2 flex items-center justify-between gap-2">
                <div>
                    <p className="font-medium text-stone-200">{ruler.username}</p>
                    {ruler.primary_title && (
                        <p className="text-xs text-stone-400">{ruler.primary_title}</p>
                    )}
                </div>
                {ruler.legitimacy !== undefined && (
                    <LegitimacyBadge legitimacy={ruler.legitimacy} size="sm" />
                )}
            </div>
        </div>
    );
}

export { getLegitimacyStatus, getLegitimacyColor };

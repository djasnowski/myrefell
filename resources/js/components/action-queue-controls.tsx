import { Loader2, Repeat, Square } from "lucide-react";

interface ActionQueueControlsProps {
    isQueueActive: boolean;
    queueProgress: { completed: number; total: number };
    isActionLoading: boolean;
    cooldown: number;
    cooldownMs: number;
    onStart: (count: number) => void;
    onCancel: () => void;
    onSingle: () => void;
    disabled?: boolean;
    actionLabel?: string;
    activeLabel?: string;
    totalXp?: number;
    buttonClassName?: string;
    disabledClassName?: string;
}

const REPEAT_OPTIONS = [5, 10, 25] as const;

export function ActionQueueControls({
    isQueueActive,
    queueProgress,
    isActionLoading,
    cooldown,
    cooldownMs,
    onStart,
    onCancel,
    onSingle,
    disabled = false,
    actionLabel = "Go",
    activeLabel = "Working",
    totalXp = 0,
    buttonClassName = "bg-amber-600 text-stone-900 hover:bg-amber-500",
    disabledClassName = "cursor-not-allowed bg-stone-700 text-stone-500",
}: ActionQueueControlsProps) {
    if (isQueueActive) {
        const isAll = queueProgress.total === Infinity;
        const progressText = isAll
            ? `${queueProgress.completed} done`
            : `${queueProgress.completed}/${queueProgress.total}`;
        const progressPct = isAll
            ? undefined
            : (queueProgress.completed / queueProgress.total) * 100;

        return (
            <div className="space-y-2">
                {/* Progress bar */}
                <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                    {progressPct !== undefined ? (
                        <div
                            className="h-full bg-gradient-to-r from-amber-600 to-amber-400 transition-all"
                            style={{ width: `${progressPct}%` }}
                        />
                    ) : (
                        <div className="h-full w-full animate-pulse bg-gradient-to-r from-amber-600/50 to-amber-400/50" />
                    )}
                </div>

                {/* Status row */}
                <div className="flex items-center justify-between">
                    <div className="min-w-0 flex-1">
                        <div className="font-pixel text-xs text-stone-300">
                            {isActionLoading ? (
                                <span className="flex items-center gap-1">
                                    <Loader2 className="h-3 w-3 animate-spin" />
                                    {activeLabel}... {progressText}
                                </span>
                            ) : cooldown > 0 ? (
                                <span className="text-stone-400">
                                    Next in {(cooldown / 1000).toFixed(1)}s — {progressText}
                                </span>
                            ) : (
                                <span>{progressText}</span>
                            )}
                        </div>
                        {totalXp > 0 && (
                            <div className="font-pixel text-[10px] text-amber-400">
                                +{totalXp.toLocaleString()} XP so far
                            </div>
                        )}
                    </div>
                    <button
                        onClick={onCancel}
                        className="ml-2 flex shrink-0 items-center gap-1 rounded-lg border border-red-600/50 bg-stone-800/80 px-3 py-1.5 font-pixel text-xs text-red-400 transition hover:bg-red-900/30"
                    >
                        <Square className="h-3 w-3" />
                        Stop
                    </button>
                </div>
            </div>
        );
    }

    const isOnCooldown = cooldown > 0;
    const canAct = !disabled && !isOnCooldown && !isActionLoading;

    return (
        <div className="flex items-center gap-1.5">
            {/* Primary single action button */}
            <button
                onClick={() => canAct && onSingle()}
                disabled={!canAct}
                className={`relative flex-1 overflow-hidden rounded-md px-3 py-1.5 font-pixel text-xs transition ${
                    canAct ? buttonClassName : disabledClassName
                }`}
            >
                {isOnCooldown && (
                    <div
                        className="absolute inset-0 bg-stone-600/30"
                        style={{ width: `${(cooldown / cooldownMs) * 100}%` }}
                    />
                )}
                <span className="relative">
                    {isActionLoading ? (
                        <span className="flex items-center justify-center gap-1">
                            <Loader2 className="h-3 w-3 animate-spin" />
                        </span>
                    ) : isOnCooldown ? (
                        `${(cooldown / 1000).toFixed(1)}s`
                    ) : (
                        actionLabel
                    )}
                </span>
            </button>

            {/* Repeat buttons - each is a self-explanatory action */}
            {REPEAT_OPTIONS.map((qty) => (
                <button
                    key={qty}
                    onClick={() => canAct && onStart(qty)}
                    disabled={!canAct}
                    title={`${actionLabel} ${qty} times`}
                    className={`rounded-md border px-2 py-1.5 font-pixel text-[10px] transition ${
                        canAct
                            ? "border-stone-600 bg-stone-800/50 text-stone-300 hover:border-amber-500/50 hover:bg-amber-900/20 hover:text-amber-300"
                            : "cursor-not-allowed border-stone-700 bg-stone-800/30 text-stone-600"
                    }`}
                >
                    x{qty}
                </button>
            ))}
            <button
                onClick={() => canAct && onStart(Infinity)}
                disabled={!canAct}
                title={`${actionLabel} until out of resources or energy`}
                className={`flex items-center gap-1 rounded-md border px-2 py-1.5 font-pixel text-[10px] transition ${
                    canAct
                        ? "border-stone-600 bg-stone-800/50 text-stone-300 hover:border-amber-500/50 hover:bg-amber-900/20 hover:text-amber-300"
                        : "cursor-not-allowed border-stone-700 bg-stone-800/30 text-stone-600"
                }`}
            >
                <Repeat className="h-3 w-3" />
            </button>
        </div>
    );
}

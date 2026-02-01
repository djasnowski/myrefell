import { Toaster as SonnerToaster, toast as sonnerToast } from "sonner";
import { ArrowUp, CheckCircle, AlertTriangle, XCircle, Info, type LucideIcon } from "lucide-react";

// Custom styled toaster that matches game aesthetics
export function GameToaster() {
    return (
        <SonnerToaster
            position="bottom-right"
            gap={8}
            toastOptions={{
                unstyled: true,
                classNames: {
                    toast: "w-full max-w-sm",
                },
            }}
        />
    );
}

interface ToastOptions {
    description?: string;
    xp?: number;
    levelUp?: { skill: string; level: number };
    icon?: LucideIcon;
    duration?: number;
}

// Custom toast component matching game style
function GameToastContent({
    type,
    message,
    description,
    xp,
    levelUp,
    icon: CustomIcon,
}: {
    type: "success" | "error" | "warning" | "info";
    message: string;
    description?: string;
    xp?: number;
    levelUp?: { skill: string; level: number };
    icon?: LucideIcon;
}) {
    const styles = {
        success: {
            border: "border-green-600/50",
            bg: "bg-green-900/95",
            iconBg: "bg-green-800/50",
            text: "text-green-300",
            icon: CheckCircle,
            iconColor: "text-green-400",
        },
        error: {
            border: "border-red-600/50",
            bg: "bg-red-900/95",
            iconBg: "bg-red-800/50",
            text: "text-red-300",
            icon: XCircle,
            iconColor: "text-red-400",
        },
        warning: {
            border: "border-orange-600/50",
            bg: "bg-orange-900/95",
            iconBg: "bg-orange-800/50",
            text: "text-orange-300",
            icon: AlertTriangle,
            iconColor: "text-orange-400",
        },
        info: {
            border: "border-blue-600/50",
            bg: "bg-blue-900/95",
            iconBg: "bg-blue-800/50",
            text: "text-blue-300",
            icon: Info,
            iconColor: "text-blue-400",
        },
    };

    const style = styles[type];
    const Icon = CustomIcon || style.icon;

    return (
        <div
            className={`rounded-lg border p-4 shadow-lg backdrop-blur-sm ${style.border} ${style.bg}`}
        >
            <div className="flex items-start gap-3">
                <div className={`shrink-0 rounded-lg p-2 ${style.iconBg}`}>
                    <Icon className={`h-5 w-5 ${style.iconColor}`} />
                </div>
                <div className="min-w-0 flex-1">
                    <div className={`font-pixel text-sm ${style.text}`}>{message}</div>
                    {description && (
                        <div className="mt-0.5 font-pixel text-[10px] text-stone-400">
                            {description}
                        </div>
                    )}
                    {(xp !== undefined || levelUp) && (
                        <div className="mt-1 flex items-center gap-2">
                            {xp !== undefined && xp > 0 && (
                                <span className="font-pixel text-[10px] text-amber-400">
                                    +{xp} XP
                                </span>
                            )}
                            {levelUp && (
                                <span className="flex items-center gap-1 font-pixel text-[10px] text-yellow-300">
                                    <ArrowUp className="h-3 w-3" />
                                    {levelUp.skill} Level {levelUp.level}!
                                </span>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

// Game toast utility functions
export const gameToast = {
    success: (message: string, options?: ToastOptions) => {
        sonnerToast.custom(
            () => (
                <GameToastContent
                    type="success"
                    message={message}
                    description={options?.description}
                    xp={options?.xp}
                    levelUp={options?.levelUp}
                    icon={options?.icon}
                />
            ),
            { duration: options?.duration ?? 4000 },
        );
    },

    error: (message: string, options?: ToastOptions) => {
        sonnerToast.custom(
            () => (
                <GameToastContent
                    type="error"
                    message={message}
                    description={options?.description}
                    icon={options?.icon}
                />
            ),
            { duration: options?.duration ?? 5000 },
        );
    },

    warning: (message: string, options?: ToastOptions) => {
        sonnerToast.custom(
            () => (
                <GameToastContent
                    type="warning"
                    message={message}
                    description={options?.description}
                    xp={options?.xp}
                    icon={options?.icon}
                />
            ),
            { duration: options?.duration ?? 4000 },
        );
    },

    info: (message: string, options?: ToastOptions) => {
        sonnerToast.custom(
            () => (
                <GameToastContent
                    type="info"
                    message={message}
                    description={options?.description}
                    icon={options?.icon}
                />
            ),
            { duration: options?.duration ?? 4000 },
        );
    },

    // Convenience method for training results
    training: (result: {
        success: boolean;
        failed?: boolean;
        message: string;
        xp_awarded?: number;
        leveled_up?: boolean;
        new_level?: number;
        skill?: string;
    }) => {
        const type = result.success ? "success" : result.failed ? "warning" : "error";
        const levelUp =
            result.leveled_up && result.new_level && result.skill
                ? { skill: result.skill, level: result.new_level }
                : undefined;

        sonnerToast.custom(
            () => (
                <GameToastContent
                    type={type}
                    message={result.message}
                    xp={result.xp_awarded}
                    levelUp={levelUp}
                />
            ),
            { duration: 4000 },
        );
    },
};

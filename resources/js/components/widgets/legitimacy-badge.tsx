import {
    AlertTriangle,
    Award,
    Crown,
    Shield,
    ShieldAlert,
    ShieldCheck,
    Star,
    ThumbsDown,
    ThumbsUp,
    TrendingDown,
    TrendingUp,
} from "lucide-react";

interface Props {
    legitimacy: number;
    showLabel?: boolean;
    size?: "sm" | "md" | "lg";
    showTrend?: "up" | "down" | null;
}

const getLegitimacyConfig = (legitimacy: number) => {
    if (legitimacy >= 90) {
        return {
            label: "Beloved",
            color: "text-green-400",
            bg: "bg-green-900/30",
            border: "border-green-600/50",
            icon: Crown,
            description: "The people adore their ruler",
        };
    }
    if (legitimacy >= 70) {
        return {
            label: "Respected",
            color: "text-blue-400",
            bg: "bg-blue-900/30",
            border: "border-blue-600/50",
            icon: ShieldCheck,
            description: "A well-regarded leader",
        };
    }
    if (legitimacy >= 50) {
        return {
            label: "Accepted",
            color: "text-stone-400",
            bg: "bg-stone-800/50",
            border: "border-stone-600/50",
            icon: Shield,
            description: "The ruler has the people's consent",
        };
    }
    if (legitimacy >= 30) {
        return {
            label: "Questioned",
            color: "text-yellow-400",
            bg: "bg-yellow-900/30",
            border: "border-yellow-600/50",
            icon: ShieldAlert,
            description: "Some doubt this ruler's right to rule",
        };
    }
    if (legitimacy >= 10) {
        return {
            label: "Unpopular",
            color: "text-orange-400",
            bg: "bg-orange-900/30",
            border: "border-orange-600/50",
            icon: ThumbsDown,
            description: "The ruler faces significant opposition",
        };
    }
    return {
        label: "Illegitimate",
        color: "text-red-400",
        bg: "bg-red-900/30",
        border: "border-red-600/50",
        icon: AlertTriangle,
        description: "The people do not recognize this ruler",
    };
};

export default function LegitimacyBadge({
    legitimacy,
    showLabel = true,
    size = "md",
    showTrend = null,
}: Props) {
    const config = getLegitimacyConfig(legitimacy);
    const Icon = config.icon;

    const sizeClasses = {
        sm: {
            container: "px-2 py-1",
            icon: "h-3 w-3",
            text: "text-[10px]",
            value: "text-xs",
        },
        md: {
            container: "px-3 py-1.5",
            icon: "h-4 w-4",
            text: "text-xs",
            value: "text-sm",
        },
        lg: {
            container: "px-4 py-2",
            icon: "h-5 w-5",
            text: "text-sm",
            value: "text-base",
        },
    };

    const sizes = sizeClasses[size];

    return (
        <div
            className={`inline-flex items-center gap-2 rounded-lg border ${config.border} ${config.bg} ${sizes.container}`}
            title={config.description}
        >
            <Icon className={`${sizes.icon} ${config.color}`} />
            <span className={`font-pixel ${sizes.value} ${config.color}`}>{legitimacy}%</span>
            {showLabel && (
                <span className={`font-pixel ${sizes.text} text-stone-500`}>{config.label}</span>
            )}
            {showTrend === "up" && <TrendingUp className={`${sizes.icon} text-green-400`} />}
            {showTrend === "down" && <TrendingDown className={`${sizes.icon} text-red-400`} />}
        </div>
    );
}

// Detailed legitimacy display for ruler info sections
export function LegitimacyDisplay({
    legitimacy,
    roleName,
    recentChange,
}: {
    legitimacy: number;
    roleName?: string;
    recentChange?: number;
}) {
    const config = getLegitimacyConfig(legitimacy);
    const Icon = config.icon;

    return (
        <div className={`rounded-lg border ${config.border} ${config.bg} p-3`}>
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <Icon className={`h-5 w-5 ${config.color}`} />
                    <div>
                        <div className={`font-pixel text-sm ${config.color}`}>{config.label}</div>
                        {roleName && (
                            <div className="font-pixel text-[10px] text-stone-500">
                                {roleName} Legitimacy
                            </div>
                        )}
                    </div>
                </div>
                <div className="text-right">
                    <div className={`font-pixel text-xl ${config.color}`}>{legitimacy}%</div>
                    {recentChange !== undefined && recentChange !== 0 && (
                        <div
                            className={`flex items-center justify-end gap-1 font-pixel text-[10px] ${
                                recentChange > 0 ? "text-green-400" : "text-red-400"
                            }`}
                        >
                            {recentChange > 0 ? (
                                <TrendingUp className="h-3 w-3" />
                            ) : (
                                <TrendingDown className="h-3 w-3" />
                            )}
                            {recentChange > 0 ? "+" : ""}
                            {recentChange}
                        </div>
                    )}
                </div>
            </div>
            <p className="mt-2 font-pixel text-[10px] text-stone-500">{config.description}</p>
            {/* Progress bar */}
            <div className="mt-2 h-1.5 w-full rounded-full bg-stone-700">
                <div
                    className={`h-full rounded-full ${config.bg.replace("/30", "")} transition-all`}
                    style={{ width: `${legitimacy}%` }}
                />
            </div>
            {/* High legitimacy indicators */}
            {legitimacy >= 70 && (
                <div className="mt-2 flex items-center gap-1">
                    {legitimacy >= 90 && <Award className="h-3 w-3 text-amber-400" />}
                    {legitimacy >= 70 && <Star className="h-3 w-3 text-amber-400" />}
                    {legitimacy >= 50 && <ThumbsUp className="h-3 w-3 text-stone-500" />}
                    <span className="font-pixel text-[8px] text-stone-500">
                        {legitimacy >= 90 ? "Legendary status" : "Highly regarded"}
                    </span>
                </div>
            )}
        </div>
    );
}

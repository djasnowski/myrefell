import {
    AlertTriangle,
    Bug,
    CloudRain,
    Flame,
    Mountain,
    Snowflake,
    Waves,
    Wind,
    Zap,
} from "lucide-react";

interface Disaster {
    id: number;
    type: string;
    name: string;
    severity: "minor" | "moderate" | "severe" | "catastrophic";
    status: "active" | "ending";
    started_at: string;
    days_active: number;
    buildings_damaged: number;
    casualties: number;
}

interface Props {
    disasters: Disaster[];
    compact?: boolean;
}

const DISASTER_ICONS: Record<string, typeof Flame> = {
    fire: Flame,
    flood: Waves,
    storm: CloudRain,
    earthquake: Mountain,
    blizzard: Snowflake,
    tornado: Wind,
    lightning: Zap,
    plague: Bug,
    default: AlertTriangle,
};

const SEVERITY_CONFIG = {
    minor: {
        color: "text-yellow-400",
        bg: "bg-yellow-900/30",
        border: "border-yellow-600/50",
    },
    moderate: {
        color: "text-orange-400",
        bg: "bg-orange-900/30",
        border: "border-orange-600/50",
    },
    severe: {
        color: "text-red-400",
        bg: "bg-red-900/30",
        border: "border-red-600/50",
    },
    catastrophic: {
        color: "text-red-300",
        bg: "bg-red-800/50",
        border: "border-red-500/50",
    },
};

export default function DisasterWidget({ disasters, compact = false }: Props) {
    if (disasters.length === 0) return null;

    const activeDisasters = disasters.filter((d) => d.status === "active");

    if (compact) {
        return (
            <div className="flex flex-wrap gap-2">
                {activeDisasters.map((disaster) => {
                    const Icon = DISASTER_ICONS[disaster.type] || DISASTER_ICONS.default;
                    const config = SEVERITY_CONFIG[disaster.severity];
                    return (
                        <div
                            key={disaster.id}
                            className={`flex items-center gap-1.5 rounded-lg border ${config.border} ${config.bg} px-2 py-1`}
                        >
                            <Icon className={`h-3.5 w-3.5 ${config.color}`} />
                            <span className={`font-pixel text-[10px] ${config.color}`}>
                                {disaster.name}
                            </span>
                        </div>
                    );
                })}
            </div>
        );
    }

    return (
        <div className="rounded-xl border-2 border-red-600/50 bg-red-900/20 p-4">
            <div className="mb-3 flex items-center gap-2">
                <AlertTriangle className="h-5 w-5 text-red-400" />
                <h3 className="font-pixel text-sm text-red-400">
                    Active Disasters ({activeDisasters.length})
                </h3>
            </div>
            <div className="space-y-2">
                {activeDisasters.map((disaster) => {
                    const Icon = DISASTER_ICONS[disaster.type] || DISASTER_ICONS.default;
                    const config = SEVERITY_CONFIG[disaster.severity];
                    return (
                        <div
                            key={disaster.id}
                            className={`flex items-start justify-between rounded-lg border ${config.border} ${config.bg} p-3`}
                        >
                            <div className="flex items-start gap-3">
                                <Icon className={`h-5 w-5 ${config.color}`} />
                                <div>
                                    <div className="flex items-center gap-2">
                                        <span className={`font-pixel text-sm ${config.color}`}>
                                            {disaster.name}
                                        </span>
                                        <span
                                            className={`rounded px-1.5 py-0.5 font-pixel text-[10px] capitalize ${config.bg} ${config.color}`}
                                        >
                                            {disaster.severity}
                                        </span>
                                    </div>
                                    <div className="mt-1 flex items-center gap-3 font-pixel text-[10px] text-stone-500">
                                        <span>Day {disaster.days_active}</span>
                                        {disaster.buildings_damaged > 0 && (
                                            <span>
                                                {disaster.buildings_damaged} buildings damaged
                                            </span>
                                        )}
                                        {disaster.casualties > 0 && (
                                            <span className="text-red-400">
                                                {disaster.casualties} casualties
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
            <p className="mt-3 font-pixel text-[10px] text-stone-500">
                Disasters may damage buildings and harm residents. Seek shelter if possible.
            </p>
        </div>
    );
}

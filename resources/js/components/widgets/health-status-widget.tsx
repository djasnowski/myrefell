import { Link } from '@inertiajs/react';
import {
    Activity,
    AlertTriangle,
    Bug,
    Heart,
    Pill,
    Thermometer,
} from 'lucide-react';

interface DiseaseInfection {
    id: number;
    disease_name: string;
    status: 'incubating' | 'symptomatic' | 'recovering';
    severity: string;
    days_infected: number;
    is_treated: boolean;
}

interface Props {
    infection: DiseaseInfection | null;
    compact?: boolean;
}

const STATUS_CONFIG = {
    incubating: {
        label: 'Incubating',
        color: 'text-yellow-400',
        bg: 'bg-yellow-900/30',
        border: 'border-yellow-600/50',
        icon: Bug,
    },
    symptomatic: {
        label: 'Symptomatic',
        color: 'text-red-400',
        bg: 'bg-red-900/30',
        border: 'border-red-600/50',
        icon: Thermometer,
    },
    recovering: {
        label: 'Recovering',
        color: 'text-green-400',
        bg: 'bg-green-900/30',
        border: 'border-green-600/50',
        icon: Activity,
    },
};

export default function HealthStatusWidget({ infection, compact = false }: Props) {
    if (!infection) {
        if (compact) return null;
        return (
            <div className="rounded-lg border border-green-600/30 bg-green-900/10 p-3">
                <div className="flex items-center gap-2">
                    <Heart className="h-4 w-4 text-green-400" />
                    <span className="font-pixel text-xs text-green-400">Healthy</span>
                </div>
            </div>
        );
    }

    const config = STATUS_CONFIG[infection.status];
    const StatusIcon = config.icon;

    if (compact) {
        return (
            <Link
                href="/healer"
                className={`flex items-center gap-2 rounded-lg border ${config.border} ${config.bg} px-3 py-2 transition hover:opacity-80`}
            >
                <StatusIcon className={`h-4 w-4 ${config.color}`} />
                <span className={`font-pixel text-xs ${config.color}`}>
                    {infection.disease_name}
                </span>
            </Link>
        );
    }

    return (
        <div className={`rounded-xl border-2 ${config.border} ${config.bg} p-4`}>
            <div className="flex items-start justify-between">
                <div className="flex items-center gap-3">
                    <div className={`rounded-lg ${config.bg} p-2`}>
                        <StatusIcon className={`h-6 w-6 ${config.color}`} />
                    </div>
                    <div>
                        <div className="flex items-center gap-2">
                            <span className={`font-pixel text-sm ${config.color}`}>
                                {infection.disease_name}
                            </span>
                            <span className={`rounded px-1.5 py-0.5 font-pixel text-[10px] ${config.bg} ${config.color}`}>
                                {config.label}
                            </span>
                        </div>
                        <div className="mt-1 flex items-center gap-3 font-pixel text-[10px] text-stone-500">
                            <span>Day {infection.days_infected}</span>
                            <span className="capitalize">{infection.severity} severity</span>
                        </div>
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    {infection.is_treated ? (
                        <div className="flex items-center gap-1 rounded bg-blue-900/30 px-2 py-1">
                            <Pill className="h-3 w-3 text-blue-400" />
                            <span className="font-pixel text-[10px] text-blue-400">Treated</span>
                        </div>
                    ) : (
                        <Link
                            href="/healer"
                            className="flex items-center gap-1 rounded bg-amber-900/30 px-2 py-1 transition hover:bg-amber-900/50"
                        >
                            <AlertTriangle className="h-3 w-3 text-amber-400" />
                            <span className="font-pixel text-[10px] text-amber-400">Seek Treatment</span>
                        </Link>
                    )}
                </div>
            </div>
        </div>
    );
}

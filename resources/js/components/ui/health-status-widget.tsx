import { Link } from "@inertiajs/react";
import { Activity, AlertTriangle, Heart, Shield, Thermometer } from "lucide-react";

import { cn } from "@/lib/utils";

interface DiseaseInfection {
    id: number;
    status: 'incubating' | 'symptomatic' | 'recovering' | 'recovered' | 'deceased';
    days_infected: number;
    days_symptomatic: number;
    is_treated: boolean;
    disease_type: {
        id: number;
        name: string;
        severity: 'minor' | 'moderate' | 'severe' | 'plague';
        symptoms: string[];
    };
}

interface DiseaseImmunity {
    id: number;
    immunity_type: string;
    expires_at: string | null;
    disease_type: {
        id: number;
        name: string;
    };
}

interface HealthStatusWidgetProps {
    infections: DiseaseInfection[];
    immunities?: DiseaseImmunity[];
    currentLocationPath?: string;
    className?: string;
}

/**
 * Get display text for infection status.
 */
function getStatusDisplay(status: string): string {
    switch (status) {
        case 'incubating':
            return 'Incubating';
        case 'symptomatic':
            return 'Symptomatic';
        case 'recovering':
            return 'Recovering';
        case 'recovered':
            return 'Recovered';
        case 'deceased':
            return 'Deceased';
        default:
            return status;
    }
}

/**
 * Get severity color classes.
 */
function getSeverityColors(severity: string): string {
    switch (severity) {
        case 'minor':
            return 'bg-yellow-900/50 text-yellow-300 border-yellow-500/50';
        case 'moderate':
            return 'bg-orange-900/50 text-orange-300 border-orange-500/50';
        case 'severe':
            return 'bg-red-900/50 text-red-300 border-red-500/50';
        case 'plague':
            return 'bg-red-900/70 text-red-200 border-red-400/50';
        default:
            return 'bg-stone-900/50 text-stone-300 border-stone-500/50';
    }
}

/**
 * Get status color classes.
 */
function getStatusColors(status: string): string {
    switch (status) {
        case 'incubating':
            return 'text-amber-400';
        case 'symptomatic':
            return 'text-red-400';
        case 'recovering':
            return 'text-green-400';
        case 'recovered':
            return 'text-green-300';
        default:
            return 'text-stone-400';
    }
}

/**
 * Get status icon.
 */
function StatusIcon({ status }: { status: string }) {
    switch (status) {
        case 'incubating':
            return <Activity className="h-4 w-4" />;
        case 'symptomatic':
            return <AlertTriangle className="h-4 w-4" />;
        case 'recovering':
            return <Heart className="h-4 w-4" />;
        default:
            return <Thermometer className="h-4 w-4" />;
    }
}

export function HealthStatusWidget({
    infections,
    immunities = [],
    currentLocationPath,
    className
}: HealthStatusWidgetProps) {
    // Only show active infections
    const activeInfections = infections.filter(
        inf => ['incubating', 'symptomatic', 'recovering'].includes(inf.status)
    );

    // If no active infections, show healthy status (compact)
    if (activeInfections.length === 0) {
        return null;
    }

    return (
        <div className={cn("rounded-lg border-2 border-red-500/50 bg-red-900/20 p-3", className)}>
            <div className="mb-2 flex items-center gap-2">
                <Thermometer className="h-4 w-4 text-red-400" />
                <span className="font-pixel text-sm text-red-300">Health Status</span>
            </div>

            <div className="space-y-2">
                {activeInfections.map((infection) => (
                    <div
                        key={infection.id}
                        className={cn(
                            "rounded border p-2",
                            getSeverityColors(infection.disease_type.severity)
                        )}
                    >
                        <div className="flex items-center justify-between gap-2">
                            <div className="flex items-center gap-2">
                                <StatusIcon status={infection.status} />
                                <span className="font-pixel text-xs">
                                    {infection.disease_type.name}
                                </span>
                            </div>
                            <span className={cn(
                                "rounded px-1.5 py-0.5 text-[10px] font-pixel",
                                getStatusColors(infection.status)
                            )}>
                                {getStatusDisplay(infection.status)}
                            </span>
                        </div>

                        <div className="mt-1 flex items-center gap-2 text-[10px] text-stone-400">
                            <span>Day {infection.days_infected}</span>
                            {infection.is_treated && (
                                <>
                                    <span>|</span>
                                    <span className="text-green-400">Treated</span>
                                </>
                            )}
                            <span>|</span>
                            <span className="capitalize">{infection.disease_type.severity}</span>
                        </div>

                        {infection.status === 'symptomatic' && infection.disease_type.symptoms.length > 0 && (
                            <div className="mt-1 text-[10px] text-stone-500">
                                Symptoms: {infection.disease_type.symptoms.slice(0, 2).join(', ')}
                                {infection.disease_type.symptoms.length > 2 && '...'}
                            </div>
                        )}
                    </div>
                ))}
            </div>

            {currentLocationPath && (
                <Link
                    href={currentLocationPath}
                    className="mt-2 flex items-center gap-1 text-xs text-amber-300 hover:text-amber-200"
                >
                    Visit Healer →
                </Link>
            )}

            {immunities.length > 0 && (
                <div className="mt-3 border-t border-stone-700 pt-2">
                    <div className="flex items-center gap-1 text-[10px] text-stone-500">
                        <Shield className="h-3 w-3" />
                        <span>
                            Immune to: {immunities.map(i => i.disease_type.name).join(', ')}
                        </span>
                    </div>
                </div>
            )}
        </div>
    );
}

export { type DiseaseInfection, type DiseaseImmunity };

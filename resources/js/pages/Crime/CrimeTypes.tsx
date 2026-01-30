import { Head, usePage } from '@inertiajs/react';
import { AlertTriangle, Gavel, Scale, Shield } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface CrimeType {
    id: number;
    name: string;
    description: string;
    severity: string;
    default_fine: number;
    default_jail_days: number;
    requires_witness: boolean;
    is_capital: boolean;
}

interface PageProps {
    crime_types: CrimeType[];
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Crime', href: '/crime' },
    { title: 'Crime Types', href: '#' },
];

const severityColors: Record<string, string> = {
    minor: 'border-yellow-500/50 bg-yellow-900/20 text-yellow-400',
    moderate: 'border-orange-500/50 bg-orange-900/20 text-orange-400',
    serious: 'border-red-500/50 bg-red-900/20 text-red-400',
    capital: 'border-purple-500/50 bg-purple-900/20 text-purple-400',
};

export default function CrimeTypes() {
    const { crime_types } = usePage<PageProps>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Crime Types" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="rounded-lg bg-red-900/30 p-2">
                        <Scale className="size-6 text-red-400" />
                    </div>
                    <div>
                        <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                            Crime Types
                        </h1>
                        <p className="text-sm text-stone-400">
                            Reference guide to crimes and their punishments
                        </p>
                    </div>
                </div>

                {/* Crime Types List */}
                <div className="grid gap-4 md:grid-cols-2">
                    {crime_types.map((crime) => (
                        <div
                            key={crime.id}
                            className={`rounded-xl border p-4 ${severityColors[crime.severity] || severityColors.minor}`}
                        >
                            <div className="flex items-start justify-between">
                                <div className="flex items-center gap-2">
                                    {crime.is_capital ? (
                                        <AlertTriangle className="size-5 text-purple-400" />
                                    ) : (
                                        <Gavel className="size-5" />
                                    )}
                                    <h3 className="font-[Cinzel] font-semibold text-stone-100">
                                        {crime.name}
                                    </h3>
                                </div>
                                <span className="rounded-full px-2 py-0.5 text-xs capitalize">
                                    {crime.severity}
                                </span>
                            </div>

                            <p className="mt-2 text-sm text-stone-300">
                                {crime.description}
                            </p>

                            <div className="mt-3 grid grid-cols-2 gap-2 text-xs">
                                <div className="rounded bg-stone-900/50 p-2">
                                    <span className="text-stone-500">Default Fine:</span>
                                    <span className="ml-1 text-amber-400">
                                        {crime.default_fine} gold
                                    </span>
                                </div>
                                <div className="rounded bg-stone-900/50 p-2">
                                    <span className="text-stone-500">Jail Time:</span>
                                    <span className="ml-1 text-stone-300">
                                        {crime.default_jail_days} days
                                    </span>
                                </div>
                            </div>

                            <div className="mt-2 flex gap-2">
                                {crime.requires_witness && (
                                    <span className="flex items-center gap-1 rounded bg-stone-900/50 px-2 py-1 text-xs text-stone-400">
                                        <Shield className="size-3" />
                                        Requires Witness
                                    </span>
                                )}
                                {crime.is_capital && (
                                    <span className="flex items-center gap-1 rounded bg-purple-900/50 px-2 py-1 text-xs text-purple-400">
                                        <AlertTriangle className="size-3" />
                                        Capital Crime
                                    </span>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}

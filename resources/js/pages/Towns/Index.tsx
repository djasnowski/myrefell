import { Head, Link, usePage } from '@inertiajs/react';
import {
    Anchor,
    Building2,
    Castle,
    Crown,
    MapPin,
    Users,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Town {
    id: number;
    name: string;
    description: string | null;
    biome: string;
    is_capital: boolean;
    is_port: boolean;
    population: number;
    visitors_count: number;
    barony: { id: number; name: string } | null;
    duchy: { id: number; name: string } | null;
    kingdom: { id: number; name: string } | null;
}

interface PageProps {
    towns: Town[];
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Towns', href: '/towns' },
];

const biomeColors: Record<string, string> = {
    plains: 'bg-green-900/20 text-green-400',
    forest: 'bg-emerald-900/20 text-emerald-400',
    mountains: 'bg-stone-700/40 text-stone-300',
    desert: 'bg-amber-900/20 text-amber-400',
    tundra: 'bg-blue-900/20 text-blue-400',
    swamp: 'bg-lime-900/20 text-lime-400',
    coastal: 'bg-cyan-900/20 text-cyan-400',
};

export default function TownsIndex() {
    const { towns } = usePage<PageProps>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Towns" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="rounded-lg bg-blue-900/30 p-2">
                        <Building2 className="size-6 text-blue-400" />
                    </div>
                    <div>
                        <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                            Towns
                        </h1>
                        <p className="text-sm text-stone-400">
                            {towns.length} towns across the realm
                        </p>
                    </div>
                </div>

                {/* Towns Grid */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {towns.map((town) => (
                        <Link
                            key={town.id}
                            href={`/towns/${town.id}`}
                            className="group rounded-xl border border-stone-800 bg-stone-900/50 p-4 transition hover:border-stone-700 hover:bg-stone-900/70"
                        >
                            <div className="flex items-start justify-between">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <h3 className="font-[Cinzel] font-semibold text-stone-100 group-hover:text-amber-400">
                                            {town.name}
                                        </h3>
                                        {town.is_capital && (
                                            <Crown className="size-4 text-amber-400" />
                                        )}
                                        {town.is_port && (
                                            <Anchor className="size-4 text-cyan-400" />
                                        )}
                                    </div>
                                    <div className="mt-1 flex items-center gap-2 text-xs text-stone-500">
                                        {town.kingdom && (
                                            <>
                                                <Castle className="size-3" />
                                                {town.kingdom.name}
                                            </>
                                        )}
                                        {town.barony && (
                                            <>
                                                <span className="text-stone-600">•</span>
                                                {town.barony.name}
                                            </>
                                        )}
                                    </div>
                                </div>
                                <span
                                    className={`rounded px-2 py-0.5 text-xs capitalize ${biomeColors[town.biome] || biomeColors.plains}`}
                                >
                                    {town.biome}
                                </span>
                            </div>

                            {town.description && (
                                <p className="mt-2 line-clamp-2 text-sm text-stone-400">
                                    {town.description}
                                </p>
                            )}

                            <div className="mt-3 flex items-center gap-4 text-sm text-stone-500">
                                <span className="flex items-center gap-1">
                                    <Users className="size-4" />
                                    {town.population.toLocaleString()} pop
                                </span>
                                <span className="flex items-center gap-1">
                                    <MapPin className="size-4" />
                                    {town.visitors_count} visitors
                                </span>
                            </div>

                            {town.duchy && (
                                <div className="mt-2 text-xs text-stone-600">
                                    Duchy of {town.duchy.name}
                                </div>
                            )}
                        </Link>
                    ))}
                </div>

                {towns.length === 0 && (
                    <div className="rounded-xl border border-stone-800 bg-stone-900/50 p-8 text-center">
                        <Building2 className="mx-auto size-12 text-stone-600" />
                        <p className="mt-4 text-stone-400">No towns found</p>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

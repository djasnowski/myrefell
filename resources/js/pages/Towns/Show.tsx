import { Head, Link } from '@inertiajs/react';
import { Anchor, Banknote, Building2, Castle, Church, Crown, Home, MapPin, Users } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Town {
    id: number;
    name: string;
    description: string;
    biome: string;
    is_capital: boolean;
    population: number;
    wealth: number;
    tax_rate: number;
    coordinates: { x: number; y: number };
    kingdom: { id: number; name: string } | null;
    barony: { id: number; name: string } | null;
    mayor: { id: number; username: string } | null;
    villages: { id: number; name: string; biome: string; population: number; is_port: boolean }[];
    village_count: number;
}

interface Service {
    name: string;
    href: string;
    description: string;
}

interface Props {
    town: Town;
    services: Service[];
}

const biomeColors: Record<string, { bg: string; text: string }> = {
    plains: { bg: 'bg-green-900/30', text: 'text-green-400' },
    forest: { bg: 'bg-emerald-900/30', text: 'text-emerald-400' },
    tundra: { bg: 'bg-blue-900/30', text: 'text-blue-400' },
    coastal: { bg: 'bg-cyan-900/30', text: 'text-cyan-400' },
    desert: { bg: 'bg-amber-900/30', text: 'text-amber-400' },
    volcano: { bg: 'bg-red-900/30', text: 'text-red-400' },
    mountains: { bg: 'bg-slate-900/30', text: 'text-slate-400' },
    swamps: { bg: 'bg-lime-900/30', text: 'text-lime-400' },
};

const serviceIcons: Record<string, typeof Banknote> = {
    Bank: Banknote,
    Infirmary: Church,
    'Town Hall': Building2,
};

export default function TownShow({ town, services }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Towns', href: '/towns' },
        { title: town.name, href: `/towns/${town.id}` },
    ];

    const biome = biomeColors[town.biome] || { bg: 'bg-stone-900/30', text: 'text-stone-400' };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={town.name} />
            <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                {/* Header */}
                <div className="mb-6">
                    <div className="flex items-center gap-3">
                        <div className={`rounded-lg p-3 ${biome.bg}`}>
                            <Church className={`h-8 w-8 ${biome.text}`} />
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="font-pixel text-2xl text-stone-200">{town.name}</h1>
                                {town.is_capital && (
                                    <span className="flex items-center gap-1 rounded-full bg-amber-900/50 px-2 py-0.5 font-pixel text-[10px] text-amber-400">
                                        <Crown className="h-3 w-3" />
                                        Capital
                                    </span>
                                )}
                            </div>
                            <div className="flex items-center gap-2 font-pixel text-xs text-stone-500">
                                <span className={`capitalize ${biome.text}`}>{town.biome}</span>
                                <span>•</span>
                                <MapPin className="h-3 w-3" />
                                <span>({town.coordinates.x}, {town.coordinates.y})</span>
                            </div>
                        </div>
                    </div>
                    {town.description && (
                        <p className="mt-3 font-pixel text-xs leading-relaxed text-stone-400">{town.description}</p>
                    )}
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    {/* Left Column - Info */}
                    <div className="space-y-4">
                        {/* Stats */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-3 font-pixel text-sm text-stone-300">Town Info</h2>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <div className="font-pixel text-[10px] text-stone-500">Barony</div>
                                    {town.barony ? (
                                        <Link href={`/baronies/${town.barony.id}`} className="font-pixel text-xs text-purple-400 hover:underline">
                                            {town.barony.name}
                                        </Link>
                                    ) : (
                                        <span className="font-pixel text-xs text-stone-400">None</span>
                                    )}
                                </div>
                                <div>
                                    <div className="font-pixel text-[10px] text-stone-500">Kingdom</div>
                                    {town.kingdom ? (
                                        <Link href={`/kingdoms/${town.kingdom.id}`} className="font-pixel text-xs text-amber-400 hover:underline">
                                            {town.kingdom.name}
                                        </Link>
                                    ) : (
                                        <span className="font-pixel text-xs text-stone-400">None</span>
                                    )}
                                </div>
                                <div>
                                    <div className="font-pixel text-[10px] text-stone-500">Mayor</div>
                                    {town.mayor ? (
                                        <span className="font-pixel text-xs text-stone-300">{town.mayor.username}</span>
                                    ) : (
                                        <span className="font-pixel text-xs text-stone-500">Vacant</span>
                                    )}
                                </div>
                                <div>
                                    <div className="font-pixel text-[10px] text-stone-500">Population</div>
                                    <div className="flex items-center gap-1">
                                        <Users className="h-3 w-3 text-stone-500" />
                                        <span className="font-pixel text-xs text-stone-300">{town.population.toLocaleString()}</span>
                                    </div>
                                </div>
                                <div>
                                    <div className="font-pixel text-[10px] text-stone-500">Wealth</div>
                                    <span className="font-pixel text-xs text-yellow-400">{town.wealth.toLocaleString()} gold</span>
                                </div>
                                <div>
                                    <div className="font-pixel text-[10px] text-stone-500">Tax Rate</div>
                                    <span className="font-pixel text-xs text-stone-300">{(town.tax_rate * 100).toFixed(0)}%</span>
                                </div>
                            </div>
                        </div>

                        {/* Services */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-3 font-pixel text-sm text-stone-300">Services</h2>
                            <div className="space-y-2">
                                {services.map((service) => {
                                    const Icon = serviceIcons[service.name] || Building2;
                                    return (
                                        <Link
                                            key={service.name}
                                            href={service.href}
                                            className="flex items-center gap-3 rounded-lg border border-stone-700 bg-stone-800/50 p-3 transition hover:bg-stone-700/50"
                                        >
                                            <Icon className="h-5 w-5 text-blue-400" />
                                            <div>
                                                <div className="font-pixel text-xs text-stone-200">{service.name}</div>
                                                <div className="font-pixel text-[10px] text-stone-500">{service.description}</div>
                                            </div>
                                        </Link>
                                    );
                                })}
                            </div>
                        </div>
                    </div>

                    {/* Middle Column - Villages */}
                    <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <h2 className="mb-3 font-pixel text-sm text-stone-300">
                            Villages ({town.village_count})
                        </h2>
                        {town.villages.length > 0 ? (
                            <div className="max-h-96 space-y-2 overflow-y-auto">
                                {town.villages.map((village) => (
                                    <Link
                                        key={village.id}
                                        href={`/villages/${village.id}`}
                                        className="flex items-center gap-3 rounded-lg border border-stone-700 bg-stone-800/50 p-3 transition hover:bg-stone-700/50"
                                    >
                                        {village.is_port ? (
                                            <Anchor className="h-5 w-5 text-blue-400" />
                                        ) : (
                                            <Home className="h-5 w-5 text-green-400" />
                                        )}
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center gap-2">
                                                <span className="truncate font-pixel text-xs text-stone-200">{village.name}</span>
                                                {village.is_port && (
                                                    <span className="rounded bg-blue-900/50 px-1.5 py-0.5 font-pixel text-[8px] text-blue-400">
                                                        Port
                                                    </span>
                                                )}
                                            </div>
                                            <div className="flex items-center gap-2 font-pixel text-[10px] text-stone-500">
                                                <span className="capitalize">{village.biome}</span>
                                                <span>•</span>
                                                <span>{village.population.toLocaleString()} pop</span>
                                            </div>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        ) : (
                            <div className="py-8 text-center font-pixel text-xs text-stone-500">
                                No villages in this town
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

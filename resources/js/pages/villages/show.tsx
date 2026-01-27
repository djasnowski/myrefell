import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Anchor,
    Banknote,
    Church,
    Coins,
    Crown,
    Gavel,
    Home,
    Loader2,
    MapPin,
    MessageCircle,
    Mountain,
    Palmtree,
    ScrollText,
    Shield,
    Snowflake,
    Store,
    Sun,
    Swords,
    TreePine,
    Trees,
    Users,
    Waves,
    Wheat,
    type LucideIcon,
} from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import DisasterWidget from '@/components/widgets/disaster-widget';
import { LegitimacyDisplay } from '@/components/widgets/legitimacy-badge';
import type { BreadcrumbItem } from '@/types';

interface Resident {
    id: number;
    username: string;
    combat_level: number;
}

interface Ruler {
    id: number;
    username: string;
    primary_title?: string | null;
    legitimacy?: number;
}

interface Village {
    id: number;
    name: string;
    description: string;
    biome: string;
    is_town: boolean;
    is_hamlet?: boolean;
    population: number;
    wealth: number;
    coordinates: {
        x: number;
        y: number;
    };
    barony: {
        id: number;
        name: string;
        biome: string;
    } | null;
    kingdom: {
        id: number;
        name: string;
    } | null;
    parent_village?: {
        id: number;
        name: string;
    } | null;
    residents: Resident[];
    resident_count: number;
    elder?: Ruler | null;
}

interface Disaster {
    id: number;
    type: string;
    name: string;
    severity: 'minor' | 'moderate' | 'severe' | 'catastrophic';
    status: 'active' | 'ending';
    started_at: string;
    days_active: number;
    buildings_damaged: number;
    casualties: number;
}

interface Props {
    village: Village;
    is_resident: boolean;
    can_migrate: boolean;
    has_pending_request: boolean;
    current_user_id: number;
    disasters?: Disaster[];
    flash?: {
        success?: string;
        error?: string;
    };
}

const biomeConfig: Record<string, { icon: LucideIcon; color: string; bg: string; border: string }> = {
    plains: { icon: Wheat, color: 'text-green-400', bg: 'bg-green-900/30', border: 'border-green-600/50' },
    forest: { icon: Trees, color: 'text-emerald-400', bg: 'bg-emerald-900/30', border: 'border-emerald-600/50' },
    tundra: { icon: Snowflake, color: 'text-cyan-400', bg: 'bg-cyan-900/30', border: 'border-cyan-600/50' },
    coastal: { icon: Waves, color: 'text-blue-400', bg: 'bg-blue-900/30', border: 'border-blue-600/50' },
    desert: { icon: Sun, color: 'text-amber-400', bg: 'bg-amber-900/30', border: 'border-amber-600/50' },
    volcano: { icon: Mountain, color: 'text-red-400', bg: 'bg-red-900/30', border: 'border-red-600/50' },
    mountains: { icon: Mountain, color: 'text-slate-400', bg: 'bg-slate-900/30', border: 'border-slate-600/50' },
    swamps: { icon: TreePine, color: 'text-lime-400', bg: 'bg-lime-900/30', border: 'border-lime-600/50' },
    tropical: { icon: Palmtree, color: 'text-teal-400', bg: 'bg-teal-900/30', border: 'border-teal-600/50' },
};

export default function VillageShow({ village, is_resident, can_migrate, has_pending_request, current_user_id, disasters = [] }: Props) {
    const { flash } = usePage<{ flash?: { success?: string; error?: string } }>().props;
    const [loading, setLoading] = useState(false);

    const biome = biomeConfig[village.biome] || biomeConfig.plains;
    const BiomeIcon = biome.icon;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: village.name, href: `/villages/${village.id}` },
    ];

    const handleRequestMigration = () => {
        setLoading(true);
        router.post(`/migration/request/${village.id}`, {}, {
            onFinish: () => setLoading(false),
        });
    };

    const quickActions = [
        { title: 'Market', href: `/villages/${village.id}/market`, icon: Store, color: 'amber' },
        { title: 'Bank', href: `/villages/${village.id}/bank`, icon: Banknote, color: 'green' },
        { title: 'Healer', href: `/villages/${village.id}/healer`, icon: Church, color: 'red' },
        { title: 'Notice Board', href: `/villages/${village.id}/quests`, icon: ScrollText, color: 'purple' },
        { title: 'Chat', href: '/chat', icon: MessageCircle, color: 'blue' },
        { title: 'Court', href: '/crime', icon: Gavel, color: 'stone' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={village.name} />
            <div className="flex flex-col gap-6 p-6">
                {/* Hero Header */}
                <div className={`rounded-xl border-2 ${biome.border} ${biome.bg} p-6`}>
                    <div className="flex items-start gap-4">
                        <div className={`rounded-xl ${biome.bg} border ${biome.border} p-4`}>
                            <BiomeIcon className={`h-12 w-12 ${biome.color}`} />
                        </div>
                        <div className="flex-1">
                            <div className="flex items-center gap-3">
                                <h1 className="font-[Cinzel] text-3xl font-bold text-stone-100">
                                    {village.name}
                                </h1>
                                {village.is_hamlet && (
                                    <span className="rounded-full bg-stone-700 px-2 py-0.5 text-xs text-stone-300">Hamlet</span>
                                )}
                                {village.is_town && (
                                    <span className="rounded-full bg-purple-900/50 px-2 py-0.5 text-xs text-purple-300">Town</span>
                                )}
                                <span className={`rounded-full ${biome.bg} border ${biome.border} px-3 py-0.5 text-xs capitalize ${biome.color}`}>
                                    {village.biome}
                                </span>
                            </div>
                            <p className="mt-2 text-stone-400">{village.description}</p>

                            {/* Hierarchy */}
                            <div className="mt-3 flex items-center gap-2 text-sm">
                                {village.kingdom && (
                                    <>
                                        <Link href={`/kingdoms/${village.kingdom.id}`} className="text-amber-400 hover:underline">
                                            {village.kingdom.name}
                                        </Link>
                                        <span className="text-stone-600">›</span>
                                    </>
                                )}
                                {village.barony && (
                                    <>
                                        <Link href={`/baronies/${village.barony.id}`} className="text-stone-300 hover:underline">
                                            {village.barony.name}
                                        </Link>
                                        <span className="text-stone-600">›</span>
                                    </>
                                )}
                                <span className="text-stone-500">{village.name}</span>
                                {village.parent_village && (
                                    <span className="text-stone-600 text-xs">
                                        (hamlet of <Link href={`/villages/${village.parent_village.id}`} className="text-stone-400 hover:underline">{village.parent_village.name}</Link>)
                                    </span>
                                )}
                            </div>
                        </div>

                        {/* Home badge */}
                        {is_resident && (
                            <div className="flex items-center gap-2 rounded-lg border border-green-600/50 bg-green-900/30 px-3 py-2">
                                <Home className="h-4 w-4 text-green-400" />
                                <span className="font-pixel text-xs text-green-400">Your Home</span>
                            </div>
                        )}
                    </div>
                </div>

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="rounded-lg border border-green-600/50 bg-green-900/20 px-4 py-3">
                        <p className="font-pixel text-sm text-green-300">{flash.success}</p>
                    </div>
                )}
                {flash?.error && (
                    <div className="rounded-lg border border-red-600/50 bg-red-900/20 px-4 py-3">
                        <p className="font-pixel text-sm text-red-300">{flash.error}</p>
                    </div>
                )}

                {/* Disaster Alert */}
                {disasters.length > 0 && (
                    <DisasterWidget disasters={disasters} />
                )}

                {/* Stats Row */}
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <Users className="mx-auto mb-2 h-6 w-6 text-blue-400" />
                        <div className="font-pixel text-2xl text-stone-100">
                            {village.resident_count}
                        </div>
                        <div className="text-xs text-stone-500">Players</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <Users className="mx-auto mb-2 h-6 w-6 text-stone-400" />
                        <div className="font-pixel text-2xl text-stone-100">
                            {village.population.toLocaleString()}
                        </div>
                        <div className="text-xs text-stone-500">NPCs</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <Coins className="mx-auto mb-2 h-6 w-6 text-amber-400" />
                        <div className="font-pixel text-2xl text-amber-300">
                            {village.wealth.toLocaleString()}
                        </div>
                        <div className="text-xs text-stone-500">Treasury</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <MapPin className="mx-auto mb-2 h-6 w-6 text-stone-400" />
                        <div className="font-pixel text-lg text-stone-300">
                            {village.coordinates.x}, {village.coordinates.y}
                        </div>
                        <div className="text-xs text-stone-500">Coordinates</div>
                    </div>
                </div>

                {/* Quick Actions */}
                <div>
                    <h2 className="mb-3 font-pixel text-sm text-stone-400">Services</h2>
                    <div className="grid grid-cols-3 gap-2 sm:grid-cols-6">
                        {quickActions.map((action) => {
                            const colorClasses: Record<string, string> = {
                                amber: 'border-amber-600/30 bg-amber-900/20 text-amber-400 hover:bg-amber-900/40',
                                green: 'border-green-600/30 bg-green-900/20 text-green-400 hover:bg-green-900/40',
                                red: 'border-red-600/30 bg-red-900/20 text-red-400 hover:bg-red-900/40',
                                purple: 'border-purple-600/30 bg-purple-900/20 text-purple-400 hover:bg-purple-900/40',
                                blue: 'border-blue-600/30 bg-blue-900/20 text-blue-400 hover:bg-blue-900/40',
                                stone: 'border-stone-600/30 bg-stone-800/50 text-stone-400 hover:bg-stone-700/50',
                            };
                            return (
                                <Link
                                    key={action.title}
                                    href={action.href}
                                    className={`flex flex-col items-center gap-2 rounded-lg border p-3 transition ${colorClasses[action.color]}`}
                                >
                                    <action.icon className="h-6 w-6" />
                                    <span className="font-pixel text-xs">{action.title}</span>
                                </Link>
                            );
                        })}
                    </div>
                </div>

                {/* Leadership Section */}
                <div className="grid gap-4 md:grid-cols-2">
                    {/* Elder */}
                    <div className="rounded-xl border border-amber-600/30 bg-amber-900/10 p-4">
                        <div className="mb-3 flex items-center gap-2">
                            <Crown className="h-5 w-5 text-amber-400" />
                            <h3 className="font-pixel text-sm text-amber-400">Village Elder</h3>
                        </div>
                        {village.elder ? (
                            <div className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <div className="font-pixel text-lg text-stone-100">
                                            {village.elder.username}
                                            {village.elder.id === current_user_id && (
                                                <span className="ml-2 text-xs text-amber-400">(You)</span>
                                            )}
                                        </div>
                                        {village.elder.primary_title && (
                                            <div className="text-xs capitalize text-stone-500">{village.elder.primary_title}</div>
                                        )}
                                    </div>
                                </div>
                                {village.elder.legitimacy !== undefined && (
                                    <LegitimacyDisplay
                                        legitimacy={village.elder.legitimacy}
                                        roleName="Elder"
                                    />
                                )}
                            </div>
                        ) : (
                            <div className="text-stone-500">
                                <p className="font-pixel">Position Vacant</p>
                                <p className="mt-1 text-xs">No elder has been elected</p>
                            </div>
                        )}
                    </div>

                    {/* Roles Link */}
                    <Link
                        href={`/villages/${village.id}/roles`}
                        className="flex items-center justify-between rounded-xl border border-stone-600/30 bg-stone-800/30 p-4 transition hover:bg-stone-800/50"
                    >
                        <div className="flex items-center gap-3">
                            <Shield className="h-8 w-8 text-stone-400" />
                            <div>
                                <div className="font-pixel text-stone-200">Village Roles</div>
                                <div className="text-xs text-stone-500">View officials and positions</div>
                            </div>
                        </div>
                        <span className="text-stone-500">›</span>
                    </Link>
                </div>

                {/* Migration / Settlement */}
                {!is_resident && (
                    <div>
                        {has_pending_request ? (
                            <div className="rounded-xl border border-amber-600/30 bg-amber-900/10 p-4">
                                <div className="flex items-center gap-3">
                                    <Loader2 className="h-5 w-5 animate-spin text-amber-400" />
                                    <div>
                                        <p className="font-pixel text-amber-300">Migration Request Pending</p>
                                        <Link href="/migration" className="text-xs text-stone-400 hover:underline">
                                            View your request status
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        ) : can_migrate ? (
                            <button
                                onClick={handleRequestMigration}
                                disabled={loading}
                                className="flex w-full items-center justify-center gap-3 rounded-xl border-2 border-blue-500/50 bg-blue-900/20 p-4 transition hover:bg-blue-900/40 disabled:opacity-50"
                            >
                                {loading ? (
                                    <Loader2 className="h-6 w-6 animate-spin text-blue-400" />
                                ) : (
                                    <Home className="h-6 w-6 text-blue-400" />
                                )}
                                <div>
                                    <span className="font-pixel text-lg text-blue-300">
                                        {village.resident_count === 0 ? 'Settle Here' : 'Request to Settle'}
                                    </span>
                                    <p className="text-xs text-stone-400">
                                        {village.resident_count === 0
                                            ? 'Be the first resident of this village'
                                            : 'The village elder must approve your request'}
                                    </p>
                                </div>
                            </button>
                        ) : (
                            <div className="rounded-xl border border-stone-700 bg-stone-800/30 p-4 text-center">
                                <p className="font-pixel text-stone-500">Migration on Cooldown</p>
                                <p className="text-xs text-stone-600">You must wait before you can move again</p>
                            </div>
                        )}
                    </div>
                )}

                {/* Residents */}
                {village.resident_count > 0 && (
                    <div>
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="font-pixel text-sm text-stone-400">
                                Residents ({village.resident_count})
                            </h2>
                            <Link
                                href={`/villages/${village.id}/residents`}
                                className="text-xs text-amber-400 hover:underline"
                            >
                                View All
                            </Link>
                        </div>
                        <div className="grid gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                            {village.residents.slice(0, 8).map((resident) => (
                                <div
                                    key={resident.id}
                                    className="flex items-center gap-3 rounded-lg border border-stone-700 bg-stone-800/30 p-3"
                                >
                                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-stone-700">
                                        <Users className="h-5 w-5 text-stone-400" />
                                    </div>
                                    <div>
                                        <div className="font-pixel text-sm text-stone-200">
                                            {resident.username}
                                            {resident.id === current_user_id && (
                                                <span className="ml-1 text-xs text-green-400">(You)</span>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-1 text-xs text-stone-500">
                                            <Swords className="h-3 w-3" />
                                            Combat Lv. {resident.combat_level}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                        {village.resident_count > 8 && (
                            <p className="mt-2 text-center text-xs text-stone-500">
                                +{village.resident_count - 8} more residents
                            </p>
                        )}
                    </div>
                )}

                {/* Port indicator */}
                {village.biome === 'coastal' && (
                    <Link
                        href={`/villages/${village.id}/port`}
                        className="flex items-center gap-4 rounded-xl border border-blue-600/30 bg-blue-900/10 p-4 transition hover:bg-blue-900/20"
                    >
                        <Anchor className="h-8 w-8 text-blue-400" />
                        <div>
                            <div className="font-pixel text-blue-300">Harbor</div>
                            <div className="text-xs text-stone-400">Book passage to distant lands</div>
                        </div>
                    </Link>
                )}
            </div>
        </AppLayout>
    );
}

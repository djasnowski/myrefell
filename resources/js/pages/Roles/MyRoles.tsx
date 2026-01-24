import { Head, router, usePage } from '@inertiajs/react';
import {
    Award,
    Briefcase,
    Clock,
    Coins,
    Crown,
    Gavel,
    Heart,
    Loader2,
    LogOut,
    MapPin,
    Scale,
    Shield,
    ShieldCheck,
    Swords,
    User,
    Users,
    Wallet,
    Wrench,
} from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface UserRole {
    id: number;
    role_id: number;
    name: string;
    slug: string;
    icon: string;
    description: string;
    location_type: string;
    location_id: number;
    location_name: string;
    permissions: string[];
    bonuses: Record<string, number>;
    salary: number;
    tier: number;
    status: string;
    appointed_at: string;
    expires_at: string | null;
    total_salary_earned: number;
}

interface PageProps {
    roles: UserRole[];
    player: {
        id: number;
        username: string;
    };
    [key: string]: unknown;
}

const iconMap: Record<string, typeof Crown> = {
    crown: Crown,
    shield: Shield,
    gavel: Gavel,
    wrench: Wrench,
    heart: Heart,
    swords: Swords,
    scale: Scale,
    briefcase: Briefcase,
    wallet: Wallet,
    award: Award,
    users: Users,
    user: User,
    shieldcheck: ShieldCheck,
};

const tierBadgeColors: Record<number, string> = {
    1: 'bg-stone-700 text-stone-300',
    2: 'bg-blue-800 text-blue-200',
    3: 'bg-purple-800 text-purple-200',
    4: 'bg-amber-800 text-amber-200',
    5: 'bg-red-800 text-red-200',
};

const locationTypeColors: Record<string, string> = {
    village: 'text-green-300',
    castle: 'text-purple-300',
    kingdom: 'text-amber-300',
};

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function MyRoleCard({
    role,
    onResign,
    resignLoading,
}: {
    role: UserRole;
    onResign: (playerRoleId: number) => void;
    resignLoading: number | null;
}) {
    const Icon = iconMap[role.icon.toLowerCase()] || Crown;

    return (
        <div className="rounded-xl border-2 border-amber-500/50 bg-amber-900/20 p-4">
            <div className="mb-3 flex items-start justify-between">
                <div className="flex items-center gap-2">
                    <div className="rounded-lg bg-stone-800/50 p-2">
                        <Icon className="h-5 w-5 text-amber-300" />
                    </div>
                    <div>
                        <h3 className="font-pixel text-sm text-amber-300">{role.name}</h3>
                        <div className="flex items-center gap-2">
                            <span className={`rounded px-1.5 py-0.5 font-pixel text-[10px] ${tierBadgeColors[role.tier] || tierBadgeColors[1]}`}>
                                Tier {role.tier}
                            </span>
                        </div>
                    </div>
                </div>
                <div className="rounded-lg bg-green-800/50 px-2 py-1">
                    <span className="font-pixel text-[10px] text-green-300">Active</span>
                </div>
            </div>

            <p className="mb-3 text-sm text-stone-300">{role.description}</p>

            {/* Location */}
            <div className="mb-3 flex items-center gap-2 rounded-lg bg-stone-800/50 p-2">
                <MapPin className="h-4 w-4 text-stone-400" />
                <span className={`font-pixel text-xs ${locationTypeColors[role.location_type] || 'text-stone-300'}`}>
                    {role.location_name}
                </span>
                <span className="font-pixel text-[10px] text-stone-500">({role.location_type})</span>
            </div>

            {/* Stats */}
            <div className="mb-3 grid grid-cols-2 gap-2 rounded-lg bg-stone-800/50 p-2">
                <div className="flex items-center gap-1">
                    <Coins className="h-3 w-3 text-amber-400" />
                    <span className="font-pixel text-[10px] text-stone-400">Salary:</span>
                    <span className="font-pixel text-xs text-amber-300">{role.salary}g/day</span>
                </div>
                <div className="flex items-center gap-1">
                    <Coins className="h-3 w-3 text-green-400" />
                    <span className="font-pixel text-[10px] text-stone-400">Earned:</span>
                    <span className="font-pixel text-xs text-green-300">{role.total_salary_earned}g</span>
                </div>
                <div className="col-span-2 flex items-center gap-1">
                    <Clock className="h-3 w-3 text-stone-400" />
                    <span className="font-pixel text-[10px] text-stone-400">Since {formatDate(role.appointed_at)}</span>
                </div>
            </div>

            {/* Permissions */}
            {role.permissions.length > 0 && (
                <div className="mb-3 rounded-lg bg-stone-900/50 p-2">
                    <div className="mb-1 flex items-center gap-1">
                        <Shield className="h-3 w-3 text-blue-400" />
                        <span className="font-pixel text-[10px] text-stone-400">Permissions:</span>
                    </div>
                    <div className="flex flex-wrap gap-1">
                        {role.permissions.map((perm) => (
                            <span key={perm} className="rounded bg-blue-900/50 px-1.5 py-0.5 font-pixel text-[10px] text-blue-300">
                                {perm.replace(/_/g, ' ')}
                            </span>
                        ))}
                    </div>
                </div>
            )}

            <button
                onClick={() => onResign(role.id)}
                disabled={resignLoading === role.id}
                className="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-red-600/50 bg-red-900/20 px-4 py-2 font-pixel text-xs text-red-300 transition hover:bg-red-800/30 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {resignLoading === role.id ? (
                    <Loader2 className="h-4 w-4 animate-spin" />
                ) : (
                    <>
                        <LogOut className="h-4 w-4" />
                        Resign
                    </>
                )}
            </button>
        </div>
    );
}

export default function MyRoles() {
    const { roles } = usePage<PageProps>().props;

    const [resignLoading, setResignLoading] = useState<number | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'My Roles', href: '#' },
    ];

    const handleResign = (playerRoleId: number) => {
        setResignLoading(playerRoleId);
        router.post(
            `/roles/${playerRoleId}/resign`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setResignLoading(null),
            }
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Roles" />
            <div className="flex h-full flex-1 flex-col overflow-hidden p-4">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">My Roles</h1>
                        <p className="font-pixel text-sm text-stone-400">Official positions you hold</p>
                    </div>
                    <div className="rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2">
                        <span className="font-pixel text-xs text-stone-400">Total Roles:</span>
                        <span className="ml-2 font-pixel text-sm text-amber-300">{roles.length}</span>
                    </div>
                </div>

                {/* Roles Grid */}
                {roles.length > 0 ? (
                    <div className="grid gap-4 overflow-y-auto md:grid-cols-2 lg:grid-cols-3">
                        {roles.map((role) => (
                            <MyRoleCard key={role.id} role={role} onResign={handleResign} resignLoading={resignLoading} />
                        ))}
                    </div>
                ) : (
                    <div className="flex flex-1 items-center justify-center py-12">
                        <div className="text-center">
                            <Crown className="mx-auto mb-3 h-16 w-16 text-stone-600" />
                            <p className="font-pixel text-base text-stone-500">No roles held</p>
                            <p className="font-pixel text-xs text-stone-600">
                                Visit a village, castle, or kingdom to view available positions.
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

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

interface RoleHolder {
    player_role_id: number;
    user_id: number;
    username: string;
    appointed_at: string;
    expires_at: string | null;
    total_salary_earned: number;
}

interface RoleNpc {
    id: number;
    name: string;
    description: string;
    icon: string;
}

interface Role {
    id: number;
    name: string;
    slug: string;
    icon: string;
    description: string;
    location_type: string;
    permissions: string[];
    bonuses: Record<string, number>;
    salary: number;
    tier: number;
    is_elected: boolean;
    max_per_location: number;
    holder: RoleHolder | null;
    npc: RoleNpc | null;
    is_vacant: boolean;
}

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
    location_type: string;
    location_id: number;
    location_name: string;
    roles: Role[];
    user_roles: UserRole[];
    user_roles_here: UserRole[];
    population: number;
    can_self_appoint: boolean;
    self_appoint_threshold: number;
    player: {
        id: number;
        username: string;
        gold: number;
        title_tier: number;
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

const tierColors: Record<number, string> = {
    1: 'border-stone-500/50 bg-stone-800/50',
    2: 'border-blue-500/50 bg-blue-900/20',
    3: 'border-purple-500/50 bg-purple-900/20',
    4: 'border-amber-500/50 bg-amber-900/20',
    5: 'border-red-500/50 bg-red-900/20',
};

const tierBadgeColors: Record<number, string> = {
    1: 'bg-stone-700 text-stone-300',
    2: 'bg-blue-800 text-blue-200',
    3: 'bg-purple-800 text-purple-200',
    4: 'bg-amber-800 text-amber-200',
    5: 'bg-red-800 text-red-200',
};

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function RoleCard({
    role,
    currentUserId,
    userRoleHere,
    userHasAnyRole,
    canSelfAppoint,
    locationType,
    locationId,
    onResign,
    onClaim,
    resignLoading,
    claimLoading,
}: {
    role: Role;
    currentUserId: number;
    userRoleHere: UserRole | undefined;
    userHasAnyRole: boolean;
    canSelfAppoint: boolean;
    locationType: string;
    locationId: number;
    onResign: (playerRoleId: number) => void;
    onClaim: (roleId: number) => void;
    resignLoading: number | null;
    claimLoading: number | null;
}) {
    const Icon = iconMap[role.icon.toLowerCase()] || Crown;
    const isCurrentUser = role.holder?.user_id === currentUserId;
    const isUserRole = userRoleHere?.role_id === role.id;
    const canClaim = role.is_vacant && canSelfAppoint && !userHasAnyRole;

    return (
        <div className={`rounded-xl border-2 ${tierColors[role.tier] || tierColors[1]} p-4`}>
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
                            {role.is_elected && <span className="font-pixel text-[10px] text-stone-400">Elected</span>}
                        </div>
                    </div>
                </div>
                {role.is_vacant ? (
                    <div className="rounded-lg bg-stone-700/50 px-2 py-1">
                        <span className="font-pixel text-[10px] text-stone-400">Vacant</span>
                    </div>
                ) : role.holder ? (
                    <div className="rounded-lg bg-green-800/50 px-2 py-1">
                        <span className="font-pixel text-[10px] text-green-300">Filled</span>
                    </div>
                ) : null}
            </div>

            <p className="mb-3 text-sm text-stone-300">{role.description}</p>

            {/* Role Benefits */}
            <div className="mb-3 grid grid-cols-2 gap-2 rounded-lg bg-stone-800/50 p-2">
                <div className="flex items-center gap-1">
                    <Coins className="h-3 w-3 text-amber-400" />
                    <span className="font-pixel text-[10px] text-stone-400">Salary:</span>
                    <span className="font-pixel text-xs text-amber-300">{role.salary}g/day</span>
                </div>
                {role.permissions.length > 0 && (
                    <div className="flex items-center gap-1">
                        <Shield className="h-3 w-3 text-blue-400" />
                        <span className="font-pixel text-xs text-blue-300">{role.permissions.length} perms</span>
                    </div>
                )}
            </div>

            {/* Current Holder */}
            {role.holder ? (
                <div className="rounded-lg border border-stone-600/30 bg-stone-900/50 p-3">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <User className="h-4 w-4 text-stone-400" />
                            <span className={`font-pixel text-sm ${isCurrentUser ? 'text-green-300' : 'text-stone-200'}`}>
                                {role.holder.username}
                                {isCurrentUser && ' (You)'}
                            </span>
                        </div>
                    </div>
                    <div className="mt-2 flex items-center gap-3 text-[10px] text-stone-400">
                        <div className="flex items-center gap-1">
                            <Clock className="h-3 w-3" />
                            <span>Since {formatDate(role.holder.appointed_at)}</span>
                        </div>
                        {role.holder.total_salary_earned > 0 && (
                            <div className="flex items-center gap-1">
                                <Coins className="h-3 w-3" />
                                <span>{role.holder.total_salary_earned}g earned</span>
                            </div>
                        )}
                    </div>
                    {isUserRole && (
                        <button
                            onClick={() => onResign(userRoleHere!.id)}
                            disabled={resignLoading === userRoleHere!.id}
                            className="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border-2 border-red-600/50 bg-red-900/20 px-4 py-2 font-pixel text-xs text-red-300 transition hover:bg-red-800/30 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {resignLoading === userRoleHere!.id ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                <>
                                    <LogOut className="h-4 w-4" />
                                    Resign from Position
                                </>
                            )}
                        </button>
                    )}
                </div>
            ) : role.npc ? (
                <div className="rounded-lg border border-stone-600/30 bg-stone-900/50 p-3">
                    <div className="flex items-center gap-2">
                        <User className="h-4 w-4 text-stone-500" />
                        <div>
                            <span className="font-pixel text-sm text-stone-400">{role.npc.name}</span>
                            <span className="ml-2 font-pixel text-[10px] text-stone-500">(NPC)</span>
                        </div>
                    </div>
                    {role.npc.description && <p className="mt-1 text-xs text-stone-500">{role.npc.description}</p>}
                    {canClaim ? (
                        <button
                            onClick={() => onClaim(role.id)}
                            disabled={claimLoading === role.id}
                            className="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border-2 border-green-600/50 bg-green-900/20 px-4 py-2 font-pixel text-xs text-green-300 transition hover:bg-green-800/30 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {claimLoading === role.id ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                <>
                                    <Crown className="h-4 w-4" />
                                    Take Over Role
                                </>
                            )}
                        </button>
                    ) : (
                        <p className="mt-2 font-pixel text-[10px] text-stone-600">Election required to replace NPC</p>
                    )}
                </div>
            ) : (
                <div className="rounded-lg border border-dashed border-stone-600/30 bg-stone-900/30 p-3 text-center">
                    {canClaim ? (
                        <button
                            onClick={() => onClaim(role.id)}
                            disabled={claimLoading === role.id}
                            className="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-green-600/50 bg-green-900/20 px-4 py-2 font-pixel text-xs text-green-300 transition hover:bg-green-800/30 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {claimLoading === role.id ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                <>
                                    <Crown className="h-4 w-4" />
                                    Claim This Role
                                </>
                            )}
                        </button>
                    ) : (
                        <span className="font-pixel text-xs text-stone-500">Election required to fill</span>
                    )}
                </div>
            )}
        </div>
    );
}

export default function RolesIndex() {
    const { location_type, location_id, location_name, roles, user_roles, user_roles_here, population, can_self_appoint, self_appoint_threshold, player } = usePage<PageProps>().props;

    const [resignLoading, setResignLoading] = useState<number | null>(null);
    const [claimLoading, setClaimLoading] = useState<number | null>(null);

    const locationTypeDisplay = location_type.charAt(0).toUpperCase() + location_type.slice(1);
    const userHasAnyRole = user_roles.length > 0;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: location_name, href: `/${location_type}s/${location_id}` },
        { title: 'Roles', href: '#' },
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

    const handleClaim = (roleId: number) => {
        setClaimLoading(roleId);
        router.post(
            '/roles/claim',
            {
                role_id: roleId,
                location_type: location_type,
                location_id: location_id,
            },
            {
                preserveScroll: true,
                onFinish: () => setClaimLoading(null),
            }
        );
    };

    // Group roles by tier for display
    const rolesByTier = roles.reduce(
        (acc, role) => {
            const tier = role.tier;
            if (!acc[tier]) acc[tier] = [];
            acc[tier].push(role);
            return acc;
        },
        {} as Record<number, Role[]>
    );

    const sortedTiers = Object.keys(rolesByTier)
        .map(Number)
        .sort((a, b) => b - a);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Roles - ${location_name}`} />
            <div className="flex h-full flex-1 flex-col overflow-hidden p-4">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">{locationTypeDisplay} Roles</h1>
                        <p className="font-pixel text-sm text-stone-400">Official positions at {location_name}</p>
                        <p className="font-pixel text-xs text-stone-500 mt-1">
                            Population: {population} {can_self_appoint
                                ? '- Vacant roles can be claimed'
                                : `- Election required (${self_appoint_threshold}+ residents)`}
                        </p>
                    </div>
                    <div className="flex items-center gap-4">
                        {user_roles_here.length > 0 && (
                            <div className="rounded-lg border-2 border-green-600/50 bg-green-900/20 px-4 py-2">
                                <span className="font-pixel text-xs text-stone-400">Your Roles:</span>
                                <span className="ml-2 font-pixel text-sm text-green-300">{user_roles_here.length}</span>
                            </div>
                        )}
                    </div>
                </div>

                {/* Roles by Tier */}
                <div className="space-y-6 overflow-y-auto">
                    {sortedTiers.map((tier) => (
                        <div key={tier}>
                            <h2 className="mb-3 font-pixel text-lg text-stone-300">
                                {tier === 5 && 'Leadership'}
                                {tier === 4 && 'Senior Officials'}
                                {tier === 3 && 'Officials'}
                                {tier === 2 && 'Officers'}
                                {tier === 1 && 'Staff'}
                            </h2>
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                {rolesByTier[tier].map((role) => {
                                    const userRoleHere = user_roles_here.find((ur) => ur.role_id === role.id);
                                    return (
                                        <RoleCard
                                            key={role.id}
                                            role={role}
                                            currentUserId={player.id}
                                            userRoleHere={userRoleHere}
                                            userHasAnyRole={userHasAnyRole}
                                            canSelfAppoint={can_self_appoint}
                                            locationType={location_type}
                                            locationId={location_id}
                                            onResign={handleResign}
                                            onClaim={handleClaim}
                                            resignLoading={resignLoading}
                                            claimLoading={claimLoading}
                                        />
                                    );
                                })}
                            </div>
                        </div>
                    ))}
                </div>

                {roles.length === 0 && (
                    <div className="flex flex-1 items-center justify-center py-12">
                        <div className="text-center">
                            <Crown className="mx-auto mb-3 h-16 w-16 text-stone-600" />
                            <p className="font-pixel text-base text-stone-500">No roles available</p>
                            <p className="font-pixel text-xs text-stone-600">This location has no official positions.</p>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

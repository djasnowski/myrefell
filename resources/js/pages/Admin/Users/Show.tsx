import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    Calendar,
    CheckCircle,
    Clock,
    Coins,
    Crown,
    Edit,
    Globe,
    Heart,
    History,
    Mail,
    MailCheck,
    Shield,
    ShieldOff,
    User,
    UserX,
    Zap,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin-layout';
import type { BreadcrumbItem, SharedData } from '@/types';
import { edit as editUser, index as usersIndex } from '@/actions/App/Http/Controllers/Admin/UserController';

interface Ban {
    id: number;
    reason: string;
    banned_at: string;
    banned_by: { id: number; username: string } | null;
    unbanned_at: string | null;
    unbanned_by: { id: number; username: string } | null;
    unban_reason: string | null;
    is_active: boolean;
}

interface UserData {
    id: number;
    username: string;
    email: string;
    is_admin: boolean;
    is_banned: boolean;
    banned_at: string | null;
    created_at: string;
    email_verified_at: string | null;
    registration_ip: string | null;
    last_login_ip: string | null;
    last_login_at: string | null;
    gender: string | null;
    social_class: string | null;
    gold: number;
    hp: number;
    max_hp: number;
    energy: number;
    max_energy: number;
    primary_title: string | null;
    home_village: { id: number; name: string } | null;
    bans: Ban[];
}

interface Props {
    user: UserData;
}

export default function Show({ user }: Props) {
    const { auth } = usePage<SharedData>().props;
    const [banReason, setBanReason] = useState('');
    const [unbanReason, setUnbanReason] = useState('');
    const [showBanForm, setShowBanForm] = useState(false);
    const [showUnbanForm, setShowUnbanForm] = useState(false);
    const [loading, setLoading] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Admin', href: '/admin' },
        { title: 'Users', href: '/admin/users' },
        { title: user.username, href: `/admin/users/${user.id}` },
    ];

    const formatDateTime = (dateStr: string) => {
        return new Date(dateStr).toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const canBan = !user.is_admin && !user.is_banned && auth.user.id !== user.id;
    const canUnban = user.is_banned;

    const handleBan = () => {
        if (!banReason.trim()) return;
        setLoading(true);
        router.post(
            `/admin/users/${user.id}/ban`,
            { reason: banReason },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                    setBanReason('');
                    setShowBanForm(false);
                },
                onFinish: () => setLoading(false),
            }
        );
    };

    const handleUnban = () => {
        setLoading(true);
        router.post(
            `/admin/users/${user.id}/unban`,
            { reason: unbanReason },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                    setUnbanReason('');
                    setShowUnbanForm(false);
                },
                onFinish: () => setLoading(false),
            }
        );
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`User: ${user.username}`} />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href={usersIndex.url()}>
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="size-4" />
                                Back
                            </Button>
                        </Link>
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-blue-900/30 p-2">
                                <User className="size-6 text-blue-400" />
                            </div>
                            <div>
                                <div className="flex items-center gap-2">
                                    <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                                        {user.username}
                                    </h1>
                                    {user.is_admin && (
                                        <Badge
                                            variant="secondary"
                                            className="bg-purple-900/30 text-purple-400"
                                        >
                                            <Shield className="size-3" />
                                            Admin
                                        </Badge>
                                    )}
                                    {user.is_banned && (
                                        <Badge
                                            variant="destructive"
                                            className="bg-red-900/30 text-red-400"
                                        >
                                            <UserX className="size-3" />
                                            Banned
                                        </Badge>
                                    )}
                                </div>
                                <p className="text-sm text-stone-400">{user.email}</p>
                            </div>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Link href={editUser.url(user.id)}>
                            <Button variant="outline" className="border-stone-700">
                                <Edit className="size-4" />
                                Edit
                            </Button>
                        </Link>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* User Details */}
                    <div className="space-y-6 lg:col-span-2">
                        {/* Account Info */}
                        <Card className="border-stone-800 bg-stone-900/50">
                            <CardHeader>
                                <CardTitle className="text-stone-100">
                                    Account Information
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <dt className="flex items-center gap-2 text-sm text-stone-400">
                                            <Mail className="size-4" />
                                            Email
                                        </dt>
                                        <dd className="mt-1 text-stone-100">{user.email}</dd>
                                    </div>
                                    <div>
                                        <dt className="flex items-center gap-2 text-sm text-stone-400">
                                            <MailCheck className="size-4" />
                                            Email Verified
                                        </dt>
                                        <dd className="mt-1 text-stone-100">
                                            {user.email_verified_at
                                                ? formatDateTime(user.email_verified_at)
                                                : 'Not verified'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="flex items-center gap-2 text-sm text-stone-400">
                                            <Calendar className="size-4" />
                                            Joined
                                        </dt>
                                        <dd className="mt-1 text-stone-100">
                                            {formatDateTime(user.created_at)}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="flex items-center gap-2 text-sm text-stone-400">
                                            <Crown className="size-4" />
                                            Title
                                        </dt>
                                        <dd className="mt-1 capitalize text-stone-100">
                                            {user.primary_title || 'Peasant'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="flex items-center gap-2 text-sm text-stone-400">
                                            <Globe className="size-4" />
                                            Registration IP
                                        </dt>
                                        <dd className="mt-1 font-mono text-sm text-stone-100">
                                            {user.registration_ip || 'Not recorded'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="flex items-center gap-2 text-sm text-stone-400">
                                            <Globe className="size-4" />
                                            Last Login IP
                                        </dt>
                                        <dd className="mt-1 font-mono text-sm text-stone-100">
                                            {user.last_login_ip || 'Never logged in'}
                                        </dd>
                                    </div>
                                    <div className="sm:col-span-2">
                                        <dt className="flex items-center gap-2 text-sm text-stone-400">
                                            <Clock className="size-4" />
                                            Last Login
                                        </dt>
                                        <dd className="mt-1 text-stone-100">
                                            {user.last_login_at
                                                ? formatDateTime(user.last_login_at)
                                                : 'Never logged in'}
                                        </dd>
                                    </div>
                                </dl>
                            </CardContent>
                        </Card>

                        {/* Game Stats */}
                        <Card className="border-stone-800 bg-stone-900/50">
                            <CardHeader>
                                <CardTitle className="text-stone-100">Game Stats</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                    <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-3">
                                        <dt className="flex items-center gap-2 text-sm text-stone-400">
                                            <Heart className="size-4 text-red-400" />
                                            Health
                                        </dt>
                                        <dd className="mt-1 text-lg font-semibold text-stone-100">
                                            {user.hp}/{user.max_hp}
                                        </dd>
                                    </div>
                                    <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-3">
                                        <dt className="flex items-center gap-2 text-sm text-stone-400">
                                            <Zap className="size-4 text-blue-400" />
                                            Energy
                                        </dt>
                                        <dd className="mt-1 text-lg font-semibold text-stone-100">
                                            {user.energy}/{user.max_energy}
                                        </dd>
                                    </div>
                                    <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-3">
                                        <dt className="flex items-center gap-2 text-sm text-stone-400">
                                            <Coins className="size-4 text-amber-400" />
                                            Gold
                                        </dt>
                                        <dd className="mt-1 text-lg font-semibold text-stone-100">
                                            {user.gold.toLocaleString()}
                                        </dd>
                                    </div>
                                    <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-3">
                                        <dt className="flex items-center gap-2 text-sm text-stone-400">
                                            <User className="size-4 text-green-400" />
                                            Class
                                        </dt>
                                        <dd className="mt-1 text-lg font-semibold capitalize text-stone-100">
                                            {user.social_class || 'Serf'}
                                        </dd>
                                    </div>
                                </dl>
                            </CardContent>
                        </Card>

                        {/* Ban History */}
                        <Card className="border-stone-800 bg-stone-900/50">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-stone-100">
                                    <History className="size-5" />
                                    Ban History
                                </CardTitle>
                                <CardDescription className="text-stone-400">
                                    {user.bans.length} total{' '}
                                    {user.bans.length === 1 ? 'ban' : 'bans'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {user.bans.length === 0 ? (
                                    <p className="text-center text-stone-500">
                                        No ban history
                                    </p>
                                ) : (
                                    <div className="space-y-4">
                                        {user.bans.map((ban) => (
                                            <div
                                                key={ban.id}
                                                className={`rounded-lg border p-4 ${
                                                    ban.is_active
                                                        ? 'border-red-900/50 bg-red-900/10'
                                                        : 'border-stone-800 bg-stone-900/30'
                                                }`}
                                            >
                                                <div className="flex items-start justify-between">
                                                    <div>
                                                        <div className="flex items-center gap-2">
                                                            {ban.is_active ? (
                                                                <Badge
                                                                    variant="destructive"
                                                                    className="bg-red-900/30 text-red-400"
                                                                >
                                                                    Active
                                                                </Badge>
                                                            ) : (
                                                                <Badge
                                                                    variant="secondary"
                                                                    className="bg-stone-800 text-stone-400"
                                                                >
                                                                    Lifted
                                                                </Badge>
                                                            )}
                                                            <span className="text-sm text-stone-400">
                                                                {formatDateTime(ban.banned_at)}
                                                            </span>
                                                        </div>
                                                        <p className="mt-2 text-stone-100">
                                                            {ban.reason}
                                                        </p>
                                                        <p className="mt-1 text-xs text-stone-500">
                                                            Banned by:{' '}
                                                            {ban.banned_by?.username || 'Unknown'}
                                                        </p>
                                                    </div>
                                                </div>
                                                {ban.unbanned_at && (
                                                    <div className="mt-3 border-t border-stone-800 pt-3">
                                                        <p className="text-sm text-green-400">
                                                            <CheckCircle className="mr-1 inline size-4" />
                                                            Unbanned{' '}
                                                            {formatDateTime(ban.unbanned_at)} by{' '}
                                                            {ban.unbanned_by?.username || 'Unknown'}
                                                        </p>
                                                        {ban.unban_reason && (
                                                            <p className="mt-1 text-sm text-stone-400">
                                                                Reason: {ban.unban_reason}
                                                            </p>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Actions Sidebar */}
                    <div className="space-y-6">
                        {/* Ban/Unban Actions */}
                        <Card className="border-stone-800 bg-stone-900/50">
                            <CardHeader>
                                <CardTitle className="text-stone-100">Actions</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {canBan && !showBanForm && (
                                    <Button
                                        variant="destructive"
                                        className="w-full"
                                        onClick={() => setShowBanForm(true)}
                                    >
                                        <Ban className="size-4" />
                                        Ban User
                                    </Button>
                                )}

                                {showBanForm && (
                                    <div className="space-y-3 rounded-lg border border-red-900/50 bg-red-900/10 p-4">
                                        <h4 className="font-medium text-red-400">
                                            Ban {user.username}
                                        </h4>
                                        <Textarea
                                            placeholder="Reason for ban (required)..."
                                            value={banReason}
                                            onChange={(e) => setBanReason(e.target.value)}
                                            className="border-stone-700 bg-stone-900/50"
                                            rows={3}
                                        />
                                        <div className="flex gap-2">
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                onClick={handleBan}
                                                disabled={loading || !banReason.trim()}
                                            >
                                                {loading ? 'Banning...' : 'Confirm Ban'}
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => {
                                                    setShowBanForm(false);
                                                    setBanReason('');
                                                }}
                                                className="border-stone-700"
                                            >
                                                Cancel
                                            </Button>
                                        </div>
                                    </div>
                                )}

                                {canUnban && !showUnbanForm && (
                                    <Button
                                        variant="outline"
                                        className="w-full border-green-900 text-green-400 hover:bg-green-900/20"
                                        onClick={() => setShowUnbanForm(true)}
                                    >
                                        <ShieldOff className="size-4" />
                                        Unban User
                                    </Button>
                                )}

                                {showUnbanForm && (
                                    <div className="space-y-3 rounded-lg border border-green-900/50 bg-green-900/10 p-4">
                                        <h4 className="font-medium text-green-400">
                                            Unban {user.username}
                                        </h4>
                                        <Textarea
                                            placeholder="Reason for unban (optional)..."
                                            value={unbanReason}
                                            onChange={(e) => setUnbanReason(e.target.value)}
                                            className="border-stone-700 bg-stone-900/50"
                                            rows={3}
                                        />
                                        <div className="flex gap-2">
                                            <Button
                                                variant="default"
                                                size="sm"
                                                onClick={handleUnban}
                                                disabled={loading}
                                                className="bg-green-600 hover:bg-green-700"
                                            >
                                                {loading ? 'Unbanning...' : 'Confirm Unban'}
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => {
                                                    setShowUnbanForm(false);
                                                    setUnbanReason('');
                                                }}
                                                className="border-stone-700"
                                            >
                                                Cancel
                                            </Button>
                                        </div>
                                    </div>
                                )}

                                {user.is_admin && user.id !== auth.user.id && (
                                    <p className="text-center text-sm text-stone-500">
                                        Cannot ban other administrators
                                    </p>
                                )}

                                {user.id === auth.user.id && (
                                    <p className="text-center text-sm text-stone-500">
                                        Cannot ban yourself
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        {/* Quick Stats */}
                        <Card className="border-stone-800 bg-stone-900/50">
                            <CardHeader>
                                <CardTitle className="text-stone-100">Quick Info</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl className="space-y-3">
                                    <div className="flex justify-between">
                                        <dt className="text-stone-400">Gender</dt>
                                        <dd className="capitalize text-stone-100">
                                            {user.gender || 'Unknown'}
                                        </dd>
                                    </div>
                                    <div className="flex justify-between">
                                        <dt className="text-stone-400">Home Village</dt>
                                        <dd className="text-stone-100">
                                            {user.home_village?.name || 'None'}
                                        </dd>
                                    </div>
                                    <div className="flex justify-between">
                                        <dt className="text-stone-400">Total Bans</dt>
                                        <dd className="text-stone-100">{user.bans.length}</dd>
                                    </div>
                                </dl>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}

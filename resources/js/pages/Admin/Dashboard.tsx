import { Head } from '@inertiajs/react';
import {
    Activity,
    Shield,
    UserCheck,
    UserPlus,
    Users,
    UserX,
} from 'lucide-react';
import {
    CartesianGrid,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AdminLayout from '@/layouts/admin-layout';
import type { BreadcrumbItem } from '@/types';

interface Props {
    stats: {
        totalUsers: number;
        activeUsers: number;
        newUsersToday: number;
        bannedUsers: number;
        adminUsers: number;
    };
    registrationTrend: Array<{ date: string; count: number }>;
    activeUsersTrend: Array<{ date: string; count: number }>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Dashboard', href: '/admin' },
];

export default function Dashboard({
    stats,
    registrationTrend,
    activeUsersTrend,
}: Props) {
    const statCards = [
        {
            title: 'Total Users',
            value: stats.totalUsers.toLocaleString(),
            description: 'All registered accounts',
            icon: Users,
            color: 'text-blue-400',
            bgColor: 'bg-blue-900/20',
        },
        {
            title: 'Active Users',
            value: stats.activeUsers.toLocaleString(),
            description: 'Last 7 days',
            icon: UserCheck,
            color: 'text-green-400',
            bgColor: 'bg-green-900/20',
        },
        {
            title: 'New Today',
            value: stats.newUsersToday.toLocaleString(),
            description: 'Registered today',
            icon: UserPlus,
            color: 'text-amber-400',
            bgColor: 'bg-amber-900/20',
        },
        {
            title: 'Banned Users',
            value: stats.bannedUsers.toLocaleString(),
            description: 'Currently banned',
            icon: UserX,
            color: 'text-red-400',
            bgColor: 'bg-red-900/20',
        },
        {
            title: 'Admins',
            value: stats.adminUsers.toLocaleString(),
            description: 'Admin accounts',
            icon: Shield,
            color: 'text-purple-400',
            bgColor: 'bg-purple-900/20',
        },
    ];

    const formatDate = (dateStr: string) => {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Dashboard" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="rounded-lg bg-purple-900/30 p-2">
                        <Shield className="size-6 text-purple-400" />
                    </div>
                    <div>
                        <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                            Admin Dashboard
                        </h1>
                        <p className="text-sm text-stone-400">
                            User management and analytics
                        </p>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    {statCards.map((stat) => (
                        <Card
                            key={stat.title}
                            className="border-stone-800 bg-stone-900/50"
                        >
                            <CardHeader className="pb-2">
                                <div className="flex items-center justify-between">
                                    <CardDescription className="text-stone-400">
                                        {stat.title}
                                    </CardDescription>
                                    <div className={`rounded-lg ${stat.bgColor} p-2`}>
                                        <stat.icon className={`size-4 ${stat.color}`} />
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-stone-100">
                                    {stat.value}
                                </div>
                                <p className="text-xs text-stone-500">
                                    {stat.description}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Charts */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Registration Trend Chart */}
                    <Card className="border-stone-800 bg-stone-900/50">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-stone-100">
                                <UserPlus className="size-5 text-amber-400" />
                                User Registrations
                            </CardTitle>
                            <CardDescription className="text-stone-400">
                                New registrations over the last 30 days
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="h-[300px]">
                                <ResponsiveContainer width="100%" height="100%">
                                    <LineChart data={registrationTrend}>
                                        <CartesianGrid
                                            strokeDasharray="3 3"
                                            stroke="#44403c"
                                        />
                                        <XAxis
                                            dataKey="date"
                                            tickFormatter={formatDate}
                                            stroke="#a8a29e"
                                            fontSize={12}
                                        />
                                        <YAxis
                                            stroke="#a8a29e"
                                            fontSize={12}
                                            allowDecimals={false}
                                        />
                                        <Tooltip
                                            contentStyle={{
                                                backgroundColor: '#1c1917',
                                                border: '1px solid #44403c',
                                                borderRadius: '8px',
                                            }}
                                            labelStyle={{ color: '#e7e5e4' }}
                                            itemStyle={{ color: '#fbbf24' }}
                                            labelFormatter={formatDate}
                                        />
                                        <Line
                                            type="monotone"
                                            dataKey="count"
                                            name="New Users"
                                            stroke="#fbbf24"
                                            strokeWidth={2}
                                            dot={false}
                                            activeDot={{ r: 4 }}
                                        />
                                    </LineChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Active Users Trend Chart */}
                    <Card className="border-stone-800 bg-stone-900/50">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-stone-100">
                                <Activity className="size-5 text-green-400" />
                                Active Users
                            </CardTitle>
                            <CardDescription className="text-stone-400">
                                Daily active users over the last 30 days
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="h-[300px]">
                                <ResponsiveContainer width="100%" height="100%">
                                    <LineChart data={activeUsersTrend}>
                                        <CartesianGrid
                                            strokeDasharray="3 3"
                                            stroke="#44403c"
                                        />
                                        <XAxis
                                            dataKey="date"
                                            tickFormatter={formatDate}
                                            stroke="#a8a29e"
                                            fontSize={12}
                                        />
                                        <YAxis
                                            stroke="#a8a29e"
                                            fontSize={12}
                                            allowDecimals={false}
                                        />
                                        <Tooltip
                                            contentStyle={{
                                                backgroundColor: '#1c1917',
                                                border: '1px solid #44403c',
                                                borderRadius: '8px',
                                            }}
                                            labelStyle={{ color: '#e7e5e4' }}
                                            itemStyle={{ color: '#4ade80' }}
                                            labelFormatter={formatDate}
                                        />
                                        <Line
                                            type="monotone"
                                            dataKey="count"
                                            name="Active Users"
                                            stroke="#4ade80"
                                            strokeWidth={2}
                                            dot={false}
                                            activeDot={{ r: 4 }}
                                        />
                                    </LineChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}

import { Head, Link, router } from "@inertiajs/react";
import { formatDistanceToNow } from "date-fns";
import {
    AlertTriangle,
    ArrowLeft,
    Ban,
    CheckCircle,
    Clock,
    Edit,
    ShieldAlert,
    User,
    UserX,
} from "lucide-react";
import { useState } from "react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import AdminLayout from "@/layouts/admin-layout";
import { cn } from "@/lib/utils";
import type { BreadcrumbItem } from "@/types";

interface TabActivity {
    id: number;
    tab_id: string;
    route: string;
    method: string;
    is_new_tab: boolean;
    previous_tab_id: string | null;
    ip_address: string | null;
    created_at: string;
}

interface Stats {
    total_requests: number;
    new_tab_switches: number;
    xp_tab_switches: number;
    non_xp_tab_switches: number;
    unique_tabs: number;
    suspicious_percentage: number;
    requests_per_hour: number;
}

interface UserData {
    id: number;
    username: string;
    email: string;
    is_banned: boolean;
    flagged_at: string | null;
}

interface Props {
    user: UserData;
    stats: {
        last_hour: Stats;
        last_24h: Stats;
        all_time: Stats;
    };
    recentActivity: TabActivity[];
}

export default function Show({ user, stats, recentActivity }: Props) {
    const [loading, setLoading] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Admin", href: "/admin" },
        { title: "Suspicious Activity", href: "/admin/suspicious-activity" },
        { title: user.username, href: `/admin/suspicious-activity/${user.id}` },
    ];

    const handleClearFlag = () => {
        setLoading(true);
        router.post(
            `/admin/suspicious-activity/${user.id}/clear`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => router.reload(),
                onFinish: () => setLoading(false),
            },
        );
    };

    const formatDateTime = (dateStr: string) => {
        return new Date(dateStr).toLocaleString("en-US", {
            month: "short",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
        });
    };

    // Group activities by tab_id to show patterns
    const tabColors: Record<string, string> = {};
    const colorPalette = [
        "bg-blue-500/20 text-blue-400",
        "bg-green-500/20 text-green-400",
        "bg-purple-500/20 text-purple-400",
        "bg-amber-500/20 text-amber-400",
        "bg-cyan-500/20 text-cyan-400",
        "bg-pink-500/20 text-pink-400",
        "bg-orange-500/20 text-orange-400",
        "bg-teal-500/20 text-teal-400",
    ];
    let colorIndex = 0;

    const getTabColor = (tabId: string) => {
        if (!tabColors[tabId]) {
            tabColors[tabId] = colorPalette[colorIndex % colorPalette.length];
            colorIndex++;
        }
        return tabColors[tabId];
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Suspicious Activity: ${user.username}`} />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/admin/suspicious-activity">
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="size-4" />
                                Back
                            </Button>
                        </Link>
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-red-900/30 p-2">
                                <ShieldAlert className="size-6 text-red-400" />
                            </div>
                            <div>
                                <div className="flex items-center gap-2">
                                    <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                                        {user.username}
                                    </h1>
                                    {user.is_banned && (
                                        <Badge
                                            variant="destructive"
                                            className="bg-red-900/30 text-red-400"
                                        >
                                            <UserX className="size-3" />
                                            Banned
                                        </Badge>
                                    )}
                                    {user.flagged_at && (
                                        <Badge
                                            variant="secondary"
                                            className="bg-amber-900/30 text-amber-400"
                                        >
                                            <AlertTriangle className="size-3" />
                                            Flagged
                                        </Badge>
                                    )}
                                </div>
                                <p className="text-sm text-stone-400">{user.email}</p>
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <Link href={`/admin/users/${user.id}`}>
                            <Button variant="outline" className="border-stone-700">
                                <User className="size-4" />
                                View Profile
                            </Button>
                        </Link>
                        <Link href={`/admin/users/${user.id}/edit`}>
                            <Button variant="outline" className="border-stone-700">
                                <Edit className="size-4" />
                                Edit
                            </Button>
                        </Link>
                        {!user.is_banned && (
                            <Link href={`/admin/users/${user.id}`}>
                                <Button
                                    variant="destructive"
                                    className="bg-red-900/30 text-red-400 hover:bg-red-900/50"
                                >
                                    <Ban className="size-4" />
                                    Ban User
                                </Button>
                            </Link>
                        )}
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="border-stone-800 bg-stone-900/50">
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center gap-2 text-sm text-stone-400">
                                <Clock className="size-4" />
                                Last Hour
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <div className="text-2xl font-bold text-stone-100">
                                        {stats.last_hour.total_requests}
                                    </div>
                                    <div className="text-xs text-stone-500">Total Requests</div>
                                </div>
                                <div>
                                    <div
                                        className={cn(
                                            "text-2xl font-bold",
                                            stats.last_hour.suspicious_percentage >= 30
                                                ? "text-red-400"
                                                : stats.last_hour.suspicious_percentage >= 20
                                                  ? "text-amber-400"
                                                  : "text-green-400",
                                        )}
                                    >
                                        {stats.last_hour.suspicious_percentage}%
                                    </div>
                                    <div className="text-xs text-stone-500">Suspicious</div>
                                </div>
                                <div>
                                    <div className="text-xl font-semibold text-amber-400">
                                        {stats.last_hour.new_tab_switches}
                                    </div>
                                    <div className="text-xs text-stone-500">Tab Switches</div>
                                </div>
                                <div>
                                    <div className="text-xl font-semibold text-stone-100">
                                        {stats.last_hour.unique_tabs}
                                    </div>
                                    <div className="text-xs text-stone-500">Unique Tabs</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-amber-900/50 bg-amber-900/10">
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center gap-2 text-sm text-amber-400">
                                <AlertTriangle className="size-4" />
                                Last 24 Hours
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <div className="text-2xl font-bold text-stone-100">
                                        {stats.last_24h.total_requests.toLocaleString()}
                                    </div>
                                    <div className="text-xs text-stone-500">Total Requests</div>
                                </div>
                                <div>
                                    <div
                                        className={cn(
                                            "text-2xl font-bold",
                                            stats.last_24h.suspicious_percentage >= 30
                                                ? "text-red-400"
                                                : stats.last_24h.suspicious_percentage >= 20
                                                  ? "text-amber-400"
                                                  : "text-green-400",
                                        )}
                                    >
                                        {stats.last_24h.suspicious_percentage}%
                                    </div>
                                    <div className="text-xs text-stone-500">Suspicious</div>
                                </div>
                                <div>
                                    <div className="text-xl font-semibold text-amber-400">
                                        {stats.last_24h.new_tab_switches.toLocaleString()}
                                    </div>
                                    <div className="text-xs text-stone-500">Tab Switches</div>
                                </div>
                                <div>
                                    <div className="text-xl font-semibold text-stone-100">
                                        {stats.last_24h.unique_tabs}
                                    </div>
                                    <div className="text-xs text-stone-500">Unique Tabs</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-stone-800 bg-stone-900/50">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm text-stone-400">All Time</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <div className="text-2xl font-bold text-stone-100">
                                        {stats.all_time.total_requests.toLocaleString()}
                                    </div>
                                    <div className="text-xs text-stone-500">Total Requests</div>
                                </div>
                                <div>
                                    <div
                                        className={cn(
                                            "text-2xl font-bold",
                                            stats.all_time.suspicious_percentage >= 30
                                                ? "text-red-400"
                                                : stats.all_time.suspicious_percentage >= 20
                                                  ? "text-amber-400"
                                                  : "text-green-400",
                                        )}
                                    >
                                        {stats.all_time.suspicious_percentage}%
                                    </div>
                                    <div className="text-xs text-stone-500">Suspicious</div>
                                </div>
                                <div>
                                    <div className="text-xl font-semibold text-amber-400">
                                        {stats.all_time.new_tab_switches.toLocaleString()}
                                    </div>
                                    <div className="text-xs text-stone-500">Tab Switches</div>
                                </div>
                                <div>
                                    <div className="text-xl font-semibold text-stone-100">
                                        {stats.all_time.unique_tabs}
                                    </div>
                                    <div className="text-xs text-stone-500">Unique Tabs</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Actions */}
                {user.flagged_at && (
                    <Card className="border-stone-800 bg-stone-900/50">
                        <CardHeader>
                            <CardTitle className="text-stone-100">Admin Actions</CardTitle>
                        </CardHeader>
                        <CardContent className="flex gap-4">
                            <Button
                                variant="outline"
                                className="border-green-900 text-green-400 hover:bg-green-900/20"
                                onClick={handleClearFlag}
                                disabled={loading}
                            >
                                <CheckCircle className="size-4" />
                                {loading ? "Clearing..." : "Clear Flag"}
                            </Button>
                            <p className="text-sm text-stone-500">
                                Flagged{" "}
                                {formatDistanceToNow(new Date(user.flagged_at), {
                                    addSuffix: true,
                                })}
                            </p>
                        </CardContent>
                    </Card>
                )}

                {/* Recent Activity */}
                <Card className="border-stone-800 bg-stone-900/50">
                    <CardHeader>
                        <CardTitle className="text-stone-100">Recent Tab Activity</CardTitle>
                        <CardDescription className="text-stone-400">
                            Last {recentActivity.length} actions (color-coded by tab)
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {recentActivity.length === 0 ? (
                            <p className="py-8 text-center text-stone-500">
                                No activity logged yet
                            </p>
                        ) : (
                            <div className="max-h-[600px] overflow-y-auto">
                                <table className="w-full text-sm">
                                    <thead className="sticky top-0 bg-stone-900">
                                        <tr className="border-b border-stone-800 text-left text-stone-400">
                                            <th className="p-2">Time</th>
                                            <th className="p-2">Tab</th>
                                            <th className="p-2">Route</th>
                                            <th className="p-2">Method</th>
                                            <th className="p-2">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {recentActivity.map((activity, index) => {
                                            // Calculate seconds since next entry (entries are in desc order)
                                            const nextActivity = recentActivity[index + 1];
                                            const secondsDiff = nextActivity
                                                ? Math.round(
                                                      (new Date(activity.created_at).getTime() -
                                                          new Date(
                                                              nextActivity.created_at,
                                                          ).getTime()) /
                                                          1000,
                                                  )
                                                : null;

                                            return (
                                                <tr
                                                    key={activity.id}
                                                    className={cn(
                                                        "border-b border-stone-800/50",
                                                        activity.is_new_tab && "bg-red-900/10",
                                                    )}
                                                >
                                                    <td className="p-2">
                                                        <div className="flex items-center gap-3">
                                                            <span className="text-stone-400">
                                                                {formatDateTime(
                                                                    activity.created_at,
                                                                )}
                                                            </span>
                                                            {secondsDiff !== null && (
                                                                <span
                                                                    className={cn(
                                                                        "text-sm font-medium",
                                                                        secondsDiff <= 1
                                                                            ? "text-red-400"
                                                                            : secondsDiff <= 3
                                                                              ? "text-amber-400"
                                                                              : "text-stone-500",
                                                                    )}
                                                                >
                                                                    +{secondsDiff}s
                                                                </span>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="p-2">
                                                        <span
                                                            className={cn(
                                                                "rounded px-2 py-0.5 font-mono text-xs",
                                                                getTabColor(activity.tab_id),
                                                            )}
                                                        >
                                                            {activity.tab_id.slice(0, 8)}...
                                                        </span>
                                                    </td>
                                                    <td className="max-w-[300px] truncate p-2 font-mono text-stone-100">
                                                        {activity.route}
                                                    </td>
                                                    <td className="p-2">
                                                        <Badge
                                                            variant="secondary"
                                                            className="bg-stone-800 text-stone-300"
                                                        >
                                                            {activity.method}
                                                        </Badge>
                                                    </td>
                                                    <td className="p-2">
                                                        {activity.is_new_tab ? (
                                                            <Badge
                                                                variant="destructive"
                                                                className="bg-red-900/30 text-red-400"
                                                            >
                                                                Tab Switch
                                                            </Badge>
                                                        ) : (
                                                            <Badge
                                                                variant="secondary"
                                                                className="bg-stone-800 text-stone-500"
                                                            >
                                                                Normal
                                                            </Badge>
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}

import { Head, Link } from "@inertiajs/react";
import { formatDistanceToNow } from "date-fns";
import { AlertTriangle, Eye, ShieldAlert, UserX } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import AdminLayout from "@/layouts/admin-layout";
import { cn } from "@/lib/utils";
import type { BreadcrumbItem } from "@/types";

interface FlaggedUser {
    id: number;
    username: string;
    email: string;
    flagged_at: string;
    is_banned: boolean;
    stats: {
        total_requests: number;
        new_tab_switches: number;
        xp_tab_switches: number;
        non_xp_tab_switches: number;
        unique_tabs: number;
        suspicious_percentage: number;
        requests_per_hour: number;
    };
}

interface Props {
    flaggedUsers: FlaggedUser[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Admin", href: "/admin" },
    { title: "Suspicious Activity", href: "/admin/suspicious-activity" },
];

export default function Index({ flaggedUsers }: Props) {
    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Suspicious Activity" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="rounded-lg bg-red-900/30 p-2">
                        <ShieldAlert className="size-6 text-red-400" />
                    </div>
                    <div>
                        <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                            Suspicious Activity
                        </h1>
                        <p className="text-sm text-stone-400">
                            Users flagged for potential multi-tab abuse
                        </p>
                    </div>
                </div>

                {/* Stats Summary */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <Card className="border-stone-800 bg-stone-900/50">
                        <CardHeader className="pb-2">
                            <CardDescription className="text-stone-400">
                                Total Flagged
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-stone-100">
                                {flaggedUsers.length}
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-stone-800 bg-stone-900/50">
                        <CardHeader className="pb-2">
                            <CardDescription className="text-stone-400">
                                Already Banned
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-400">
                                {flaggedUsers.filter((u) => u.is_banned).length}
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-stone-800 bg-stone-900/50">
                        <CardHeader className="pb-2">
                            <CardDescription className="text-stone-400">
                                Needs Review
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-amber-400">
                                {flaggedUsers.filter((u) => !u.is_banned).length}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Flagged Users List */}
                <Card className="border-stone-800 bg-stone-900/50">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-stone-100">
                            <AlertTriangle className="size-5 text-amber-400" />
                            Flagged Users
                        </CardTitle>
                        <CardDescription className="text-stone-400">
                            Users with suspicious multi-tab activity patterns
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {flaggedUsers.length === 0 ? (
                            <p className="py-8 text-center text-stone-500">
                                No users have been flagged for suspicious activity
                            </p>
                        ) : (
                            <div className="space-y-3">
                                {flaggedUsers.map((user) => (
                                    <div
                                        key={user.id}
                                        className={cn(
                                            "flex items-center justify-between rounded-lg border p-4",
                                            user.is_banned
                                                ? "border-stone-800 bg-stone-900/30 opacity-60"
                                                : "border-amber-900/50 bg-amber-900/10",
                                        )}
                                    >
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2">
                                                <span className="font-medium text-stone-100">
                                                    {user.username}
                                                </span>
                                                {user.is_banned && (
                                                    <Badge
                                                        variant="destructive"
                                                        className="bg-red-900/30 text-red-400"
                                                    >
                                                        <UserX className="mr-1 size-3" />
                                                        Banned
                                                    </Badge>
                                                )}
                                            </div>
                                            <p className="text-sm text-stone-400">{user.email}</p>
                                            <p className="mt-1 text-xs text-stone-500">
                                                Flagged{" "}
                                                {formatDistanceToNow(new Date(user.flagged_at), {
                                                    addSuffix: true,
                                                })}
                                            </p>
                                        </div>

                                        <div className="flex items-center gap-6">
                                            {/* Stats */}
                                            <div className="grid grid-cols-5 gap-4 text-center">
                                                <div>
                                                    <div className="text-lg font-semibold text-stone-100">
                                                        {user.stats.total_requests.toLocaleString()}
                                                    </div>
                                                    <div className="text-xs text-stone-500">
                                                        Requests (24h)
                                                    </div>
                                                </div>
                                                <div>
                                                    <div className="text-lg font-semibold text-cyan-400">
                                                        {user.stats.requests_per_hour.toLocaleString()}
                                                    </div>
                                                    <div className="text-xs text-stone-500">
                                                        Reqs/Hour
                                                    </div>
                                                </div>
                                                <div>
                                                    <div className="text-lg font-semibold text-red-400">
                                                        {user.stats.xp_tab_switches.toLocaleString()}
                                                    </div>
                                                    <div className="text-xs text-stone-500">
                                                        XP Switches
                                                    </div>
                                                </div>
                                                <div>
                                                    <div className="text-lg font-semibold text-amber-400">
                                                        {user.stats.non_xp_tab_switches.toLocaleString()}
                                                    </div>
                                                    <div className="text-xs text-stone-500">
                                                        Other Switches
                                                    </div>
                                                </div>
                                                <div>
                                                    <div
                                                        className={cn(
                                                            "text-lg font-semibold",
                                                            user.stats.suspicious_percentage >= 30
                                                                ? "text-red-400"
                                                                : user.stats
                                                                        .suspicious_percentage >= 20
                                                                  ? "text-amber-400"
                                                                  : "text-stone-100",
                                                        )}
                                                    >
                                                        {user.stats.suspicious_percentage}%
                                                    </div>
                                                    <div className="text-xs text-stone-500">
                                                        Suspicious
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Actions */}
                                            <Link href={`/admin/suspicious-activity/${user.id}`}>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="border-stone-700"
                                                >
                                                    <Eye className="size-4" />
                                                    View Details
                                                </Button>
                                            </Link>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}

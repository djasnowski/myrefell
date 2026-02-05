import { Head, Link } from "@inertiajs/react";
import { formatDistanceToNow } from "date-fns";
import { CheckCircle, MessageSquare, Scroll, UserX } from "lucide-react";
import { show as showUser } from "@/actions/App/Http/Controllers/Admin/UserController";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import AdminLayout from "@/layouts/admin-layout";
import type { BreadcrumbItem } from "@/types";

interface Appeal {
    id: number;
    user_id: number;
    username: string;
    reason: string;
    appeal_text: string;
    appeal_submitted_at: string;
    banned_at: string;
    banned_by: string;
    is_active: boolean;
    unbanned_at: string | null;
}

interface Props {
    appeals: Appeal[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Admin", href: "/admin" },
    { title: "Appeals", href: "/admin/appeals" },
];

export default function Index({ appeals }: Props) {
    const activeAppeals = appeals.filter((a) => a.is_active);
    const resolvedAppeals = appeals.filter((a) => !a.is_active);

    const formatDate = (dateStr: string) => {
        return new Date(dateStr).toLocaleDateString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Ban Appeals" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="rounded-lg bg-amber-900/30 p-2">
                        <Scroll className="size-6 text-amber-400" />
                    </div>
                    <div>
                        <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                            Ban Appeals
                        </h1>
                        <p className="text-sm text-stone-400">
                            {activeAppeals.length} pending, {resolvedAppeals.length} resolved
                        </p>
                    </div>
                </div>

                {/* Active Appeals */}
                <Card className="border-stone-800 bg-stone-900/50">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-stone-100">
                            <MessageSquare className="size-5 text-amber-400" />
                            Pending Appeals
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {activeAppeals.length === 0 ? (
                            <p className="py-8 text-center text-stone-500">No pending appeals</p>
                        ) : (
                            <div className="space-y-4">
                                {activeAppeals.map((appeal) => (
                                    <Link
                                        key={appeal.id}
                                        href={showUser.url(appeal.user_id)}
                                        className="block"
                                    >
                                        <div className="rounded-lg border border-amber-900/50 bg-amber-900/10 p-4 hover:bg-amber-900/20 transition">
                                            <div className="flex items-start justify-between mb-3">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-medium text-stone-100">
                                                        {appeal.username}
                                                    </span>
                                                    <Badge
                                                        variant="destructive"
                                                        className="bg-red-900/30 text-red-400"
                                                    >
                                                        <UserX className="size-3 mr-1" />
                                                        Banned
                                                    </Badge>
                                                </div>
                                                <span className="text-xs text-stone-500">
                                                    {formatDistanceToNow(
                                                        new Date(appeal.appeal_submitted_at),
                                                        { addSuffix: true },
                                                    )}
                                                </span>
                                            </div>
                                            <div className="mb-3">
                                                <p className="text-xs text-stone-500 uppercase mb-1">
                                                    Ban Reason
                                                </p>
                                                <p className="text-sm text-stone-400">
                                                    {appeal.reason}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-xs text-amber-500 uppercase mb-1">
                                                    Appeal
                                                </p>
                                                <p className="text-sm text-stone-300 whitespace-pre-wrap">
                                                    {appeal.appeal_text}
                                                </p>
                                            </div>
                                            <div className="mt-3 pt-3 border-t border-stone-800 flex items-center gap-4 text-xs text-stone-500">
                                                <span>Banned {formatDate(appeal.banned_at)}</span>
                                                <span>by {appeal.banned_by}</span>
                                            </div>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Resolved Appeals */}
                {resolvedAppeals.length > 0 && (
                    <Card className="border-stone-800 bg-stone-900/50">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-stone-100">
                                <CheckCircle className="size-5 text-green-400" />
                                Resolved Appeals
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {resolvedAppeals.map((appeal) => (
                                    <Link
                                        key={appeal.id}
                                        href={showUser.url(appeal.user_id)}
                                        className="block"
                                    >
                                        <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-4 hover:bg-stone-800/50 transition opacity-70">
                                            <div className="flex items-start justify-between mb-3">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-medium text-stone-100">
                                                        {appeal.username}
                                                    </span>
                                                    <Badge
                                                        variant="secondary"
                                                        className="bg-green-900/30 text-green-400"
                                                    >
                                                        <CheckCircle className="size-3 mr-1" />
                                                        Unbanned
                                                    </Badge>
                                                </div>
                                                <span className="text-xs text-stone-500">
                                                    {formatDistanceToNow(
                                                        new Date(appeal.appeal_submitted_at),
                                                        { addSuffix: true },
                                                    )}
                                                </span>
                                            </div>
                                            <div className="mb-3">
                                                <p className="text-xs text-stone-500 uppercase mb-1">
                                                    Ban Reason
                                                </p>
                                                <p className="text-sm text-stone-400">
                                                    {appeal.reason}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-xs text-stone-500 uppercase mb-1">
                                                    Appeal
                                                </p>
                                                <p className="text-sm text-stone-400 whitespace-pre-wrap">
                                                    {appeal.appeal_text}
                                                </p>
                                            </div>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AdminLayout>
    );
}

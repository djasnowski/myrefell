import { Head, Link } from "@inertiajs/react";
import { formatDistanceToNow } from "date-fns";
import { Bird, ChevronDown, ChevronUp, Coins, Mail, MailOpen } from "lucide-react";
import { useState } from "react";
import { show as showUser } from "@/actions/App/Http/Controllers/Admin/UserController";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import AdminLayout from "@/layouts/admin-layout";
import type { BreadcrumbItem } from "@/types";

interface MailItem {
    id: number;
    sender_id: number;
    sender_username: string;
    recipient_id: number;
    recipient_username: string;
    subject: string;
    body: string;
    is_read: boolean;
    is_carrier_pigeon: boolean;
    gold_cost: number;
    is_deleted_by_sender: boolean;
    is_deleted_by_recipient: boolean;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedMails {
    data: MailItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

interface Props {
    mails: PaginatedMails;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Admin", href: "/admin" },
    { title: "Mail", href: "/admin/mail" },
];

export default function Index({ mails }: Props) {
    const [expandedId, setExpandedId] = useState<number | null>(null);

    const carrierPigeonCount = mails.data.filter((m) => m.is_carrier_pigeon).length;

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
            <Head title="Mail" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="rounded-lg bg-amber-900/30 p-2">
                        <Mail className="size-6 text-amber-400" />
                    </div>
                    <div>
                        <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                            Player Mail
                        </h1>
                        <p className="text-sm text-stone-400">
                            {mails.total} total mails, {carrierPigeonCount} carrier pigeons on this
                            page
                        </p>
                    </div>
                </div>

                {/* Mail List */}
                <Card className="border-stone-800 bg-stone-900/50">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-stone-100">
                            <Mail className="size-5 text-amber-400" />
                            All Mail
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {mails.data.length === 0 ? (
                            <p className="py-8 text-center text-stone-500">No mail found</p>
                        ) : (
                            <div className="space-y-2">
                                {mails.data.map((mail) => (
                                    <div key={mail.id}>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setExpandedId(
                                                    expandedId === mail.id ? null : mail.id,
                                                )
                                            }
                                            className="w-full rounded-lg border border-stone-800 bg-stone-900/30 p-4 text-left transition hover:bg-stone-800/50"
                                        >
                                            <div className="flex items-start justify-between gap-4">
                                                <div className="min-w-0 flex-1">
                                                    <div className="mb-1 flex flex-wrap items-center gap-2">
                                                        <Link
                                                            href={showUser.url(mail.sender_id)}
                                                            className="font-medium text-stone-100 hover:text-amber-400"
                                                            onClick={(e) => e.stopPropagation()}
                                                        >
                                                            {mail.sender_username}
                                                        </Link>
                                                        <span className="text-stone-500">
                                                            &rarr;
                                                        </span>
                                                        <Link
                                                            href={showUser.url(mail.recipient_id)}
                                                            className="font-medium text-stone-100 hover:text-amber-400"
                                                            onClick={(e) => e.stopPropagation()}
                                                        >
                                                            {mail.recipient_username}
                                                        </Link>

                                                        {mail.is_carrier_pigeon && (
                                                            <Badge
                                                                variant="secondary"
                                                                className="bg-sky-900/30 text-sky-400"
                                                            >
                                                                <Bird className="mr-1 size-3" />
                                                                Pigeon
                                                            </Badge>
                                                        )}

                                                        {mail.is_read ? (
                                                            <MailOpen className="size-4 text-stone-600" />
                                                        ) : (
                                                            <Badge
                                                                variant="secondary"
                                                                className="bg-amber-900/30 text-amber-400"
                                                            >
                                                                Unread
                                                            </Badge>
                                                        )}

                                                        {(mail.is_deleted_by_sender ||
                                                            mail.is_deleted_by_recipient) && (
                                                            <Badge
                                                                variant="secondary"
                                                                className="bg-red-900/30 text-red-400"
                                                            >
                                                                {mail.is_deleted_by_sender &&
                                                                mail.is_deleted_by_recipient
                                                                    ? "Deleted by both"
                                                                    : mail.is_deleted_by_sender
                                                                      ? "Deleted by sender"
                                                                      : "Deleted by recipient"}
                                                            </Badge>
                                                        )}
                                                    </div>

                                                    <p className="text-sm font-medium text-stone-300">
                                                        {mail.subject}
                                                    </p>

                                                    {expandedId !== mail.id && (
                                                        <p className="mt-1 truncate text-sm text-stone-500">
                                                            {mail.body}
                                                        </p>
                                                    )}
                                                </div>

                                                <div className="flex shrink-0 items-center gap-3">
                                                    {mail.gold_cost > 0 && (
                                                        <span className="flex items-center gap-1 text-xs text-amber-400">
                                                            <Coins className="size-3" />
                                                            {mail.gold_cost}
                                                        </span>
                                                    )}
                                                    <span className="text-xs text-stone-500">
                                                        {formatDistanceToNow(
                                                            new Date(mail.created_at),
                                                            { addSuffix: true },
                                                        )}
                                                    </span>
                                                    {expandedId === mail.id ? (
                                                        <ChevronUp className="size-4 text-stone-500" />
                                                    ) : (
                                                        <ChevronDown className="size-4 text-stone-500" />
                                                    )}
                                                </div>
                                            </div>
                                        </button>

                                        {expandedId === mail.id && (
                                            <div className="mx-4 rounded-b-lg border border-t-0 border-stone-800 bg-stone-950/50 p-4">
                                                <p className="mb-2 text-xs uppercase text-stone-500">
                                                    Full Message
                                                </p>
                                                <p className="whitespace-pre-wrap text-sm text-stone-300">
                                                    {mail.body}
                                                </p>
                                                <div className="mt-3 flex items-center gap-4 border-t border-stone-800 pt-3 text-xs text-stone-500">
                                                    <span>Sent {formatDate(mail.created_at)}</span>
                                                    <span>Cost: {mail.gold_cost} gold</span>
                                                    {mail.is_carrier_pigeon && (
                                                        <span>Via carrier pigeon</span>
                                                    )}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Pagination */}
                {mails.last_page > 1 && (
                    <div className="flex items-center justify-center gap-1">
                        {mails.links.map((link, index) => (
                            <span key={index}>
                                {link.url ? (
                                    <Link
                                        href={link.url}
                                        className={`rounded px-3 py-1.5 text-sm transition ${
                                            link.active
                                                ? "bg-amber-900/50 font-medium text-amber-400"
                                                : "text-stone-400 hover:bg-stone-800 hover:text-stone-200"
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        preserveScroll
                                    />
                                ) : (
                                    <span
                                        className="rounded px-3 py-1.5 text-sm text-stone-600"
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                )}
                            </span>
                        ))}
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}

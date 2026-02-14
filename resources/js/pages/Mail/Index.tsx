import { Head, router, usePage } from "@inertiajs/react";
import {
    Bird,
    ChevronDown,
    ChevronUp,
    Clock,
    Coins,
    Inbox,
    Loader2,
    Mail,
    Reply,
    Send,
    Trash2,
    X,
} from "lucide-react";
import { useEffect, useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { gameToast } from "@/components/ui/game-toast";
import type { BreadcrumbItem } from "@/types";

interface MailUser {
    id: number;
    username: string;
}

interface MailItem {
    id: number;
    sender?: MailUser;
    recipient?: MailUser;
    subject: string;
    body: string;
    is_read: boolean;
    is_carrier_pigeon: boolean;
    gold_cost: number;
    created_at: string;
}

interface PaginatedMails {
    data: MailItem[];
    current_page: number;
    last_page: number;
    total: number;
}

interface PageProps {
    inbox: PaginatedMails;
    sent: PaginatedMails;
    tab: string;
    selected_mail?: MailItem;
    unread_count: number;
    player_gold: number;
    mail_cost: number;
    flash: {
        success?: string;
        error?: string;
    };
    [key: string]: unknown;
}

function formatDate(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) {
        return "Just now";
    }
    if (diffMins < 60) {
        return `${diffMins}m ago`;
    }
    if (diffHours < 24) {
        return `${diffHours}h ago`;
    }
    if (diffDays < 7) {
        return `${diffDays}d ago`;
    }
    return date.toLocaleDateString("en-US", { month: "short", day: "numeric" });
}

function MailRow({
    mail,
    type,
    isExpanded,
    onToggle,
    onDelete,
    deleteLoading,
    mailCost,
    playerGold,
    onReply,
    replyLoading,
}: {
    mail: MailItem;
    type: "inbox" | "sent";
    isExpanded: boolean;
    onToggle: () => void;
    onDelete: (id: number) => void;
    deleteLoading: number | null;
    mailCost: number;
    playerGold: number;
    onReply: (recipient: string, subject: string, body: string) => void;
    replyLoading: boolean;
}) {
    const isUnread = type === "inbox" && !mail.is_read;
    const otherUser = type === "inbox" ? mail.sender : mail.recipient;
    const [showReply, setShowReply] = useState(false);
    const [replyBody, setReplyBody] = useState("");

    return (
        <div
            className={`rounded-lg border transition ${
                isUnread
                    ? "border-amber-600/50 bg-amber-900/15"
                    : "border-stone-700/50 bg-stone-800/30 hover:bg-stone-800/50"
            }`}
        >
            <button onClick={onToggle} className="flex w-full items-center gap-3 p-3 text-left">
                {/* Unread indicator */}
                <div className="flex w-2 flex-shrink-0 justify-center">
                    {isUnread && <span className="h-2 w-2 rounded-full bg-amber-400" />}
                </div>

                {/* Pigeon icon */}
                <div className="flex w-5 flex-shrink-0 justify-center">
                    {mail.is_carrier_pigeon && <Bird className="h-3.5 w-3.5 text-amber-500/70" />}
                </div>

                {/* Sender/Recipient */}
                <span
                    className={`w-24 flex-shrink-0 truncate font-pixel text-xs ${
                        isUnread ? "font-bold text-amber-200" : "text-stone-300"
                    }`}
                >
                    {otherUser?.username ?? "Unknown"}
                </span>

                {/* Subject */}
                <span
                    className={`min-w-0 flex-1 truncate font-pixel text-xs ${
                        isUnread ? "font-bold text-stone-200" : "text-stone-400"
                    }`}
                >
                    {mail.subject}
                </span>

                {/* Date */}
                <span className="flex flex-shrink-0 items-center gap-1 font-pixel text-[10px] text-stone-500">
                    <Clock className="h-2.5 w-2.5" />
                    {formatDate(mail.created_at)}
                </span>

                {/* Expand icon */}
                {isExpanded ? (
                    <ChevronUp className="h-3.5 w-3.5 flex-shrink-0 text-stone-500" />
                ) : (
                    <ChevronDown className="h-3.5 w-3.5 flex-shrink-0 text-stone-500" />
                )}
            </button>

            {/* Expanded body */}
            {isExpanded && (
                <div className="border-t border-stone-700/50 px-3 pb-3 pt-2">
                    <div className="mb-2 flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <span className="font-pixel text-[10px] text-stone-500">
                                {type === "inbox" ? "From:" : "To:"}{" "}
                                <span className="text-stone-300">
                                    {otherUser?.username ?? "Unknown"}
                                </span>
                            </span>
                            {mail.is_carrier_pigeon && (
                                <span className="flex items-center gap-1 rounded bg-amber-900/40 px-1.5 py-0.5 font-pixel text-[9px] text-amber-400">
                                    <Bird className="h-2.5 w-2.5" />
                                    {mail.gold_cost}g
                                </span>
                            )}
                        </div>
                        <div className="flex items-center gap-1">
                            {type === "inbox" && (
                                <button
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        setShowReply(!showReply);
                                    }}
                                    className="flex items-center gap-1 rounded px-2 py-1 font-pixel text-[10px] text-amber-400 transition hover:bg-amber-900/30"
                                >
                                    <Reply className="h-3 w-3" />
                                    Reply
                                </button>
                            )}
                            <button
                                onClick={(e) => {
                                    e.stopPropagation();
                                    onDelete(mail.id);
                                }}
                                disabled={deleteLoading === mail.id}
                                className="flex items-center gap-1 rounded px-2 py-1 font-pixel text-[10px] text-red-400 transition hover:bg-red-900/30"
                            >
                                {deleteLoading === mail.id ? (
                                    <Loader2 className="h-3 w-3 animate-spin" />
                                ) : (
                                    <>
                                        <Trash2 className="h-3 w-3" />
                                        Delete
                                    </>
                                )}
                            </button>
                        </div>
                    </div>
                    <div className="whitespace-pre-wrap rounded bg-stone-900/50 p-3 font-pixel text-xs leading-relaxed text-stone-300">
                        {mail.body}
                    </div>

                    {/* Inline reply */}
                    {showReply && type === "inbox" && (
                        <div className="mt-3 rounded-lg border border-stone-600/50 bg-stone-900/30 p-3">
                            <div className="mb-2 flex items-center gap-2">
                                <Reply className="h-3 w-3 text-amber-400" />
                                <span className="font-pixel text-[10px] text-stone-400">
                                    Reply to {otherUser?.username}
                                </span>
                                <span className="ml-auto flex items-center gap-1 font-pixel text-[9px] text-amber-400/70">
                                    <Bird className="h-2.5 w-2.5" />
                                    {mailCost}g
                                </span>
                            </div>
                            <textarea
                                value={replyBody}
                                onChange={(e) => setReplyBody(e.target.value)}
                                placeholder="Write your reply..."
                                maxLength={1000}
                                rows={3}
                                className="w-full resize-none rounded border border-stone-600/50 bg-stone-800/50 px-3 py-2 font-pixel text-xs leading-relaxed text-stone-200 placeholder-stone-500 outline-none focus:border-amber-500/50"
                            />
                            <div className="mt-2 flex items-center justify-between">
                                <span className="font-pixel text-[9px] text-stone-600">
                                    {replyBody.length}/1000
                                </span>
                                <div className="flex items-center gap-2">
                                    {playerGold < mailCost && (
                                        <span className="font-pixel text-[9px] text-red-400">
                                            Not enough gold!
                                        </span>
                                    )}
                                    <button
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            setShowReply(false);
                                            setReplyBody("");
                                        }}
                                        className="rounded px-2 py-1 font-pixel text-[10px] text-stone-400 hover:bg-stone-700/50"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            if (replyBody.trim() && otherUser) {
                                                const reSubject = mail.subject.startsWith("Re: ")
                                                    ? mail.subject
                                                    : `Re: ${mail.subject}`.slice(0, 100);
                                                onReply(
                                                    otherUser.username,
                                                    reSubject,
                                                    replyBody.trim(),
                                                );
                                            }
                                        }}
                                        disabled={
                                            !replyBody.trim() ||
                                            replyLoading ||
                                            playerGold < mailCost
                                        }
                                        className="flex items-center gap-1 rounded border border-amber-600/50 bg-amber-900/30 px-3 py-1 font-pixel text-[10px] text-amber-300 hover:bg-amber-800/40 disabled:opacity-40"
                                    >
                                        {replyLoading ? (
                                            <Loader2 className="h-3 w-3 animate-spin" />
                                        ) : (
                                            <>
                                                <Send className="h-3 w-3" />
                                                Send ({mailCost}g)
                                            </>
                                        )}
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

function ComposeForm({
    playerGold,
    mailCost,
    sending,
    onSend,
}: {
    playerGold: number;
    mailCost: number;
    sending: boolean;
    onSend: (recipient: string, subject: string, body: string) => void;
}) {
    const [recipient, setRecipient] = useState("");
    const [subject, setSubject] = useState("");
    const [body, setBody] = useState("");

    const canSend =
        recipient.trim() && subject.trim() && body.trim() && !sending && playerGold >= mailCost;

    return (
        <div className="space-y-4">
            {/* Recipient */}
            <div>
                <label className="mb-1 block font-pixel text-xs text-stone-400">Recipient</label>
                <input
                    type="text"
                    value={recipient}
                    onChange={(e) => setRecipient(e.target.value)}
                    placeholder="Enter player username..."
                    className="w-full rounded-lg border border-stone-600/50 bg-stone-800/50 px-3 py-2 font-pixel text-xs text-stone-200 placeholder-stone-500 outline-none focus:border-amber-500/50"
                />
            </div>

            {/* Subject */}
            <div>
                <label className="mb-1 block font-pixel text-xs text-stone-400">Subject</label>
                <input
                    type="text"
                    value={subject}
                    onChange={(e) => setSubject(e.target.value)}
                    placeholder="Mail subject..."
                    maxLength={100}
                    className="w-full rounded-lg border border-stone-600/50 bg-stone-800/50 px-3 py-2 font-pixel text-xs text-stone-200 placeholder-stone-500 outline-none focus:border-amber-500/50"
                />
                <span className="mt-0.5 block text-right font-pixel text-[9px] text-stone-600">
                    {subject.length}/100
                </span>
            </div>

            {/* Body */}
            <div>
                <label className="mb-1 block font-pixel text-xs text-stone-400">Message</label>
                <textarea
                    value={body}
                    onChange={(e) => setBody(e.target.value)}
                    placeholder="Write your message..."
                    maxLength={1000}
                    rows={6}
                    className="w-full resize-none rounded-lg border border-stone-600/50 bg-stone-800/50 px-3 py-2 font-pixel text-xs leading-relaxed text-stone-200 placeholder-stone-500 outline-none focus:border-amber-500/50"
                />
                <span className="mt-0.5 block text-right font-pixel text-[9px] text-stone-600">
                    {body.length}/1000
                </span>
            </div>

            {/* Cost notice */}
            <div className="flex items-center gap-1 font-pixel text-[10px] text-amber-400">
                <Bird className="h-3 w-3" />
                <Coins className="h-3 w-3" />
                Carrier pigeon — {mailCost}g
                {playerGold < mailCost && (
                    <span className="text-red-400">
                        {" "}
                        — Not enough gold! You have {playerGold}g.
                    </span>
                )}
            </div>

            {/* Send button */}
            <button
                onClick={() => {
                    if (canSend) {
                        onSend(recipient.trim(), subject.trim(), body.trim());
                    }
                }}
                disabled={!canSend}
                className="flex w-full items-center justify-center gap-2 rounded-lg border border-amber-600/50 bg-amber-900/30 px-4 py-2.5 font-pixel text-sm text-amber-300 transition hover:bg-amber-800/40 disabled:cursor-not-allowed disabled:opacity-40"
            >
                {sending ? (
                    <Loader2 className="h-4 w-4 animate-spin" />
                ) : (
                    <>
                        <Bird className="h-4 w-4" />
                        Send via Carrier Pigeon ({mailCost}g)
                    </>
                )}
            </button>
        </div>
    );
}

export default function MailIndex() {
    const { inbox, sent, tab, selected_mail, unread_count, player_gold, mail_cost, flash } =
        usePage<PageProps>().props;

    const [activeTab, setActiveTab] = useState(tab || "inbox");
    const [expandedMail, setExpandedMail] = useState<number | null>(selected_mail?.id ?? null);
    const [deleteLoading, setDeleteLoading] = useState<number | null>(null);
    const [sending, setSending] = useState(false);
    const [replyLoading, setReplyLoading] = useState(false);

    useEffect(() => {
        if (flash?.success) {
            gameToast.success(flash.success);
        }
        if (flash?.error) {
            gameToast.error(flash.error);
        }
    }, [flash?.success, flash?.error]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Mail", href: "/mail" },
    ];

    const handleDelete = (mailId: number) => {
        setDeleteLoading(mailId);
        router.post(
            `/mail/${mailId}/delete`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    setExpandedMail(null);
                    router.reload();
                },
                onFinish: () => {
                    setDeleteLoading(null);
                },
            },
        );
    };

    const handleSend = (recipient: string, subject: string, body: string) => {
        setSending(true);
        router.post(
            "/mail/send",
            {
                recipient_username: recipient,
                subject,
                body,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setActiveTab("sent");
                    router.reload();
                },
                onFinish: () => {
                    setSending(false);
                },
            },
        );
    };

    const handleReply = (recipient: string, subject: string, body: string) => {
        setReplyLoading(true);
        router.post(
            "/mail/send",
            {
                recipient_username: recipient,
                subject,
                body,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => {
                    setReplyLoading(false);
                },
            },
        );
    };

    const toggleMail = (mailId: number) => {
        if (expandedMail === mailId) {
            setExpandedMail(null);
        } else {
            setExpandedMail(mailId);
            // If expanding an inbox mail, visit the show route to mark as read
            const inboxMail = inbox.data.find((m) => m.id === mailId);
            if (inboxMail && !inboxMail.is_read) {
                router.get(`/mail/${mailId}`, {}, { preserveState: true, preserveScroll: true });
            }
        }
    };

    const tabs = [
        {
            key: "inbox",
            label: "Inbox",
            icon: Inbox,
            count: unread_count,
        },
        {
            key: "sent",
            label: "Sent",
            icon: Send,
            count: 0,
        },
        {
            key: "compose",
            label: "Compose",
            icon: Mail,
            count: 0,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mail" />
            <div className="flex h-full flex-1 flex-col overflow-hidden p-4">
                {/* Header */}
                <div className="mb-4">
                    <h1 className="font-pixel text-xl text-amber-400">Mail</h1>
                    <p className="font-pixel text-xs text-stone-400">
                        Send messages to other players across the realm
                    </p>
                </div>

                {/* Tab bar */}
                <div className="mb-4 flex gap-1 rounded-lg border border-stone-700/50 bg-stone-800/30 p-1">
                    {tabs.map((t) => {
                        const Icon = t.icon;
                        const isActive = activeTab === t.key;
                        return (
                            <button
                                key={t.key}
                                onClick={() => setActiveTab(t.key)}
                                className={`flex flex-1 items-center justify-center gap-2 rounded-md px-3 py-2 font-pixel text-xs transition ${
                                    isActive
                                        ? "bg-amber-900/40 text-amber-300"
                                        : "text-stone-400 hover:bg-stone-700/30 hover:text-stone-300"
                                }`}
                            >
                                <Icon className="h-3.5 w-3.5" />
                                {t.label}
                                {t.count > 0 && (
                                    <span className="flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 font-pixel text-[9px] text-white">
                                        {t.count}
                                    </span>
                                )}
                            </button>
                        );
                    })}
                </div>

                {/* Tab content */}
                <div className="-mx-1 flex-1 overflow-y-auto px-1">
                    {/* Inbox */}
                    {activeTab === "inbox" && (
                        <div className="space-y-2">
                            {inbox.data.length === 0 ? (
                                <div className="flex flex-col items-center justify-center py-12">
                                    <Inbox className="mb-2 h-10 w-10 text-stone-600" />
                                    <p className="font-pixel text-sm text-stone-500">
                                        Your inbox is empty
                                    </p>
                                    <p className="font-pixel text-[10px] text-stone-600">
                                        Messages from other players will appear here
                                    </p>
                                </div>
                            ) : (
                                inbox.data.map((mail) => (
                                    <MailRow
                                        key={mail.id}
                                        mail={mail}
                                        type="inbox"
                                        isExpanded={expandedMail === mail.id}
                                        onToggle={() => toggleMail(mail.id)}
                                        onDelete={handleDelete}
                                        deleteLoading={deleteLoading}
                                        mailCost={mail_cost}
                                        playerGold={player_gold}
                                        onReply={handleReply}
                                        replyLoading={replyLoading}
                                    />
                                ))
                            )}
                        </div>
                    )}

                    {/* Sent */}
                    {activeTab === "sent" && (
                        <div className="space-y-2">
                            {sent.data.length === 0 ? (
                                <div className="flex flex-col items-center justify-center py-12">
                                    <Send className="mb-2 h-10 w-10 text-stone-600" />
                                    <p className="font-pixel text-sm text-stone-500">
                                        No sent mail
                                    </p>
                                    <p className="font-pixel text-[10px] text-stone-600">
                                        Messages you send will appear here
                                    </p>
                                </div>
                            ) : (
                                sent.data.map((mail) => (
                                    <MailRow
                                        key={mail.id}
                                        mail={mail}
                                        type="sent"
                                        isExpanded={expandedMail === mail.id}
                                        onToggle={() =>
                                            setExpandedMail(
                                                expandedMail === mail.id ? null : mail.id,
                                            )
                                        }
                                        onDelete={handleDelete}
                                        deleteLoading={deleteLoading}
                                        mailCost={mail_cost}
                                        playerGold={player_gold}
                                        onReply={handleReply}
                                        replyLoading={replyLoading}
                                    />
                                ))
                            )}
                        </div>
                    )}

                    {/* Compose */}
                    {activeTab === "compose" && (
                        <div className="mx-auto max-w-xl">
                            <div className="rounded-xl border border-stone-700/50 bg-stone-800/30 p-4">
                                <div className="mb-4 flex items-center gap-2">
                                    <Mail className="h-4 w-4 text-amber-400" />
                                    <h2 className="font-pixel text-sm text-amber-300">
                                        New Message
                                    </h2>
                                    <div className="ml-auto flex items-center gap-1">
                                        <Coins className="h-3 w-3 text-amber-400" />
                                        <span className="font-pixel text-[10px] text-amber-400">
                                            {player_gold}g
                                        </span>
                                    </div>
                                </div>
                                <ComposeForm
                                    playerGold={player_gold}
                                    mailCost={mail_cost}
                                    sending={sending}
                                    onSend={handleSend}
                                />
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

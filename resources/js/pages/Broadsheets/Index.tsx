import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    Castle,
    Coins,
    Crown,
    Eye,
    Feather,
    Loader2,
    MessageSquare,
    Newspaper,
    ScrollText,
    ThumbsDown,
    ThumbsUp,
} from "lucide-react";
import { useEffect, useState } from "react";
import type { Descendant } from "slate";
import AppLayout from "@/layouts/app-layout";
import { gameToast } from "@/components/ui/game-toast";
import { locationPath } from "@/lib/utils";
import type { BreadcrumbItem } from "@/types";
import SlateEditor from "@/components/slate/SlateEditor";
import {
    EMPTY_EDITOR_VALUE,
    isEmptyContent,
    serializeToPlainText,
} from "@/components/slate/slate-utils";

interface Author {
    id: number;
    username: string;
}

interface BroadsheetItem {
    id: number;
    title: string;
    plain_text: string;
    location_name: string;
    view_count: number;
    endorse_count: number;
    denounce_count: number;
    comment_count: number;
    published_at: string;
    author: Author;
}

interface PaginatedBroadsheets {
    data: BroadsheetItem[];
    current_page: number;
    last_page: number;
    total: number;
    next_page_url: string | null;
    prev_page_url: string | null;
}

interface Location {
    type: string;
    id: number;
    name: string;
}

interface PageProps {
    local: PaginatedBroadsheets | null;
    barony: PaginatedBroadsheets | null;
    kingdom: PaginatedBroadsheets | null;
    tab: string;
    player_gold: number;
    publish_cost: number;
    has_published_today: boolean;
    can_publish_here: boolean;
    location: Location | null;
    barony_name: string | null;
    kingdom_name: string | null;
    flash: {
        success?: string;
        error?: string;
    };
    [key: string]: unknown;
}

function formatTimeAgo(dateString: string): string {
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

function BroadsheetCard({ broadsheet, baseUrl }: { broadsheet: BroadsheetItem; baseUrl: string }) {
    const preview =
        broadsheet.plain_text.length > 300
            ? broadsheet.plain_text.substring(0, 300) + "..."
            : broadsheet.plain_text;

    return (
        <Link
            href={`${baseUrl}/${broadsheet.id}`}
            className="block rounded-lg border border-stone-700/50 bg-stone-800/30 p-5 transition hover:border-amber-700/50 hover:bg-stone-800/50"
        >
            <div className="mb-2 flex items-start justify-between gap-2">
                <h3 className="font-pixel text-sm font-bold text-amber-200 sm:text-base">
                    {broadsheet.title}
                </h3>
                <span className="shrink-0 font-pixel text-[10px] text-stone-500">
                    {formatTimeAgo(broadsheet.published_at)}
                </span>
            </div>

            <div className="mb-2 flex items-center gap-2">
                <Link
                    href={`/players/${broadsheet.author.username}`}
                    className="font-pixel text-xs text-amber-400 hover:underline"
                    onClick={(e) => e.stopPropagation()}
                >
                    {broadsheet.author.username}
                </Link>
                <span className="font-pixel text-[10px] text-stone-500">
                    {broadsheet.location_name}
                </span>
            </div>

            <p className="mb-3 text-xs leading-relaxed text-stone-400 sm:text-sm">{preview}</p>

            <div className="flex items-center gap-3 text-stone-500">
                <span className="flex items-center gap-1 font-pixel text-[10px]">
                    <Eye className="h-3 w-3" />
                    {broadsheet.view_count}
                </span>
                <span className="flex items-center gap-1 font-pixel text-[10px] text-emerald-600">
                    <ThumbsUp className="h-3 w-3" />
                    {broadsheet.endorse_count}
                </span>
                <span className="flex items-center gap-1 font-pixel text-[10px] text-red-600">
                    <ThumbsDown className="h-3 w-3" />
                    {broadsheet.denounce_count}
                </span>
                <span className="flex items-center gap-1 font-pixel text-[10px]">
                    <MessageSquare className="h-3 w-3" />
                    {broadsheet.comment_count}
                </span>
            </div>
        </Link>
    );
}

function BroadsheetList({
    broadsheets,
    emptyMessage,
    baseUrl,
}: {
    broadsheets: PaginatedBroadsheets | null;
    emptyMessage: string;
    baseUrl: string;
}) {
    if (!broadsheets || broadsheets.data.length === 0) {
        return (
            <div className="py-12 text-center">
                <Newspaper className="mx-auto mb-3 h-8 w-8 text-stone-600" />
                <p className="font-pixel text-xs text-stone-500">{emptyMessage}</p>
            </div>
        );
    }

    return (
        <div className="space-y-3">
            {broadsheets.data.map((broadsheet) => (
                <BroadsheetCard key={broadsheet.id} broadsheet={broadsheet} baseUrl={baseUrl} />
            ))}

            {broadsheets.last_page > 1 && (
                <div className="flex items-center justify-center gap-2 pt-2">
                    {broadsheets.prev_page_url && (
                        <Link
                            href={broadsheets.prev_page_url}
                            className="rounded border border-stone-700 px-3 py-1 font-pixel text-xs text-stone-400 hover:bg-stone-800"
                        >
                            Previous
                        </Link>
                    )}
                    <span className="font-pixel text-[10px] text-stone-500">
                        Page {broadsheets.current_page} of {broadsheets.last_page}
                    </span>
                    {broadsheets.next_page_url && (
                        <Link
                            href={broadsheets.next_page_url}
                            className="rounded border border-stone-700 px-3 py-1 font-pixel text-xs text-stone-400 hover:bg-stone-800"
                        >
                            Next
                        </Link>
                    )}
                </div>
            )}
        </div>
    );
}

function WriteTab({
    playerGold,
    publishCost,
    hasPublishedToday,
    canPublishHere,
    baseUrl,
    onPublished,
}: {
    playerGold: number;
    publishCost: number;
    hasPublishedToday: boolean;
    canPublishHere: boolean;
    baseUrl: string;
    onPublished?: () => void;
}) {
    const [title, setTitle] = useState("");
    const [content, setContent] = useState<Descendant[]>(EMPTY_EDITOR_VALUE);
    const [publishing, setPublishing] = useState(false);

    const canAfford = playerGold >= publishCost;
    const hasContent = title.trim().length > 0 && !isEmptyContent(content);
    const canPublish =
        canAfford && hasContent && !hasPublishedToday && !publishing && canPublishHere;

    if (!canPublishHere) {
        return (
            <div className="py-12 text-center">
                <Newspaper className="mx-auto mb-3 h-8 w-8 text-stone-600" />
                <p className="font-pixel text-xs text-stone-500 sm:text-sm">
                    You can only publish broadsheets in your home settlement.
                </p>
            </div>
        );
    }

    const handlePublish = () => {
        if (!canPublish) {
            return;
        }

        setPublishing(true);
        router.post(
            baseUrl,
            {
                title: title.trim(),
                content,
                plain_text: serializeToPlainText(content),
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setTitle("");
                    setContent(EMPTY_EDITOR_VALUE);
                    onPublished?.();
                    router.reload();
                },
                onFinish: () => {
                    setPublishing(false);
                },
            },
        );
    };

    return (
        <div className="space-y-4">
            {hasPublishedToday && (
                <div className="rounded-lg border border-stone-600/30 bg-stone-800/30 p-4 text-center">
                    <p className="font-pixel text-xs text-stone-400 sm:text-sm">
                        You have published a broadsheet today. You may publish again tomorrow.
                    </p>
                </div>
            )}

            <div>
                <label className="mb-1.5 block font-pixel text-xs text-stone-400 sm:text-sm">
                    Title
                </label>
                <input
                    type="text"
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                    maxLength={150}
                    placeholder="Your broadsheet headline..."
                    className="w-full rounded-lg border border-stone-600/50 bg-stone-900/50 px-4 py-3 text-sm text-stone-200 placeholder:text-stone-500 focus:border-amber-600/50 focus:outline-none sm:text-base"
                    disabled={hasPublishedToday}
                />
                <div className="mt-1 text-right font-pixel text-[10px] text-stone-600">
                    {title.length}/150
                </div>
            </div>

            <div>
                <label className="mb-1.5 block font-pixel text-xs text-stone-400 sm:text-sm">
                    Content
                </label>
                <SlateEditor
                    value={content}
                    onChange={setContent}
                    placeholder="Write your broadsheet... Use the toolbar for formatting."
                    minHeight="min-h-[350px]"
                />
            </div>

            <div className="flex items-center justify-between rounded-lg border border-stone-700/50 bg-stone-800/30 p-4">
                <div className="flex items-center gap-2">
                    <Coins className="h-5 w-5 text-amber-400" />
                    <span className="font-pixel text-xs text-stone-400 sm:text-sm">
                        Cost:{" "}
                        <span className={canAfford ? "text-amber-300" : "text-red-400"}>
                            {publishCost}g
                        </span>
                    </span>
                    <span className="font-pixel text-[10px] text-stone-600">
                        (You have {playerGold}g)
                    </span>
                </div>

                <button
                    onClick={handlePublish}
                    disabled={!canPublish}
                    className="flex items-center gap-2 rounded-lg border border-amber-600/50 bg-amber-900/30 px-5 py-2.5 font-pixel text-xs text-amber-300 transition hover:bg-amber-900/50 disabled:cursor-not-allowed disabled:opacity-50 sm:text-sm"
                >
                    {publishing ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                        <Feather className="h-4 w-4" />
                    )}
                    Publish Broadsheet
                </button>
            </div>
        </div>
    );
}

const tabs = [
    { id: "local", label: "Local", icon: ScrollText, subtitle: null },
    { id: "barony", label: "Barony", icon: Castle, subtitle: "5+ endorsements" },
    { id: "kingdom", label: "Kingdom", icon: Crown, subtitle: "15+ endorsements" },
    { id: "write", label: "Write", icon: Feather, subtitle: null },
] as const;

export default function BroadsheetIndex() {
    const {
        local,
        barony,
        kingdom,
        tab,
        player_gold,
        publish_cost,
        has_published_today,
        can_publish_here,
        location,
        barony_name,
        kingdom_name,
        flash,
    } = usePage<PageProps>().props;

    const [activeTab, setActiveTab] = useState(tab || "local");

    const baseUrl = location
        ? `${locationPath(location.type, location.id)}/notice-board`
        : "/notice-board";

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
        ...(location
            ? [
                  { title: location.name, href: locationPath(location.type, location.id) },
                  { title: "Notice Board", href: "#" },
              ]
            : [{ title: "Notice Board", href: "#" }]),
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Notice Board - ${location?.name || "Unknown"}`} />

            <div className="flex h-full flex-1 flex-col overflow-hidden p-4">
                {/* Header */}
                <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg bg-amber-900/30 p-3">
                            <Newspaper className="h-8 w-8 text-amber-400" />
                        </div>
                        <div>
                            <h1 className="font-pixel text-xl text-amber-400 sm:text-2xl">
                                Notice Board
                            </h1>
                            <p className="font-pixel text-xs text-stone-400 sm:text-sm">
                                Read and publish broadsheets at {location?.name}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2 sm:gap-4">
                        <div className="flex items-center gap-2 rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-3 py-1.5 sm:px-4 sm:py-2">
                            <Coins className="h-4 w-4 text-amber-400" />
                            <span className="font-pixel text-xs text-amber-300 sm:text-sm">
                                {player_gold.toLocaleString()}g
                            </span>
                        </div>
                    </div>
                </div>

                {/* Tabs */}
                <div className="mb-6 flex gap-1 rounded-lg border border-stone-700/50 bg-stone-800/30 p-1">
                    {tabs.map((t) => {
                        const Icon = t.icon;
                        return (
                            <button
                                key={t.id}
                                onClick={() => setActiveTab(t.id)}
                                className={`flex flex-1 flex-col items-center justify-center gap-0.5 rounded-md px-3 py-2 font-pixel transition ${
                                    activeTab === t.id
                                        ? "bg-amber-900/40 text-amber-300"
                                        : "text-stone-500 hover:text-stone-300"
                                }`}
                            >
                                <div className="flex items-center gap-1.5">
                                    <Icon className="h-3.5 w-3.5" />
                                    <span className="text-xs">{t.label}</span>
                                </div>
                                {t.subtitle && (
                                    <span className="hidden text-[9px] opacity-60 sm:inline">
                                        {t.subtitle}
                                    </span>
                                )}
                            </button>
                        );
                    })}
                </div>

                {/* Tab Content */}
                <div className="flex-1 overflow-y-auto">
                    {activeTab === "local" && (
                        <div>
                            {location && (
                                <p className="mb-3 font-pixel text-xs text-stone-500">
                                    Broadsheets from {location.name}
                                </p>
                            )}
                            <BroadsheetList
                                broadsheets={local}
                                emptyMessage="No broadsheets have been posted here yet."
                                baseUrl={baseUrl}
                            />
                        </div>
                    )}

                    {activeTab === "barony" && (
                        <div>
                            {barony_name && (
                                <p className="mb-3 font-pixel text-xs text-stone-500">
                                    Broadsheets from across {barony_name} with 5+ endorsements
                                </p>
                            )}
                            <BroadsheetList
                                broadsheets={barony}
                                emptyMessage="No broadsheets have reached 5 endorsements in this barony yet."
                                baseUrl={baseUrl}
                            />
                        </div>
                    )}

                    {activeTab === "kingdom" && (
                        <div>
                            {kingdom_name && (
                                <p className="mb-3 font-pixel text-xs text-stone-500">
                                    Top broadsheets from {kingdom_name} with 15+ endorsements
                                </p>
                            )}
                            <BroadsheetList
                                broadsheets={kingdom}
                                emptyMessage="No broadsheets have reached 15 endorsements in this kingdom yet."
                                baseUrl={baseUrl}
                            />
                        </div>
                    )}

                    {activeTab === "write" && (
                        <WriteTab
                            playerGold={player_gold}
                            publishCost={publish_cost}
                            hasPublishedToday={has_published_today}
                            canPublishHere={can_publish_here}
                            baseUrl={baseUrl}
                            onPublished={() => setActiveTab("local")}
                        />
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

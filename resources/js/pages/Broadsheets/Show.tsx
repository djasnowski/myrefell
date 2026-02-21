import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    ArrowLeft,
    Eye,
    Loader2,
    MessageSquare,
    Newspaper,
    Reply,
    ThumbsDown,
    ThumbsUp,
    Trash2,
} from "lucide-react";
import { useEffect, useState } from "react";
import type { Descendant } from "slate";
import AppLayout from "@/layouts/app-layout";
import { gameToast } from "@/components/ui/game-toast";
import { locationPath } from "@/lib/utils";
import type { BreadcrumbItem } from "@/types";
import SlateRenderer from "@/components/slate/SlateRenderer";

interface Author {
    id: number;
    username: string;
}

interface CommentReply {
    id: number;
    body: string;
    created_at: string;
    user: Author;
}

interface Comment {
    id: number;
    body: string;
    created_at: string;
    user: Author;
    replies: CommentReply[];
}

interface BroadsheetDetail {
    id: number;
    title: string;
    content: Descendant[];
    plain_text: string;
    location_name: string;
    view_count: number;
    endorse_count: number;
    denounce_count: number;
    comment_count: number;
    published_at: string;
    author: Author;
    author_id: number;
    user_reaction: string | null;
}

interface Location {
    type: string;
    id: number;
    name: string;
}

interface PageProps {
    broadsheet: BroadsheetDetail;
    comments: Comment[];
    current_user_id: number;
    location: Location | null;
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
    return date.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });
}

function CommentItem({
    comment,
    baseUrl,
    broadsheetId,
    currentUserId,
    isReply = false,
}: {
    comment: Comment | CommentReply;
    baseUrl: string;
    broadsheetId: number;
    currentUserId: number;
    isReply?: boolean;
}) {
    const [showReplyInput, setShowReplyInput] = useState(false);
    const [replyBody, setReplyBody] = useState("");
    const [submitting, setSubmitting] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const isAuthor = comment.user.id === currentUserId;

    const handleReply = () => {
        if (!replyBody.trim()) {
            return;
        }

        setSubmitting(true);
        router.post(
            `${baseUrl}/${broadsheetId}/comments`,
            {
                body: replyBody.trim(),
                parent_id: comment.id,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setReplyBody("");
                    setShowReplyInput(false);
                    router.reload();
                },
                onFinish: () => {
                    setSubmitting(false);
                },
            },
        );
    };

    const handleDelete = () => {
        setDeleting(true);
        router.delete(`${baseUrl}/${comment.id}/delete-comment`, {
            preserveScroll: true,
            onSuccess: () => {
                router.reload();
            },
            onFinish: () => {
                setDeleting(false);
            },
        });
    };

    return (
        <div className={isReply ? "ml-6 border-l border-stone-700/30 pl-3" : ""}>
            <div className="rounded-lg border border-stone-700/30 bg-stone-800/20 p-3">
                <div className="mb-1 flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Link
                            href={`/players/${comment.user.username}`}
                            className="font-pixel text-xs text-amber-400 hover:underline"
                        >
                            {comment.user.username}
                        </Link>
                        <span className="font-pixel text-[10px] text-stone-600">
                            {formatDate(comment.created_at)}
                        </span>
                    </div>
                    <div className="flex items-center gap-1">
                        {!isReply && (
                            <button
                                onClick={() => setShowReplyInput(!showReplyInput)}
                                className="rounded p-1 text-stone-500 transition hover:bg-stone-700/30 hover:text-stone-300"
                            >
                                <Reply className="h-3 w-3" />
                            </button>
                        )}
                        {isAuthor && (
                            <button
                                onClick={handleDelete}
                                disabled={deleting}
                                className="rounded p-1 text-stone-500 transition hover:bg-red-900/30 hover:text-red-400"
                            >
                                {deleting ? (
                                    <Loader2 className="h-3 w-3 animate-spin" />
                                ) : (
                                    <Trash2 className="h-3 w-3" />
                                )}
                            </button>
                        )}
                    </div>
                </div>
                <p className="text-xs leading-relaxed text-stone-300 sm:text-sm">{comment.body}</p>
            </div>

            {showReplyInput && (
                <div className="ml-6 mt-2 flex gap-2">
                    <input
                        type="text"
                        value={replyBody}
                        onChange={(e) => setReplyBody(e.target.value)}
                        maxLength={500}
                        placeholder="Write a reply..."
                        className="flex-1 rounded-lg border border-stone-600/50 bg-stone-900/50 px-3 py-1.5 text-xs text-stone-200 placeholder:text-stone-500 focus:border-amber-600/50 focus:outline-none"
                        onKeyDown={(e) => {
                            if (e.key === "Enter" && !e.shiftKey) {
                                e.preventDefault();
                                handleReply();
                            }
                        }}
                    />
                    <button
                        onClick={handleReply}
                        disabled={submitting || !replyBody.trim()}
                        className="rounded-lg border border-amber-600/50 bg-amber-900/30 px-3 py-1.5 font-pixel text-[10px] text-amber-300 transition hover:bg-amber-900/50 disabled:opacity-50"
                    >
                        {submitting ? <Loader2 className="h-3 w-3 animate-spin" /> : "Reply"}
                    </button>
                </div>
            )}

            {"replies" in comment && comment.replies?.length > 0 && (
                <div className="mt-2 space-y-2">
                    {comment.replies.map((reply) => (
                        <CommentItem
                            key={reply.id}
                            comment={reply}
                            baseUrl={baseUrl}
                            broadsheetId={broadsheetId}
                            currentUserId={currentUserId}
                            isReply
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

export default function BroadsheetShow() {
    const { broadsheet, comments, current_user_id, location, flash } = usePage<PageProps>().props;

    const [reactLoading, setReactLoading] = useState<string | null>(null);
    const [commentBody, setCommentBody] = useState("");
    const [commentSubmitting, setCommentSubmitting] = useState(false);
    const [deleteLoading, setDeleteLoading] = useState(false);

    const isAuthor = broadsheet.author_id === current_user_id;

    const baseUrl = location ? `${locationPath(location.type, location.id)}/notice-board` : "";

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
                  {
                      title: "Notice Board",
                      href: `${locationPath(location.type, location.id)}/notice-board`,
                  },
                  { title: broadsheet.title, href: "#" },
              ]
            : [
                  { title: "Notice Board", href: "#" },
                  { title: broadsheet.title, href: "#" },
              ]),
    ];

    const handleReact = (type: "endorse" | "denounce") => {
        if (!baseUrl) {
            return;
        }
        setReactLoading(type);
        router.post(
            `${baseUrl}/${broadsheet.id}/react`,
            { type },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => {
                    setReactLoading(null);
                },
            },
        );
    };

    const handleComment = () => {
        if (!commentBody.trim() || !baseUrl) {
            return;
        }

        setCommentSubmitting(true);
        router.post(
            `${baseUrl}/${broadsheet.id}/comments`,
            { body: commentBody.trim() },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setCommentBody("");
                    router.reload();
                },
                onFinish: () => {
                    setCommentSubmitting(false);
                },
            },
        );
    };

    const handleDelete = () => {
        if (!baseUrl || !confirm("Are you sure you want to delete this broadsheet?")) {
            return;
        }

        setDeleteLoading(true);
        router.delete(`${baseUrl}/${broadsheet.id}`, {
            onSuccess: () => {
                router.reload();
            },
            onFinish: () => {
                setDeleteLoading(false);
            },
        });
    };

    const noticeBoardUrl = location
        ? `${locationPath(location.type, location.id)}/notice-board`
        : null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={broadsheet.title} />

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
                            {location && (
                                <p className="font-pixel text-xs text-stone-400 sm:text-sm">
                                    {location.name}
                                </p>
                            )}
                        </div>
                    </div>
                    {noticeBoardUrl && (
                        <Link
                            href={noticeBoardUrl}
                            className="inline-flex items-center gap-1 font-pixel text-xs text-stone-500 hover:text-stone-300"
                        >
                            <ArrowLeft className="h-3 w-3" />
                            Back to Notice Board
                        </Link>
                    )}
                </div>

                {/* Content */}
                <div className="flex-1 overflow-y-auto space-y-4">
                    {/* Broadsheet */}
                    <div className="rounded-lg border border-stone-700/50 bg-stone-800/30 p-5 sm:p-6">
                        <h2 className="mb-3 font-pixel text-lg font-bold text-amber-200 sm:text-xl">
                            {broadsheet.title}
                        </h2>

                        <div className="mb-4 flex flex-wrap items-center gap-3 text-stone-500">
                            <Link
                                href={`/players/${broadsheet.author.username}`}
                                className="font-pixel text-xs text-amber-400 hover:underline"
                            >
                                {broadsheet.author.username}
                            </Link>
                            <span className="font-pixel text-[10px]">
                                {broadsheet.location_name}
                            </span>
                            <span className="font-pixel text-[10px]">
                                {formatDate(broadsheet.published_at)}
                            </span>
                            <span className="flex items-center gap-1 font-pixel text-[10px]">
                                <Eye className="h-3 w-3" />
                                {broadsheet.view_count} views
                            </span>
                        </div>

                        {/* Content */}
                        <div className="mb-5 border-t border-stone-700/30 pt-4">
                            <SlateRenderer content={broadsheet.content} />
                        </div>

                        {/* Reaction bar */}
                        <div className="flex items-center justify-between border-t border-stone-700/30 pt-4">
                            <div className="flex items-center gap-2">
                                {baseUrl && (
                                    <>
                                        <button
                                            onClick={() => handleReact("endorse")}
                                            disabled={reactLoading !== null}
                                            className={`flex items-center gap-1.5 rounded-lg border px-3 py-1.5 font-pixel text-xs transition ${
                                                broadsheet.user_reaction === "endorse"
                                                    ? "border-emerald-600/50 bg-emerald-900/30 text-emerald-300"
                                                    : "border-stone-600/50 text-stone-400 hover:border-emerald-600/30 hover:text-emerald-400"
                                            }`}
                                        >
                                            {reactLoading === "endorse" ? (
                                                <Loader2 className="h-3.5 w-3.5 animate-spin" />
                                            ) : (
                                                <ThumbsUp className="h-3.5 w-3.5" />
                                            )}
                                            Endorse ({broadsheet.endorse_count})
                                        </button>
                                        <button
                                            onClick={() => handleReact("denounce")}
                                            disabled={reactLoading !== null}
                                            className={`flex items-center gap-1.5 rounded-lg border px-3 py-1.5 font-pixel text-xs transition ${
                                                broadsheet.user_reaction === "denounce"
                                                    ? "border-red-600/50 bg-red-900/30 text-red-300"
                                                    : "border-stone-600/50 text-stone-400 hover:border-red-600/30 hover:text-red-400"
                                            }`}
                                        >
                                            {reactLoading === "denounce" ? (
                                                <Loader2 className="h-3.5 w-3.5 animate-spin" />
                                            ) : (
                                                <ThumbsDown className="h-3.5 w-3.5" />
                                            )}
                                            Denounce ({broadsheet.denounce_count})
                                        </button>
                                    </>
                                )}
                                {!baseUrl && (
                                    <div className="flex items-center gap-3 text-stone-500">
                                        <span className="flex items-center gap-1 font-pixel text-xs">
                                            <ThumbsUp className="h-3.5 w-3.5" />
                                            {broadsheet.endorse_count}
                                        </span>
                                        <span className="flex items-center gap-1 font-pixel text-xs">
                                            <ThumbsDown className="h-3.5 w-3.5" />
                                            {broadsheet.denounce_count}
                                        </span>
                                    </div>
                                )}
                            </div>

                            {isAuthor && baseUrl && (
                                <button
                                    onClick={handleDelete}
                                    disabled={deleteLoading}
                                    className="flex items-center gap-1.5 rounded-lg border border-red-600/30 px-3 py-1.5 font-pixel text-xs text-red-400 transition hover:bg-red-900/20"
                                >
                                    {deleteLoading ? (
                                        <Loader2 className="h-3.5 w-3.5 animate-spin" />
                                    ) : (
                                        <Trash2 className="h-3.5 w-3.5" />
                                    )}
                                    Delete
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Comments */}
                    <div className="rounded-lg border border-stone-700/50 bg-stone-800/30 p-5 sm:p-6">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-amber-200 sm:text-base">
                            <MessageSquare className="h-4 w-4" />
                            Comments ({broadsheet.comment_count})
                        </h2>

                        {/* New comment */}
                        {baseUrl && (
                            <div className="mb-4 flex gap-2">
                                <input
                                    type="text"
                                    value={commentBody}
                                    onChange={(e) => setCommentBody(e.target.value)}
                                    maxLength={500}
                                    placeholder="Write a comment..."
                                    className="flex-1 rounded-lg border border-stone-600/50 bg-stone-900/50 px-3 py-2 text-xs text-stone-200 placeholder:text-stone-500 focus:border-amber-600/50 focus:outline-none sm:text-sm"
                                    onKeyDown={(e) => {
                                        if (e.key === "Enter" && !e.shiftKey) {
                                            e.preventDefault();
                                            handleComment();
                                        }
                                    }}
                                />
                                <button
                                    onClick={handleComment}
                                    disabled={commentSubmitting || !commentBody.trim()}
                                    className="rounded-lg border border-amber-600/50 bg-amber-900/30 px-4 py-2 font-pixel text-xs text-amber-300 transition hover:bg-amber-900/50 disabled:opacity-50"
                                >
                                    {commentSubmitting ? (
                                        <Loader2 className="h-4 w-4 animate-spin" />
                                    ) : (
                                        "Post"
                                    )}
                                </button>
                            </div>
                        )}

                        {/* Comment list */}
                        <div className="space-y-3">
                            {comments.length === 0 ? (
                                <p className="py-4 text-center font-pixel text-xs text-stone-600">
                                    No comments yet. Be the first to share your thoughts!
                                </p>
                            ) : (
                                comments.map((comment) => (
                                    <CommentItem
                                        key={comment.id}
                                        comment={comment}
                                        baseUrl={baseUrl}
                                        broadsheetId={broadsheet.id}
                                        currentUserId={current_user_id}
                                    />
                                ))
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

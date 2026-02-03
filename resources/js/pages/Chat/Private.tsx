import { Head, router, usePage } from "@inertiajs/react";
import { ArrowLeft, Loader2, MessageCircle, Send, User } from "lucide-react";
import { useCallback, useEffect, useRef, useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface ChatMessage {
    id: number;
    sender_id: number;
    sender_username: string;
    content: string;
    created_at: string;
    is_deleted: boolean;
    is_from_me: boolean;
}

interface Conversation {
    user_id: number;
    username: string;
    last_message: string;
    last_message_at: string;
    is_from_me: boolean;
}

interface OtherUser {
    id: number;
    username: string;
}

interface PageProps {
    messages: ChatMessage[];
    conversations: Conversation[];
    other_user: OtherUser;
    max_message_length: number;
    [key: string]: unknown;
}

export default function PrivateChat() {
    const {
        messages: initialMessages,
        conversations,
        other_user,
        max_message_length,
    } = usePage<PageProps>().props;

    const [messages, setMessages] = useState<ChatMessage[]>(initialMessages);
    const [newMessage, setNewMessage] = useState("");
    const [sending, setSending] = useState(false);
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const pollIntervalRef = useRef<NodeJS.Timeout | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Chat", href: "/chat" },
        { title: other_user.username, href: "#" },
    ];

    const scrollToBottom = useCallback(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
    }, []);

    useEffect(() => {
        scrollToBottom();
    }, [messages, scrollToBottom]);

    // Poll for new messages (only when tab is visible)
    useEffect(() => {
        const poll = async () => {
            // Skip polling if tab is not visible
            if (document.visibilityState !== "visible") return;

            const lastMessageId = messages[messages.length - 1]?.id || 0;

            try {
                const response = await fetch("/chat/poll/private", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN":
                            document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute("content") || "",
                    },
                    body: JSON.stringify({
                        other_user_id: other_user.id,
                        after_id: lastMessageId,
                    }),
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.messages.length > 0) {
                        setMessages((prev) => [...prev, ...data.messages]);
                    }
                }
            } catch {
                // Ignore polling errors
            }
        };

        pollIntervalRef.current = setInterval(poll, 10000);

        return () => {
            if (pollIntervalRef.current) {
                clearInterval(pollIntervalRef.current);
            }
        };
    }, [messages, other_user.id]);

    const handleSend = async () => {
        if (!newMessage.trim() || sending) return;

        setSending(true);
        try {
            const response = await fetch("/chat/send/private", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
                body: JSON.stringify({
                    content: newMessage,
                    recipient_id: other_user.id,
                }),
            });

            const data = await response.json();
            if (data.success && data.message) {
                setMessages((prev) => [...prev, data.message]);
                setNewMessage("");
            }
        } finally {
            setSending(false);
        }
    };

    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            handleSend();
        }
    };

    const formatTime = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Chat with ${other_user.username}`} />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-4 flex items-center gap-3">
                    <button
                        onClick={() => router.visit("/chat")}
                        className="rounded-lg bg-stone-700 p-2 transition hover:bg-stone-600"
                    >
                        <ArrowLeft className="h-5 w-5 text-stone-400" />
                    </button>
                    <div className="rounded-lg bg-purple-900/30 p-3">
                        <User className="h-8 w-8 text-purple-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-2xl text-purple-400">
                            {other_user.username}
                        </h1>
                        <p className="font-pixel text-xs text-stone-400">Private conversation</p>
                    </div>
                </div>

                <div className="grid flex-1 gap-4 lg:grid-cols-4">
                    {/* Sidebar - Conversations */}
                    <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4 lg:col-span-1">
                        <h3 className="mb-2 font-pixel text-xs text-stone-400">Conversations</h3>
                        {conversations.length > 0 ? (
                            <div className="space-y-2">
                                {conversations.map((conv) => (
                                    <button
                                        key={conv.user_id}
                                        onClick={() =>
                                            router.visit(`/chat/private/${conv.user_id}`)
                                        }
                                        className={`w-full rounded-lg p-2 text-left transition ${
                                            conv.user_id === other_user.id
                                                ? "bg-purple-900/30 border border-purple-600/50"
                                                : "bg-stone-900/50 hover:bg-stone-700/50"
                                        }`}
                                    >
                                        <div
                                            className={`font-pixel text-xs ${
                                                conv.user_id === other_user.id
                                                    ? "text-purple-300"
                                                    : "text-stone-300"
                                            }`}
                                        >
                                            {conv.username}
                                        </div>
                                        <div className="truncate font-pixel text-[10px] text-stone-500">
                                            {conv.is_from_me ? "You: " : ""}
                                            {conv.last_message}
                                        </div>
                                    </button>
                                ))}
                            </div>
                        ) : (
                            <div className="py-4 text-center">
                                <User className="mx-auto mb-2 h-6 w-6 text-stone-600" />
                                <p className="font-pixel text-[10px] text-stone-500">
                                    No other conversations
                                </p>
                            </div>
                        )}
                    </div>

                    {/* Main Chat Area */}
                    <div className="flex flex-col rounded-xl border-2 border-stone-700 bg-stone-800/50 lg:col-span-3">
                        {/* Messages */}
                        <div className="flex-1 overflow-y-auto p-4">
                            {messages.length > 0 ? (
                                <div className="space-y-3">
                                    {messages.map((msg) => (
                                        <div
                                            key={msg.id}
                                            className={`flex gap-3 ${msg.is_from_me ? "flex-row-reverse" : ""}`}
                                        >
                                            <div
                                                className={`flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full ${
                                                    msg.is_from_me
                                                        ? "bg-purple-700"
                                                        : "bg-stone-700"
                                                }`}
                                            >
                                                <User
                                                    className={`h-4 w-4 ${msg.is_from_me ? "text-purple-300" : "text-stone-400"}`}
                                                />
                                            </div>
                                            <div
                                                className={`max-w-[70%] ${msg.is_from_me ? "text-right" : ""}`}
                                            >
                                                <div
                                                    className={`flex items-center gap-2 ${msg.is_from_me ? "flex-row-reverse" : ""}`}
                                                >
                                                    <span
                                                        className={`font-pixel text-xs ${msg.is_from_me ? "text-purple-400" : "text-blue-400"}`}
                                                    >
                                                        {msg.sender_username}
                                                    </span>
                                                    <span className="font-pixel text-[10px] text-stone-500">
                                                        {formatTime(msg.created_at)}
                                                    </span>
                                                </div>
                                                <div
                                                    className={`mt-1 inline-block rounded-lg px-3 py-2 ${
                                                        msg.is_from_me
                                                            ? "bg-purple-900/30 text-purple-200"
                                                            : "bg-stone-700/50 text-stone-300"
                                                    }`}
                                                >
                                                    <p className="font-pixel text-xs">
                                                        {msg.content}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                    <div ref={messagesEndRef} />
                                </div>
                            ) : (
                                <div className="flex h-full flex-col items-center justify-center">
                                    <MessageCircle className="mb-2 h-10 w-10 text-stone-600" />
                                    <p className="font-pixel text-xs text-stone-500">
                                        No messages yet
                                    </p>
                                    <p className="font-pixel text-[10px] text-stone-600">
                                        Start the conversation!
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Message Input */}
                        <div className="border-t border-stone-700 p-4">
                            <div className="flex gap-2">
                                <input
                                    type="text"
                                    value={newMessage}
                                    onChange={(e) => setNewMessage(e.target.value)}
                                    onKeyDown={handleKeyDown}
                                    placeholder={`Message ${other_user.username}...`}
                                    maxLength={max_message_length}
                                    className="flex-1 rounded-lg border border-stone-600 bg-stone-900/50 px-3 py-2 font-pixel text-xs text-stone-300 placeholder-stone-500 focus:border-purple-500 focus:outline-none"
                                />
                                <button
                                    onClick={handleSend}
                                    disabled={sending || !newMessage.trim()}
                                    className={`flex items-center gap-2 rounded-lg px-4 py-2 font-pixel text-xs transition ${
                                        sending || !newMessage.trim()
                                            ? "cursor-not-allowed bg-stone-700 text-stone-500"
                                            : "bg-purple-600 text-stone-100 hover:bg-purple-500"
                                    }`}
                                >
                                    {sending ? (
                                        <Loader2 className="h-4 w-4 animate-spin" />
                                    ) : (
                                        <Send className="h-4 w-4" />
                                    )}
                                </button>
                            </div>
                            <div className="mt-1 text-right font-pixel text-[10px] text-stone-500">
                                {newMessage.length}/{max_message_length}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

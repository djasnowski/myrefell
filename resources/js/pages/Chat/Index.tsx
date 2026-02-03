import { Head, router, usePage } from "@inertiajs/react";
import { Loader2, MessageCircle, Send, Trash2, User, Users } from "lucide-react";
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
}

interface Conversation {
    user_id: number;
    username: string;
    last_message: string;
    last_message_at: string;
    is_from_me: boolean;
}

interface PageProps {
    messages: ChatMessage[];
    conversations: Conversation[];
    current_location_type: string;
    current_location_id: number;
    location_name: string;
    max_message_length: number;
    [key: string]: unknown;
}

export default function ChatIndex() {
    const {
        messages: initialMessages,
        conversations,
        current_location_type,
        current_location_id,
        location_name,
        max_message_length,
    } = usePage<PageProps>().props;

    const [messages, setMessages] = useState<ChatMessage[]>(initialMessages);
    const [newMessage, setNewMessage] = useState("");
    const [sending, setSending] = useState(false);
    const [deleting, setDeleting] = useState<number | null>(null);
    const [activeTab, setActiveTab] = useState<"location" | "private">("location");
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const pollIntervalRef = useRef<NodeJS.Timeout | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Chat", href: "#" },
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
            if (messages.length === 0) return;

            const lastMessageId = messages[messages.length - 1]?.id || 0;

            try {
                const response = await fetch("/chat/poll/location", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN":
                            document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute("content") || "",
                    },
                    body: JSON.stringify({
                        location_type: current_location_type,
                        location_id: current_location_id,
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
    }, [messages, current_location_type, current_location_id]);

    const handleSend = async () => {
        if (!newMessage.trim() || sending) return;

        setSending(true);
        try {
            const response = await fetch("/chat/send/location", {
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
                    location_type: current_location_type,
                    location_id: current_location_id,
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

    const handleDelete = async (messageId: number) => {
        setDeleting(messageId);
        try {
            await fetch(`/chat/messages/${messageId}`, {
                method: "DELETE",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
            });
            setMessages((prev) => prev.filter((m) => m.id !== messageId));
        } finally {
            setDeleting(null);
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
            <Head title="Chat" />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-4 flex items-center gap-3">
                    <div className="rounded-lg bg-blue-900/30 p-3">
                        <MessageCircle className="h-8 w-8 text-blue-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-2xl text-blue-400">Chat</h1>
                        <p className="font-pixel text-xs text-stone-400">{location_name}</p>
                    </div>
                </div>

                <div className="grid flex-1 gap-4 lg:grid-cols-4">
                    {/* Sidebar - Conversations */}
                    <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4 lg:col-span-1">
                        {/* Tabs */}
                        <div className="mb-4 flex gap-2">
                            <button
                                onClick={() => setActiveTab("location")}
                                className={`flex-1 rounded px-2 py-1 font-pixel text-xs transition ${
                                    activeTab === "location"
                                        ? "bg-blue-600 text-stone-100"
                                        : "bg-stone-700 text-stone-400 hover:bg-stone-600"
                                }`}
                            >
                                <Users className="mx-auto h-4 w-4" />
                            </button>
                            <button
                                onClick={() => setActiveTab("private")}
                                className={`flex-1 rounded px-2 py-1 font-pixel text-xs transition ${
                                    activeTab === "private"
                                        ? "bg-blue-600 text-stone-100"
                                        : "bg-stone-700 text-stone-400 hover:bg-stone-600"
                                }`}
                            >
                                <User className="mx-auto h-4 w-4" />
                            </button>
                        </div>

                        {activeTab === "location" ? (
                            <div>
                                <h3 className="mb-2 font-pixel text-xs text-stone-400">
                                    Location Chat
                                </h3>
                                <div className="rounded-lg bg-blue-900/20 p-3">
                                    <div className="flex items-center gap-2">
                                        <Users className="h-4 w-4 text-blue-400" />
                                        <span className="font-pixel text-xs text-blue-300">
                                            {location_name}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div>
                                <h3 className="mb-2 font-pixel text-xs text-stone-400">
                                    Private Messages
                                </h3>
                                {conversations.length > 0 ? (
                                    <div className="space-y-2">
                                        {conversations.map((conv) => (
                                            <button
                                                key={conv.user_id}
                                                onClick={() =>
                                                    router.visit(`/chat/private/${conv.user_id}`)
                                                }
                                                className="w-full rounded-lg bg-stone-900/50 p-2 text-left transition hover:bg-stone-700/50"
                                            >
                                                <div className="font-pixel text-xs text-stone-300">
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
                                            No conversations yet
                                        </p>
                                    </div>
                                )}
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
                                        <div key={msg.id} className="group flex gap-3">
                                            <div className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-stone-700">
                                                <User className="h-4 w-4 text-stone-400" />
                                            </div>
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-pixel text-xs text-blue-400">
                                                        {msg.sender_username}
                                                    </span>
                                                    <span className="font-pixel text-[10px] text-stone-500">
                                                        {formatTime(msg.created_at)}
                                                    </span>
                                                    <button
                                                        onClick={() => handleDelete(msg.id)}
                                                        disabled={deleting === msg.id}
                                                        className="ml-auto hidden rounded p-1 text-stone-500 transition hover:bg-red-900/30 hover:text-red-400 group-hover:block"
                                                        title="Delete message"
                                                    >
                                                        {deleting === msg.id ? (
                                                            <Loader2 className="h-3 w-3 animate-spin" />
                                                        ) : (
                                                            <Trash2 className="h-3 w-3" />
                                                        )}
                                                    </button>
                                                </div>
                                                <p className="font-pixel text-xs text-stone-300">
                                                    {msg.content}
                                                </p>
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
                                        Be the first to say something!
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
                                    placeholder="Type a message..."
                                    maxLength={max_message_length}
                                    className="flex-1 rounded-lg border border-stone-600 bg-stone-900/50 px-3 py-2 font-pixel text-xs text-stone-300 placeholder-stone-500 focus:border-blue-500 focus:outline-none"
                                />
                                <button
                                    onClick={handleSend}
                                    disabled={sending || !newMessage.trim()}
                                    className={`flex items-center gap-2 rounded-lg px-4 py-2 font-pixel text-xs transition ${
                                        sending || !newMessage.trim()
                                            ? "cursor-not-allowed bg-stone-700 text-stone-500"
                                            : "bg-blue-600 text-stone-100 hover:bg-blue-500"
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

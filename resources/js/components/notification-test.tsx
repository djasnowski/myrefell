import { Bell, BellOff, BellRing } from "lucide-react";
import { useState } from "react";
import { useNotifications } from "@/hooks/use-notifications";

export function NotificationTest() {
    const { isSupported, permission, requestPermission } = useNotifications();
    const [expanded, setExpanded] = useState(false);
    const [countdown, setCountdown] = useState<number | null>(null);

    if (!isSupported) return null;

    const handleTest = async () => {
        if (permission !== "granted") {
            const granted = await requestPermission();
            if (!granted) return;
        }

        // Start countdown
        setCountdown(3);

        const interval = setInterval(() => {
            setCountdown((prev) => {
                if (prev === null || prev <= 1) {
                    clearInterval(interval);
                    // Send notification after countdown
                    try {
                        new Notification("Test Notification", {
                            body: "Browser notifications are working!",
                            icon: `${window.location.origin}/crown.png`,
                        });
                    } catch {
                        // Fallback
                    }
                    return null;
                }
                return prev - 1;
            });
        }, 1000);
    };

    const getIcon = () => {
        if (permission === "granted") return <BellRing className="h-4 w-4" />;
        if (permission === "denied") return <BellOff className="h-4 w-4" />;
        return <Bell className="h-4 w-4" />;
    };

    const getStatusColor = () => {
        if (permission === "granted") return "bg-green-600 hover:bg-green-500";
        if (permission === "denied") return "bg-red-600 hover:bg-red-500";
        return "bg-amber-600 hover:bg-amber-500";
    };

    return (
        <div className="fixed bottom-4 right-4 z-50">
            {expanded ? (
                <div className="rounded-lg border border-stone-600 bg-stone-800 p-3 shadow-lg">
                    <div className="mb-2 flex items-center justify-between gap-4">
                        <span className="font-pixel text-xs text-stone-300">Notifications</span>
                        <button
                            onClick={() => setExpanded(false)}
                            className="text-stone-500 hover:text-stone-300"
                        >
                            ✕
                        </button>
                    </div>
                    <div className="mb-2 font-pixel text-[10px] text-stone-500">
                        Status:{" "}
                        <span
                            className={
                                permission === "granted"
                                    ? "text-green-400"
                                    : permission === "denied"
                                      ? "text-red-400"
                                      : "text-amber-400"
                            }
                        >
                            {permission}
                        </span>
                    </div>
                    <button
                        onClick={handleTest}
                        disabled={permission === "denied" || countdown !== null}
                        className="w-full rounded bg-purple-600 px-3 py-1.5 font-pixel text-xs text-white hover:bg-purple-500 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {countdown !== null
                            ? `Sending in ${countdown}...`
                            : permission === "granted"
                              ? "Send Test"
                              : "Enable & Test"}
                    </button>
                    {permission === "denied" && (
                        <p className="mt-2 font-pixel text-[10px] text-red-400">
                            Blocked. Reset in browser settings.
                        </p>
                    )}
                </div>
            ) : (
                <button
                    onClick={() => setExpanded(true)}
                    className={`rounded-full p-2.5 text-white shadow-lg transition ${getStatusColor()}`}
                    title="Test notifications"
                >
                    {getIcon()}
                </button>
            )}
        </div>
    );
}

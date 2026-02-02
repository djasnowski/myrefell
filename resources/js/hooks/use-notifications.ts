import { useCallback, useEffect, useState } from "react";

const PERMISSION_KEY = "myrefell_notifications_permission";

type NotificationPermission = "default" | "granted" | "denied";

interface NotificationOptions {
    body?: string;
    icon?: string;
    tag?: string;
    requireInteraction?: boolean;
}

export function useNotifications() {
    const [permission, setPermission] = useState<NotificationPermission>("default");
    const [isSupported, setIsSupported] = useState(false);

    useEffect(() => {
        if (typeof window !== "undefined" && "Notification" in window) {
            setIsSupported(true);
            setPermission(Notification.permission);
        }
    }, []);

    const requestPermission = useCallback(async (): Promise<boolean> => {
        if (!isSupported) return false;

        try {
            const result = await Notification.requestPermission();
            setPermission(result);
            localStorage.setItem(PERMISSION_KEY, result);
            return result === "granted";
        } catch {
            return false;
        }
    }, [isSupported]);

    const sendNotification = useCallback(
        async (title: string, options?: NotificationOptions): Promise<boolean> => {
            if (!isSupported) return false;

            // Only send if tab is not visible
            if (!document.hidden) return false;

            // Check permission
            if (permission !== "granted") {
                const granted = await requestPermission();
                if (!granted) return false;
            }

            try {
                const iconUrl = `${window.location.origin}/crown.png`;
                const notification = new Notification(title, {
                    icon: iconUrl,
                    ...options,
                });

                // Auto-close after 5 seconds
                setTimeout(() => notification.close(), 5000);

                // Focus window when clicked
                notification.onclick = () => {
                    window.focus();
                    notification.close();
                };

                return true;
            } catch {
                return false;
            }
        },
        [isSupported, permission, requestPermission],
    );

    return {
        isSupported,
        permission,
        requestPermission,
        sendNotification,
    };
}

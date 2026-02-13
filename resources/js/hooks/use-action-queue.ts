import { router } from "@inertiajs/react";
import { useCallback, useEffect, useRef, useState } from "react";

export interface ActionResult {
    success: boolean;
    failed?: boolean;
    message: string;
    xp_awarded?: number;
    leveled_up?: boolean;
    new_level?: number;
    skill?: string;
    energy_remaining?: number;
    item?: { name: string; quantity: number };
    resource?: { name: string; description: string };
    quantity?: number;
}

export interface QueueStats {
    completed: number;
    total: number;
    totalXp: number;
    lastLevelUp?: { skill: string; level: number };
    itemName?: string;
    totalQuantity: number;
    cancelled: boolean;
    stopReason?: string;
}

export interface UseActionQueueOptions {
    url: string;
    buildBody: () => Record<string, unknown>;
    cooldownMs?: number;
    onActionComplete: (data: ActionResult) => void;
    onQueueComplete: (stats: QueueStats) => void;
    shouldContinue?: (data: ActionResult) => boolean;
    reloadProps?: string[];
}

export interface UseActionQueueReturn {
    startQueue: (count: number) => void;
    cancelQueue: () => void;
    isQueueActive: boolean;
    queueProgress: { completed: number; total: number };
    isActionLoading: boolean;
    cooldown: number;
    performSingleAction: () => void;
    isGloballyLocked: boolean;
}

// Global lock — only one queue can run at a time across all pages
let globalQueueOwner: symbol | null = null;

function getCsrfToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
}

function requestNotificationPermission(): void {
    if ("Notification" in window && Notification.permission === "default") {
        Notification.requestPermission();
    }
}

function sendBrowserNotification(title: string, body: string): void {
    if ("Notification" in window && Notification.permission === "granted" && document.hidden) {
        new Notification(title, { body, icon: "/favicon.ico" });
    }
}

export function useActionQueue(options: UseActionQueueOptions): UseActionQueueReturn {
    const {
        url,
        buildBody,
        cooldownMs = 3000,
        onActionComplete,
        onQueueComplete,
        shouldContinue = (data) => data.success,
        reloadProps = ["sidebar"],
    } = options;

    // Unique identity for this hook instance (stable across renders)
    const ownerRef = useRef(Symbol());

    const [isQueueActive, setIsQueueActive] = useState(false);
    const [queueProgress, setQueueProgress] = useState({ completed: 0, total: 0 });
    const [isActionLoading, setIsActionLoading] = useState(false);
    const [cooldown, setCooldown] = useState(0);

    const cooldownTimer = useRef<NodeJS.Timeout | null>(null);
    const cooldownStart = useRef<number>(0);
    const cancelledRef = useRef(false);
    const statsRef = useRef<QueueStats>({
        completed: 0,
        total: 0,
        totalXp: 0,
        totalQuantity: 0,
        cancelled: false,
    });
    const isQueueActiveRef = useRef(false);
    const queueTimeoutRef = useRef<NodeJS.Timeout | null>(null);

    // Refs for latest option values (avoids stale closures)
    const urlRef = useRef(url);
    const buildBodyRef = useRef(buildBody);
    const onActionCompleteRef = useRef(onActionComplete);
    const onQueueCompleteRef = useRef(onQueueComplete);
    const shouldContinueRef = useRef(shouldContinue);
    const reloadPropsRef = useRef(reloadProps);
    const cooldownMsRef = useRef(cooldownMs);

    useEffect(() => {
        urlRef.current = url;
    }, [url]);
    useEffect(() => {
        buildBodyRef.current = buildBody;
    }, [buildBody]);
    useEffect(() => {
        onActionCompleteRef.current = onActionComplete;
    }, [onActionComplete]);
    useEffect(() => {
        onQueueCompleteRef.current = onQueueComplete;
    }, [onQueueComplete]);
    useEffect(() => {
        shouldContinueRef.current = shouldContinue;
    }, [shouldContinue]);
    useEffect(() => {
        reloadPropsRef.current = reloadProps;
    }, [reloadProps]);
    useEffect(() => {
        cooldownMsRef.current = cooldownMs;
    }, [cooldownMs]);

    const cleanup = useCallback(() => {
        if (cooldownTimer.current) {
            clearInterval(cooldownTimer.current);
            cooldownTimer.current = null;
        }
        if (queueTimeoutRef.current) {
            clearTimeout(queueTimeoutRef.current);
            queueTimeoutRef.current = null;
        }
    }, []);

    useEffect(() => {
        const owner = ownerRef.current;
        return () => {
            cleanup();
            cancelledRef.current = true;
            isQueueActiveRef.current = false;
            if (globalQueueOwner === owner) {
                globalQueueOwner = null;
            }
        };
    }, [cleanup]);

    const startCooldownTimer = useCallback(() => {
        const ms = cooldownMsRef.current;
        setCooldown(ms);
        cooldownStart.current = Date.now();
        if (cooldownTimer.current) {
            clearInterval(cooldownTimer.current);
        }
        cooldownTimer.current = setInterval(() => {
            const remaining = Math.max(0, ms - (Date.now() - cooldownStart.current));
            setCooldown(remaining);
            if (remaining <= 0 && cooldownTimer.current) {
                clearInterval(cooldownTimer.current);
                cooldownTimer.current = null;
            }
        }, 50);
    }, []);

    const finishQueue = useCallback(
        (cancelled: boolean, stopReason?: string) => {
            const stats = { ...statsRef.current, cancelled, stopReason };
            setIsQueueActive(false);
            isQueueActiveRef.current = false;
            setIsActionLoading(false);
            if (globalQueueOwner === ownerRef.current) {
                globalQueueOwner = null;
            }
            cleanup();

            // Send browser notification if queue had multiple actions
            if (stats.total > 1 || stats.total === Infinity) {
                const qty = stats.totalQuantity > 0 ? `${stats.totalQuantity}x ` : "";
                const itemPart = stats.itemName
                    ? `${qty}${stats.itemName}`
                    : `${stats.completed} actions`;

                if (stopReason) {
                    sendBrowserNotification(
                        "Queue stopped",
                        `Completed ${itemPart} (+${stats.totalXp.toLocaleString()} XP). ${stopReason}`,
                    );
                } else if (cancelled) {
                    sendBrowserNotification(
                        "Queue cancelled",
                        `Completed ${itemPart} (+${stats.totalXp.toLocaleString()} XP)`,
                    );
                } else {
                    sendBrowserNotification(
                        "Queue finished",
                        `Completed ${itemPart} (+${stats.totalXp.toLocaleString()} XP)`,
                    );
                }
            }

            // Reload fresh data
            router.reload({ only: reloadPropsRef.current });
            onQueueCompleteRef.current(stats);
        },
        [cleanup],
    );

    const executeAction = useCallback(async () => {
        if (cancelledRef.current || !isQueueActiveRef.current) {
            finishQueue(true);
            return;
        }

        setIsActionLoading(true);

        try {
            const response = await fetch(urlRef.current, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": getCsrfToken(),
                },
                body: JSON.stringify(buildBodyRef.current()),
            });

            const data: ActionResult = await response.json();

            if (cancelledRef.current || !isQueueActiveRef.current) {
                finishQueue(true);
                return;
            }

            onActionCompleteRef.current(data);

            const continueQueue = shouldContinueRef.current(data);
            const failureMessage = !continueQueue ? data.message : undefined;

            if (continueQueue) {
                statsRef.current.completed++;
                statsRef.current.totalXp += data.xp_awarded ?? 0;

                // Track item/resource name and quantity
                if (data.item) {
                    statsRef.current.itemName = data.item.name;
                    statsRef.current.totalQuantity += data.item.quantity;
                } else if (data.resource) {
                    statsRef.current.itemName = data.resource.name;
                    statsRef.current.totalQuantity += data.quantity ?? 1;
                } else {
                    statsRef.current.totalQuantity += 1;
                }

                if (data.leveled_up && data.new_level && data.skill) {
                    statsRef.current.lastLevelUp = {
                        skill: data.skill,
                        level: data.new_level,
                    };
                }

                setQueueProgress({
                    completed: statsRef.current.completed,
                    total: statsRef.current.total,
                });
            }

            setIsActionLoading(false);

            // Reload sidebar after each action so energy bar stays in sync
            router.reload({ only: ["sidebar"] });

            // Check if queue should stop
            if (
                !continueQueue ||
                cancelledRef.current ||
                !isQueueActiveRef.current ||
                statsRef.current.completed >= statsRef.current.total
            ) {
                finishQueue(cancelledRef.current, failureMessage);
                return;
            }

            // Start cooldown, then fire next action
            startCooldownTimer();
            queueTimeoutRef.current = setTimeout(() => {
                executeAction();
            }, cooldownMsRef.current);
        } catch {
            setIsActionLoading(false);
            finishQueue(false);
        }
    }, [finishQueue, startCooldownTimer]);

    const startQueue = useCallback(
        (count: number) => {
            // Prevent running multiple queues at the same time
            if (globalQueueOwner && globalQueueOwner !== ownerRef.current) {
                return;
            }
            if (count > 1 || count === Infinity) {
                requestNotificationPermission();
            }
            globalQueueOwner = ownerRef.current;
            cancelledRef.current = false;
            isQueueActiveRef.current = true;
            statsRef.current = {
                completed: 0,
                total: count,
                totalXp: 0,
                totalQuantity: 0,
                cancelled: false,
            };
            setIsQueueActive(true);
            setQueueProgress({ completed: 0, total: count });
            executeAction();
        },
        [executeAction],
    );

    const cancelQueue = useCallback(() => {
        cancelledRef.current = true;
        isQueueActiveRef.current = false;
        cleanup();
        finishQueue(true);
    }, [cleanup, finishQueue]);

    const performSingleAction = useCallback(() => {
        startQueue(1);
    }, [startQueue]);

    return {
        startQueue,
        cancelQueue,
        isQueueActive,
        queueProgress,
        isActionLoading,
        cooldown,
        performSingleAction,
        isGloballyLocked: !!globalQueueOwner && globalQueueOwner !== ownerRef.current,
    };
}

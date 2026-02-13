import { router, usePage } from "@inertiajs/react";
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

export interface ServerQueueData {
    id: number;
    action_type: string;
    status: "active" | "completed" | "cancelled" | "failed";
    total: number;
    completed: number;
    total_xp: number;
    total_quantity: number;
    item_name: string | null;
    last_level_up: { skill: string; level: number } | null;
    stop_reason: string | null;
    created_at: string | null;
}

export interface UseActionQueueOptions {
    url: string;
    buildBody: () => Record<string, unknown>;
    cooldownMs?: number;
    onActionComplete: (data: ActionResult) => void;
    onQueueComplete: (stats: QueueStats) => void;
    shouldContinue?: (data: ActionResult) => boolean;
    reloadProps?: string[];
    /** Server-side queue action type (craft, smelt, gather, train, agility) */
    actionType: string;
    /** Function that builds the action_params for the server-side queue */
    buildActionParams: () => Record<string, unknown>;
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
    totalXp: number;
    queueStartedAt: number | null;
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
        actionType,
        buildActionParams,
    } = options;

    // Unique identity for this hook instance (stable across renders)
    const ownerRef = useRef(Symbol());

    const [isQueueActive, setIsQueueActive] = useState(false);
    const [queueProgress, setQueueProgress] = useState({ completed: 0, total: 0 });
    const [isActionLoading, setIsActionLoading] = useState(false);
    const [cooldown, setCooldown] = useState(0);
    const [totalXp, setTotalXp] = useState(0);
    const [queueStartedAt, setQueueStartedAt] = useState<number | null>(null);

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
    const pollTimerRef = useRef<NodeJS.Timeout | null>(null);
    const serverQueueIdRef = useRef<number | null>(null);

    // Track whether we're in "server mode" (multi-action) or "client mode" (single action)
    const isServerModeRef = useRef(false);
    // Track the last known completed count to detect progress
    const lastCompletedRef = useRef(0);

    // Refs for latest option values (avoids stale closures)
    const urlRef = useRef(url);
    const buildBodyRef = useRef(buildBody);
    const onActionCompleteRef = useRef(onActionComplete);
    const onQueueCompleteRef = useRef(onQueueComplete);
    const shouldContinueRef = useRef(shouldContinue);
    const reloadPropsRef = useRef(reloadProps);
    const cooldownMsRef = useRef(cooldownMs);
    const actionTypeRef = useRef(actionType);
    const buildActionParamsRef = useRef(buildActionParams);

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
    useEffect(() => {
        actionTypeRef.current = actionType;
    }, [actionType]);
    useEffect(() => {
        buildActionParamsRef.current = buildActionParams;
    }, [buildActionParams]);

    // Read server queue data from sidebar props
    const page = usePage<{
        sidebar?: { action_queue?: ServerQueueData | null };
    }>();
    const serverQueue = page.props.sidebar?.action_queue ?? null;

    const cleanup = useCallback(() => {
        if (cooldownTimer.current) {
            clearInterval(cooldownTimer.current);
            cooldownTimer.current = null;
        }
        if (queueTimeoutRef.current) {
            clearTimeout(queueTimeoutRef.current);
            queueTimeoutRef.current = null;
        }
        if (pollTimerRef.current) {
            clearInterval(pollTimerRef.current);
            pollTimerRef.current = null;
        }
    }, []);

    useEffect(() => {
        const owner = ownerRef.current;
        return () => {
            cleanup();
            cancelledRef.current = true;
            isQueueActiveRef.current = false;
            isServerModeRef.current = false;
            serverQueueIdRef.current = null;
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

    const finishQueue = useCallback((cancelled: boolean, stopReason?: string) => {
        const stats = { ...statsRef.current, cancelled, stopReason };
        setIsQueueActive(false);
        isQueueActiveRef.current = false;
        isServerModeRef.current = false;
        serverQueueIdRef.current = null;
        setIsActionLoading(false);
        setTotalXp(0);
        setQueueStartedAt(null);
        if (globalQueueOwner === ownerRef.current) {
            globalQueueOwner = null;
        }
        // Clean up queue/poll timers but preserve cooldown timer
        if (queueTimeoutRef.current) {
            clearTimeout(queueTimeoutRef.current);
            queueTimeoutRef.current = null;
        }
        if (pollTimerRef.current) {
            clearInterval(pollTimerRef.current);
            pollTimerRef.current = null;
        }

        // Send browser notification if queue had multiple actions
        if (stats.total > 1 || stats.total === Infinity || stats.total === 0) {
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
    }, []);

    // ============================================================
    // Server-side queue: monitor via sidebar polling
    // ============================================================

    const startPolling = useCallback(() => {
        if (pollTimerRef.current) {
            clearInterval(pollTimerRef.current);
        }
        pollTimerRef.current = setInterval(() => {
            router.reload({ only: reloadPropsRef.current });
        }, 3000);
    }, []);

    // React to sidebar action_queue changes when in server mode
    useEffect(() => {
        if (!isServerModeRef.current || !serverQueue) {
            return;
        }

        // Only track the queue we started
        if (serverQueueIdRef.current !== null && serverQueue.id !== serverQueueIdRef.current) {
            return;
        }

        // Update progress (total=0 on server means infinite)
        setQueueProgress({
            completed: serverQueue.completed,
            total: serverQueue.total === 0 ? Infinity : serverQueue.total,
        });
        setTotalXp(serverQueue.total_xp);

        // Update stats ref
        statsRef.current = {
            completed: serverQueue.completed,
            total: serverQueue.total === 0 ? Infinity : serverQueue.total,
            totalXp: serverQueue.total_xp,
            totalQuantity: serverQueue.total_quantity,
            itemName: serverQueue.item_name ?? undefined,
            lastLevelUp: serverQueue.last_level_up ?? undefined,
            cancelled: false,
        };

        // Check if queue finished
        if (serverQueue.status !== "active") {
            const isCancelled = serverQueue.status === "cancelled";
            const stopReason = serverQueue.stop_reason ?? undefined;

            // Auto-dismiss the completed queue
            fetch("/action-queue/dismiss", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": getCsrfToken(),
                },
                body: JSON.stringify({ queue_id: serverQueue.id }),
            });

            finishQueue(isCancelled, stopReason);
        }
    }, [serverQueue, finishQueue]);

    // On mount, check if there's already a server queue (page reload scenario)
    useEffect(() => {
        if (!serverQueue || isQueueActiveRef.current) {
            return;
        }

        if (serverQueue.status === "active") {
            // Resume tracking this active queue
            globalQueueOwner = ownerRef.current;
            isServerModeRef.current = true;
            isQueueActiveRef.current = true;
            serverQueueIdRef.current = serverQueue.id;
            lastCompletedRef.current = serverQueue.completed;
            setIsQueueActive(true);
            setQueueProgress({
                completed: serverQueue.completed,
                total: serverQueue.total === 0 ? Infinity : serverQueue.total,
            });
            setTotalXp(serverQueue.total_xp);
            setQueueStartedAt(
                serverQueue.created_at ? new Date(serverQueue.created_at).getTime() : Date.now(),
            );
            statsRef.current = {
                completed: serverQueue.completed,
                total: serverQueue.total === 0 ? Infinity : serverQueue.total,
                totalXp: serverQueue.total_xp,
                totalQuantity: serverQueue.total_quantity,
                itemName: serverQueue.item_name ?? undefined,
                lastLevelUp: serverQueue.last_level_up ?? undefined,
                cancelled: false,
            };
            startPolling();
        } else {
            // Stale completed/failed/cancelled queue — auto-dismiss it
            fetch("/action-queue/dismiss", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": getCsrfToken(),
                },
                body: JSON.stringify({ queue_id: serverQueue.id }),
            }).then(() => {
                router.reload({ only: ["sidebar"] });
            });
        }
        // Only run on mount
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const startServerQueue = useCallback(
        async (count: number) => {
            setIsActionLoading(true);

            try {
                const params = buildActionParamsRef.current();
                const response = await fetch("/action-queue/start", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": getCsrfToken(),
                    },
                    body: JSON.stringify({
                        action_type: actionTypeRef.current,
                        action_params: params,
                        total: count === Infinity ? 0 : count,
                    }),
                });

                const data = await response.json();

                if (!data.success) {
                    setIsActionLoading(false);
                    finishQueue(false, data.message);
                    return;
                }

                // Store the queue ID so we track the right one
                if (data.queue) {
                    serverQueueIdRef.current = data.queue.id;
                }

                isServerModeRef.current = true;
                lastCompletedRef.current = 0;
                setIsActionLoading(false);

                // Start polling for updates
                startPolling();

                // Reload sidebar immediately to pick up new queue
                router.reload({ only: ["sidebar"] });
            } catch {
                setIsActionLoading(false);
                finishQueue(false, "Failed to start queue.");
            }
        },
        [finishQueue, startPolling],
    );

    // ============================================================
    // Client-side single action (unchanged from original)
    // ============================================================

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
                setTotalXp(statsRef.current.totalXp);
            }

            setIsActionLoading(false);

            // Reload sidebar after each action so energy bar stays in sync
            router.reload({ only: reloadPropsRef.current });

            // Check if queue should stop
            if (
                !continueQueue ||
                cancelledRef.current ||
                !isQueueActiveRef.current ||
                statsRef.current.completed >= statsRef.current.total
            ) {
                // Start cooldown even after finishing so button stays disabled briefly
                startCooldownTimer();
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

    // ============================================================
    // Public interface
    // ============================================================

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
            setTotalXp(0);
            setQueueStartedAt(Date.now());

            if (count === 1) {
                // Single action: client-side for instant feedback
                isServerModeRef.current = false;
                executeAction();
            } else {
                // Multi-action: dispatch to server
                startServerQueue(count);
            }
        },
        [executeAction, startServerQueue],
    );

    const cancelQueue = useCallback(() => {
        if (isServerModeRef.current) {
            // Cancel the server-side queue
            fetch("/action-queue/cancel", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": getCsrfToken(),
                },
            }).then(() => {
                router.reload({ only: ["sidebar"] });
            });
            // The sidebar update will trigger finishQueue via the useEffect
        } else {
            // Cancel the client-side queue
            cancelledRef.current = true;
            isQueueActiveRef.current = false;
            cleanup();
            finishQueue(true);
        }
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
        queueStartedAt,
        totalXp,
    };
}

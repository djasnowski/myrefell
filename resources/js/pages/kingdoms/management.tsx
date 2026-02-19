import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    Anchor,
    Building,
    ChevronDown,
    ChevronRight,
    Crown,
    Home,
    Loader2,
    Mail,
    Shield,
    type LucideIcon,
} from "lucide-react";
import { useMemo, useState } from "react";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Settlement {
    id: number;
    name: string;
    type: "town" | "village" | "hamlet";
    is_capital?: boolean;
    is_port?: boolean;
    population: number;
    ruler: string | null;
    ruler_title: string;
}

interface Barony {
    id: number;
    name: string;
    is_capital: boolean;
    baron_name: string | null;
    settlements: Settlement[];
}

interface RoleHolder {
    player_role_id: number;
    username: string;
    user_id: number;
    role_name: string;
    role_slug: string;
    role_tier: number;
    location_type: string;
    location_id: number;
    location_name: string;
}

interface Props {
    kingdom: {
        id: number;
        name: string;
        baronies: Barony[];
    };
    role_holders: RoleHolder[];
    mail_cost: number;
    current_user_id: number;
}

const locationTypeOrder: Record<string, number> = {
    kingdom: 0,
    barony: 1,
    town: 2,
    village: 3,
};

const locationTypeIcon: Record<string, string> = {
    kingdom: "\u265B",
    barony: "\uD83D\uDEE1\uFE0F",
    town: "\uD83C\uDFDB\uFE0F",
    village: "\uD83C\uDFE0",
};

function RoyalDecreeDialog({
    open,
    onOpenChange,
    targetName,
    roleName,
    playerRoleId,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    targetName: string;
    roleName: string;
    playerRoleId: number;
}) {
    const [reason, setReason] = useState("");
    const [removing, setRemoving] = useState(false);

    const handleRemove = () => {
        setRemoving(true);
        router.post(
            `/roles/${playerRoleId}/remove`,
            { reason: reason || null },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                    onOpenChange(false);
                    setReason("");
                },
                onFinish: () => setRemoving(false),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="border-red-700/60 bg-stone-900 sm:max-w-md">
                <DialogHeader>
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg border border-red-700/50 bg-red-900/30 p-2">
                            <Crown className="h-5 w-5 text-red-400" />
                        </div>
                        <div>
                            <DialogTitle className="font-[Cinzel] text-red-300">
                                Royal Decree of Removal
                            </DialogTitle>
                            <DialogDescription className="text-stone-500">
                                By the King's authority
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <p className="text-sm text-stone-300">
                    Remove <span className="font-bold text-amber-400">{targetName}</span> from their
                    position as <span className="font-bold text-stone-200">{roleName}</span>?
                </p>

                <div>
                    <label className="mb-1 block text-xs text-stone-500">Reason (optional)</label>
                    <textarea
                        value={reason}
                        onChange={(e) => setReason(e.target.value)}
                        maxLength={255}
                        rows={2}
                        className="w-full rounded-lg border border-stone-700 bg-stone-800 px-3 py-2 text-sm text-stone-200 placeholder-stone-600 focus:border-red-600 focus:outline-none"
                        placeholder="State the reason for removal..."
                    />
                </div>

                <DialogFooter>
                    <button
                        onClick={() => onOpenChange(false)}
                        disabled={removing}
                        className="rounded-lg border border-stone-600 px-4 py-2 text-sm text-stone-400 transition hover:bg-stone-800"
                    >
                        Cancel
                    </button>
                    <button
                        onClick={handleRemove}
                        disabled={removing}
                        className="rounded-lg border border-red-700/50 bg-red-900/40 px-4 py-2 text-sm font-bold text-red-300 transition hover:bg-red-900/60 disabled:opacity-50"
                    >
                        {removing ? (
                            <Loader2 className="mx-auto h-4 w-4 animate-spin" />
                        ) : (
                            "Issue Decree"
                        )}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function ComposeMessageDialog({
    open,
    onOpenChange,
    recipientUsername,
    mailCost,
    playerGold,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    recipientUsername: string;
    mailCost: number;
    playerGold: number;
}) {
    const [subject, setSubject] = useState("");
    const [body, setBody] = useState("");
    const [sending, setSending] = useState(false);

    const canAfford = playerGold >= mailCost;

    const handleSend = () => {
        setSending(true);
        router.post(
            "/mail/send",
            {
                recipient_username: recipientUsername,
                subject,
                body,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                    onOpenChange(false);
                    setSubject("");
                    setBody("");
                },
                onFinish: () => setSending(false),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="border-amber-700/60 bg-stone-900 sm:max-w-md">
                <DialogHeader>
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg border border-amber-700/50 bg-amber-900/30 p-2">
                            <Mail className="h-5 w-5 text-amber-400" />
                        </div>
                        <div>
                            <DialogTitle className="font-[Cinzel] text-amber-300">
                                Royal Correspondence
                            </DialogTitle>
                            <DialogDescription className="text-stone-500">
                                Send a message to {recipientUsername} ({mailCost}g)
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div className="space-y-3">
                    <div>
                        <label className="mb-1 block text-xs text-stone-500">Subject</label>
                        <input
                            type="text"
                            value={subject}
                            onChange={(e) => setSubject(e.target.value)}
                            maxLength={100}
                            className="w-full rounded-lg border border-stone-700 bg-stone-800 px-3 py-2 text-sm text-stone-200 placeholder-stone-600 focus:border-amber-600 focus:outline-none"
                            placeholder="Message subject..."
                        />
                    </div>
                    <div>
                        <label className="mb-1 block text-xs text-stone-500">Message</label>
                        <textarea
                            value={body}
                            onChange={(e) => setBody(e.target.value)}
                            maxLength={2000}
                            rows={4}
                            className="w-full rounded-lg border border-stone-700 bg-stone-800 px-3 py-2 text-sm text-stone-200 placeholder-stone-600 focus:border-amber-600 focus:outline-none"
                            placeholder="Write your message..."
                        />
                    </div>
                </div>

                {!canAfford && (
                    <p className="text-xs text-red-400">
                        Not enough gold ({playerGold}g / {mailCost}g required)
                    </p>
                )}

                <DialogFooter>
                    <button
                        onClick={() => onOpenChange(false)}
                        disabled={sending}
                        className="rounded-lg border border-stone-600 px-4 py-2 text-sm text-stone-400 transition hover:bg-stone-800"
                    >
                        Cancel
                    </button>
                    <button
                        onClick={handleSend}
                        disabled={sending || !canAfford || !subject.trim() || !body.trim()}
                        className="rounded-lg border border-amber-700/50 bg-amber-900/40 px-4 py-2 text-sm font-bold text-amber-300 transition hover:bg-amber-900/60 disabled:opacity-50"
                    >
                        {sending ? (
                            <Loader2 className="mx-auto h-4 w-4 animate-spin" />
                        ) : (
                            `Send (${mailCost}g)`
                        )}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function HierarchyTree({ baronies }: { baronies: Barony[] }) {
    const [expandedBaronies, setExpandedBaronies] = useState<Set<number>>(
        new Set(baronies.map((b) => b.id)),
    );

    const toggleBarony = (id: number) => {
        setExpandedBaronies((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    };

    const expandAll = () => {
        setExpandedBaronies(new Set(baronies.map((b) => b.id)));
    };

    const collapseAll = () => {
        setExpandedBaronies(new Set());
    };

    return (
        <div className="space-y-2">
            <div className="mb-3 flex items-center justify-between">
                <h3 className="font-pixel text-sm text-stone-400">Kingdom Hierarchy</h3>
                <div className="flex gap-2">
                    <button
                        onClick={expandAll}
                        className="rounded px-2 py-1 text-xs text-stone-400 transition hover:bg-stone-700 hover:text-stone-200"
                    >
                        Expand All
                    </button>
                    <button
                        onClick={collapseAll}
                        className="rounded px-2 py-1 text-xs text-stone-400 transition hover:bg-stone-700 hover:text-stone-200"
                    >
                        Collapse All
                    </button>
                </div>
            </div>
            <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-4">
                {baronies.map((barony, idx) => {
                    const isExpanded = expandedBaronies.has(barony.id);
                    const isLast = idx === baronies.length - 1;
                    const sortedSettlements = [...barony.settlements].sort((a, b) => {
                        if (a.type === "town" && b.type !== "town") return -1;
                        if (a.type !== "town" && b.type === "town") return 1;
                        if (a.is_capital) return -1;
                        if (b.is_capital) return 1;
                        return a.name.localeCompare(b.name);
                    });

                    return (
                        <div key={barony.id} className={!isLast ? "mb-2" : ""}>
                            <button
                                onClick={() => toggleBarony(barony.id)}
                                className="group flex w-full items-center gap-2 rounded p-1.5 text-left transition hover:bg-stone-700/50"
                            >
                                {isExpanded ? (
                                    <ChevronDown className="h-4 w-4 text-stone-500" />
                                ) : (
                                    <ChevronRight className="h-4 w-4 text-stone-500" />
                                )}
                                <Shield className="h-4 w-4 text-amber-400" />
                                <span className="font-medium text-stone-200">{barony.name}</span>
                                {barony.is_capital && (
                                    <span className="text-xs text-amber-500">(Capital Region)</span>
                                )}
                                <span className="text-xs text-stone-500">—</span>
                                <span className="text-xs">
                                    <span className="text-stone-500">Baron: </span>
                                    {barony.baron_name ? (
                                        <span className="text-amber-400">{barony.baron_name}</span>
                                    ) : (
                                        <span className="italic text-stone-600">Vacant</span>
                                    )}
                                </span>
                                <span className="ml-auto text-xs text-stone-500">
                                    {barony.settlements.length} settlements
                                </span>
                            </button>
                            {isExpanded && sortedSettlements.length > 0 && (
                                <div className="ml-6 mt-1 space-y-1 border-l border-stone-700 pl-4">
                                    {sortedSettlements.map((settlement, sIdx) => {
                                        const isSettlementLast =
                                            sIdx === sortedSettlements.length - 1;
                                        return (
                                            <div
                                                key={`${settlement.type}-${settlement.id}`}
                                                className={`flex items-center gap-2 text-sm ${!isSettlementLast ? "pb-1" : ""}`}
                                            >
                                                {settlement.type === "town" ? (
                                                    <Building className="h-3 w-3 text-purple-400" />
                                                ) : settlement.type === "hamlet" ? (
                                                    <Home className="h-3 w-3 text-stone-500" />
                                                ) : (
                                                    <Home className="h-3 w-3 text-stone-400" />
                                                )}
                                                <span className="text-stone-300">
                                                    {settlement.name}
                                                </span>
                                                {settlement.is_capital && (
                                                    <Crown
                                                        className="h-3 w-3 text-amber-400"
                                                        title="Kingdom Capital"
                                                    />
                                                )}
                                                {settlement.is_port && (
                                                    <Anchor
                                                        className="h-3 w-3 text-blue-400"
                                                        title="Port"
                                                    />
                                                )}
                                                <span className="text-xs text-stone-600">—</span>
                                                <span className="text-xs">
                                                    <span className="text-stone-500">
                                                        {settlement.ruler_title}:{" "}
                                                    </span>
                                                    {settlement.ruler ? (
                                                        <span className="text-amber-400">
                                                            {settlement.ruler}
                                                        </span>
                                                    ) : (
                                                        <span className="italic text-stone-600">
                                                            Vacant
                                                        </span>
                                                    )}
                                                </span>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

function RoleHolderList({
    roleHolders,
    currentUserId,
    mailCost,
    onRemove,
    onMessage,
}: {
    roleHolders: RoleHolder[];
    currentUserId: number;
    mailCost: number;
    onRemove: (holder: RoleHolder) => void;
    onMessage: (holder: RoleHolder) => void;
}) {
    const grouped = useMemo(() => {
        const groups: Record<
            string,
            { locationName: string; locationType: string; holders: RoleHolder[] }
        > = {};
        for (const holder of roleHolders) {
            const key = `${holder.location_type}:${holder.location_id}`;
            if (!groups[key]) {
                groups[key] = {
                    locationName: holder.location_name,
                    locationType: holder.location_type,
                    holders: [],
                };
            }
            groups[key].holders.push(holder);
        }

        for (const group of Object.values(groups)) {
            group.holders.sort((a, b) => b.role_tier - a.role_tier);
        }

        return Object.values(groups).sort((a, b) => {
            const orderA = locationTypeOrder[a.locationType] ?? 99;
            const orderB = locationTypeOrder[b.locationType] ?? 99;
            if (orderA !== orderB) return orderA - orderB;
            return a.locationName.localeCompare(b.locationName);
        });
    }, [roleHolders]);

    if (roleHolders.length === 0) {
        return (
            <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-4 text-center text-sm text-stone-500">
                No active role holders in the kingdom.
            </div>
        );
    }

    return (
        <div className="space-y-2">
            <h3 className="font-pixel text-sm text-stone-400">Role Holders</h3>
            <div className="rounded-lg border border-amber-700/40 bg-stone-800/30 p-3">
                <div className="space-y-4">
                    {grouped.map((group) => (
                        <div key={`${group.locationType}:${group.locationName}`}>
                            <div className="mb-2 flex items-center gap-2 border-b border-stone-700/50 pb-1">
                                <span className="text-sm">
                                    {locationTypeIcon[group.locationType] ?? ""}
                                </span>
                                <span className="text-xs font-medium text-stone-300">
                                    {group.locationName}
                                </span>
                                <span className="text-xs text-stone-600">
                                    ({group.locationType})
                                </span>
                            </div>
                            <div className="space-y-1 pl-1">
                                {group.holders.map((holder) => {
                                    const isSelf = holder.user_id === currentUserId;
                                    return (
                                        <div
                                            key={holder.player_role_id}
                                            className="flex items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-stone-700/30"
                                        >
                                            <span className="text-amber-400">
                                                {holder.username}
                                            </span>
                                            <span className="text-xs text-stone-500">—</span>
                                            <span className="text-xs text-stone-400">
                                                {holder.role_name}
                                            </span>
                                            {isSelf && (
                                                <span className="text-xs text-amber-600">
                                                    (you)
                                                </span>
                                            )}
                                            <span className="ml-auto flex gap-1">
                                                {!isSelf && holder.role_slug !== "king" && (
                                                    <button
                                                        onClick={() => onRemove(holder)}
                                                        title="Remove by royal decree"
                                                        className="rounded px-1.5 py-0.5 text-xs text-red-500/70 transition hover:bg-red-900/30 hover:text-red-400"
                                                    >
                                                        Remove
                                                    </button>
                                                )}
                                                {!isSelf && (
                                                    <button
                                                        onClick={() => onMessage(holder)}
                                                        title={`Send message (${mailCost}g)`}
                                                        className="rounded px-1.5 py-0.5 text-xs text-blue-500/70 transition hover:bg-blue-900/30 hover:text-blue-400"
                                                    >
                                                        <Mail className="h-3 w-3" />
                                                    </button>
                                                )}
                                            </span>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

export default function KingdomManagement({
    kingdom,
    role_holders,
    mail_cost,
    current_user_id,
}: Props) {
    const { flash } = usePage<{ flash?: { success?: string; error?: string } }>().props;
    const sidebarData = usePage().props.sidebar as { player?: { gold?: number } } | undefined;
    const playerGold = sidebarData?.player?.gold ?? 0;

    const [removeTarget, setRemoveTarget] = useState<{
        playerRoleId: number;
        targetName: string;
        roleName: string;
    } | null>(null);
    const [messageTarget, setMessageTarget] = useState<{
        username: string;
    } | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Kingdoms", href: "/kingdoms" },
        { title: kingdom.name, href: `/kingdoms/${kingdom.id}` },
        { title: "Royal Management", href: `/kingdoms/${kingdom.id}/management` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Royal Management — ${kingdom.name}`} />
            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="rounded-lg border border-red-700/50 bg-red-900/30 p-3">
                        <Crown className="h-8 w-8 text-red-400" />
                    </div>
                    <div>
                        <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                            Royal Management
                        </h1>
                        <p className="text-sm text-stone-500">
                            Manage officials and issue decrees for {kingdom.name}
                        </p>
                    </div>
                </div>

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="rounded-lg border border-green-600/50 bg-green-900/20 px-4 py-3">
                        <p className="font-pixel text-sm text-green-300">{flash.success}</p>
                    </div>
                )}
                {flash?.error && (
                    <div className="rounded-lg border border-red-600/50 bg-red-900/20 px-4 py-3">
                        <p className="font-pixel text-sm text-red-300">{flash.error}</p>
                    </div>
                )}

                {/* Two-pane layout */}
                <div className="grid gap-6 lg:grid-cols-[1fr_380px]">
                    <div className="min-w-0">
                        {kingdom.baronies.length > 0 ? (
                            <HierarchyTree baronies={kingdom.baronies} />
                        ) : (
                            <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-4 text-center text-sm text-stone-500">
                                No baronies in this kingdom yet.
                            </div>
                        )}
                    </div>

                    <RoleHolderList
                        roleHolders={role_holders}
                        currentUserId={current_user_id}
                        mailCost={mail_cost}
                        onRemove={(holder) =>
                            setRemoveTarget({
                                playerRoleId: holder.player_role_id,
                                targetName: holder.username,
                                roleName: holder.role_name,
                            })
                        }
                        onMessage={(holder) => setMessageTarget({ username: holder.username })}
                    />
                </div>
            </div>

            {/* Royal Decree Dialog */}
            <RoyalDecreeDialog
                open={removeTarget !== null}
                onOpenChange={(open) => {
                    if (!open) setRemoveTarget(null);
                }}
                targetName={removeTarget?.targetName ?? ""}
                roleName={removeTarget?.roleName ?? ""}
                playerRoleId={removeTarget?.playerRoleId ?? 0}
            />

            {/* Compose Message Dialog */}
            <ComposeMessageDialog
                open={messageTarget !== null}
                onOpenChange={(open) => {
                    if (!open) setMessageTarget(null);
                }}
                recipientUsername={messageTarget?.username ?? ""}
                mailCost={mail_cost}
                playerGold={playerGold}
            />
        </AppLayout>
    );
}

import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    Anchor,
    Award,
    Building,
    ChevronDown,
    ChevronRight,
    Crown,
    Home,
    Loader2,
    Mail,
    Search,
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
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/tooltip";
import type { BreadcrumbItem } from "@/types";

function formatLastActive(isoDate: string | null): string {
    if (!isoDate) return "Never";
    const diff = Date.now() - new Date(isoDate).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return "Just now";
    if (mins < 60) return `${mins}m ago`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    if (days < 30) return `${days}d ago`;
    return `${Math.floor(days / 30)}mo ago`;
}

function formatFullDate(isoDate: string | null): string {
    if (!isoDate) return "Never active";
    return new Date(isoDate).toLocaleString();
}

function LastActive({ date, className }: { date: string | null; className?: string }) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span className={`${lastActiveColor(date)} ${className ?? ""}`}>
                    {formatLastActive(date)}
                </span>
            </TooltipTrigger>
            <TooltipContent>{formatFullDate(date)}</TooltipContent>
        </Tooltip>
    );
}

function lastActiveColor(isoDate: string | null): string {
    if (!isoDate) return "text-stone-600";
    const diff = Date.now() - new Date(isoDate).getTime();
    const hours = diff / 3600000;
    if (hours < 1) return "text-green-400";
    if (hours < 24) return "text-amber-400";
    if (hours < 72) return "text-stone-400";
    return "text-stone-600";
}

interface Settlement {
    id: number;
    name: string;
    type: "town" | "village" | "hamlet";
    is_capital?: boolean;
    is_port?: boolean;
    population: number;
    ruler: string | null;
    ruler_last_active: string | null;
    ruler_title: string;
}

interface Barony {
    id: number;
    name: string;
    is_capital: boolean;
    baron_name: string | null;
    baron_last_active: string | null;
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
    last_active_at: string | null;
}

interface GrantableTitle {
    id: number;
    name: string;
    slug: string;
    tier: number;
    category: string;
    description: string;
    style_of_address: string | null;
    requires_ceremony: boolean;
    domain_type: string | null;
}

interface KingdomSubject {
    id: number;
    username: string;
    primary_title: string | null;
    title_tier: number | null;
}

interface TitledPlayer {
    id: number;
    user_id: number;
    username: string;
    title_name: string;
    title_tier: number;
    category: string | null;
    style_of_address: string | null;
    granted_by: string | null;
    granted_at: string | null;
    acquisition_method: string | null;
    last_active_at: string | null;
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
    grantable_titles: GrantableTitle[];
    kingdom_subjects: KingdomSubject[];
    titled_players: TitledPlayer[];
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

const categoryLabels: Record<string, string> = {
    commoner: "Commoner",
    minor_nobility: "Minor Nobility",
    landed_nobility: "Landed Nobility",
    royalty: "Royalty",
};

const categoryOrder = ["commoner", "minor_nobility", "landed_nobility", "royalty"];

function GrantTitleDialog({
    open,
    onOpenChange,
    grantableTitles,
    subjects,
    kingdomId,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    grantableTitles: GrantableTitle[];
    subjects: KingdomSubject[];
    kingdomId: number;
}) {
    const [selectedSubject, setSelectedSubject] = useState<KingdomSubject | null>(null);
    const [selectedTitle, setSelectedTitle] = useState<GrantableTitle | null>(null);
    const [searchQuery, setSearchQuery] = useState("");
    const [granting, setGranting] = useState(false);
    const [showDropdown, setShowDropdown] = useState(false);

    const filteredSubjects = useMemo(() => {
        if (!searchQuery.trim()) return subjects;
        const q = searchQuery.toLowerCase();
        return subjects.filter((s) => s.username.toLowerCase().includes(q));
    }, [subjects, searchQuery]);

    const groupedTitles = useMemo(() => {
        const groups: Record<string, GrantableTitle[]> = {};
        for (const title of grantableTitles) {
            const cat = title.category;
            if (!groups[cat]) groups[cat] = [];
            groups[cat].push(title);
        }
        return categoryOrder
            .filter((cat) => groups[cat]?.length)
            .map((cat) => ({ category: cat, titles: groups[cat] }));
    }, [grantableTitles]);

    const handleGrant = () => {
        if (!selectedSubject || !selectedTitle) return;
        setGranting(true);
        router.post(
            "/titles/grant",
            {
                recipient_id: selectedSubject.id,
                title_type_id: selectedTitle.id,
                domain_type: "kingdom",
                domain_id: kingdomId,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                    onOpenChange(false);
                    setSelectedSubject(null);
                    setSelectedTitle(null);
                    setSearchQuery("");
                },
                onFinish: () => setGranting(false),
            },
        );
    };

    const handleClose = (isOpen: boolean) => {
        if (!isOpen) {
            setSelectedSubject(null);
            setSelectedTitle(null);
            setSearchQuery("");
            setShowDropdown(false);
        }
        onOpenChange(isOpen);
    };

    return (
        <Dialog open={open} onOpenChange={handleClose}>
            <DialogContent className="border-amber-700/60 bg-stone-900 sm:max-w-lg">
                <DialogHeader>
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg border border-amber-700/50 bg-amber-900/30 p-2">
                            <Award className="h-5 w-5 text-amber-400" />
                        </div>
                        <div>
                            <DialogTitle className="font-[Cinzel] text-amber-300">
                                Grant Royal Title
                            </DialogTitle>
                            <DialogDescription className="text-stone-500">
                                Bestow a title upon a subject of the realm
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div className="space-y-4">
                    {/* Player Search */}
                    <div>
                        <label className="mb-1 block text-xs text-stone-500">Recipient</label>
                        <div className="relative">
                            <Search className="absolute top-2.5 left-3 h-4 w-4 text-stone-500" />
                            <input
                                type="text"
                                value={selectedSubject ? selectedSubject.username : searchQuery}
                                onChange={(e) => {
                                    setSearchQuery(e.target.value);
                                    setSelectedSubject(null);
                                    setShowDropdown(true);
                                }}
                                onFocus={() => setShowDropdown(true)}
                                className="w-full rounded-lg border border-stone-700 bg-stone-800 py-2 pr-3 pl-9 text-sm text-stone-200 placeholder-stone-600 focus:border-amber-600 focus:outline-none"
                                placeholder="Search for a subject..."
                            />
                            {showDropdown && !selectedSubject && filteredSubjects.length > 0 && (
                                <div className="absolute z-10 mt-1 max-h-40 w-full overflow-y-auto rounded-lg border border-stone-700 bg-stone-800 shadow-lg">
                                    {filteredSubjects.slice(0, 20).map((subject) => (
                                        <button
                                            key={subject.id}
                                            onClick={() => {
                                                setSelectedSubject(subject);
                                                setSearchQuery("");
                                                setShowDropdown(false);
                                            }}
                                            className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm transition hover:bg-stone-700"
                                        >
                                            <span className="text-amber-400">
                                                {subject.username}
                                            </span>
                                            {subject.primary_title && (
                                                <span className="text-xs text-stone-500">
                                                    ({subject.primary_title})
                                                </span>
                                            )}
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Title Selector */}
                    <div>
                        <label className="mb-1 block text-xs text-stone-500">Title</label>
                        <select
                            value={selectedTitle?.id ?? ""}
                            onChange={(e) => {
                                const title = grantableTitles.find(
                                    (t) => t.id === Number(e.target.value),
                                );
                                setSelectedTitle(title ?? null);
                            }}
                            className="w-full rounded-lg border border-stone-700 bg-stone-800 px-3 py-2 text-sm text-stone-200 focus:border-amber-600 focus:outline-none"
                        >
                            <option value="">Select a title...</option>
                            {groupedTitles.map((group) => (
                                <optgroup
                                    key={group.category}
                                    label={categoryLabels[group.category] ?? group.category}
                                >
                                    {group.titles.map((title) => (
                                        <option key={title.id} value={title.id}>
                                            {title.name} (Tier {title.tier})
                                        </option>
                                    ))}
                                </optgroup>
                            ))}
                        </select>
                    </div>

                    {/* Selected Title Details */}
                    {selectedTitle && (
                        <div className="rounded-lg border border-stone-700/50 bg-stone-800/50 p-3">
                            <div className="mb-1 flex items-center gap-2">
                                <span className="font-medium text-amber-400">
                                    {selectedTitle.name}
                                </span>
                                <span className="text-xs text-stone-500">
                                    Tier {selectedTitle.tier} &middot;{" "}
                                    {categoryLabels[selectedTitle.category]}
                                </span>
                            </div>
                            <p className="text-xs text-stone-400">{selectedTitle.description}</p>
                            {selectedTitle.style_of_address && (
                                <p className="mt-1 text-xs text-stone-500">
                                    Style of address:{" "}
                                    <span className="text-stone-300">
                                        {selectedTitle.style_of_address}
                                    </span>
                                </p>
                            )}
                            {selectedTitle.requires_ceremony && (
                                <p className="mt-1 text-xs text-amber-600">
                                    Requires a ceremony to complete
                                </p>
                            )}
                        </div>
                    )}
                </div>

                <DialogFooter>
                    <button
                        onClick={() => handleClose(false)}
                        disabled={granting}
                        className="rounded-lg border border-stone-600 px-4 py-2 text-sm text-stone-400 transition hover:bg-stone-800"
                    >
                        Cancel
                    </button>
                    <button
                        onClick={handleGrant}
                        disabled={granting || !selectedSubject || !selectedTitle}
                        className="rounded-lg border border-amber-700/50 bg-amber-900/40 px-4 py-2 text-sm font-bold text-amber-300 transition hover:bg-amber-900/60 disabled:opacity-50"
                    >
                        {granting ? (
                            <Loader2 className="mx-auto h-4 w-4 animate-spin" />
                        ) : (
                            "Grant Title"
                        )}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

const categoryColors: Record<string, string> = {
    commoner: "text-stone-400",
    minor_nobility: "text-blue-400",
    landed_nobility: "text-purple-400",
    royalty: "text-amber-400",
};

const methodLabels: Record<string, string> = {
    appointment: "Appointed",
    election: "Elected",
    petition: "Petition",
    purchase: "Purchased",
    ceremony: "Ceremony",
    signup: "Default",
    inheritance: "Inherited",
    conquest: "Conquest",
};

function TitleHoldersList({ titledPlayers }: { titledPlayers: TitledPlayer[] }) {
    if (titledPlayers.length === 0) {
        return (
            <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-4 text-center text-sm text-stone-500">
                No titled subjects in the kingdom.
            </div>
        );
    }

    return (
        <div className="overflow-x-auto rounded-lg border border-amber-700/40 bg-stone-800/30">
            <table className="w-full">
                <thead>
                    <tr className="border-b border-stone-700/50 text-left text-sm text-stone-500">
                        <th className="px-4 py-3 font-medium">Subject</th>
                        <th className="px-4 py-3 font-medium">Title</th>
                        <th className="hidden px-4 py-3 font-medium sm:table-cell">Rank</th>
                        <th className="hidden px-4 py-3 font-medium md:table-cell">Method</th>
                        <th className="px-4 py-3 font-medium">Granted By</th>
                        <th className="hidden px-4 py-3 font-medium lg:table-cell">Last Active</th>
                        <th className="hidden px-4 py-3 font-medium xl:table-cell">Date</th>
                    </tr>
                </thead>
                <tbody>
                    {titledPlayers.map((player, idx) => {
                        const catColor = categoryColors[player.category ?? ""] ?? "text-stone-400";
                        const prevTier = idx > 0 ? titledPlayers[idx - 1].title_tier : null;
                        const showDivider = prevTier !== null && prevTier !== player.title_tier;

                        return (
                            <tr
                                key={player.id}
                                className={`transition hover:bg-stone-700/30 ${showDivider ? "border-t border-stone-700/40" : ""}`}
                            >
                                <td className="px-4 py-2.5">
                                    <span className="text-amber-400">{player.username}</span>
                                </td>
                                <td className="px-4 py-2.5">
                                    <div className="flex items-center gap-2">
                                        <span className="font-medium text-stone-200">
                                            {player.title_name}
                                        </span>
                                        <span className="text-sm text-stone-600">
                                            T{player.title_tier}
                                        </span>
                                    </div>
                                    {player.style_of_address && (
                                        <div className="text-sm text-stone-600 italic">
                                            {player.style_of_address}
                                        </div>
                                    )}
                                </td>
                                <td className={`hidden px-4 py-2.5 sm:table-cell ${catColor}`}>
                                    {categoryLabels[player.category ?? ""] ?? "—"}
                                </td>
                                <td className="hidden px-4 py-2.5 text-stone-400 md:table-cell">
                                    {methodLabels[player.acquisition_method ?? ""] ?? "—"}
                                </td>
                                <td className="px-4 py-2.5 text-stone-400">
                                    {player.granted_by ?? "—"}
                                </td>
                                <td className="hidden px-4 py-2.5 lg:table-cell">
                                    <LastActive date={player.last_active_at} />
                                </td>
                                <td className="hidden px-4 py-2.5 text-stone-500 xl:table-cell">
                                    {player.granted_at ?? "—"}
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
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
        <div className="space-y-3">
            <div className="mb-3 flex items-center justify-between">
                <h3 className="font-pixel text-stone-400">Kingdom Hierarchy</h3>
                <div className="flex gap-2">
                    <button
                        onClick={expandAll}
                        className="rounded px-2.5 py-1 text-sm text-stone-400 transition hover:bg-stone-700 hover:text-stone-200"
                    >
                        Expand All
                    </button>
                    <button
                        onClick={collapseAll}
                        className="rounded px-2.5 py-1 text-sm text-stone-400 transition hover:bg-stone-700 hover:text-stone-200"
                    >
                        Collapse All
                    </button>
                </div>
            </div>
            <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-5">
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
                        <div key={barony.id} className={!isLast ? "mb-3" : ""}>
                            <button
                                onClick={() => toggleBarony(barony.id)}
                                className="group flex w-full items-center gap-2.5 rounded p-2 text-left transition hover:bg-stone-700/50"
                            >
                                {isExpanded ? (
                                    <ChevronDown className="h-5 w-5 text-stone-500" />
                                ) : (
                                    <ChevronRight className="h-5 w-5 text-stone-500" />
                                )}
                                <Shield className="h-5 w-5 text-amber-400" />
                                <span className="font-medium text-stone-200">{barony.name}</span>
                                {barony.is_capital && (
                                    <span className="text-sm text-amber-500">(Capital Region)</span>
                                )}
                                <span className="text-stone-500">—</span>
                                <span className="text-sm">
                                    <span className="text-stone-500">Baron: </span>
                                    {barony.baron_name ? (
                                        <>
                                            <span className="text-amber-400">
                                                {barony.baron_name}
                                            </span>
                                            <span className="ml-1.5 text-xs">
                                                (<LastActive date={barony.baron_last_active} />)
                                            </span>
                                        </>
                                    ) : (
                                        <span className="italic text-stone-600">Vacant</span>
                                    )}
                                </span>
                                <span className="ml-auto text-sm text-stone-500">
                                    {barony.settlements.length} settlements
                                </span>
                            </button>
                            {isExpanded && sortedSettlements.length > 0 && (
                                <div className="ml-7 mt-1.5 space-y-1.5 border-l border-stone-700 pl-5">
                                    {sortedSettlements.map((settlement, sIdx) => {
                                        const isSettlementLast =
                                            sIdx === sortedSettlements.length - 1;
                                        return (
                                            <div
                                                key={`${settlement.type}-${settlement.id}`}
                                                className={`flex items-center gap-2.5 ${!isSettlementLast ? "pb-1" : ""}`}
                                            >
                                                {settlement.type === "town" ? (
                                                    <Building className="h-4 w-4 text-purple-400" />
                                                ) : settlement.type === "hamlet" ? (
                                                    <Home className="h-4 w-4 text-stone-500" />
                                                ) : (
                                                    <Home className="h-4 w-4 text-stone-400" />
                                                )}
                                                <span className="text-stone-300">
                                                    {settlement.name}
                                                </span>
                                                {settlement.is_capital && (
                                                    <Crown
                                                        className="h-4 w-4 text-amber-400"
                                                        title="Kingdom Capital"
                                                    />
                                                )}
                                                {settlement.is_port && (
                                                    <Anchor
                                                        className="h-4 w-4 text-blue-400"
                                                        title="Port"
                                                    />
                                                )}
                                                <span className="text-stone-600">—</span>
                                                <span>
                                                    <span className="text-stone-500">
                                                        {settlement.ruler_title}:{" "}
                                                    </span>
                                                    {settlement.ruler ? (
                                                        <>
                                                            <span className="text-amber-400">
                                                                {settlement.ruler}
                                                            </span>
                                                            <span className="ml-1.5 text-xs">
                                                                (
                                                                <LastActive
                                                                    date={
                                                                        settlement.ruler_last_active
                                                                    }
                                                                />
                                                                )
                                                            </span>
                                                        </>
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
            <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-4 text-center text-stone-500">
                No active role holders in the kingdom.
            </div>
        );
    }

    return (
        <div className="space-y-3">
            <h3 className="font-pixel text-stone-400">Role Holders</h3>
            <div className="overflow-x-auto rounded-lg border border-amber-700/40 bg-stone-800/30">
                <table className="w-full">
                    <thead>
                        <tr className="border-b border-stone-700/50 text-left text-sm text-stone-500">
                            <th className="px-4 py-3 font-medium">Official</th>
                            <th className="px-4 py-3 font-medium">Role</th>
                            <th className="hidden px-4 py-3 font-medium sm:table-cell">Location</th>
                            <th className="hidden px-4 py-3 font-medium md:table-cell">
                                Last Active
                            </th>
                            <th className="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {grouped.map((group) =>
                            group.holders.map((holder, hIdx) => {
                                const isSelf = holder.user_id === currentUserId;
                                const isFirstInGroup = hIdx === 0;
                                return (
                                    <tr
                                        key={holder.player_role_id}
                                        className={`transition hover:bg-stone-700/30 ${isFirstInGroup && group !== grouped[0] ? "border-t border-stone-700/40" : ""}`}
                                    >
                                        <td className="px-4 py-2.5">
                                            <span className="text-amber-400">
                                                {holder.username}
                                            </span>
                                            {isSelf && (
                                                <span className="ml-1.5 text-sm text-amber-600">
                                                    (you)
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-2.5 text-stone-300">
                                            {holder.role_name}
                                        </td>
                                        <td className="hidden px-4 py-2.5 sm:table-cell">
                                            <div className="flex items-center gap-2">
                                                <span>
                                                    {locationTypeIcon[holder.location_type] ?? ""}
                                                </span>
                                                <span className="text-stone-300">
                                                    {holder.location_name}
                                                </span>
                                                <span className="text-sm text-stone-600">
                                                    ({holder.location_type})
                                                </span>
                                            </div>
                                        </td>
                                        <td className="hidden px-4 py-2.5 md:table-cell">
                                            <LastActive date={holder.last_active_at} />
                                        </td>
                                        <td className="px-4 py-2.5 text-right">
                                            <span className="flex justify-end gap-2">
                                                {!isSelf && holder.role_slug !== "king" && (
                                                    <button
                                                        onClick={() => onRemove(holder)}
                                                        title="Remove by royal decree"
                                                        className="rounded px-2 py-1 text-sm text-red-500/70 transition hover:bg-red-900/30 hover:text-red-400"
                                                    >
                                                        Remove
                                                    </button>
                                                )}
                                                {!isSelf && (
                                                    <button
                                                        onClick={() => onMessage(holder)}
                                                        title={`Send message (${mailCost}g)`}
                                                        className="rounded px-2 py-1 text-sm text-blue-500/70 transition hover:bg-blue-900/30 hover:text-blue-400"
                                                    >
                                                        <Mail className="h-4 w-4" />
                                                    </button>
                                                )}
                                            </span>
                                        </td>
                                    </tr>
                                );
                            }),
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

type Tab = "hierarchy" | "roles" | "titles";

const tabs: { key: Tab; label: string; icon: LucideIcon }[] = [
    { key: "hierarchy", label: "Hierarchy", icon: Shield },
    { key: "roles", label: "Role Holders", icon: Crown },
    { key: "titles", label: "Titles", icon: Award },
];

export default function KingdomManagement({
    kingdom,
    role_holders,
    mail_cost,
    current_user_id,
    grantable_titles,
    kingdom_subjects,
    titled_players,
}: Props) {
    const { flash } = usePage<{ flash?: { success?: string; error?: string } }>().props;
    const sidebarData = usePage().props.sidebar as { player?: { gold?: number } } | undefined;
    const playerGold = sidebarData?.player?.gold ?? 0;

    const [activeTab, setActiveTab] = useState<Tab>("hierarchy");
    const [removeTarget, setRemoveTarget] = useState<{
        playerRoleId: number;
        targetName: string;
        roleName: string;
    } | null>(null);
    const [messageTarget, setMessageTarget] = useState<{
        username: string;
    } | null>(null);
    const [grantDialogOpen, setGrantDialogOpen] = useState(false);

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

                {/* Tabs */}
                <div className="flex gap-1 border-b border-stone-700/50">
                    {tabs.map((tab) => {
                        const Icon = tab.icon;
                        const isActive = activeTab === tab.key;
                        return (
                            <button
                                key={tab.key}
                                onClick={() => setActiveTab(tab.key)}
                                className={`flex items-center gap-1.5 border-b-2 px-4 py-2 text-sm transition ${
                                    isActive
                                        ? "border-amber-500 text-amber-300"
                                        : "border-transparent text-stone-500 hover:text-stone-300"
                                }`}
                            >
                                <Icon className="h-3.5 w-3.5" />
                                {tab.label}
                            </button>
                        );
                    })}
                </div>

                {/* Tab Content */}
                {activeTab === "hierarchy" && (
                    <div>
                        {kingdom.baronies.length > 0 ? (
                            <HierarchyTree baronies={kingdom.baronies} />
                        ) : (
                            <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-4 text-center text-sm text-stone-500">
                                No baronies in this kingdom yet.
                            </div>
                        )}
                    </div>
                )}

                {activeTab === "roles" && (
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
                )}

                {activeTab === "titles" && (
                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <h3 className="font-pixel text-sm text-stone-400">Title Holders</h3>
                            {grantable_titles.length > 0 && (
                                <button
                                    onClick={() => setGrantDialogOpen(true)}
                                    className="flex items-center gap-1.5 rounded-lg border border-amber-700/50 bg-amber-900/30 px-3 py-1.5 text-xs font-medium text-amber-300 transition hover:bg-amber-900/50"
                                >
                                    <Award className="h-3.5 w-3.5" />
                                    Grant Title
                                </button>
                            )}
                        </div>
                        <TitleHoldersList titledPlayers={titled_players} />
                    </div>
                )}
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

            {/* Grant Title Dialog */}
            <GrantTitleDialog
                open={grantDialogOpen}
                onOpenChange={setGrantDialogOpen}
                grantableTitles={grantable_titles}
                subjects={kingdom_subjects}
                kingdomId={kingdom.id}
            />
        </AppLayout>
    );
}

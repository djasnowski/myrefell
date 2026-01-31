import { Head, router, usePage } from "@inertiajs/react";
import {
    Award,
    Ban,
    Briefcase,
    Building2,
    Check,
    ChevronDown,
    ChevronUp,
    Clock,
    Coins,
    Crown,
    FileText,
    Gavel,
    Heart,
    Loader2,
    MapPin,
    Shield,
    Swords,
    User,
    Users,
    Vote,
    X,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface ManumissionRequest {
    id: number;
    type: string;
    type_display: string;
    status: string;
    gold_offered: number;
    reason: string | null;
    response_message: string | null;
    created_at: string;
    responded_at: string | null;
}

interface EnnoblementRequest {
    id: number;
    type: string;
    type_display: string;
    status: string;
    gold_offered: number;
    reason: string | null;
    response_message: string | null;
    title_granted: string | null;
    created_at: string;
    responded_at: string | null;
}

interface ClassHistory {
    old_class: string;
    new_class: string;
    reason: string;
    granted_by: string | null;
    created_at: string;
}

interface Rights {
    can_vote: boolean;
    can_join_guild: boolean;
    can_own_business: boolean;
    can_own_property: boolean;
    can_hold_high_office: boolean;
    can_freely_travel: boolean;
}

interface PageProps {
    player: {
        id: number;
        username: string;
        social_class: string;
        social_class_display: string;
        bound_to_barony: { id: number; name: string } | null;
        labor_days_owed: number;
        labor_days_completed: number;
        remaining_labor_days: number;
        gold: number;
    };
    rights: Rights;
    manumission_requests: ManumissionRequest[];
    ennoblement_requests: EnnoblementRequest[];
    class_history: ClassHistory[];
    manumission_cost: number;
    ennoblement_cost: number;
    [key: string]: unknown;
}

const classColors: Record<string, string> = {
    serf: "text-stone-400 border-stone-500 bg-stone-900/50",
    freeman: "text-green-300 border-green-500 bg-green-900/50",
    burgher: "text-blue-300 border-blue-500 bg-blue-900/50",
    noble: "text-purple-300 border-purple-500 bg-purple-900/50",
    clergy: "text-amber-300 border-amber-500 bg-amber-900/50",
};

const statusColors: Record<string, string> = {
    pending: "text-yellow-300 bg-yellow-900/50",
    approved: "text-green-300 bg-green-900/50",
    denied: "text-red-300 bg-red-900/50",
    cancelled: "text-stone-400 bg-stone-800/50",
};

function RightBadge({ name, has }: { name: string; has: boolean }) {
    return (
        <div
            className={`flex items-center gap-2 rounded-lg border px-3 py-2 ${
                has ? "border-green-600/50 bg-green-900/20" : "border-red-600/50 bg-red-900/20"
            }`}
        >
            {has ? (
                <Check className="h-4 w-4 text-green-400" />
            ) : (
                <X className="h-4 w-4 text-red-400" />
            )}
            <span className={`font-pixel text-xs ${has ? "text-green-300" : "text-red-300"}`}>
                {name}
            </span>
        </div>
    );
}

function RequestCard({
    request,
    type,
    onCancel,
    cancelLoading,
}: {
    request: ManumissionRequest | EnnoblementRequest;
    type: "manumission" | "ennoblement";
    onCancel: (id: number) => void;
    cancelLoading: number | null;
}) {
    const isPending = request.status === "pending";
    const isEnnoblement = type === "ennoblement";
    const ennoblementRequest = request as EnnoblementRequest;

    return (
        <div className="rounded-lg border border-stone-600/50 bg-stone-800/50 p-3">
            <div className="mb-2 flex items-start justify-between">
                <div>
                    <span className="font-pixel text-sm text-amber-300">
                        {request.type_display}
                    </span>
                    <div className="flex items-center gap-2">
                        <span
                            className={`rounded px-1.5 py-0.5 font-pixel text-[10px] ${statusColors[request.status]}`}
                        >
                            {request.status}
                        </span>
                        {isEnnoblement && ennoblementRequest.title_granted && (
                            <span className="font-pixel text-[10px] text-purple-300">
                                Title: {ennoblementRequest.title_granted}
                            </span>
                        )}
                    </div>
                </div>
                {request.gold_offered > 0 && (
                    <div className="flex items-center gap-1">
                        <Coins className="h-3 w-3 text-amber-400" />
                        <span className="font-pixel text-xs text-amber-300">
                            {request.gold_offered.toLocaleString()}g
                        </span>
                    </div>
                )}
            </div>

            {request.reason && <p className="mb-2 text-xs text-stone-400">{request.reason}</p>}

            {request.response_message && (
                <div className="mb-2 rounded bg-stone-900/50 p-2">
                    <span className="font-pixel text-[10px] text-stone-500">Response:</span>
                    <p className="text-xs text-stone-300">{request.response_message}</p>
                </div>
            )}

            <div className="flex items-center justify-between text-[10px] text-stone-500">
                <span>Submitted {request.created_at}</span>
                {request.responded_at && <span>Responded {request.responded_at}</span>}
            </div>

            {isPending && (
                <button
                    onClick={() => onCancel(request.id)}
                    disabled={cancelLoading === request.id}
                    className="mt-2 flex w-full items-center justify-center gap-1 rounded border border-red-600/50 bg-red-900/20 px-2 py-1 font-pixel text-[10px] text-red-300 transition hover:bg-red-800/30 disabled:opacity-50"
                >
                    {cancelLoading === request.id ? (
                        <Loader2 className="h-3 w-3 animate-spin" />
                    ) : (
                        <X className="h-3 w-3" />
                    )}
                    Cancel Request
                </button>
            )}
        </div>
    );
}

function ManumissionForm({
    gold,
    cost,
    onSubmit,
    loading,
}: {
    gold: number;
    cost: number;
    onSubmit: (data: { request_type: string; reason?: string; gold_offered?: number }) => void;
    loading: boolean;
}) {
    const [requestType, setRequestType] = useState("decree");
    const [reason, setReason] = useState("");
    const [goldOffered, setGoldOffered] = useState(cost);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        onSubmit({
            request_type: requestType,
            reason: reason || undefined,
            gold_offered: requestType === "purchase" ? goldOffered : undefined,
        });
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-3">
            <div>
                <label className="mb-1 block font-pixel text-xs text-stone-400">Request Type</label>
                <select
                    value={requestType}
                    onChange={(e) => setRequestType(e.target.value)}
                    className="w-full rounded border border-stone-600 bg-stone-800 p-2 font-pixel text-xs text-stone-200"
                >
                    <option value="decree">By Baron's Decree</option>
                    <option value="purchase">Purchase Freedom ({cost.toLocaleString()}g)</option>
                    <option value="military_service">Military Service</option>
                    <option value="exceptional_service">Exceptional Service</option>
                </select>
            </div>

            {requestType === "purchase" && (
                <div>
                    <label className="mb-1 block font-pixel text-xs text-stone-400">
                        Gold Offered
                    </label>
                    <input
                        type="number"
                        value={goldOffered}
                        onChange={(e) => setGoldOffered(Number(e.target.value))}
                        min={cost}
                        max={gold}
                        className="w-full rounded border border-stone-600 bg-stone-800 p-2 font-pixel text-xs text-stone-200"
                    />
                    <p className="mt-1 font-pixel text-[10px] text-stone-500">
                        Your gold: {gold.toLocaleString()} | Minimum: {cost.toLocaleString()}
                    </p>
                </div>
            )}

            <div>
                <label className="mb-1 block font-pixel text-xs text-stone-400">
                    Reason (Optional)
                </label>
                <textarea
                    value={reason}
                    onChange={(e) => setReason(e.target.value)}
                    placeholder="Why do you deserve freedom?"
                    maxLength={1000}
                    rows={3}
                    className="w-full rounded border border-stone-600 bg-stone-800 p-2 font-pixel text-xs text-stone-200"
                />
            </div>

            <button
                type="submit"
                disabled={loading || (requestType === "purchase" && gold < cost)}
                className="flex w-full items-center justify-center gap-2 rounded border-2 border-green-600/50 bg-green-900/20 px-4 py-2 font-pixel text-xs text-green-300 transition hover:bg-green-800/30 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {loading ? (
                    <Loader2 className="h-4 w-4 animate-spin" />
                ) : (
                    <FileText className="h-4 w-4" />
                )}
                Submit Request
            </button>
        </form>
    );
}

function EnnoblementForm({
    gold,
    cost,
    onSubmit,
    loading,
}: {
    gold: number;
    cost: number;
    onSubmit: (data: {
        kingdom_id: number;
        request_type: string;
        reason?: string;
        gold_offered?: number;
    }) => void;
    loading: boolean;
}) {
    const [requestType, setRequestType] = useState("royal_decree");
    const [reason, setReason] = useState("");
    const [goldOffered, setGoldOffered] = useState(cost);
    const [kingdomId, setKingdomId] = useState(1); // Default to first kingdom

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        onSubmit({
            kingdom_id: kingdomId,
            request_type: requestType,
            reason: reason || undefined,
            gold_offered: requestType === "purchase" ? goldOffered : undefined,
        });
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-3">
            <div>
                <label className="mb-1 block font-pixel text-xs text-stone-400">Kingdom</label>
                <input
                    type="number"
                    value={kingdomId}
                    onChange={(e) => setKingdomId(Number(e.target.value))}
                    min={1}
                    className="w-full rounded border border-stone-600 bg-stone-800 p-2 font-pixel text-xs text-stone-200"
                />
            </div>

            <div>
                <label className="mb-1 block font-pixel text-xs text-stone-400">Request Type</label>
                <select
                    value={requestType}
                    onChange={(e) => setRequestType(e.target.value)}
                    className="w-full rounded border border-stone-600 bg-stone-800 p-2 font-pixel text-xs text-stone-200"
                >
                    <option value="royal_decree">By Royal Decree</option>
                    <option value="purchase">Purchase Title ({cost.toLocaleString()}g)</option>
                    <option value="military_service">Military Service</option>
                    <option value="marriage">Marriage into Nobility</option>
                </select>
            </div>

            {requestType === "purchase" && (
                <div>
                    <label className="mb-1 block font-pixel text-xs text-stone-400">
                        Gold Offered
                    </label>
                    <input
                        type="number"
                        value={goldOffered}
                        onChange={(e) => setGoldOffered(Number(e.target.value))}
                        min={cost}
                        max={gold}
                        className="w-full rounded border border-stone-600 bg-stone-800 p-2 font-pixel text-xs text-stone-200"
                    />
                    <p className="mt-1 font-pixel text-[10px] text-stone-500">
                        Your gold: {gold.toLocaleString()} | Minimum: {cost.toLocaleString()}
                    </p>
                </div>
            )}

            <div>
                <label className="mb-1 block font-pixel text-xs text-stone-400">
                    Reason (Optional)
                </label>
                <textarea
                    value={reason}
                    onChange={(e) => setReason(e.target.value)}
                    placeholder="Why do you deserve ennoblement?"
                    maxLength={1000}
                    rows={3}
                    className="w-full rounded border border-stone-600 bg-stone-800 p-2 font-pixel text-xs text-stone-200"
                />
            </div>

            <button
                type="submit"
                disabled={loading || (requestType === "purchase" && gold < cost)}
                className="flex w-full items-center justify-center gap-2 rounded border-2 border-purple-600/50 bg-purple-900/20 px-4 py-2 font-pixel text-xs text-purple-300 transition hover:bg-purple-800/30 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {loading ? (
                    <Loader2 className="h-4 w-4 animate-spin" />
                ) : (
                    <Crown className="h-4 w-4" />
                )}
                Submit Request
            </button>
        </form>
    );
}

export default function SocialClassIndex() {
    const {
        player,
        rights,
        manumission_requests,
        ennoblement_requests,
        class_history,
        manumission_cost,
        ennoblement_cost,
    } = usePage<PageProps>().props;

    const [manumissionLoading, setManumissionLoading] = useState(false);
    const [ennoblementLoading, setEnnoblementLoading] = useState(false);
    const [burgherLoading, setBurgherLoading] = useState(false);
    const [cancelLoading, setCancelLoading] = useState<number | null>(null);
    const [showManumissionForm, setShowManumissionForm] = useState(false);
    const [showEnnoblementForm, setShowEnnoblementForm] = useState(false);
    const [showHistory, setShowHistory] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Social Class", href: "#" },
    ];

    const isSerf = player.social_class === "serf";
    const isFreeman = player.social_class === "freeman";
    const isNoble = player.social_class === "noble";
    const hasPendingManumission = manumission_requests.some((r) => r.status === "pending");
    const hasPendingEnnoblement = ennoblement_requests.some((r) => r.status === "pending");

    const handleManumission = (data: {
        request_type: string;
        reason?: string;
        gold_offered?: number;
    }) => {
        setManumissionLoading(true);
        router.post("/social-class/manumission", data, {
            preserveScroll: true,
            onFinish: () => {
                setManumissionLoading(false);
                setShowManumissionForm(false);
            },
        });
    };

    const handleEnnoblement = (data: {
        kingdom_id: number;
        request_type: string;
        reason?: string;
        gold_offered?: number;
    }) => {
        setEnnoblementLoading(true);
        router.post("/social-class/ennoblement", data, {
            preserveScroll: true,
            onFinish: () => {
                setEnnoblementLoading(false);
                setShowEnnoblementForm(false);
            },
        });
    };

    const handleBecomeBurgher = () => {
        setBurgherLoading(true);
        router.post(
            "/social-class/burgher",
            {},
            {
                preserveScroll: true,
                onFinish: () => setBurgherLoading(false),
            },
        );
    };

    const handleCancelManumission = (id: number) => {
        setCancelLoading(id);
        router.post(
            `/social-class/manumission/${id}/cancel`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setCancelLoading(null),
            },
        );
    };

    const handleCancelEnnoblement = (id: number) => {
        setCancelLoading(id);
        router.post(
            `/social-class/ennoblement/${id}/cancel`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setCancelLoading(null),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Social Class" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-y-auto p-4">
                {/* Current Class Status */}
                <div className={`rounded-xl border-2 p-4 ${classColors[player.social_class]}`}>
                    <div className="mb-4 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-stone-800/50 p-3">
                                <User className="h-6 w-6" />
                            </div>
                            <div>
                                <h1 className="font-pixel text-xl">
                                    {player.social_class_display}
                                </h1>
                                <p className="font-pixel text-xs text-stone-400">
                                    {player.username}
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Coins className="h-5 w-5 text-amber-400" />
                            <span className="font-pixel text-lg text-amber-300">
                                {player.gold.toLocaleString()}g
                            </span>
                        </div>
                    </div>

                    {/* Serf-specific info */}
                    {isSerf && player.bound_to_barony && (
                        <div className="rounded-lg border border-stone-600/50 bg-stone-800/50 p-3">
                            <div className="mb-2 flex items-center gap-2">
                                <MapPin className="h-4 w-4 text-stone-400" />
                                <span className="font-pixel text-sm text-stone-300">
                                    Bound to: {player.bound_to_barony.name}
                                </span>
                            </div>
                            <div className="flex items-center gap-4">
                                <div className="flex items-center gap-2">
                                    <Clock className="h-4 w-4 text-amber-400" />
                                    <span className="font-pixel text-xs text-stone-400">
                                        Labor Days:
                                    </span>
                                    <span className="font-pixel text-sm text-amber-300">
                                        {player.labor_days_completed} / {player.labor_days_owed}
                                    </span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <span className="font-pixel text-xs text-stone-400">
                                        Remaining:
                                    </span>
                                    <span
                                        className={`font-pixel text-sm ${player.remaining_labor_days > 0 ? "text-red-300" : "text-green-300"}`}
                                    >
                                        {player.remaining_labor_days}
                                    </span>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Rights Grid */}
                <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                    <h2 className="mb-3 flex items-center gap-2 font-pixel text-lg text-amber-300">
                        <Shield className="h-5 w-5" />
                        Your Rights
                    </h2>
                    <div className="grid grid-cols-2 gap-2 md:grid-cols-3">
                        <RightBadge name="Vote in Elections" has={rights.can_vote} />
                        <RightBadge name="Join Guilds" has={rights.can_join_guild} />
                        <RightBadge name="Own Business" has={rights.can_own_business} />
                        <RightBadge name="Own Property" has={rights.can_own_property} />
                        <RightBadge name="Hold High Office" has={rights.can_hold_high_office} />
                        <RightBadge name="Travel Freely" has={rights.can_freely_travel} />
                    </div>

                    {/* Class Traits */}
                    <div className="mt-3 flex flex-wrap gap-2">
                        {isSerf && (
                            <span className="flex items-center gap-1 rounded bg-red-900/30 px-2 py-1 font-pixel text-[10px] text-red-300">
                                <Ban className="h-3 w-3" /> Bound to Land
                            </span>
                        )}
                        {rights.can_vote && (
                            <span className="flex items-center gap-1 rounded bg-blue-900/30 px-2 py-1 font-pixel text-[10px] text-blue-300">
                                <Vote className="h-3 w-3" /> Voter
                            </span>
                        )}
                        {rights.can_own_business && (
                            <span className="flex items-center gap-1 rounded bg-green-900/30 px-2 py-1 font-pixel text-[10px] text-green-300">
                                <Briefcase className="h-3 w-3" /> Business Rights
                            </span>
                        )}
                        {rights.can_hold_high_office && (
                            <span className="flex items-center gap-1 rounded bg-purple-900/30 px-2 py-1 font-pixel text-[10px] text-purple-300">
                                <Gavel className="h-3 w-3" /> Office Holder
                            </span>
                        )}
                        {isNoble && (
                            <span className="flex items-center gap-1 rounded bg-amber-900/30 px-2 py-1 font-pixel text-[10px] text-amber-300">
                                <Swords className="h-3 w-3" /> Noble Arms
                            </span>
                        )}
                        {rights.can_join_guild && (
                            <span className="flex items-center gap-1 rounded bg-stone-700/50 px-2 py-1 font-pixel text-[10px] text-stone-300">
                                <Users className="h-3 w-3" /> Guild Member
                            </span>
                        )}
                        {!isSerf && (
                            <span className="flex items-center gap-1 rounded bg-pink-900/30 px-2 py-1 font-pixel text-[10px] text-pink-300">
                                <Heart className="h-3 w-3" /> Free Citizen
                            </span>
                        )}
                    </div>
                </div>

                {/* Actions */}
                <div className="grid gap-4 md:grid-cols-2">
                    {/* Manumission (for Serfs) */}
                    {isSerf && (
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                            <h2 className="mb-3 flex items-center gap-2 font-pixel text-lg text-green-300">
                                <Award className="h-5 w-5" />
                                Request Manumission
                            </h2>
                            <p className="mb-3 text-xs text-stone-400">
                                Petition your baron for freedom. You can purchase it for{" "}
                                {manumission_cost.toLocaleString()} gold, or request it through
                                service.
                            </p>

                            {hasPendingManumission ? (
                                <div className="rounded-lg border border-yellow-600/50 bg-yellow-900/20 p-3">
                                    <p className="font-pixel text-xs text-yellow-300">
                                        You already have a pending manumission request.
                                    </p>
                                </div>
                            ) : (
                                <>
                                    <button
                                        onClick={() => setShowManumissionForm(!showManumissionForm)}
                                        className="flex w-full items-center justify-between rounded-lg border border-stone-600/50 bg-stone-700/50 px-4 py-2 font-pixel text-xs text-stone-200 transition hover:bg-stone-600/50"
                                    >
                                        <span>Request Freedom</span>
                                        {showManumissionForm ? (
                                            <ChevronUp className="h-4 w-4" />
                                        ) : (
                                            <ChevronDown className="h-4 w-4" />
                                        )}
                                    </button>
                                    {showManumissionForm && (
                                        <div className="mt-3">
                                            <ManumissionForm
                                                gold={player.gold}
                                                cost={manumission_cost}
                                                onSubmit={handleManumission}
                                                loading={manumissionLoading}
                                            />
                                        </div>
                                    )}
                                </>
                            )}
                        </div>
                    )}

                    {/* Become Burgher (for Freeman) */}
                    {isFreeman && (
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                            <h2 className="mb-3 flex items-center gap-2 font-pixel text-lg text-blue-300">
                                <Building2 className="h-5 w-5" />
                                Become a Burgher
                            </h2>
                            <p className="mb-3 text-xs text-stone-400">
                                Town citizens with full economic rights. Requires a home in a town.
                            </p>
                            <button
                                onClick={handleBecomeBurgher}
                                disabled={burgherLoading}
                                className="flex w-full items-center justify-center gap-2 rounded border-2 border-blue-600/50 bg-blue-900/20 px-4 py-2 font-pixel text-xs text-blue-300 transition hover:bg-blue-800/30 disabled:opacity-50"
                            >
                                {burgherLoading ? (
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                ) : (
                                    <Building2 className="h-4 w-4" />
                                )}
                                Become Burgher
                            </button>
                        </div>
                    )}

                    {/* Ennoblement (for non-Nobles, non-Serfs) */}
                    {!isSerf && !isNoble && (
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                            <h2 className="mb-3 flex items-center gap-2 font-pixel text-lg text-purple-300">
                                <Crown className="h-5 w-5" />
                                Request Ennoblement
                            </h2>
                            <p className="mb-3 text-xs text-stone-400">
                                Petition the king for a noble title. You can purchase it for{" "}
                                {ennoblement_cost.toLocaleString()} gold, or earn it through
                                service.
                            </p>

                            {hasPendingEnnoblement ? (
                                <div className="rounded-lg border border-yellow-600/50 bg-yellow-900/20 p-3">
                                    <p className="font-pixel text-xs text-yellow-300">
                                        You already have a pending ennoblement request.
                                    </p>
                                </div>
                            ) : (
                                <>
                                    <button
                                        onClick={() => setShowEnnoblementForm(!showEnnoblementForm)}
                                        className="flex w-full items-center justify-between rounded-lg border border-stone-600/50 bg-stone-700/50 px-4 py-2 font-pixel text-xs text-stone-200 transition hover:bg-stone-600/50"
                                    >
                                        <span>Request Noble Title</span>
                                        {showEnnoblementForm ? (
                                            <ChevronUp className="h-4 w-4" />
                                        ) : (
                                            <ChevronDown className="h-4 w-4" />
                                        )}
                                    </button>
                                    {showEnnoblementForm && (
                                        <div className="mt-3">
                                            <EnnoblementForm
                                                gold={player.gold}
                                                cost={ennoblement_cost}
                                                onSubmit={handleEnnoblement}
                                                loading={ennoblementLoading}
                                            />
                                        </div>
                                    )}
                                </>
                            )}
                        </div>
                    )}
                </div>

                {/* Pending Requests */}
                {(manumission_requests.length > 0 || ennoblement_requests.length > 0) && (
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <h2 className="mb-3 flex items-center gap-2 font-pixel text-lg text-amber-300">
                            <FileText className="h-5 w-5" />
                            Your Requests
                        </h2>
                        <div className="grid gap-3 md:grid-cols-2">
                            {manumission_requests.map((req) => (
                                <RequestCard
                                    key={`m-${req.id}`}
                                    request={req}
                                    type="manumission"
                                    onCancel={handleCancelManumission}
                                    cancelLoading={cancelLoading}
                                />
                            ))}
                            {ennoblement_requests.map((req) => (
                                <RequestCard
                                    key={`e-${req.id}`}
                                    request={req}
                                    type="ennoblement"
                                    onCancel={handleCancelEnnoblement}
                                    cancelLoading={cancelLoading}
                                />
                            ))}
                        </div>
                    </div>
                )}

                {/* Class History */}
                {class_history.length > 0 && (
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <button
                            onClick={() => setShowHistory(!showHistory)}
                            className="flex w-full items-center justify-between"
                        >
                            <h2 className="flex items-center gap-2 font-pixel text-lg text-amber-300">
                                <Clock className="h-5 w-5" />
                                Class History
                            </h2>
                            {showHistory ? (
                                <ChevronUp className="h-5 w-5 text-stone-400" />
                            ) : (
                                <ChevronDown className="h-5 w-5 text-stone-400" />
                            )}
                        </button>

                        {showHistory && (
                            <div className="mt-3 space-y-2">
                                {class_history.map((entry, i) => (
                                    <div
                                        key={i}
                                        className="rounded-lg border border-stone-600/50 bg-stone-800/50 p-2"
                                    >
                                        <div className="flex items-center gap-2">
                                            <span className="font-pixel text-xs text-stone-400">
                                                {entry.old_class}
                                            </span>
                                            <span className="text-stone-500">&rarr;</span>
                                            <span className="font-pixel text-xs text-amber-300">
                                                {entry.new_class}
                                            </span>
                                        </div>
                                        <p className="text-[10px] text-stone-400">{entry.reason}</p>
                                        <div className="flex items-center justify-between text-[10px] text-stone-500">
                                            {entry.granted_by && (
                                                <span>By: {entry.granted_by}</span>
                                            )}
                                            <span>{entry.created_at}</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

import { Head, Link, router } from "@inertiajs/react";
import {
    AlertTriangle,
    Calendar,
    ChevronLeft,
    Crown,
    Handshake,
    Heart,
    History,
    Shield,
    Swords,
    X,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Alliance {
    id: number;
    type: "marriage" | "pact" | "blood_oath";
    status: "active" | "broken" | "expired";
    other_dynasty: {
        id: number;
        name: string;
        prestige: number;
        head: string | null;
    };
    marriage?: {
        id: number;
        spouse1: string;
        spouse2: string;
    } | null;
    terms: string[] | null;
    formed_at: string;
    expires_at: string | null;
    ended_at: string | null;
    can_break: boolean;
}

interface Dynasty {
    id: number;
    name: string;
    prestige: number;
}

interface Props {
    dynasty: Dynasty;
    active_alliances: Alliance[];
    past_alliances: Alliance[];
    is_head: boolean;
}

const ALLIANCE_ICONS = {
    marriage: Heart,
    pact: Handshake,
    blood_oath: Swords,
};

const ALLIANCE_COLORS = {
    marriage: { border: "border-pink-600/50", bg: "bg-pink-900/20", text: "text-pink-400" },
    pact: { border: "border-blue-600/50", bg: "bg-blue-900/20", text: "text-blue-400" },
    blood_oath: { border: "border-red-600/50", bg: "bg-red-900/20", text: "text-red-400" },
};

const ALLIANCE_LABELS = {
    marriage: "Marriage Alliance",
    pact: "Diplomatic Pact",
    blood_oath: "Blood Oath",
};

export default function DynastyAlliances({
    dynasty,
    active_alliances,
    past_alliances,
    is_head,
}: Props) {
    const [breaking, setBreaking] = useState<number | null>(null);
    const [confirmBreak, setConfirmBreak] = useState<number | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Dynasty", href: "/dynasty" },
        { title: "Alliances", href: "/dynasty/alliances" },
    ];

    const handleBreakAlliance = (allianceId: number) => {
        setBreaking(allianceId);
        router.post(
            `/dynasty/alliances/${allianceId}/break`,
            {},
            {
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => {
                    setBreaking(null);
                    setConfirmBreak(null);
                },
            },
        );
    };

    const AllianceCard = ({
        alliance,
        showActions = false,
    }: {
        alliance: Alliance;
        showActions?: boolean;
    }) => {
        const Icon = ALLIANCE_ICONS[alliance.type];
        const colors = ALLIANCE_COLORS[alliance.type];
        const isExpired = alliance.status === "expired";
        const isBroken = alliance.status === "broken";

        return (
            <div
                className={`rounded-lg border ${
                    isExpired || isBroken
                        ? "border-stone-700 bg-stone-800/30 opacity-75"
                        : `${colors.border} ${colors.bg}`
                } p-4`}
            >
                <div className="flex items-start justify-between gap-3">
                    <div className="flex items-start gap-3">
                        <div
                            className={`rounded-lg p-2 ${
                                isExpired || isBroken ? "bg-stone-800" : colors.bg
                            }`}
                        >
                            <Icon
                                className={`h-5 w-5 ${
                                    isExpired || isBroken ? "text-stone-500" : colors.text
                                }`}
                            />
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="font-pixel text-sm text-stone-200">
                                    House {alliance.other_dynasty.name}
                                </span>
                                {isBroken && (
                                    <span className="rounded bg-red-900/50 px-1.5 py-0.5 font-pixel text-[10px] text-red-400">
                                        Broken
                                    </span>
                                )}
                                {isExpired && (
                                    <span className="rounded bg-stone-700 px-1.5 py-0.5 font-pixel text-[10px] text-stone-400">
                                        Expired
                                    </span>
                                )}
                            </div>
                            <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                {ALLIANCE_LABELS[alliance.type]}
                            </div>
                            {alliance.other_dynasty.head && (
                                <div className="mt-1 flex items-center gap-1 font-pixel text-[10px] text-stone-500">
                                    <Crown className="h-3 w-3" />
                                    Led by {alliance.other_dynasty.head}
                                </div>
                            )}
                        </div>
                    </div>
                    <div className="text-right">
                        <div className="font-pixel text-xs text-amber-400">
                            {alliance.other_dynasty.prestige.toLocaleString()} prestige
                        </div>
                    </div>
                </div>

                {/* Marriage info */}
                {alliance.type === "marriage" && alliance.marriage && (
                    <div className="mt-3 rounded-lg border border-stone-700 bg-stone-800/30 p-2">
                        <div className="flex items-center gap-2 font-pixel text-xs text-stone-400">
                            <Heart className="h-3 w-3 text-pink-400" />
                            {alliance.marriage.spouse1} & {alliance.marriage.spouse2}
                        </div>
                    </div>
                )}

                {/* Terms */}
                {alliance.terms && alliance.terms.length > 0 && (
                    <div className="mt-3 space-y-1">
                        <div className="font-pixel text-[10px] text-stone-500">Terms:</div>
                        <ul className="space-y-1">
                            {alliance.terms.map((term, i) => (
                                <li
                                    key={i}
                                    className="flex items-center gap-2 font-pixel text-xs text-stone-400"
                                >
                                    <span className="h-1 w-1 rounded-full bg-stone-500" />
                                    {term}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {/* Dates */}
                <div className="mt-3 flex items-center gap-4 border-t border-stone-700 pt-3">
                    <div className="flex items-center gap-1 font-pixel text-[10px] text-stone-500">
                        <Calendar className="h-3 w-3" />
                        Formed: {alliance.formed_at}
                    </div>
                    {alliance.expires_at && !isBroken && !isExpired && (
                        <div className="flex items-center gap-1 font-pixel text-[10px] text-stone-500">
                            <AlertTriangle className="h-3 w-3 text-amber-400" />
                            Expires: {alliance.expires_at}
                        </div>
                    )}
                    {alliance.ended_at && (
                        <div className="flex items-center gap-1 font-pixel text-[10px] text-stone-500">
                            <X className="h-3 w-3 text-red-400" />
                            Ended: {alliance.ended_at}
                        </div>
                    )}
                </div>

                {/* Actions */}
                {showActions && alliance.can_break && is_head && (
                    <div className="mt-3 border-t border-stone-700 pt-3">
                        {confirmBreak === alliance.id ? (
                            <div className="flex items-center gap-2">
                                <span className="font-pixel text-xs text-red-400">
                                    Break alliance?
                                </span>
                                <button
                                    onClick={() => handleBreakAlliance(alliance.id)}
                                    disabled={breaking === alliance.id}
                                    className="rounded bg-red-600 px-3 py-1 font-pixel text-xs text-white transition hover:bg-red-500 disabled:opacity-50"
                                >
                                    {breaking === alliance.id ? "Breaking..." : "Confirm"}
                                </button>
                                <button
                                    onClick={() => setConfirmBreak(null)}
                                    className="rounded border border-stone-600 px-3 py-1 font-pixel text-xs text-stone-400 transition hover:bg-stone-700"
                                >
                                    Cancel
                                </button>
                            </div>
                        ) : (
                            <button
                                onClick={() => setConfirmBreak(alliance.id)}
                                className="flex items-center gap-1 font-pixel text-xs text-red-400 transition hover:text-red-300"
                            >
                                <X className="h-3 w-3" />
                                Break Alliance
                            </button>
                        )}
                    </div>
                )}
            </div>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`House ${dynasty.name} - Alliances`} />
            <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                {/* Header */}
                <div className="mb-6 flex items-center gap-4">
                    <Link
                        href="/dynasty"
                        className="rounded-lg border border-stone-700 p-2 transition hover:bg-stone-800"
                    >
                        <ChevronLeft className="h-5 w-5 text-stone-400" />
                    </Link>
                    <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-900/30">
                        <Handshake className="h-7 w-7 text-blue-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-xl text-amber-400">Dynasty Alliances</h1>
                        <p className="font-pixel text-xs text-stone-500">
                            Diplomatic relations of House {dynasty.name}
                        </p>
                    </div>
                </div>

                <div className="mx-auto w-full max-w-4xl">
                    {/* Stats */}
                    <div className="mb-4 grid grid-cols-3 gap-3">
                        <div className="rounded-lg border border-pink-600/50 bg-pink-900/20 p-3 text-center">
                            <div className="flex items-center justify-center gap-2">
                                <Heart className="h-4 w-4 text-pink-400" />
                                <span className="font-pixel text-lg text-pink-400">
                                    {active_alliances.filter((a) => a.type === "marriage").length}
                                </span>
                            </div>
                            <div className="font-pixel text-[10px] text-stone-500">
                                Marriage Alliances
                            </div>
                        </div>
                        <div className="rounded-lg border border-blue-600/50 bg-blue-900/20 p-3 text-center">
                            <div className="flex items-center justify-center gap-2">
                                <Handshake className="h-4 w-4 text-blue-400" />
                                <span className="font-pixel text-lg text-blue-400">
                                    {active_alliances.filter((a) => a.type === "pact").length}
                                </span>
                            </div>
                            <div className="font-pixel text-[10px] text-stone-500">
                                Diplomatic Pacts
                            </div>
                        </div>
                        <div className="rounded-lg border border-red-600/50 bg-red-900/20 p-3 text-center">
                            <div className="flex items-center justify-center gap-2">
                                <Swords className="h-4 w-4 text-red-400" />
                                <span className="font-pixel text-lg text-red-400">
                                    {active_alliances.filter((a) => a.type === "blood_oath").length}
                                </span>
                            </div>
                            <div className="font-pixel text-[10px] text-stone-500">Blood Oaths</div>
                        </div>
                    </div>

                    {/* Active Alliances */}
                    <div className="mb-4 rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                            <Shield className="h-4 w-4 text-green-400" />
                            Active Alliances ({active_alliances.length})
                        </h2>

                        {active_alliances.length === 0 ? (
                            <div className="py-8 text-center">
                                <Handshake className="mx-auto mb-3 h-10 w-10 text-stone-600" />
                                <div className="font-pixel text-sm text-stone-500">
                                    No active alliances
                                </div>
                                <p className="mt-1 font-pixel text-xs text-stone-600">
                                    Form alliances through marriage or diplomatic pacts
                                </p>
                                <Link
                                    href="/dynasty/proposals"
                                    className="mt-4 inline-flex items-center gap-2 rounded-lg border border-pink-600/50 bg-pink-900/20 px-4 py-2 font-pixel text-xs text-pink-400 transition hover:bg-pink-900/30"
                                >
                                    <Heart className="h-4 w-4" />
                                    Propose Marriage
                                </Link>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {active_alliances.map((alliance) => (
                                    <AllianceCard
                                        key={alliance.id}
                                        alliance={alliance}
                                        showActions={true}
                                    />
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Past Alliances */}
                    {past_alliances.length > 0 && (
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                <History className="h-4 w-4 text-stone-400" />
                                Past Alliances ({past_alliances.length})
                            </h2>
                            <div className="space-y-3">
                                {past_alliances.map((alliance) => (
                                    <AllianceCard
                                        key={alliance.id}
                                        alliance={alliance}
                                        showActions={false}
                                    />
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Alliance Types Info */}
                    <div className="mt-4 rounded-xl border border-stone-700 bg-stone-800/30 p-4">
                        <h3 className="mb-3 font-pixel text-xs text-stone-400">Alliance Types</h3>
                        <div className="grid gap-3 md:grid-cols-3">
                            <div className="rounded-lg border border-pink-600/30 bg-pink-900/10 p-3">
                                <div className="flex items-center gap-2 mb-1">
                                    <Heart className="h-4 w-4 text-pink-400" />
                                    <span className="font-pixel text-xs text-pink-400">
                                        Marriage Alliance
                                    </span>
                                </div>
                                <p className="font-pixel text-[10px] text-stone-500">
                                    Formed through marriage between dynasty members. Strongest and
                                    most permanent.
                                </p>
                            </div>
                            <div className="rounded-lg border border-blue-600/30 bg-blue-900/10 p-3">
                                <div className="flex items-center gap-2 mb-1">
                                    <Handshake className="h-4 w-4 text-blue-400" />
                                    <span className="font-pixel text-xs text-blue-400">
                                        Diplomatic Pact
                                    </span>
                                </div>
                                <p className="font-pixel text-[10px] text-stone-500">
                                    Formal agreement between houses. May have an expiration date.
                                </p>
                            </div>
                            <div className="rounded-lg border border-red-600/30 bg-red-900/10 p-3">
                                <div className="flex items-center gap-2 mb-1">
                                    <Swords className="h-4 w-4 text-red-400" />
                                    <span className="font-pixel text-xs text-red-400">
                                        Blood Oath
                                    </span>
                                </div>
                                <p className="font-pixel text-[10px] text-stone-500">
                                    Sacred oath of mutual defense. Breaking incurs severe prestige
                                    penalty.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

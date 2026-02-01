import { Head, Link, router } from "@inertiajs/react";
import {
    Crown,
    Heart,
    History,
    ScrollText,
    Shield,
    Star,
    Users,
    Calendar,
    User,
    Sparkles,
    Check,
    X,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface DynastyMember {
    id: number;
    name: string;
    first_name: string;
    gender: string;
    age: number | null;
    generation: number;
    is_heir: boolean;
    is_head: boolean;
    is_married: boolean;
    is_player: boolean;
    status: string;
    user: { id: number; name: string } | null;
}

interface DynastyEvent {
    id: number;
    type: string;
    title: string;
    description: string;
    prestige_change: number;
    occurred_at: string;
    member: { id: number; name: string } | null;
}

interface Dynasty {
    id: number;
    name: string;
    motto: string | null;
    coat_of_arms: string | null;
    prestige: number;
    prestige_rank: string;
    wealth_score: number;
    members_count: number;
    living_members: number;
    generations: number;
    founded_at: string | null;
    head: { id: number; name: string } | null;
    heir: { id: number; name: string; relation: string; age: number | null } | null;
}

interface FoundingRequirement {
    label: string;
    met: boolean;
}

interface Props {
    dynasty: Dynasty | null;
    members: DynastyMember[];
    recent_events: DynastyEvent[];
    is_head: boolean;
    can_found: boolean;
    founding_cost?: number;
    founding_requirements?: FoundingRequirement[];
}

export default function DynastyIndex({
    dynasty,
    members,
    recent_events,
    is_head,
    can_found,
    founding_cost = 100,
    founding_requirements = [],
}: Props) {
    const [founding, setFounding] = useState(false);
    const [showFoundForm, setShowFoundForm] = useState(false);
    const [dynastyName, setDynastyName] = useState("");
    const [dynastyMotto, setDynastyMotto] = useState("");
    const [editing, setEditing] = useState(false);
    const [newMotto, setNewMotto] = useState(dynasty?.motto || "");

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Dynasty", href: "/dynasty" },
    ];

    const handleFoundDynasty = () => {
        if (!dynastyName.trim() || founding) return;
        setFounding(true);
        router.post(
            "/dynasty",
            {
                name: dynastyName,
                motto: dynastyMotto || null,
            },
            {
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setFounding(false),
            },
        );
    };

    const handleUpdateMotto = () => {
        router.put(
            "/dynasty",
            {
                motto: newMotto || null,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setEditing(false),
            },
        );
    };

    const getEventIcon = (type: string) => {
        switch (type) {
            case "birth":
                return "🎂";
            case "death":
                return "💀";
            case "marriage":
                return "💍";
            case "divorce":
                return "💔";
            case "succession":
                return "👑";
            case "achievement":
                return "🏆";
            case "scandal":
                return "😱";
            case "alliance":
                return "🤝";
            case "inheritance":
                return "📜";
            default:
                return "📌";
        }
    };

    const getPrestigeColor = (rank: string) => {
        switch (rank) {
            case "Legendary":
                return "text-amber-400";
            case "Illustrious":
                return "text-purple-400";
            case "Notable":
                return "text-blue-400";
            case "Established":
                return "text-green-400";
            case "Rising":
                return "text-stone-300";
            default:
                return "text-stone-500";
        }
    };

    // Show founding form if no dynasty
    if (!dynasty) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Found a Dynasty" />
                <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                    <div className="w-full">
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-6">
                            <div className="mb-6 flex items-center gap-3">
                                <div className="rounded-lg bg-amber-900/30 p-3">
                                    <Crown className="h-8 w-8 text-amber-400" />
                                </div>
                                <div>
                                    <h1 className="font-pixel text-2xl text-amber-400">
                                        Found a Dynasty
                                    </h1>
                                    <p className="font-pixel text-xs text-stone-500">
                                        Establish your noble house
                                    </p>
                                </div>
                            </div>

                            <div className="mb-6 space-y-3 rounded-lg border border-stone-700 bg-stone-900/50 p-4">
                                <p className="font-pixel text-sm text-stone-300">
                                    You have not yet founded a dynasty. A dynasty allows you to:
                                </p>
                                <ul className="space-y-2 font-pixel text-xs text-stone-400">
                                    <li className="flex items-center gap-2">
                                        <Sparkles className="h-4 w-4 text-amber-400" />
                                        Pass on titles and wealth to heirs
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <Heart className="h-4 w-4 text-pink-400" />
                                        Form alliances through marriage
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <Star className="h-4 w-4 text-purple-400" />
                                        Build lasting prestige and legacy
                                    </li>
                                </ul>
                            </div>

                            <div className="mb-6">
                                <h3 className="mb-3 font-pixel text-sm text-stone-300">
                                    Requirements
                                </h3>
                                <div className="space-y-2">
                                    {founding_requirements.map((req, i) => (
                                        <div key={i} className="flex items-center gap-2">
                                            {req.met ? (
                                                <Check className="h-4 w-4 text-green-400" />
                                            ) : (
                                                <X className="h-4 w-4 text-red-400" />
                                            )}
                                            <span
                                                className={`font-pixel text-xs ${req.met ? "text-stone-300" : "text-stone-500"}`}
                                            >
                                                {req.label}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {can_found && !showFoundForm && (
                                <button
                                    onClick={() => setShowFoundForm(true)}
                                    className="w-full rounded-lg bg-amber-600 py-3 font-pixel text-sm text-white transition hover:bg-amber-500"
                                >
                                    Found Dynasty ({founding_cost}g)
                                </button>
                            )}

                            {can_found && showFoundForm && (
                                <div className="space-y-4">
                                    <div>
                                        <label className="mb-1 block font-pixel text-xs text-stone-400">
                                            Dynasty Name
                                        </label>
                                        <input
                                            type="text"
                                            value={dynastyName}
                                            onChange={(e) => setDynastyName(e.target.value)}
                                            placeholder="House of..."
                                            maxLength={50}
                                            className="w-full rounded-lg border border-stone-600 bg-stone-900 px-3 py-2 font-pixel text-sm text-stone-200 placeholder:text-stone-600"
                                        />
                                    </div>
                                    <div>
                                        <label className="mb-1 block font-pixel text-xs text-stone-400">
                                            Motto (optional)
                                        </label>
                                        <input
                                            type="text"
                                            value={dynastyMotto}
                                            onChange={(e) => setDynastyMotto(e.target.value)}
                                            placeholder="Our words..."
                                            maxLength={100}
                                            className="w-full rounded-lg border border-stone-600 bg-stone-900 px-3 py-2 font-pixel text-sm text-stone-200 placeholder:text-stone-600"
                                        />
                                    </div>
                                    <div className="flex gap-2">
                                        <button
                                            onClick={handleFoundDynasty}
                                            disabled={founding || !dynastyName.trim()}
                                            className="flex-1 rounded-lg bg-amber-600 py-2 font-pixel text-sm text-white transition hover:bg-amber-500 disabled:opacity-50"
                                        >
                                            {founding
                                                ? "Founding..."
                                                : `Found Dynasty (${founding_cost}g)`}
                                        </button>
                                        <button
                                            onClick={() => setShowFoundForm(false)}
                                            className="rounded-lg border border-stone-600 px-4 py-2 font-pixel text-xs text-stone-400 transition hover:bg-stone-700"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            )}

                            {!can_found && (
                                <div className="rounded-lg border border-red-600/50 bg-red-900/20 p-3 text-center">
                                    <p className="font-pixel text-xs text-red-400">
                                        You do not meet the requirements to found a dynasty.
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </AppLayout>
        );
    }

    // Show dynasty overview
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`House ${dynasty.name}`} />
            <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                {/* Header */}
                <div className="mb-6 flex items-center gap-4">
                    <div className="flex h-16 w-16 items-center justify-center rounded-lg bg-amber-900/30">
                        <Shield className="h-10 w-10 text-amber-400" />
                    </div>
                    <div className="flex-1">
                        <h1 className="font-pixel text-2xl text-amber-400">House {dynasty.name}</h1>
                        {editing ? (
                            <div className="mt-1 flex items-center gap-2">
                                <input
                                    type="text"
                                    value={newMotto}
                                    onChange={(e) => setNewMotto(e.target.value)}
                                    placeholder="Enter motto..."
                                    maxLength={100}
                                    className="flex-1 rounded border border-stone-600 bg-stone-900 px-2 py-1 font-pixel text-xs text-stone-300"
                                />
                                <button
                                    onClick={handleUpdateMotto}
                                    className="rounded bg-green-600 px-2 py-1 font-pixel text-xs text-white"
                                >
                                    Save
                                </button>
                                <button
                                    onClick={() => {
                                        setEditing(false);
                                        setNewMotto(dynasty.motto || "");
                                    }}
                                    className="rounded border border-stone-600 px-2 py-1 font-pixel text-xs text-stone-400"
                                >
                                    Cancel
                                </button>
                            </div>
                        ) : (
                            <div className="flex items-center gap-2">
                                <p className="font-pixel text-sm italic text-stone-400">
                                    "{dynasty.motto || "No motto set"}"
                                </p>
                                {is_head && (
                                    <button
                                        onClick={() => setEditing(true)}
                                        className="font-pixel text-[10px] text-stone-500 hover:text-stone-300"
                                    >
                                        [Edit]
                                    </button>
                                )}
                            </div>
                        )}
                    </div>
                </div>

                <div className="mx-auto w-full max-w-6xl">
                    <div className="grid gap-4 lg:grid-cols-3">
                        {/* Dynasty Stats */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                <Star className="h-4 w-4 text-amber-400" />
                                Dynasty Stats
                            </h2>
                            <div className="space-y-3">
                                <div className="flex justify-between">
                                    <span className="font-pixel text-[10px] text-stone-500">
                                        Prestige
                                    </span>
                                    <span
                                        className={`font-pixel text-xs ${getPrestigeColor(dynasty.prestige_rank)}`}
                                    >
                                        {dynasty.prestige.toLocaleString()} ({dynasty.prestige_rank}
                                        )
                                    </span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="font-pixel text-[10px] text-stone-500">
                                        Members
                                    </span>
                                    <span className="font-pixel text-xs text-stone-300">
                                        {dynasty.living_members} living
                                    </span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="font-pixel text-[10px] text-stone-500">
                                        Generations
                                    </span>
                                    <span className="font-pixel text-xs text-stone-300">
                                        {dynasty.generations}
                                    </span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="font-pixel text-[10px] text-stone-500">
                                        Founded
                                    </span>
                                    <span className="font-pixel text-xs text-stone-300">
                                        Year {dynasty.founded_at || "?"}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Leadership */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                <Crown className="h-4 w-4 text-purple-400" />
                                Leadership
                            </h2>
                            <div className="space-y-4">
                                <div className="rounded-lg border border-amber-600/50 bg-amber-900/20 p-3">
                                    <div className="font-pixel text-[10px] text-stone-500">
                                        Head of House
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Crown className="h-4 w-4 text-amber-400" />
                                        <span className="font-pixel text-sm text-amber-400">
                                            {dynasty.head?.name || "None"}
                                        </span>
                                    </div>
                                </div>
                                {dynasty.heir ? (
                                    <div className="rounded-lg border border-purple-600/50 bg-purple-900/20 p-3">
                                        <div className="font-pixel text-[10px] text-stone-500">
                                            Heir Apparent
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Star className="h-4 w-4 text-purple-400" />
                                            <span className="font-pixel text-sm text-purple-400">
                                                {dynasty.heir.name}
                                            </span>
                                        </div>
                                        <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                            {dynasty.heir.relation}, age {dynasty.heir.age || "?"}
                                        </div>
                                    </div>
                                ) : (
                                    <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-3 text-center">
                                        <span className="font-pixel text-xs text-stone-500">
                                            No heir designated
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Quick Links */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                <ScrollText className="h-4 w-4 text-blue-400" />
                                Quick Links
                            </h2>
                            <div className="grid grid-cols-2 gap-2">
                                <Link
                                    href="/dynasty/tree"
                                    className="flex items-center gap-2 rounded-lg border border-green-600/50 bg-green-900/20 p-2 font-pixel text-xs text-green-400 transition hover:bg-green-900/30"
                                >
                                    <Users className="h-4 w-4" />
                                    Family Tree
                                </Link>
                                <Link
                                    href="/dynasty/history"
                                    className="flex items-center gap-2 rounded-lg border border-amber-600/50 bg-amber-900/20 p-2 font-pixel text-xs text-amber-400 transition hover:bg-amber-900/30"
                                >
                                    <History className="h-4 w-4" />
                                    History
                                </Link>
                                <Link
                                    href="/dynasty/proposals"
                                    className="flex items-center gap-2 rounded-lg border border-pink-600/50 bg-pink-900/20 p-2 font-pixel text-xs text-pink-400 transition hover:bg-pink-900/30"
                                >
                                    <Heart className="h-4 w-4" />
                                    Marriages
                                </Link>
                                <Link
                                    href="/dynasty/alliances"
                                    className="flex items-center gap-2 rounded-lg border border-blue-600/50 bg-blue-900/20 p-2 font-pixel text-xs text-blue-400 transition hover:bg-blue-900/30"
                                >
                                    <Shield className="h-4 w-4" />
                                    Alliances
                                </Link>
                            </div>
                            <Link
                                href="/dynasty/succession"
                                className="mt-2 flex items-center justify-center gap-2 rounded-lg border border-purple-600/50 bg-purple-900/20 p-2 font-pixel text-xs text-purple-400 transition hover:bg-purple-900/30"
                            >
                                <Crown className="h-4 w-4" />
                                Succession Settings
                            </Link>
                        </div>
                    </div>

                    {/* Living Members */}
                    <div className="mt-4 rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                            <Users className="h-4 w-4 text-green-400" />
                            Living Members ({members.length})
                        </h2>
                        {members.length === 0 ? (
                            <div className="py-8 text-center">
                                <Users className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                <div className="font-pixel text-xs text-stone-500">
                                    No living members
                                </div>
                            </div>
                        ) : (
                            <div className="space-y-2">
                                {members.map((member) => (
                                    <div
                                        key={member.id}
                                        className={`flex items-center justify-between rounded-lg border p-3 ${
                                            member.is_head
                                                ? "border-amber-600/50 bg-amber-900/20"
                                                : member.is_heir
                                                  ? "border-purple-600/50 bg-purple-900/20"
                                                  : "border-stone-700 bg-stone-800/30"
                                        }`}
                                    >
                                        <div className="flex items-center gap-3">
                                            <div
                                                className={`flex h-8 w-8 items-center justify-center rounded-full ${
                                                    member.gender === "male"
                                                        ? "bg-blue-900/50"
                                                        : "bg-pink-900/50"
                                                }`}
                                            >
                                                <User
                                                    className={`h-4 w-4 ${
                                                        member.gender === "male"
                                                            ? "text-blue-400"
                                                            : "text-pink-400"
                                                    }`}
                                                />
                                            </div>
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <span className="font-pixel text-sm text-stone-200">
                                                        {member.name}
                                                    </span>
                                                    {member.is_head && (
                                                        <Crown className="h-3 w-3 text-amber-400" />
                                                    )}
                                                    {member.is_heir && !member.is_head && (
                                                        <Star className="h-3 w-3 text-purple-400" />
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-2 font-pixel text-[10px] text-stone-500">
                                                    <span>Gen {member.generation}</span>
                                                    {member.age !== null && (
                                                        <span>Age {member.age}</span>
                                                    )}
                                                    {member.is_married && (
                                                        <span className="flex items-center gap-1">
                                                            <Heart className="h-3 w-3 text-pink-400" />
                                                            Married
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            {member.is_head && (
                                                <span className="rounded bg-amber-900/50 px-2 py-0.5 font-pixel text-[10px] text-amber-400">
                                                    Head
                                                </span>
                                            )}
                                            {member.is_heir && !member.is_head && (
                                                <span className="rounded bg-purple-900/50 px-2 py-0.5 font-pixel text-[10px] text-purple-400">
                                                    Heir
                                                </span>
                                            )}
                                            {member.is_player &&
                                                !member.is_head &&
                                                !member.is_heir && (
                                                    <span className="rounded bg-blue-900/50 px-2 py-0.5 font-pixel text-[10px] text-blue-400">
                                                        Player
                                                    </span>
                                                )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Recent Events */}
                    <div className="mt-4 rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                            <History className="h-4 w-4 text-amber-400" />
                            Recent Events
                        </h2>
                        {recent_events.length === 0 ? (
                            <div className="py-8 text-center">
                                <Calendar className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                <div className="font-pixel text-xs text-stone-500">
                                    No recent events
                                </div>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {recent_events.map((event) => (
                                    <div
                                        key={event.id}
                                        className="flex items-start gap-3 rounded-lg border border-stone-700 bg-stone-800/30 p-3"
                                    >
                                        <div className="text-lg">{getEventIcon(event.type)}</div>
                                        <div className="flex-1">
                                            <div className="flex items-center justify-between">
                                                <span className="font-pixel text-xs text-stone-300">
                                                    {event.title}
                                                </span>
                                                <span className="font-pixel text-[10px] text-stone-500">
                                                    {event.occurred_at}
                                                </span>
                                            </div>
                                            {event.description && (
                                                <p className="mt-1 font-pixel text-[10px] text-stone-500">
                                                    {event.description}
                                                </p>
                                            )}
                                            {event.prestige_change !== 0 && (
                                                <span
                                                    className={`font-pixel text-[10px] ${
                                                        event.prestige_change > 0
                                                            ? "text-green-400"
                                                            : "text-red-400"
                                                    }`}
                                                >
                                                    {event.prestige_change > 0 ? "+" : ""}
                                                    {event.prestige_change} prestige
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

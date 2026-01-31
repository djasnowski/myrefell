import { Head, router, usePage } from "@inertiajs/react";
import { Church, Coins, Eye, EyeOff, Heart, Plus, Sparkles, Star, Users, Zap } from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Belief {
    id: number;
    name: string;
    description: string;
    icon: string;
    type: "virtue" | "vice" | "neutral";
    effects: Record<string, number> | null;
}

interface Religion {
    id: number;
    name: string;
    description: string | null;
    icon: string;
    color: string;
    type: "cult" | "religion";
    is_public: boolean;
    is_cult: boolean;
    is_religion: boolean;
    member_count: number;
    member_limit: number | null;
    belief_limit: number;
    founder: { id: number; username: string } | null;
    beliefs: Belief[];
    combined_effects: Record<string, number>;
}

interface Membership {
    id: number;
    religion_id: number;
    religion_name: string;
    religion_icon: string;
    religion_color: string;
    religion_type: string;
    rank: string;
    rank_display: string;
    devotion: number;
    joined_at: string;
    can_be_promoted: boolean;
    is_prophet: boolean;
    is_priest: boolean;
}

interface Structure {
    id: number;
    name: string;
    structure_type: string;
    type_display: string;
    religion: {
        id: number;
        name: string;
        icon: string;
        color: string;
    };
    devotion_multiplier: number;
}

interface PageProps {
    available_religions: Religion[];
    my_religions: Membership[];
    beliefs: Belief[];
    structures: Structure[];
    energy: { current: number };
    gold: number;
    action_costs: {
        prayer: number;
        ritual: number;
        sacrifice: number;
        pilgrimage: number;
    };
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Religions", href: "/religions" },
];

const beliefTypeColors: Record<string, string> = {
    virtue: "text-green-400 bg-green-900/30 border-green-500/30",
    vice: "text-red-400 bg-red-900/30 border-red-500/30",
    neutral: "text-blue-400 bg-blue-900/30 border-blue-500/30",
};

const rankColors: Record<string, string> = {
    prophet: "text-yellow-400 bg-yellow-900/30",
    priest: "text-purple-400 bg-purple-900/30",
    follower: "text-stone-400 bg-stone-700/30",
};

export default function ReligionsIndex() {
    const { available_religions, my_religions, beliefs, structures, energy, gold, action_costs } =
        usePage<PageProps>().props;

    const [showCreateCult, setShowCreateCult] = useState(false);
    const [cultName, setCultName] = useState("");
    const [cultDescription, setCultDescription] = useState("");
    const [selectedBeliefs, setSelectedBeliefs] = useState<number[]>([]);
    const [isCreating, setIsCreating] = useState(false);
    const [joiningId, setJoiningId] = useState<number | null>(null);
    const [actionLoading, setActionLoading] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const toggleBelief = (beliefId: number) => {
        if (selectedBeliefs.includes(beliefId)) {
            setSelectedBeliefs(selectedBeliefs.filter((id) => id !== beliefId));
        } else if (selectedBeliefs.length < 2) {
            setSelectedBeliefs([...selectedBeliefs, beliefId]);
        }
    };

    const createCult = async () => {
        if (!cultName.trim() || selectedBeliefs.length === 0) return;
        setIsCreating(true);
        setError(null);

        try {
            const response = await fetch("/religions/create-cult", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify({
                    name: cultName,
                    description: cultDescription,
                    belief_ids: selectedBeliefs,
                }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                setShowCreateCult(false);
                setCultName("");
                setCultDescription("");
                setSelectedBeliefs([]);
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError("Failed to create cult");
        } finally {
            setIsCreating(false);
        }
    };

    const joinReligion = async (religionId: number) => {
        setJoiningId(religionId);
        setError(null);

        try {
            const response = await fetch("/religions/join", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify({ religion_id: religionId }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError("Failed to join religion");
        } finally {
            setJoiningId(null);
        }
    };

    const performAction = async (religionId: number, actionType: string, structureId?: number) => {
        setActionLoading(`${religionId}-${actionType}`);
        setError(null);

        try {
            const response = await fetch("/religions/action", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify({
                    religion_id: religionId,
                    action_type: actionType,
                    structure_id: structureId,
                }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError("Failed to perform action");
        } finally {
            setActionLoading(null);
        }
    };

    const canCreateCult = !my_religions.some((m) => m.is_prophet);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Religions" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Religions</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            Found cults, join religions, and earn devotion
                        </p>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2 font-pixel text-sm">
                            <Zap className="h-4 w-4 text-yellow-400" />
                            <span className="text-stone-300">{energy.current}</span>
                        </div>
                        <div className="flex items-center gap-2 font-pixel text-sm">
                            <Coins className="h-4 w-4 text-amber-400" />
                            <span className="text-stone-300">{gold.toLocaleString()}</span>
                        </div>
                    </div>
                </div>

                {/* Messages */}
                {error && (
                    <div className="rounded-lg border border-red-500/50 bg-red-900/30 p-3 font-pixel text-sm text-red-300">
                        {error}
                    </div>
                )}
                {success && (
                    <div className="rounded-lg border border-green-500/50 bg-green-900/30 p-3 font-pixel text-sm text-green-300">
                        {success}
                    </div>
                )}

                {/* My Religions */}
                {my_religions.length > 0 && (
                    <div>
                        <h2 className="mb-4 font-pixel text-lg text-amber-300">My Religions</h2>
                        <div className="grid gap-4 md:grid-cols-2">
                            {my_religions.map((membership) => (
                                <div
                                    key={membership.id}
                                    className="rounded-xl border-2 border-purple-500/30 bg-purple-900/20 p-4"
                                >
                                    <div className="mb-3 flex items-center justify-between">
                                        <div className="flex items-center gap-2">
                                            <Church
                                                className="h-5 w-5"
                                                style={{ color: membership.religion_color }}
                                            />
                                            <h3 className="font-pixel text-base text-white">
                                                {membership.religion_name}
                                            </h3>
                                        </div>
                                        <span
                                            className={`rounded px-2 py-1 font-pixel text-xs ${rankColors[membership.rank]}`}
                                        >
                                            {membership.rank_display}
                                        </span>
                                    </div>

                                    <div className="mb-4 grid grid-cols-2 gap-2 font-pixel text-xs">
                                        <div className="flex items-center gap-1 text-stone-400">
                                            <Heart className="h-3 w-3 text-pink-400" />
                                            <span>Devotion: {membership.devotion}</span>
                                        </div>
                                        <div className="flex items-center gap-1 text-stone-400">
                                            {membership.religion_type === "cult" ? (
                                                <EyeOff className="h-3 w-3 text-stone-500" />
                                            ) : (
                                                <Eye className="h-3 w-3 text-green-400" />
                                            )}
                                            <span className="capitalize">
                                                {membership.religion_type}
                                            </span>
                                        </div>
                                    </div>

                                    {/* Action Buttons */}
                                    <div className="flex flex-wrap gap-2">
                                        <button
                                            onClick={() =>
                                                performAction(membership.religion_id, "prayer")
                                            }
                                            disabled={
                                                actionLoading ===
                                                    `${membership.religion_id}-prayer` ||
                                                energy.current < action_costs.prayer
                                            }
                                            className="flex items-center gap-1 rounded bg-purple-600/50 px-3 py-1 font-pixel text-xs text-purple-200 transition hover:bg-purple-600 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <Sparkles className="h-3 w-3" />
                                            Pray ({action_costs.prayer}E)
                                        </button>
                                        <button
                                            onClick={() =>
                                                performAction(membership.religion_id, "ritual")
                                            }
                                            disabled={
                                                actionLoading ===
                                                    `${membership.religion_id}-ritual` ||
                                                energy.current < action_costs.ritual
                                            }
                                            className="flex items-center gap-1 rounded bg-indigo-600/50 px-3 py-1 font-pixel text-xs text-indigo-200 transition hover:bg-indigo-600 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <Star className="h-3 w-3" />
                                            Ritual ({action_costs.ritual}E)
                                        </button>
                                        <a
                                            href={`/religions/${membership.religion_id}`}
                                            className="flex items-center gap-1 rounded bg-stone-600/50 px-3 py-1 font-pixel text-xs text-stone-200 transition hover:bg-stone-600"
                                        >
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Structures at Location */}
                {structures.length > 0 && (
                    <div>
                        <h2 className="mb-4 font-pixel text-lg text-amber-300">
                            Religious Structures Here
                        </h2>
                        <div className="grid gap-3 md:grid-cols-3">
                            {structures.map((structure) => (
                                <div
                                    key={structure.id}
                                    className="rounded-lg border border-stone-700 bg-stone-800/50 p-3"
                                >
                                    <div className="flex items-center gap-2">
                                        <Church
                                            className="h-4 w-4"
                                            style={{ color: structure.religion.color }}
                                        />
                                        <span className="font-pixel text-sm text-white">
                                            {structure.name}
                                        </span>
                                    </div>
                                    <div className="mt-1 font-pixel text-xs text-stone-400">
                                        {structure.type_display} - {structure.religion.name}
                                    </div>
                                    <div className="mt-1 font-pixel text-xs text-green-400">
                                        +{((structure.devotion_multiplier - 1) * 100).toFixed(0)}%
                                        devotion bonus
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Create Cult Section */}
                {canCreateCult && (
                    <div>
                        <div className="flex items-center justify-between">
                            <h2 className="font-pixel text-lg text-amber-300">Found a Cult</h2>
                            <button
                                onClick={() => setShowCreateCult(!showCreateCult)}
                                className="flex items-center gap-1 rounded bg-purple-600 px-3 py-2 font-pixel text-xs text-white transition hover:bg-purple-500"
                            >
                                <Plus className="h-4 w-4" />
                                {showCreateCult ? "Cancel" : "Create Cult"}
                            </button>
                        </div>

                        {showCreateCult && (
                            <div className="mt-4 rounded-xl border-2 border-purple-500/30 bg-purple-900/20 p-4">
                                <div className="mb-4">
                                    <label className="mb-1 block font-pixel text-xs text-stone-400">
                                        Cult Name
                                    </label>
                                    <input
                                        type="text"
                                        value={cultName}
                                        onChange={(e) => setCultName(e.target.value)}
                                        maxLength={50}
                                        className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-purple-500 focus:outline-none"
                                        placeholder="Enter cult name..."
                                    />
                                </div>

                                <div className="mb-4">
                                    <label className="mb-1 block font-pixel text-xs text-stone-400">
                                        Description
                                    </label>
                                    <textarea
                                        value={cultDescription}
                                        onChange={(e) => setCultDescription(e.target.value)}
                                        maxLength={500}
                                        rows={2}
                                        className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-purple-500 focus:outline-none"
                                        placeholder="Describe your cult's purpose..."
                                    />
                                </div>

                                <div className="mb-4">
                                    <label className="mb-2 block font-pixel text-xs text-stone-400">
                                        Select Beliefs (max 2): {selectedBeliefs.length}/2
                                    </label>
                                    <div className="grid gap-2 md:grid-cols-2">
                                        {beliefs.map((belief) => (
                                            <button
                                                key={belief.id}
                                                onClick={() => toggleBelief(belief.id)}
                                                disabled={
                                                    selectedBeliefs.length >= 2 &&
                                                    !selectedBeliefs.includes(belief.id)
                                                }
                                                className={`rounded-lg border p-3 text-left transition ${
                                                    selectedBeliefs.includes(belief.id)
                                                        ? "border-purple-500 bg-purple-900/50"
                                                        : beliefTypeColors[belief.type]
                                                } ${selectedBeliefs.length >= 2 && !selectedBeliefs.includes(belief.id) ? "cursor-not-allowed opacity-50" : ""}`}
                                            >
                                                <div className="font-pixel text-sm text-white">
                                                    {belief.name}
                                                </div>
                                                <div className="font-pixel text-xs text-stone-400">
                                                    {belief.description}
                                                </div>
                                                {belief.effects && (
                                                    <div className="mt-1 font-pixel text-xs text-amber-400">
                                                        {Object.entries(belief.effects).map(
                                                            ([k, v]) => (
                                                                <span key={k} className="mr-2">
                                                                    {k}: {v > 0 ? "+" : ""}
                                                                    {v}
                                                                </span>
                                                            ),
                                                        )}
                                                    </div>
                                                )}
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                <button
                                    onClick={createCult}
                                    disabled={
                                        !cultName.trim() ||
                                        selectedBeliefs.length === 0 ||
                                        isCreating
                                    }
                                    className="w-full rounded bg-purple-600 py-2 font-pixel text-sm text-white transition hover:bg-purple-500 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {isCreating ? "Creating..." : "Found Cult (Free)"}
                                </button>
                            </div>
                        )}
                    </div>
                )}

                {/* Available Religions to Join */}
                {available_religions.length > 0 && (
                    <div>
                        <h2 className="mb-4 font-pixel text-lg text-amber-300">Public Religions</h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {available_religions.map((religion) => (
                                <div
                                    key={religion.id}
                                    className="rounded-xl border border-stone-700 bg-stone-800/50 p-4"
                                >
                                    <div className="mb-3 flex items-center justify-between">
                                        <div className="flex items-center gap-2">
                                            <Church
                                                className="h-5 w-5"
                                                style={{ color: religion.color }}
                                            />
                                            <h3 className="font-pixel text-base text-white">
                                                {religion.name}
                                            </h3>
                                        </div>
                                        <span
                                            className={`rounded px-2 py-1 font-pixel text-xs capitalize ${
                                                religion.is_cult
                                                    ? "bg-stone-700 text-stone-300"
                                                    : "bg-amber-900/50 text-amber-300"
                                            }`}
                                        >
                                            {religion.type}
                                        </span>
                                    </div>

                                    {religion.description && (
                                        <p className="mb-3 font-pixel text-xs text-stone-400">
                                            {religion.description}
                                        </p>
                                    )}

                                    <div className="mb-3 flex items-center gap-3 font-pixel text-xs text-stone-400">
                                        <div className="flex items-center gap-1">
                                            <Users className="h-3 w-3" />
                                            {religion.member_count}
                                            {religion.member_limit && `/${religion.member_limit}`}
                                        </div>
                                        {religion.founder && (
                                            <div>Founded by: {religion.founder.username}</div>
                                        )}
                                    </div>

                                    {/* Beliefs */}
                                    <div className="mb-3">
                                        <div className="mb-1 font-pixel text-xs text-stone-500">
                                            Beliefs:
                                        </div>
                                        <div className="flex flex-wrap gap-1">
                                            {religion.beliefs.map((belief) => (
                                                <span
                                                    key={belief.id}
                                                    className={`rounded px-2 py-0.5 font-pixel text-xs ${beliefTypeColors[belief.type]}`}
                                                    title={belief.description}
                                                >
                                                    {belief.name}
                                                </span>
                                            ))}
                                        </div>
                                    </div>

                                    <button
                                        onClick={() => joinReligion(religion.id)}
                                        disabled={joiningId === religion.id}
                                        className="w-full rounded bg-purple-600/50 py-2 font-pixel text-xs text-purple-200 transition hover:bg-purple-600 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {joiningId === religion.id ? "Joining..." : "Join"}
                                    </button>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Empty State */}
                {available_religions.length === 0 && my_religions.length === 0 && (
                    <div className="flex flex-1 items-center justify-center">
                        <div className="text-center">
                            <Church className="mx-auto mb-3 h-16 w-16 text-stone-600" />
                            <p className="font-pixel text-base text-stone-500">
                                No religions available
                            </p>
                            <p className="font-pixel text-xs text-stone-600">
                                {canCreateCult
                                    ? "Create a cult to start spreading your beliefs!"
                                    : "Wait for religions to become public"}
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

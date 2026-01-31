import { Head, Link, router } from "@inertiajs/react";
import { ArrowLeft, Coins, Filter, Heart, Search, Send, Sparkles, User, Users } from "lucide-react";
import { useMemo, useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface EligibleMember {
    id: number;
    name: string;
    first_name: string;
    age: number | null;
    gender: string;
    generation: number;
}

interface Candidate {
    id: number;
    name: string;
    first_name: string;
    age: number | null;
    gender: string;
    generation: number;
    dynasty_id: number;
    dynasty_name: string | null;
    dynasty_prestige: number;
}

interface DynastyOption {
    id: number;
    name: string;
    prestige: number;
}

interface Props {
    has_dynasty: boolean;
    dynasty_name?: string;
    eligible_members: EligibleMember[];
    candidates: Candidate[];
    dynasties: DynastyOption[];
    player_gold: number;
    is_head?: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Dynasty", href: "/dynasty" },
    { title: "Marriage Proposals", href: "/dynasty/proposals" },
    { title: "Propose Marriage", href: "/dynasty/proposals/create" },
];

export default function ProposeMarriage({
    has_dynasty,
    dynasty_name,
    eligible_members,
    candidates,
    dynasties,
    player_gold,
    is_head,
}: Props) {
    const [selectedMember, setSelectedMember] = useState<number | null>(null);
    const [selectedCandidate, setSelectedCandidate] = useState<number | null>(null);
    const [dowryAmount, setDowryAmount] = useState(0);
    const [message, setMessage] = useState("");
    const [searchQuery, setSearchQuery] = useState("");
    const [dynastyFilter, setDynastyFilter] = useState<number | "all">("all");
    const [genderFilter, setGenderFilter] = useState<"all" | "male" | "female">("all");
    const [ageMin, setAgeMin] = useState<number | "">("");
    const [ageMax, setAgeMax] = useState<number | "">("");
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Filter candidates based on search and filters
    const filteredCandidates = useMemo(() => {
        return candidates.filter((c) => {
            // Search query
            if (searchQuery) {
                const query = searchQuery.toLowerCase();
                const nameMatch = c.name.toLowerCase().includes(query);
                const dynastyMatch = c.dynasty_name?.toLowerCase().includes(query);
                if (!nameMatch && !dynastyMatch) return false;
            }

            // Dynasty filter
            if (dynastyFilter !== "all" && c.dynasty_id !== dynastyFilter) {
                return false;
            }

            // Gender filter
            if (genderFilter !== "all" && c.gender !== genderFilter) {
                return false;
            }

            // Age filter
            if (ageMin !== "" && (c.age === null || c.age < ageMin)) {
                return false;
            }
            if (ageMax !== "" && (c.age === null || c.age > ageMax)) {
                return false;
            }

            return true;
        });
    }, [candidates, searchQuery, dynastyFilter, genderFilter, ageMin, ageMax]);

    const selectedMemberData = eligible_members.find((m) => m.id === selectedMember);
    const selectedCandidateData = candidates.find((c) => c.id === selectedCandidate);

    const handleSubmit = () => {
        if (!selectedMember || !selectedCandidate) {
            setError("Please select both a dynasty member and a candidate.");
            return;
        }

        if (dowryAmount > player_gold) {
            setError("You do not have enough gold for this dowry.");
            return;
        }

        setProcessing(true);
        setError(null);

        router.post(
            "/dynasty/proposals",
            {
                proposer_member_id: selectedMember,
                proposed_member_id: selectedCandidate,
                offered_dowry: dowryAmount,
                message: message || null,
            },
            {
                onSuccess: () => {
                    router.reload();
                },
                onError: (errors) => {
                    setError(Object.values(errors).flat().join(", ") || "Failed to send proposal");
                },
                onFinish: () => setProcessing(false),
            },
        );
    };

    // No dynasty state
    if (!has_dynasty) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Propose Marriage" />
                <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                    <div className="w-full">
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-6 text-center">
                            <Heart className="mx-auto mb-4 h-12 w-12 text-stone-600" />
                            <h2 className="mb-2 font-pixel text-lg text-stone-300">No Dynasty</h2>
                            <p className="mb-4 font-pixel text-xs text-stone-500">
                                You must found a dynasty before you can propose marriages.
                            </p>
                            <Link
                                href="/dynasty"
                                className="inline-block rounded border-2 border-amber-600/50 bg-amber-900/20 px-4 py-2 font-pixel text-sm text-amber-400 transition hover:bg-amber-900/40"
                            >
                                Go to Dynasty
                            </Link>
                        </div>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Propose Marriage" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-y-auto p-4">
                {/* Back Link */}
                <Link
                    href="/dynasty/proposals"
                    className="flex items-center gap-2 font-pixel text-sm text-stone-400 transition hover:text-stone-300"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to Proposals
                </Link>

                {/* Error Message */}
                {error && (
                    <div className="rounded-lg border border-red-500/50 bg-red-900/30 p-3 font-pixel text-sm text-red-300">
                        {error}
                    </div>
                )}

                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="rounded-lg bg-pink-900/30 p-3">
                        <Heart className="h-6 w-6 text-pink-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-xl text-pink-400">Propose Marriage</h1>
                        <p className="font-pixel text-xs text-stone-500">
                            House {dynasty_name} {is_head && "— Head of House"}
                        </p>
                    </div>
                </div>

                <div className="mx-auto w-full max-w-4xl space-y-6">
                    {/* Step 1: Select Dynasty Member */}
                    <div className="rounded-xl border-2 border-blue-600/50 bg-blue-900/20 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-blue-400">
                            <User className="h-4 w-4" />
                            Step 1: Select Dynasty Member to Marry
                        </h2>

                        {eligible_members.length === 0 ? (
                            <div className="py-6 text-center">
                                <Users className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                <p className="font-pixel text-xs text-stone-500">
                                    No eligible members (must be unmarried and at least 14 years
                                    old)
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-2">
                                {eligible_members.map((member) => (
                                    <label
                                        key={member.id}
                                        className={`flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition ${
                                            selectedMember === member.id
                                                ? "border-blue-500 bg-blue-900/30"
                                                : "border-stone-700 bg-stone-800/50 hover:bg-stone-800"
                                        }`}
                                    >
                                        <input
                                            type="radio"
                                            name="member"
                                            value={member.id}
                                            checked={selectedMember === member.id}
                                            onChange={() => setSelectedMember(member.id)}
                                            className="sr-only"
                                        />
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
                                        <div className="flex-1">
                                            <div className="font-pixel text-sm text-stone-200">
                                                {member.name}
                                            </div>
                                            <div className="font-pixel text-[10px] text-stone-500">
                                                {member.gender === "male" ? "Son" : "Daughter"} |
                                                Age {member.age ?? "?"} | Gen {member.generation}
                                            </div>
                                        </div>
                                        <div
                                            className={`h-4 w-4 rounded-full border-2 ${
                                                selectedMember === member.id
                                                    ? "border-blue-400 bg-blue-400"
                                                    : "border-stone-600"
                                            }`}
                                        />
                                    </label>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Step 2: Search for Partner */}
                    <div className="rounded-xl border-2 border-purple-600/50 bg-purple-900/20 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-purple-400">
                            <Search className="h-4 w-4" />
                            Step 2: Search for Partner
                        </h2>

                        {/* Search and Filters */}
                        <div className="mb-4 space-y-3">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-500" />
                                <input
                                    type="text"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    placeholder="Search by name or dynasty..."
                                    className="w-full rounded-lg border border-stone-600 bg-stone-900 py-2 pl-10 pr-4 font-pixel text-sm text-stone-200 placeholder:text-stone-600"
                                />
                            </div>

                            <div className="flex flex-wrap items-center gap-2">
                                <Filter className="h-4 w-4 text-stone-500" />

                                <select
                                    value={dynastyFilter === "all" ? "all" : dynastyFilter}
                                    onChange={(e) =>
                                        setDynastyFilter(
                                            e.target.value === "all"
                                                ? "all"
                                                : parseInt(e.target.value),
                                        )
                                    }
                                    className="rounded border border-stone-600 bg-stone-900 px-2 py-1 font-pixel text-xs text-stone-300"
                                >
                                    <option value="all">All Dynasties</option>
                                    {dynasties.map((d) => (
                                        <option key={d.id} value={d.id}>
                                            {d.name} ({d.prestige} prestige)
                                        </option>
                                    ))}
                                </select>

                                <select
                                    value={genderFilter}
                                    onChange={(e) =>
                                        setGenderFilter(e.target.value as "all" | "male" | "female")
                                    }
                                    className="rounded border border-stone-600 bg-stone-900 px-2 py-1 font-pixel text-xs text-stone-300"
                                >
                                    <option value="all">Any Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>

                                <div className="flex items-center gap-1">
                                    <span className="font-pixel text-[10px] text-stone-500">
                                        Age:
                                    </span>
                                    <input
                                        type="number"
                                        value={ageMin}
                                        onChange={(e) =>
                                            setAgeMin(
                                                e.target.value === ""
                                                    ? ""
                                                    : parseInt(e.target.value),
                                            )
                                        }
                                        placeholder="Min"
                                        min={14}
                                        className="w-14 rounded border border-stone-600 bg-stone-900 px-2 py-1 font-pixel text-xs text-stone-300"
                                    />
                                    <span className="text-stone-500">-</span>
                                    <input
                                        type="number"
                                        value={ageMax}
                                        onChange={(e) =>
                                            setAgeMax(
                                                e.target.value === ""
                                                    ? ""
                                                    : parseInt(e.target.value),
                                            )
                                        }
                                        placeholder="Max"
                                        min={14}
                                        className="w-14 rounded border border-stone-600 bg-stone-900 px-2 py-1 font-pixel text-xs text-stone-300"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Candidates List */}
                        <div className="max-h-64 space-y-2 overflow-y-auto">
                            {filteredCandidates.length === 0 ? (
                                <div className="py-6 text-center">
                                    <Users className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                    <p className="font-pixel text-xs text-stone-500">
                                        No eligible candidates found
                                    </p>
                                </div>
                            ) : (
                                filteredCandidates.map((candidate) => (
                                    <label
                                        key={candidate.id}
                                        className={`flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition ${
                                            selectedCandidate === candidate.id
                                                ? "border-purple-500 bg-purple-900/30"
                                                : "border-stone-700 bg-stone-800/50 hover:bg-stone-800"
                                        }`}
                                    >
                                        <input
                                            type="radio"
                                            name="candidate"
                                            value={candidate.id}
                                            checked={selectedCandidate === candidate.id}
                                            onChange={() => setSelectedCandidate(candidate.id)}
                                            className="sr-only"
                                        />
                                        <div
                                            className={`flex h-8 w-8 items-center justify-center rounded-full ${
                                                candidate.gender === "male"
                                                    ? "bg-blue-900/50"
                                                    : "bg-pink-900/50"
                                            }`}
                                        >
                                            <User
                                                className={`h-4 w-4 ${
                                                    candidate.gender === "male"
                                                        ? "text-blue-400"
                                                        : "text-pink-400"
                                                }`}
                                            />
                                        </div>
                                        <div className="flex-1">
                                            <div className="font-pixel text-sm text-stone-200">
                                                {candidate.name}
                                            </div>
                                            <div className="font-pixel text-[10px] text-stone-500">
                                                House {candidate.dynasty_name || "Unknown"} | Age{" "}
                                                {candidate.age ?? "?"}
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <div className="font-pixel text-[10px] text-amber-400">
                                                <Sparkles className="mr-1 inline h-3 w-3" />
                                                {candidate.dynasty_prestige} prestige
                                            </div>
                                        </div>
                                        <div
                                            className={`h-4 w-4 rounded-full border-2 ${
                                                selectedCandidate === candidate.id
                                                    ? "border-purple-400 bg-purple-400"
                                                    : "border-stone-600"
                                            }`}
                                        />
                                    </label>
                                ))
                            )}
                        </div>

                        <div className="mt-2 font-pixel text-[10px] text-stone-500">
                            Showing {filteredCandidates.length} of {candidates.length} candidates
                        </div>
                    </div>

                    {/* Step 3: Proposal Details */}
                    <div className="rounded-xl border-2 border-amber-600/50 bg-amber-900/20 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-amber-400">
                            <Coins className="h-4 w-4" />
                            Step 3: Proposal Details
                        </h2>

                        <div className="space-y-4">
                            {/* Dowry */}
                            <div>
                                <label className="mb-2 flex items-center justify-between font-pixel text-xs text-stone-400">
                                    <span>Dowry Amount</span>
                                    <span className="text-stone-500">You have: {player_gold}g</span>
                                </label>
                                <div className="flex items-center gap-3">
                                    <input
                                        type="range"
                                        min={0}
                                        max={player_gold}
                                        step={10}
                                        value={dowryAmount}
                                        onChange={(e) => setDowryAmount(parseInt(e.target.value))}
                                        className="flex-1"
                                    />
                                    <input
                                        type="number"
                                        min={0}
                                        max={player_gold}
                                        value={dowryAmount}
                                        onChange={(e) =>
                                            setDowryAmount(
                                                Math.min(
                                                    player_gold,
                                                    Math.max(0, parseInt(e.target.value) || 0),
                                                ),
                                            )
                                        }
                                        className="w-24 rounded border border-stone-600 bg-stone-900 px-3 py-2 font-pixel text-sm text-stone-200"
                                    />
                                    <span className="font-pixel text-sm text-yellow-400">gold</span>
                                </div>
                            </div>

                            {/* Message */}
                            <div>
                                <label className="mb-2 block font-pixel text-xs text-stone-400">
                                    Message to{" "}
                                    {selectedCandidateData?.dynasty_name
                                        ? `House ${selectedCandidateData.dynasty_name}`
                                        : "Recipient"}
                                </label>
                                <textarea
                                    value={message}
                                    onChange={(e) => setMessage(e.target.value)}
                                    placeholder="We propose a union between our houses..."
                                    maxLength={500}
                                    rows={3}
                                    className="w-full rounded-lg border border-stone-600 bg-stone-900 px-3 py-2 font-pixel text-sm text-stone-200 placeholder:text-stone-600"
                                />
                                <div className="mt-1 text-right font-pixel text-[10px] text-stone-500">
                                    {message.length}/500
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Alliance Preview */}
                    {selectedMemberData && selectedCandidateData && (
                        <div className="rounded-xl border-2 border-green-600/50 bg-green-900/20 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-green-400">
                                <Sparkles className="h-4 w-4" />
                                Alliance Preview
                            </h2>

                            <div className="mb-4 flex items-center justify-center gap-4">
                                <div className="text-center">
                                    <div
                                        className={`mx-auto flex h-12 w-12 items-center justify-center rounded-full ${
                                            selectedMemberData.gender === "male"
                                                ? "bg-blue-900/50"
                                                : "bg-pink-900/50"
                                        }`}
                                    >
                                        <User
                                            className={`h-6 w-6 ${
                                                selectedMemberData.gender === "male"
                                                    ? "text-blue-400"
                                                    : "text-pink-400"
                                            }`}
                                        />
                                    </div>
                                    <div className="mt-2 font-pixel text-xs text-stone-300">
                                        {selectedMemberData.name}
                                    </div>
                                    <div className="font-pixel text-[10px] text-stone-500">
                                        House {dynasty_name}
                                    </div>
                                </div>

                                <Heart className="h-6 w-6 text-pink-400" />

                                <div className="text-center">
                                    <div
                                        className={`mx-auto flex h-12 w-12 items-center justify-center rounded-full ${
                                            selectedCandidateData.gender === "male"
                                                ? "bg-blue-900/50"
                                                : "bg-pink-900/50"
                                        }`}
                                    >
                                        <User
                                            className={`h-6 w-6 ${
                                                selectedCandidateData.gender === "male"
                                                    ? "text-blue-400"
                                                    : "text-pink-400"
                                            }`}
                                        />
                                    </div>
                                    <div className="mt-2 font-pixel text-xs text-stone-300">
                                        {selectedCandidateData.name}
                                    </div>
                                    <div className="font-pixel text-[10px] text-stone-500">
                                        House {selectedCandidateData.dynasty_name}
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-1 rounded-lg bg-stone-900/50 p-3">
                                <div className="flex items-center gap-2 font-pixel text-xs text-stone-300">
                                    <span className="text-green-400">+</span>
                                    Marriage alliance with House{" "}
                                    {selectedCandidateData.dynasty_name}
                                </div>
                                {dowryAmount > 0 && (
                                    <div className="flex items-center gap-2 font-pixel text-xs text-stone-300">
                                        <Coins className="h-3 w-3 text-yellow-400" />
                                        {dowryAmount}g dowry offered
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Action Buttons */}
                    <div className="flex items-center justify-end gap-3">
                        <Link
                            href="/dynasty/proposals"
                            className="rounded border-2 border-stone-600/50 bg-stone-900/20 px-6 py-2 font-pixel text-sm text-stone-400 transition hover:bg-stone-900/40"
                        >
                            Cancel
                        </Link>
                        <button
                            onClick={handleSubmit}
                            disabled={processing || !selectedMember || !selectedCandidate}
                            className="flex items-center gap-2 rounded border-2 border-pink-600/50 bg-pink-900/20 px-6 py-2 font-pixel text-sm text-pink-400 transition hover:bg-pink-900/40 disabled:opacity-50"
                        >
                            <Send className="h-4 w-4" />
                            {processing ? "Sending..." : "Send Proposal"}
                        </button>
                    </div>

                    {/* Info Notice */}
                    <div className="rounded-lg border border-amber-600/30 bg-amber-900/10 p-3">
                        <p className="font-pixel text-[10px] text-amber-400/80">
                            Marriage proposals expire after 14 days if not responded to. The head of
                            the target dynasty will receive your proposal and can accept, reject, or
                            ignore it. Higher dowries may increase the likelihood of acceptance.
                        </p>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

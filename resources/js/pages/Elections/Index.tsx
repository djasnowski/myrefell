import { Head, Link } from "@inertiajs/react";
import { Crown, Vote } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Election {
    id: number;
    election_type: string;
    role: string | null;
    domain_type: string;
    domain_name: string;
    status: string;
    voting_starts_at: string | null;
    voting_ends_at: string | null;
    votes_cast: number;
    quorum_required: number;
    quorum_met: boolean;
    winner: { id: number; username: string } | null;
    is_self_appointment: boolean;
}

interface Props {
    elections: {
        data: Election[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}

export default function ElectionsIndex({ elections }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Elections", href: "/elections" },
    ];

    const getStatusColor = (status: string) => {
        switch (status) {
            case "open":
                return "bg-green-900/50 text-green-400";
            case "completed":
                return "bg-blue-900/50 text-blue-400";
            case "failed":
                return "bg-red-900/50 text-red-400";
            case "closed":
                return "bg-yellow-900/50 text-yellow-400";
            default:
                return "bg-stone-700 text-stone-400";
        }
    };

    const getElectionTitle = (election: Election) => {
        if (election.election_type === "mayor") return "Mayor";
        if (election.election_type === "king") return "King";
        if (election.election_type === "village_role") return election.role;
        return election.election_type;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Elections" />
            <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                {/* Header */}
                <div className="mb-6 flex items-center gap-3">
                    <div className="rounded-lg bg-purple-900/30 p-3">
                        <Vote className="h-8 w-8 text-purple-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-2xl text-purple-400">Elections</h1>
                        <p className="font-pixel text-xs text-stone-500">
                            All elections across the realm
                        </p>
                    </div>
                </div>

                <div className="mx-auto w-full max-w-4xl">
                    {elections.data.length === 0 ? (
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-8 text-center">
                            <Vote className="mx-auto mb-2 h-12 w-12 text-stone-600" />
                            <div className="font-pixel text-sm text-stone-400">
                                No elections found
                            </div>
                            <div className="font-pixel text-xs text-stone-600">
                                The realm is at peace... for now.
                            </div>
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {elections.data.map((election) => (
                                <Link
                                    key={election.id}
                                    href={`/elections/${election.id}`}
                                    className="block rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4 transition hover:border-purple-600/50 hover:bg-stone-800"
                                >
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="rounded-lg bg-purple-900/30 p-2">
                                                <Vote className="h-5 w-5 text-purple-400" />
                                            </div>
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <span className="font-pixel text-sm text-stone-200 capitalize">
                                                        {getElectionTitle(election)}
                                                    </span>
                                                    <span className="font-pixel text-xs text-stone-500">
                                                        - {election.domain_name}
                                                    </span>
                                                    <span
                                                        className={`rounded px-2 py-0.5 font-pixel text-[10px] ${getStatusColor(election.status)}`}
                                                    >
                                                        {election.status}
                                                    </span>
                                                </div>
                                                <div className="mt-1 flex items-center gap-3 font-pixel text-[10px] text-stone-500">
                                                    <span className="capitalize">
                                                        {election.domain_type}
                                                    </span>
                                                    <span>
                                                        {election.votes_cast}/
                                                        {election.quorum_required} votes
                                                    </span>
                                                    {election.voting_ends_at && (
                                                        <span>
                                                            Ends:{" "}
                                                            {new Date(
                                                                election.voting_ends_at,
                                                            ).toLocaleDateString()}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            {election.winner && (
                                                <div className="flex items-center gap-2 rounded-lg border border-amber-600/50 bg-amber-900/20 px-2 py-1">
                                                    <Crown className="h-4 w-4 text-amber-400" />
                                                    <span className="font-pixel text-xs text-amber-400">
                                                        {election.winner.username}
                                                    </span>
                                                </div>
                                            )}
                                            {election.is_self_appointment && (
                                                <span className="rounded bg-stone-700 px-2 py-0.5 font-pixel text-[10px] text-stone-400">
                                                    Appointed
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}

                    {/* Pagination */}
                    {elections.links.length > 3 && (
                        <div className="mt-4 flex justify-center gap-1">
                            {elections.links.map((link, index) => (
                                <Link
                                    key={index}
                                    href={link.url || "#"}
                                    className={`rounded px-3 py-1 font-pixel text-xs ${
                                        link.active
                                            ? "bg-purple-600 text-white"
                                            : link.url
                                              ? "bg-stone-700 text-stone-300 hover:bg-stone-600"
                                              : "bg-stone-800 text-stone-600 cursor-not-allowed"
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

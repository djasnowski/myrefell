import { Head, router, usePage } from "@inertiajs/react";
import { ArrowLeft, Award, Coins, Crown, Hammer, Star, Users, Vote, Settings } from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface GuildBenefit {
    id: number;
    name: string;
    description: string;
    icon: string;
    effects: Record<string, number> | null;
}

interface Guild {
    id: number;
    name: string;
    description: string | null;
    icon: string;
    color: string;
    primary_skill: string;
    skill_display: string;
    level: number;
    level_progress: number;
    total_contribution: number;
    treasury: number;
    membership_fee: number;
    weekly_dues: number;
    is_public: boolean;
    has_monopoly: boolean;
    member_count: number;
    master_count: number;
    founder: { id: number; username: string } | null;
    guildmaster: { id: number; username: string } | null;
    benefits: GuildBenefit[];
    combined_effects: Record<string, number>;
}

interface Membership {
    id: number;
    guild_id: number;
    guild_name: string;
    rank: string;
    rank_display: string;
    contribution: number;
    years_membership: number;
    joined_at: string;
    dues_paid: boolean;
    dues_paid_until: string | null;
    can_be_promoted: boolean;
    promotion_requirements: {
        years_required: number;
        contribution_required: number;
        years_met: boolean;
        contribution_met: boolean;
    };
    has_voting_rights: boolean;
    is_guildmaster: boolean;
}

interface Member {
    id: number;
    user_id: number;
    username: string;
    rank: string;
    rank_display: string;
    contribution: number;
    years_membership: number;
    joined_at: string;
}

interface Candidate {
    id: number;
    user_id: number;
    username: string;
    platform: string | null;
    votes: number;
}

interface Election {
    id: number;
    guild_id: number;
    status: string;
    status_display: string;
    nomination_ends_at: string;
    voting_ends_at: string;
    is_nomination_phase: boolean;
    is_voting_phase: boolean;
    candidates: Candidate[];
    total_votes: number;
}

interface PriceControl {
    id: number;
    item_name: string;
    min_price: number;
    max_price: number | null;
    min_quality: number;
    is_active: boolean;
}

interface PageProps {
    guild: Guild;
    membership: Membership | null;
    is_member: boolean;
    can_join: boolean;
    player_skill_level: number;
    members: Member[];
    active_election: Election | null;
    price_controls: PriceControl[];
    gold: number;
    [key: string]: unknown;
}

const rankColors: Record<string, string> = {
    guildmaster: "text-yellow-400 bg-yellow-900/30",
    master: "text-purple-400 bg-purple-900/30",
    journeyman: "text-blue-400 bg-blue-900/30",
    apprentice: "text-stone-400 bg-stone-700/30",
};

export default function GuildShow() {
    const {
        guild,
        membership,
        is_member,
        can_join,
        player_skill_level,
        members,
        active_election,
        price_controls,
        gold,
    } = usePage<PageProps>().props;

    const [donateAmount, setDonateAmount] = useState("100");
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);
    const [candidatePlatform, setCandidatePlatform] = useState("");
    const [showSettings, setShowSettings] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Guilds", href: "/guilds" },
        { title: guild.name, href: `/guilds/${guild.id}` },
    ];

    const makeRequest = async (url: string, body: object) => {
        setIsLoading(true);
        setError(null);

        try {
            const response = await fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify(body),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError("Request failed");
        } finally {
            setIsLoading(false);
        }
    };

    const handleDonate = () => {
        const amount = parseInt(donateAmount, 10);
        if (amount >= 10) {
            makeRequest("/guilds/donate", { guild_id: guild.id, amount });
        }
    };

    const handlePayDues = () => {
        makeRequest("/guilds/pay-dues", { guild_id: guild.id });
    };

    const handleLeave = () => {
        if (confirm("Are you sure you want to leave this guild?")) {
            makeRequest("/guilds/leave", { guild_id: guild.id });
        }
    };

    const handleJoin = () => {
        makeRequest("/guilds/join", { guild_id: guild.id });
    };

    const handlePromote = (memberId: number) => {
        makeRequest("/guilds/promote", { member_id: memberId });
    };

    const handleStartElection = () => {
        makeRequest("/guilds/start-election", { guild_id: guild.id });
    };

    const handleDeclare = () => {
        if (active_election) {
            makeRequest("/guilds/declare-candidacy", {
                election_id: active_election.id,
                platform: candidatePlatform,
            });
        }
    };

    const handleVote = (candidateId: number) => {
        if (active_election) {
            makeRequest("/guilds/vote", {
                election_id: active_election.id,
                candidate_id: candidateId,
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={guild.name} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Back Button */}
                <a
                    href="/guilds"
                    className="flex items-center gap-1 font-pixel text-sm text-stone-400 transition hover:text-white"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to Guilds
                </a>

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

                {/* Guild Header */}
                <div className="rounded-xl border-2 border-amber-500/30 bg-amber-900/20 p-6">
                    <div className="flex items-start justify-between">
                        <div>
                            <div className="flex items-center gap-3">
                                <Hammer className="h-8 w-8" style={{ color: guild.color }} />
                                <h1 className="font-pixel text-2xl text-white">{guild.name}</h1>
                            </div>
                            {guild.description && (
                                <p className="mt-2 font-pixel text-sm text-stone-400">
                                    {guild.description}
                                </p>
                            )}
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="flex items-center gap-2 font-pixel text-sm">
                                <Coins className="h-4 w-4 text-amber-400" />
                                <span className="text-stone-300">{gold.toLocaleString()}</span>
                            </div>
                        </div>
                    </div>

                    <div className="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
                        <div className="rounded-lg bg-stone-800/50 p-3">
                            <div className="font-pixel text-xs text-stone-500">Skill</div>
                            <div className="font-pixel text-sm text-amber-300">
                                {guild.skill_display}
                            </div>
                        </div>
                        <div className="rounded-lg bg-stone-800/50 p-3">
                            <div className="font-pixel text-xs text-stone-500">Level</div>
                            <div className="font-pixel text-sm text-white">{guild.level}</div>
                            <div className="mt-1 h-1 w-full rounded-full bg-stone-700">
                                <div
                                    className="h-1 rounded-full bg-amber-500"
                                    style={{ width: `${guild.level_progress}%` }}
                                />
                            </div>
                        </div>
                        <div className="rounded-lg bg-stone-800/50 p-3">
                            <div className="font-pixel text-xs text-stone-500">Members</div>
                            <div className="font-pixel text-sm text-white">
                                {guild.member_count} ({guild.master_count} masters)
                            </div>
                        </div>
                        <div className="rounded-lg bg-stone-800/50 p-3">
                            <div className="font-pixel text-xs text-stone-500">Treasury</div>
                            <div className="font-pixel text-sm text-amber-300">
                                {guild.treasury.toLocaleString()} gold
                            </div>
                        </div>
                    </div>

                    {guild.guildmaster && (
                        <div className="mt-4 flex items-center gap-2 font-pixel text-sm text-stone-400">
                            <Crown className="h-4 w-4 text-yellow-400" />
                            Guildmaster:{" "}
                            <span className="text-white">{guild.guildmaster.username}</span>
                        </div>
                    )}

                    {guild.has_monopoly && (
                        <div className="mt-2 inline-block rounded bg-amber-600/30 px-2 py-1 font-pixel text-xs text-amber-300">
                            Monopoly Rights Granted
                        </div>
                    )}
                </div>

                {/* Membership Section */}
                {membership && (
                    <div className="rounded-xl border border-stone-700 bg-stone-800/50 p-4">
                        <h2 className="mb-4 font-pixel text-lg text-amber-300">Your Membership</h2>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <div className="mb-3 flex items-center gap-2">
                                    <span
                                        className={`rounded px-2 py-1 font-pixel text-sm ${rankColors[membership.rank]}`}
                                    >
                                        {membership.rank_display}
                                    </span>
                                    {membership.has_voting_rights && (
                                        <span className="rounded bg-purple-900/30 px-2 py-1 font-pixel text-xs text-purple-300">
                                            Voting Rights
                                        </span>
                                    )}
                                </div>
                                <div className="space-y-2 font-pixel text-sm text-stone-400">
                                    <div className="flex items-center gap-2">
                                        <Star className="h-4 w-4 text-amber-400" />
                                        Contribution: {membership.contribution.toLocaleString()}
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Award className="h-4 w-4 text-blue-400" />
                                        Membership: {membership.years_membership} years
                                    </div>
                                    <div
                                        className={
                                            membership.dues_paid ? "text-green-400" : "text-red-400"
                                        }
                                    >
                                        {membership.dues_paid
                                            ? membership.dues_paid_until
                                                ? `Dues paid until ${new Date(membership.dues_paid_until).toLocaleDateString()}`
                                                : "Dues paid"
                                            : "Dues owed"}
                                    </div>
                                </div>

                                {/* Promotion Requirements */}
                                {membership.promotion_requirements &&
                                    Object.keys(membership.promotion_requirements).length > 0 && (
                                        <div className="mt-3 rounded-lg bg-stone-700/50 p-3">
                                            <div className="font-pixel text-xs text-stone-400">
                                                Promotion Requirements:
                                            </div>
                                            <div className="mt-1 space-y-1 font-pixel text-xs">
                                                <div
                                                    className={
                                                        membership.promotion_requirements.years_met
                                                            ? "text-green-400"
                                                            : "text-stone-500"
                                                    }
                                                >
                                                    {membership.promotion_requirements.years_met
                                                        ? "✓"
                                                        : "○"}{" "}
                                                    {
                                                        membership.promotion_requirements
                                                            .years_required
                                                    }{" "}
                                                    years membership
                                                </div>
                                                <div
                                                    className={
                                                        membership.promotion_requirements
                                                            .contribution_met
                                                            ? "text-green-400"
                                                            : "text-stone-500"
                                                    }
                                                >
                                                    {membership.promotion_requirements
                                                        .contribution_met
                                                        ? "✓"
                                                        : "○"}{" "}
                                                    {membership.promotion_requirements.contribution_required.toLocaleString()}{" "}
                                                    contribution
                                                </div>
                                            </div>
                                        </div>
                                    )}
                            </div>

                            <div className="space-y-3">
                                {/* Donate */}
                                <div>
                                    <label className="mb-1 block font-pixel text-xs text-stone-400">
                                        Donate to Guild
                                    </label>
                                    <div className="flex gap-2">
                                        <input
                                            type="number"
                                            value={donateAmount}
                                            onChange={(e) => setDonateAmount(e.target.value)}
                                            min={10}
                                            className="w-24 rounded border border-stone-600 bg-stone-800 px-2 py-1 font-pixel text-sm text-white"
                                        />
                                        <button
                                            onClick={handleDonate}
                                            disabled={
                                                isLoading ||
                                                parseInt(donateAmount, 10) < 10 ||
                                                gold < parseInt(donateAmount, 10)
                                            }
                                            className="rounded bg-amber-600 px-3 py-1 font-pixel text-xs text-white transition hover:bg-amber-500 disabled:opacity-50"
                                        >
                                            Donate
                                        </button>
                                    </div>
                                </div>

                                {/* Pay Dues */}
                                {!membership.dues_paid && (
                                    <button
                                        onClick={handlePayDues}
                                        disabled={isLoading || gold < guild.weekly_dues}
                                        className="w-full rounded bg-green-600 py-2 font-pixel text-xs text-white transition hover:bg-green-500 disabled:opacity-50"
                                    >
                                        Pay Dues ({guild.weekly_dues} gold)
                                    </button>
                                )}

                                {/* Guildmaster Settings */}
                                {membership.is_guildmaster && (
                                    <button
                                        onClick={() => setShowSettings(!showSettings)}
                                        className="flex w-full items-center justify-center gap-2 rounded bg-stone-700 py-2 font-pixel text-xs text-stone-300 transition hover:bg-stone-600"
                                    >
                                        <Settings className="h-4 w-4" />
                                        Guild Settings
                                    </button>
                                )}

                                {/* Leave Guild */}
                                {!membership.is_guildmaster && (
                                    <button
                                        onClick={handleLeave}
                                        disabled={isLoading}
                                        className="w-full rounded bg-red-600/50 py-2 font-pixel text-xs text-red-200 transition hover:bg-red-600 disabled:opacity-50"
                                    >
                                        Leave Guild
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {/* Join Button for Non-Members */}
                {!is_member && can_join && (
                    <button
                        onClick={handleJoin}
                        disabled={isLoading || gold < guild.membership_fee}
                        className="rounded bg-amber-600 py-3 font-pixel text-sm text-white transition hover:bg-amber-500 disabled:opacity-50"
                    >
                        Join Guild ({guild.membership_fee.toLocaleString()} gold)
                    </button>
                )}

                {!is_member && !can_join && (
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 font-pixel text-sm text-stone-400">
                        {player_skill_level < 1
                            ? `You need at least level 1 in ${guild.skill_display} to join this guild.`
                            : "This guild is not accepting new members."}
                    </div>
                )}

                {/* Election Section */}
                {active_election && membership?.has_voting_rights && (
                    <div className="rounded-xl border border-purple-500/30 bg-purple-900/20 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-purple-300">
                            <Vote className="h-5 w-5" />
                            Guildmaster Election
                        </h2>
                        <div className="mb-4 font-pixel text-sm text-stone-400">
                            Status:{" "}
                            <span className="text-purple-300">
                                {active_election.status_display}
                            </span>
                        </div>

                        {active_election.is_nomination_phase && (
                            <div className="mb-4">
                                <div className="font-pixel text-xs text-stone-400">
                                    Nominations close:{" "}
                                    {new Date(active_election.nomination_ends_at).toLocaleString()}
                                </div>
                                <div className="mt-3">
                                    <label className="mb-1 block font-pixel text-xs text-stone-400">
                                        Your Platform
                                    </label>
                                    <textarea
                                        value={candidatePlatform}
                                        onChange={(e) => setCandidatePlatform(e.target.value)}
                                        maxLength={500}
                                        rows={2}
                                        className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white"
                                        placeholder="Describe your plans if elected..."
                                    />
                                    <button
                                        onClick={handleDeclare}
                                        disabled={isLoading}
                                        className="mt-2 rounded bg-purple-600 px-4 py-2 font-pixel text-xs text-white transition hover:bg-purple-500 disabled:opacity-50"
                                    >
                                        Declare Candidacy
                                    </button>
                                </div>
                            </div>
                        )}

                        {active_election.is_voting_phase && (
                            <div className="mb-4 font-pixel text-xs text-stone-400">
                                Voting closes:{" "}
                                {new Date(active_election.voting_ends_at).toLocaleString()}
                            </div>
                        )}

                        {active_election.candidates.length > 0 && (
                            <div className="space-y-2">
                                <div className="font-pixel text-sm text-stone-400">Candidates:</div>
                                {active_election.candidates.map((candidate) => (
                                    <div
                                        key={candidate.id}
                                        className="flex items-center justify-between rounded-lg bg-stone-800 p-3"
                                    >
                                        <div>
                                            <div className="font-pixel text-sm text-white">
                                                {candidate.username}
                                            </div>
                                            {candidate.platform && (
                                                <div className="font-pixel text-xs text-stone-400">
                                                    {candidate.platform}
                                                </div>
                                            )}
                                            <div className="font-pixel text-xs text-purple-400">
                                                {candidate.votes} votes
                                            </div>
                                        </div>
                                        {active_election.is_voting_phase && (
                                            <button
                                                onClick={() => handleVote(candidate.id)}
                                                disabled={isLoading}
                                                className="rounded bg-purple-600 px-3 py-1 font-pixel text-xs text-white transition hover:bg-purple-500 disabled:opacity-50"
                                            >
                                                Vote
                                            </button>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}

                {/* Start Election Button */}
                {membership?.has_voting_rights && !active_election && (
                    <button
                        onClick={handleStartElection}
                        disabled={isLoading}
                        className="rounded bg-purple-600/50 py-2 font-pixel text-xs text-purple-200 transition hover:bg-purple-600 disabled:opacity-50"
                    >
                        <Vote className="mr-2 inline h-4 w-4" />
                        Call for Election
                    </button>
                )}

                {/* Benefits Section */}
                {guild.benefits.length > 0 && (
                    <div className="rounded-xl border border-stone-700 bg-stone-800/50 p-4">
                        <h2 className="mb-4 font-pixel text-lg text-amber-300">Guild Benefits</h2>
                        <div className="grid gap-3 md:grid-cols-2">
                            {guild.benefits.map((benefit) => (
                                <div key={benefit.id} className="rounded-lg bg-stone-700/50 p-3">
                                    <div className="font-pixel text-sm text-white">
                                        {benefit.name}
                                    </div>
                                    <div className="font-pixel text-xs text-stone-400">
                                        {benefit.description}
                                    </div>
                                    {benefit.effects && (
                                        <div className="mt-1 font-pixel text-xs text-amber-400">
                                            {Object.entries(benefit.effects).map(([k, v]) => (
                                                <span key={k} className="mr-2">
                                                    {k.replace(/_/g, " ")}: {v > 0 ? "+" : ""}
                                                    {v}%
                                                </span>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Members List */}
                <div className="rounded-xl border border-stone-700 bg-stone-800/50 p-4">
                    <h2 className="mb-4 font-pixel text-lg text-amber-300">
                        <Users className="mr-2 inline h-5 w-5" />
                        Members ({members.length})
                    </h2>
                    <div className="space-y-2">
                        {members.map((member) => (
                            <div
                                key={member.id}
                                className="flex items-center justify-between rounded-lg bg-stone-700/50 p-3"
                            >
                                <div className="flex items-center gap-3">
                                    <span
                                        className={`rounded px-2 py-1 font-pixel text-xs ${rankColors[member.rank]}`}
                                    >
                                        {member.rank_display}
                                    </span>
                                    <span className="font-pixel text-sm text-white">
                                        {member.username}
                                    </span>
                                </div>
                                <div className="flex items-center gap-4 font-pixel text-xs text-stone-400">
                                    <span>{member.contribution.toLocaleString()} contribution</span>
                                    <span>{member.years_membership} years</span>
                                    {membership?.is_guildmaster &&
                                        member.rank !== "guildmaster" && (
                                            <button
                                                onClick={() => handlePromote(member.id)}
                                                disabled={isLoading}
                                                className="rounded bg-purple-600/50 px-2 py-1 text-purple-200 transition hover:bg-purple-600 disabled:opacity-50"
                                            >
                                                Promote
                                            </button>
                                        )}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Price Controls */}
                {price_controls.length > 0 && (
                    <div className="rounded-xl border border-stone-700 bg-stone-800/50 p-4">
                        <h2 className="mb-4 font-pixel text-lg text-amber-300">Price Controls</h2>
                        <div className="space-y-2">
                            {price_controls.map((pc) => (
                                <div
                                    key={pc.id}
                                    className="flex items-center justify-between rounded-lg bg-stone-700/50 p-3"
                                >
                                    <span className="font-pixel text-sm text-white">
                                        {pc.item_name}
                                    </span>
                                    <span className="font-pixel text-xs text-stone-400">
                                        Min: {pc.min_price} gold
                                        {pc.max_price && ` | Max: ${pc.max_price} gold`}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

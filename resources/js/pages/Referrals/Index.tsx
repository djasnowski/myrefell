import { Head, usePage } from "@inertiajs/react";
import {
    Check,
    Clock,
    Coins,
    Copy,
    Gift,
    Link2,
    Mail,
    Share2,
    Shield,
    Sparkles,
    Swords,
    Timer,
    Users,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface ReferralStats {
    referral_code: string;
    referral_link: string;
    total_referrals: number;
    pending_referrals: number;
    qualified_referrals: number;
    rewarded_referrals: number;
    total_earned: number;
}

interface ReferralStages {
    email_verified: boolean;
    level_reached: boolean;
    account_age_met: boolean;
    bonus_level_reached: boolean;
    current_level: number;
    account_age_minutes: number;
}

interface Referral {
    id: number;
    username: string;
    level: number;
    status: "pending" | "qualified" | "rewarded";
    reward_amount: number;
    created_at: string;
    qualified_at: string | null;
    rewarded_at: string | null;
    bonus_rewarded_at: string | null;
    referrer_bonus_item: string | null;
    referred_bonus_item: string | null;
    stages: ReferralStages | null;
}

interface Rewards {
    referrer_reward: number;
    referred_bonus: number;
    required_level: number;
    bonus_level: number;
}

interface PageProps {
    stats: ReferralStats;
    referrals: Referral[];
    rewards: Rewards;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Referrals", href: "/referrals" },
];

const statusConfig = {
    pending: {
        label: "Pending",
        color: "text-amber-400",
        bg: "bg-amber-900/20",
        border: "border-amber-600/50",
        icon: Clock,
    },
    qualified: {
        label: "Qualified",
        color: "text-blue-400",
        bg: "bg-blue-900/20",
        border: "border-blue-600/50",
        icon: Shield,
    },
    rewarded: {
        label: "Rewarded",
        color: "text-green-400",
        bg: "bg-green-900/20",
        border: "border-green-600/50",
        icon: Check,
    },
};

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
}

export default function ReferralsIndex() {
    const { stats, referrals, rewards } = usePage<PageProps>().props;
    const [copied, setCopied] = useState(false);

    const copyLink = async () => {
        try {
            await navigator.clipboard.writeText(stats.referral_link);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch (err) {
            console.error("Failed to copy:", err);
        }
    };

    const shareLink = async () => {
        if (navigator.share) {
            try {
                await navigator.share({
                    title: "Join me on Myrefell!",
                    text: `Use my referral link to join Myrefell and get ${rewards.referred_bonus} gold bonus!`,
                    url: stats.referral_link,
                });
            } catch {
                // User cancelled or share failed
            }
        } else {
            copyLink();
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Referrals" />
            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div>
                    <h1 className="font-pixel text-2xl text-amber-400">Invite Friends</h1>
                    <p className="text-sm text-stone-400">
                        Share your referral link and earn gold when friends join
                    </p>
                </div>

                {/* Referral Link Card */}
                <div className="rounded-xl border-2 border-amber-600/50 bg-gradient-to-b from-stone-800 to-stone-900 p-6">
                    <div className="mb-4 flex items-center gap-3">
                        <div className="rounded-lg bg-amber-900/30 p-3">
                            <Link2 className="h-6 w-6 text-amber-400" />
                        </div>
                        <div>
                            <h2 className="font-pixel text-lg text-amber-300">
                                Your Referral Link
                            </h2>
                            <p className="text-xs text-stone-400">
                                Code:{" "}
                                <span className="font-mono text-amber-400">
                                    {stats.referral_code}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div className="mb-4 flex items-center gap-2">
                        <input
                            type="text"
                            readOnly
                            value={stats.referral_link}
                            className="flex-1 rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2.5 font-mono text-sm text-stone-200"
                        />
                        <button
                            onClick={copyLink}
                            className="flex items-center gap-2 rounded-lg border-2 border-stone-600/50 bg-stone-700/50 px-4 py-2.5 text-sm text-stone-200 transition hover:bg-stone-700"
                        >
                            {copied ? (
                                <>
                                    <Check className="h-4 w-4 text-green-400" />
                                    <span className="text-green-400">Copied!</span>
                                </>
                            ) : (
                                <>
                                    <Copy className="h-4 w-4" />
                                    Copy
                                </>
                            )}
                        </button>
                        <button
                            onClick={shareLink}
                            className="flex items-center gap-2 rounded-lg border-2 border-amber-600/50 bg-amber-900/30 px-4 py-2.5 text-sm text-amber-300 transition hover:bg-amber-900/50"
                        >
                            <Share2 className="h-4 w-4" />
                            Share
                        </button>
                    </div>

                    <div className="rounded-lg bg-stone-800/50 p-4">
                        <h3 className="mb-3 font-pixel text-base text-stone-200">How it works:</h3>
                        <ul className="space-y-2 text-sm text-stone-300">
                            <li className="flex items-start gap-2">
                                <span className="font-bold text-amber-400">1.</span>
                                Share your referral link with friends
                            </li>
                            <li className="flex items-start gap-2">
                                <span className="font-bold text-amber-400">2.</span>
                                They sign up and verify their email
                            </li>
                            <li className="flex items-start gap-2">
                                <span className="font-bold text-amber-400">3.</span>
                                When they reach combat level {rewards.required_level}, you earn{" "}
                                <span className="font-bold text-amber-300">
                                    {rewards.referrer_reward} gold
                                </span>
                            </li>
                            <li className="flex items-start gap-2">
                                <Gift className="mt-0.5 h-4 w-4 text-green-400" />
                                <span>
                                    They also get{" "}
                                    <span className="font-bold text-green-300">
                                        {rewards.referred_bonus} gold
                                    </span>{" "}
                                    for using your link!
                                </span>
                            </li>
                            <li className="flex items-start gap-2">
                                <Sparkles className="mt-0.5 h-4 w-4 text-purple-400" />
                                <span>
                                    At combat level {rewards.bonus_level}, they get a{" "}
                                    <span className="font-bold text-blue-300">rare item</span> and
                                    you get an{" "}
                                    <span className="font-bold text-purple-300">epic item</span>!
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                {/* Stats Grid */}
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <Users className="mx-auto mb-2 h-6 w-6 text-blue-400" />
                        <div className="font-pixel text-2xl text-stone-100">
                            {stats.total_referrals}
                        </div>
                        <div className="text-sm text-stone-400">Total Referrals</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <Clock className="mx-auto mb-2 h-6 w-6 text-amber-400" />
                        <div className="font-pixel text-2xl text-stone-100">
                            {stats.pending_referrals}
                        </div>
                        <div className="text-sm text-stone-400">Pending</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <Check className="mx-auto mb-2 h-6 w-6 text-green-400" />
                        <div className="font-pixel text-2xl text-stone-100">
                            {stats.rewarded_referrals}
                        </div>
                        <div className="text-sm text-stone-400">Completed</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <Coins className="mx-auto mb-2 h-6 w-6 text-amber-400" />
                        <div className="font-pixel text-2xl text-amber-300">
                            {stats.total_earned.toLocaleString()}
                        </div>
                        <div className="text-sm text-stone-400">Gold Earned</div>
                    </div>
                </div>

                {/* Referrals List */}
                <div>
                    <h2 className="mb-4 font-pixel text-lg text-stone-300">Your Referrals</h2>
                    {referrals.length > 0 ? (
                        <div className="space-y-3">
                            {referrals.map((referral) => {
                                const status = statusConfig[referral.status];
                                const StatusIcon = status.icon;
                                const stages = referral.stages;
                                return (
                                    <div
                                        key={referral.id}
                                        className={`rounded-lg border ${status.border} ${status.bg} p-4`}
                                    >
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-3">
                                                <div className="rounded-lg bg-stone-800/50 p-2">
                                                    <Users className="h-5 w-5 text-stone-400" />
                                                </div>
                                                <div>
                                                    <div className="font-pixel text-sm text-stone-200">
                                                        {referral.username}
                                                    </div>
                                                    <div className="text-xs text-stone-500">
                                                        Level {referral.level} • Joined{" "}
                                                        {formatDate(referral.created_at)}
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-3">
                                                {referral.status === "rewarded" && (
                                                    <div className="flex items-center gap-1 text-amber-300">
                                                        <Coins className="h-4 w-4" />
                                                        <span className="font-pixel text-sm">
                                                            +{referral.reward_amount}
                                                        </span>
                                                    </div>
                                                )}
                                                <div
                                                    className={`flex items-center gap-1 rounded-full ${status.bg} border ${status.border} px-3 py-1`}
                                                >
                                                    <StatusIcon
                                                        className={`h-3 w-3 ${status.color}`}
                                                    />
                                                    <span
                                                        className={`font-pixel text-xs ${status.color}`}
                                                    >
                                                        {status.label}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Requirement Stages */}
                                        {stages && !referral.bonus_rewarded_at && (
                                            <div className="mt-3 border-t border-stone-700/50 pt-3">
                                                <div className="mb-2 text-xs font-medium text-stone-400">
                                                    {referral.status === "pending"
                                                        ? "Requirements to qualify:"
                                                        : "Bonus progress:"}
                                                </div>
                                                <div className="grid grid-cols-1 gap-2 sm:grid-cols-4">
                                                    <div
                                                        className={`flex items-center gap-2 rounded-md px-2 py-1.5 ${stages.email_verified ? "bg-green-900/20 text-green-400" : "bg-stone-800/50 text-stone-500"}`}
                                                    >
                                                        <Mail className="h-4 w-4" />
                                                        <span className="text-xs">
                                                            Email Verified
                                                        </span>
                                                        {stages.email_verified && (
                                                            <Check className="ml-auto h-3 w-3" />
                                                        )}
                                                    </div>
                                                    <div
                                                        className={`flex items-center gap-2 rounded-md px-2 py-1.5 ${stages.level_reached ? "bg-green-900/20 text-green-400" : "bg-stone-800/50 text-stone-500"}`}
                                                    >
                                                        <Swords className="h-4 w-4" />
                                                        <span className="text-xs">
                                                            Combat Lvl {rewards.required_level}
                                                        </span>
                                                        {stages.level_reached && (
                                                            <Check className="ml-auto h-3 w-3" />
                                                        )}
                                                    </div>
                                                    <div
                                                        className={`flex items-center gap-2 rounded-md px-2 py-1.5 ${stages.account_age_met ? "bg-green-900/20 text-green-400" : "bg-stone-800/50 text-stone-500"}`}
                                                    >
                                                        <Timer className="h-4 w-4" />
                                                        <span className="text-xs">
                                                            1hr Account Age
                                                        </span>
                                                        {stages.account_age_met && (
                                                            <Check className="ml-auto h-3 w-3" />
                                                        )}
                                                    </div>
                                                    <div
                                                        className={`flex items-center gap-2 rounded-md px-2 py-1.5 ${stages.bonus_level_reached ? "bg-purple-900/20 text-purple-400" : "bg-stone-800/50 text-stone-500"}`}
                                                    >
                                                        <Sparkles className="h-4 w-4" />
                                                        <span className="text-xs">
                                                            Combat Lvl {rewards.bonus_level}
                                                        </span>
                                                        {stages.bonus_level_reached && (
                                                            <Check className="ml-auto h-3 w-3" />
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                        {/* Bonus Items Awarded */}
                                        {referral.bonus_rewarded_at && (
                                            <div className="mt-3 border-t border-stone-700/50 pt-3">
                                                <div className="flex flex-wrap items-center gap-3">
                                                    <div className="flex items-center gap-2">
                                                        <Sparkles className="h-4 w-4 text-purple-400" />
                                                        <span className="text-xs text-stone-400">
                                                            Bonus Items:
                                                        </span>
                                                    </div>
                                                    {referral.referrer_bonus_item && (
                                                        <div className="flex items-center gap-1.5 rounded-md bg-purple-900/30 border border-purple-600/50 px-2 py-1">
                                                            <Gift className="h-3 w-3 text-purple-400" />
                                                            <span className="text-xs text-purple-300">
                                                                You: {referral.referrer_bonus_item}
                                                            </span>
                                                        </div>
                                                    )}
                                                    {referral.referred_bonus_item && (
                                                        <div className="flex items-center gap-1.5 rounded-md bg-blue-900/30 border border-blue-600/50 px-2 py-1">
                                                            <Gift className="h-3 w-3 text-blue-400" />
                                                            <span className="text-xs text-blue-300">
                                                                They: {referral.referred_bonus_item}
                                                            </span>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="rounded-xl border-2 border-dashed border-stone-700 p-8 text-center">
                            <Users className="mx-auto mb-3 h-12 w-12 text-stone-600" />
                            <p className="font-pixel text-lg text-stone-500">No referrals yet</p>
                            <p className="text-sm text-stone-500">
                                Share your link to start earning gold!
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

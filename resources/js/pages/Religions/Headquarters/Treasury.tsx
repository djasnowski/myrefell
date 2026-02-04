import { Head, usePage } from "@inertiajs/react";
import { ArrowLeft, Coins, TrendingDown, TrendingUp } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Religion {
    id: number;
    name: string;
    icon: string;
    color: string;
}

interface Transaction {
    id: number;
    type: string;
    amount: number;
    balance_after: number;
    description: string;
    user: string | null;
    created_at: string;
    time_ago: string;
}

interface Treasury {
    balance: number;
    total_collected: number;
    total_distributed: number;
    recent_transactions: Transaction[];
}

interface PageProps {
    religion: Religion;
    treasury: Treasury;
    [key: string]: unknown;
}

const typeLabels: Record<string, string> = {
    donation: "Donation",
    upgrade_cost: "Upgrade Cost",
    feature_cost: "Feature Cost",
    withdrawal: "Withdrawal",
};

export default function TreasuryHistory() {
    const { religion, treasury } = usePage<PageProps>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Religions", href: "/religions" },
        { title: religion.name, href: `/religions/${religion.id}` },
        { title: "Headquarters", href: `/religions/${religion.id}/headquarters` },
        { title: "Treasury", href: `/religions/${religion.id}/headquarters/treasury` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${religion.name} Treasury`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Back Button */}
                <a
                    href={`/religions/${religion.id}/headquarters`}
                    className="flex items-center gap-2 font-pixel text-sm text-stone-400 hover:text-white"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to Headquarters
                </a>

                {/* Header */}
                <div className="flex items-center gap-3">
                    <Coins className="h-8 w-8 text-amber-400" />
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Treasury</h1>
                        <p className="font-pixel text-sm text-stone-400">{religion.name}</p>
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="grid grid-cols-3 gap-4">
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <div className="font-pixel text-xs text-stone-400 mb-1">
                            Current Balance
                        </div>
                        <div className="font-pixel text-2xl text-amber-400">
                            {treasury.balance.toLocaleString()}
                        </div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <div className="font-pixel text-xs text-stone-400 mb-1">
                            Total Collected
                        </div>
                        <div className="font-pixel text-2xl text-green-400">
                            {treasury.total_collected.toLocaleString()}
                        </div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <div className="font-pixel text-xs text-stone-400 mb-1">Total Spent</div>
                        <div className="font-pixel text-2xl text-red-400">
                            {treasury.total_distributed.toLocaleString()}
                        </div>
                    </div>
                </div>

                {/* Transaction History */}
                <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                    <h2 className="mb-4 font-pixel text-sm text-amber-300">Transaction History</h2>

                    {treasury.recent_transactions.length === 0 ? (
                        <p className="text-center font-pixel text-sm text-stone-500 py-8">
                            No transactions yet
                        </p>
                    ) : (
                        <div className="space-y-2">
                            {treasury.recent_transactions.map((tx) => (
                                <div
                                    key={tx.id}
                                    className="flex items-center justify-between rounded bg-stone-900/50 p-3"
                                >
                                    <div className="flex items-center gap-3">
                                        {tx.amount > 0 ? (
                                            <TrendingUp className="h-5 w-5 text-green-400" />
                                        ) : (
                                            <TrendingDown className="h-5 w-5 text-red-400" />
                                        )}
                                        <div>
                                            <div className="font-pixel text-sm text-white">
                                                {tx.description}
                                            </div>
                                            <div className="flex items-center gap-2 font-pixel text-xs text-stone-500">
                                                <span>{typeLabels[tx.type] || tx.type}</span>
                                                <span>-</span>
                                                <span>{tx.time_ago}</span>
                                                {tx.user && (
                                                    <>
                                                        <span>-</span>
                                                        <span>{tx.user}</span>
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <div
                                            className={`font-pixel text-sm ${
                                                tx.amount > 0 ? "text-green-400" : "text-red-400"
                                            }`}
                                        >
                                            {tx.amount > 0 ? "+" : ""}
                                            {tx.amount.toLocaleString()}
                                        </div>
                                        <div className="font-pixel text-xs text-stone-500">
                                            Balance: {tx.balance_after.toLocaleString()}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

import { Head, router, usePage } from "@inertiajs/react";
import {
    ArrowDownToLine,
    ArrowUpFromLine,
    Banknote,
    Castle,
    Church,
    Coins,
    Home,
    Loader2,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface BankInfo {
    location_type: string;
    location_id: number;
    location_name: string;
    balance: number;
    gold_on_hand: number;
    total_wealth: number;
}

interface Transaction {
    id: number;
    type: "deposit" | "withdrawal";
    amount: number;
    balance_after: number;
    description: string;
    created_at: string;
    formatted_date: string;
}

interface BankAccountSummary {
    id: number;
    location_type: string;
    location_id: number;
    location_name: string;
    balance: number;
}

interface PageProps {
    bank_info: BankInfo;
    transactions: Transaction[];
    all_accounts: BankAccountSummary[];
    total_balance: number;
    [key: string]: unknown;
}

const locationPaths: Record<string, string> = {
    village: "villages",
    barony: "baronies",
    town: "towns",
    duchy: "duchies",
    kingdom: "kingdoms",
};

const locationIcons: Record<string, typeof Home> = {
    village: Home,
    barony: Castle,
    town: Church,
};

function formatGold(amount: number): string {
    return amount.toLocaleString();
}

export default function BankIndex() {
    const { bank_info, transactions, all_accounts, total_balance } = usePage<PageProps>().props;
    const [amount, setAmount] = useState("");
    const [loading, setLoading] = useState<"deposit" | "withdraw" | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const LocationIcon = locationIcons[bank_info.location_type] || Home;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        {
            title: bank_info.location_name,
            href: `/${locationPaths[bank_info.location_type] || bank_info.location_type + "s"}/${bank_info.location_id}`,
        },
        { title: "Bank", href: "#" },
    ];

    const handleDeposit = async () => {
        const value = parseInt(amount, 10);
        if (isNaN(value) || value <= 0) {
            setError("Enter a valid amount");
            return;
        }

        setLoading("deposit");
        setError(null);
        setSuccess(null);

        try {
            const response = await fetch("/bank/deposit", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
                body: JSON.stringify({ amount: value }),
            });

            const data = await response.json();

            if (data.success) {
                setSuccess(data.message);
                setAmount("");
                router.reload({
                    only: ["bank_info", "transactions", "all_accounts", "total_balance", "sidebar"],
                });
            } else {
                setError(data.message);
            }
        } catch {
            setError("An error occurred");
        } finally {
            setLoading(null);
        }
    };

    const handleWithdraw = async () => {
        const value = parseInt(amount, 10);
        if (isNaN(value) || value <= 0) {
            setError("Enter a valid amount");
            return;
        }

        setLoading("withdraw");
        setError(null);
        setSuccess(null);

        try {
            const response = await fetch("/bank/withdraw", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
                body: JSON.stringify({ amount: value }),
            });

            const data = await response.json();

            if (data.success) {
                setSuccess(data.message);
                setAmount("");
                router.reload({
                    only: ["bank_info", "transactions", "all_accounts", "total_balance", "sidebar"],
                });
            } else {
                setError(data.message);
            }
        } catch {
            setError("An error occurred");
        } finally {
            setLoading(null);
        }
    };

    const handleQuickAmount = (pct: number) => {
        const baseAmount =
            pct === 1 ? bank_info.gold_on_hand : Math.floor(bank_info.gold_on_hand * pct);
        setAmount(baseAmount.toString());
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Bank - ${bank_info.location_name}`} />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-4 flex items-center gap-3 sm:mb-6">
                    <div className="rounded-lg bg-amber-900/30 p-3">
                        <Banknote className="h-8 w-8 text-amber-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-xl text-amber-400 sm:text-2xl">Bank</h1>
                        <div className="flex items-center gap-1 text-stone-400">
                            <LocationIcon className="h-3 w-3" />
                            <span className="font-pixel text-xs">{bank_info.location_name}</span>
                        </div>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Left Column - Banking Actions */}
                    <div className="space-y-4">
                        {/* Balance Cards */}
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                                <div className="mb-2 flex items-center gap-2 text-stone-400">
                                    <Coins className="h-4 w-4" />
                                    <span className="font-pixel text-xs">On Hand</span>
                                </div>
                                <div className="font-pixel text-2xl text-yellow-400">
                                    {formatGold(bank_info.gold_on_hand)}
                                </div>
                            </div>
                            <div className="rounded-xl border-2 border-amber-600/50 bg-amber-900/20 p-4">
                                <div className="mb-2 flex items-center gap-2 text-amber-300">
                                    <Banknote className="h-4 w-4" />
                                    <span className="font-pixel text-xs">In Vault</span>
                                </div>
                                <div className="font-pixel text-2xl text-amber-400">
                                    {formatGold(bank_info.balance)}
                                </div>
                            </div>
                        </div>

                        {/* Transaction Form */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 font-pixel text-sm text-stone-300">Transaction</h2>

                            {/* Messages */}
                            {error && (
                                <div className="mb-4 rounded-lg border border-red-600/50 bg-red-900/20 px-4 py-2 font-pixel text-xs text-red-400">
                                    {error}
                                </div>
                            )}
                            {success && (
                                <div className="mb-4 rounded-lg border border-green-600/50 bg-green-900/20 px-4 py-2 font-pixel text-xs text-green-400">
                                    {success}
                                </div>
                            )}

                            {/* Amount Input */}
                            <div className="mb-4">
                                <label className="mb-2 block font-pixel text-xs text-stone-400">
                                    Amount
                                </label>
                                <input
                                    type="number"
                                    value={amount}
                                    onChange={(e) => setAmount(e.target.value)}
                                    placeholder="0"
                                    className="w-full rounded-lg border-2 border-stone-600 bg-stone-900 px-4 py-2 font-pixel text-lg text-amber-300 placeholder-stone-600 focus:border-amber-500 focus:outline-none"
                                    min="1"
                                />
                            </div>

                            {/* Quick Amount Buttons */}
                            <div className="mb-4 flex gap-2">
                                <button
                                    onClick={() => handleQuickAmount(0.25)}
                                    className="flex-1 rounded-lg border border-stone-600 bg-stone-700 px-2 py-1 font-pixel text-[10px] text-stone-300 transition hover:bg-stone-600"
                                >
                                    25%
                                </button>
                                <button
                                    onClick={() => handleQuickAmount(0.5)}
                                    className="flex-1 rounded-lg border border-stone-600 bg-stone-700 px-2 py-1 font-pixel text-[10px] text-stone-300 transition hover:bg-stone-600"
                                >
                                    50%
                                </button>
                                <button
                                    onClick={() => handleQuickAmount(0.75)}
                                    className="flex-1 rounded-lg border border-stone-600 bg-stone-700 px-2 py-1 font-pixel text-[10px] text-stone-300 transition hover:bg-stone-600"
                                >
                                    75%
                                </button>
                                <button
                                    onClick={() => handleQuickAmount(1)}
                                    className="flex-1 rounded-lg border border-stone-600 bg-stone-700 px-2 py-1 font-pixel text-[10px] text-stone-300 transition hover:bg-stone-600"
                                >
                                    All
                                </button>
                            </div>

                            {/* Action Buttons */}
                            <div className="grid grid-cols-2 gap-3">
                                <button
                                    onClick={handleDeposit}
                                    disabled={loading !== null}
                                    className="flex items-center justify-center gap-2 rounded-lg border-2 border-green-600 bg-green-900/30 px-4 py-3 font-pixel text-sm text-green-300 transition hover:bg-green-800/50 disabled:opacity-50"
                                >
                                    {loading === "deposit" ? (
                                        <Loader2 className="h-4 w-4 animate-spin" />
                                    ) : (
                                        <ArrowDownToLine className="h-4 w-4" />
                                    )}
                                    Deposit
                                </button>
                                <button
                                    onClick={handleWithdraw}
                                    disabled={loading !== null}
                                    className="flex items-center justify-center gap-2 rounded-lg border-2 border-amber-600 bg-amber-900/30 px-4 py-3 font-pixel text-sm text-amber-300 transition hover:bg-amber-800/50 disabled:opacity-50"
                                >
                                    {loading === "withdraw" ? (
                                        <Loader2 className="h-4 w-4 animate-spin" />
                                    ) : (
                                        <ArrowUpFromLine className="h-4 w-4" />
                                    )}
                                    Withdraw
                                </button>
                            </div>
                        </div>

                        {/* Total Wealth */}
                        <div className="rounded-xl border-2 border-stone-700 bg-gradient-to-br from-stone-800/80 to-stone-900/80 p-4">
                            <div className="mb-1 font-pixel text-xs text-stone-400">
                                Total Wealth
                            </div>
                            <div className="font-pixel text-3xl text-yellow-300">
                                {formatGold(bank_info.total_wealth)}
                            </div>
                            <div className="mt-2 font-pixel text-[10px] text-stone-500">
                                Across all vaults: {formatGold(total_balance)}
                            </div>
                        </div>
                    </div>

                    {/* Right Column - Transactions & Other Accounts */}
                    <div className="space-y-4">
                        {/* Recent Transactions */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 font-pixel text-sm text-stone-300">
                                Recent Transactions
                            </h2>
                            {transactions.length > 0 ? (
                                <div className="space-y-2">
                                    {transactions.map((tx) => (
                                        <div
                                            key={tx.id}
                                            className="flex items-center justify-between rounded-lg bg-stone-900/50 px-3 py-2"
                                        >
                                            <div className="flex items-center gap-2">
                                                {tx.type === "deposit" ? (
                                                    <ArrowDownToLine className="h-3 w-3 text-green-400" />
                                                ) : (
                                                    <ArrowUpFromLine className="h-3 w-3 text-amber-400" />
                                                )}
                                                <div>
                                                    <div className="font-pixel text-xs text-stone-300">
                                                        {tx.type === "deposit"
                                                            ? "Deposit"
                                                            : "Withdrawal"}
                                                    </div>
                                                    <div className="font-pixel text-[10px] text-stone-500">
                                                        {tx.formatted_date}
                                                    </div>
                                                </div>
                                            </div>
                                            <div
                                                className={`font-pixel text-sm ${tx.type === "deposit" ? "text-green-400" : "text-amber-400"}`}
                                            >
                                                {tx.type === "deposit" ? "+" : "-"}
                                                {formatGold(tx.amount)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="py-8 text-center">
                                    <div className="mb-2 text-4xl">
                                        <Banknote className="mx-auto h-8 w-8 text-stone-600" />
                                    </div>
                                    <p className="font-pixel text-xs text-stone-500">
                                        No transactions yet
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Other Accounts */}
                        {all_accounts.length > 1 && (
                            <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                                <h2 className="mb-4 font-pixel text-sm text-stone-300">
                                    Other Vaults
                                </h2>
                                <div className="space-y-2">
                                    {all_accounts
                                        .filter(
                                            (acc) =>
                                                acc.location_id !== bank_info.location_id ||
                                                acc.location_type !== bank_info.location_type,
                                        )
                                        .map((acc) => {
                                            const Icon = locationIcons[acc.location_type] || Home;
                                            return (
                                                <div
                                                    key={acc.id}
                                                    className="flex items-center justify-between rounded-lg bg-stone-900/50 px-3 py-2"
                                                >
                                                    <div className="flex items-center gap-2">
                                                        <Icon className="h-3 w-3 text-stone-400" />
                                                        <div>
                                                            <div className="font-pixel text-xs text-stone-300">
                                                                {acc.location_name}
                                                            </div>
                                                            <div className="font-pixel text-[10px] capitalize text-stone-500">
                                                                {acc.location_type}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="font-pixel text-sm text-amber-400">
                                                        {formatGold(acc.balance)}
                                                    </div>
                                                </div>
                                            );
                                        })}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

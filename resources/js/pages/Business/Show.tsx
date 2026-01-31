import { Head, router, usePage } from "@inertiajs/react";
import {
    ArrowLeft,
    Axe,
    Bed,
    Building,
    Coins,
    Hammer,
    Loader2,
    Minus,
    Package,
    Pickaxe,
    Plus,
    Store,
    Trash2,
    Users,
    Wheat,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Employee {
    id: number;
    name: string;
    is_player: boolean;
    role: string;
    daily_wage: number;
    skill_level: number;
    efficiency: number;
    hired_at: string;
    last_paid_at: string | null;
}

interface InventoryItem {
    item_id: number;
    item_name: string;
    quantity: number;
    value: number;
}

interface Transaction {
    type: string;
    type_display: string;
    amount: number;
    description: string;
    created_at: string;
}

interface ProductionOrder {
    id: number;
    item_id: number;
    item_name: string;
    quantity: number;
    quantity_completed: number;
    status: string;
    completion_percentage: number;
}

interface Business {
    id: number;
    name: string;
    type_name: string;
    type_icon: string;
    category: string;
    location_type: string;
    location_id: number;
    location_name: string;
    status: string;
    treasury: number;
    total_revenue: number;
    total_expenses: number;
    reputation: number;
    employee_count: number;
    max_employees: number;
    weekly_upkeep: number;
    established_at: string;
    owner_id: number;
    owner_name: string;
    employees: Employee[];
    inventory: InventoryItem[];
    production_orders: ProductionOrder[];
    recent_transactions: Transaction[];
}

interface Npc {
    id: number;
    name: string;
}

interface PageProps {
    business: Business;
    available_npcs: Npc[];
    player: {
        gold: number;
    };
    [key: string]: unknown;
}

const iconMap: Record<string, typeof Store> = {
    hammer: Hammer,
    croissant: Wheat,
    axe: Axe,
    bed: Bed,
    store: Store,
    pickaxe: Pickaxe,
    wheat: Wheat,
    building: Building,
};

export default function BusinessShow() {
    const { business, available_npcs, player } = usePage<PageProps>().props;

    const [depositAmount, setDepositAmount] = useState("");
    const [withdrawAmount, setWithdrawAmount] = useState("");
    const [hireNpcId, setHireNpcId] = useState<number | null>(null);
    const [hireWage, setHireWage] = useState("20");
    const [loading, setLoading] = useState<string | null>(null);

    const Icon = iconMap[business.type_icon] || Store;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "My Businesses", href: "/businesses" },
        { title: business.name, href: "#" },
    ];

    const handleDeposit = () => {
        const amount = parseInt(depositAmount);
        if (!amount || amount <= 0) return;

        setLoading("deposit");
        router.post(
            `/businesses/${business.id}/deposit`,
            { amount },
            {
                preserveScroll: true,
                onFinish: () => {
                    setLoading(null);
                    setDepositAmount("");
                },
            },
        );
    };

    const handleWithdraw = () => {
        const amount = parseInt(withdrawAmount);
        if (!amount || amount <= 0) return;

        setLoading("withdraw");
        router.post(
            `/businesses/${business.id}/withdraw`,
            { amount },
            {
                preserveScroll: true,
                onFinish: () => {
                    setLoading(null);
                    setWithdrawAmount("");
                },
            },
        );
    };

    const handleHire = () => {
        if (!hireNpcId) return;

        setLoading("hire");
        router.post(
            `/businesses/${business.id}/hire`,
            {
                npc_id: hireNpcId,
                daily_wage: parseInt(hireWage),
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    setLoading(null);
                    setHireNpcId(null);
                },
            },
        );
    };

    const handleFire = (employeeId: number) => {
        setLoading(`fire-${employeeId}`);
        router.post(
            `/businesses/${business.id}/employees/${employeeId}/fire`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setLoading(null),
            },
        );
    };

    const handleClose = () => {
        if (!confirm("Are you sure you want to close this business? This action cannot be undone."))
            return;

        setLoading("close");
        router.post(
            `/businesses/${business.id}/close`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setLoading(null),
            },
        );
    };

    const statusColors: Record<string, string> = {
        active: "text-green-300 bg-green-800/50",
        suspended: "text-amber-300 bg-amber-800/50",
        closed: "text-red-300 bg-red-800/50",
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={business.name} />
            <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                {/* Header */}
                <div className="mb-6">
                    <button
                        onClick={() => router.get("/businesses")}
                        className="mb-4 flex items-center gap-2 font-pixel text-xs text-stone-400 hover:text-stone-200"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to Businesses
                    </button>
                    <div className="flex items-start justify-between">
                        <div className="flex items-center gap-4">
                            <div className="rounded-xl bg-stone-800/50 p-3">
                                <Icon className="h-8 w-8 text-amber-300" />
                            </div>
                            <div>
                                <h1 className="font-pixel text-2xl text-amber-400">
                                    {business.name}
                                </h1>
                                <p className="font-pixel text-sm text-stone-400">
                                    {business.type_name} in {business.location_name}
                                </p>
                            </div>
                        </div>
                        <div className={`rounded-lg px-3 py-1 ${statusColors[business.status]}`}>
                            <span className="font-pixel text-sm">{business.status}</span>
                        </div>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Treasury Section */}
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/50 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-amber-300">
                            <Coins className="h-5 w-5" />
                            Treasury
                        </h2>

                        <div className="mb-4 grid grid-cols-3 gap-4">
                            <div className="rounded-lg bg-stone-900/50 p-3 text-center">
                                <span className="font-pixel text-[10px] text-stone-400">
                                    Balance
                                </span>
                                <p className="font-pixel text-lg text-amber-300">
                                    {business.treasury}g
                                </p>
                            </div>
                            <div className="rounded-lg bg-stone-900/50 p-3 text-center">
                                <span className="font-pixel text-[10px] text-stone-400">
                                    Total Revenue
                                </span>
                                <p className="font-pixel text-lg text-green-300">
                                    {business.total_revenue}g
                                </p>
                            </div>
                            <div className="rounded-lg bg-stone-900/50 p-3 text-center">
                                <span className="font-pixel text-[10px] text-stone-400">
                                    Total Expenses
                                </span>
                                <p className="font-pixel text-lg text-red-300">
                                    {business.total_expenses}g
                                </p>
                            </div>
                        </div>

                        <div className="mb-4 flex gap-2">
                            <div className="flex-1">
                                <div className="flex gap-2">
                                    <input
                                        type="number"
                                        value={depositAmount}
                                        onChange={(e) => setDepositAmount(e.target.value)}
                                        placeholder="Amount..."
                                        className="flex-1 rounded-lg border-2 border-stone-600/50 bg-stone-900/50 px-3 py-2 font-pixel text-xs text-stone-200 placeholder-stone-500 focus:border-green-500 focus:outline-none"
                                    />
                                    <button
                                        onClick={handleDeposit}
                                        disabled={loading === "deposit" || !depositAmount}
                                        className="flex items-center gap-1 rounded-lg border-2 border-green-600 bg-green-900/30 px-3 py-2 font-pixel text-xs text-green-300 transition hover:bg-green-800/50 disabled:opacity-50"
                                    >
                                        {loading === "deposit" ? (
                                            <Loader2 className="h-4 w-4 animate-spin" />
                                        ) : (
                                            <Plus className="h-4 w-4" />
                                        )}
                                        Deposit
                                    </button>
                                </div>
                                <span className="font-pixel text-[10px] text-stone-500">
                                    Your gold: {player.gold}g
                                </span>
                            </div>
                        </div>

                        <div className="flex gap-2">
                            <div className="flex-1">
                                <div className="flex gap-2">
                                    <input
                                        type="number"
                                        value={withdrawAmount}
                                        onChange={(e) => setWithdrawAmount(e.target.value)}
                                        placeholder="Amount..."
                                        className="flex-1 rounded-lg border-2 border-stone-600/50 bg-stone-900/50 px-3 py-2 font-pixel text-xs text-stone-200 placeholder-stone-500 focus:border-amber-500 focus:outline-none"
                                    />
                                    <button
                                        onClick={handleWithdraw}
                                        disabled={loading === "withdraw" || !withdrawAmount}
                                        className="flex items-center gap-1 rounded-lg border-2 border-amber-600 bg-amber-900/30 px-3 py-2 font-pixel text-xs text-amber-300 transition hover:bg-amber-800/50 disabled:opacity-50"
                                    >
                                        {loading === "withdraw" ? (
                                            <Loader2 className="h-4 w-4 animate-spin" />
                                        ) : (
                                            <Minus className="h-4 w-4" />
                                        )}
                                        Withdraw
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div className="mt-4 rounded-lg bg-stone-900/50 p-2">
                            <span className="font-pixel text-[10px] text-stone-400">
                                Weekly Upkeep: {business.weekly_upkeep}g
                            </span>
                        </div>
                    </div>

                    {/* Employees Section */}
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/50 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-amber-300">
                            <Users className="h-5 w-5" />
                            Employees ({business.employee_count}/{business.max_employees})
                        </h2>

                        {business.employees.length > 0 ? (
                            <div className="mb-4 space-y-2">
                                {business.employees.map((emp) => (
                                    <div
                                        key={emp.id}
                                        className="flex items-center justify-between rounded-lg bg-stone-900/50 p-2"
                                    >
                                        <div>
                                            <span className="font-pixel text-xs text-stone-200">
                                                {emp.name}
                                            </span>
                                            <div className="flex gap-2">
                                                <span className="font-pixel text-[10px] text-stone-400">
                                                    Wage: {emp.daily_wage}g/day
                                                </span>
                                                <span className="font-pixel text-[10px] text-emerald-400">
                                                    Eff: {Math.round(emp.efficiency * 100)}%
                                                </span>
                                            </div>
                                        </div>
                                        <button
                                            onClick={() => handleFire(emp.id)}
                                            disabled={loading === `fire-${emp.id}`}
                                            className="rounded-lg border border-red-600/50 bg-red-900/20 p-1 text-red-400 transition hover:bg-red-800/30"
                                            title="Fire employee"
                                        >
                                            {loading === `fire-${emp.id}` ? (
                                                <Loader2 className="h-4 w-4 animate-spin" />
                                            ) : (
                                                <Trash2 className="h-4 w-4" />
                                            )}
                                        </button>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="mb-4 font-pixel text-xs text-stone-500">
                                No employees hired yet.
                            </p>
                        )}

                        {business.employee_count < business.max_employees &&
                            available_npcs.length > 0 && (
                                <div className="rounded-lg border border-stone-600/50 bg-stone-900/50 p-3">
                                    <span className="font-pixel text-[10px] text-stone-400">
                                        Hire Worker
                                    </span>
                                    <div className="mt-2 flex gap-2">
                                        <select
                                            value={hireNpcId || ""}
                                            onChange={(e) =>
                                                setHireNpcId(parseInt(e.target.value) || null)
                                            }
                                            className="flex-1 rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-3 py-2 font-pixel text-xs text-stone-200"
                                        >
                                            <option value="">Select worker...</option>
                                            {available_npcs.map((npc) => (
                                                <option key={npc.id} value={npc.id}>
                                                    {npc.name}
                                                </option>
                                            ))}
                                        </select>
                                        <input
                                            type="number"
                                            value={hireWage}
                                            onChange={(e) => setHireWage(e.target.value)}
                                            placeholder="Wage"
                                            className="w-20 rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-2 py-2 font-pixel text-xs text-stone-200"
                                        />
                                        <button
                                            onClick={handleHire}
                                            disabled={loading === "hire" || !hireNpcId}
                                            className="rounded-lg border-2 border-green-600 bg-green-900/30 px-3 py-2 font-pixel text-xs text-green-300 transition hover:bg-green-800/50 disabled:opacity-50"
                                        >
                                            {loading === "hire" ? (
                                                <Loader2 className="h-4 w-4 animate-spin" />
                                            ) : (
                                                "Hire"
                                            )}
                                        </button>
                                    </div>
                                </div>
                            )}
                    </div>

                    {/* Inventory Section */}
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/50 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-amber-300">
                            <Package className="h-5 w-5" />
                            Inventory
                        </h2>

                        {business.inventory.length > 0 ? (
                            <div className="space-y-2">
                                {business.inventory.map((item) => (
                                    <div
                                        key={item.item_id}
                                        className="flex items-center justify-between rounded-lg bg-stone-900/50 p-2"
                                    >
                                        <div>
                                            <span className="font-pixel text-xs text-stone-200">
                                                {item.item_name}
                                            </span>
                                            <span className="ml-2 font-pixel text-[10px] text-stone-400">
                                                x{item.quantity}
                                            </span>
                                        </div>
                                        <span className="font-pixel text-xs text-amber-300">
                                            {item.value}g
                                        </span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="font-pixel text-xs text-stone-500">
                                No inventory stored.
                            </p>
                        )}
                    </div>

                    {/* Recent Transactions */}
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/50 p-4">
                        <h2 className="mb-4 font-pixel text-lg text-amber-300">
                            Recent Transactions
                        </h2>

                        {business.recent_transactions.length > 0 ? (
                            <div className="max-h-64 space-y-2 overflow-y-auto">
                                {business.recent_transactions.map((tx, i) => (
                                    <div
                                        key={i}
                                        className="flex items-center justify-between rounded-lg bg-stone-900/50 p-2"
                                    >
                                        <div>
                                            <span className="font-pixel text-xs text-stone-200">
                                                {tx.type_display}
                                            </span>
                                            <p className="font-pixel text-[10px] text-stone-400">
                                                {tx.description}
                                            </p>
                                        </div>
                                        <span
                                            className={`font-pixel text-xs ${tx.amount >= 0 ? "text-green-300" : "text-red-300"}`}
                                        >
                                            {tx.amount >= 0 ? "+" : ""}
                                            {tx.amount}g
                                        </span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="font-pixel text-xs text-stone-500">
                                No transactions yet.
                            </p>
                        )}
                    </div>
                </div>

                {/* Close Business */}
                {business.status === "active" && (
                    <div className="mt-6 rounded-xl border-2 border-red-600/30 bg-red-900/10 p-4">
                        <h3 className="mb-2 font-pixel text-sm text-red-300">Danger Zone</h3>
                        <p className="mb-3 font-pixel text-xs text-stone-400">
                            Closing your business is permanent. All employees will be fired and
                            remaining treasury will be transferred to you.
                        </p>
                        <button
                            onClick={handleClose}
                            disabled={loading === "close"}
                            className="flex items-center gap-2 rounded-lg border-2 border-red-600 bg-red-900/30 px-4 py-2 font-pixel text-xs text-red-300 transition hover:bg-red-800/50 disabled:opacity-50"
                        >
                            {loading === "close" ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                "Close Business Permanently"
                            )}
                        </button>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

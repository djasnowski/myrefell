import { Head, router, usePage } from '@inertiajs/react';
import { ArrowDownToLine, ArrowUpFromLine, Castle, Coins, Crown, Home, Landmark, Loader2, Settings } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface TreasuryInfo {
    id: number;
    location_type: string;
    location_id: number;
    location_name: string;
    balance: number;
    total_collected: number;
    total_distributed: number;
    tax_rate: number;
}

interface Transaction {
    id: number;
    type: string;
    amount: number;
    balance_after: number;
    description: string;
    related_user: { id: number; username: string } | null;
    created_at: string;
    formatted_date: string;
}

interface TaxRecord {
    id: number;
    amount: number;
    tax_type: string;
    receiver_type: string;
    receiver_name: string;
    description: string;
    tax_period: string;
    created_at: string;
    formatted_date: string;
}

interface SalaryRecord {
    id: number;
    amount: number;
    role_name: string;
    source_type: string;
    source_name: string;
    pay_period: string;
    created_at: string;
    formatted_date: string;
}

interface LocationInfo {
    id: number;
    name: string;
    tax_rate: number;
}

interface PageProps {
    location_type: string;
    location_id: number;
    location_name: string;
    treasury: TreasuryInfo;
    transactions: Transaction[];
    can_configure: boolean;
    user_tax_history: TaxRecord[] | null;
    user_salary_history: SalaryRecord[] | null;
    min_tax_rate: number;
    max_tax_rate: number;
    barony: LocationInfo | null;
    kingdom: LocationInfo | null;
    player: {
        id: number;
        username: string;
        gold: number;
    };
    [key: string]: unknown;
}

const locationIcons: Record<string, typeof Home> = {
    village: Home,
    barony: Castle,
    kingdom: Crown,
};

function formatGold(amount: number): string {
    return amount.toLocaleString();
}

export default function TaxesIndex() {
    const {
        location_type,
        location_id,
        location_name,
        treasury,
        transactions,
        can_configure,
        user_tax_history,
        user_salary_history,
        min_tax_rate,
        max_tax_rate,
        barony,
        kingdom,
    } = usePage<PageProps>().props;

    const [newTaxRate, setNewTaxRate] = useState(treasury.tax_rate.toString());
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const LocationIcon = locationIcons[location_type] || Home;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: location_name, href: `/${location_type}s/${location_id}` },
        { title: 'Taxes', href: '#' },
    ];

    const handleSetTaxRate = async () => {
        const rate = parseFloat(newTaxRate);
        if (isNaN(rate) || rate < min_tax_rate || rate > max_tax_rate) {
            setError(`Tax rate must be between ${min_tax_rate}% and ${max_tax_rate}%`);
            return;
        }

        setLoading(true);
        setError(null);
        setSuccess(null);

        try {
            const response = await fetch('/taxes/set-rate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    location_type,
                    location_id,
                    tax_rate: rate,
                }),
            });

            const data = await response.json();

            if (data.success) {
                setSuccess(data.message);
                router.reload({ only: ['treasury'] });
            } else {
                setError(data.message);
            }
        } catch {
            setError('An error occurred');
        } finally {
            setLoading(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Taxes - ${location_name}`} />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-6 flex items-center gap-3">
                    <div className="rounded-lg bg-amber-900/30 p-3">
                        <Landmark className="h-8 w-8 text-amber-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Treasury</h1>
                        <div className="flex items-center gap-1 text-stone-400">
                            <LocationIcon className="h-3 w-3" />
                            <span className="font-pixel text-xs">{location_name}</span>
                        </div>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Left Column - Treasury Info */}
                    <div className="space-y-4">
                        {/* Treasury Balance */}
                        <div className="rounded-xl border-2 border-amber-600/50 bg-amber-900/20 p-4">
                            <div className="mb-2 flex items-center gap-2 text-amber-300">
                                <Coins className="h-4 w-4" />
                                <span className="font-pixel text-xs">Treasury Balance</span>
                            </div>
                            <div className="font-pixel text-3xl text-amber-400">{formatGold(treasury.balance)}</div>
                            <div className="mt-3 grid grid-cols-2 gap-4 border-t border-amber-700/30 pt-3">
                                <div>
                                    <div className="font-pixel text-[10px] text-stone-500">Total Collected</div>
                                    <div className="font-pixel text-sm text-green-400">+{formatGold(treasury.total_collected)}</div>
                                </div>
                                <div>
                                    <div className="font-pixel text-[10px] text-stone-500">Total Distributed</div>
                                    <div className="font-pixel text-sm text-red-400">-{formatGold(treasury.total_distributed)}</div>
                                </div>
                            </div>
                        </div>

                        {/* Tax Rate Card */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <div className="mb-4 flex items-center justify-between">
                                <h2 className="font-pixel text-sm text-stone-300">Tax Rate</h2>
                                <div className="font-pixel text-2xl text-yellow-400">{treasury.tax_rate}%</div>
                            </div>

                            {can_configure && (location_type === 'barony' || location_type === 'kingdom') && (
                                <div className="border-t border-stone-700 pt-4">
                                    <div className="mb-3 flex items-center gap-2 text-stone-400">
                                        <Settings className="h-4 w-4" />
                                        <span className="font-pixel text-xs">Configure Tax Rate</span>
                                    </div>

                                    {error && (
                                        <div className="mb-3 rounded-lg border border-red-600/50 bg-red-900/20 px-3 py-2 font-pixel text-xs text-red-400">
                                            {error}
                                        </div>
                                    )}
                                    {success && (
                                        <div className="mb-3 rounded-lg border border-green-600/50 bg-green-900/20 px-3 py-2 font-pixel text-xs text-green-400">
                                            {success}
                                        </div>
                                    )}

                                    <div className="flex gap-2">
                                        <input
                                            type="number"
                                            value={newTaxRate}
                                            onChange={(e) => setNewTaxRate(e.target.value)}
                                            min={min_tax_rate}
                                            max={max_tax_rate}
                                            step="0.5"
                                            className="w-24 rounded-lg border-2 border-stone-600 bg-stone-900 px-3 py-2 font-pixel text-amber-300 focus:border-amber-500 focus:outline-none"
                                        />
                                        <span className="flex items-center font-pixel text-stone-400">%</span>
                                        <button
                                            onClick={handleSetTaxRate}
                                            disabled={loading}
                                            className="flex-1 rounded-lg border-2 border-amber-600 bg-amber-900/30 px-4 py-2 font-pixel text-sm text-amber-300 transition hover:bg-amber-800/50 disabled:opacity-50"
                                        >
                                            {loading ? <Loader2 className="mx-auto h-4 w-4 animate-spin" /> : 'Set Rate'}
                                        </button>
                                    </div>
                                    <p className="mt-2 font-pixel text-[10px] text-stone-500">
                                        Rate must be between {min_tax_rate}% and {max_tax_rate}%
                                    </p>
                                </div>
                            )}

                            {location_type === 'village' && barony && (
                                <div className="border-t border-stone-700 pt-3 text-stone-500">
                                    <p className="font-pixel text-[10px]">
                                        Tax rate set by {barony.name} ({barony.tax_rate}%)
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Hierarchy Info */}
                        {(barony || kingdom) && (
                            <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                                <h2 className="mb-3 font-pixel text-sm text-stone-300">Tax Hierarchy</h2>
                                <div className="space-y-2">
                                    {barony && (
                                        <div className="flex items-center justify-between rounded-lg bg-stone-900/50 px-3 py-2">
                                            <div className="flex items-center gap-2">
                                                <Castle className="h-4 w-4 text-stone-400" />
                                                <span className="font-pixel text-xs text-stone-300">{barony.name}</span>
                                            </div>
                                            <span className="font-pixel text-xs text-yellow-400">{barony.tax_rate}%</span>
                                        </div>
                                    )}
                                    {kingdom && (
                                        <div className="flex items-center justify-between rounded-lg bg-stone-900/50 px-3 py-2">
                                            <div className="flex items-center gap-2">
                                                <Crown className="h-4 w-4 text-stone-400" />
                                                <span className="font-pixel text-xs text-stone-300">{kingdom.name}</span>
                                            </div>
                                            <span className="font-pixel text-xs text-yellow-400">{kingdom.tax_rate}%</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Right Column - Transactions & History */}
                    <div className="space-y-4">
                        {/* Treasury Transactions */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 font-pixel text-sm text-stone-300">Recent Treasury Activity</h2>
                            {transactions.length > 0 ? (
                                <div className="space-y-2">
                                    {transactions.map((tx) => (
                                        <div
                                            key={tx.id}
                                            className="flex items-center justify-between rounded-lg bg-stone-900/50 px-3 py-2"
                                        >
                                            <div className="flex items-center gap-2">
                                                {tx.amount > 0 ? (
                                                    <ArrowDownToLine className="h-3 w-3 text-green-400" />
                                                ) : (
                                                    <ArrowUpFromLine className="h-3 w-3 text-red-400" />
                                                )}
                                                <div>
                                                    <div className="font-pixel text-xs text-stone-300">{tx.description}</div>
                                                    <div className="font-pixel text-[10px] text-stone-500">{tx.formatted_date}</div>
                                                </div>
                                            </div>
                                            <div
                                                className={`font-pixel text-sm ${tx.amount > 0 ? 'text-green-400' : 'text-red-400'}`}
                                            >
                                                {tx.amount > 0 ? '+' : ''}
                                                {formatGold(tx.amount)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="py-8 text-center">
                                    <Landmark className="mx-auto h-8 w-8 text-stone-600" />
                                    <p className="mt-2 font-pixel text-xs text-stone-500">No treasury activity yet</p>
                                </div>
                            )}
                        </div>

                        {/* User Tax History (only for village) */}
                        {user_tax_history && user_tax_history.length > 0 && (
                            <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                                <h2 className="mb-4 font-pixel text-sm text-stone-300">Your Tax Payments</h2>
                                <div className="space-y-2">
                                    {user_tax_history.map((tax) => (
                                        <div
                                            key={tax.id}
                                            className="flex items-center justify-between rounded-lg bg-stone-900/50 px-3 py-2"
                                        >
                                            <div>
                                                <div className="font-pixel text-xs text-stone-300">{tax.description}</div>
                                                <div className="font-pixel text-[10px] text-stone-500">
                                                    To {tax.receiver_name} &bull; {tax.formatted_date}
                                                </div>
                                            </div>
                                            <div className="font-pixel text-sm text-red-400">-{formatGold(tax.amount)}</div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* User Salary History (only for village) */}
                        {user_salary_history && user_salary_history.length > 0 && (
                            <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                                <h2 className="mb-4 font-pixel text-sm text-stone-300">Your Salary Payments</h2>
                                <div className="space-y-2">
                                    {user_salary_history.map((salary) => (
                                        <div
                                            key={salary.id}
                                            className="flex items-center justify-between rounded-lg bg-stone-900/50 px-3 py-2"
                                        >
                                            <div>
                                                <div className="font-pixel text-xs text-stone-300">{salary.role_name}</div>
                                                <div className="font-pixel text-[10px] text-stone-500">
                                                    From {salary.source_name} &bull; {salary.formatted_date}
                                                </div>
                                            </div>
                                            <div className="font-pixel text-sm text-green-400">+{formatGold(salary.amount)}</div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

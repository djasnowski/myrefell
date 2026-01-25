import { Head, usePage } from '@inertiajs/react';
import { ArrowDownToLine, ArrowUpFromLine, Coins, Landmark } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

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

interface PageProps {
    tax_history: TaxRecord[];
    salary_history: SalaryRecord[];
    player: {
        id: number;
        username: string;
        gold: number;
    };
    [key: string]: unknown;
}

function formatGold(amount: number): string {
    return amount.toLocaleString();
}

export default function MyTaxes() {
    const { tax_history, salary_history, player } = usePage<PageProps>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'My Taxes', href: '#' },
    ];

    const totalTaxesPaid = tax_history.reduce((sum, tax) => sum + tax.amount, 0);
    const totalSalariesReceived = salary_history.reduce((sum, salary) => sum + salary.amount, 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Taxes" />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-6 flex items-center gap-3">
                    <div className="rounded-lg bg-amber-900/30 p-3">
                        <Landmark className="h-8 w-8 text-amber-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">My Taxes</h1>
                        <p className="font-pixel text-xs text-stone-400">Your tax and salary history</p>
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="mb-6 grid gap-4 sm:grid-cols-3">
                    <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <div className="mb-2 flex items-center gap-2 text-stone-400">
                            <Coins className="h-4 w-4" />
                            <span className="font-pixel text-xs">Current Gold</span>
                        </div>
                        <div className="font-pixel text-2xl text-yellow-400">{formatGold(player.gold)}</div>
                    </div>
                    <div className="rounded-xl border-2 border-red-600/30 bg-red-900/10 p-4">
                        <div className="mb-2 flex items-center gap-2 text-red-400">
                            <ArrowUpFromLine className="h-4 w-4" />
                            <span className="font-pixel text-xs">Total Taxes Paid</span>
                        </div>
                        <div className="font-pixel text-2xl text-red-400">{formatGold(totalTaxesPaid)}</div>
                    </div>
                    <div className="rounded-xl border-2 border-green-600/30 bg-green-900/10 p-4">
                        <div className="mb-2 flex items-center gap-2 text-green-400">
                            <ArrowDownToLine className="h-4 w-4" />
                            <span className="font-pixel text-xs">Total Salaries</span>
                        </div>
                        <div className="font-pixel text-2xl text-green-400">{formatGold(totalSalariesReceived)}</div>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Tax Payments */}
                    <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <h2 className="mb-4 font-pixel text-sm text-stone-300">Tax Payments</h2>
                        {tax_history.length > 0 ? (
                            <div className="space-y-2">
                                {tax_history.map((tax) => (
                                    <div
                                        key={tax.id}
                                        className="flex items-center justify-between rounded-lg bg-stone-900/50 px-3 py-2"
                                    >
                                        <div className="flex items-center gap-2">
                                            <ArrowUpFromLine className="h-3 w-3 text-red-400" />
                                            <div>
                                                <div className="font-pixel text-xs text-stone-300">{tax.description}</div>
                                                <div className="font-pixel text-[10px] text-stone-500">
                                                    To {tax.receiver_name} &bull; {tax.formatted_date}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="font-pixel text-sm text-red-400">-{formatGold(tax.amount)}</div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="py-8 text-center">
                                <Landmark className="mx-auto h-8 w-8 text-stone-600" />
                                <p className="mt-2 font-pixel text-xs text-stone-500">No tax payments yet</p>
                            </div>
                        )}
                    </div>

                    {/* Salary Payments */}
                    <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <h2 className="mb-4 font-pixel text-sm text-stone-300">Salary Payments</h2>
                        {salary_history.length > 0 ? (
                            <div className="space-y-2">
                                {salary_history.map((salary) => (
                                    <div
                                        key={salary.id}
                                        className="flex items-center justify-between rounded-lg bg-stone-900/50 px-3 py-2"
                                    >
                                        <div className="flex items-center gap-2">
                                            <ArrowDownToLine className="h-3 w-3 text-green-400" />
                                            <div>
                                                <div className="font-pixel text-xs text-stone-300">{salary.role_name}</div>
                                                <div className="font-pixel text-[10px] text-stone-500">
                                                    From {salary.source_name} &bull; {salary.formatted_date}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="font-pixel text-sm text-green-400">+{formatGold(salary.amount)}</div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="py-8 text-center">
                                <Coins className="mx-auto h-8 w-8 text-stone-600" />
                                <p className="mt-2 font-pixel text-xs text-stone-500">No salary payments yet</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

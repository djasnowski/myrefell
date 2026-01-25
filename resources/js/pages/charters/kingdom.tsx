import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Kingdom {
    id: number;
    name: string;
    biome: string;
    king: {
        id: number;
        username: string;
    } | null;
}

interface Charter {
    id: number;
    settlement_name: string;
    description: string | null;
    settlement_type: string;
    kingdom: {
        id: number;
        name: string;
    };
    founder: {
        id: number;
        username: string;
    } | null;
    status: string;
    required_signatories: number;
    current_signatories: number;
    has_enough_signatories: boolean;
    gold_cost: number;
    submitted_at: string | null;
}

interface Ruin {
    id: number;
    name: string;
    description: string | null;
    biome: string;
    coordinates: {
        x: number;
        y: number;
    };
    reclaim_cost: number;
    original_founder: {
        id: number;
        username: string;
    } | null;
    ruined_at: string;
}

interface Props {
    kingdom: Kingdom;
    charters: Charter[];
    ruins: Ruin[];
}

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    approved: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    expired: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
    failed: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
};

const typeColors: Record<string, string> = {
    village: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
    town: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    castle: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
};

export default function KingdomCharters({ kingdom, charters, ruins }: Props) {
    const [isSubmitting, setIsSubmitting] = useState<number | null>(null);
    const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Kingdoms', href: '/kingdoms' },
        { title: kingdom.name, href: `/kingdoms/${kingdom.id}` },
        { title: 'Charters', href: `/kingdoms/${kingdom.id}/charters` },
    ];

    const formatGold = (amount: number) => amount.toLocaleString();

    const handleReclaim = async (ruinId: number) => {
        setIsSubmitting(ruinId);
        setMessage(null);

        try {
            const response = await fetch(`/ruins/${ruinId}/reclaim`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (data.success) {
                setMessage({ type: 'success', text: data.message });
                router.reload();
            } else {
                setMessage({ type: 'error', text: data.message });
            }
        } catch {
            setMessage({ type: 'error', text: 'An error occurred. Please try again.' });
        } finally {
            setIsSubmitting(null);
        }
    };

    const pendingCharters = charters.filter((c) => c.status === 'pending');
    const approvedCharters = charters.filter((c) => c.status === 'approved');
    const activeCharters = charters.filter((c) => c.status === 'active');
    const otherCharters = charters.filter((c) => !['pending', 'approved', 'active'].includes(c.status));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Charters - ${kingdom.name}`} />
            <div className="flex flex-col gap-6 p-6">
                {message && (
                    <div className={`p-3 rounded-md text-sm ${message.type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                        {message.text}
                    </div>
                )}

                <div>
                    <h1 className="text-2xl font-bold">Charters of {kingdom.name}</h1>
                    <p className="text-muted-foreground">
                        {kingdom.king ? `Ruled by ${kingdom.king.username}` : 'No ruler'} | {kingdom.biome} biome
                    </p>
                </div>

                {/* Pending Charters */}
                {pendingCharters.length > 0 && (
                    <div>
                        <h2 className="text-xl font-semibold mb-4">Pending Approval ({pendingCharters.length})</h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {pendingCharters.map((charter) => (
                                <Link key={charter.id} href={`/charters/${charter.id}`}>
                                    <Card className="transition-shadow hover:shadow-lg cursor-pointer h-full">
                                        <CardHeader>
                                            <div className="flex items-center justify-between gap-2">
                                                <CardTitle className="text-lg">{charter.settlement_name}</CardTitle>
                                                <Badge className={statusColors[charter.status] || ''}>
                                                    {charter.status}
                                                </Badge>
                                            </div>
                                            <CardDescription>
                                                <Badge variant="outline" className={typeColors[charter.settlement_type] || ''}>
                                                    {charter.settlement_type}
                                                </Badge>
                                                <span className="ml-2">by {charter.founder?.username || 'Unknown'}</span>
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="text-sm">
                                                <p>
                                                    Signatories: {charter.current_signatories} / {charter.required_signatories}
                                                    {charter.has_enough_signatories && ' ✓'}
                                                </p>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}

                {/* Approved Charters */}
                {approvedCharters.length > 0 && (
                    <div>
                        <h2 className="text-xl font-semibold mb-4">Approved - Awaiting Foundation ({approvedCharters.length})</h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {approvedCharters.map((charter) => (
                                <Link key={charter.id} href={`/charters/${charter.id}`}>
                                    <Card className="transition-shadow hover:shadow-lg cursor-pointer h-full border-blue-200 dark:border-blue-800">
                                        <CardHeader>
                                            <div className="flex items-center justify-between gap-2">
                                                <CardTitle className="text-lg">{charter.settlement_name}</CardTitle>
                                                <Badge className={statusColors[charter.status] || ''}>
                                                    {charter.status}
                                                </Badge>
                                            </div>
                                            <CardDescription>
                                                <Badge variant="outline" className={typeColors[charter.settlement_type] || ''}>
                                                    {charter.settlement_type}
                                                </Badge>
                                                <span className="ml-2">by {charter.founder?.username || 'Unknown'}</span>
                                            </CardDescription>
                                        </CardHeader>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}

                {/* Active Settlements */}
                {activeCharters.length > 0 && (
                    <div>
                        <h2 className="text-xl font-semibold mb-4">Founded Settlements ({activeCharters.length})</h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {activeCharters.map((charter) => (
                                <Link key={charter.id} href={`/charters/${charter.id}`}>
                                    <Card className="transition-shadow hover:shadow-lg cursor-pointer h-full border-green-200 dark:border-green-800">
                                        <CardHeader>
                                            <div className="flex items-center justify-between gap-2">
                                                <CardTitle className="text-lg">{charter.settlement_name}</CardTitle>
                                                <Badge className={statusColors[charter.status] || ''}>
                                                    founded
                                                </Badge>
                                            </div>
                                            <CardDescription>
                                                <Badge variant="outline" className={typeColors[charter.settlement_type] || ''}>
                                                    {charter.settlement_type}
                                                </Badge>
                                                <span className="ml-2">by {charter.founder?.username || 'Unknown'}</span>
                                            </CardDescription>
                                        </CardHeader>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}

                {/* Ruins */}
                {ruins.length > 0 && (
                    <div>
                        <h2 className="text-xl font-semibold mb-4">Ruins Available for Reclamation ({ruins.length})</h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {ruins.map((ruin) => (
                                <Card key={ruin.id} className="h-full border-amber-200 dark:border-amber-800">
                                    <CardHeader>
                                        <CardTitle className="text-lg">{ruin.name}</CardTitle>
                                        <CardDescription>
                                            {ruin.biome} | ({ruin.coordinates.x}, {ruin.coordinates.y})
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        {ruin.description && (
                                            <p className="text-sm text-muted-foreground">{ruin.description}</p>
                                        )}
                                        <div className="text-sm">
                                            {ruin.original_founder && (
                                                <p className="text-muted-foreground">
                                                    Originally founded by {ruin.original_founder.username}
                                                </p>
                                            )}
                                            <p className="text-muted-foreground">
                                                Ruined: {new Date(ruin.ruined_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="font-medium text-yellow-600">
                                                {formatGold(ruin.reclaim_cost)} gold
                                            </span>
                                            <Button
                                                size="sm"
                                                onClick={() => handleReclaim(ruin.id)}
                                                disabled={isSubmitting === ruin.id}
                                            >
                                                {isSubmitting === ruin.id ? 'Claiming...' : 'Reclaim'}
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </div>
                )}

                {/* Other Charters (rejected, expired, failed) */}
                {otherCharters.length > 0 && (
                    <div>
                        <h2 className="text-xl font-semibold mb-4">Past Charters ({otherCharters.length})</h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {otherCharters.map((charter) => (
                                <Link key={charter.id} href={`/charters/${charter.id}`}>
                                    <Card className="transition-shadow hover:shadow-lg cursor-pointer h-full opacity-75">
                                        <CardHeader>
                                            <div className="flex items-center justify-between gap-2">
                                                <CardTitle className="text-lg">{charter.settlement_name}</CardTitle>
                                                <Badge className={statusColors[charter.status] || ''}>
                                                    {charter.status}
                                                </Badge>
                                            </div>
                                            <CardDescription>
                                                <Badge variant="outline" className={typeColors[charter.settlement_type] || ''}>
                                                    {charter.settlement_type}
                                                </Badge>
                                                <span className="ml-2">by {charter.founder?.username || 'Unknown'}</span>
                                            </CardDescription>
                                        </CardHeader>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}

                {charters.length === 0 && ruins.length === 0 && (
                    <Card>
                        <CardContent className="py-8 text-center text-muted-foreground">
                            No charters or ruins in this kingdom yet.
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

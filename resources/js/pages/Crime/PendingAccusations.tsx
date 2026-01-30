import { Head, router, usePage } from '@inertiajs/react';
import { AlertTriangle, Check, FileText, Gavel, User, X } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Accusation {
    id: number;
    accuser: string;
    accused: string;
    crime_type: string;
    accusation_text: string;
    evidence: string | null;
    created_at: string;
}

interface PageProps {
    accusations: Accusation[];
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Crime', href: '/crime' },
    { title: 'Pending Accusations', href: '#' },
];

export default function PendingAccusations() {
    const { accusations } = usePage<PageProps>().props;
    const [reviewingId, setReviewingId] = useState<number | null>(null);
    const [notes, setNotes] = useState('');
    const [loading, setLoading] = useState(false);

    const handleReview = (id: number, decision: 'accept' | 'reject' | 'false') => {
        setLoading(true);
        router.post(
            `/crime/accusations/${id}/review`,
            { decision, notes },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                    setReviewingId(null);
                    setNotes('');
                },
                onFinish: () => setLoading(false),
            }
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pending Accusations" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="rounded-lg bg-amber-900/30 p-2">
                        <Gavel className="size-6 text-amber-400" />
                    </div>
                    <div>
                        <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                            Pending Accusations
                        </h1>
                        <p className="text-sm text-stone-400">
                            Review accusations in your jurisdiction
                        </p>
                    </div>
                </div>

                {accusations.length === 0 ? (
                    <div className="rounded-xl border border-stone-800 bg-stone-900/50 p-8 text-center">
                        <FileText className="mx-auto size-12 text-stone-600" />
                        <p className="mt-4 text-stone-400">
                            No pending accusations to review
                        </p>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {accusations.map((accusation) => (
                            <div
                                key={accusation.id}
                                className="rounded-xl border border-stone-800 bg-stone-900/50 p-4"
                            >
                                <div className="flex items-start justify-between">
                                    <div>
                                        <div className="flex items-center gap-2 text-sm">
                                            <User className="size-4 text-stone-500" />
                                            <span className="text-stone-400">
                                                {accusation.accuser}
                                            </span>
                                            <span className="text-stone-600">accuses</span>
                                            <span className="font-semibold text-stone-100">
                                                {accusation.accused}
                                            </span>
                                        </div>
                                        <div className="mt-1 flex items-center gap-2">
                                            <span className="rounded bg-red-900/30 px-2 py-0.5 text-xs text-red-400">
                                                {accusation.crime_type}
                                            </span>
                                            <span className="text-xs text-stone-500">
                                                {accusation.created_at}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div className="mt-3 rounded bg-stone-900/50 p-3">
                                    <p className="text-sm text-stone-300">
                                        {accusation.accusation_text}
                                    </p>
                                    {accusation.evidence && (
                                        <div className="mt-2 border-t border-stone-800 pt-2">
                                            <p className="text-xs text-stone-500">Evidence:</p>
                                            <p className="text-sm text-stone-400">
                                                {accusation.evidence}
                                            </p>
                                        </div>
                                    )}
                                </div>

                                {reviewingId === accusation.id ? (
                                    <div className="mt-4 space-y-3 border-t border-stone-800 pt-4">
                                        <Textarea
                                            placeholder="Notes (optional)..."
                                            value={notes}
                                            onChange={(e) => setNotes(e.target.value)}
                                            className="border-stone-700 bg-stone-900/50"
                                            rows={2}
                                        />
                                        <div className="flex gap-2">
                                            <Button
                                                size="sm"
                                                onClick={() => handleReview(accusation.id, 'accept')}
                                                disabled={loading}
                                                className="bg-green-600 hover:bg-green-700"
                                            >
                                                <Check className="size-4" />
                                                Accept (Schedule Trial)
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => handleReview(accusation.id, 'reject')}
                                                disabled={loading}
                                                className="border-stone-700"
                                            >
                                                <X className="size-4" />
                                                Reject
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() => handleReview(accusation.id, 'false')}
                                                disabled={loading}
                                            >
                                                <AlertTriangle className="size-4" />
                                                False Accusation
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                onClick={() => {
                                                    setReviewingId(null);
                                                    setNotes('');
                                                }}
                                                disabled={loading}
                                            >
                                                Cancel
                                            </Button>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="mt-4 flex justify-end">
                                        <Button
                                            size="sm"
                                            onClick={() => setReviewingId(accusation.id)}
                                        >
                                            <Gavel className="size-4" />
                                            Review
                                        </Button>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

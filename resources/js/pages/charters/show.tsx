import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Signatory {
    id: number;
    user: {
        id: number;
        username: string;
    };
    comment: string | null;
    signed_at: string;
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
    issuer: {
        id: number;
        username: string;
    } | null;
    tax_terms: {
        village_rate: number;
        kingdom_tribute: number;
        years_tax_free: number;
    } | null;
    status: string;
    required_signatories: number;
    current_signatories: number;
    has_enough_signatories: boolean;
    gold_cost: number;
    coordinates: {
        x: number;
        y: number;
    } | null;
    biome: string | null;
    is_vulnerable: boolean;
    vulnerability_ends_at: string | null;
    submitted_at: string | null;
    approved_at: string | null;
    founded_at: string | null;
    expires_at: string | null;
    rejection_reason: string | null;
    founded_settlement: {
        type: string;
        id: number;
        name: string;
    } | null;
    signatories: Signatory[];
}

interface Props {
    charter: Charter;
}

interface PageProps {
    auth: {
        user: {
            id: number;
            username: string;
            is_admin?: boolean;
        };
    };
    [key: string]: unknown;
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
    barony: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
};

export default function CharterShow({ charter }: Props) {
    const { auth } = usePage<PageProps>().props;
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);
    const [signComment, setSignComment] = useState('');
    const [rejectReason, setRejectReason] = useState('');
    const [coordinatesX, setCoordinatesX] = useState<string>(charter.coordinates?.x?.toString() || '');
    const [coordinatesY, setCoordinatesY] = useState<string>(charter.coordinates?.y?.toString() || '');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Charters', href: '/charters' },
        { title: charter.settlement_name, href: `/charters/${charter.id}` },
    ];

    const isFounder = charter.founder?.id === auth.user.id;
    const hasAlreadySigned = charter.signatories.some((s) => s.user.id === auth.user.id);
    const canSign = charter.status === 'pending' && !hasAlreadySigned;
    const canApprove = charter.status === 'pending' && charter.has_enough_signatories && auth.user.is_admin;
    const canReject = charter.status === 'pending' && auth.user.is_admin;
    const canFound = charter.status === 'approved' && isFounder;
    const canCancel = charter.status === 'pending' && isFounder;

    const formatGold = (amount: number) => amount.toLocaleString();
    const formatDate = (dateStr: string) => new Date(dateStr).toLocaleString();

    const handleAction = async (action: string, body: Record<string, unknown> = {}) => {
        setIsSubmitting(true);
        setMessage(null);

        try {
            const response = await fetch(`/charters/${charter.id}/${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(body),
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
            setIsSubmitting(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Charter: ${charter.settlement_name}`} />
            <div className="flex flex-col gap-6 p-6">
                {message && (
                    <div className={`p-3 rounded-md text-sm ${message.type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                        {message.text}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <CardTitle className="text-2xl">{charter.settlement_name}</CardTitle>
                                <CardDescription className="flex items-center gap-2 mt-1">
                                    <Badge className={typeColors[charter.settlement_type] || ''}>
                                        {charter.settlement_type}
                                    </Badge>
                                    <Badge className={statusColors[charter.status] || ''}>
                                        {charter.status}
                                    </Badge>
                                    in {charter.kingdom.name}
                                </CardDescription>
                            </div>
                            {charter.is_vulnerable && (
                                <Badge variant="destructive">Vulnerable</Badge>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        {charter.description && (
                            <p className="text-muted-foreground">{charter.description}</p>
                        )}

                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <span className="text-sm text-muted-foreground">Founder</span>
                                <p className="font-medium">{charter.founder?.username || 'Unknown'}</p>
                            </div>
                            <div>
                                <span className="text-sm text-muted-foreground">Charter Cost</span>
                                <p className="font-medium text-yellow-600">{formatGold(charter.gold_cost)} gold</p>
                            </div>
                            <div>
                                <span className="text-sm text-muted-foreground">Signatories</span>
                                <p className="font-medium">
                                    {charter.current_signatories} / {charter.required_signatories}
                                    {charter.has_enough_signatories && ' ✓'}
                                </p>
                            </div>
                            <div>
                                <span className="text-sm text-muted-foreground">Biome</span>
                                <p className="font-medium">{charter.biome || 'Not set'}</p>
                            </div>
                        </div>

                        {charter.tax_terms && (
                            <div>
                                <h3 className="font-semibold mb-2">Tax Terms</h3>
                                <div className="grid grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <span className="text-muted-foreground">Village Rate:</span>
                                        <p>{charter.tax_terms.village_rate}%</p>
                                    </div>
                                    <div>
                                        <span className="text-muted-foreground">Kingdom Tribute:</span>
                                        <p>{charter.tax_terms.kingdom_tribute}%</p>
                                    </div>
                                    <div>
                                        <span className="text-muted-foreground">Tax-Free Period:</span>
                                        <p>{charter.tax_terms.years_tax_free} year(s)</p>
                                    </div>
                                </div>
                            </div>
                        )}

                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            {charter.submitted_at && (
                                <div>
                                    <span className="text-muted-foreground">Submitted</span>
                                    <p>{formatDate(charter.submitted_at)}</p>
                                </div>
                            )}
                            {charter.approved_at && (
                                <div>
                                    <span className="text-muted-foreground">Approved by</span>
                                    <p>{charter.issuer?.username || 'Unknown'}</p>
                                    <p className="text-xs">{formatDate(charter.approved_at)}</p>
                                </div>
                            )}
                            {charter.expires_at && charter.status === 'approved' && (
                                <div>
                                    <span className="text-muted-foreground">Expires</span>
                                    <p className="text-amber-600">{formatDate(charter.expires_at)}</p>
                                </div>
                            )}
                            {charter.founded_at && (
                                <div>
                                    <span className="text-muted-foreground">Founded</span>
                                    <p>{formatDate(charter.founded_at)}</p>
                                </div>
                            )}
                            {charter.vulnerability_ends_at && charter.is_vulnerable && (
                                <div>
                                    <span className="text-muted-foreground">Protected until</span>
                                    <p>{formatDate(charter.vulnerability_ends_at)}</p>
                                </div>
                            )}
                        </div>

                        {charter.rejection_reason && (
                            <div className="p-3 bg-red-50 dark:bg-red-900/20 rounded-md">
                                <span className="text-sm font-medium text-red-800 dark:text-red-200">Rejection Reason:</span>
                                <p className="text-red-700 dark:text-red-300">{charter.rejection_reason}</p>
                            </div>
                        )}

                        {charter.founded_settlement && (
                            <div className="p-3 bg-green-50 dark:bg-green-900/20 rounded-md">
                                <span className="text-sm font-medium text-green-800 dark:text-green-200">Settlement Founded:</span>
                                <p className="text-green-700 dark:text-green-300">
                                    {charter.founded_settlement.name} ({charter.founded_settlement.type})
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Actions */}
                <div className="flex flex-wrap gap-4">
                    {canSign && (
                        <Card className="flex-1 min-w-[300px]">
                            <CardHeader>
                                <CardTitle className="text-lg">Sign Charter</CardTitle>
                                <CardDescription>Add your signature to support this charter.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="comment">Comment (optional)</Label>
                                    <Textarea
                                        id="comment"
                                        value={signComment}
                                        onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setSignComment(e.target.value)}
                                        placeholder="Your message of support..."
                                        rows={2}
                                    />
                                </div>
                                <Button
                                    onClick={() => handleAction('sign', { comment: signComment || null })}
                                    disabled={isSubmitting}
                                >
                                    Sign Charter
                                </Button>
                            </CardContent>
                        </Card>
                    )}

                    {canFound && (
                        <Card className="flex-1 min-w-[300px]">
                            <CardHeader>
                                <CardTitle className="text-lg">Found Settlement</CardTitle>
                                <CardDescription>Establish your new settlement at the specified coordinates.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="x">X Coordinate</Label>
                                        <Input
                                            id="x"
                                            type="number"
                                            min="0"
                                            max="1000"
                                            value={coordinatesX}
                                            onChange={(e) => setCoordinatesX(e.target.value)}
                                            placeholder="0-1000"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="y">Y Coordinate</Label>
                                        <Input
                                            id="y"
                                            type="number"
                                            min="0"
                                            max="1000"
                                            value={coordinatesY}
                                            onChange={(e) => setCoordinatesY(e.target.value)}
                                            placeholder="0-1000"
                                        />
                                    </div>
                                </div>
                                <Button
                                    onClick={() => handleAction('found', {
                                        coordinates_x: coordinatesX ? parseInt(coordinatesX) : null,
                                        coordinates_y: coordinatesY ? parseInt(coordinatesY) : null,
                                    })}
                                    disabled={isSubmitting}
                                    className="bg-green-600 hover:bg-green-700"
                                >
                                    Found Settlement
                                </Button>
                            </CardContent>
                        </Card>
                    )}

                    {canApprove && (
                        <Card className="flex-1 min-w-[300px]">
                            <CardHeader>
                                <CardTitle className="text-lg">Royal Approval</CardTitle>
                                <CardDescription>As ruler, approve or reject this charter.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <Button
                                    onClick={() => handleAction('approve')}
                                    disabled={isSubmitting}
                                    className="w-full bg-green-600 hover:bg-green-700"
                                >
                                    Approve Charter
                                </Button>
                                <div className="space-y-2">
                                    <Label htmlFor="reject_reason">Rejection Reason</Label>
                                    <Textarea
                                        id="reject_reason"
                                        value={rejectReason}
                                        onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setRejectReason(e.target.value)}
                                        placeholder="Reason for rejection..."
                                        rows={2}
                                    />
                                </div>
                                <Button
                                    onClick={() => handleAction('reject', { reason: rejectReason })}
                                    disabled={isSubmitting || !rejectReason}
                                    variant="destructive"
                                    className="w-full"
                                >
                                    Reject Charter
                                </Button>
                            </CardContent>
                        </Card>
                    )}

                    {canReject && !canApprove && (
                        <Card className="flex-1 min-w-[300px]">
                            <CardHeader>
                                <CardTitle className="text-lg">Reject Charter</CardTitle>
                                <CardDescription>As ruler, reject this charter request.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="reject_reason">Rejection Reason</Label>
                                    <Textarea
                                        id="reject_reason"
                                        value={rejectReason}
                                        onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setRejectReason(e.target.value)}
                                        placeholder="Reason for rejection..."
                                        rows={2}
                                    />
                                </div>
                                <Button
                                    onClick={() => handleAction('reject', { reason: rejectReason })}
                                    disabled={isSubmitting || !rejectReason}
                                    variant="destructive"
                                >
                                    Reject Charter
                                </Button>
                            </CardContent>
                        </Card>
                    )}

                    {canCancel && (
                        <Card className="flex-1 min-w-[300px]">
                            <CardHeader>
                                <CardTitle className="text-lg">Cancel Charter</CardTitle>
                                <CardDescription>Cancel your charter request. You will receive a 75% refund.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button
                                    onClick={() => handleAction('cancel')}
                                    disabled={isSubmitting}
                                    variant="destructive"
                                >
                                    Cancel Charter
                                </Button>
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* Signatories */}
                <Card>
                    <CardHeader>
                        <CardTitle>Signatories ({charter.signatories.length})</CardTitle>
                        <CardDescription>
                            {charter.has_enough_signatories
                                ? 'This charter has enough signatories.'
                                : `${charter.required_signatories - charter.current_signatories} more signatures needed.`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {charter.signatories.length === 0 ? (
                            <p className="text-muted-foreground text-center py-4">No signatories yet.</p>
                        ) : (
                            <div className="space-y-3">
                                {charter.signatories.map((sig) => (
                                    <div key={sig.id} className="flex items-start justify-between p-3 bg-muted/50 rounded-md">
                                        <div>
                                            <p className="font-medium">{sig.user.username}</p>
                                            {sig.comment && (
                                                <p className="text-sm text-muted-foreground italic">"{sig.comment}"</p>
                                            )}
                                        </div>
                                        <span className="text-xs text-muted-foreground">
                                            {new Date(sig.signed_at).toLocaleDateString()}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

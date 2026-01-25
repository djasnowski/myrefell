import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Kingdom {
    id: number;
    name: string;
    biome: string;
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
    approved_at: string | null;
    founded_at: string | null;
    expires_at: string | null;
}

interface Props {
    myCharters: Charter[];
    kingdoms: Kingdom[];
    costs: {
        village: number;
        town: number;
        castle: number;
    };
    signatoryRequirements: {
        village: number;
        town: number;
        castle: number;
    };
    userGold: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Charters', href: '/charters' },
];

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

export default function ChartersIndex({ myCharters, kingdoms, costs, signatoryRequirements, userGold }: Props) {
    const [isCreating, setIsCreating] = useState(false);
    const [settlementName, setSettlementName] = useState('');
    const [settlementType, setSettlementType] = useState<string>('village');
    const [kingdomId, setKingdomId] = useState<string>('');
    const [description, setDescription] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const selectedCost = costs[settlementType as keyof typeof costs] || costs.village;
    const selectedSignatories = signatoryRequirements[settlementType as keyof typeof signatoryRequirements] || signatoryRequirements.village;
    const canAfford = userGold >= selectedCost;

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setError(null);

        try {
            const response = await fetch('/charters', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    settlement_name: settlementName,
                    settlement_type: settlementType,
                    kingdom_id: parseInt(kingdomId),
                    description: description || null,
                }),
            });

            const data = await response.json();

            if (data.success) {
                setIsCreating(false);
                setSettlementName('');
                setDescription('');
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError('An error occurred. Please try again.');
        } finally {
            setIsSubmitting(false);
        }
    };

    const formatGold = (amount: number) => {
        return amount.toLocaleString();
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Charters" />
            <div className="flex flex-col gap-6 p-6">
                <div className="flex justify-between items-start">
                    <div>
                        <h1 className="text-2xl font-bold">Settlement Charters</h1>
                        <p className="text-muted-foreground">Found new settlements in the realm of Myrefell.</p>
                    </div>
                    <div className="text-right">
                        <p className="text-sm text-muted-foreground">Your Gold</p>
                        <p className="text-xl font-bold text-yellow-600">{formatGold(userGold)}</p>
                    </div>
                </div>

                {!isCreating ? (
                    <Button onClick={() => setIsCreating(true)} className="w-fit">
                        Request New Charter
                    </Button>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle>Request a Charter</CardTitle>
                            <CardDescription>
                                Submit a request to found a new settlement. You will need to gather signatories and receive royal approval.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                {error && (
                                    <div className="p-3 bg-red-100 text-red-800 rounded-md text-sm">
                                        {error}
                                    </div>
                                )}

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="settlement_name">Settlement Name</Label>
                                        <Input
                                            id="settlement_name"
                                            value={settlementName}
                                            onChange={(e) => setSettlementName(e.target.value)}
                                            placeholder="New Valdoria"
                                            required
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="settlement_type">Settlement Type</Label>
                                        <Select value={settlementType} onValueChange={setSettlementType}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="village">Village ({formatGold(costs.village)} gold)</SelectItem>
                                                <SelectItem value="town">Town ({formatGold(costs.town)} gold)</SelectItem>
                                                <SelectItem value="castle">Castle ({formatGold(costs.castle)} gold)</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="kingdom">Kingdom</Label>
                                        <Select value={kingdomId} onValueChange={setKingdomId}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select kingdom" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {kingdoms.map((kingdom) => (
                                                    <SelectItem key={kingdom.id} value={kingdom.id.toString()}>
                                                        {kingdom.name} ({kingdom.biome})
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="space-y-2">
                                        <Label>Requirements</Label>
                                        <div className="text-sm text-muted-foreground">
                                            <p>Cost: <span className={canAfford ? 'text-green-600' : 'text-red-600'}>{formatGold(selectedCost)} gold</span></p>
                                            <p>Signatories needed: {selectedSignatories}</p>
                                        </div>
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="description">Description (optional)</Label>
                                    <Textarea
                                        id="description"
                                        value={description}
                                        onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setDescription(e.target.value)}
                                        placeholder="A brief description of your planned settlement..."
                                        rows={3}
                                    />
                                </div>

                                <div className="flex gap-2">
                                    <Button type="submit" disabled={isSubmitting || !canAfford || !kingdomId}>
                                        {isSubmitting ? 'Submitting...' : 'Submit Charter Request'}
                                    </Button>
                                    <Button type="button" variant="outline" onClick={() => setIsCreating(false)}>
                                        Cancel
                                    </Button>
                                </div>

                                {!canAfford && (
                                    <p className="text-sm text-red-600">
                                        You need {formatGold(selectedCost - userGold)} more gold to request this charter.
                                    </p>
                                )}
                            </form>
                        </CardContent>
                    </Card>
                )}

                <div>
                    <h2 className="text-xl font-semibold mb-4">Your Charters</h2>
                    {myCharters.length === 0 ? (
                        <Card>
                            <CardContent className="py-8 text-center text-muted-foreground">
                                You have not requested any charters yet.
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {myCharters.map((charter) => (
                                <Link key={charter.id} href={`/charters/${charter.id}`}>
                                    <Card className="transition-shadow hover:shadow-lg cursor-pointer h-full">
                                        <CardHeader>
                                            <div className="flex items-center justify-between gap-2">
                                                <CardTitle className="text-lg">{charter.settlement_name}</CardTitle>
                                                <Badge className={statusColors[charter.status] || ''}>
                                                    {charter.status}
                                                </Badge>
                                            </div>
                                            <CardDescription className="flex items-center gap-2">
                                                <Badge variant="outline" className={typeColors[charter.settlement_type] || ''}>
                                                    {charter.settlement_type}
                                                </Badge>
                                                in {charter.kingdom.name}
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="grid grid-cols-2 gap-2 text-sm">
                                                <div>
                                                    <span className="text-muted-foreground">Signatories:</span>
                                                    <p className="font-medium">
                                                        {charter.current_signatories} / {charter.required_signatories}
                                                        {charter.has_enough_signatories && ' ✓'}
                                                    </p>
                                                </div>
                                                <div>
                                                    <span className="text-muted-foreground">Cost:</span>
                                                    <p className="font-medium">{formatGold(charter.gold_cost)}</p>
                                                </div>
                                                {charter.expires_at && charter.status === 'approved' && (
                                                    <div className="col-span-2">
                                                        <span className="text-muted-foreground">Expires:</span>
                                                        <p className="font-medium text-amber-600">
                                                            {new Date(charter.expires_at).toLocaleDateString()}
                                                        </p>
                                                    </div>
                                                )}
                                            </div>
                                        </CardContent>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>

                <div>
                    <h2 className="text-xl font-semibold mb-4">Browse Kingdom Charters</h2>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        {kingdoms.map((kingdom) => (
                            <Link key={kingdom.id} href={`/kingdoms/${kingdom.id}/charters`}>
                                <Card className="transition-shadow hover:shadow-lg cursor-pointer">
                                    <CardHeader className="pb-2">
                                        <CardTitle className="text-base">{kingdom.name}</CardTitle>
                                        <CardDescription>{kingdom.biome}</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-sm text-muted-foreground">View charters and ruins</p>
                                    </CardContent>
                                </Card>
                            </Link>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

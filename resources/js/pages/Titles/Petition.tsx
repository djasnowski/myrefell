import { Head, Link, router } from '@inertiajs/react';
import {
    Crown,
    ScrollText,
    Check,
    X,
    Coins,
    ChevronLeft,
    User,
    Sparkles,
    Shield,
} from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface TitleType {
    id: number;
    name: string;
    slug: string;
    tier: number;
    category: string;
    description: string | null;
    style_of_address: string | null;
    requirements: Record<string, unknown> | null;
    can_purchase: boolean;
    purchase_cost: number | null;
    requires_ceremony: boolean;
}

interface PotentialGrantor {
    id: number;
    username: string;
    title: string | null;
    domain_name: string | null;
}

interface Props {
    title_type: TitleType;
    meets_requirements: boolean;
    met_requirements: string[];
    unmet_requirements: string[];
    potential_grantors: PotentialGrantor[];
    user_gold: number;
}

export default function Petition({
    title_type,
    meets_requirements,
    met_requirements,
    unmet_requirements,
    potential_grantors,
    user_gold,
}: Props) {
    const [submitting, setSubmitting] = useState(false);
    const [selectedGrantor, setSelectedGrantor] = useState<number | null>(
        potential_grantors.length === 1 ? potential_grantors[0].id : null
    );
    const [message, setMessage] = useState('');
    const [isPurchase, setIsPurchase] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Titles', href: '/titles' },
        { title: `Petition for ${title_type.name}`, href: '#' },
    ];

    const canAffordPurchase = title_type.can_purchase &&
        title_type.purchase_cost !== null &&
        user_gold >= title_type.purchase_cost;

    const canSubmit = meets_requirements && selectedGrantor !== null;

    const handleSubmit = () => {
        if (!canSubmit || submitting) return;

        setSubmitting(true);
        router.post('/titles/petition', {
            title_type_id: title_type.id,
            petition_to_id: selectedGrantor,
            message: message || null,
            is_purchase: isPurchase && canAffordPurchase,
        }, {
            onSuccess: () => {
                router.reload();
            },
            onFinish: () => setSubmitting(false),
        });
    };

    const getCategoryColor = (category: string) => {
        switch (category) {
            case 'royalty': return 'text-purple-400';
            case 'landed_nobility': return 'text-amber-400';
            case 'minor_nobility': return 'text-blue-400';
            default: return 'text-stone-400';
        }
    };

    const getCategoryBorder = (category: string) => {
        switch (category) {
            case 'royalty': return 'border-purple-600/50 bg-purple-900/20';
            case 'landed_nobility': return 'border-amber-600/50 bg-amber-900/20';
            case 'minor_nobility': return 'border-blue-600/50 bg-blue-900/20';
            default: return 'border-stone-700 bg-stone-800/30';
        }
    };

    const getCategoryLabel = (category: string) => {
        switch (category) {
            case 'royalty': return 'Royalty';
            case 'landed_nobility': return 'Landed Nobility';
            case 'minor_nobility': return 'Minor Nobility';
            default: return 'Commoner';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Petition for ${title_type.name}`} />
            <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                {/* Back Link */}
                <Link
                    href="/titles"
                    className="mb-4 inline-flex items-center gap-1 font-pixel text-xs text-stone-400 hover:text-stone-300"
                >
                    <ChevronLeft className="h-4 w-4" />
                    Back to Titles
                </Link>

                <div className="mx-auto w-full max-w-2xl space-y-6">
                    {/* Title Info */}
                    <div className={`rounded-xl border-2 p-6 ${getCategoryBorder(title_type.category)}`}>
                        <div className="flex items-center gap-4">
                            <div className={`flex h-14 w-14 items-center justify-center rounded-lg ${
                                title_type.category === 'royalty' ? 'bg-purple-900/50' :
                                title_type.category === 'landed_nobility' ? 'bg-amber-900/50' :
                                title_type.category === 'minor_nobility' ? 'bg-blue-900/50' :
                                'bg-stone-800/50'
                            }`}>
                                <Crown className={`h-8 w-8 ${getCategoryColor(title_type.category)}`} />
                            </div>
                            <div className="flex-1">
                                <h1 className={`font-pixel text-2xl ${getCategoryColor(title_type.category)}`}>
                                    {title_type.name}
                                </h1>
                                <div className="flex items-center gap-3 font-pixel text-xs text-stone-500">
                                    <span>{getCategoryLabel(title_type.category)}</span>
                                    <span>Tier {title_type.tier}</span>
                                </div>
                            </div>
                        </div>

                        {title_type.description && (
                            <p className="mt-4 font-pixel text-sm text-stone-400">
                                {title_type.description}
                            </p>
                        )}

                        {title_type.style_of_address && (
                            <div className="mt-4 rounded-lg border border-stone-700 bg-stone-900/50 p-3">
                                <div className="font-pixel text-[10px] text-stone-500">Style of Address</div>
                                <div className="font-pixel text-sm text-stone-300">{title_type.style_of_address}</div>
                            </div>
                        )}

                        <div className="mt-4 flex flex-wrap gap-2">
                            {title_type.requires_ceremony && (
                                <span className="inline-flex items-center gap-1 rounded bg-purple-900/30 px-2 py-1 font-pixel text-[10px] text-purple-400">
                                    <Sparkles className="h-3 w-3" />
                                    Requires Ceremony
                                </span>
                            )}
                            {title_type.can_purchase && title_type.purchase_cost && (
                                <span className="inline-flex items-center gap-1 rounded bg-amber-900/30 px-2 py-1 font-pixel text-[10px] text-amber-400">
                                    <Coins className="h-3 w-3" />
                                    Can Purchase for {title_type.purchase_cost}g
                                </span>
                            )}
                        </div>
                    </div>

                    {/* Requirements */}
                    <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                            <Shield className="h-4 w-4 text-blue-400" />
                            Requirements
                        </h2>
                        <div className="space-y-2">
                            {met_requirements.map((req, i) => (
                                <div key={`met-${i}`} className="flex items-center gap-2">
                                    <Check className="h-4 w-4 text-green-400" />
                                    <span className="font-pixel text-xs text-stone-300">{req}</span>
                                </div>
                            ))}
                            {unmet_requirements.map((req, i) => (
                                <div key={`unmet-${i}`} className="flex items-center gap-2">
                                    <X className="h-4 w-4 text-red-400" />
                                    <span className="font-pixel text-xs text-stone-500">{req}</span>
                                </div>
                            ))}
                        </div>

                        {!meets_requirements && (
                            <div className="mt-4 rounded-lg border border-red-600/50 bg-red-900/20 p-3">
                                <p className="font-pixel text-xs text-red-400">
                                    You do not meet all requirements for this title.
                                </p>
                            </div>
                        )}
                    </div>

                    {/* Petition Form */}
                    {meets_requirements && (
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                <ScrollText className="h-4 w-4 text-amber-400" />
                                Submit Petition
                            </h2>

                            {/* Select Grantor */}
                            <div className="mb-4">
                                <label className="mb-2 block font-pixel text-xs text-stone-400">
                                    Petition To *
                                </label>
                                {potential_grantors.length === 0 ? (
                                    <div className="rounded-lg border border-red-600/50 bg-red-900/20 p-3">
                                        <p className="font-pixel text-xs text-red-400">
                                            No eligible grantors found for this title.
                                        </p>
                                    </div>
                                ) : (
                                    <div className="space-y-2">
                                        {potential_grantors.map((grantor) => (
                                            <button
                                                key={grantor.id}
                                                type="button"
                                                onClick={() => setSelectedGrantor(grantor.id)}
                                                className={`flex w-full items-center gap-3 rounded-lg border p-3 text-left transition ${
                                                    selectedGrantor === grantor.id
                                                        ? 'border-amber-600 bg-amber-900/30'
                                                        : 'border-stone-700 bg-stone-800/30 hover:bg-stone-800/50'
                                                }`}
                                            >
                                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-stone-700">
                                                    <User className="h-4 w-4 text-stone-400" />
                                                </div>
                                                <div className="flex-1">
                                                    <div className="font-pixel text-sm text-stone-200">
                                                        {grantor.username}
                                                    </div>
                                                    {(grantor.title || grantor.domain_name) && (
                                                        <div className="font-pixel text-[10px] text-stone-500">
                                                            {grantor.title}
                                                            {grantor.domain_name && ` of ${grantor.domain_name}`}
                                                        </div>
                                                    )}
                                                </div>
                                                {selectedGrantor === grantor.id && (
                                                    <Check className="h-5 w-5 text-amber-400" />
                                                )}
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* Message */}
                            <div className="mb-4">
                                <label className="mb-2 block font-pixel text-xs text-stone-400">
                                    Petition Message (optional)
                                </label>
                                <textarea
                                    value={message}
                                    onChange={(e) => setMessage(e.target.value)}
                                    maxLength={1000}
                                    rows={3}
                                    placeholder="State your case for why you deserve this title..."
                                    className="w-full rounded-lg border border-stone-600 bg-stone-900 px-3 py-2 font-pixel text-sm text-stone-200 placeholder:text-stone-600"
                                />
                                <div className="mt-1 text-right font-pixel text-[10px] text-stone-600">
                                    {message.length}/1000
                                </div>
                            </div>

                            {/* Purchase Option */}
                            {title_type.can_purchase && title_type.purchase_cost && (
                                <div className="mb-4">
                                    <button
                                        type="button"
                                        onClick={() => setIsPurchase(!isPurchase)}
                                        className={`flex w-full items-center justify-between rounded-lg border p-3 transition ${
                                            isPurchase
                                                ? 'border-amber-600 bg-amber-900/30'
                                                : 'border-stone-700 bg-stone-800/30 hover:bg-stone-800/50'
                                        }`}
                                    >
                                        <div className="flex items-center gap-2">
                                            <Coins className={`h-5 w-5 ${isPurchase ? 'text-amber-400' : 'text-stone-500'}`} />
                                            <div className="text-left">
                                                <div className={`font-pixel text-sm ${isPurchase ? 'text-amber-400' : 'text-stone-300'}`}>
                                                    Purchase this title
                                                </div>
                                                <div className="font-pixel text-[10px] text-stone-500">
                                                    Offer {title_type.purchase_cost}g (you have {user_gold}g)
                                                </div>
                                            </div>
                                        </div>
                                        <div className={`h-5 w-5 rounded border-2 ${
                                            isPurchase
                                                ? 'border-amber-400 bg-amber-400'
                                                : 'border-stone-600'
                                        }`}>
                                            {isPurchase && <Check className="h-4 w-4 text-stone-900" />}
                                        </div>
                                    </button>
                                    {isPurchase && !canAffordPurchase && (
                                        <div className="mt-2 rounded-lg border border-red-600/50 bg-red-900/20 p-2">
                                            <p className="font-pixel text-xs text-red-400">
                                                You don't have enough gold for this purchase.
                                            </p>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Submit Button */}
                            <button
                                onClick={handleSubmit}
                                disabled={!canSubmit || submitting || (isPurchase && !canAffordPurchase)}
                                className="w-full rounded-lg bg-amber-600 py-3 font-pixel text-sm text-white transition hover:bg-amber-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {submitting ? 'Submitting...' : 'Submit Petition'}
                            </button>

                            {title_type.requires_ceremony && (
                                <p className="mt-3 font-pixel text-[10px] text-stone-500 text-center">
                                    Note: If approved, you must attend a ceremony to receive this title.
                                </p>
                            )}
                        </div>
                    )}

                    {/* Cannot Petition */}
                    {!meets_requirements && (
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4 text-center">
                            <p className="font-pixel text-xs text-stone-400">
                                You cannot petition for this title until you meet all requirements.
                            </p>
                            <Link
                                href="/titles"
                                className="mt-3 inline-block rounded-lg bg-stone-700 px-4 py-2 font-pixel text-xs text-stone-300 transition hover:bg-stone-600"
                            >
                                Back to Titles
                            </Link>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

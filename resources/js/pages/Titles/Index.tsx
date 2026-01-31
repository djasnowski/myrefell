import { Head, Link, router } from '@inertiajs/react';
import {
    Crown,
    ScrollText,
    Shield,
    Star,
    Check,
    X,
    ChevronRight,
    Award,
    Coins,
    Users,
    Sparkles,
} from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

// Matches TitleService::getUserTitles flat structure
interface UserTitle {
    id: number;
    name: string;
    tier: number;
    category: string | null;
    style_of_address: string | null;
    domain_type: string | null;
    domain_name: string | null;
    granted_by: string | null;
    granted_at: string | null;
    is_landed: boolean;
}

// Matches TitleService::formatPetition with forPetitioner=true
interface Petition {
    id: number;
    title_name: string;
    title_slug: string;
    status: string;
    petition_message: string | null;
    response_message: string | null;
    is_purchase: boolean;
    gold_offered: number;
    ceremony_required: boolean;
    ceremony_completed: boolean;
    domain_type: string | null;
    domain_name: string | null;
    created_at: string;
    expires_at: string | null;
    responded_at: string | null;
    petition_to: {
        id: number;
        username: string;
        styled_name: string;
    };
}

// Matches TitleService::formatPetition with forPetitioner=false
interface PendingReview {
    id: number;
    title_name: string;
    title_slug: string;
    status: string;
    petition_message: string | null;
    is_purchase: boolean;
    gold_offered: number;
    created_at: string;
    petitioner: {
        id: number;
        username: string;
    };
}

// Same structure as PendingReview for awaiting ceremony
interface AwaitingCeremony {
    id: number;
    title_name: string;
    title_slug: string;
    ceremony_required: boolean;
    ceremony_completed: boolean;
    petitioner: {
        id: number;
        username: string;
    };
}

// Matches TitleService::getTitlesAvailableForPetition
interface AvailableTitle {
    id: number;
    name: string;
    slug: string;
    tier: number;
    category: string;
    description: string | null;
    style_of_address: string | null;
    meets_requirements: boolean;
    unmet_requirements: Record<string, string>;
    can_purchase: boolean;
    purchase_cost: number | null;
    requires_ceremony: boolean;
    domain_type: string | null;
}

interface Props {
    my_titles: UserTitle[];
    my_petitions: Petition[];
    available_titles: AvailableTitle[];
    pending_to_review: PendingReview[];
    awaiting_ceremony: AwaitingCeremony[];
    styled_name: string;
}

export default function TitlesIndex({
    my_titles,
    my_petitions,
    available_titles,
    pending_to_review,
    awaiting_ceremony,
    styled_name,
}: Props) {
    const [withdrawing, setWithdrawing] = useState<number | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Titles', href: '/titles' },
    ];

    const handleWithdraw = (petitionId: number) => {
        setWithdrawing(petitionId);
        router.post(`/titles/petition/${petitionId}/withdraw`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                router.reload();
            },
            onFinish: () => setWithdrawing(null),
        });
    };

    const getCategoryColor = (category: string | null) => {
        switch (category) {
            case 'royalty': return 'text-purple-400';
            case 'landed_nobility': return 'text-amber-400';
            case 'minor_nobility': return 'text-blue-400';
            default: return 'text-stone-400';
        }
    };

    const getCategoryBorder = (category: string | null) => {
        switch (category) {
            case 'royalty': return 'border-purple-600/50 bg-purple-900/20';
            case 'landed_nobility': return 'border-amber-600/50 bg-amber-900/20';
            case 'minor_nobility': return 'border-blue-600/50 bg-blue-900/20';
            default: return 'border-stone-700 bg-stone-800/30';
        }
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'pending': return 'text-amber-400 bg-amber-900/20 border-amber-600/50';
            case 'approved': return 'text-green-400 bg-green-900/20 border-green-600/50';
            case 'denied': return 'text-red-400 bg-red-900/20 border-red-600/50';
            case 'withdrawn': return 'text-stone-400 bg-stone-800/50 border-stone-600/50';
            case 'expired': return 'text-stone-500 bg-stone-900/20 border-stone-700/50';
            case 'ceremony_pending': return 'text-purple-400 bg-purple-900/20 border-purple-600/50';
            default: return 'text-stone-400 bg-stone-800/50 border-stone-600/50';
        }
    };

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'pending': return 'Awaiting Response';
            case 'approved': return 'Approved';
            case 'denied': return 'Denied';
            case 'withdrawn': return 'Withdrawn';
            case 'expired': return 'Expired';
            case 'ceremony_pending': return 'Awaiting Ceremony';
            default: return status;
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Noble Titles" />
            <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                {/* Header */}
                <div className="mb-6 flex items-center gap-4">
                    <div className="flex h-16 w-16 items-center justify-center rounded-lg bg-amber-900/30">
                        <Crown className="h-10 w-10 text-amber-400" />
                    </div>
                    <div className="flex-1">
                        <h1 className="font-pixel text-2xl text-amber-400">Noble Titles</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            You are known as: <span className="text-stone-200">{styled_name}</span>
                        </p>
                    </div>
                </div>

                <div className="mx-auto w-full max-w-5xl space-y-6">
                    {/* Pending Reviews Alert */}
                    {pending_to_review.length > 0 && (
                        <div className="rounded-xl border-2 border-amber-600/50 bg-amber-900/20 p-4">
                            <div className="mb-3 flex items-center gap-2">
                                <ScrollText className="h-5 w-5 text-amber-400" />
                                <h2 className="font-pixel text-sm text-amber-400">
                                    Petitions Awaiting Your Review ({pending_to_review.length})
                                </h2>
                            </div>
                            <div className="space-y-2">
                                {pending_to_review.map((petition) => (
                                    <Link
                                        key={petition.id}
                                        href={`/titles/review/${petition.id}`}
                                        className="flex items-center justify-between rounded-lg border border-amber-600/30 bg-amber-900/30 p-3 transition hover:bg-amber-900/50"
                                    >
                                        <div>
                                            <span className="font-pixel text-xs text-stone-200">
                                                {petition.petitioner.username}
                                            </span>
                                            <span className="font-pixel text-xs text-stone-500"> petitions for </span>
                                            <span className="font-pixel text-xs text-amber-400">
                                                {petition.title_name}
                                            </span>
                                            {petition.is_purchase && (
                                                <span className="ml-2 inline-flex items-center gap-1 font-pixel text-[10px] text-amber-300">
                                                    <Coins className="h-3 w-3" />
                                                    {petition.gold_offered}g offered
                                                </span>
                                            )}
                                        </div>
                                        <ChevronRight className="h-4 w-4 text-amber-400" />
                                    </Link>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Awaiting Ceremony Alert */}
                    {awaiting_ceremony.length > 0 && (
                        <div className="rounded-xl border-2 border-purple-600/50 bg-purple-900/20 p-4">
                            <div className="mb-3 flex items-center gap-2">
                                <Sparkles className="h-5 w-5 text-purple-400" />
                                <h2 className="font-pixel text-sm text-purple-400">
                                    Ceremonies Awaiting ({awaiting_ceremony.length})
                                </h2>
                            </div>
                            <div className="space-y-2">
                                {awaiting_ceremony.map((petition) => (
                                    <Link
                                        key={petition.id}
                                        href={`/titles/ceremony/${petition.id}`}
                                        className="flex items-center justify-between rounded-lg border border-purple-600/30 bg-purple-900/30 p-3 transition hover:bg-purple-900/50"
                                    >
                                        <div>
                                            <span className="font-pixel text-xs text-stone-200">
                                                {petition.petitioner.username}
                                            </span>
                                            <span className="font-pixel text-xs text-stone-500"> to receive </span>
                                            <span className="font-pixel text-xs text-purple-400">
                                                {petition.title_name}
                                            </span>
                                        </div>
                                        <ChevronRight className="h-4 w-4 text-purple-400" />
                                    </Link>
                                ))}
                            </div>
                        </div>
                    )}

                    <div className="grid gap-6 lg:grid-cols-2">
                        {/* My Titles */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                <Award className="h-4 w-4 text-amber-400" />
                                My Titles ({my_titles.length})
                            </h2>
                            {my_titles.length === 0 ? (
                                <div className="py-8 text-center">
                                    <Shield className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                    <div className="font-pixel text-xs text-stone-500">You hold no titles</div>
                                </div>
                            ) : (
                                <div className="space-y-2">
                                    {my_titles.map((title) => (
                                        <div
                                            key={title.id}
                                            className={`rounded-lg border p-3 ${getCategoryBorder(title.category)}`}
                                        >
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    <Crown className={`h-4 w-4 ${getCategoryColor(title.category)}`} />
                                                    <span className={`font-pixel text-sm ${getCategoryColor(title.category)}`}>
                                                        {title.name}
                                                    </span>
                                                </div>
                                                <span className="font-pixel text-[10px] text-stone-500">
                                                    Tier {title.tier}
                                                </span>
                                            </div>
                                            {title.domain_name && (
                                                <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                                    of {title.domain_name}
                                                </div>
                                            )}
                                            <div className="mt-2 flex items-center gap-3 font-pixel text-[10px] text-stone-500">
                                                <span>Granted: {title.granted_at}</span>
                                                {title.granted_by && (
                                                    <span>by {title.granted_by}</span>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        {/* My Petitions */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                <ScrollText className="h-4 w-4 text-blue-400" />
                                My Petitions ({my_petitions.length})
                            </h2>
                            {my_petitions.length === 0 ? (
                                <div className="py-8 text-center">
                                    <ScrollText className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                    <div className="font-pixel text-xs text-stone-500">No active petitions</div>
                                </div>
                            ) : (
                                <div className="space-y-2">
                                    {my_petitions.map((petition) => (
                                        <div
                                            key={petition.id}
                                            className="rounded-lg border border-stone-700 bg-stone-800/30 p-3"
                                        >
                                            <div className="flex items-center justify-between">
                                                <span className="font-pixel text-sm text-stone-200">
                                                    {petition.title_name}
                                                </span>
                                                <span className={`rounded border px-2 py-0.5 font-pixel text-[10px] ${getStatusColor(petition.status)}`}>
                                                    {getStatusLabel(petition.status)}
                                                </span>
                                            </div>
                                            <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                                To: {petition.petition_to.username}
                                            </div>
                                            {petition.is_purchase && (
                                                <div className="mt-1 flex items-center gap-1 font-pixel text-[10px] text-amber-400">
                                                    <Coins className="h-3 w-3" />
                                                    {petition.gold_offered}g offered
                                                </div>
                                            )}
                                            <div className="mt-2 flex items-center justify-between">
                                                <span className="font-pixel text-[10px] text-stone-600">
                                                    Submitted: {petition.created_at}
                                                </span>
                                                {petition.status === 'pending' && (
                                                    <button
                                                        onClick={() => handleWithdraw(petition.id)}
                                                        disabled={withdrawing === petition.id}
                                                        className="font-pixel text-[10px] text-red-400 hover:text-red-300 disabled:opacity-50"
                                                    >
                                                        {withdrawing === petition.id ? 'Withdrawing...' : 'Withdraw'}
                                                    </button>
                                                )}
                                                {petition.status === 'ceremony_pending' && (
                                                    <Link
                                                        href={`/titles/ceremony/${petition.id}`}
                                                        className="font-pixel text-[10px] text-purple-400 hover:text-purple-300"
                                                    >
                                                        View Ceremony
                                                    </Link>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Available Titles */}
                    <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                            <Users className="h-4 w-4 text-green-400" />
                            Titles Available for Petition
                        </h2>
                        {available_titles.length === 0 ? (
                            <div className="py-8 text-center">
                                <Crown className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                <div className="font-pixel text-xs text-stone-500">
                                    No titles available for petition at this time
                                </div>
                            </div>
                        ) : (
                            <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                                {available_titles.map((title) => (
                                    <div
                                        key={title.id}
                                        className={`rounded-lg border p-3 ${getCategoryBorder(title.category)}`}
                                    >
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <Crown className={`h-4 w-4 ${getCategoryColor(title.category)}`} />
                                                <span className={`font-pixel text-sm ${getCategoryColor(title.category)}`}>
                                                    {title.name}
                                                </span>
                                            </div>
                                            <span className="font-pixel text-[10px] text-stone-500">
                                                Tier {title.tier}
                                            </span>
                                        </div>
                                        {title.description && (
                                            <p className="mt-2 font-pixel text-[10px] text-stone-500 line-clamp-2">
                                                {title.description}
                                            </p>
                                        )}
                                        <div className="mt-2 flex items-center gap-2">
                                            {title.can_purchase && title.purchase_cost && (
                                                <span className="flex items-center gap-1 rounded bg-amber-900/30 px-1.5 py-0.5 font-pixel text-[10px] text-amber-400">
                                                    <Coins className="h-3 w-3" />
                                                    {title.purchase_cost}g
                                                </span>
                                            )}
                                            {title.requires_ceremony && (
                                                <span className="flex items-center gap-1 rounded bg-purple-900/30 px-1.5 py-0.5 font-pixel text-[10px] text-purple-400">
                                                    <Star className="h-3 w-3" />
                                                    Ceremony
                                                </span>
                                            )}
                                        </div>
                                        <div className="mt-3 flex items-center justify-between">
                                            <div className="flex items-center gap-1">
                                                {title.meets_requirements ? (
                                                    <>
                                                        <Check className="h-3 w-3 text-green-400" />
                                                        <span className="font-pixel text-[10px] text-green-400">Eligible</span>
                                                    </>
                                                ) : (
                                                    <>
                                                        <X className="h-3 w-3 text-red-400" />
                                                        <span className="font-pixel text-[10px] text-red-400">Not Eligible</span>
                                                    </>
                                                )}
                                            </div>
                                            <Link
                                                href={`/titles/petition/${title.slug}`}
                                                className={`rounded px-2 py-1 font-pixel text-[10px] transition ${
                                                    title.meets_requirements
                                                        ? 'bg-amber-600 text-white hover:bg-amber-500'
                                                        : 'bg-stone-700 text-stone-400 hover:bg-stone-600'
                                                }`}
                                            >
                                                View Details
                                            </Link>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

import { Head, Link, router } from '@inertiajs/react';
import {
    Crown,
    ChevronLeft,
    User,
    Sparkles,
    Star,
    Shield,
} from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface TitleType {
    id: number;
    name: string;
    style_of_address: string | null;
    female_variant: string | null;
}

interface Petitioner {
    id: number;
    username: string;
}

interface ApprovedBy {
    id: number;
    username: string;
    styled_name: string;
}

interface Petition {
    id: number;
    petitioner: Petitioner;
    title_type: TitleType;
    approved_by: ApprovedBy;
    ceremony_scheduled_at: string | null;
}

interface Props {
    petition: Petition;
    can_officiate: boolean;
}

export default function Ceremony({ petition, can_officiate }: Props) {
    const [completing, setCompleting] = useState(false);
    const [ceremonyStep, setCeremonyStep] = useState(0);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Titles', href: '/titles' },
        { title: 'Ceremony', href: '#' },
    ];

    const handleCompleteCeremony = () => {
        if (completing) return;
        setCompleting(true);
        router.post(`/titles/ceremony/${petition.id}/complete`, {}, {
            onSuccess: () => {
                router.reload();
            },
            onFinish: () => setCompleting(false),
        });
    };

    const ceremonySteps = [
        {
            title: 'The Gathering',
            description: `${petition.petitioner.username} kneels before ${petition.approved_by.styled_name}, ready to receive the title of ${petition.title_type.name}.`,
        },
        {
            title: 'The Oath',
            description: `"Do you, ${petition.petitioner.username}, swear to uphold the duties and responsibilities of ${petition.title_type.name}?"`,
        },
        {
            title: 'The Acceptance',
            description: `"I do solemnly swear."`,
        },
        {
            title: 'The Investiture',
            description: `${petition.approved_by.styled_name} places their hands upon ${petition.petitioner.username}'s shoulders.`,
        },
        {
            title: 'The Proclamation',
            description: `"Rise, ${petition.title_type.style_of_address || petition.title_type.name} ${petition.petitioner.username}. May you serve with honor."`,
        },
    ];

    const advanceCeremony = () => {
        if (ceremonyStep < ceremonySteps.length - 1) {
            setCeremonyStep(ceremonyStep + 1);
        }
    };

    const isLastStep = ceremonyStep === ceremonySteps.length - 1;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Title Ceremony" />
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
                    {/* Header */}
                    <div className="text-center">
                        <div className="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-purple-900/30">
                            <Sparkles className="h-12 w-12 text-purple-400" />
                        </div>
                        <h1 className="font-pixel text-2xl text-purple-400">Title Ceremony</h1>
                        <p className="mt-2 font-pixel text-sm text-stone-400">
                            The Investiture of {petition.title_type.name}
                        </p>
                    </div>

                    {/* Participants */}
                    <div className="grid gap-4 md:grid-cols-2">
                        {/* Petitioner */}
                        <div className="rounded-xl border-2 border-blue-600/50 bg-blue-900/20 p-4">
                            <div className="flex items-center gap-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-blue-900/50">
                                    <User className="h-6 w-6 text-blue-400" />
                                </div>
                                <div>
                                    <div className="font-pixel text-[10px] text-stone-500">Recipient</div>
                                    <div className="font-pixel text-lg text-blue-400">
                                        {petition.petitioner.username}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Approved By */}
                        <div className="rounded-xl border-2 border-amber-600/50 bg-amber-900/20 p-4">
                            <div className="flex items-center gap-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-amber-900/50">
                                    <Crown className="h-6 w-6 text-amber-400" />
                                </div>
                                <div>
                                    <div className="font-pixel text-[10px] text-stone-500">Officiant</div>
                                    <div className="font-pixel text-lg text-amber-400">
                                        {petition.approved_by.styled_name}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Title Being Granted */}
                    <div className="rounded-xl border-2 border-purple-600/50 bg-purple-900/20 p-4 text-center">
                        <div className="font-pixel text-[10px] text-stone-500">Title to be Conferred</div>
                        <div className="mt-1 flex items-center justify-center gap-2">
                            <Crown className="h-6 w-6 text-purple-400" />
                            <span className="font-pixel text-xl text-purple-400">
                                {petition.title_type.name}
                            </span>
                        </div>
                        {petition.title_type.style_of_address && (
                            <div className="mt-2 font-pixel text-xs text-stone-500">
                                Style: {petition.title_type.style_of_address}
                            </div>
                        )}
                    </div>

                    {/* Ceremony Progress */}
                    {can_officiate ? (
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-6">
                            {/* Progress Dots */}
                            <div className="mb-6 flex justify-center gap-2">
                                {ceremonySteps.map((_, index) => (
                                    <div
                                        key={index}
                                        className={`h-2 w-2 rounded-full transition-all ${
                                            index <= ceremonyStep
                                                ? 'bg-purple-400'
                                                : 'bg-stone-600'
                                        }`}
                                    />
                                ))}
                            </div>

                            {/* Current Step */}
                            <div className="mb-6 text-center">
                                <div className="font-pixel text-xs text-purple-400">
                                    {ceremonySteps[ceremonyStep].title}
                                </div>
                                <div className="mt-4 rounded-lg border border-stone-700 bg-stone-900/50 p-4">
                                    <p className="font-pixel text-sm italic text-stone-300">
                                        {ceremonySteps[ceremonyStep].description}
                                    </p>
                                </div>
                            </div>

                            {/* Action Buttons */}
                            {isLastStep ? (
                                <button
                                    onClick={handleCompleteCeremony}
                                    disabled={completing}
                                    className="w-full rounded-lg bg-purple-600 py-3 font-pixel text-sm text-white transition hover:bg-purple-500 disabled:opacity-50"
                                >
                                    <span className="flex items-center justify-center gap-2">
                                        <Star className="h-4 w-4" />
                                        {completing ? 'Completing Ceremony...' : 'Complete Ceremony & Grant Title'}
                                    </span>
                                </button>
                            ) : (
                                <button
                                    onClick={advanceCeremony}
                                    className="w-full rounded-lg bg-stone-700 py-3 font-pixel text-sm text-stone-200 transition hover:bg-stone-600"
                                >
                                    Continue
                                </button>
                            )}
                        </div>
                    ) : (
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-6">
                            <div className="text-center">
                                <Shield className="mx-auto mb-4 h-12 w-12 text-stone-600" />
                                <h2 className="font-pixel text-sm text-stone-300">Awaiting Ceremony</h2>
                                <p className="mt-2 font-pixel text-xs text-stone-500">
                                    Only {petition.approved_by.styled_name} or a noble of equal or higher rank can officiate this ceremony.
                                </p>
                                {petition.ceremony_scheduled_at && (
                                    <div className="mt-4 rounded-lg border border-purple-600/50 bg-purple-900/20 p-3">
                                        <div className="font-pixel text-[10px] text-stone-500">Scheduled For</div>
                                        <div className="font-pixel text-sm text-purple-400">
                                            {petition.ceremony_scheduled_at}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Ceremony Info */}
                    <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-4">
                        <h3 className="mb-2 font-pixel text-xs text-stone-400">About Ceremonies</h3>
                        <p className="font-pixel text-[10px] text-stone-500">
                            Title ceremonies are formal occasions where a noble is invested with their new rank.
                            The ceremony must be officiated by the approving noble or someone of equal or higher standing.
                            Upon completion, the title is officially conferred and recognized throughout the realm.
                        </p>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

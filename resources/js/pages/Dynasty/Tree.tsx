import { Head, Link } from '@inertiajs/react';
import {
    ChevronLeft,
    Crown,
    Filter,
    Heart,
    Minus,
    Plus,
    Skull,
    Star,
    User,
    Users,
    ZoomIn,
    ZoomOut,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface TreeMember {
    id: number;
    name: string;
    first_name: string;
    gender: string;
    generation: number;
    birth_order: number;
    father_id: number | null;
    mother_id: number | null;
    status: string;
    is_alive: boolean;
    is_heir: boolean;
    is_head: boolean;
    is_player: boolean;
    is_legitimate: boolean;
    is_disinherited: boolean;
    birth_year: string | null;
    death_year: string | null;
    age: number | null;
    user: { id: number; name: string } | null;
}

interface TreeMarriage {
    id: number;
    spouse1_id: number;
    spouse2_id: number;
    status: string;
    wedding_date: string | null;
}

interface Dynasty {
    id: number;
    name: string;
    motto: string | null;
    prestige: number;
    generations: number;
    head_id: number | null;
}

interface Props {
    dynasty: Dynasty;
    members: TreeMember[];
    marriages: TreeMarriage[];
    player_member_id: number | null;
}

export default function DynastyTree({
    dynasty,
    members,
    marriages,
    player_member_id,
}: Props) {
    const [showDeceased, setShowDeceased] = useState(true);
    const [zoomLevel, setZoomLevel] = useState(1);
    const [selectedMember, setSelectedMember] = useState<TreeMember | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Dynasty', href: '/dynasty' },
        { title: 'Family Tree', href: '/dynasty/tree' },
    ];

    // Filter members based on show deceased setting
    const filteredMembers = useMemo(() => {
        if (showDeceased) return members;
        return members.filter((m) => m.is_alive);
    }, [members, showDeceased]);

    // Group members by generation
    const membersByGeneration = useMemo(() => {
        const grouped: Record<number, TreeMember[]> = {};
        filteredMembers.forEach((member) => {
            if (!grouped[member.generation]) {
                grouped[member.generation] = [];
            }
            grouped[member.generation].push(member);
        });
        // Sort each generation by birth order
        Object.keys(grouped).forEach((gen) => {
            grouped[Number(gen)].sort((a, b) => a.birth_order - b.birth_order);
        });
        return grouped;
    }, [filteredMembers]);

    const generations = useMemo(() => {
        return Object.keys(membersByGeneration)
            .map(Number)
            .sort((a, b) => a - b);
    }, [membersByGeneration]);

    // Find spouse for a member
    const getSpouse = (memberId: number): TreeMember | null => {
        const marriage = marriages.find(
            (m) =>
                (m.spouse1_id === memberId || m.spouse2_id === memberId) &&
                m.status === 'active'
        );
        if (!marriage) return null;
        const spouseId =
            marriage.spouse1_id === memberId ? marriage.spouse2_id : marriage.spouse1_id;
        return members.find((m) => m.id === spouseId) || null;
    };

    // Find marriage between two members
    const getMarriage = (member1Id: number, member2Id: number): TreeMarriage | null => {
        return (
            marriages.find(
                (m) =>
                    (m.spouse1_id === member1Id && m.spouse2_id === member2Id) ||
                    (m.spouse1_id === member2Id && m.spouse2_id === member1Id)
            ) || null
        );
    };

    // Get children of a member
    const getChildren = (memberId: number): TreeMember[] => {
        return filteredMembers.filter(
            (m) => m.father_id === memberId || m.mother_id === memberId
        );
    };

    const handleZoomIn = () => {
        setZoomLevel((prev) => Math.min(prev + 0.1, 1.5));
    };

    const handleZoomOut = () => {
        setZoomLevel((prev) => Math.max(prev - 0.1, 0.5));
    };

    const MemberCard = ({ member }: { member: TreeMember }) => {
        const spouse = getSpouse(member.id);
        const isSelected = selectedMember?.id === member.id;
        const isPlayer = member.id === player_member_id;

        return (
            <div className="flex flex-col items-center">
                <div className="flex items-center gap-1">
                    {/* Main member card */}
                    <button
                        onClick={() => setSelectedMember(isSelected ? null : member)}
                        className={`flex min-w-[100px] flex-col items-center rounded-lg border-2 p-2 transition ${
                            member.is_head
                                ? 'border-amber-500/70 bg-amber-900/30 hover:bg-amber-900/40'
                                : member.is_heir
                                    ? 'border-purple-500/70 bg-purple-900/30 hover:bg-purple-900/40'
                                    : isPlayer
                                        ? 'border-blue-500/70 bg-blue-900/30 hover:bg-blue-900/40'
                                        : member.is_alive
                                            ? 'border-stone-600 bg-stone-800/50 hover:bg-stone-800/70'
                                            : 'border-stone-700/50 bg-stone-900/50 hover:bg-stone-900/70'
                        } ${isSelected ? 'ring-2 ring-amber-400' : ''}`}
                    >
                        {/* Avatar */}
                        <div
                            className={`flex h-10 w-10 items-center justify-center rounded-full ${
                                !member.is_alive
                                    ? 'bg-stone-700/50'
                                    : member.gender === 'male'
                                        ? 'bg-blue-900/50'
                                        : 'bg-pink-900/50'
                            }`}
                        >
                            {!member.is_alive ? (
                                <Skull className="h-5 w-5 text-stone-500" />
                            ) : (
                                <User
                                    className={`h-5 w-5 ${
                                        member.gender === 'male'
                                            ? 'text-blue-400'
                                            : 'text-pink-400'
                                    }`}
                                />
                            )}
                        </div>

                        {/* Name */}
                        <div className="mt-1 flex items-center gap-1">
                            <span
                                className={`font-pixel text-[10px] ${
                                    member.is_alive ? 'text-stone-200' : 'text-stone-500'
                                }`}
                            >
                                {member.first_name}
                            </span>
                            {member.is_head && (
                                <Crown className="h-3 w-3 text-amber-400" />
                            )}
                            {member.is_heir && !member.is_head && (
                                <Star className="h-3 w-3 text-purple-400" />
                            )}
                        </div>

                        {/* Life years */}
                        <div className="font-pixel text-[8px] text-stone-500">
                            {member.birth_year || '?'}
                            {member.death_year ? ` - ${member.death_year}` : ''}
                        </div>
                    </button>

                    {/* Marriage connector and spouse */}
                    {spouse && showDeceased || (spouse && spouse.is_alive) ? (
                        <>
                            <div className="flex items-center">
                                <div className="h-px w-2 bg-pink-500/50" />
                                <Heart className="h-3 w-3 text-pink-400" />
                                <div className="h-px w-2 bg-pink-500/50" />
                            </div>
                            <button
                                onClick={() =>
                                    setSelectedMember(
                                        selectedMember?.id === spouse.id ? null : spouse
                                    )
                                }
                                className={`flex min-w-[100px] flex-col items-center rounded-lg border-2 p-2 transition ${
                                    spouse.is_head
                                        ? 'border-amber-500/70 bg-amber-900/30 hover:bg-amber-900/40'
                                        : spouse.is_heir
                                            ? 'border-purple-500/70 bg-purple-900/30 hover:bg-purple-900/40'
                                            : spouse.is_alive
                                                ? 'border-stone-600 bg-stone-800/50 hover:bg-stone-800/70'
                                                : 'border-stone-700/50 bg-stone-900/50 hover:bg-stone-900/70'
                                } ${selectedMember?.id === spouse.id ? 'ring-2 ring-amber-400' : ''}`}
                            >
                                <div
                                    className={`flex h-10 w-10 items-center justify-center rounded-full ${
                                        !spouse.is_alive
                                            ? 'bg-stone-700/50'
                                            : spouse.gender === 'male'
                                                ? 'bg-blue-900/50'
                                                : 'bg-pink-900/50'
                                    }`}
                                >
                                    {!spouse.is_alive ? (
                                        <Skull className="h-5 w-5 text-stone-500" />
                                    ) : (
                                        <User
                                            className={`h-5 w-5 ${
                                                spouse.gender === 'male'
                                                    ? 'text-blue-400'
                                                    : 'text-pink-400'
                                            }`}
                                        />
                                    )}
                                </div>
                                <div className="mt-1 flex items-center gap-1">
                                    <span
                                        className={`font-pixel text-[10px] ${
                                            spouse.is_alive
                                                ? 'text-stone-200'
                                                : 'text-stone-500'
                                        }`}
                                    >
                                        {spouse.first_name}
                                    </span>
                                    {spouse.is_head && (
                                        <Crown className="h-3 w-3 text-amber-400" />
                                    )}
                                    {spouse.is_heir && !spouse.is_head && (
                                        <Star className="h-3 w-3 text-purple-400" />
                                    )}
                                </div>
                                <div className="font-pixel text-[8px] text-stone-500">
                                    {spouse.birth_year || '?'}
                                    {spouse.death_year ? ` - ${spouse.death_year}` : ''}
                                </div>
                            </button>
                        </>
                    ) : null}
                </div>
            </div>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Family Tree - House ${dynasty.name}`} />
            <div className="flex h-full flex-1 flex-col overflow-hidden">
                {/* Header */}
                <div className="flex items-center justify-between border-b border-stone-700 bg-stone-800/50 p-4">
                    <div className="flex items-center gap-3">
                        <Link
                            href="/dynasty"
                            className="flex items-center gap-1 rounded-lg border border-stone-600 bg-stone-800 px-2 py-1 font-pixel text-xs text-stone-400 transition hover:bg-stone-700"
                        >
                            <ChevronLeft className="h-4 w-4" />
                            Back
                        </Link>
                        <div>
                            <h1 className="font-pixel text-lg text-amber-400">
                                Family Tree - House {dynasty.name}
                            </h1>
                            <p className="font-pixel text-[10px] text-stone-500">
                                {dynasty.generations} generations | {members.length} members
                            </p>
                        </div>
                    </div>

                    {/* Controls */}
                    <div className="flex items-center gap-2">
                        {/* Filter */}
                        <button
                            onClick={() => setShowDeceased(!showDeceased)}
                            className={`flex items-center gap-1 rounded-lg border px-2 py-1 font-pixel text-xs transition ${
                                showDeceased
                                    ? 'border-green-600/50 bg-green-900/30 text-green-400'
                                    : 'border-stone-600 bg-stone-800 text-stone-400'
                            }`}
                        >
                            <Filter className="h-3 w-3" />
                            {showDeceased ? 'All' : 'Living'}
                        </button>

                        {/* Zoom controls */}
                        <div className="flex items-center gap-1 rounded-lg border border-stone-600 bg-stone-800 px-1">
                            <button
                                onClick={handleZoomOut}
                                className="p-1 text-stone-400 transition hover:text-stone-200"
                                disabled={zoomLevel <= 0.5}
                            >
                                <ZoomOut className="h-4 w-4" />
                            </button>
                            <span className="font-pixel text-[10px] text-stone-400 w-10 text-center">
                                {Math.round(zoomLevel * 100)}%
                            </span>
                            <button
                                onClick={handleZoomIn}
                                className="p-1 text-stone-400 transition hover:text-stone-200"
                                disabled={zoomLevel >= 1.5}
                            >
                                <ZoomIn className="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                {/* Tree container */}
                <div className="flex-1 overflow-auto p-4">
                    <div
                        className="min-w-max transition-transform duration-200"
                        style={{ transform: `scale(${zoomLevel})`, transformOrigin: 'top center' }}
                    >
                        {/* Legend */}
                        <div className="mb-6 flex flex-wrap items-center justify-center gap-4 rounded-lg border border-stone-700 bg-stone-800/30 p-3">
                            <div className="flex items-center gap-2">
                                <Crown className="h-4 w-4 text-amber-400" />
                                <span className="font-pixel text-[10px] text-stone-400">Head</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Star className="h-4 w-4 text-purple-400" />
                                <span className="font-pixel text-[10px] text-stone-400">Heir</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Skull className="h-4 w-4 text-stone-500" />
                                <span className="font-pixel text-[10px] text-stone-400">Deceased</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Heart className="h-4 w-4 text-pink-400" />
                                <span className="font-pixel text-[10px] text-stone-400">Marriage</span>
                            </div>
                        </div>

                        {/* Generations */}
                        <div className="space-y-8">
                            {generations.map((gen) => {
                                // Group members by family unit (parents together)
                                const genMembers = membersByGeneration[gen] || [];

                                // Track which members we've already shown (as spouses)
                                const shownAsSpouse = new Set<number>();
                                marriages.forEach((m) => {
                                    const spouse1 = members.find((mem) => mem.id === m.spouse1_id);
                                    const spouse2 = members.find((mem) => mem.id === m.spouse2_id);
                                    if (spouse1 && spouse2 && spouse1.generation === gen && spouse2.generation === gen) {
                                        // The spouse with higher ID is shown as the spouse
                                        shownAsSpouse.add(Math.max(m.spouse1_id, m.spouse2_id));
                                    }
                                });

                                // Filter out members who are shown as spouses
                                const primaryMembers = genMembers.filter(
                                    (m) => !shownAsSpouse.has(m.id)
                                );

                                return (
                                    <div key={gen}>
                                        {/* Generation label */}
                                        <div className="mb-3 text-center">
                                            <span className="rounded-full bg-stone-800 px-3 py-1 font-pixel text-xs text-stone-400">
                                                Generation {gen}
                                            </span>
                                        </div>

                                        {/* Members row */}
                                        <div className="flex flex-wrap items-start justify-center gap-6">
                                            {primaryMembers.map((member) => (
                                                <div key={member.id} className="flex flex-col items-center">
                                                    <MemberCard member={member} />

                                                    {/* Children connector */}
                                                    {getChildren(member.id).length > 0 && (
                                                        <div className="mt-2 flex flex-col items-center">
                                                            <div className="h-4 w-px bg-stone-600" />
                                                        </div>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        {/* Empty state */}
                        {filteredMembers.length === 0 && (
                            <div className="py-16 text-center">
                                <Users className="mx-auto mb-4 h-12 w-12 text-stone-600" />
                                <p className="font-pixel text-sm text-stone-500">
                                    No members to display
                                </p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Member detail panel */}
                {selectedMember && (
                    <div className="border-t border-stone-700 bg-stone-800/80 p-4">
                        <div className="w-full">
                            <div className="flex items-start justify-between">
                                <div className="flex items-center gap-4">
                                    <div
                                        className={`flex h-14 w-14 items-center justify-center rounded-full ${
                                            !selectedMember.is_alive
                                                ? 'bg-stone-700/50'
                                                : selectedMember.gender === 'male'
                                                    ? 'bg-blue-900/50'
                                                    : 'bg-pink-900/50'
                                        }`}
                                    >
                                        {!selectedMember.is_alive ? (
                                            <Skull className="h-7 w-7 text-stone-500" />
                                        ) : (
                                            <User
                                                className={`h-7 w-7 ${
                                                    selectedMember.gender === 'male'
                                                        ? 'text-blue-400'
                                                        : 'text-pink-400'
                                                }`}
                                            />
                                        )}
                                    </div>
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <h3 className="font-pixel text-lg text-stone-200">
                                                {selectedMember.name}
                                            </h3>
                                            {selectedMember.is_head && (
                                                <span className="rounded bg-amber-900/50 px-2 py-0.5 font-pixel text-[10px] text-amber-400">
                                                    Head of House
                                                </span>
                                            )}
                                            {selectedMember.is_heir && !selectedMember.is_head && (
                                                <span className="rounded bg-purple-900/50 px-2 py-0.5 font-pixel text-[10px] text-purple-400">
                                                    Heir
                                                </span>
                                            )}
                                            {selectedMember.is_disinherited && (
                                                <span className="rounded bg-red-900/50 px-2 py-0.5 font-pixel text-[10px] text-red-400">
                                                    Disinherited
                                                </span>
                                            )}
                                            {!selectedMember.is_legitimate && (
                                                <span className="rounded bg-stone-700/50 px-2 py-0.5 font-pixel text-[10px] text-stone-400">
                                                    Illegitimate
                                                </span>
                                            )}
                                        </div>
                                        <div className="mt-1 flex items-center gap-3 font-pixel text-xs text-stone-500">
                                            <span>
                                                {selectedMember.gender === 'male' ? 'Male' : 'Female'}
                                            </span>
                                            <span>Generation {selectedMember.generation}</span>
                                            {selectedMember.birth_year && (
                                                <span>
                                                    Born {selectedMember.birth_year}
                                                    {selectedMember.death_year &&
                                                        ` - Died ${selectedMember.death_year}`}
                                                </span>
                                            )}
                                            {selectedMember.age !== null && selectedMember.is_alive && (
                                                <span>Age {selectedMember.age}</span>
                                            )}
                                        </div>
                                    </div>
                                </div>
                                <button
                                    onClick={() => setSelectedMember(null)}
                                    className="rounded p-1 text-stone-500 transition hover:bg-stone-700 hover:text-stone-300"
                                >
                                    <Minus className="h-4 w-4" />
                                </button>
                            </div>

                            {/* Family connections */}
                            <div className="mt-4 grid grid-cols-3 gap-4">
                                {/* Parents */}
                                <div>
                                    <h4 className="mb-2 font-pixel text-[10px] text-stone-500">
                                        Parents
                                    </h4>
                                    {selectedMember.father_id || selectedMember.mother_id ? (
                                        <div className="space-y-1">
                                            {selectedMember.father_id && (
                                                <button
                                                    onClick={() => {
                                                        const father = members.find(
                                                            (m) => m.id === selectedMember.father_id
                                                        );
                                                        if (father) setSelectedMember(father);
                                                    }}
                                                    className="flex w-full items-center gap-2 rounded border border-stone-700 bg-stone-800/50 px-2 py-1 text-left transition hover:bg-stone-700"
                                                >
                                                    <User className="h-3 w-3 text-blue-400" />
                                                    <span className="font-pixel text-[10px] text-stone-300">
                                                        {members.find(
                                                            (m) => m.id === selectedMember.father_id
                                                        )?.first_name || 'Unknown'}
                                                    </span>
                                                    <span className="font-pixel text-[8px] text-stone-500">
                                                        (Father)
                                                    </span>
                                                </button>
                                            )}
                                            {selectedMember.mother_id && (
                                                <button
                                                    onClick={() => {
                                                        const mother = members.find(
                                                            (m) => m.id === selectedMember.mother_id
                                                        );
                                                        if (mother) setSelectedMember(mother);
                                                    }}
                                                    className="flex w-full items-center gap-2 rounded border border-stone-700 bg-stone-800/50 px-2 py-1 text-left transition hover:bg-stone-700"
                                                >
                                                    <User className="h-3 w-3 text-pink-400" />
                                                    <span className="font-pixel text-[10px] text-stone-300">
                                                        {members.find(
                                                            (m) => m.id === selectedMember.mother_id
                                                        )?.first_name || 'Unknown'}
                                                    </span>
                                                    <span className="font-pixel text-[8px] text-stone-500">
                                                        (Mother)
                                                    </span>
                                                </button>
                                            )}
                                        </div>
                                    ) : (
                                        <p className="font-pixel text-[10px] text-stone-600">
                                            Unknown parents
                                        </p>
                                    )}
                                </div>

                                {/* Spouse */}
                                <div>
                                    <h4 className="mb-2 font-pixel text-[10px] text-stone-500">
                                        Spouse
                                    </h4>
                                    {(() => {
                                        const spouse = getSpouse(selectedMember.id);
                                        if (spouse) {
                                            return (
                                                <button
                                                    onClick={() => setSelectedMember(spouse)}
                                                    className="flex w-full items-center gap-2 rounded border border-pink-600/50 bg-pink-900/20 px-2 py-1 text-left transition hover:bg-pink-900/30"
                                                >
                                                    <Heart className="h-3 w-3 text-pink-400" />
                                                    <span className="font-pixel text-[10px] text-stone-300">
                                                        {spouse.first_name}
                                                    </span>
                                                </button>
                                            );
                                        }
                                        return (
                                            <p className="font-pixel text-[10px] text-stone-600">
                                                Not married
                                            </p>
                                        );
                                    })()}
                                </div>

                                {/* Children */}
                                <div>
                                    <h4 className="mb-2 font-pixel text-[10px] text-stone-500">
                                        Children
                                    </h4>
                                    {(() => {
                                        const children = getChildren(selectedMember.id);
                                        if (children.length > 0) {
                                            return (
                                                <div className="space-y-1 max-h-24 overflow-y-auto">
                                                    {children.map((child) => (
                                                        <button
                                                            key={child.id}
                                                            onClick={() => setSelectedMember(child)}
                                                            className="flex w-full items-center gap-2 rounded border border-stone-700 bg-stone-800/50 px-2 py-1 text-left transition hover:bg-stone-700"
                                                        >
                                                            <User
                                                                className={`h-3 w-3 ${
                                                                    child.gender === 'male'
                                                                        ? 'text-blue-400'
                                                                        : 'text-pink-400'
                                                                }`}
                                                            />
                                                            <span className="font-pixel text-[10px] text-stone-300">
                                                                {child.first_name}
                                                            </span>
                                                        </button>
                                                    ))}
                                                </div>
                                            );
                                        }
                                        return (
                                            <p className="font-pixel text-[10px] text-stone-600">
                                                No children
                                            </p>
                                        );
                                    })()}
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

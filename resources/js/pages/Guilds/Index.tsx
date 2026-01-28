import { Head, router, usePage } from '@inertiajs/react';
import { Award, Coins, Crown, Hammer, Plus, Star, Users } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface GuildBenefit {
    id: number;
    name: string;
    description: string;
    icon: string;
    effects: Record<string, number> | null;
}

interface Guild {
    id: number;
    name: string;
    description: string | null;
    icon: string;
    color: string;
    primary_skill: string;
    skill_display: string;
    level: number;
    level_progress: number;
    total_contribution: number;
    treasury: number;
    membership_fee: number;
    weekly_dues: number;
    is_public: boolean;
    has_monopoly: boolean;
    member_count: number;
    master_count: number;
    founder: { id: number; username: string } | null;
    guildmaster: { id: number; username: string } | null;
    benefits: GuildBenefit[];
    combined_effects: Record<string, number>;
}

interface Membership {
    id: number;
    guild_id: number;
    guild_name: string;
    guild_icon: string;
    guild_color: string;
    guild_skill: string;
    rank: string;
    rank_display: string;
    contribution: number;
    years_membership: number;
    joined_at: string;
    dues_paid: boolean;
    dues_paid_until: string | null;
    can_be_promoted: boolean;
    has_voting_rights: boolean;
    is_guildmaster: boolean;
}

interface PageProps {
    available_guilds: Guild[];
    my_guilds: Membership[];
    local_guilds: Guild[];
    guild_skills: string[];
    founding_cost: number;
    gold: number;
    location: { type: string; id: number };
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Guilds', href: '/guilds' },
];

const rankColors: Record<string, string> = {
    guildmaster: 'text-yellow-400 bg-yellow-900/30',
    master: 'text-purple-400 bg-purple-900/30',
    journeyman: 'text-blue-400 bg-blue-900/30',
    apprentice: 'text-stone-400 bg-stone-700/30',
};

const skillIcons: Record<string, string> = {
    smithing: 'hammer',
    crafting: 'wrench',
    cooking: 'chef-hat',
    mining: 'pickaxe',
    woodcutting: 'axe',
    fishing: 'fish',
};

export default function GuildsIndex() {
    const { available_guilds, my_guilds, local_guilds, guild_skills, founding_cost, gold } =
        usePage<PageProps>().props;

    const [showCreateGuild, setShowCreateGuild] = useState(false);
    const [guildName, setGuildName] = useState('');
    const [guildDescription, setGuildDescription] = useState('');
    const [selectedSkill, setSelectedSkill] = useState<string>('');
    const [isCreating, setIsCreating] = useState(false);
    const [joiningId, setJoiningId] = useState<number | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const createGuild = async () => {
        if (!guildName.trim() || !selectedSkill) return;
        setIsCreating(true);
        setError(null);

        try {
            const response = await fetch('/guilds/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    name: guildName,
                    description: guildDescription,
                    primary_skill: selectedSkill,
                }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                setShowCreateGuild(false);
                setGuildName('');
                setGuildDescription('');
                setSelectedSkill('');
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError('Failed to create guild');
        } finally {
            setIsCreating(false);
        }
    };

    const joinGuild = async (guildId: number) => {
        setJoiningId(guildId);
        setError(null);

        try {
            const response = await fetch('/guilds/join', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ guild_id: guildId }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError('Failed to join guild');
        } finally {
            setJoiningId(null);
        }
    };

    const canCreateGuild = !my_guilds.some((m) => m.is_guildmaster) && gold >= founding_cost;

    // Check if skill already has a guild at this location
    const getExistingGuildForSkill = (skill: string) => {
        return local_guilds.find((g) => g.primary_skill === skill);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Guilds" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Guilds</h1>
                        <p className="font-pixel text-sm text-stone-400">Join craft guilds, earn contribution, and gain benefits</p>
                    </div>
                    <div className="flex items-center gap-2 font-pixel text-sm">
                        <Coins className="h-4 w-4 text-amber-400" />
                        <span className="text-stone-300">{gold.toLocaleString()}</span>
                    </div>
                </div>

                {/* Messages */}
                {error && (
                    <div className="rounded-lg border border-red-500/50 bg-red-900/30 p-3 font-pixel text-sm text-red-300">
                        {error}
                    </div>
                )}
                {success && (
                    <div className="rounded-lg border border-green-500/50 bg-green-900/30 p-3 font-pixel text-sm text-green-300">
                        {success}
                    </div>
                )}

                {/* My Guilds */}
                {my_guilds.length > 0 && (
                    <div>
                        <h2 className="mb-4 font-pixel text-lg text-amber-300">My Guilds</h2>
                        <div className="grid gap-4 md:grid-cols-2">
                            {my_guilds.map((membership) => (
                                <div
                                    key={membership.id}
                                    className="rounded-xl border-2 border-amber-500/30 bg-amber-900/20 p-4"
                                >
                                    <div className="mb-3 flex items-center justify-between">
                                        <div className="flex items-center gap-2">
                                            <Hammer className="h-5 w-5" style={{ color: membership.guild_color }} />
                                            <h3 className="font-pixel text-base text-white">{membership.guild_name}</h3>
                                        </div>
                                        <span className={`rounded px-2 py-1 font-pixel text-xs ${rankColors[membership.rank]}`}>
                                            {membership.rank_display}
                                        </span>
                                    </div>

                                    <div className="mb-4 grid grid-cols-2 gap-2 font-pixel text-xs">
                                        <div className="flex items-center gap-1 text-stone-400">
                                            <Star className="h-3 w-3 text-amber-400" />
                                            <span>Contribution: {membership.contribution.toLocaleString()}</span>
                                        </div>
                                        <div className="flex items-center gap-1 text-stone-400">
                                            <Award className="h-3 w-3 text-blue-400" />
                                            <span>{membership.years_membership} years</span>
                                        </div>
                                        <div className="flex items-center gap-1 text-stone-400">
                                            <span className="capitalize">{membership.guild_skill}</span>
                                        </div>
                                        <div className={`flex items-center gap-1 ${membership.dues_paid ? 'text-green-400' : 'text-red-400'}`}>
                                            {membership.dues_paid ? 'Dues Paid' : 'Dues Owed'}
                                        </div>
                                    </div>

                                    {/* Action Buttons */}
                                    <div className="flex flex-wrap gap-2">
                                        <a
                                            href={`/guilds/${membership.guild_id}`}
                                            className="flex items-center gap-1 rounded bg-amber-600/50 px-3 py-1 font-pixel text-xs text-amber-200 transition hover:bg-amber-600"
                                        >
                                            View Guild
                                        </a>
                                        {membership.is_guildmaster && (
                                            <span className="flex items-center gap-1 rounded bg-yellow-600/50 px-3 py-1 font-pixel text-xs text-yellow-200">
                                                <Crown className="h-3 w-3" />
                                                Guildmaster
                                            </span>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Local Guilds */}
                {local_guilds.length > 0 && (
                    <div>
                        <h2 className="mb-4 font-pixel text-lg text-amber-300">Guilds at This Location</h2>
                        <div className="grid gap-3 md:grid-cols-3">
                            {local_guilds.map((guild) => (
                                <div
                                    key={guild.id}
                                    className="rounded-lg border border-stone-700 bg-stone-800/50 p-3"
                                >
                                    <div className="flex items-center gap-2">
                                        <Hammer className="h-4 w-4" style={{ color: guild.color }} />
                                        <span className="font-pixel text-sm text-white">{guild.name}</span>
                                    </div>
                                    <div className="mt-1 font-pixel text-xs text-stone-400">
                                        {guild.skill_display} Guild - Level {guild.level}
                                    </div>
                                    <div className="mt-1 flex items-center gap-2 font-pixel text-xs text-stone-400">
                                        <Users className="h-3 w-3" />
                                        <span>{guild.member_count} members</span>
                                    </div>
                                    {guild.has_monopoly && (
                                        <div className="mt-1 font-pixel text-xs text-amber-400">
                                            Monopoly Rights
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Create Guild Section */}
                <div>
                    <div className="flex items-center justify-between">
                        <h2 className="font-pixel text-lg text-amber-300">Found a Guild</h2>
                        <button
                            onClick={() => setShowCreateGuild(!showCreateGuild)}
                            disabled={!canCreateGuild && !showCreateGuild}
                            className="flex items-center gap-1 rounded bg-amber-600 px-3 py-2 font-pixel text-xs text-white transition hover:bg-amber-500 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <Plus className="h-4 w-4" />
                            {showCreateGuild ? 'Cancel' : 'Create Guild'}
                        </button>
                    </div>

                    {!canCreateGuild && !showCreateGuild && (
                        <p className="mt-2 font-pixel text-xs text-stone-500">
                            {my_guilds.some((m) => m.is_guildmaster)
                                ? 'You are already a guildmaster.'
                                : `You need ${founding_cost.toLocaleString()} gold to found a guild.`}
                        </p>
                    )}

                    {showCreateGuild && (
                        <div className="mt-4 rounded-xl border-2 border-amber-500/30 bg-amber-900/20 p-4">
                            <div className="mb-4">
                                <label className="mb-1 block font-pixel text-xs text-stone-400">Guild Name</label>
                                <input
                                    type="text"
                                    value={guildName}
                                    onChange={(e) => setGuildName(e.target.value)}
                                    maxLength={50}
                                    className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-amber-500 focus:outline-none"
                                    placeholder="Enter guild name..."
                                />
                            </div>

                            <div className="mb-4">
                                <label className="mb-1 block font-pixel text-xs text-stone-400">Description</label>
                                <textarea
                                    value={guildDescription}
                                    onChange={(e) => setGuildDescription(e.target.value)}
                                    maxLength={500}
                                    rows={2}
                                    className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-amber-500 focus:outline-none"
                                    placeholder="Describe your guild's purpose..."
                                />
                            </div>

                            <div className="mb-4">
                                <label className="mb-2 block font-pixel text-xs text-stone-400">
                                    Primary Skill
                                </label>
                                <div className="grid gap-2 md:grid-cols-3">
                                    {guild_skills.map((skill) => {
                                        const existingGuild = getExistingGuildForSkill(skill);
                                        const isDisabled = !!existingGuild;
                                        return (
                                            <button
                                                key={skill}
                                                onClick={() => !isDisabled && setSelectedSkill(skill)}
                                                disabled={isDisabled}
                                                className={`rounded-lg border p-3 text-left transition ${
                                                    selectedSkill === skill
                                                        ? 'border-amber-500 bg-amber-900/50'
                                                        : isDisabled
                                                        ? 'cursor-not-allowed border-stone-700 bg-stone-800/30 opacity-50'
                                                        : 'border-stone-600 bg-stone-800 hover:border-stone-500'
                                                }`}
                                            >
                                                <div className="font-pixel text-sm capitalize text-white">{skill}</div>
                                                {isDisabled && (
                                                    <div className="font-pixel text-xs text-red-400">
                                                        {existingGuild?.name} exists
                                                    </div>
                                                )}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>

                            <div className="mb-4 rounded-lg border border-stone-600 bg-stone-800/50 p-3 font-pixel text-xs text-stone-400">
                                <div className="mb-1 text-amber-300">Requirements:</div>
                                <ul className="list-inside list-disc space-y-1">
                                    <li>Level 10 in the chosen skill</li>
                                    <li>{founding_cost.toLocaleString()} gold founding cost</li>
                                    <li>Located in a town or barony</li>
                                </ul>
                            </div>

                            <button
                                onClick={createGuild}
                                disabled={!guildName.trim() || !selectedSkill || isCreating || gold < founding_cost}
                                className="w-full rounded bg-amber-600 py-2 font-pixel text-sm text-white transition hover:bg-amber-500 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {isCreating ? 'Creating...' : `Found Guild (${founding_cost.toLocaleString()} gold)`}
                            </button>
                        </div>
                    )}
                </div>

                {/* Available Guilds to Join */}
                {available_guilds.length > 0 && (
                    <div>
                        <h2 className="mb-4 font-pixel text-lg text-amber-300">Available Guilds</h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {available_guilds.map((guild) => (
                                <div
                                    key={guild.id}
                                    className="rounded-xl border border-stone-700 bg-stone-800/50 p-4"
                                >
                                    <div className="mb-3 flex items-center justify-between">
                                        <div className="flex items-center gap-2">
                                            <Hammer className="h-5 w-5" style={{ color: guild.color }} />
                                            <h3 className="font-pixel text-base text-white">{guild.name}</h3>
                                        </div>
                                        <span className="rounded bg-stone-700 px-2 py-1 font-pixel text-xs text-stone-300">
                                            Level {guild.level}
                                        </span>
                                    </div>

                                    {guild.description && (
                                        <p className="mb-3 font-pixel text-xs text-stone-400">{guild.description}</p>
                                    )}

                                    <div className="mb-3 space-y-1 font-pixel text-xs text-stone-400">
                                        <div className="flex items-center gap-1" title={skillIcons[guild.primary_skill] || guild.primary_skill}>
                                            <span className="text-amber-400 capitalize">{guild.skill_display}</span> Guild
                                        </div>
                                        <div className="flex items-center gap-1">
                                            <Users className="h-3 w-3" />
                                            {guild.member_count} members ({guild.master_count} masters)
                                        </div>
                                        {guild.guildmaster && (
                                            <div className="flex items-center gap-1">
                                                <Crown className="h-3 w-3 text-yellow-400" />
                                                Guildmaster: {guild.guildmaster.username}
                                            </div>
                                        )}
                                        <div>
                                            Membership Fee: {guild.membership_fee.toLocaleString()} gold
                                        </div>
                                        <div>
                                            Weekly Dues: {guild.weekly_dues.toLocaleString()} gold
                                        </div>
                                    </div>

                                    {/* Benefits */}
                                    {guild.benefits.length > 0 && (
                                        <div className="mb-3">
                                            <div className="mb-1 font-pixel text-xs text-stone-500">Benefits:</div>
                                            <div className="flex flex-wrap gap-1">
                                                {guild.benefits.map((benefit) => (
                                                    <span
                                                        key={benefit.id}
                                                        className="rounded bg-amber-900/30 px-2 py-0.5 font-pixel text-xs text-amber-300"
                                                        title={benefit.description}
                                                    >
                                                        {benefit.name}
                                                    </span>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    <button
                                        onClick={() => joinGuild(guild.id)}
                                        disabled={joiningId === guild.id || gold < guild.membership_fee}
                                        className="w-full rounded bg-amber-600/50 py-2 font-pixel text-xs text-amber-200 transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {joiningId === guild.id ? 'Joining...' : `Join (${guild.membership_fee.toLocaleString()} gold)`}
                                    </button>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Empty State */}
                {available_guilds.length === 0 && my_guilds.length === 0 && local_guilds.length === 0 && (
                    <div className="flex flex-1 items-center justify-center">
                        <div className="text-center">
                            <Hammer className="mx-auto mb-3 h-16 w-16 text-stone-600" />
                            <p className="font-pixel text-base text-stone-500">No guilds at this location</p>
                            <p className="font-pixel text-xs text-stone-600">
                                Be the first to found a guild here!
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

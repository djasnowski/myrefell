import { Head, Link, usePage } from '@inertiajs/react';
import {
    Calendar,
    Crown,
    Flag,
    MapPin,
    Shield,
    Skull,
    Sword,
    Swords,
    Target,
    Trophy,
    Users,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface WarSide {
    type: string;
    id: number;
    name: string;
    kingdom_id: number | null;
    kingdom_name: string | null;
}

interface Participant {
    id: number;
    participant_type: string;
    participant_id: number;
    name: string;
    side: 'attacker' | 'defender';
    role: 'primary' | 'ally' | 'vassal';
    is_war_leader: boolean;
    contribution_score: number;
    joined_at: string | null;
}

interface Battle {
    id: number;
    battle_type: string;
    status: string;
    location_name: string;
    attacker_troops_start: number;
    defender_troops_start: number;
    attacker_casualties: number;
    defender_casualties: number;
    started_at: string | null;
    ended_at: string | null;
}

interface ActiveSiege {
    id: number;
    target_name: string;
    target_type: string;
    status: string;
    fortification_level: number;
    garrison_strength: number;
    garrison_morale: number;
    supplies_remaining: number;
    days_besieged: number;
    has_breach: boolean;
    started_at: string | null;
}

interface WarGoal {
    id: number;
    goal_type: string;
    is_achieved: boolean;
    war_score_value: number;
}

interface PeaceTreaty {
    treaty_type: string;
    winner_side: string;
    gold_payment: number;
    truce_days: number;
    signed_at: string | null;
}

interface UserParticipation {
    side: 'attacker' | 'defender';
    role: string;
    is_war_leader: boolean;
    contribution_score: number;
}

interface War {
    id: number;
    name: string;
    casus_belli: string;
    status: string;
    attacker_war_score: number;
    defender_war_score: number;
    declared_at: string | null;
    ended_at: string | null;
    days_active: number;
    attacker: WarSide;
    defender: WarSide;
    participant_count: number;
    battle_count: number;
    siege_count: number;
    attacker_participants?: Participant[];
    defender_participants?: Participant[];
    recent_battles?: Battle[];
    active_sieges?: ActiveSiege[];
    goals?: WarGoal[];
    user_participation: UserParticipation | null;
    peace_treaty?: PeaceTreaty;
}

interface PageProps {
    active_wars: War[];
    concluded_wars: War[];
    user_participation: Array<{
        war_id: number;
        side: string;
        role: string;
        is_war_leader: boolean;
        contribution_score: number;
    }>;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Warfare', href: '#' },
    { title: 'Wars', href: '/warfare/wars' },
];

const statusColors: Record<string, { bg: string; text: string; label: string }> = {
    active: { bg: 'bg-amber-900/30', text: 'text-amber-400', label: 'Active' },
    attacker_winning: { bg: 'bg-red-900/30', text: 'text-red-400', label: 'Attackers Winning' },
    defender_winning: { bg: 'bg-blue-900/30', text: 'text-blue-400', label: 'Defenders Winning' },
    white_peace: { bg: 'bg-stone-900/30', text: 'text-stone-400', label: 'White Peace' },
    attacker_victory: { bg: 'bg-red-900/30', text: 'text-red-400', label: 'Attacker Victory' },
    defender_victory: { bg: 'bg-blue-900/30', text: 'text-blue-400', label: 'Defender Victory' },
};

const casusBelliLabels: Record<string, { label: string; icon: typeof Sword }> = {
    claim: { label: 'Pressing Claim', icon: Crown },
    conquest: { label: 'Conquest', icon: Sword },
    rebellion: { label: 'Rebellion', icon: Flag },
    holy_war: { label: 'Holy War', icon: Target },
    defense: { label: 'Defensive War', icon: Shield },
    raid: { label: 'Raid', icon: Skull },
};

const battleStatusColors: Record<string, string> = {
    ongoing: 'text-amber-400',
    attacker_victory: 'text-red-400',
    defender_victory: 'text-blue-400',
    draw: 'text-stone-400',
    inconclusive: 'text-stone-400',
};

const siegeStatusColors: Record<string, string> = {
    active: 'text-amber-400',
    assault: 'text-red-400',
    breached: 'text-orange-400',
    captured: 'text-green-400',
    lifted: 'text-stone-400',
    abandoned: 'text-stone-500',
};

export default function Wars() {
    const { active_wars, concluded_wars } = usePage<PageProps>().props;

    const renderWarScoreBar = (attackerScore: number, defenderScore: number) => {
        const totalScore = attackerScore + defenderScore;
        const attackerWidth = totalScore > 0 ? (attackerScore / 200) * 100 : 50;
        const defenderWidth = totalScore > 0 ? (defenderScore / 200) * 100 : 50;

        return (
            <div className="flex h-4 w-full overflow-hidden rounded bg-stone-800">
                <div
                    className="flex items-center justify-end bg-gradient-to-r from-red-700 to-red-500 pr-1 transition-all duration-300"
                    style={{ width: `${attackerWidth}%` }}
                >
                    {attackerScore > 10 && (
                        <span className="font-pixel text-[10px] text-white">{attackerScore}</span>
                    )}
                </div>
                <div className="w-1 bg-stone-600" />
                <div
                    className="flex items-center justify-start bg-gradient-to-l from-blue-700 to-blue-500 pl-1 transition-all duration-300"
                    style={{ width: `${defenderWidth}%` }}
                >
                    {defenderScore > 10 && (
                        <span className="font-pixel text-[10px] text-white">{defenderScore}</span>
                    )}
                </div>
            </div>
        );
    };

    const renderWarCard = (war: War, isActive: boolean = true) => {
        const status = statusColors[war.status] || statusColors.active;
        const casusBelli = casusBelliLabels[war.casus_belli] || { label: war.casus_belli, icon: Sword };
        const CasusBelliIcon = casusBelli.icon;

        return (
            <div
                key={war.id}
                className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4"
            >
                {/* Header */}
                <div className="mb-3 flex items-start justify-between">
                    <div className="flex items-center gap-2">
                        <Swords className="h-5 w-5 text-red-400" />
                        <h3 className="font-pixel text-base text-white">{war.name}</h3>
                    </div>
                    <span className={`rounded px-2 py-1 font-pixel text-[10px] ${status.bg} ${status.text}`}>
                        {status.label}
                    </span>
                </div>

                {/* Casus Belli */}
                <div className="mb-3 flex items-center gap-2 font-pixel text-xs">
                    <CasusBelliIcon className="h-3 w-3 text-amber-400" />
                    <span className="text-stone-400">Casus Belli:</span>
                    <span className="text-amber-300">{casusBelli.label}</span>
                </div>

                {/* Combatants */}
                <div className="mb-3 rounded-lg bg-stone-900/50 p-3">
                    <div className="mb-2 grid grid-cols-2 gap-4">
                        {/* Attacker Side */}
                        <div>
                            <div className="mb-1 flex items-center gap-1 font-pixel text-[10px] text-red-400">
                                <Sword className="h-3 w-3" />
                                ATTACKERS
                            </div>
                            <div className="font-pixel text-sm text-white">{war.attacker.name}</div>
                            {war.attacker.kingdom_name && war.attacker.type !== 'kingdom' && (
                                <div className="font-pixel text-[10px] text-stone-400">
                                    ({war.attacker.kingdom_name})
                                </div>
                            )}
                            {war.attacker_participants && war.attacker_participants.length > 1 && (
                                <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                    +{war.attacker_participants.length - 1} allies
                                </div>
                            )}
                        </div>

                        {/* Defender Side */}
                        <div className="text-right">
                            <div className="mb-1 flex items-center justify-end gap-1 font-pixel text-[10px] text-blue-400">
                                <Shield className="h-3 w-3" />
                                DEFENDERS
                            </div>
                            <div className="font-pixel text-sm text-white">{war.defender.name}</div>
                            {war.defender.kingdom_name && war.defender.type !== 'kingdom' && (
                                <div className="font-pixel text-[10px] text-stone-400">
                                    ({war.defender.kingdom_name})
                                </div>
                            )}
                            {war.defender_participants && war.defender_participants.length > 1 && (
                                <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                    +{war.defender_participants.length - 1} allies
                                </div>
                            )}
                        </div>
                    </div>

                    {/* War Score Bar */}
                    {isActive && (
                        <div className="mt-2">
                            <div className="mb-1 flex justify-between font-pixel text-[10px]">
                                <span className="text-red-400">War Score: {war.attacker_war_score}</span>
                                <span className="text-blue-400">War Score: {war.defender_war_score}</span>
                            </div>
                            {renderWarScoreBar(war.attacker_war_score, war.defender_war_score)}
                        </div>
                    )}
                </div>

                {/* Stats Row */}
                <div className="mb-3 grid grid-cols-3 gap-2 font-pixel text-xs">
                    <div className="flex items-center gap-1">
                        <Calendar className="h-3 w-3 text-stone-400" />
                        <span className="text-stone-400">Duration:</span>
                        <span className="text-white">{war.days_active}d</span>
                    </div>
                    <div className="flex items-center gap-1">
                        <Swords className="h-3 w-3 text-red-400" />
                        <span className="text-stone-400">Battles:</span>
                        <span className="text-white">{war.battle_count}</span>
                    </div>
                    <div className="flex items-center gap-1">
                        <Target className="h-3 w-3 text-amber-400" />
                        <span className="text-stone-400">Sieges:</span>
                        <span className="text-white">{war.siege_count}</span>
                    </div>
                </div>

                {/* User Participation Badge */}
                {war.user_participation && (
                    <div className={`mb-3 rounded-lg p-2 ${
                        war.user_participation.side === 'attacker'
                            ? 'border border-red-500/30 bg-red-900/20'
                            : 'border border-blue-500/30 bg-blue-900/20'
                    }`}>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Users className="h-3 w-3 text-amber-400" />
                                <span className="font-pixel text-xs text-stone-300">
                                    Your Role: {war.user_participation.role === 'primary' ? 'Primary Belligerent' :
                                        war.user_participation.role === 'ally' ? 'Allied Power' : 'Vassal'}
                                    {war.user_participation.is_war_leader && ' (War Leader)'}
                                </span>
                            </div>
                            <span className="font-pixel text-xs text-amber-300">
                                Contribution: {war.user_participation.contribution_score}
                            </span>
                        </div>
                    </div>
                )}

                {/* Recent Battles (for active wars) */}
                {isActive && war.recent_battles && war.recent_battles.length > 0 && (
                    <div className="mb-3">
                        <div className="mb-1 font-pixel text-[10px] text-stone-400">Recent Battles:</div>
                        <div className="space-y-1">
                            {war.recent_battles.map((battle) => (
                                <div
                                    key={battle.id}
                                    className="flex items-center justify-between rounded bg-stone-900/30 px-2 py-1"
                                >
                                    <div className="flex items-center gap-2">
                                        <MapPin className="h-3 w-3 text-stone-400" />
                                        <span className="font-pixel text-[10px] text-stone-300">
                                            {battle.location_name}
                                        </span>
                                    </div>
                                    <span className={`font-pixel text-[10px] capitalize ${battleStatusColors[battle.status] || 'text-stone-400'}`}>
                                        {battle.status.replace('_', ' ')}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Active Sieges */}
                {isActive && war.active_sieges && war.active_sieges.length > 0 && (
                    <div className="mb-3">
                        <div className="mb-1 font-pixel text-[10px] text-stone-400">Active Sieges:</div>
                        <div className="space-y-1">
                            {war.active_sieges.map((siege) => (
                                <div
                                    key={siege.id}
                                    className="flex items-center justify-between rounded bg-stone-900/30 px-2 py-1"
                                >
                                    <div className="flex items-center gap-2">
                                        <Target className="h-3 w-3 text-amber-400" />
                                        <span className="font-pixel text-[10px] text-stone-300">
                                            {siege.target_name}
                                        </span>
                                        <span className="font-pixel text-[10px] text-stone-500">
                                            Day {siege.days_besieged}
                                        </span>
                                    </div>
                                    <span className={`font-pixel text-[10px] capitalize ${siegeStatusColors[siege.status] || 'text-stone-400'}`}>
                                        {siege.status}
                                        {siege.has_breach && ' (Breached)'}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* War Goals (for active wars) */}
                {isActive && war.goals && war.goals.length > 0 && (
                    <div className="mb-3">
                        <div className="mb-1 font-pixel text-[10px] text-stone-400">War Goals:</div>
                        <div className="flex flex-wrap gap-1">
                            {war.goals.map((goal) => (
                                <span
                                    key={goal.id}
                                    className={`rounded px-2 py-0.5 font-pixel text-[10px] ${
                                        goal.is_achieved
                                            ? 'bg-green-900/30 text-green-400'
                                            : 'bg-stone-700/50 text-stone-300'
                                    }`}
                                >
                                    {goal.goal_type.replace('_', ' ')} (+{goal.war_score_value})
                                    {goal.is_achieved && ' ✓'}
                                </span>
                            ))}
                        </div>
                    </div>
                )}

                {/* Peace Treaty Info (for concluded wars) */}
                {!isActive && war.peace_treaty && (
                    <div className="rounded-lg border border-stone-500/30 bg-stone-900/30 p-2">
                        <div className="flex items-center gap-2 font-pixel text-xs">
                            <Trophy className="h-3 w-3 text-amber-400" />
                            <span className="text-stone-400">Peace Treaty:</span>
                            <span className="capitalize text-white">
                                {war.peace_treaty.treaty_type.replace('_', ' ')}
                            </span>
                        </div>
                        {war.peace_treaty.gold_payment > 0 && (
                            <div className="mt-1 font-pixel text-[10px] text-yellow-400">
                                War Indemnity: {war.peace_treaty.gold_payment}g
                            </div>
                        )}
                        {war.peace_treaty.truce_days > 0 && (
                            <div className="font-pixel text-[10px] text-stone-400">
                                Truce: {war.peace_treaty.truce_days} days
                            </div>
                        )}
                    </div>
                )}

                {/* Participants List (expanded view for active wars) */}
                {isActive && war.attacker_participants && war.attacker_participants.length > 0 && (
                    <details className="mt-3">
                        <summary className="cursor-pointer font-pixel text-[10px] text-stone-400 hover:text-stone-300">
                            View All Participants ({war.participant_count})
                        </summary>
                        <div className="mt-2 grid grid-cols-2 gap-2">
                            <div>
                                <div className="mb-1 font-pixel text-[10px] text-red-400">Attackers:</div>
                                {war.attacker_participants.map((p) => (
                                    <div key={p.id} className="font-pixel text-[10px] text-stone-300">
                                        {p.name}
                                        {p.is_war_leader && (
                                            <Crown className="ml-1 inline h-2 w-2 text-amber-400" />
                                        )}
                                        <span className="text-stone-500"> ({p.role})</span>
                                    </div>
                                ))}
                            </div>
                            <div>
                                <div className="mb-1 font-pixel text-[10px] text-blue-400">Defenders:</div>
                                {war.defender_participants?.map((p) => (
                                    <div key={p.id} className="font-pixel text-[10px] text-stone-300">
                                        {p.name}
                                        {p.is_war_leader && (
                                            <Crown className="ml-1 inline h-2 w-2 text-amber-400" />
                                        )}
                                        <span className="text-stone-500"> ({p.role})</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </details>
                )}
            </div>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Wars" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-red-400">Wars</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            Track ongoing conflicts and war history
                        </p>
                    </div>
                    <Link
                        href="/warfare/armies"
                        className="flex items-center gap-2 rounded border-2 border-stone-600/50 bg-stone-800/30 px-4 py-2 font-pixel text-xs text-stone-300 transition hover:bg-stone-700/30"
                    >
                        <Shield className="h-4 w-4" />
                        Manage Armies
                    </Link>
                </div>

                {/* Active Wars */}
                {active_wars.length > 0 && (
                    <div>
                        <h2 className="mb-3 font-pixel text-lg text-red-300">Active Wars</h2>
                        <div className="grid gap-4 md:grid-cols-2">
                            {active_wars.map((war) => renderWarCard(war, true))}
                        </div>
                    </div>
                )}

                {/* Concluded Wars */}
                {concluded_wars.length > 0 && (
                    <div>
                        <h2 className="mb-3 font-pixel text-lg text-stone-400">War History</h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {concluded_wars.map((war) => renderWarCard(war, false))}
                        </div>
                    </div>
                )}

                {/* Empty State */}
                {active_wars.length === 0 && concluded_wars.length === 0 && (
                    <div className="flex flex-1 items-center justify-center">
                        <div className="text-center">
                            <Swords className="mx-auto mb-3 h-16 w-16 text-stone-600" />
                            <p className="font-pixel text-base text-stone-500">No wars yet</p>
                            <p className="font-pixel text-xs text-stone-600">
                                The realm is at peace... for now.
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

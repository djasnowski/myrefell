import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Calendar,
    Castle,
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
    war: War;
    all_battles: Battle[];
    all_sieges: ActiveSiege[];
    can_offer_peace: boolean;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Warfare', href: '#' },
    { title: 'Wars', href: '/warfare/wars' },
    { title: 'War Details', href: '#' },
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

const battleStatusColors: Record<string, { text: string; label: string }> = {
    ongoing: { text: 'text-amber-400', label: 'Ongoing' },
    attacker_victory: { text: 'text-red-400', label: 'Attacker Victory' },
    defender_victory: { text: 'text-blue-400', label: 'Defender Victory' },
    draw: { text: 'text-stone-400', label: 'Draw' },
    inconclusive: { text: 'text-stone-400', label: 'Inconclusive' },
};

const siegeStatusColors: Record<string, { text: string; label: string }> = {
    active: { text: 'text-amber-400', label: 'Under Siege' },
    assault: { text: 'text-red-400', label: 'Assault' },
    breached: { text: 'text-orange-400', label: 'Breached' },
    captured: { text: 'text-green-400', label: 'Captured' },
    lifted: { text: 'text-stone-400', label: 'Lifted' },
    abandoned: { text: 'text-stone-500', label: 'Abandoned' },
};

const goalTypeLabels: Record<string, string> = {
    conquer_territory: 'Conquer Territory',
    subjugation: 'Subjugation',
    independence: 'Independence',
    raid: 'Raid',
    humiliate: 'Humiliate',
    enforce_claim: 'Enforce Claim',
    holy_conquest: 'Holy Conquest',
};

export default function WarShow() {
    const { war, all_battles, all_sieges, can_offer_peace } = usePage<PageProps>().props;

    const status = statusColors[war.status] || statusColors.active;
    const casusBelli = casusBelliLabels[war.casus_belli] || { label: war.casus_belli, icon: Sword };
    const CasusBelliIcon = casusBelli.icon;
    const isEnded = ['white_peace', 'attacker_victory', 'defender_victory'].includes(war.status);

    const totalAttackerContribution = war.attacker_participants?.reduce((sum, p) => sum + p.contribution_score, 0) || 0;
    const totalDefenderContribution = war.defender_participants?.reduce((sum, p) => sum + p.contribution_score, 0) || 0;
    const totalAttackerCasualties = all_battles.reduce((sum, b) => sum + b.attacker_casualties, 0);
    const totalDefenderCasualties = all_battles.reduce((sum, b) => sum + b.defender_casualties, 0);

    const renderWarScoreBar = (attackerScore: number, defenderScore: number) => {
        const attackerWidth = (attackerScore / 200) * 100;
        const defenderWidth = (defenderScore / 200) * 100;

        return (
            <div className="flex h-6 w-full overflow-hidden rounded-lg bg-stone-800">
                <div
                    className="flex items-center justify-end bg-gradient-to-r from-red-700 to-red-500 pr-2 transition-all duration-300"
                    style={{ width: `${attackerWidth}%` }}
                >
                    {attackerScore > 10 && (
                        <span className="font-pixel text-xs text-white">{attackerScore}</span>
                    )}
                </div>
                <div className="w-1 bg-stone-600" />
                <div
                    className="flex items-center justify-start bg-gradient-to-l from-blue-700 to-blue-500 pl-2 transition-all duration-300"
                    style={{ width: `${defenderWidth}%` }}
                >
                    {defenderScore > 10 && (
                        <span className="font-pixel text-xs text-white">{defenderScore}</span>
                    )}
                </div>
            </div>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`War: ${war.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Back Link */}
                <Link
                    href="/warfare/wars"
                    className="flex items-center gap-2 font-pixel text-sm text-stone-400 transition hover:text-stone-300"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to Wars
                </Link>

                {/* Header */}
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <Swords className="h-8 w-8 text-red-400" />
                        <div>
                            <h1 className="font-pixel text-2xl text-white">{war.name}</h1>
                            <div className="flex items-center gap-3 font-pixel text-sm text-stone-400">
                                <span className="flex items-center gap-1">
                                    <CasusBelliIcon className="h-3 w-3 text-amber-400" />
                                    {casusBelli.label}
                                </span>
                                {war.declared_at && (
                                    <span className="flex items-center gap-1">
                                        <Calendar className="h-3 w-3 text-stone-400" />
                                        Started: {new Date(war.declared_at).toLocaleDateString()}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <span className={`rounded px-3 py-1.5 font-pixel text-xs ${status.bg} ${status.text}`}>
                            {status.label}
                        </span>
                        {can_offer_peace && (
                            <Link
                                href={`/warfare/wars/${war.id}/peace`}
                                className="flex items-center gap-1 rounded border border-green-600/50 bg-green-900/20 px-3 py-1.5 font-pixel text-xs text-green-300 transition hover:bg-green-900/40"
                            >
                                <Trophy className="h-3 w-3" />
                                Offer Peace
                            </Link>
                        )}
                    </div>
                </div>

                {/* User Participation Banner */}
                {war.user_participation && (
                    <div className={`rounded-xl border-2 p-4 ${
                        war.user_participation.side === 'attacker'
                            ? 'border-red-500/30 bg-red-900/20'
                            : 'border-blue-500/30 bg-blue-900/20'
                    }`}>
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div className="flex items-center gap-3">
                                <Users className="h-5 w-5 text-amber-400" />
                                <div>
                                    <span className="font-pixel text-sm text-white">
                                        Your Role: {war.user_participation.role === 'primary' ? 'Primary Belligerent' :
                                            war.user_participation.role === 'ally' ? 'Allied Power' : 'Vassal'}
                                    </span>
                                    {war.user_participation.is_war_leader && (
                                        <span className="ml-2 rounded bg-amber-900/50 px-2 py-0.5 font-pixel text-[10px] text-amber-300">
                                            War Leader
                                        </span>
                                    )}
                                </div>
                            </div>
                            <div className="font-pixel text-sm">
                                <span className="text-stone-400">Contribution: </span>
                                <span className="text-amber-300">{war.user_participation.contribution_score} pts</span>
                            </div>
                        </div>
                    </div>
                )}

                {/* War Score */}
                {!isEnded && (
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <h2 className="mb-3 flex items-center gap-2 font-pixel text-lg text-white">
                            <Target className="h-5 w-5 text-amber-400" />
                            War Score
                        </h2>
                        <div className="mb-2 flex justify-between font-pixel text-xs">
                            <span className="text-red-400">Attackers: {war.attacker_war_score}</span>
                            <span className="text-stone-400">100 to win</span>
                            <span className="text-blue-400">Defenders: {war.defender_war_score}</span>
                        </div>
                        {renderWarScoreBar(war.attacker_war_score, war.defender_war_score)}
                    </div>
                )}

                {/* Peace Treaty (for concluded wars) */}
                {isEnded && war.peace_treaty && (
                    <div className="rounded-xl border-2 border-amber-500/30 bg-amber-900/20 p-4">
                        <h2 className="mb-3 flex items-center gap-2 font-pixel text-lg text-white">
                            <Trophy className="h-5 w-5 text-amber-400" />
                            Peace Treaty
                        </h2>
                        <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                            <div className="font-pixel text-xs">
                                <span className="text-stone-400">Treaty Type: </span>
                                <span className="capitalize text-white">{war.peace_treaty.treaty_type.replace('_', ' ')}</span>
                            </div>
                            <div className="font-pixel text-xs">
                                <span className="text-stone-400">Victor: </span>
                                <span className={war.peace_treaty.winner_side === 'attacker' ? 'text-red-400' : 'text-blue-400'}>
                                    {war.peace_treaty.winner_side === 'attacker' ? 'Attackers' : 'Defenders'}
                                </span>
                            </div>
                            {war.peace_treaty.gold_payment > 0 && (
                                <div className="font-pixel text-xs">
                                    <span className="text-stone-400">War Indemnity: </span>
                                    <span className="text-yellow-400">{war.peace_treaty.gold_payment}g</span>
                                </div>
                            )}
                            {war.peace_treaty.truce_days > 0 && (
                                <div className="font-pixel text-xs">
                                    <span className="text-stone-400">Truce Duration: </span>
                                    <span className="text-white">{war.peace_treaty.truce_days} days</span>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* Combatants Grid */}
                <div className="grid gap-4 md:grid-cols-2">
                    {/* Attackers */}
                    <div className="rounded-xl border-2 border-red-500/30 bg-red-900/20 p-4">
                        <div className="mb-4 flex items-center justify-between">
                            <h2 className="flex items-center gap-2 font-pixel text-lg text-red-400">
                                <Sword className="h-5 w-5" />
                                Attackers
                            </h2>
                            <span className="font-pixel text-xs text-stone-400">
                                Score: {war.attacker_war_score}
                            </span>
                        </div>

                        {/* Primary Attacker */}
                        <div className="mb-3 rounded-lg bg-stone-900/50 p-3">
                            <div className="flex items-center gap-2">
                                <Crown className="h-4 w-4 text-amber-400" />
                                <span className="font-pixel text-sm text-white">{war.attacker.name}</span>
                                <span className="font-pixel text-[10px] text-stone-500">(War Leader)</span>
                            </div>
                            {war.attacker.kingdom_name && war.attacker.type !== 'kingdom' && (
                                <div className="mt-1 font-pixel text-[10px] text-stone-400">
                                    Kingdom: {war.attacker.kingdom_name}
                                </div>
                            )}
                        </div>

                        {/* Allied Participants */}
                        {war.attacker_participants && war.attacker_participants.length > 0 && (
                            <div className="space-y-2">
                                <div className="font-pixel text-[10px] text-stone-400">Allies & Vassals:</div>
                                {war.attacker_participants
                                    .filter(p => !p.is_war_leader)
                                    .map((p) => (
                                        <div key={p.id} className="flex items-center justify-between rounded bg-stone-900/30 px-2 py-1">
                                            <div className="flex items-center gap-2">
                                                <span className="font-pixel text-xs text-stone-300">{p.name}</span>
                                                <span className="font-pixel text-[10px] text-stone-500">({p.role})</span>
                                            </div>
                                            <span className="font-pixel text-[10px] text-amber-300">+{p.contribution_score}</span>
                                        </div>
                                    ))}
                            </div>
                        )}

                        {/* Attacker Stats */}
                        <div className="mt-4 border-t border-red-500/20 pt-3">
                            <div className="grid grid-cols-2 gap-2 font-pixel text-xs">
                                <div>
                                    <span className="text-stone-400">Total Contribution: </span>
                                    <span className="text-white">{totalAttackerContribution}</span>
                                </div>
                                <div>
                                    <span className="text-stone-400">Casualties: </span>
                                    <span className="text-red-300">{totalAttackerCasualties}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Defenders */}
                    <div className="rounded-xl border-2 border-blue-500/30 bg-blue-900/20 p-4">
                        <div className="mb-4 flex items-center justify-between">
                            <h2 className="flex items-center gap-2 font-pixel text-lg text-blue-400">
                                <Shield className="h-5 w-5" />
                                Defenders
                            </h2>
                            <span className="font-pixel text-xs text-stone-400">
                                Score: {war.defender_war_score}
                            </span>
                        </div>

                        {/* Primary Defender */}
                        <div className="mb-3 rounded-lg bg-stone-900/50 p-3">
                            <div className="flex items-center gap-2">
                                <Crown className="h-4 w-4 text-amber-400" />
                                <span className="font-pixel text-sm text-white">{war.defender.name}</span>
                                <span className="font-pixel text-[10px] text-stone-500">(War Leader)</span>
                            </div>
                            {war.defender.kingdom_name && war.defender.type !== 'kingdom' && (
                                <div className="mt-1 font-pixel text-[10px] text-stone-400">
                                    Kingdom: {war.defender.kingdom_name}
                                </div>
                            )}
                        </div>

                        {/* Allied Participants */}
                        {war.defender_participants && war.defender_participants.length > 0 && (
                            <div className="space-y-2">
                                <div className="font-pixel text-[10px] text-stone-400">Allies & Vassals:</div>
                                {war.defender_participants
                                    .filter(p => !p.is_war_leader)
                                    .map((p) => (
                                        <div key={p.id} className="flex items-center justify-between rounded bg-stone-900/30 px-2 py-1">
                                            <div className="flex items-center gap-2">
                                                <span className="font-pixel text-xs text-stone-300">{p.name}</span>
                                                <span className="font-pixel text-[10px] text-stone-500">({p.role})</span>
                                            </div>
                                            <span className="font-pixel text-[10px] text-amber-300">+{p.contribution_score}</span>
                                        </div>
                                    ))}
                            </div>
                        )}

                        {/* Defender Stats */}
                        <div className="mt-4 border-t border-blue-500/20 pt-3">
                            <div className="grid grid-cols-2 gap-2 font-pixel text-xs">
                                <div>
                                    <span className="text-stone-400">Total Contribution: </span>
                                    <span className="text-white">{totalDefenderContribution}</span>
                                </div>
                                <div>
                                    <span className="text-stone-400">Casualties: </span>
                                    <span className="text-blue-300">{totalDefenderCasualties}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* War Goals */}
                {war.goals && war.goals.length > 0 && (
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                            <Flag className="h-5 w-5 text-amber-400" />
                            War Goals
                        </h2>
                        <div className="grid gap-2 md:grid-cols-2 lg:grid-cols-3">
                            {war.goals.map((goal) => (
                                <div
                                    key={goal.id}
                                    className={`flex items-center justify-between rounded-lg p-3 ${
                                        goal.is_achieved
                                            ? 'border border-green-500/30 bg-green-900/20'
                                            : 'bg-stone-900/50'
                                    }`}
                                >
                                    <div className="flex items-center gap-2">
                                        {goal.is_achieved ? (
                                            <Trophy className="h-4 w-4 text-green-400" />
                                        ) : (
                                            <Target className="h-4 w-4 text-stone-400" />
                                        )}
                                        <span className={`font-pixel text-xs ${goal.is_achieved ? 'text-green-300' : 'text-stone-300'}`}>
                                            {goalTypeLabels[goal.goal_type] || goal.goal_type.replace('_', ' ')}
                                        </span>
                                    </div>
                                    <span className={`font-pixel text-xs ${goal.is_achieved ? 'text-green-400' : 'text-amber-400'}`}>
                                        +{goal.war_score_value}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Main Grid: Battles and Sieges */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Active Sieges */}
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                            <Castle className="h-5 w-5 text-purple-400" />
                            Active Sieges
                            {war.active_sieges && war.active_sieges.length > 0 && (
                                <span className="ml-2 font-pixel text-xs text-stone-400">
                                    ({war.active_sieges.length})
                                </span>
                            )}
                        </h2>

                        {war.active_sieges && war.active_sieges.length > 0 ? (
                            <div className="space-y-2">
                                {war.active_sieges.map((siege) => {
                                    const siegeStatus = siegeStatusColors[siege.status] || { text: 'text-stone-400', label: siege.status };
                                    return (
                                        <Link
                                            key={siege.id}
                                            href={`/warfare/sieges/${siege.id}`}
                                            className="block rounded-lg bg-stone-900/50 p-3 transition hover:bg-stone-900/70"
                                        >
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    <Castle className="h-4 w-4 text-purple-400" />
                                                    <span className="font-pixel text-sm text-white">{siege.target_name}</span>
                                                </div>
                                                <span className={`font-pixel text-[10px] ${siegeStatus.text}`}>
                                                    {siegeStatus.label}
                                                    {siege.has_breach && ' (Breached)'}
                                                </span>
                                            </div>
                                            <div className="mt-2 grid grid-cols-3 gap-2 font-pixel text-[10px]">
                                                <div>
                                                    <span className="text-stone-400">Day: </span>
                                                    <span className="text-white">{siege.days_besieged}</span>
                                                </div>
                                                <div>
                                                    <span className="text-stone-400">Garrison: </span>
                                                    <span className="text-white">{siege.garrison_strength}</span>
                                                </div>
                                                <div>
                                                    <span className="text-stone-400">Morale: </span>
                                                    <span className={siege.garrison_morale >= 50 ? 'text-green-400' : siege.garrison_morale >= 25 ? 'text-yellow-400' : 'text-red-400'}>
                                                        {siege.garrison_morale}%
                                                    </span>
                                                </div>
                                            </div>
                                        </Link>
                                    );
                                })}
                            </div>
                        ) : (
                            <p className="font-pixel text-sm text-stone-500">
                                No active sieges
                            </p>
                        )}

                        {/* All Sieges Summary */}
                        {all_sieges.length > (war.active_sieges?.length || 0) && (
                            <div className="mt-4 border-t border-stone-700 pt-3">
                                <div className="font-pixel text-[10px] text-stone-400">
                                    Total sieges in this war: {all_sieges.length}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Recent Battles */}
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                            <Swords className="h-5 w-5 text-red-400" />
                            Battles
                            {all_battles.length > 0 && (
                                <span className="ml-2 font-pixel text-xs text-stone-400">
                                    ({all_battles.length})
                                </span>
                            )}
                        </h2>

                        {all_battles.length > 0 ? (
                            <div className="space-y-2">
                                {all_battles.slice(0, 5).map((battle) => {
                                    const battleStatus = battleStatusColors[battle.status] || { text: 'text-stone-400', label: battle.status };
                                    return (
                                        <div
                                            key={battle.id}
                                            className="rounded-lg bg-stone-900/50 p-3"
                                        >
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    <MapPin className="h-4 w-4 text-stone-400" />
                                                    <span className="font-pixel text-sm text-white">
                                                        Battle at {battle.location_name}
                                                    </span>
                                                </div>
                                                <span className={`font-pixel text-[10px] ${battleStatus.text}`}>
                                                    {battleStatus.label}
                                                </span>
                                            </div>
                                            <div className="mt-2 grid grid-cols-2 gap-4 font-pixel text-[10px]">
                                                <div className="text-red-300">
                                                    <span className="text-stone-400">Attackers: </span>
                                                    {battle.attacker_troops_start} troops
                                                    {battle.attacker_casualties > 0 && (
                                                        <span className="text-red-500"> (-{battle.attacker_casualties})</span>
                                                    )}
                                                </div>
                                                <div className="text-blue-300">
                                                    <span className="text-stone-400">Defenders: </span>
                                                    {battle.defender_troops_start} troops
                                                    {battle.defender_casualties > 0 && (
                                                        <span className="text-blue-500"> (-{battle.defender_casualties})</span>
                                                    )}
                                                </div>
                                            </div>
                                            {battle.started_at && (
                                                <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                                    {new Date(battle.started_at).toLocaleDateString()}
                                                    {battle.ended_at && ` - ${new Date(battle.ended_at).toLocaleDateString()}`}
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                                {all_battles.length > 5 && (
                                    <div className="text-center font-pixel text-xs text-stone-500">
                                        + {all_battles.length - 5} more battles
                                    </div>
                                )}
                            </div>
                        ) : (
                            <p className="font-pixel text-sm text-stone-500">
                                No battles fought yet
                            </p>
                        )}
                    </div>
                </div>

                {/* War Statistics Summary */}
                <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                    <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                        <Users className="h-5 w-5 text-stone-400" />
                        War Statistics
                    </h2>
                    <div className="grid gap-4 md:grid-cols-4">
                        <div className="rounded-lg bg-stone-900/50 p-3 text-center">
                            <div className="font-pixel text-2xl text-white">{war.days_active}</div>
                            <div className="font-pixel text-[10px] text-stone-400">Days Active</div>
                        </div>
                        <div className="rounded-lg bg-stone-900/50 p-3 text-center">
                            <div className="font-pixel text-2xl text-white">{war.participant_count}</div>
                            <div className="font-pixel text-[10px] text-stone-400">Participants</div>
                        </div>
                        <div className="rounded-lg bg-stone-900/50 p-3 text-center">
                            <div className="font-pixel text-2xl text-white">{war.battle_count}</div>
                            <div className="font-pixel text-[10px] text-stone-400">Battles Fought</div>
                        </div>
                        <div className="rounded-lg bg-stone-900/50 p-3 text-center">
                            <div className="font-pixel text-2xl text-red-400">{totalAttackerCasualties + totalDefenderCasualties}</div>
                            <div className="font-pixel text-[10px] text-stone-400">Total Casualties</div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage, router } from '@inertiajs/react';
import {
    Mail, Check, X, Users, Calendar, MapPin,
    ArrowRight, UserPlus, Info, ShieldAlert,
    Clock, Trophy
} from 'lucide-react';

export default function Invitations({ invitations }) {
    const { auth } = usePage().props;
    const user = auth.user;
    const [processing, setProcessing] = useState(false);

    const handleAccept = (type, token, raceId) => {
        setProcessing(true);
        const routeName = type === 'temp_team' ? 'invitation.accept' : (type === 'team' ? 'team.invitations.accept' : 'clubs.invitations.accept');

        router.post(route(routeName, token), {}, {
            onFinish: () => setProcessing(false),
        });
    };

    const handleReject = (type, token) => {
        setProcessing(true);
        const routeName = type === 'temp_team' ? 'invitation.reject' : (type === 'team' ? 'team.invitations.reject' : 'clubs.invitations.reject');

        router.post(route(routeName, token), {}, {
            onFinish: () => setProcessing(false),
        });
    };

    const hasInvitations = invitations.temp_team.length > 0 || invitations.team.length > 0 || invitations.club.length > 0;

    return (
        <AuthenticatedLayout>
            <Head title="Mes invitations" />

            <div className="py-12 bg-gray-50/50 min-h-screen">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-10 text-center">
                        <h1 className="text-4xl font-black text-blue-900 italic uppercase tracking-tighter leading-none mb-4">
                            Mes Invitations
                        </h1>
                        <p className="text-lg text-blue-800/60 font-medium">
                            Gérez vos invitations à rejoindre des équipes ou des clubs.
                        </p>
                    </div>

                    {!hasInvitations ? (
                        <div className="max-w-md mx-auto bg-white rounded-[2.5rem] p-12 text-center shadow-sm border border-blue-50">
                            <div className="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-6">
                                <Mail className="w-10 h-10 text-blue-300" />
                            </div>
                            <h3 className="text-xl font-black text-blue-900 uppercase italic mb-2">Aucune invitation</h3>
                            <p className="text-blue-800/60 font-medium mb-8">
                                Vous n'avez aucune invitation en attente pour le moment.
                            </p>
                            <Link
                                href={route('raids.index')}
                                className="inline-flex items-center gap-2 px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-black text-xs uppercase tracking-widest rounded-2xl transition-all shadow-lg shadow-blue-200"
                            >
                                <Trophy className="w-4 h-4" />
                                Découvrir les courses
                            </Link>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            {/* Temporary Team Invitations */}
                            {invitations.temp_team.map((inv) => (
                                <InvitationCard
                                    key={inv.id}
                                    invitation={inv}
                                    type="temp_team"
                                    onAccept={handleAccept}
                                    onReject={handleReject}
                                    processing={processing}
                                />
                            ))}

                            {/* Permanent Team Invitations */}
                            {invitations.team.map((inv) => (
                                <InvitationCard
                                    key={inv.id}
                                    invitation={inv}
                                    type="team"
                                    onAccept={handleAccept}
                                    onReject={handleReject}
                                    processing={processing}
                                />
                            ))}

                            {/* Club Invitations */}
                            {invitations.club.map((inv) => (
                                <ClubInvitationCard
                                    key={inv.id}
                                    invitation={inv}
                                    onAccept={handleAccept}
                                    onReject={handleReject}
                                    processing={processing}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function InvitationCard({ invitation, type, onAccept, onReject, processing }) {
    const race = type === 'temp_team' ? invitation.registration?.race : invitation.race;
    const inviter = invitation.inviter;
    const members = type === 'temp_team'
        ? (invitation.registration?.temporary_team_data || [])
        : (invitation.team?.users || []);

    return (
        <div className="bg-white rounded-[2.5rem] overflow-hidden shadow-sm border border-blue-50 flex flex-col hover:shadow-xl hover:shadow-blue-900/5 transition-all duration-500">
            {/* Header / Race Info */}
            <div className="p-8 bg-gradient-to-br from-blue-900 to-blue-800 text-white relative overflow-hidden">
                <div className="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                    <Trophy className="w-32 h-32 -rotate-12" />
                </div>

                <div className="relative z-10 flex flex-col h-full justify-between gap-4">
                    <div className="space-y-2">
                        <div className="flex items-center gap-2">
                            <span className="px-3 py-1 bg-emerald-500 text-white text-[10px] font-black uppercase tracking-widest rounded-full shadow-lg">
                                Invitation Course
                            </span>
                            <span className="flex items-center gap-1 text-[10px] font-bold text-blue-200 uppercase tracking-widest opacity-80">
                                <Clock className="w-3 h-3" />
                                {new Date(invitation.expires_at).toLocaleDateString('fr-FR')}
                            </span>
                        </div>
                        <h3 className="text-2xl font-black italic tracking-tighter uppercase leading-none">
                            {race?.race_name || 'Épreuve'}
                        </h3>
                    </div>

                    <div className="flex flex-wrap gap-4 text-[10px] font-bold text-blue-100/60 uppercase tracking-widest">
                        <div className="flex items-center gap-1.5">
                            <MapPin className="w-3 h-3 text-emerald-400" />
                            {race?.raid?.raid_location || 'Lieu à définir'}
                        </div>
                        <div className="flex items-center gap-1.5">
                            <Calendar className="w-3 h-3 text-emerald-400" />
                            {race?.race_date_start ? new Date(race.race_date_start).toLocaleDateString('fr-FR', {
                                day: 'numeric', month: 'long'
                            }) : 'Date à définir'}
                        </div>
                    </div>
                </div>
            </div>

            {/* Content / Team Info */}
            <div className="p-8 flex-1 space-y-8">
                {/* Inviter Info */}
                <div className="flex items-center gap-4 bg-gray-50/50 p-4 rounded-3xl border border-gray-100">
                    <div className="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-200">
                        <span className="text-white font-black text-lg italic">{inviter?.name[0]}</span>
                    </div>
                    <div>
                        <p className="text-xs font-black text-blue-900 uppercase italic leading-none mb-1">
                            {inviter?.name}
                        </p>
                        <p className="text-[10px] font-bold text-blue-700/40 uppercase tracking-widest">Vous invite à rejoindre son équipe</p>
                    </div>
                </div>

                {/* Team Members */}
                <div className="space-y-4">
                    <h4 className="text-[10px] font-black text-blue-900 uppercase tracking-[0.2em] flex items-center gap-2">
                        <Users className="h-4 w-4 text-emerald-500" />
                        Coéquipiers ({members.length})
                    </h4>
                    <div className="flex flex-wrap gap-2">
                        {/* Display creator first if temp_team */}
                        {type === 'temp_team' && invitation.registration?.user && (
                            <div className="flex items-center gap-2 px-3 py-2 bg-emerald-50 border border-emerald-100 rounded-xl">
                                <div className="w-6 h-6 bg-emerald-500 rounded-lg flex items-center justify-center text-[10px] font-black text-white italic">
                                    {invitation.registration.user.name[0]}
                                </div>
                                <span className="text-[10px] font-bold text-emerald-800 uppercase italic whitespace-nowrap">
                                    {invitation.registration.user.name}
                                </span>
                            </div>
                        )}
                        {members.map((member, idx) => {
                            // Skip the current user and the creator (already added)
                            const isCurrentUser = member.email === invitation.email;
                            const isCreator = type === 'temp_team' && member.user_id === invitation.registration?.user?.id;

                            if (isCurrentUser || isCreator) return null;

                            return (
                                <div key={idx} className="flex items-center gap-2 px-3 py-2 bg-blue-50 border border-blue-100 rounded-xl">
                                    <div className="w-6 h-6 bg-blue-500 rounded-lg flex items-center justify-center text-[10px] font-black text-white italic">
                                        {(member.name || member.email)[0].toUpperCase()}
                                    </div>
                                    <span className="text-[10px] font-bold text-blue-800 uppercase italic whitespace-nowrap">
                                        {member.name || member.email.split('@')[0]}
                                    </span>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>

            {/* Actions */}
            <div className="p-8 pt-0 grid grid-cols-2 gap-4">
                <button
                    onClick={() => onReject(type, type === 'temp_team' ? invitation.token : invitation.id)}
                    disabled={processing}
                    className="flex items-center justify-center gap-2 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all border border-red-100 text-red-500 hover:bg-red-50 disabled:opacity-50"
                >
                    <X className="w-4 h-4" />
                    Refuser
                </button>
                <button
                    onClick={() => onAccept(type, type === 'temp_team' ? invitation.token : invitation.id, race?.race_id)}
                    disabled={processing}
                    className="flex items-center justify-center gap-2 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all bg-emerald-500 text-white hover:bg-emerald-600 shadow-lg shadow-emerald-100 disabled:opacity-50"
                >
                    <Check className="w-4 h-4" />
                    Accepter
                </button>
            </div>
        </div>
    );
}

function ClubInvitationCard({ invitation, onAccept, onReject, processing }) {
    const club = invitation.club;
    const inviter = invitation.inviter;

    return (
        <div className="bg-white rounded-[2.5rem] overflow-hidden shadow-sm border border-blue-50 flex flex-col hover:shadow-xl hover:shadow-blue-900/5 transition-all duration-500">
            {/* Header / Club Info */}
            <div className="p-8 bg-gradient-to-br from-emerald-900 to-emerald-800 text-white relative overflow-hidden">
                <div className="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                    <Users className="w-32 h-32 -rotate-12" />
                </div>

                <div className="relative z-10 flex flex-col h-full justify-between gap-4">
                    <div className="space-y-2">
                        <span className="px-3 py-1 bg-blue-500 text-white text-[10px] font-black uppercase tracking-widest rounded-full shadow-lg">
                            Invitation Club
                        </span>
                        <h3 className="text-2xl font-black italic tracking-tighter uppercase leading-none">
                            {club?.equ_name || 'Club'}
                        </h3>
                    </div>
                </div>
            </div>

            {/* Content */}
            <div className="p-8 flex-1 space-y-8">
                <div className="flex items-center gap-4 bg-gray-50/50 p-4 rounded-3xl border border-gray-100">
                    <div className="w-12 h-12 bg-emerald-600 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-200">
                        <span className="text-white font-black text-lg italic">{inviter?.name[0]}</span>
                    </div>
                    <div>
                        <p className="text-xs font-black text-blue-900 uppercase italic leading-none mb-1">
                            {inviter?.name}
                        </p>
                        <p className="text-[10px] font-bold text-blue-700/40 uppercase tracking-widest">Vous invite à rejoindre le club</p>
                    </div>
                </div>

                <p className="text-sm text-blue-800/70 leading-relaxed font-medium">
                    Rejoindre un club vous permet de participer à des épreuves sous leurs couleurs et de bénéficier de leur encadrement.
                </p>
            </div>

            {/* Actions */}
            <div className="p-8 pt-0 grid grid-cols-2 gap-4">
                <button
                    onClick={() => onReject('club', invitation.id)}
                    disabled={processing}
                    className="flex items-center justify-center gap-2 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all border border-red-100 text-red-500 hover:bg-red-50 disabled:opacity-50"
                >
                    <X className="w-4 h-4" />
                    Refuser
                </button>
                <button
                    onClick={() => onAccept('club', invitation.id)}
                    disabled={processing}
                    className="flex items-center justify-center gap-2 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all bg-emerald-500 text-white hover:bg-emerald-600 shadow-lg shadow-emerald-100 disabled:opacity-50"
                >
                    <Check className="w-4 h-4" />
                    Accepter
                </button>
            </div>
        </div>
    );
}

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import React, { useState } from 'react';
import TeamRegistrationModal from '@/Components/TeamRegistrationModal';
import MyRegistrationModal from '@/Components/MyRegistrationModal';
import UpdatePPSModal from '@/Components/UpdatePPSModal';
import TeamPaymentModal from '@/Components/TeamPaymentModal';
import {
    Calendar, Timer, MapPin, Users, Info, ChevronRight,
    Trophy, Heart, ShieldCheck, FileText, UserCheck,
    AlertCircle, Clock, CheckCircle2, XCircle, Settings,
    CreditCard, Utensils
} from 'lucide-react';

export default function VisuRace({ auth, race, isManager, participants = [], error, errorMessage, userTeams = [], registeredByLeader = null, registeredTeam = null }) {
    const translations = usePage().props.translations?.messages || {};
    const [isTeamModalOpen, setIsTeamModalOpen] = useState(false);
    const [isMyRegistrationModalOpen, setIsMyRegistrationModalOpen] = useState(false);
    const [selectedParticipant, setSelectedParticipant] = useState(null);
    const [selectedTeamForPayment, setSelectedTeamForPayment] = useState(null);

    // Group participants by team
    const teamGroups = participants.reduce((acc, participant) => {
        if (!acc[participant.equ_id]) {
            acc[participant.equ_id] = {
                team_name: participant.equ_name,
                team_id: participant.equ_id,
                members: []
            };
        }
        acc[participant.equ_id].members.push(participant);
        return acc;
    }, {});

    const handlePPSClick = (participant) => {
        setSelectedParticipant(participant);
    };

    const handlePaymentClick = (teamId) => {
        const teamData = teamGroups[teamId];
        const teamInfo = {
            id: teamId,
            name: teamData.team_name,
            is_paid: teamData.members.every(p => p.reg_validated),
            members: teamData.members.map(p => ({
                first_name: p.first_name,
                last_name: p.last_name,
                validated: p.reg_validated,
                price: p.price,
                price_category: p.price_category
            }))
        };
        setSelectedTeamForPayment(teamInfo);
    };

    // If race not found, display error message
    if (error || !race) {
        return (
            <AuthenticatedLayout user={auth?.user}>
                <Head title="Course non trouvée" />
                <div className="py-20 bg-gray-50 min-h-screen flex items-center justify-center">
                    <div className="max-w-md w-full bg-white rounded-[2rem] p-12 shadow-xl text-center space-y-6">
                        <div className="mx-auto w-20 h-20 bg-red-50 rounded-full flex items-center justify-center">
                            <AlertCircle className="w-10 h-10 text-red-500" />
                        </div>
                        <h3 className="text-2xl font-black text-blue-900 italic uppercase italic">
                            {error || 'ÉPREUVE INTROUVABLE'}
                        </h3>
                        <p className="text-blue-700/60 font-medium leading-relaxed">
                            {errorMessage || "L'épreuve que vous recherchez n'existe pas ou a été déplacée."}
                        </p>
                        <Link
                            href="/"
                            className="inline-flex items-center px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-black text-xs uppercase tracking-widest rounded-2xl transition-all shadow-lg shadow-blue-200"
                        >
                            RETOUR À L'ACCUEIL
                        </Link>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    const formatDate = (dateString) => {
        if (!dateString) return 'Non définie';
        return new Date(dateString).toLocaleDateString('fr-FR', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
        });
    };

    const statusConfig = {
        completed: { label: 'Épreuve Terminée', icon: <CheckCircle2 className="h-4 w-4" />, color: 'bg-gray-900 text-white' },
        ongoing: { label: 'En cours', icon: <Clock className="h-4 w-4" />, color: 'bg-emerald-500 text-white' },
        planned: { label: 'À venir', icon: <Calendar className="h-4 w-4" />, color: 'bg-blue-600 text-white' }
    };

    const currentStatus = statusConfig[race.status] || statusConfig.planned;
    const userIsLog = auth.user;
    const userIsBusy = false;

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={race.title} />

            {/* Header / Hero Section */}
            <div className="bg-blue-900 pt-10 pb-16 relative overflow-hidden border-b-8 border-emerald-500">
                <div className="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                    <Trophy className="w-96 h-96 -rotate-12" />
                </div>

                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                    {/* Back Button */}
                    {race.raidId && (
                        <Link href={route('raids.show', race.raidId)} className="inline-flex items-center gap-2 text-sm font-medium text-emerald-400 hover:text-white mb-6 transition-colors">
                            <ChevronRight className="w-4 h-4 rotate-180" />
                            Retour au raid
                        </Link>
                    )}

                    <div className="flex flex-col md:flex-row md:items-start justify-between gap-8">
                        {/* Left side - Image */}
                        <div className="w-full md:w-64 flex-shrink-0">
                            <div className="aspect-video rounded-2xl overflow-hidden shadow-2xl relative bg-gradient-to-br from-blue-800 to-emerald-800 flex items-center justify-center">
                                {race.imageUrl ? (
                                    <img
                                        src={race.imageUrl}
                                        alt={race.title}
                                        className="w-full h-full object-cover"
                                    />
                                ) : (
                                    <div className="flex flex-col items-center justify-center gap-2">
                                        <Trophy className="w-12 h-12 text-white/30" />
                                        <p className="text-white/50 font-bold text-xs uppercase tracking-wider">Pas d'image</p>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Right side - Race info */}
                        <div className="flex-1 space-y-6">
                            <span className={`inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-[0.2em] shadow-lg ${currentStatus.color}`}>
                                {currentStatus.icon}
                                {currentStatus.label}
                            </span>

                            <div>
                                <h1 className="text-5xl font-black text-white italic tracking-tighter mb-4 leading-none uppercase">
                                    {race.title}
                                </h1>
                                <div className="flex flex-wrap items-center gap-6 text-blue-100/60 text-sm font-bold uppercase tracking-widest">
                                    <div className="flex items-center gap-2">
                                        <MapPin className="h-4 w-4 text-emerald-400" />
                                        {race.location}
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Calendar className="h-4 w-4 text-emerald-400" />
                                        {formatDate(race.raceDate)}
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <ShieldCheck className="h-4 w-4 text-emerald-400" />
                                        {race.difficulty}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="flex items-center gap-4">
                            {isManager && (
                                <Link href={route('races.edit', race.id)}>
                                    <button className="bg-white/10 hover:bg-white/20 text-white px-8 py-4 rounded-2xl font-black text-xs transition-all backdrop-blur-md border border-white/20 flex items-center gap-2 tracking-widest uppercase">
                                        <Settings className="h-4 w-4" />
                                        CONFIGURER
                                    </button>
                                </Link>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <div className="py-12 bg-gray-50/50 min-h-screen">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-10">
                        {/* Left Side: Description & Details */}
                        <div className="lg:col-span-8 space-y-10">
                            {/* Description Card */}
                            <div className="bg-white rounded-[2.5rem] p-10 shadow-sm border border-blue-50">
                                <h2 className="text-2xl font-black text-blue-900 italic mb-6 flex items-center gap-3 uppercase">
                                    <Info className="h-6 w-6 text-emerald-500" />
                                    Présentation de l'épreuve
                                </h2>
                                <p className="text-lg text-blue-800/70 leading-relaxed font-medium">
                                    {race.description}
                                </p>
                            </div>

                            {/* Manager Panel */}
                            {isManager && (
                                <div className="space-y-6">
                                    <div className="flex items-center justify-between">
                                        <h2 className="text-2xl font-black text-blue-900 italic uppercase">Gestion des Inscriptions</h2>
                                        <span className="bg-blue-600 text-white px-4 py-1.5 rounded-full text-xs font-black tracking-widest uppercase">
                                            {participants.length} INSCRITS
                                        </span>
                                    </div>

                                    <div className="bg-white rounded-[2.5rem] overflow-hidden shadow-sm border border-blue-50">
                                        <div className="overflow-x-auto">
                                            <table className="min-w-full divide-y divide-blue-50">
                                                <thead className="bg-blue-50/50">
                                                    <tr>
                                                        <th className="px-8 py-5 text-left text-[10px] font-black text-blue-400 uppercase tracking-[0.2em]">Participant / Équipe</th>
                                                        <th className="px-8 py-5 text-center text-[10px] font-black text-blue-400 uppercase tracking-[0.2em]">Licence</th>
                                                        <th className="px-8 py-5 text-center text-[10px] font-black text-blue-400 uppercase tracking-[0.2em]">PPS</th>
                                                        <th className="px-8 py-5 text-center text-[10px] font-black text-blue-400 uppercase tracking-[0.2em]">Paiement</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-blue-50">
                                                    {participants.map((p, idx) => (
                                                        <tr key={idx} className="hover:bg-blue-50/30 transition-colors">
                                                            <td className="px-8 py-6">
                                                                <div className="flex items-center gap-4">
                                                                    <div className="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center font-black text-blue-600 uppercase italic">
                                                                        {p.first_name[0]}{p.last_name[0]}
                                                                    </div>
                                                                    <div>
                                                                        <p className="text-sm font-black text-blue-900 uppercase italic">{p.first_name} {p.last_name}</p>
                                                                        <p className="text-[10px] text-emerald-600 font-bold uppercase tracking-widest">{p.equ_name}</p>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td className="px-8 py-6 text-center">
                                                                <div className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase ${p.is_license_valid ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'}`}>
                                                                    {p.is_license_valid ? <CheckCircle2 className="h-3 w-3" /> : <XCircle className="h-3 w-3" />}
                                                                    {p.adh_license || 'SANS'}
                                                                </div>
                                                            </td>
                                                            <td className="px-8 py-6 text-center">
                                                                {p.is_license_valid ? (
                                                                    <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase bg-gray-100 text-gray-500">
                                                                        NON REQUIS
                                                                    </div>
                                                                ) : p.is_pps_valid ? (
                                                                    <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-600">
                                                                        <CheckCircle2 className="h-3 w-3" />
                                                                        VALIDE
                                                                    </div>
                                                                ) : (
                                                                    <button 
                                                                        onClick={() => handlePPSClick(p)}
                                                                        className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase bg-red-50 text-red-600 hover:bg-red-100 transition-colors cursor-pointer"
                                                                    >
                                                                        <XCircle className="h-3 w-3" />
                                                                        REQUIS
                                                                    </button>
                                                                )}
                                                            </td>
                                                            <td className="px-8 py-6 text-center">
                                                                {p.reg_validated ? (
                                                                    <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-600">
                                                                        <CheckCircle2 className="h-3 w-3" />
                                                                        PAYÉ
                                                                    </div>
                                                                ) : (
                                                                    <button 
                                                                        onClick={() => handlePaymentClick(p.equ_id)}
                                                                        className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase bg-orange-50 text-orange-600 hover:bg-orange-100 transition-colors cursor-pointer"
                                                                    >
                                                                        <CreditCard className="h-3 w-3" />
                                                                        EN ATTENTE
                                                                    </button>
                                                                )}
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Right Side: Sidebar */}
                        <div className="lg:col-span-4 space-y-8">
                            {/* Registration Box */}
                            <div className="bg-emerald-900 rounded-[2.5rem] p-10 text-white shadow-2xl shadow-emerald-950/20 relative overflow-hidden group">
                                <div className="relative z-10 space-y-8">
                                    <div className="space-y-2">
                                        <p className="text-[10px] font-black text-emerald-400 uppercase tracking-[0.3em]">État de l'épreuve</p>
                                        <h3 className="text-3xl font-black italic uppercase leading-none">
                                            {race.status === 'completed' ? 'INSCRIPTIONS CLOSES' : race.isOpen ? 'VIVEZ L\'EXPÉRIENCE' : 'BIENTÔT DISPONIBLE'}
                                        </h3>
                                    </div>

                                    {race.isOpen && !race.isAlreadyRegistered && !userIsLog ? (
                                        <Link
                                            href={route('register', { redirect_uri: window.location.href })}
                                            className="w-full bg-emerald-500 hover:bg-emerald-400 py-5 rounded-[1.25rem] font-black text-xs tracking-[0.2em] transition-all shadow-xl shadow-emerald-950 uppercase flex items-center justify-center gap-3 text-white"
                                        >
                                            CREER MON COMPTE
                                            <ChevronRight className="h-4 w-4" />
                                        </Link>
                                    ) : race.isOpen && !race.isAlreadyRegistered && userIsBusy ? (
                                        <div className="bg-white/5 border border-white/10 p-6 rounded-3xl">
                                            <p className="text-xs font-bold text-emerald-100/60 leading-relaxed uppercase tracking-widest text-center">
                                                Vous êtes déjà inscrit a une course sur ce creneau là
                                            </p>
                                        </div>
                                    ) : (race.isOpen && race.registeredCount >= race.maxParticipants && race.maxTeams >= race.teamsCount) ? (
                                        <div className="bg-white/5 border border-white/10 p-6 rounded-3xl">
                                            <p className="text-xs font-bold text-emerald-100/60 leading-relaxed uppercase tracking-widest text-center">
                                                Le nombre maximum d'inscrits est atteint.
                                            </p>
                                        </div>
                                    ) : (!race.is_finished && race.isOpen && registeredByLeader) ? (
                                        <div className="bg-blue-50 border-2 border-blue-200 p-6 rounded-2xl">
                                                <div className="flex items-start gap-3">
                                                    <div className="bg-blue-500 rounded-full p-2 mt-0.5">
                                                        <Info className="h-4 w-4 text-white" />
                                                    </div>
                                                    <div className="flex-1">
                                                        <p className="text-xs font-black text-blue-900 uppercase tracking-widest mb-2">
                                                            Inscription par un chef d'équipe
                                                        </p>
                                                        <p className="text-sm font-medium text-blue-700 leading-relaxed">
                                                            Vous avez été inscrit par <span className="font-black">{registeredByLeader.leader_name}</span> dans l'équipe <span className="font-black italic">{registeredByLeader.team_name}</span> à cette course.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        
                                    
                                    ) : !race.is_finished && race.isOpen && !race.alreadyRegistered ? (
                                        <div className="space-y-4">
                                           
                                            <button
                                                onClick={() => setIsTeamModalOpen(true)}
                                                className="w-full bg-blue-600 hover:bg-blue-500 py-4 rounded-[1.25rem] font-black text-xs tracking-[0.2em] transition-all shadow-lg shadow-blue-900/50 uppercase flex items-center justify-center gap-3 text-white border border-blue-400/20"
                                            >
                                                S'INSCRIRE MAINTENANT
                                                <Users className="h-4 w-4" />
                                            </button>
                                        </div>
                                    ) : race.isOpen && race.alreadyRegistered ? (
                                        <button 
                                            onClick={() => setIsMyRegistrationModalOpen(true)}
                                            className="w-full bg-emerald-500 hover:bg-emerald-400 py-5 rounded-[1.25rem] font-black text-xs tracking-[0.2em] transition-all shadow-xl shadow-emerald-950 uppercase flex items-center justify-center gap-3"
                                        >
                                            VOIR MON INSCRIPTION
                                            <ChevronRight className="h-4 w-4" />
                                        </button>

                                    ) : race.status === 'completed' ? (
                                        <button className="w-full bg-white/10 hover:bg-white/20 py-5 rounded-[1.25rem] font-black text-xs tracking-[0.2em] transition-all border border-white/20 uppercase flex items-center justify-center gap-3">
                                            VOIR LES RÉSULTATS
                                            <ChevronRight className="h-4 w-4" />
                                        </button>
                                    ) : (
                                        <div className="bg-white/5 border border-white/10 p-6 rounded-3xl">
                                            <p className="text-xs font-bold text-emerald-100/60 leading-relaxed uppercase tracking-widest text-center">
                                                Revenez le {new Date(race.raceDate).toLocaleDateString()} pour l'ouverture des inscriptions.
                                            </p>
                                        </div>
                                    )}

                                    <div className="pt-8 border-t border-white/10 grid grid-cols-2 gap-6">
                                        <div>
                                            <p className="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-1">Participants</p>
                                            <div className="flex items-baseline gap-1">
                                                <span className="text-2xl font-black italic leading-none">{race.registeredCount}</span>
                                                <span className="text-xs font-bold text-white/30 uppercase">/ {race.maxParticipants}</span>
                                            </div>
                                        </div>
                                        <div>
                                            <p className="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-1">Format</p>
                                            <p className="text-sm font-black uppercase italic leading-none">{race.raceType}</p>
                                        </div>
                                    </div>
                                </div>
                                <Heart className="absolute -bottom-10 -right-10 w-48 h-48 text-white/5 -rotate-12 group-hover:scale-110 transition-transform duration-1000" />
                            </div>

                            {/* Tarifs Card */}
                            <div className="bg-white rounded-[2.5rem] p-8 border border-blue-50 shadow-sm space-y-6">
                                <h4 className="text-xs font-black text-blue-900 uppercase tracking-[0.2em] flex items-center gap-2">
                                    <CreditCard className="h-4 w-4 text-emerald-500" />
                                    LISTE DES TARIFS
                                </h4>
                                <div className="space-y-4">
                                    {[
                                        { label: 'Tarif Majeur', price: race.priceMajor, isMain: true },
                                        ...(!race.raceType === 'compétitif' ? [{ label: 'Tarif Mineur', price: race.priceMinor }] : []),
                                        { label: 'Tarif Adhérent', price: race.priceAdherent, sub: 'Licenciés club' },
                                    ].filter(t => t.price !== null && t.price !== undefined).map((t, idx) => (
                                        <div key={idx} className={`flex items-center justify-between p-4 rounded-2xl border transition-colors ${t.isMain ? 'bg-blue-900 text-white border-blue-900 shadow-xl shadow-blue-200' : 'bg-blue-50/30 border-blue-50 text-blue-900'}`}>
                                            <div>
                                                <p className={`text-[10px] font-black uppercase tracking-widest ${t.isMain ? 'text-blue-100/40' : 'text-blue-400'}`}>{t.label}</p>
                                                {t.sub && <p className={`text-[10px] font-bold ${t.isMain ? 'text-blue-200' : 'text-blue-800/40'}`}>{t.sub}</p>}
                                            </div>
                                            <span className="text-lg font-black italic">{t.price}€</span>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Équipes Info Card */}
                            <div className="bg-white rounded-[2.5rem] p-8 border border-blue-50 shadow-sm space-y-6">
                                <h4 className="text-xs font-black text-blue-900 uppercase tracking-[0.2em] flex items-center gap-2">
                                    <Users className="h-4 w-4 text-emerald-500" />
                                    INFORMATIONS SUR LES ÉQUIPES
                                </h4>
                                <div className="grid grid-cols-3 gap-4">
                                    <div className="bg-blue-50/50 p-4 rounded-2xl border border-blue-100 text-center">
                                        <p className="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">Membres Min</p>
                                        <p className="text-2xl font-black text-blue-900 italic">{race.minMembers}</p>
                                    </div>
                                    <div className="bg-blue-50/50 p-4 rounded-2xl border border-blue-100 text-center">
                                        <p className="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">Max Équipes</p>
                                        <p className="text-2xl font-black text-blue-900 italic">{race.maxTeams}</p>
                                    </div>
                                    <div className="bg-blue-50/50 p-4 rounded-2xl border border-blue-100 text-center">
                                        <p className="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">Membres Max</p>
                                        <p className="text-2xl font-black text-blue-900 italic">{race.maxMembers}</p>
                                    </div>
                                </div>
                            </div>

                            {/* Organizer Card */}
                            <div className="bg-white rounded-[2.5rem] p-8 border border-blue-50 shadow-sm space-y-6">
                                <h4 className="text-xs font-black text-blue-900 uppercase tracking-[0.2em] flex items-center gap-2">
                                    <Users className="h-4 w-4 text-emerald-500" />
                                    VOTRE RESPONSABLE
                                </h4>
                                <div className="flex items-center gap-4 bg-gray-50/50 p-4 rounded-3xl border border-gray-100">
                                    <div className="w-14 h-14 bg-emerald-600 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-200">
                                        <span className="text-white font-black text-xl italic">{race.organizer.name[0]}</span>
                                    </div>
                                    <div>
                                        <p className="text-sm font-black text-blue-900 uppercase italic leading-none mb-1">{race.organizer.name}</p>
                                        <p className="text-[10px] font-bold text-blue-700/40 uppercase tracking-widest">ORGANISATEUR</p>
                                    </div>
                                </div>
                                <button className="w-full py-4 text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] hover:bg-blue-50 rounded-2xl border border-blue-100 transition-colors">
                                    CONTACTER LE RESPONSABLE
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <TeamRegistrationModal
                isOpen={isTeamModalOpen}
                onClose={() => setIsTeamModalOpen(false)}
                teams={userTeams}
                minRunners={race.minMembers}
                maxRunners={race.maxMembers}
                raceId={race.id}
                racePrices={{
                    major: race.priceMajor,
                    minor: race.priceMinor,
                    adherent: race.priceAdherent
                }}
                isCompetitive={race.isCompetitive}
            />
            <MyRegistrationModal
                isOpen={isMyRegistrationModalOpen}
                onClose={() => setIsMyRegistrationModalOpen(false)}
                registeredTeam={registeredTeam}
                raceId={race.id}
            />
            <UpdatePPSModal
                isOpen={selectedParticipant !== null}
                onClose={() => setSelectedParticipant(null)}
                participant={selectedParticipant}
                raceId={race.id}
            />
            <TeamPaymentModal
                isOpen={selectedTeamForPayment !== null}
                onClose={() => setSelectedTeamForPayment(null)}
                team={selectedTeamForPayment}
                raceId={race.id}
            />
        </AuthenticatedLayout>
    );
}

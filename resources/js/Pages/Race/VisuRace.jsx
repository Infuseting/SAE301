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
    const [activeTab, setActiveTab] = useState('tarifs');
    const [isTeamModalOpen, setIsTeamModalOpen] = useState(false);
    const [isMyRegistrationModalOpen, setIsMyRegistrationModalOpen] = useState(false);
    const [selectedParticipant, setSelectedParticipant] = useState(null);
    const [selectedTeamForPayment, setSelectedTeamForPayment] = useState(null);

    // Handler functions for modals
    const handlePPSClick = (participant) => {
        setSelectedParticipant(participant);
    };

    const handlePaymentClick = (teamId) => {
        // Get all participants from this team
        const teamMembers = participants.filter(p => p.equ_id === teamId);
        if (teamMembers.length > 0) {
            const teamData = {
                id: teamId,
                name: teamMembers[0].equ_name,
                members: teamMembers.map(member => ({
                    id: member.id_users,
                    first_name: member.first_name,
                    last_name: member.last_name,
                    price: member.price,
                    price_category: member.price_category,
                    validated: member.reg_validated
                }))
            };
            setSelectedTeamForPayment(teamData);
        }
    };

    const handleOpenRegistration = () => {
        if (registeredTeam) {
            setIsMyRegistrationModalOpen(true);
        } else {
            setIsTeamModalOpen(true);
        }
    };

    // Check if current date is within registration period
    const isRegistrationOpen = () => {
        if (!race.registrationPeriod) return false;
        const now = new Date();
        const startDate = new Date(race.registrationPeriod.startDate);
        const endDate = new Date(race.registrationPeriod.endDate);
        return now >= startDate && now <= endDate;
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

    const formatTime = (dateString) => {
        if (!dateString) return '';
        return new Date(dateString).toLocaleTimeString('fr-FR', {
            hour: '2-digit',
            minute: '2-digit'
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

            {/* Header / Hero Section - Compact */}
            <div className="bg-blue-900 py-8 relative overflow-hidden border-b-4 border-emerald-500">
                <div className="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                    <Trophy className="w-96 h-96 -rotate-12" />
                </div>

                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                    {/* Back Button */}
                    {race.raidId && (
                        <Link href={route('raids.show', race.raidId)} className="inline-flex items-center gap-2 text-xs font-bold text-emerald-400 hover:text-white mb-4 transition-colors uppercase tracking-widest">
                            <ChevronRight className="w-4 h-4 rotate-180" />
                            Retour au raid
                        </Link>
                    )}

                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        {/* Left side - Image + Title */}
                        <div className="flex-1 flex items-start gap-4 space-y-3">
                            {/* Small Image or Placeholder */}
                            <div className="w-16 h-16 flex-shrink-0 rounded-lg overflow-hidden shadow-lg bg-gradient-to-br from-blue-800 to-emerald-800 flex items-center justify-center">
                                {race.imageUrl ? (
                                    <img
                                        src={race.imageUrl.startsWith('/storage/') ? race.imageUrl : `/storage/${race.imageUrl}`}
                                        alt={race.title}
                                        className="w-full h-full object-cover"
                                    />
                                ) : (
                                    <Trophy className="w-8 h-8 text-white/40" />
                                )}
                            </div>
                            
                            <div className="flex-1">
                                <div className="space-y-3">
                                    <span className={`inline-flex items-center gap-2 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-[0.2em] shadow-lg ${currentStatus.color}`}>
                                        {currentStatus.icon}
                                        {currentStatus.label}
                                    </span>

                                    <div>
                                        <h1 className="text-4xl font-black text-white italic tracking-tighter leading-none uppercase">
                                            {race.title}
                                        </h1>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Right side - Config button */}
                        {isManager && (
                            <Link href={route('races.edit', race.id)}>
                                <button className="bg-white/10 hover:bg-white/20 text-white px-6 py-3 rounded-xl font-black text-xs transition-all backdrop-blur-md border border-white/20 flex items-center gap-2 tracking-widest uppercase">
                                    <Settings className="h-4 w-4" />
                                    CONFIGURER
                                </button>
                            </Link>
                        )}
                    </div>
                </div>
            </div>

            <div className="py-12 bg-gray-50/50 min-h-screen">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-10">
                        {/* Left Side: Description & Details */}
                        <div className="lg:col-span-8 space-y-6">
                            {/* Essential Info Card */}
                            <div className="bg-white rounded-2xl p-6 shadow-sm border border-blue-50">
                                <div className="grid grid-cols-3 gap-4">
                                    <div className="flex items-center gap-3">
                                        <MapPin className="h-5 w-5 text-emerald-500 flex-shrink-0" />
                                        <div>
                                            <p className="text-[10px] font-black text-blue-400 uppercase tracking-widest">Lieu</p>
                                            <p className="text-sm font-bold text-blue-900">{race.raid?.location || race.location || 'Lieu à définir'}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Calendar className="h-5 w-5 text-emerald-500 flex-shrink-0" />
                                        <div>
                                            <p className="text-[10px] font-black text-blue-400 uppercase tracking-widest">Date & Heure</p>
                                            <p className="text-sm font-bold text-blue-900">
                                                {formatDate(race.raceDate)} à <span className="text-emerald-600 font-black">{formatTime(race.raceDate)}</span>
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <ShieldCheck className="h-5 w-5 text-emerald-500 flex-shrink-0" />
                                        <div>
                                            <p className="text-[10px] font-black text-blue-400 uppercase tracking-widest">Difficulté</p>
                                            <p className="text-sm font-bold text-blue-900">{race.difficulty}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Description Card - Compact */}
                            <div className="bg-white rounded-2xl p-6 shadow-sm border border-blue-50">
                                <p className="text-base text-blue-800/70 leading-relaxed font-medium">
                                    {race.description}
                                </p>
                            </div>

                            {/* Age Categories Display */}
                            {race.ageCategories && race.ageCategories.length > 0 && (
                                <div className="bg-white rounded-2xl p-6 shadow-sm border border-blue-50">
                                    <h3 className="text-sm font-black text-blue-900 uppercase tracking-widest mb-4 flex items-center gap-2">
                                        <Users className="h-5 w-5 text-emerald-500" />
                                        Catégories d'âges acceptées
                                    </h3>
                                    <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
                                        {race.ageCategories.map((category) => (
                                            <div 
                                                key={category.id}
                                                className="bg-emerald-50/50 border border-emerald-200 rounded-xl p-3 text-center hover:shadow-md transition-all"
                                            >
                                                <p className="text-xs font-black text-emerald-700 uppercase tracking-widest">{category.nom}</p>
                                                <p className="text-sm font-bold text-emerald-900 mt-1">
                                                    {category.age_min}{category.age_max ? `–${category.age_max}` : '+'} ans
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Tabs for Tarifs, Équipes, Organisateur */}
                            <div className="bg-white rounded-2xl border border-blue-50 shadow-sm overflow-hidden">
                                <div className="flex border-b border-blue-50">
                                    <button
                                        onClick={() => setActiveTab('tarifs')}
                                        className={`flex-1 px-4 py-3 text-[10px] font-black uppercase tracking-widest transition-colors ${
                                            activeTab === 'tarifs'
                                                ? 'bg-emerald-50 text-emerald-600 border-b-2 border-emerald-500'
                                                : 'text-blue-400 hover:bg-blue-50/50'
                                        }`}
                                    >
                                        <CreditCard className="h-3 w-3 inline mr-1.5" />
                                        Tarifs
                                    </button>
                                    <button
                                        onClick={() => setActiveTab('equipes')}
                                        className={`flex-1 px-4 py-3 text-[10px] font-black uppercase tracking-widest transition-colors ${
                                            activeTab === 'equipes'
                                                ? 'bg-emerald-50 text-emerald-600 border-b-2 border-emerald-500'
                                                : 'text-blue-400 hover:bg-blue-50/50'
                                        }`}
                                    >
                                        <Users className="h-3 w-3 inline mr-1.5" />
                                        Équipes
                                    </button>
                                    <button
                                        onClick={() => setActiveTab('responsable')}
                                        className={`flex-1 px-4 py-3 text-[10px] font-black uppercase tracking-widest transition-colors ${
                                            activeTab === 'responsable'
                                                ? 'bg-emerald-50 text-emerald-600 border-b-2 border-emerald-500'
                                                : 'text-blue-400 hover:bg-blue-50/50'
                                        }`}
                                    >
                                        <ShieldCheck className="h-3 w-3 inline mr-1.5" />
                                        Responsable
                                    </button>
                                </div>

                                <div className="p-6 space-y-4">
                                    {/* Tarifs Tab */}
                                    {activeTab === 'tarifs' && (
                                        <div className="space-y-3">
                                            {[
                                                { label: 'Majeur', price: race.priceMajor, isMain: true },
                                                ...(!race.raceType === 'compétitif' ? [{ label: 'Mineur', price: race.priceMinor }] : []),
                                                { label: 'Adhérent', price: race.priceAdherent, sub: 'Licenciés' },
                                            ].filter(t => t.price !== null && t.price !== undefined).map((t, idx) => (
                                                <div key={idx} className="flex items-center justify-between p-3 rounded-xl border bg-blue-50/30 border-blue-50 text-blue-900 transition-colors">
                                                    <div>
                                                        <p className="text-[9px] font-black uppercase tracking-widest text-blue-400">{t.label}</p>
                                                        {t.sub && <p className="text-[8px] font-bold text-blue-800/40">{t.sub}</p>}
                                                    </div>
                                                    <span className="text-lg font-black italic text-blue-900">{t.price}€</span>
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    {/* Équipes Tab */}
                                    {activeTab === 'equipes' && (
                                        <div className="space-y-4">
                                            {/* Participants */}
                                            <div>
                                                <p className="text-[9px] font-black text-blue-400 uppercase tracking-widest mb-3">Coureurs</p>
                                                <div className="grid grid-cols-2 gap-3">
                                                    <div className="bg-blue-50/50 p-3 rounded-lg border border-blue-100 text-center">
                                                        <p className="text-[8px] font-black text-blue-400 uppercase tracking-widest mb-1">Min</p>
                                                        <p className="text-xl font-black text-blue-900 italic">{race.minParticipants}</p>
                                                    </div>
                                                    <div className="bg-blue-50/50 p-3 rounded-lg border border-blue-100 text-center">
                                                        <p className="text-[8px] font-black text-blue-400 uppercase tracking-widest mb-1">Max</p>
                                                        <p className="text-xl font-black text-blue-900 italic">{race.maxParticipants}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Teams */}
                                            <div>
                                                <p className="text-[9px] font-black text-blue-400 uppercase tracking-widest mb-3">Équipes</p>
                                                <div className="grid grid-cols-3 gap-3">
                                                    <div className="bg-blue-50/50 p-3 rounded-lg border border-blue-100 text-center">
                                                        <p className="text-[8px] font-black text-blue-400 uppercase tracking-widest mb-1">Min</p>
                                                        <p className="text-xl font-black text-blue-900 italic">{race.minTeams}</p>
                                                    </div>
                                                    <div className="bg-blue-50/50 p-3 rounded-lg border border-blue-100 text-center">
                                                        <p className="text-[8px] font-black text-blue-400 uppercase tracking-widest mb-1">Max</p>
                                                        <p className="text-xl font-black text-blue-900 italic">{race.maxTeams}</p>
                                                    </div>
                                                    <div className="bg-blue-50/50 p-3 rounded-lg border border-blue-100 text-center">
                                                        <p className="text-[8px] font-black text-blue-400 uppercase tracking-widest mb-1">Taille Éq</p>
                                                        <p className="text-xl font-black text-blue-900 italic">{race.maxPerTeam}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Catégories d'âges Tab */}
                                    {activeTab === 'categories' && (
                                        <div className="space-y-3">
                                            {race.ageCategories && race.ageCategories.length > 0 ? (
                                                race.ageCategories.map((category) => (
                                                    <div
                                                        key={category.id}
                                                        className="p-4 rounded-xl border-2 border-emerald-300 bg-emerald-50/50 shadow-sm hover:shadow-md transition-all"
                                                    >
                                                        <div className="flex items-center justify-between">
                                                            <div>
                                                                <p className="text-sm font-black text-emerald-900 uppercase italic">{category.nom}</p>
                                                                <p className="text-xs text-emerald-700 font-bold mt-1">
                                                                    {category.age_min} {category.age_max ? `- ${category.age_max}` : '+'} ans
                                                                </p>
                                                            </div>
                                                            <div className="bg-emerald-500 rounded-full p-2">
                                                                <CheckCircle2 className="w-5 h-5 text-white" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))
                                            ) : (
                                                <p className="text-center text-blue-600 text-sm font-bold py-4">Aucune catégorie d'âge définie</p>
                                            )}
                                        </div>
                                    )}

                                    {/* Responsable Tab */}
                                    {activeTab === 'responsable' && (
                                        <div className="space-y-3">
                                            <div className="flex items-center gap-3 bg-gray-50/50 p-3 rounded-2xl border border-gray-100">
                                                <div className="w-12 h-12 bg-emerald-600 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-200 flex-shrink-0">
                                                    <span className="text-white font-black text-lg italic">{race.organizer.name[0]}</span>
                                                </div>
                                                <div>
                                                    <p className="text-xs font-black text-blue-900 uppercase italic leading-none">{race.organizer.name}</p>
                                                    <p className="text-[8px] font-bold text-blue-700/40 uppercase tracking-widest mt-0.5">ORGANISATEUR</p>
                                                </div>
                                            </div>
                                            <button className="w-full py-3 text-[9px] font-black text-blue-600 uppercase tracking-[0.15em] hover:bg-blue-50 rounded-xl border border-blue-100 transition-colors">
                                                Contacter
                                            </button>
                                        </div>
                                    )}
                                </div>
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
                            <div className="bg-emerald-900 rounded-2xl p-6 text-white shadow-2xl shadow-emerald-950/20 relative overflow-hidden group">
                                <div className="relative z-10 space-y-6">
                                    <div className="space-y-2">
                                        <p className="text-[10px] font-black text-emerald-400 uppercase tracking-[0.3em]">État de l'épreuve</p>
                                        <h3 className="text-2xl font-black italic uppercase leading-none">
                                            {race.status === 'completed' ? 'INSCRIPTIONS CLOSES' : race.isOpen ? 'VIVEZ L\'EXPÉRIENCE' : 'BIENTÔT DISPONIBLE'}
                                        </h3>
                                    </div>

                                    {!race.is_finished && race.isOpen && isRegistrationOpen() ? (
                                        <div className="space-y-3">
                                            <button 
                                                onClick={handleOpenRegistration}
                                                className="w-full bg-emerald-500 hover:bg-emerald-400 py-4 rounded-xl font-black text-xs tracking-[0.2em] transition-all shadow-xl shadow-emerald-950 uppercase flex items-center justify-center gap-3"
                                            >
                                                {registeredTeam ? 'VOIR MON INSCRIPTION' : 'S\'INSCRIRE MAINTENANT'}
                                                <ChevronRight className="h-4 w-4" />
                                            </button>
                                            {race.registrationPeriod && (
                                                <div className="bg-white/10 p-3 rounded-lg text-center space-y-2">
                                                    <p className="text-[10px] font-black text-emerald-300 uppercase tracking-widest">Périoide d'inscription</p>
                                                    <div className="flex items-center justify-center gap-2 text-xs font-bold text-emerald-100">
                                                        <Clock className="h-3 w-3" />
                                                        <span>
                                                            {new Date(race.registrationPeriod.startDate).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }).replace(' ', ', ')}
                                                        </span>
                                                    </div>
                                                    <p className="text-emerald-400 text-xs">jusqu'au</p>
                                                    <div className="flex items-center justify-center gap-2 text-xs font-bold text-emerald-100">
                                                        <Clock className="h-3 w-3" />
                                                        <span>
                                                            {new Date(race.registrationPeriod.endDate).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }).replace(' ', ', ')}
                                                        </span>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    ) : !isRegistrationOpen() && race.registrationPeriod ? (
                                        <div className="space-y-3">
                                            <button disabled className="w-full bg-gray-600 cursor-not-allowed py-4 rounded-xl font-black text-xs tracking-[0.2em] transition-all shadow-xl shadow-gray-950 uppercase flex items-center justify-center gap-3 opacity-50">
                                                INSCRIPTIONS FERMÉES
                                                <ChevronRight className="h-4 w-4" />
                                            </button>
                                            <div className="bg-white/10 p-3 rounded-lg text-center space-y-2">
                                                <p className="text-[10px] font-black text-emerald-300 uppercase tracking-widest">Périoide d'inscription</p>
                                                <div className="flex items-center justify-center gap-2 text-xs font-bold text-emerald-100">
                                                    <Clock className="h-3 w-3" />
                                                    <span>
                                                        {new Date(race.registrationPeriod.startDate).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }).replace(' ', ', ')}
                                                    </span>
                                                </div>
                                                <p className="text-emerald-400 text-xs">jusqu'au</p>
                                                <div className="flex items-center justify-center gap-2 text-xs font-bold text-emerald-100">
                                                    <Clock className="h-3 w-3" />
                                                    <span>
                                                        {new Date(race.registrationPeriod.endDate).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }).replace(' ', ', ')}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    ) : race.status === 'completed' ? (
                                        <button className="w-full bg-white/10 hover:bg-white/20 py-4 rounded-xl font-black text-xs tracking-[0.2em] transition-all border border-white/20 uppercase flex items-center justify-center gap-3">
                                            VOIR LES RÉSULTATS
                                            <ChevronRight className="h-4 w-4" />
                                        </button>
                                    ) : (
                                        <div className="bg-white/5 border border-white/10 p-4 rounded-xl">
                                            <p className="text-xs font-bold text-emerald-100/60 leading-relaxed uppercase tracking-widest text-center">
                                                {race.registrationPeriod ? 
                                                    `Inscriptions du ${new Date(race.registrationPeriod.startDate).toLocaleDateString('fr-FR')} au ${new Date(race.registrationPeriod.endDate).toLocaleDateString('fr-FR')}`
                                                    : 'Dates d\'inscription à définir'
                                                }
                                            </p>
                                        </div>
                                    )}

                                    <div className="pt-4 border-t border-white/10 grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <p className="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-1">Participants</p>
                                            <div className="flex items-baseline gap-1">
                                                <span className="text-xl font-black italic leading-none">{race.registeredCount}</span>
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
                                        <p className="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">Équipes Min</p>
                                        <p className="text-2xl font-black text-blue-900 italic">{race.minTeams}</p>
                                    </div>
                                    <div className="bg-blue-50/50 p-4 rounded-2xl border border-blue-100 text-center">
                                        <p className="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">Équipes Max</p>
                                        <p className="text-2xl font-black text-blue-900 italic">{race.maxTeams}</p>
                                    </div>
                                    <div className="bg-blue-50/50 p-4 rounded-2xl border border-blue-100 text-center">
                                        <p className="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">Taille Équipe</p>
                                        <p className="text-2xl font-black text-blue-900 italic">{race.maxPerTeam}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <TeamRegistrationModal
                isOpen={isTeamModalOpen}
                onClose={() => setIsTeamModalOpen(false)}
                teams={userTeams}
                minRunners={race.maxPerTeam}
                maxRunners={race.maxPerTeam}
                raceId={race.id}
                racePrices={{
                    major: race.priceMajor,
                    minor: race.priceMinor,
                    adherent: race.priceAdherent
                }}
                isCompetitive={race.isCompetitive}
                maxTeams={race.maxTeams}
                maxParticipants={race.maxParticipants}
                currentTeamsCount={race.teamsCount}
                currentParticipantsCount={race.registeredCount}
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

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import React, { useState } from 'react';
import {
    Calendar, Timer, MapPin, Users, Info, ChevronRight,
    Trophy, Heart, ShieldCheck, FileText, UserCheck,
    AlertCircle, Clock, CheckCircle2, XCircle, Settings,
    CreditCard, Utensils
} from 'lucide-react';
import { RegistrationModal, RegistrationViewModal } from '@/Components/Registration';

export default function VisuRace({ auth, race, isManager, participants = [], userRegistration, error, errorMessage }) {
    const translations = usePage().props.translations?.messages || {};
    const [showRegistrationModal, setShowRegistrationModal] = useState(false);
    const [showViewModal, setShowViewModal] = useState(false);

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

    return (
        <AuthenticatedLayout>
            <Head title={race.title} />

            {/* Header / Hero Section */}
            <div className="bg-blue-900 py-16 relative overflow-hidden border-b-8 border-emerald-500">
                <div className="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                    <Trophy className="w-96 h-96 -rotate-12" />
                </div>

                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="flex flex-col md:flex-row md:items-end justify-between gap-8">
                        <div className="space-y-6">
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
                            {/* Hero Image */}
                            <div className="aspect-video rounded-[3rem] overflow-hidden shadow-2xl relative group bg-gradient-to-br from-blue-50 to-emerald-50 flex items-center justify-center">
                                {race.imageUrl ? (
                                    <img
                                        src={race.imageUrl}
                                        alt={race.title}
                                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-1000"
                                    />
                                ) : (
                                    <div className="flex flex-col items-center justify-center gap-4">
                                        <Trophy className="w-24 h-24 text-blue-300 opacity-50" />
                                        <p className="text-blue-400 font-black text-lg uppercase tracking-wider">Pas d'image disponible</p>
                                    </div>
                                )}
                                <div className="absolute inset-0 bg-gradient-to-t from-blue-900/60 to-transparent opacity-60" />
                            </div>

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
                                                        <th className="px-8 py-5 text-right text-[10px] font-black text-blue-400 uppercase tracking-[0.2em]">Validation</th>
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
                                                                <div className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase ${p.is_pps_valid ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'}`}>
                                                                    {p.is_pps_valid ? <CheckCircle2 className="h-3 w-3" /> : <XCircle className="h-3 w-3" />}
                                                                    {p.pps_number ? 'VALIDE' : 'REQUIS'}
                                                                </div>
                                                            </td>
                                                            <td className="px-8 py-6 text-right">
                                                                {p.reg_validated ? (
                                                                    <span className="text-emerald-500 bg-emerald-50 p-2 rounded-xl block ml-auto w-fit">
                                                                        <UserCheck className="h-5 w-5" />
                                                                    </span>
                                                                ) : (
                                                                    <button className="text-blue-400 hover:text-blue-600 transition-colors p-2 hover:bg-blue-50 rounded-xl">
                                                                        <ChevronRight className="h-5 w-5" />
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

                                    {!race.is_finished && race.isOpen ? (
                                        userRegistration && userRegistration.status !== 'cancelled' ? (
                                            <button
                                                onClick={() => setShowViewModal(true)}
                                                className="w-full bg-white py-5 rounded-[1.25rem] font-black text-xs tracking-[0.2em] transition-all shadow-xl uppercase flex items-center justify-center gap-3 text-emerald-700"
                                            >
                                                <CheckCircle2 className="h-4 w-4" />
                                                VOIR MON INSCRIPTION
                                            </button>
                                        ) : (
                                            <button
                                                onClick={() => setShowRegistrationModal(true)}
                                                className="w-full bg-emerald-500 hover:bg-emerald-400 py-5 rounded-[1.25rem] font-black text-xs tracking-[0.2em] transition-all shadow-xl shadow-emerald-950 uppercase flex items-center justify-center gap-3"
                                            >
                                                S'INSCRIRE MAINTENANT
                                                <ChevronRight className="h-4 w-4" />
                                            </button>
                                        )
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
                                        { label: 'Tarif Mineur', price: race.priceMinor },
                                        { label: 'Tarif Adhérent Majeur', price: race.priceMajorAdherent, sub: 'Licenciés club' },
                                        { label: 'Tarif Adhérent Mineur', price: race.priceMinorAdherent, sub: 'Licenciés club' },
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
                                        <p className="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">Par Équipe Max</p>
                                        <p className="text-2xl font-black text-blue-900 italic">{race.maxPerTeam}</p>
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

            {/* Registration Modal */}
            <RegistrationModal
                isOpen={showRegistrationModal}
                onClose={() => setShowRegistrationModal(false)}
                race={race}
            />

            {/* View Registration Modal */}
            <RegistrationViewModal
                isOpen={showViewModal}
                onClose={() => setShowViewModal(false)}
                race={race}
                registration={userRegistration}
            />
        </AuthenticatedLayout>
    );
}

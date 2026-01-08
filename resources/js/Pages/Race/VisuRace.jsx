import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import React, { useState } from 'react';
import {
    Calendar, Timer, MapPin, Users, Info, ChevronRight,
    Trophy, Heart, ShieldCheck, FileText, UserCheck,
    AlertCircle, Clock, CheckCircle2, XCircle, Settings,
    CreditCard, Utensils
} from 'lucide-react';

export default function VisuRace({ auth, race, isManager, participants = [], error, errorMessage }) {
    const translations = usePage().props.translations?.messages || {};

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

            {/* Header / Hero Section - Compact with Image */}
            <div className="bg-blue-900 py-8 relative overflow-hidden border-b-8 border-emerald-500">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div className="space-y-3 flex-1">
                            <span className={`inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-[0.2em] shadow-lg ${currentStatus.color}`}>
                                {currentStatus.icon}
                                {currentStatus.label}
                            </span>

                            <div>
                                <h1 className="text-4xl font-black text-white italic tracking-tighter mb-2 leading-tight uppercase">
                                    {race.title}
                                </h1>
                                <div className="flex flex-wrap items-center gap-4 text-blue-100/60 text-xs font-bold uppercase tracking-widest">
                                    <div className="flex items-center gap-1">
                                        <MapPin className="h-3 w-3 text-emerald-400" />
                                        {race.location}
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <Calendar className="h-3 w-3 text-emerald-400" />
                                        {formatDate(race.raceDate)}
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <ShieldCheck className="h-3 w-3 text-emerald-400" />
                                        {race.difficulty}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Hero Image in Header */}
                        <div className="w-full md:w-48 h-32 rounded-2xl overflow-hidden shadow-xl flex-shrink-0 bg-gradient-to-br from-blue-50 to-emerald-50 flex items-center justify-center border-4 border-white/20">
                            {race.imageUrl ? (
                                <img
                                    src={race.imageUrl}
                                    alt={race.title}
                                    className="w-full h-full object-cover"
                                />
                            ) : (
                                <Trophy className="w-12 h-12 text-blue-300 opacity-50" />
                            )}
                        </div>

                        <div className="flex items-center gap-4">
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
            </div>

            <div className="py-8 bg-gray-50/50 min-h-screen">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                        {/* Left Side: Description & Details */}
                        <div className="lg:col-span-8 space-y-6">
                            {/* Description Card */}
                            <div className="bg-white rounded-2xl p-8 shadow-sm border border-blue-50">
                                <h2 className="text-xl font-black text-blue-900 italic mb-4 flex items-center gap-2 uppercase">
                                    <Info className="h-5 w-5 text-emerald-500" />
                                    Présentation de l'épreuve
                                </h2>
                                <p className="text-base text-blue-800/70 leading-relaxed font-medium">
                                    {race.description}
                                </p>
                            </div>

                            {/* Raid Info Card */}
                            {race.raid && (
                                <div className="bg-white rounded-2xl p-8 shadow-sm border border-blue-50 space-y-5">
                                    <h2 className="text-xl font-black text-blue-900 italic mb-4 flex items-center gap-2 uppercase">
                                        <Trophy className="h-5 w-5 text-emerald-500" />
                                        À propos du raid "{race.raid.nom}"
                                    </h2>
                                    
                                    {/* Raid Description */}
                                    <div>
                                        <h3 className="text-xs font-black text-blue-400 uppercase tracking-widest mb-2">Description</h3>
                                        <p className="text-base text-blue-800/70 leading-relaxed font-medium">
                                            {race.raid.description || "Aucune description disponible."}
                                        </p>
                                    </div>

                                    {/* Raid Details Grid */}
                                    <div className="grid grid-cols-2 gap-4">
                                        {/* Localisation */}
                                        <div className="bg-gradient-to-br from-blue-50 to-emerald-50 p-4 rounded-xl border border-blue-100">
                                            <p className="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">Localisation</p>
                                            <div className="flex items-start gap-3">
                                                <MapPin className="h-5 w-5 text-emerald-600 flex-shrink-0 mt-1" />
                                                <p className="text-sm font-bold text-blue-900">{race.raid.location}</p>
                                            </div>
                                        </div>

                                        {/* Club */}
                                        {race.raid.club && (
                                            <div className="bg-gradient-to-br from-blue-50 to-emerald-50 p-4 rounded-xl border border-blue-100">
                                                <p className="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">Club Organisateur</p>
                                                <p className="text-xs font-bold text-blue-900">{race.raid.club.nom}</p>
                                            </div>
                                        )}

                                        {/* Dates du Raid */}
                                        <div className="bg-gradient-to-br from-blue-50 to-emerald-50 p-4 rounded-xl border border-blue-100">
                                            <p className="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">Début du Raid</p>
                                            <div className="flex items-start gap-2">
                                                <Calendar className="h-4 w-4 text-emerald-600 flex-shrink-0 mt-0.5" />
                                                <p className="text-xs font-bold text-blue-900">
                                                    {new Date(race.raid.dateStart).toLocaleDateString('fr-FR', { 
                                                        weekday: 'short', 
                                                        year: '2-digit', 
                                                        month: 'short', 
                                                        day: 'numeric',
                                                        hour: '2-digit',
                                                        minute: '2-digit'
                                                    })}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="bg-gradient-to-br from-blue-50 to-emerald-50 p-4 rounded-xl border border-blue-100">
                                            <p className="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">Fin du Raid</p>
                                            <div className="flex items-start gap-2">
                                                <Calendar className="h-4 w-4 text-emerald-600 flex-shrink-0 mt-0.5" />
                                                <p className="text-xs font-bold text-blue-900">
                                                    {new Date(race.raid.dateEnd).toLocaleDateString('fr-FR', { 
                                                        weekday: 'short', 
                                                        year: '2-digit', 
                                                        month: 'short', 
                                                        day: 'numeric',
                                                        hour: '2-digit',
                                                        minute: '2-digit'
                                                    })}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Bouton vers la page du raid */}
                                    <div className="flex justify-end pt-6 border-t border-blue-100">
                                        <Link href={route('raids.show', race.raid.id)}>
                                            <button className="bg-emerald-500 hover:bg-emerald-600 text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest transition-all shadow-lg shadow-emerald-200 hover:shadow-emerald-300 flex items-center gap-2">
                                                <Trophy className="h-4 w-4" />
                                                Voir le Raid Complet
                                            </button>
                                        </Link>
                                    </div>
                                </div>
                            )}

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
                                        <button className="w-full bg-emerald-500 hover:bg-emerald-400 py-5 rounded-[1.25rem] font-black text-xs tracking-[0.2em] transition-all shadow-xl shadow-emerald-950 uppercase flex items-center justify-center gap-3">
                                            S'INSCRIRE MAINTENANT
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
                            <div className="bg-white rounded-2xl p-6 border border-blue-50 shadow-sm space-y-4">
                                <h4 className="text-xs font-black text-blue-900 uppercase tracking-[0.2em] flex items-center gap-2">
                                    <CreditCard className="h-4 w-4 text-emerald-500" />
                                    TARIFS
                                </h4>
                                <div className="space-y-3">
                                    {[
                                        { label: 'Tarif Majeur', price: race.priceMajor, isMain: true },
                                        { label: 'Tarif Mineur', price: race.priceMinor },
                                        { label: 'Tarif Adhérent', price: race.priceAdherent, sub: 'Licenciés club' },
                                    ].filter(t => t.price !== null && t.price !== undefined).map((t, idx) => (
                                        <div key={idx} className={`flex items-center justify-between p-3 rounded-xl border transition-colors text-sm ${t.isMain ? 'bg-blue-900 text-white border-blue-900 shadow-lg shadow-blue-200' : 'bg-blue-50/30 border-blue-50 text-blue-900'}`}>
                                            <div>
                                                <p className={`text-[9px] font-black uppercase tracking-widest ${t.isMain ? 'text-blue-100/40' : 'text-blue-400'}`}>{t.label}</p>
                                                {t.sub && <p className={`text-[9px] font-bold ${t.isMain ? 'text-blue-200' : 'text-blue-800/40'}`}>{t.sub}</p>}
                                            </div>
                                            <span className="text-base font-black italic">{t.price}€</span>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Équipes Info Card */}
                            <div className="bg-white rounded-2xl p-6 border border-blue-50 shadow-sm space-y-4">
                                <h4 className="text-xs font-black text-blue-900 uppercase tracking-[0.2em] flex items-center gap-2">
                                    <Users className="h-4 w-4 text-emerald-500" />
                                    ÉQUIPES
                                </h4>
                                <div className="grid grid-cols-3 gap-3">
                                    <div className="bg-blue-50/50 p-3 rounded-xl border border-blue-100 text-center">
                                        <p className="text-[9px] font-black text-blue-400 uppercase tracking-widest mb-1">Min</p>
                                        <p className="text-xl font-black text-blue-900 italic">{race.minTeams}</p>
                                    </div>
                                    <div className="bg-blue-50/50 p-3 rounded-xl border border-blue-100 text-center">
                                        <p className="text-[9px] font-black text-blue-400 uppercase tracking-widest mb-1">Max</p>
                                        <p className="text-xl font-black text-blue-900 italic">{race.maxTeams}</p>
                                    </div>
                                    <div className="bg-blue-50/50 p-3 rounded-xl border border-blue-100 text-center">
                                        <p className="text-[9px] font-black text-blue-400 uppercase tracking-widest mb-1">Par Équipe</p>
                                        <p className="text-xl font-black text-blue-900 italic">{race.maxPerTeam}</p>
                                    </div>
                                </div>
                            </div>

                            {/* Catégories d'âges acceptées */}
                            {race.ageCategories && race.ageCategories.length > 0 && (
                                <div className="bg-white rounded-2xl p-6 border border-blue-50 shadow-sm space-y-4">
                                    <h4 className="text-xs font-black text-blue-900 uppercase tracking-[0.2em] flex items-center gap-2">
                                        <Trophy className="h-4 w-4 text-emerald-500" />
                                        CATÉGORIES D'ÂGES
                                    </h4>
                                    <div className="grid grid-cols-2 gap-3">
                                        {race.ageCategories.map((cat, idx) => (
                                            <div key={idx} className="bg-gradient-to-br from-emerald-50 to-blue-50 p-3 rounded-xl border border-emerald-100">
                                                <p className="text-[9px] font-black text-emerald-600 uppercase tracking-widest mb-1">{cat.nom}</p>
                                                <p className="text-xs font-black text-blue-900">
                                                    {cat.age_max ? `${cat.age_min}-${cat.age_max} ans` : `${cat.age_min}+ ans`}
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

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
        </AuthenticatedLayout>
    );
}

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage, router } from '@inertiajs/react';
import React, { useState, useRef, useMemo } from 'react';
import TeamRegistrationModal from '@/Components/TeamRegistrationModal';
import MyRegistrationModal from '@/Components/MyRegistrationModal';
import UpdatePPSModal from '@/Components/UpdatePPSModal';
import TeamPaymentModal from '@/Components/TeamPaymentModal';
import TeamRegistrationCard from '@/Components/TeamRegistrationCard';
import axios from 'axios';
import {
    Calendar, Timer, MapPin, Users, Info, ChevronRight,
    Trophy, Heart, ShieldCheck, FileText, UserCheck,
    AlertCircle, Clock, CheckCircle2, XCircle, Settings,
    CreditCard, Utensils, QrCode, Download, Upload, Loader2
} from 'lucide-react';

export default function VisuRace({ auth, race, isManager, participants = [], error, errorMessage, userTeams = [], registeredByLeader = null, registeredTeam = null, racePhase = 'registration', hasResults = false }) {
    const translations = usePage().props.translations?.messages || {};
    const [activeTab, setActiveTab] = useState('tarifs');
    const [isTeamModalOpen, setIsTeamModalOpen] = useState(false);
    const [isMyRegistrationModalOpen, setIsMyRegistrationModalOpen] = useState(false);
    const [selectedParticipant, setSelectedParticipant] = useState(null);
    const [selectedTeamForPayment, setSelectedTeamForPayment] = useState(null);
    const [participantsState, setParticipantsState] = useState(participants);
    const [csvFile, setCsvFile] = useState(null);
    const [isUploading, setIsUploading] = useState(false);
    const [uploadMessage, setUploadMessage] = useState(null);
    const fileInputRef = useRef(null);

    /**
     * Group participants by team for TeamRegistrationCard component
     * @returns {Array} Array of team objects with their members
     */
    const teamsData = useMemo(() => {
        const teamMap = new Map();
        
        participantsState.forEach(p => {
            if (!teamMap.has(p.equ_id)) {
                teamMap.set(p.equ_id, {
                    id: p.equ_id,
                    name: p.equ_name,
                    dossard: p.reg_dossard,
                    reg_id: p.reg_id,
                    members: []
                });
            }
            teamMap.get(p.equ_id).members.push({
                ...p,
                user_id: p.user_id || p.id_users,
            });
        });
        
        return Array.from(teamMap.values());
    }, [participantsState]);

    // Handler functions for modals
    const handlePPSClick = (participant) => {
        setSelectedParticipant(participant);
    };

    const handlePaymentClick = (teamId) => {
        // Get all participants from this team
        const teamMembers = participantsState.filter(p => p.equ_id === teamId);
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

    const handleTogglePresence = async (regId) => {
        console.log('Toggle presence called for reg_id:', regId);
        try {
            const response = await axios.post(`/races/${race.id}/toggle-presence`, {
                reg_id: regId
            });

            console.log('Toggle response:', response.data);

            if (response.data.success) {
                console.log('Updating state for reg_id:', regId, 'to is_present:', response.data.is_present);
                // Update local state
                setParticipantsState(prevParticipants => 
                    prevParticipants.map(p => 
                        p.reg_id === regId 
                            ? { ...p, is_present: response.data.is_present }
                            : p
                    )
                );
            }
        } catch (error) {
            console.error('Error toggling presence:', error);
            if (error.response) {
                console.error('Error response:', error.response.data);
            }
        }
    };

    /**
     * Handle member update from TeamRegistrationCard component
     * @param {number} regId - Registration ID
     * @param {Object} updates - Updates to apply
     */
    const handleMemberUpdate = (regId, updates) => {
        setParticipantsState(prevParticipants => 
            prevParticipants.map(p => 
                p.reg_id === regId ? { ...p, ...updates } : p
            )
        );
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

    /**
     * Handles CSV file selection for results import
     * @param {Event} e - The file input change event
     */
    const handleCsvFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setCsvFile(file);
            setUploadMessage(null);
        }
    };

    /**
     * Handles CSV file upload for importing race results
     */
    const handleCsvUpload = async () => {
        if (!csvFile) return;
        
        setIsUploading(true);
        setUploadMessage(null);
        
        const formData = new FormData();
        formData.append('csv_file', csvFile);
        
        try {
            const response = await axios.post(route('races.results.import', race.id), formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });
            
            setUploadMessage({ type: 'success', text: response.data.message });
            setCsvFile(null);
            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
            // Reload page to reflect new results
            router.reload();
        } catch (error) {
            const errorMsg = error.response?.data?.error || 'Erreur lors de l\'import du fichier';
            setUploadMessage({ type: 'error', text: errorMsg });
        } finally {
            setIsUploading(false);
        }
    };

    /**
     * Returns the message to display based on the current race phase
     * @returns {Object|null} Phase message config or null if no message needed
     */
    const getPhaseMessage = () => {
        switch (racePhase) {
            case 'pre_race':
                return {
                    icon: <Clock className="h-5 w-5" />,
                    title: 'En attente du début de la course',
                    text: 'La période d\'inscription est terminée. La course débutera bientôt.',
                    color: 'bg-amber-50 border-amber-200 text-amber-800'
                };
            case 'racing':
                return {
                    icon: <Timer className="h-5 w-5" />,
                    title: 'Course en cours',
                    text: 'La course est actuellement en cours. Les résultats seront disponibles après la fin.',
                    color: 'bg-blue-50 border-blue-200 text-blue-800'
                };
            case 'post_race':
                if (!hasResults) {
                    return {
                        icon: <AlertCircle className="h-5 w-5" />,
                        title: 'En attente des résultats',
                        text: 'La course est terminée. Les résultats n\'ont pas encore été publiés.',
                        color: 'bg-gray-50 border-gray-200 text-gray-700'
                    };
                }
                return null;
            default:
                return null;
        }
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

                        {/* Right side - Action buttons */}
                        {isManager && (
                            <div className="flex gap-3">
                                <a href={`/races/${race.id}/scanner`} target="_blank" rel="noopener noreferrer">
                                    <button className="bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-3 rounded-xl font-black text-xs transition-all shadow-lg flex items-center gap-2 tracking-widest uppercase">
                                        <QrCode className="h-4 w-4" />
                                        SCANNER
                                    </button>
                                </a>
                                <Link href={route('races.edit', race.id)}>
                                    <button className="bg-white/10 hover:bg-white/20 text-white px-6 py-3 rounded-xl font-black text-xs transition-all backdrop-blur-md border border-white/20 flex items-center gap-2 tracking-widest uppercase">
                                        <Settings className="h-4 w-4" />
                                        CONFIGURER
                                    </button>
                                </Link>
                            </div>
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

                            {/* Age Categories (Competitive) or Age Rules (Leisure) */}
                            {race.isCompetitive ? (
                                // Competitive: Show age categories
                                race.ageCategories && race.ageCategories.length > 0 && (
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
                                )
                            ) : (
                                // Leisure: Show age rules (A, B, C)
                                (race.leisureAgeMin !== null || race.leisureAgeIntermediate !== null || race.leisureAgeSupervisor !== null) && (
                                    <div className="bg-white rounded-2xl p-6 shadow-sm border border-blue-50">
                                        <h3 className="text-sm font-black text-blue-900 uppercase tracking-widest mb-4 flex items-center gap-2">
                                            <Users className="h-5 w-5 text-emerald-500" />
                                            Règles d'âge
                                        </h3>
                                        <div className="space-y-4">
                                            <div className="grid grid-cols-3 gap-3">
                                                <div className="bg-blue-50/50 border border-blue-200 rounded-xl p-4 text-center">
                                                    <p className="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1">Âge minimum</p>
                                                    <p className="text-2xl font-black text-blue-900 italic">{race.leisureAgeMin ?? '—'}</p>
                                                    <p className="text-[10px] font-bold text-blue-600 mt-1">ans</p>
                                                </div>
                                                <div className="bg-amber-50/50 border border-amber-200 rounded-xl p-4 text-center">
                                                    <p className="text-[10px] font-black text-amber-500 uppercase tracking-widest mb-1">Seuil intermédiaire</p>
                                                    <p className="text-2xl font-black text-amber-900 italic">{race.leisureAgeIntermediate ?? '—'}</p>
                                                    <p className="text-[10px] font-bold text-amber-600 mt-1">ans</p>
                                                </div>
                                                <div className="bg-emerald-50/50 border border-emerald-200 rounded-xl p-4 text-center">
                                                    <p className="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-1">Âge accompagnateur</p>
                                                    <p className="text-2xl font-black text-emerald-900 italic">{race.leisureAgeSupervisor ?? '—'}</p>
                                                    <p className="text-[10px] font-bold text-emerald-600 mt-1">ans</p>
                                                </div>
                                            </div>
                                            <div className="bg-blue-50 border border-blue-100 rounded-xl p-4">
                                                <p className="text-xs font-bold text-blue-800 leading-relaxed">
                                                    <span className="font-black">Règles :</span> Tous les participants doivent avoir au moins <span className="font-black text-blue-600">{race.leisureAgeMin} ans</span>. 
                                                    Si un membre a moins de <span className="font-black text-amber-600">{race.leisureAgeIntermediate} ans</span>, 
                                                    l'équipe doit inclure un accompagnateur d'au moins <span className="font-black text-emerald-600">{race.leisureAgeSupervisor} ans</span>.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                )
                            )}

                            {/* Tabs for Prices, Teams, Organizer */}
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

                                    {/* Teams Tab */}
                                    {activeTab === 'equipes' && (
                                        <div className="space-y-4">
                                            {/* Participants */}
                                            <div>
                                                <p className="text-[9px] font-black text-blue-400 uppercase tracking-widest mb-3">Runners</p>
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

                                    {/* Age categories Tab */}
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
                                        <div className="flex items-center gap-3">
                                            <span className="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-xs font-black tracking-widest uppercase">
                                                {teamsData.length} ÉQUIPES
                                            </span>
                                            <span className="bg-blue-600 text-white px-4 py-1.5 rounded-full text-xs font-black tracking-widest uppercase">
                                                {participants.length} INSCRITS
                                            </span>
                                        </div>
                                    </div>

                                    {/* Teams List using TeamRegistrationCard */}
                                    <div className="space-y-4">
                                        {teamsData.map((team) => (
                                            <TeamRegistrationCard
                                                key={team.id}
                                                team={team}
                                                raceId={race.id}
                                                onPPSClick={handlePPSClick}
                                                onPaymentClick={handlePaymentClick}
                                                onPresenceToggle={handleTogglePresence}
                                                onMemberUpdate={handleMemberUpdate}
                                                isCompact={true}
                                                showHeader={true}
                                            />
                                        ))}
                                    </div>

                                    {/* Empty state */}
                                    {teamsData.length === 0 && (
                                        <div className="bg-white rounded-2xl p-12 text-center border border-blue-50">
                                            <Users className="w-12 h-12 mx-auto mb-4 text-blue-200" />
                                            <p className="text-blue-400 font-bold">Aucune inscription pour le moment</p>
                                        </div>
                                    )}

                                    {/* CSV Results Management Panel */}
                                    {(racePhase === 'racing' || racePhase === 'post_race') && (
                                        <div className="bg-white rounded-[2.5rem] overflow-hidden shadow-sm border border-blue-50 p-8 space-y-6">
                                            <div className="flex items-center justify-between">
                                                <h3 className="text-lg font-black text-blue-900 italic uppercase flex items-center gap-3">
                                                    <Trophy className="h-5 w-5 text-amber-500" />
                                                    Gestion des Résultats
                                                </h3>
                                                <span className={`px-3 py-1 rounded-full text-[10px] font-black uppercase ${
                                                    hasResults 
                                                        ? 'bg-emerald-50 text-emerald-600' 
                                                        : 'bg-orange-50 text-orange-600'
                                                }`}>
                                                    {hasResults ? 'Résultats publiés' : 'En attente'}
                                                </span>
                                            </div>

                                            {/* Download Template */}
                                            <div className="space-y-3">
                                                <p className="text-xs text-blue-700/60 font-medium">
                                                    Téléchargez le template CSV avec la liste des équipes présentes, puis remplissez les temps et points.
                                                </p>
                                                <a 
                                                    href={route('races.results.export-template', race.id)}
                                                    className="w-full bg-blue-600 hover:bg-blue-700 py-3 rounded-xl font-black text-xs tracking-[0.15em] transition-all shadow-lg shadow-blue-200 uppercase flex items-center justify-center gap-2 text-white"
                                                >
                                                    <Download className="h-4 w-4" />
                                                    Télécharger le Template CSV
                                                </a>
                                            </div>

                                            {/* Upload Results */}
                                            <div className="space-y-3 pt-4 border-t border-blue-50">
                                                <p className="text-xs text-blue-700/60 font-medium">
                                                    Importez le fichier CSV complété avec les résultats de la course.
                                                </p>
                                                <div className="flex gap-2">
                                                    <input 
                                                        type="file" 
                                                        accept=".csv"
                                                        ref={fileInputRef}
                                                        onChange={handleCsvFileChange}
                                                        className="flex-1 text-sm text-blue-700 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:uppercase file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100 file:cursor-pointer cursor-pointer"
                                                    />
                                                    <button 
                                                        onClick={handleCsvUpload}
                                                        disabled={!csvFile || isUploading}
                                                        className={`px-6 py-2 rounded-xl font-black text-xs uppercase flex items-center gap-2 transition-all ${
                                                            !csvFile || isUploading
                                                                ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                                : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-200'
                                                        }`}
                                                    >
                                                        {isUploading ? (
                                                            <>
                                                                <Loader2 className="h-4 w-4 animate-spin" />
                                                                Import...
                                                            </>
                                                        ) : (
                                                            <>
                                                                <Upload className="h-4 w-4" />
                                                                Importer
                                                            </>
                                                        )}
                                                    </button>
                                                </div>
                                                {uploadMessage && (
                                                    <div className={`p-3 rounded-xl text-xs font-bold flex items-center gap-2 ${
                                                        uploadMessage.type === 'success' 
                                                            ? 'bg-emerald-50 text-emerald-600' 
                                                            : 'bg-red-50 text-red-600'
                                                    }`}>
                                                        {uploadMessage.type === 'success' ? (
                                                            <CheckCircle2 className="h-4 w-4" />
                                                        ) : (
                                                            <AlertCircle className="h-4 w-4" />
                                                        )}
                                                        {uploadMessage.text}
                                                    </div>
                                                )}
                                            </div>

                                            {/* View Results Link */}
                                            {hasResults && (
                                                <div className="pt-4 border-t border-blue-50">
                                                    <Link 
                                                        href={route('leaderboard.index', { race_id: race.id })}
                                                        className="w-full bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 py-3 rounded-xl font-black text-xs tracking-[0.15em] transition-all shadow-lg shadow-amber-200 uppercase flex items-center justify-center gap-2 text-white"
                                                    >
                                                        <Trophy className="h-4 w-4" />
                                                        Voir les Résultats
                                                    </Link>
                                                </div>
                                            )}
                                        </div>
                                    )}
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
                                            {auth?.user ? (
                                                <button 
                                                    onClick={handleOpenRegistration}
                                                    className="w-full bg-emerald-500 hover:bg-emerald-400 py-4 rounded-xl font-black text-xs tracking-[0.2em] transition-all shadow-xl shadow-emerald-950 uppercase flex items-center justify-center gap-3"
                                                >
                                                    {registeredTeam ? 'VOIR MON INSCRIPTION' : 'S\'INSCRIRE MAINTENANT'}
                                                    <ChevronRight className="h-4 w-4" />
                                                </button>
                                            ) : (
                                                <Link 
                                                    href={route('login', { redirect_uri: window.location.href })}
                                                    className="w-full bg-blue-600 hover:bg-blue-500 py-4 rounded-xl font-black text-xs tracking-[0.2em] transition-all shadow-xl shadow-blue-950 uppercase flex items-center justify-center gap-3"
                                                >
                                                    SE CONNECTER À MON COMPTE
                                                    <ChevronRight className="h-4 w-4" />
                                                </Link>
                                            )}
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
                                    ) : race.status === 'completed' || hasResults ? (
                                        <div className="space-y-3">
                                            <Link 
                                                href={route('leaderboard.index', { race_id: race.id })}
                                                className="w-full bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 py-4 rounded-xl font-black text-xs tracking-[0.2em] transition-all shadow-xl shadow-amber-950/50 uppercase flex items-center justify-center gap-3"
                                            >
                                                <Trophy className="h-4 w-4" />
                                                VOIR LES RÉSULTATS
                                                <ChevronRight className="h-4 w-4" />
                                            </Link>
                                            <a 
                                                href={route('races.results.download', race.id)}
                                                className="w-full bg-white/10 hover:bg-white/20 py-3 rounded-xl font-bold text-[10px] tracking-[0.15em] transition-all border border-white/20 uppercase flex items-center justify-center gap-2"
                                            >
                                                <Download className="h-3 w-3" />
                                                Télécharger les résultats (CSV)
                                            </a>
                                        </div>
                                    ) : racePhase !== 'registration' ? (
                                        <div className="space-y-3">
                                            {(() => {
                                                const phaseMsg = getPhaseMessage();
                                                if (phaseMsg) {
                                                    return (
                                                        <div className="bg-white/10 p-4 rounded-xl border border-white/20">
                                                            <div className="flex items-center gap-3 mb-2">
                                                                {phaseMsg.icon}
                                                                <p className="text-sm font-black uppercase">{phaseMsg.title}</p>
                                                            </div>
                                                            <p className="text-xs text-emerald-100/70">{phaseMsg.text}</p>
                                                        </div>
                                                    );
                                                }
                                                return null;
                                            })()}
                                        </div>
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
                                                <span className="text-xl font-black italic leading-none">{participants.length}</span>
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

                            {/* Teams Info Card */}
                            <div className="bg-white rounded-[2.5rem] p-8 border border-blue-50 shadow-sm space-y-6">
                                <h4 className="text-xs font-black text-blue-900 uppercase tracking-[0.2em] flex items-center gap-2">
                                    <Users className="h-4 w-4 text-emerald-500" />
                                    TEAM INFORMATION
                                </h4>
                                <div className="grid grid-cols-3 gap-4">
                                    <div className="bg-blue-50/50 p-4 rounded-2xl border border-blue-100 text-center">
                                        <p className="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">Min Teams</p>
                                        <p className="text-2xl font-black text-blue-900 italic">{race.minTeams}</p>
                                    </div>
                                    <div className="bg-blue-50/50 p-4 rounded-2xl border border-blue-100 text-center">
                                        <p className="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">Max Teams</p>
                                        <p className="text-2xl font-black text-blue-900 italic">{race.maxTeams}</p>
                                    </div>
                                    <div className="bg-blue-50/50 p-4 rounded-2xl border border-blue-100 text-center">
                                        <p className="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">Team Size</p>
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
                minRunners={race.minPerTeam}
                maxRunners={race.maxPerTeam}
                raceId={race.id}
                racePrices={{
                    major: race.priceMajor,
                    minor: race.priceMinor,
                    adherent: race.priceAdherent
                }}
                isCompetitive={race.isCompetitive}
                ageCategories={race.ageCategories || []}
                leisureAgeMin={race.leisureAgeMin}
                leisureAgeIntermediate={race.leisureAgeIntermediate}
                leisureAgeSupervisor={race.leisureAgeSupervisor}
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

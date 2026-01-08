import React, { useState, useMemo } from 'react';
import { useForm, Link } from '@inertiajs/react';
import { X, Search, Users, UserPlus, Check, AlertCircle } from 'lucide-react';
import Modal from '@/Components/Modal'; // Assuming generic Modal exists, or I'll use a simple fixed div overlay if not

export default function TeamRegistrationModal({ isOpen, onClose, teams = [], minRunners, maxRunners, raceId, racePrices = {}, isCompetitive = false, maxTeams = 100, maxParticipants = 100, currentTeamsCount = 0, currentParticipantsCount = 0 }) {
    console.log('TeamRegistrationModal received teams:', teams);
    const [searchQuery, setSearchQuery] = useState('');
    const { data, setData, post, processing, errors, reset } = useForm({
        team_id: null,
    });

    // Vérifier si les limites sont atteintes
    const isTeamsLimitReached = currentTeamsCount >= maxTeams;
    const isParticipantsLimitReached = currentParticipantsCount >= maxParticipants;

    const filteredTeams = useMemo(() => {
        return teams.filter(team =>
            team.name.toLowerCase().includes(searchQuery.toLowerCase())
        );
    }, [teams, searchQuery]);

    const handleSelectTeam = (teamId) => {
        setData('team_id', teamId);
    };

    const selectedTeam = teams.find(t => t.id === data.team_id);

    // Calculate total runners
    const currentRunners = selectedTeam ? selectedTeam.members_count : 0;
    const totalRunners = currentRunners;

    const isValid = selectedTeam && totalRunners >= minRunners && totalRunners <= maxRunners;

    const handleSubmit = (e) => {
        e.preventDefault();
        if (!isValid) return;

        post(route('race.registerTeam', raceId), {
            onSuccess: () => {
                onClose();
                reset();
            },
        });
    };

    return (
        <Modal show={isOpen} onClose={onClose} maxWidth="2xl">
            <div className="bg-white rounded-2xl overflow-hidden shadow-xl transform transition-all">
                {/* Header */}
                <div className="bg-blue-900 p-6 flex items-center justify-between">
                    <h3 className="text-xl font-black text-white italic uppercase tracking-wider flex items-center gap-3">
                        <Users className="w-6 h-6 text-emerald-400" />
                        Inscription par équipe
                    </h3>
                    <button onClick={onClose} className="text-blue-200 hover:text-white transition-colors">
                        <X className="w-6 h-6" />
                    </button>
                </div>

                <div className="p-8 space-y-8">
                    {/* Message d'alerte si limites atteintes */}
                    {(isTeamsLimitReached || isParticipantsLimitReached) && (
                        <div className="bg-red-50 border-2 border-red-200 rounded-2xl p-6 flex items-start gap-4">
                            <AlertCircle className="w-6 h-6 text-red-500 flex-shrink-0 mt-0.5" />
                            <div className="space-y-2">
                                <h4 className="font-black text-red-900 uppercase text-sm">Inscriptions complètes</h4>
                                <p className="text-sm text-red-700 font-medium leading-relaxed">
                                    {isTeamsLimitReached && `Le nombre maximum d'équipes (${maxTeams}) est atteint.`}
                                    {isParticipantsLimitReached && !isTeamsLimitReached && `Le nombre maximum de participants (${maxParticipants}) est atteint.`}
                                    {isTeamsLimitReached && isParticipantsLimitReached && ` Les deux limites sont atteintes.`}
                                    {' '}Aucune nouvelle inscription n'est possible pour le moment.
                                </p>
                            </div>
                        </div>
                    )}

                    {/* Search and Create */}
                    <div className="flex gap-4">
                        <div className="relative flex-1">
                            <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                            <input
                                type="text"
                                placeholder="Rechercher une équipe..."
                                className="w-full pl-12 pr-4 py-3 rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 font-medium"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                        </div>
                        <Link
                            href={route('team.create')}
                            className="bg-emerald-500 hover:bg-emerald-600 text-white px-6 rounded-xl font-black text-xs uppercase tracking-widest flex items-center gap-2 transition-colors shadow-lg shadow-emerald-200"
                        >
                            <UserPlus className="w-4 h-4" />
                            Créer
                        </Link>
                    </div>

                    {/* Team List */}
                    <div className="space-y-4 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                        {filteredTeams.length > 0 ? (
                            filteredTeams.map(team => {
                                const currentCount = team.members_count;

                                const isValid = currentCount >= minRunners && currentCount <= maxRunners;
                                
                                // Vérifier si l'ajout de cette équipe dépasserait les limites
                                const wouldExceedTeamLimit = isTeamsLimitReached;
                                const wouldExceedParticipantLimit = (currentParticipantsCount + currentCount) > maxParticipants;
                                const isBlocked = wouldExceedTeamLimit || wouldExceedParticipantLimit;
                                
                                const isCompatible = isValid && !isBlocked;

                                let statusMessage = "";
                                if (isBlocked) {
                                    if (wouldExceedTeamLimit) {
                                        statusMessage = `Limite d'équipes atteinte (${currentTeamsCount}/${maxTeams})`;
                                    } else if (wouldExceedParticipantLimit) {
                                        statusMessage = `Limite de participants atteinte (${currentParticipantsCount + currentCount} > ${maxParticipants})`;
                                    }
                                } else if (!isValid) {
                                    if (minRunners === maxRunners) {
                                        statusMessage = `L'équipe doit avoir exactement ${maxRunners} membres (actuellement ${currentCount})`;
                                    } else {
                                        statusMessage = `L'équipe doit avoir entre ${minRunners} et ${maxRunners} membres (actuellement ${currentCount})`;
                                    }
                                }

                                return (
                                    <div
                                        key={team.id}
                                        onClick={() => isCompatible && handleSelectTeam(team.id)}
                                        title={statusMessage || "Équipe éligible"}
                                        className={`p-4 rounded-xl border-2 transition-all flex items-center justify-between group relative 
                                        ${!isCompatible ? 'opacity-50 grayscale cursor-not-allowed bg-gray-50 border-gray-100' : 'cursor-pointer'}
                                        ${data.team_id === team.id
                                                ? 'border-blue-500 bg-blue-50/50'
                                                : isCompatible ? 'border-gray-100 hover:border-blue-200 hover:bg-gray-50' : ''
                                            }`}
                                    >
                                        <div className="flex items-center gap-4">
                                            <div className={`w-10 h-10 rounded-lg flex items-center justify-center font-black text-lg ${data.team_id === team.id ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-400 group-hover:bg-blue-100 group-hover:text-blue-600'
                                                }`}>
                                                {team.name[0]}
                                            </div>
                                            <div>
                                                <h4 className={`font-black uppercase italic ${data.team_id === team.id ? 'text-blue-900' : 'text-gray-700'}`}>
                                                    {team.name}
                                                </h4>
                                                <p className="text-xs font-bold text-gray-400 uppercase tracking-wider">
                                                    {team.members_count} Membres
                                                    {!isCompatible && <span className="text-red-400 ml-2 normal-case tracking-normal">- {statusMessage}</span>}
                                                </p>
                                            </div>
                                        </div>

                                        {data.team_id === team.id && (
                                            <div className="bg-blue-500 text-white p-1 rounded-full">
                                                <Check className="w-4 h-4" />
                                            </div>
                                        )}
                                    </div>
                                );
                            })
                        ) : (
                            <div className="text-center py-8 text-gray-400">
                                <Users className="w-12 h-12 mx-auto mb-3 opacity-20" />
                                <p className="font-medium">Aucune équipe trouvée</p>
                            </div>
                        )}
                    </div>

                    {/* Options & Validation */}
                    {selectedTeam && (
                        <div className="bg-gray-50 rounded-2xl p-6 space-y-6 border border-gray-100">
                            <div className="grid grid-cols-2 gap-6">
                                <div>
                                    <p className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Coureurs</p>
                                    <div className={`flex items-baseline gap-2 ${isValid ? 'text-emerald-600' : 'text-red-500'}`}>
                                        <span className="text-3xl font-black italic">{totalRunners}</span>
                                        <span className="text-xs font-bold uppercase overflow-visible whitespace-nowrap">
                                            / {minRunners} min - {maxRunners} max
                                        </span>
                                    </div>
                                </div>
                                
                                {racePrices.major && (
                                    <div>
                                        <p className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Prix Estimé</p>
                                        <div className="flex items-baseline gap-2 text-blue-600">
                                            <span className="text-3xl font-black italic">
                                                {(() => {
                                                    if (!selectedTeam) return racePrices.major * totalRunners;
                                                    
                                                    const licensedCount = selectedTeam.licensed_members_count || 0;
                                                    const nonLicensedCount = totalRunners - licensedCount;
                                                    
                                                    const licensedPrice = racePrices.adherent ? licensedCount * racePrices.adherent : 0;
                                                    const nonLicensedPrice = nonLicensedCount * racePrices.major;
                                                    
                                                    return licensedPrice + nonLicensedPrice;
                                                })()}
                                            </span>
                                            <span className="text-xs font-bold uppercase">€</span>
                                        </div>
                                        <p className="text-[9px] text-gray-500 font-medium mt-1">
                                            {(() => {
                                                if (!selectedTeam) return `Base tarif majeur (${racePrices.major}€/pers)`;
                                                
                                                const licensedCount = selectedTeam.licensed_members_count || 0;
                                                const nonLicensedCount = totalRunners - licensedCount;
                                                
                                                if (licensedCount > 0 && nonLicensedCount > 0) {
                                                    return `${licensedCount} licencié(s) à ${racePrices.adherent}€ + ${nonLicensedCount} non-licencié(s) à ${racePrices.major}€`;
                                                } else if (licensedCount > 0) {
                                                    return `Tous licenciés (${racePrices.adherent}€/pers)`;
                                                } else {
                                                    return `Tarif majeur (${racePrices.major}€/pers)`;
                                                }
                                            })()}
                                        </p>
                                    </div>
                                )}
                            </div>

                            <div className="flex items-center justify-between pt-4 border-t border-gray-200">
                                <div className="space-y-1">
                                    {racePrices.adherent && (
                                        <p className="text-xs text-gray-600">
                                            <span className="font-black text-emerald-600">{racePrices.adherent}€</span> si licencié
                                        </p>
                                    )}
                                    {!isCompetitive && racePrices.minor && (
                                        <p className="text-xs text-gray-600">
                                            <span className="font-black text-blue-600">{racePrices.minor}€</span> si mineur
                                        </p>
                                    )}
                                </div>

                                {!isValid && (
                                    <div className="flex items-center gap-2 text-red-500 text-xs font-bold max-w-[50%] text-right bg-red-50 px-3 py-2 rounded-lg">
                                        <AlertCircle className="w-4 h-4 flex-shrink-0" />
                                        <span>
                                            {minRunners === maxRunners 
                                                ? `L'équipe doit avoir exactement ${maxRunners} membres.`
                                                : `L'équipe doit avoir entre ${minRunners} et ${maxRunners} membres.`}
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Actions */}
                    <div className="flex justify-end gap-3 pt-4">
                        <button
                            onClick={onClose}
                            className="px-6 py-3 font-bold text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition-colors uppercase text-xs tracking-widest"
                        >
                            Annuler
                        </button>
                        <button
                            onClick={handleSubmit}
                            disabled={!isValid || processing}
                            className={`px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all shadow-lg flex items-center gap-2 ${isValid && !processing
                                ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-blue-200'
                                : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                }`}
                        >
                            {processing ? 'INSCRIPTION...' : 'VALIDER L\'INSCRIPTION'}
                            <ChevronRight className="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    );
}

function ChevronRight({ className }) {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            className={className}
        >
            <path d="m9 18 6-6-6-6" />
        </svg>
    );
}

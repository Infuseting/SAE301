import React, { useState, useMemo } from 'react';
import { useForm, Link } from '@inertiajs/react';
import { X, Search, Users, UserPlus, Check, AlertCircle, Info } from 'lucide-react';
import Modal from '@/Components/Modal';

/**
 * Validates a team for a competitive race.
 * All members must be in the same age category, and that category must be in the accepted list.
 * 
 * @param {Array} members - Array of team members with age property
 * @param {Array} acceptedCategories - Array of accepted age categories with age_min, age_max
 * @returns {Object} - { isValid: boolean, errors: string[], category: string|null }
 */
function validateCompetitiveTeam(members, acceptedCategories) {
    const result = {
        isValid: true,
        errors: [],
        category: null,
        memberCategories: [],
    };

    if (!members || members.length === 0) {
        result.isValid = false;
        result.errors.push("L'équipe n'a pas de membres.");
        return result;
    }

    if (!acceptedCategories || acceptedCategories.length === 0) {
        result.isValid = false;
        result.errors.push("Aucune catégorie d'âge n'est définie pour cette course.");
        return result;
    }

    // Determine each member's category
    const memberCategories = members.map(member => {
        if (member.age === null || member.age === undefined) {
            return { member, category: null, error: "Date de naissance non renseignée" };
        }

        const matchingCategory = acceptedCategories.find(cat => {
            const minAge = cat.age_min;
            const maxAge = cat.age_max !== null ? cat.age_max : Infinity;
            return member.age >= minAge && member.age <= maxAge;
        });

        return {
            member,
            category: matchingCategory ? matchingCategory.nom : null,
            categoryId: matchingCategory ? matchingCategory.id : null,
            error: matchingCategory ? null : `Âge ${member.age} ans non accepté pour cette course`,
        };
    });

    result.memberCategories = memberCategories;

    // Check for members without valid categories
    const invalidMembers = memberCategories.filter(mc => mc.category === null);
    if (invalidMembers.length > 0) {
        result.isValid = false;
        invalidMembers.forEach(mc => {
            const name = `${mc.member.first_name} ${mc.member.last_name}`;
            result.errors.push(`${name}: ${mc.error}`);
        });
        return result;
    }

    // Check if all members are in the same category
    const uniqueCategories = [...new Set(memberCategories.map(mc => mc.category))];
    if (uniqueCategories.length > 1) {
        result.isValid = false;
        result.errors.push(`Tous les membres doivent être dans la même catégorie d'âge. Catégories présentes: ${uniqueCategories.join(', ')}`);
        
        // Detail per member
        memberCategories.forEach(mc => {
            const name = `${mc.member.first_name} ${mc.member.last_name}`;
            result.errors.push(`${name}: ${mc.category} (${mc.member.age} ans)`);
        });
        return result;
    }

    result.category = uniqueCategories[0];
    return result;
}

/**
 * Validates a team for a leisure race.
 * Rules:
 * - All participants must be at least A years old
 * - If any participant is under B years old, the team must include someone at least C years old
 * - OR all participants must be at least B years old
 * 
 * @param {Array} members - Array of team members with age property
 * @param {number} ageA - Minimum age for all participants
 * @param {number} ageB - Intermediate age threshold
 * @param {number} ageC - Supervisor age requirement
 * @returns {Object} - { isValid: boolean, errors: string[], warnings: string[] }
 */
function validateLeisureTeam(members, ageA, ageB, ageC) {
    const result = {
        isValid: true,
        errors: [],
        warnings: [],
        needsSupervisor: false,
        hasSupervisor: false,
    };

    if (!members || members.length === 0) {
        result.isValid = false;
        result.errors.push("L'équipe n'a pas de membres.");
        return result;
    }

    // Check if leisure rules are defined
    if (ageA === null || ageA === undefined || 
        ageB === null || ageB === undefined || 
        ageC === null || ageC === undefined) {
        // No leisure rules defined, allow all
        return result;
    }

    // Check members with missing birth dates
    const membersWithoutAge = members.filter(m => m.age === null || m.age === undefined);
    if (membersWithoutAge.length > 0) {
        result.isValid = false;
        membersWithoutAge.forEach(m => {
            const name = `${m.first_name} ${m.last_name}`;
            result.errors.push(`${name}: Date de naissance non renseignée`);
        });
        return result;
    }

    // Rule 1: All participants must be at least A years old
    const membersBelowMinAge = members.filter(m => m.age < ageA);
    if (membersBelowMinAge.length > 0) {
        result.isValid = false;
        membersBelowMinAge.forEach(m => {
            const name = `${m.first_name} ${m.last_name}`;
            result.errors.push(`${name} (${m.age} ans): Âge minimum requis: ${ageA} ans`);
        });
        return result;
    }

    // Check if any member is under B years old
    const membersBelowB = members.filter(m => m.age < ageB);
    result.needsSupervisor = membersBelowB.length > 0;

    // Check if there's a supervisor (someone at least C years old)
    const supervisors = members.filter(m => m.age >= ageC);
    result.hasSupervisor = supervisors.length > 0;

    // Rule 2: If any participant is under B, team must have someone at least C
    if (result.needsSupervisor && !result.hasSupervisor) {
        result.isValid = false;
        result.errors.push(`L'équipe contient des membres de moins de ${ageB} ans et nécessite un accompagnateur d'au moins ${ageC} ans.`);
        membersBelowB.forEach(m => {
            const name = `${m.first_name} ${m.last_name}`;
            result.errors.push(`${name} (${m.age} ans): Nécessite un accompagnateur`);
        });
    }

    // Add info warning if supervisor is present
    if (result.needsSupervisor && result.hasSupervisor) {
        const supervisorNames = supervisors.map(s => `${s.first_name} ${s.last_name}`).join(', ');
        result.warnings.push(`Accompagnateur(s): ${supervisorNames}`);
    }

    return result;
}

/**
 * TeamRegistrationModal Component
 * Handles team registration for races with age validation
 * 
 * @param {Object} props - Component props
 * @param {boolean} props.isOpen - Whether modal is open
 * @param {Function} props.onClose - Close handler
 * @param {Array} props.teams - User's teams with members array
 * @param {number} props.minRunners - Minimum runners per team
 * @param {number} props.maxRunners - Maximum runners per team
 * @param {number} props.raceId - Race ID
 * @param {Object} props.racePrices - Pricing info { major, minor, adherent }
 * @param {boolean} props.isCompetitive - Whether race is competitive type
 * @param {Array} props.ageCategories - Accepted age categories for competitive races
 * @param {number} props.leisureAgeMin - Age A for leisure races
 * @param {number} props.leisureAgeIntermediate - Age B for leisure races
 * @param {number} props.leisureAgeSupervisor - Age C for leisure races
 * @param {number} props.maxTeams - Maximum teams allowed
 * @param {number} props.maxParticipants - Maximum participants allowed
 * @param {number} props.currentTeamsCount - Current registered teams count
 * @param {number} props.currentParticipantsCount - Current registered participants count
 */
export default function TeamRegistrationModal({ 
    isOpen, 
    onClose, 
    teams = [], 
    minRunners, 
    maxRunners, 
    raceId, 
    racePrices = {}, 
    isCompetitive = false, 
    ageCategories = [],
    leisureAgeMin = null,
    leisureAgeIntermediate = null,
    leisureAgeSupervisor = null,
    maxTeams = 100, 
    maxParticipants = 100, 
    currentTeamsCount = 0, 
    currentParticipantsCount = 0 
}) {
    const [searchQuery, setSearchQuery] = useState('');
    const { data, setData, post, processing, errors, reset } = useForm({
        team_id: null,
    });

    // Check if limits are reached
    const isTeamsLimitReached = currentTeamsCount >= maxTeams;
    const isParticipantsLimitReached = currentParticipantsCount >= maxParticipants;

    /**
     * Validate team based on race type and age rules
     * 
     * @param {Object} team - Team object with members array
     * @returns {Object} - Validation result with isValid, errors, warnings, category
     */
    const validateTeam = (team) => {
        if (!team || !team.members) {
            return { isValid: false, errors: ["Équipe invalide"], warnings: [] };
        }

        const membersCount = team.members.length;
        const baseValidation = {
            isValid: true,
            errors: [],
            warnings: [],
            category: null,
        };

        // Check team size
        if (membersCount < minRunners) {
            baseValidation.isValid = false;
            if (minRunners === maxRunners) {
                baseValidation.errors.push(`L'équipe doit avoir exactement ${maxRunners} membre(s) (actuellement ${membersCount})`);
            } else {
                baseValidation.errors.push(`L'équipe doit avoir au moins ${minRunners} membre(s) (actuellement ${membersCount})`);
            }
        }

        if (membersCount > maxRunners) {
            baseValidation.isValid = false;
            baseValidation.errors.push(`L'équipe ne peut pas avoir plus de ${maxRunners} membre(s) (actuellement ${membersCount})`);
        }

        // Age validation based on race type
        let ageValidation;
        if (isCompetitive) {
            ageValidation = validateCompetitiveTeam(team.members, ageCategories);
            baseValidation.category = ageValidation.category;
        } else {
            ageValidation = validateLeisureTeam(
                team.members, 
                leisureAgeMin, 
                leisureAgeIntermediate, 
                leisureAgeSupervisor
            );
        }

        // Merge validations
        if (!ageValidation.isValid) {
            baseValidation.isValid = false;
            baseValidation.errors = [...baseValidation.errors, ...ageValidation.errors];
        }
        baseValidation.warnings = [...baseValidation.warnings, ...(ageValidation.warnings || [])];

        // Check if adding this team would exceed limits
        const wouldExceedTeamLimit = isTeamsLimitReached;
        const wouldExceedParticipantLimit = (currentParticipantsCount + membersCount) > maxParticipants;
        
        if (wouldExceedTeamLimit) {
            baseValidation.isValid = false;
            baseValidation.errors.push(`Limite d'équipes atteinte (${currentTeamsCount}/${maxTeams})`);
        }
        
        if (wouldExceedParticipantLimit) {
            baseValidation.isValid = false;
            baseValidation.errors.push(`Limite de participants dépassée (${currentParticipantsCount + membersCount} > ${maxParticipants})`);
        }

        return baseValidation;
    };

    // Calculate validation for each team
    const teamsWithValidation = useMemo(() => {
        return teams.map(team => ({
            ...team,
            validation: validateTeam(team),
        }));
    }, [teams, minRunners, maxRunners, isCompetitive, ageCategories, leisureAgeMin, leisureAgeIntermediate, leisureAgeSupervisor, currentTeamsCount, currentParticipantsCount, maxTeams, maxParticipants]);

    const filteredTeams = useMemo(() => {
        return teamsWithValidation.filter(team =>
            team.name.toLowerCase().includes(searchQuery.toLowerCase())
        );
    }, [teamsWithValidation, searchQuery]);

    /**
     * Handle team selection
     * 
     * @param {number} teamId - Team ID to select
     * @param {boolean} isValid - Whether team is valid for selection
     */
    const handleSelectTeam = (teamId, isValid) => {
        if (isValid) {
            setData('team_id', teamId);
        }
    };

    const selectedTeam = teamsWithValidation.find(t => t.id === data.team_id);
    const isValid = selectedTeam && selectedTeam.validation.isValid;

    /**
     * Handle form submission
     * 
     * @param {Event} e - Form event
     */
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

                <div className="p-8 space-y-6">
                    {/* Info banner for race type */}
                    <div className={`p-4 rounded-xl border-2 flex items-start gap-3 ${
                        isCompetitive 
                            ? 'bg-blue-50 border-blue-200' 
                            : 'bg-emerald-50 border-emerald-200'
                    }`}>
                        <Info className={`w-5 h-5 flex-shrink-0 mt-0.5 ${
                            isCompetitive ? 'text-blue-500' : 'text-emerald-500'
                        }`} />
                        <div>
                            <h4 className={`font-black text-sm uppercase ${
                                isCompetitive ? 'text-blue-900' : 'text-emerald-900'
                            }`}>
                                Course {isCompetitive ? 'Compétitive' : 'Loisir'}
                            </h4>
                            <p className={`text-xs font-medium ${
                                isCompetitive ? 'text-blue-700' : 'text-emerald-700'
                            }`}>
                                {isCompetitive 
                                    ? 'Tous les membres de l\'équipe doivent être dans la même catégorie d\'âge parmi celles acceptées.'
                                    : leisureAgeMin !== null 
                                        ? `Règles d'âge: minimum ${leisureAgeMin} ans. Les équipes avec des membres de moins de ${leisureAgeIntermediate} ans doivent avoir un accompagnateur d'au moins ${leisureAgeSupervisor} ans.`
                                        : 'Pas de restriction d\'âge spécifique.'
                                }
                            </p>
                        </div>
                    </div>

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
                        <a
                            href={`${route('team.create')}?redirect_uri=${encodeURIComponent(typeof window !== 'undefined' ? window.location.href : '')}`}
                            className="bg-emerald-500 hover:bg-emerald-600 text-white px-6 rounded-xl font-black text-xs uppercase tracking-widest flex items-center gap-2 transition-colors shadow-lg shadow-emerald-200"
                        >
                            <UserPlus className="w-4 h-4" />
                            Créer
                        </a>
                    </div>

                    {/* Team List */}
                    <div className="space-y-4 max-h-80 overflow-y-auto pr-2 custom-scrollbar">
                        {filteredTeams.length > 0 ? (
                            filteredTeams.map(team => {
                                const { isValid: teamIsValid, errors: teamErrors, warnings: teamWarnings, category } = team.validation;

                                return (
                                    <div
                                        key={team.id}
                                        onClick={() => handleSelectTeam(team.id, teamIsValid)}
                                        className={`p-4 rounded-xl border-2 transition-all group relative 
                                            ${!teamIsValid ? 'opacity-70 cursor-not-allowed bg-gray-50 border-gray-200' : 'cursor-pointer'}
                                            ${data.team_id === team.id
                                                ? 'border-blue-500 bg-blue-50/50'
                                                : teamIsValid ? 'border-gray-100 hover:border-blue-200 hover:bg-gray-50' : ''
                                            }`}
                                    >
                                        <div className="flex items-start justify-between">
                                            <div className="flex items-center gap-4">
                                                <div className={`w-10 h-10 rounded-lg flex items-center justify-center font-black text-lg ${
                                                    data.team_id === team.id 
                                                        ? 'bg-blue-500 text-white' 
                                                        : teamIsValid
                                                            ? 'bg-gray-100 text-gray-400 group-hover:bg-blue-100 group-hover:text-blue-600'
                                                            : 'bg-red-100 text-red-400'
                                                }`}>
                                                    {team.name[0]}
                                                </div>
                                                <div>
                                                    <h4 className={`font-black uppercase italic ${
                                                        data.team_id === team.id ? 'text-blue-900' : 'text-gray-700'
                                                    }`}>
                                                        {team.name}
                                                    </h4>
                                                    <p className="text-xs font-bold text-gray-400 uppercase tracking-wider">
                                                        {team.members_count} Membre{team.members_count !== 1 ? 's' : ''}
                                                        {isCompetitive && category && (
                                                            <span className="ml-2 text-blue-500">• {category}</span>
                                                        )}
                                                    </p>
                                                </div>
                                            </div>

                                            {data.team_id === team.id && (
                                                <div className="bg-blue-500 text-white p-1 rounded-full">
                                                    <Check className="w-4 h-4" />
                                                </div>
                                            )}
                                        </div>

                                        {/* Errors */}
                                        {teamErrors.length > 0 && (
                                            <div className="mt-3 p-3 bg-red-50 rounded-lg border border-red-100">
                                                <p className="text-xs font-black text-red-700 uppercase mb-1">Non éligible</p>
                                                <ul className="text-xs text-red-600 space-y-0.5">
                                                    {teamErrors.slice(0, 3).map((err, idx) => (
                                                        <li key={idx}>• {err}</li>
                                                    ))}
                                                    {teamErrors.length > 3 && (
                                                        <li className="italic">... et {teamErrors.length - 3} autre(s) problème(s)</li>
                                                    )}
                                                </ul>
                                            </div>
                                        )}

                                        {/* Warnings */}
                                        {teamWarnings.length > 0 && teamIsValid && (
                                            <div className="mt-3 p-3 bg-amber-50 rounded-lg border border-amber-100">
                                                <p className="text-xs font-black text-amber-700 uppercase mb-1">Information</p>
                                                <ul className="text-xs text-amber-600 space-y-0.5">
                                                    {teamWarnings.map((warn, idx) => (
                                                        <li key={idx}>• {warn}</li>
                                                    ))}
                                                </ul>
                                            </div>
                                        )}
                                    </div>
                                );
                            })
                        ) : (
                            <div className="text-center py-8 text-gray-400">
                                <Users className="w-12 h-12 mx-auto mb-3 opacity-20" />
                                <p className="font-medium">Aucune équipe trouvée</p>
                                <p className="text-sm">Créez une équipe pour vous inscrire</p>
                            </div>
                        )}
                    </div>

                    {/* Options & Validation */}
                    {selectedTeam && (
                        <div className="bg-gray-50 rounded-2xl p-6 space-y-6 border border-gray-100">
                            <div className="grid grid-cols-2 gap-6">
                                <div>
                                    <p className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Coureurs</p>
                                    <div className={`flex items-baseline gap-2 ${selectedTeam.validation.isValid ? 'text-emerald-600' : 'text-red-500'}`}>
                                        <span className="text-3xl font-black italic">{selectedTeam.members_count}</span>
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
                                                    if (!selectedTeam) return racePrices.major * selectedTeam.members_count;
                                                    
                                                    const licensedCount = selectedTeam.licensed_members_count || 0;
                                                    const nonLicensedCount = selectedTeam.members_count - licensedCount;
                                                    
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
                                                const nonLicensedCount = selectedTeam.members_count - licensedCount;
                                                
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

                                {!selectedTeam.validation.isValid && (
                                    <div className="flex items-center gap-2 text-red-500 text-xs font-bold max-w-[50%] text-right bg-red-50 px-3 py-2 rounded-lg">
                                        <AlertCircle className="w-4 h-4 flex-shrink-0" />
                                        <span>Équipe non éligible - Voir les erreurs ci-dessus</span>
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
                            className={`px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all shadow-lg flex items-center gap-2 ${
                                isValid && !processing
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

/**
 * ChevronRight icon component
 * 
 * @param {Object} props - Component props
 * @param {string} props.className - CSS class name
 * @returns {JSX.Element} SVG icon element
 */
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

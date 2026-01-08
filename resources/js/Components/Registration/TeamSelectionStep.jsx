import React from 'react';
import { Users, UserPlus, AlertCircle, Check, X } from 'lucide-react';

/**
 * Team selection step - choose existing team or create new one.
 * Shows user's compatible teams and option to create temporary team.
 * 
 * @param {object} race - Race data
 * @param {object} eligibility - Eligibility data with compatible teams
 * @param {object} registrationData - Current registration data
 * @param {function} updateRegistrationData - Update handler
 * @param {function} onNext - Handler for proceeding to next step
 * @param {object} translations - Translation strings
 */
export default function TeamSelectionStep({
    race,
    eligibility,
    registrationData,
    updateRegistrationData,
    onNext,
    translations
}) {
    const teams = eligibility?.compatible_teams || [];
    const compatibleTeams = teams.filter(t => t.is_compatible);
    const incompatibleTeams = teams.filter(t => !t.is_compatible);

    const handleSelectTeam = (team) => {
        updateRegistrationData({
            selectedTeam: team,
            isTemporaryTeam: false,
        });
        onNext();
    };

    const handleCreateTemporaryTeam = () => {
        updateRegistrationData({
            selectedTeam: null,
            isTemporaryTeam: true,
            temporaryTeamMembers: [],
        });
        onNext();
    };

    return (
        <div className="space-y-6">
            <div className="text-center mb-6">
                <h3 className="text-xl font-black text-slate-800 uppercase tracking-tight">
                    Choisissez votre équipe
                </h3>
                <p className="text-slate-500 mt-2">
                    Sélectionnez une équipe existante ou créez-en une nouvelle
                </p>
            </div>

            {/* Create new team button */}
            <button
                onClick={handleCreateTemporaryTeam}
                className="w-full p-4 rounded-2xl border-2 border-dashed border-blue-300 bg-blue-50/50 hover:bg-blue-100 hover:border-blue-400 transition-all flex items-center gap-4"
            >
                <div className="p-3 rounded-xl bg-blue-500 text-white">
                    <UserPlus className="h-6 w-6" />
                </div>
                <div className="text-left">
                    <h4 className="font-bold text-blue-800">
                        Créer une équipe temporaire
                    </h4>
                    <p className="text-blue-600/70 text-sm">
                        Composez une équipe uniquement pour cette course
                    </p>
                </div>
            </button>

            {/* Compatible teams */}
            {compatibleTeams.length > 0 && (
                <div className="space-y-3">
                    <h4 className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                        Vos équipes compatibles
                    </h4>
                    {compatibleTeams.map(team => (
                        <button
                            key={team.id}
                            onClick={() => handleSelectTeam(team)}
                            className={`
                                w-full p-4 rounded-2xl border-2 text-left transition-all
                                hover:border-emerald-500 hover:shadow-lg
                                ${registrationData.selectedTeam?.id === team.id
                                    ? 'border-emerald-500 bg-emerald-50'
                                    : 'border-slate-200 bg-white hover:shadow-emerald-100'
                                }
                            `}
                        >
                            <div className="flex items-center gap-4">
                                {team.image ? (
                                    <img
                                        src={`/storage/${team.image}`}
                                        alt={team.name}
                                        className="w-12 h-12 rounded-xl object-cover"
                                    />
                                ) : (
                                    <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center">
                                        <Users className="h-6 w-6 text-slate-400" />
                                    </div>
                                )}
                                <div className="flex-1">
                                    <h5 className="font-bold text-slate-800">{team.name}</h5>
                                    <p className="text-slate-500 text-sm">
                                        {team.member_count} membre{team.member_count > 1 ? 's' : ''}
                                    </p>
                                </div>
                                <div className="flex items-center gap-1 px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">
                                    <Check className="h-3 w-3" />
                                    Compatible
                                </div>
                            </div>
                            {/* Member avatars */}
                            {team.members && team.members.length > 0 && (
                                <div className="flex items-center gap-2 mt-3 pl-16">
                                    <div className="flex -space-x-2">
                                        {team.members.slice(0, 5).map((member, idx) => (
                                            <div
                                                key={member.id}
                                                className="w-8 h-8 rounded-full bg-slate-200 border-2 border-white flex items-center justify-center text-xs font-bold text-slate-500"
                                                title={member.name}
                                            >
                                                {member.profile_photo_url ? (
                                                    <img
                                                        src={member.profile_photo_url}
                                                        alt={member.name}
                                                        className="w-full h-full rounded-full object-cover"
                                                    />
                                                ) : (
                                                    member.name?.charAt(0)?.toUpperCase()
                                                )}
                                            </div>
                                        ))}
                                        {team.members.length > 5 && (
                                            <div className="w-8 h-8 rounded-full bg-slate-300 border-2 border-white flex items-center justify-center text-xs font-bold text-slate-600">
                                                +{team.members.length - 5}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}
                        </button>
                    ))}
                </div>
            )}

            {/* Incompatible teams (grayed out) */}
            {incompatibleTeams.length > 0 && (
                <div className="space-y-3">
                    <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">
                        Équipes incompatibles
                    </h4>
                    {incompatibleTeams.map(team => (
                        <div
                            key={team.id}
                            className="w-full p-4 rounded-2xl border-2 border-slate-100 bg-slate-50 opacity-60"
                        >
                            <div className="flex items-center gap-4">
                                <div className="w-12 h-12 rounded-xl bg-slate-200 flex items-center justify-center">
                                    <Users className="h-6 w-6 text-slate-400" />
                                </div>
                                <div className="flex-1">
                                    <h5 className="font-bold text-slate-500">{team.name}</h5>
                                    <p className="text-slate-400 text-sm">
                                        {team.member_count} membre{team.member_count > 1 ? 's' : ''}
                                    </p>
                                </div>
                                <div className="flex items-center gap-1 px-3 py-1 bg-red-100 text-red-600 rounded-full text-xs font-bold">
                                    <X className="h-3 w-3" />
                                    Incompatible
                                </div>
                            </div>
                            {team.errors && team.errors.length > 0 && (
                                <div className="flex items-center gap-2 mt-2 pl-16 text-xs text-red-500">
                                    <AlertCircle className="h-3 w-3" />
                                    {team.errors[0]}
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {/* No teams message */}
            {teams.length === 0 && (
                <div className="text-center py-8 text-slate-500">
                    <Users className="h-12 w-12 mx-auto mb-3 text-slate-300" />
                    <p>Vous n'avez pas encore d'équipe.</p>
                    <p className="text-sm">Créez une équipe temporaire pour cette course.</p>
                </div>
            )}
        </div>
    );
}

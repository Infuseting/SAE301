import React from 'react';
import { Check, Users, Calendar, MapPin, CreditCard } from 'lucide-react';

/**
 * Registration confirmation step - summary before final submission.
 * 
 * @param {object} race - Race data
 * @param {object} user - Current user
 * @param {object} registrationData - Current registration data
 * @param {object} translations - Translation strings
 */
export default function RegistrationConfirmStep({ race, user, registrationData, translations }) {
    const members = registrationData.temporaryTeamMembers || [];
    const totalMembers = registrationData.isCreatorParticipating ? members.length + 1 : members.length;

    // Calculate price (if available)
    const pricePerPerson = race?.price_major || race?.price || 0;
    const totalPrice = pricePerPerson * totalMembers;

    return (
        <div className="space-y-6">
            <div className="text-center mb-6">
                <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-100 flex items-center justify-center">
                    <Check className="h-8 w-8 text-emerald-600" />
                </div>
                <h3 className="text-xl font-black text-slate-800 uppercase tracking-tight">
                    Confirmer l'inscription
                </h3>
                <p className="text-slate-500 mt-2">
                    Vérifiez les informations avant de valider
                </p>
            </div>

            {/* Race info card */}
            <div className="p-4 rounded-2xl bg-blue-50 border border-blue-100">
                <h4 className="font-bold text-blue-900 text-lg">{race?.title || race?.race_name}</h4>
                <div className="flex flex-wrap gap-4 mt-3 text-sm text-blue-700">
                    {race?.race_date_start && (
                        <div className="flex items-center gap-1">
                            <Calendar className="h-4 w-4" />
                            {new Date(race.race_date_start).toLocaleDateString('fr-FR', {
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric'
                            })}
                        </div>
                    )}
                    {race?.location && (
                        <div className="flex items-center gap-1">
                            <MapPin className="h-4 w-4" />
                            {race.location}
                        </div>
                    )}
                </div>
            </div>

            {/* Team summary */}
            <div className="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-3">
                <div className="flex items-center justify-between">
                    <h4 className="font-bold text-slate-700 flex items-center gap-2">
                        <Users className="h-4 w-4" />
                        {registrationData.isTemporaryTeam ? 'Équipe temporaire' : 'Équipe'}
                    </h4>
                    <span className="text-sm text-slate-500">{totalMembers} membre{totalMembers > 1 ? 's' : ''}</span>
                </div>

                {/* Team members list */}
                <div className="space-y-2 pt-2 border-t border-slate-200">
                    {/* Creator if participating */}
                    {registrationData.isCreatorParticipating && (
                        <div className="flex items-center gap-3">
                            <div className="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center text-sm font-bold">
                                {user?.name?.charAt(0)?.toUpperCase()}
                            </div>
                            <span className="font-medium text-slate-700">{user?.name}</span>
                            <span className="text-xs text-emerald-600 bg-emerald-100 px-2 py-0.5 rounded-full">Chef d'équipe</span>
                        </div>
                    )}

                    {/* Selected team members or temporary team members */}
                    {registrationData.selectedTeam?.members
                        ?.filter(member => member.id !== user?.id) // Filter out creator
                        ?.map((member, idx) => (
                            <div key={idx} className="flex items-center gap-3">
                                <div className="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-sm font-bold text-slate-500">
                                    {member.name?.charAt(0)?.toUpperCase()}
                                </div>
                                <span className="text-slate-600">{member.name}</span>
                            </div>
                        ))}

                    {/* Temporary team members */}
                    {members.map((member, idx) => (
                        <div key={idx} className="flex items-center gap-3">
                            <div className="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-sm font-bold text-slate-500">
                                {(member.name || member.email)?.charAt(0)?.toUpperCase() || '?'}
                            </div>
                            <div className="flex flex-col">
                                <span className="font-medium text-slate-700">{member.name || member.email}</span>
                                {member.name && member.email && member.name !== member.email && (
                                    <span className="text-[10px] text-slate-400 truncate">{member.email}</span>
                                )}
                            </div>
                            <div className="ml-auto flex items-center gap-2">
                                {member.status === 'pending_account' && (
                                    <span className="text-xs text-blue-600 bg-blue-100 px-2 py-0.5 rounded-full">
                                        Invitation envoyée
                                    </span>
                                )}
                                {member.status === 'pending' && (
                                    <span className="text-xs text-amber-600 bg-amber-100 px-2 py-0.5 rounded-full">
                                        En attente
                                    </span>
                                )}
                                {member.status === 'accepted' && (
                                    <span className="text-xs text-emerald-600 bg-emerald-100 px-2 py-0.5 rounded-full">
                                        Accepté
                                    </span>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Price summary */}
            {pricePerPerson > 0 && (
                <div className="p-4 rounded-2xl bg-emerald-50 border border-emerald-100">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <CreditCard className="h-5 w-5 text-emerald-600" />
                            <span className="font-bold text-emerald-800">Total à payer</span>
                        </div>
                        <div className="text-right">
                            <p className="text-2xl font-black text-emerald-700">{totalPrice.toFixed(2)} €</p>
                            <p className="text-xs text-emerald-600">{pricePerPerson.toFixed(2)} € × {totalMembers}</p>
                        </div>
                    </div>
                </div>
            )}

            {/* Disclaimer */}
            <div className="text-xs text-slate-400 text-center">
                En confirmant, vous acceptez les conditions générales de participation.
                {members.some(m => m.status === 'pending' || m.status === 'pending_account') && (
                    <span className="block mt-1 text-amber-600">
                        Note: Les membres en attente recevront une invitation par email.
                    </span>
                )}
            </div>
        </div>
    );
}

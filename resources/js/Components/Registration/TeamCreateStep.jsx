import React, { useState } from 'react';
import { UserPlus, X, Mail, Clock, Check, Search, AlertCircle } from 'lucide-react';

/**
 * Team creation step - create temporary team by adding members.
 * Supports adding existing users and inviting by email.
 * 
 * @param {object} race - Race data  
 * @param {object} user - Current user
 * @param {object} registrationData - Current registration data
 * @param {function} updateRegistrationData - Update handler
 * @param {object} translations - Translation strings
 */
export default function TeamCreateStep({
    race,
    user,
    registrationData,
    updateRegistrationData,
    translations
}) {
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [searching, setSearching] = useState(false);
    const [emailInput, setEmailInput] = useState('');
    const [error, setError] = useState(null);

    // Get team size limits from race params
    const minMembers = race?.team_params?.pae_nb_min || 2;
    const maxMembers = race?.team_params?.pae_nb_max || 5;

    // Account for whether creator is participating
    const effectiveMin = registrationData.isCreatorParticipating ? minMembers - 1 : minMembers;
    const effectiveMax = registrationData.isCreatorParticipating ? maxMembers - 1 : maxMembers;

    const members = registrationData.temporaryTeamMembers || [];

    // Search for users
    const handleSearch = async (query) => {
        setSearchQuery(query);
        if (query.length < 2) {
            setSearchResults([]);
            return;
        }

        setSearching(true);
        try {
            const response = await fetch(`/api/users/search?q=${encodeURIComponent(query)}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await response.json();
            // API returns array directly, filter out current user and already added members
            const usersList = Array.isArray(data) ? data : (data.users || data.data || []);
            const filtered = usersList.filter(u =>
                u.id !== user?.id &&
                !members.find(m => m.user_id === u.id)
            );
            setSearchResults(filtered);
        } catch (err) {
            console.error('Search error:', err);
            setSearchResults([]);
        } finally {
            setSearching(false);
        }
    };

    // Add existing user
    const addMember = (selectedUser) => {
        if (members.length >= effectiveMax) {
            setError(`Maximum ${effectiveMax} membres autorisés`);
            return;
        }

        const newMember = {
            user_id: selectedUser.id,
            email: selectedUser.email,
            name: selectedUser.name,
            profile_photo_url: selectedUser.profile_photo_url,
            status: 'pending',
        };

        updateRegistrationData({
            temporaryTeamMembers: [...members, newMember],
        });
        setSearchQuery('');
        setSearchResults([]);
        setError(null);
    };

    // Add by email (for non-existing users)
    const addByEmail = () => {
        if (!emailInput.trim() || !emailInput.includes('@')) {
            setError('Veuillez entrer une adresse email valide');
            return;
        }

        if (members.find(m => m.email === emailInput)) {
            setError('Cette personne est déjà dans l\'équipe');
            return;
        }

        if (members.length >= effectiveMax) {
            setError(`Maximum ${effectiveMax} membres autorisés`);
            return;
        }

        const newMember = {
            user_id: null,
            email: emailInput,
            name: emailInput.split('@')[0],
            status: 'pending_account',
        };

        updateRegistrationData({
            temporaryTeamMembers: [...members, newMember],
        });
        setEmailInput('');
        setError(null);
    };

    // Remove member
    const removeMember = (index) => {
        const updated = members.filter((_, i) => i !== index);
        updateRegistrationData({ temporaryTeamMembers: updated });
    };

    const getMemberStatus = (status) => {
        switch (status) {
            case 'confirmed':
                return { icon: Check, color: 'text-emerald-500', label: 'Confirmé' };
            case 'pending':
                return { icon: Clock, color: 'text-amber-500', label: 'En attente' };
            case 'pending_account':
                return { icon: Mail, color: 'text-blue-500', label: 'Invitation envoyée' };
            default:
                return { icon: Clock, color: 'text-slate-400', label: 'En attente' };
        }
    };

    return (
        <div className="space-y-6">
            <div className="text-center mb-4">
                <h3 className="text-xl font-black text-slate-800 uppercase tracking-tight">
                    Créer votre équipe
                </h3>
                <p className="text-slate-500 mt-2">
                    Ajoutez {effectiveMin} à {effectiveMax} membres à votre équipe
                </p>
            </div>

            {/* Creator indicator */}
            {registrationData.isCreatorParticipating && (
                <div className="p-4 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center gap-3">
                    <div className="w-10 h-10 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold">
                        {user?.name?.charAt(0)?.toUpperCase() || 'V'}
                    </div>
                    <div className="flex-1">
                        <p className="font-bold text-emerald-800">{user?.name}</p>
                        <p className="text-emerald-600 text-xs">Vous (Chef d'équipe)</p>
                    </div>
                    <div className="flex items-center gap-1 px-2 py-1 bg-emerald-500 text-white rounded-full text-xs font-bold">
                        <Check className="h-3 w-3" />
                        Inscrit
                    </div>
                </div>
            )}

            {/* Error message */}
            {error && (
                <div className="p-3 bg-red-50 border border-red-100 rounded-xl flex items-center gap-2 text-sm text-red-700">
                    <AlertCircle className="h-4 w-4" />
                    {error}
                </div>
            )}

            {/* Current members list */}
            {members.length > 0 && (
                <div className="space-y-2">
                    <h4 className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                        Membres ajoutés ({members.length}/{effectiveMax})
                    </h4>
                    {members.map((member, index) => {
                        const status = getMemberStatus(member.status);
                        const StatusIcon = status.icon;

                        return (
                            <div
                                key={index}
                                className="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center gap-3"
                            >
                                <div className="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center text-sm font-bold text-slate-500">
                                    {member.profile_photo_url ? (
                                        <img
                                            src={member.profile_photo_url}
                                            alt={member.name}
                                            className="w-full h-full rounded-full object-cover"
                                        />
                                    ) : (
                                        member.name?.charAt(0)?.toUpperCase() || '?'
                                    )}
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="font-medium text-slate-700 truncate">{member.name}</p>
                                    <p className="text-slate-400 text-xs truncate">{member.email}</p>
                                </div>
                                <div className={`flex items-center gap-1 text-xs ${status.color}`}>
                                    <StatusIcon className="h-3 w-3" />
                                    <span className="hidden sm:inline">{status.label}</span>
                                </div>
                                <button
                                    onClick={() => removeMember(index)}
                                    className="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                >
                                    <X className="h-4 w-4" />
                                </button>
                            </div>
                        );
                    })}
                </div>
            )}

            {/* Add member section */}
            {members.length < effectiveMax && (
                <div className="space-y-4">
                    {/* Search existing users */}
                    <div className="relative">
                        <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                            <Search className="inline h-3 w-3 mr-1" />
                            Rechercher un utilisateur
                        </label>
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => handleSearch(e.target.value)}
                            placeholder="Nom, email..."
                            className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all"
                        />

                        {/* Search results - positioned absolute */}
                        {searchResults.length > 0 && (
                            <div className="absolute z-50 left-0 right-0 mt-2 border border-slate-200 rounded-xl overflow-hidden max-h-48 overflow-y-auto bg-white shadow-xl">
                                {searchResults.map(result => (
                                    <button
                                        key={result.id}
                                        onClick={() => addMember(result)}
                                        className="w-full p-3 flex items-center gap-3 hover:bg-blue-50 transition-colors border-b border-slate-100 last:border-0"
                                    >
                                        <div className="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-sm font-bold">
                                            {result.profile_photo_url ? (
                                                <img
                                                    src={result.profile_photo_url}
                                                    alt={result.name}
                                                    className="w-full h-full rounded-full object-cover"
                                                />
                                            ) : (
                                                result.name?.charAt(0)?.toUpperCase()
                                            )}
                                        </div>
                                        <div className="text-left">
                                            <p className="font-medium text-slate-700">{result.name}</p>
                                            <p className="text-slate-400 text-xs">{result.email}</p>
                                        </div>
                                    </button>
                                ))}
                            </div>
                        )}

                        {searching && (
                            <p className="mt-2 text-sm text-slate-400">Recherche...</p>
                        )}
                    </div>

                    {/* Separator */}
                    <div className="flex items-center gap-4">
                        <div className="flex-1 h-px bg-slate-200"></div>
                        <span className="text-xs text-slate-400 uppercase tracking-wider">ou</span>
                        <div className="flex-1 h-px bg-slate-200"></div>
                    </div>

                    {/* Invite by email */}
                    <div>
                        <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                            <Mail className="inline h-3 w-3 mr-1" />
                            Inviter par email
                        </label>
                        <div className="flex gap-2">
                            <input
                                type="email"
                                value={emailInput}
                                onChange={(e) => setEmailInput(e.target.value)}
                                placeholder="email@exemple.com"
                                className="flex-1 px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all"
                            />
                            <button
                                onClick={addByEmail}
                                disabled={!emailInput.trim()}
                                className="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <UserPlus className="h-5 w-5" />
                            </button>
                        </div>
                        <p className="mt-2 text-xs text-slate-400">
                            Un email d'invitation sera envoyé pour créer un compte
                        </p>
                    </div>
                </div>
            )}

            {/* Progress indicator */}
            <div className="pt-4 border-t border-slate-100">
                <div className="flex items-center justify-between text-sm">
                    <span className="text-slate-500">Progression</span>
                    <span className={`font-bold ${members.length >= effectiveMin ? 'text-emerald-600' : 'text-amber-600'
                        }`}>
                        {registrationData.isCreatorParticipating ? members.length + 1 : members.length} / {effectiveMin} minimum
                    </span>
                </div>
                <div className="mt-2 h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div
                        className={`h-full transition-all ${members.length >= effectiveMin ? 'bg-emerald-500' : 'bg-amber-500'
                            }`}
                        style={{
                            width: `${Math.min(100, ((registrationData.isCreatorParticipating ? members.length + 1 : members.length) / effectiveMin) * 100)}%`
                        }}
                    />
                </div>
            </div>
        </div>
    );
}
